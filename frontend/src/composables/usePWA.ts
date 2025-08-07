import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { offlineService } from '@/services/offlineService';

interface SyncStatus {
  isVisible: boolean;
  type: 'syncing' | 'success' | 'error' | 'pending' | null;
  message: string;
  progress: { current: number; total: number } | null;
}

interface StorageInfo {
  used: number;
  quota: number;
  percentage: number;
}

export function usePWA() {
  const { t } = useI18n();
  const isOnline = ref(navigator.onLine);
  const syncStatus = reactive<SyncStatus>({
    isVisible: false,
    type: null,
    message: '',
    progress: null
  });
  const storageInfo = reactive<StorageInfo>({
    used: 0,
    quota: 0,
    percentage: 0
  });

  const needRefresh = ref(false);
  const offlineReady = ref(false);
  
  const updateServiceWorker = () => {
    // Implementation for updating service worker
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.getRegistration().then(registration => {
        if (registration) {
          registration.update();
        }
      });
    }
  };

  // Handle online/offline status
  const handleOnline = () => {
    isOnline.value = true;
    syncPendingOperations();
  };

  const handleOffline = () => {
    isOnline.value = false;
    showSyncStatus('error', t('sync.offline'), null);
  };

  // Show sync status with auto-hide
  const showSyncStatus = (type: SyncStatus['type'], message: string, progress: SyncStatus['progress'] = null) => {
    syncStatus.isVisible = true;
    syncStatus.type = type;
    syncStatus.message = message;
    syncStatus.progress = progress;

    if (type === 'success') {
      setTimeout(() => {
        syncStatus.isVisible = false;
      }, 3000);
    }
  };

  // Submit form offline
  const submitFormOffline = async (formType: string, formData: any): Promise<string> => {
    try {
      const id = await offlineService.storeOfflineForm(formType, formData);
      showSyncStatus('pending', t('sync.queued'), null);
      
      // Try to sync immediately if online
      if (isOnline.value) {
        setTimeout(() => syncPendingOperations(), 1000);
      }
      
      return id;
    } catch (error) {
      console.error('Error storing offline form:', error);
      throw error;
    }
  };

  // Sync pending operations
  const syncPendingOperations = async () => {
    if (!isOnline.value) return;

    try {
      showSyncStatus('syncing', t('sync.syncing'), null);

      const pendingSync = await offlineService.getPendingSync();
      const offlineForms = await offlineService.getOfflineForms();
      const totalOperations = pendingSync.length + offlineForms.filter(f => !f.synced).length;

      if (totalOperations === 0) {
        syncStatus.isVisible = false;
        return;
      }

      let completed = 0;

      // Sync pending API operations
      for (const operation of pendingSync) {
        try {
          const response = await fetch(operation.url, {
            method: operation.method,
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: operation.data ? JSON.stringify(operation.data) : undefined
          });

          if (response.ok) {
            await offlineService.removePendingSync(operation.id);
            completed++;
            showSyncStatus('syncing', t('sync.syncing'), { current: completed, total: totalOperations });
          } else {
            throw new Error(`HTTP ${response.status}`);
          }
        } catch (error) {
          console.error('Sync operation failed:', error);
          // Increment retry count or remove after max retries
          if (operation.retries >= 3) {
            await offlineService.removePendingSync(operation.id);
          }
        }
      }

      // Sync offline forms
      for (const form of offlineForms.filter(f => !f.synced)) {
        try {
          const endpoint = getFormEndpoint(form.type);
          const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(form.data)
          });

          if (response.ok) {
            await offlineService.markFormSynced(form.id);
            completed++;
            showSyncStatus('syncing', t('sync.syncing'), { current: completed, total: totalOperations });
          } else {
            throw new Error(`HTTP ${response.status}`);
          }
        } catch (error) {
          console.error('Form sync failed:', error);
        }
      }

      showSyncStatus('success', t('sync.completed'), null);
    } catch (error) {
      console.error('Sync failed:', error);
      showSyncStatus('error', t('sync.failed'), null);
    }
  };

  // Get form endpoint based on type
  const getFormEndpoint = (formType: string): string => {
    const endpoints: Record<string, string> = {
      'customer': '/api/customers',
      'invoice': '/api/invoices',
      'inventory': '/api/inventory/items',
      'transaction': '/api/accounting/transactions'
    };
    return endpoints[formType] || '/api/forms';
  };

  // Retry sync operation
  const retrySyncOperation = () => {
    if (isOnline.value) {
      syncPendingOperations();
    }
  };

  // Clear old cached data
  const clearOldData = async () => {
    try {
      await offlineService.clearOldCache();
      await updateStorageInfo();
      showSyncStatus('success', t('storage.cleared'), null);
    } catch (error) {
      console.error('Error clearing old data:', error);
      showSyncStatus('error', t('storage.clearError'), null);
    }
  };

  // Update storage information
  const updateStorageInfo = async () => {
    try {
      const info = await offlineService.getStorageInfo();
      Object.assign(storageInfo, info);
    } catch (error) {
      console.error('Error getting storage info:', error);
    }
  };

  // Cache API response
  const cacheApiResponse = async (type: string, id: string, data: any) => {
    try {
      await offlineService.cacheData(type as any, id, data);
    } catch (error) {
      console.error('Error caching data:', error);
    }
  };

  // Get cached data
  const getCachedData = async (type: string, id?: string) => {
    try {
      return await offlineService.getCachedData(type as any, id);
    } catch (error) {
      console.error('Error getting cached data:', error);
      return [];
    }
  };

  // Initialize PWA
  const initializePWA = async () => {
    await offlineService.init();
    await updateStorageInfo();
    
    // Sync on startup if online
    if (isOnline.value) {
      setTimeout(() => syncPendingOperations(), 2000);
    }

    // Update storage info periodically
    setInterval(updateStorageInfo, 60000); // Every minute
  };

  // Setup event listeners
  onMounted(() => {
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    initializePWA();
  });

  onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
  });

  return {
    isOnline,
    syncStatus,
    storageInfo,
    needRefresh,
    offlineReady,
    updateServiceWorker,
    submitFormOffline,
    syncPendingOperations,
    retrySyncOperation,
    clearOldData,
    cacheApiResponse,
    getCachedData
  };
}