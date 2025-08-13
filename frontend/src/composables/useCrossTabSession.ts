import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { crossTabSessionManager } from '@/services/crossTabSessionManager';
import type { SessionData } from '@/services/crossTabSessionManager';
import { useNotifications } from '@/composables/useNotifications';

export interface CrossTabSessionOptions {
  autoSync?: boolean;
  conflictResolution?: 'auto' | 'manual';
  syncInterval?: number; // milliseconds
}

export function useCrossTabSession(options: CrossTabSessionOptions = {}) {
  const authStore = useAuthStore();
  const { showWarning, showError, showInfo } = useNotifications();
  
  const {
    autoSync = true,
    conflictResolution = 'auto',
    syncInterval = 5000 // 5 seconds
  } = options;

  // State
  const isInitialized = ref(false);
  const activeTabs = ref<string[]>([]);
  const lastSyncTime = ref<Date | null>(null);
  const syncInterval_id = ref<number | null>(null);
  const eventListeners = ref<Array<() => void>>([]);

  // Computed
  const tabCount = computed(() => activeTabs.value.length);
  const isMultiTab = computed(() => tabCount.value > 1);
  const currentTabId = computed(() => crossTabSessionManager.getSessionData().tabId);

  // Initialize cross-tab session management
  const initialize = async (): Promise<void> => {
    if (isInitialized.value) return;

    try {
      console.log('[useCrossTabSession] Initializing cross-tab session management');
      
      // Initialize the cross-tab session manager
      await crossTabSessionManager.initialize();
      
      // Set up event listeners
      setupEventListeners();
      
      // Sync with existing auth store data
      await syncWithAuthStore();
      
      // Start auto-sync if enabled
      if (autoSync) {
        startAutoSync();
      }
      
      isInitialized.value = true;
      console.log('[useCrossTabSession] Cross-tab session management initialized');
      
    } catch (error) {
      console.error('[useCrossTabSession] Failed to initialize:', error);
      throw error;
    }
  };

  // Setup event listeners for cross-tab communication
  const setupEventListeners = (): void => {
    // Listen for cross-tab logout events
    const handleCrossTabLogout = (event: CustomEvent) => {
      console.log('[useCrossTabSession] Received cross-tab logout event');
      handleLogoutFromOtherTab(event.detail.initiatingTab);
    };

    window.addEventListener('cross-tab-logout', handleCrossTabLogout as EventListener);
    eventListeners.value.push(() => {
      window.removeEventListener('cross-tab-logout', handleCrossTabLogout as EventListener);
    });

    // Listen for auth store changes
    const stopWatchingAuth = watch(
      () => authStore.isAuthenticated,
      (isAuthenticated) => {
        if (isAuthenticated) {
          syncAuthStoreToManager();
        } else {
          handleLocalLogout();
        }
      },
      { immediate: true }
    );

    eventListeners.value.push(stopWatchingAuth);

    // Watch for token changes
    const stopWatchingToken = watch(
      () => authStore.token,
      (newToken) => {
        if (newToken) {
          syncAuthStoreToManager();
        }
      }
    );

    eventListeners.value.push(stopWatchingToken);

    // Watch for user changes
    const stopWatchingUser = watch(
      () => authStore.user,
      (newUser) => {
        if (newUser) {
          syncAuthStoreToManager();
        }
      },
      { deep: true }
    );

    eventListeners.value.push(stopWatchingUser);
  };

  // Sync current auth store data to cross-tab manager
  const syncAuthStoreToManager = (): void => {
    if (!authStore.isAuthenticated) return;

    const sessionData: Partial<SessionData> = {
      userId: authStore.user?.id || null,
      token: authStore.token,
      expiresAt: authStore.sessionExpiry,
      isActive: authStore.isAuthenticated,
      metadata: {
        userAgent: navigator.userAgent,
        loginTime: authStore.user ? new Date() : null,
        refreshCount: 0
      }
    };

    crossTabSessionManager.updateSessionData(sessionData);
    console.log('[useCrossTabSession] Synced auth store to cross-tab manager');
  };

  // Sync cross-tab manager data to auth store
  const syncManagerToAuthStore = async (): Promise<void> => {
    const sessionData = crossTabSessionManager.getSessionData();
    
    if (sessionData.token && sessionData.userId && sessionData.isActive) {
      // Update auth store with session data
      authStore.token = sessionData.token;
      authStore.sessionExpiry = sessionData.expiresAt;
      
      // Fetch user data if we don't have it
      if (!authStore.user && sessionData.userId) {
        try {
          await authStore.fetchUser();
        } catch (error) {
          console.error('[useCrossTabSession] Failed to fetch user data:', error);
        }
      }
      
      console.log('[useCrossTabSession] Synced cross-tab manager to auth store');
    }
  };

  // Sync with auth store (bidirectional)
  const syncWithAuthStore = async (): Promise<void> => {
    // First, try to sync from manager to auth store (in case we have data from other tabs)
    await syncManagerToAuthStore();
    
    // Then sync from auth store to manager (in case this tab has newer data)
    syncAuthStoreToManager();
    
    // Update active tabs list
    updateActiveTabsList();
  };

  // Update active tabs list
  const updateActiveTabsList = (): void => {
    activeTabs.value = crossTabSessionManager.getActiveTabs();
  };

  // Handle logout initiated from another tab
  const handleLogoutFromOtherTab = async (initiatingTab: string): Promise<void> => {
    console.log(`[useCrossTabSession] Handling logout from tab ${initiatingTab}`);
    
    // Show notification to user
    showInfo(
      'Logged Out',
      'You have been logged out from another tab.',
      { duration: 3000 }
    );

    // Clear auth store without calling backend (already done by initiating tab)
    authStore.cleanupAuthState();
    
    // Update our state
    updateActiveTabsList();
  };

  // Handle logout initiated from this tab
  const handleLocalLogout = (): void => {
    console.log('[useCrossTabSession] Handling local logout');
    
    // Broadcast logout to other tabs
    crossTabSessionManager.broadcastLogout();
    
    // Update our state
    updateActiveTabsList();
  };

  // Start automatic synchronization
  const startAutoSync = (): void => {
    if (syncInterval_id.value) return;

    syncInterval_id.value = window.setInterval(async () => {
      try {
        await performSync();
      } catch (error) {
        console.error('[useCrossTabSession] Auto-sync failed:', error);
      }
    }, syncInterval);

    console.log(`[useCrossTabSession] Started auto-sync with ${syncInterval}ms interval`);
  };

  // Stop automatic synchronization
  const stopAutoSync = (): void => {
    if (syncInterval_id.value) {
      clearInterval(syncInterval_id.value);
      syncInterval_id.value = null;
      console.log('[useCrossTabSession] Stopped auto-sync');
    }
  };

  // Perform synchronization
  const performSync = async (): Promise<void> => {
    if (!isInitialized.value) return;

    try {
      // Check for conflicts
      const conflict = await crossTabSessionManager.detectSessionConflicts();
      
      if (conflict) {
        console.log('[useCrossTabSession] Session conflict detected:', conflict.reason);
        
        if (conflictResolution === 'auto') {
          await crossTabSessionManager.recoverFromConflict(conflict);
          await syncManagerToAuthStore();
          
          showWarning(
            'Session Synchronized',
            'Your session was automatically synchronized with other tabs.',
            { duration: 3000 }
          );
        } else {
          // Manual conflict resolution - show notification to user
          showWarning(
            'Session Conflict',
            `Session conflict detected: ${conflict.reason}. Please refresh the page if you experience issues.`,
            { duration: 5000 }
          );
        }
      }

      // Update active tabs
      updateActiveTabsList();
      
      // Update last sync time
      lastSyncTime.value = new Date();
      
    } catch (error) {
      console.error('[useCrossTabSession] Sync failed:', error);
    }
  };

  // Force synchronization
  const forceSync = async (): Promise<void> => {
    console.log('[useCrossTabSession] Forcing synchronization');
    await performSync();
  };

  // Request session lock for critical operations
  const requestLock = async (operation: string): Promise<boolean> => {
    return await crossTabSessionManager.requestSessionLock(operation);
  };

  // Release session lock
  const releaseLock = (operation: string): void => {
    crossTabSessionManager.releaseSessionLock(operation);
  };

  // Broadcast session update to other tabs
  const broadcastUpdate = (data: Partial<SessionData>): void => {
    crossTabSessionManager.broadcastSessionUpdate(data);
  };

  // Get session information
  const getSessionInfo = () => {
    return {
      sessionData: crossTabSessionManager.getSessionData(),
      activeTabs: activeTabs.value,
      tabCount: tabCount.value,
      isMultiTab: isMultiTab.value,
      currentTabId: currentTabId.value,
      lastSyncTime: lastSyncTime.value
    };
  };

  // Cleanup resources
  const cleanup = (): void => {
    console.log('[useCrossTabSession] Cleaning up resources');
    
    stopAutoSync();
    
    // Remove event listeners
    eventListeners.value.forEach(cleanup => cleanup());
    eventListeners.value = [];
    
    // Cleanup cross-tab manager
    crossTabSessionManager.cleanup();
    
    isInitialized.value = false;
  };

  // Lifecycle hooks
  onMounted(async () => {
    try {
      await initialize();
    } catch (error) {
      console.error('[useCrossTabSession] Failed to initialize on mount:', error);
    }
  });

  onUnmounted(() => {
    cleanup();
  });

  return {
    // State
    isInitialized,
    activeTabs,
    lastSyncTime,
    
    // Computed
    tabCount,
    isMultiTab,
    currentTabId,
    
    // Methods
    initialize,
    cleanup,
    forceSync,
    requestLock,
    releaseLock,
    broadcastUpdate,
    getSessionInfo,
    syncWithAuthStore,
    
    // Auto-sync control
    startAutoSync,
    stopAutoSync,
  };
}