import { offlineService } from './offlineService';
import { backgroundSyncService } from './backgroundSyncService';

interface SyncStrategy {
  cacheFirst: boolean;
  networkFirst: boolean;
  staleWhileRevalidate: boolean;
  networkOnly: boolean;
  cacheOnly: boolean;
}

interface DataSyncOptions {
  strategy: keyof SyncStrategy;
  maxAge?: number;
  priority?: 'critical' | 'normal' | 'low';
  retries?: number;
}

class OfflineFirstService {
  private syncStrategies: Record<string, DataSyncOptions> = {
    // Critical business data - cache first with background sync
    'customers': { strategy: 'cacheFirst', maxAge: 3600000, priority: 'critical' }, // 1 hour
    'invoices': { strategy: 'cacheFirst', maxAge: 1800000, priority: 'critical' }, // 30 minutes
    'inventory': { strategy: 'staleWhileRevalidate', maxAge: 900000, priority: 'normal' }, // 15 minutes
    'transactions': { strategy: 'cacheFirst', maxAge: 3600000, priority: 'critical' }, // 1 hour
    
    // Configuration data - cache first with longer TTL
    'settings': { strategy: 'cacheFirst', maxAge: 86400000, priority: 'normal' }, // 24 hours
    'templates': { strategy: 'cacheFirst', maxAge: 43200000, priority: 'normal' }, // 12 hours
    
    // Real-time data - network first
    'dashboard': { strategy: 'networkFirst', maxAge: 300000, priority: 'normal' }, // 5 minutes
    'reports': { strategy: 'networkFirst', maxAge: 600000, priority: 'normal' }, // 10 minutes
    
    // Static data - cache first with very long TTL
    'translations': { strategy: 'cacheFirst', maxAge: 604800000, priority: 'low' }, // 7 days
    'categories': { strategy: 'cacheFirst', maxAge: 86400000, priority: 'normal' } // 24 hours
  };

  // Get data with offline-first strategy
  async getData(type: string, id?: string, forceRefresh = false): Promise<any> {
    const options = this.syncStrategies[type] || { strategy: 'networkFirst', priority: 'normal' };
    
    switch (options.strategy) {
      case 'cacheFirst':
        return this.cacheFirstStrategy(type, id, options, forceRefresh);
      case 'networkFirst':
        return this.networkFirstStrategy(type, id, options);
      case 'staleWhileRevalidate':
        return this.staleWhileRevalidateStrategy(type, id, options);
      case 'networkOnly':
        return this.networkOnlyStrategy(type, id);
      case 'cacheOnly':
        return this.cacheOnlyStrategy(type, id);
      default:
        return this.networkFirstStrategy(type, id, options);
    }
  }

  // Cache-first strategy: Check cache first, fallback to network
  private async cacheFirstStrategy(type: string, id: string | undefined, options: DataSyncOptions, forceRefresh: boolean): Promise<any> {
    if (!forceRefresh) {
      // Try cache first
      const cachedData = await offlineService.getCachedData(type as any, id);
      if (cachedData.length > 0) {
        const data = cachedData[0];
        
        // Check if data is still fresh
        if (options.maxAge && Date.now() - data.timestamp < options.maxAge) {
          // Schedule background refresh if data is getting stale
          if (Date.now() - data.timestamp > (options.maxAge * 0.8)) {
            this.scheduleBackgroundRefresh(type, id, options);
          }
          return data;
        }
      }
    }

    // Cache miss or stale data, try network
    try {
      const networkData = await this.fetchFromNetwork(type, id);
      await offlineService.cacheData(type as any, id || 'list', networkData);
      return networkData;
    } catch (error) {
      // Network failed, return stale cache if available
      const cachedData = await offlineService.getCachedData(type as any, id);
      if (cachedData.length > 0) {
        console.warn(`Network failed for ${type}, returning stale cache`);
        return cachedData[0];
      }
      throw error;
    }
  }

  // Network-first strategy: Try network first, fallback to cache
  private async networkFirstStrategy(type: string, id: string | undefined, options: DataSyncOptions): Promise<any> {
    try {
      const networkData = await this.fetchFromNetwork(type, id);
      await offlineService.cacheData(type as any, id || 'list', networkData);
      return networkData;
    } catch (error) {
      // Network failed, try cache
      const cachedData = await offlineService.getCachedData(type as any, id);
      if (cachedData.length > 0) {
        console.warn(`Network failed for ${type}, returning cached data`);
        return cachedData[0];
      }
      throw error;
    }
  }

  // Stale-while-revalidate strategy: Return cache immediately, update in background
  private async staleWhileRevalidateStrategy(type: string, id: string | undefined, options: DataSyncOptions): Promise<any> {
    // Get cached data immediately
    const cachedData = await offlineService.getCachedData(type as any, id);
    let cacheResult = null;
    
    if (cachedData.length > 0) {
      cacheResult = cachedData[0];
    }

    // Start background network request
    this.fetchFromNetwork(type, id)
      .then(networkData => {
        offlineService.cacheData(type as any, id || 'list', networkData);
        // Notify components about updated data
        this.notifyDataUpdate(type, id, networkData);
      })
      .catch(error => {
        console.warn(`Background refresh failed for ${type}:`, error);
      });

    // Return cached data immediately, or wait for network if no cache
    if (cacheResult) {
      return cacheResult;
    } else {
      return this.fetchFromNetwork(type, id);
    }
  }

  // Network-only strategy: Always fetch from network
  private async networkOnlyStrategy(type: string, id: string | undefined): Promise<any> {
    return this.fetchFromNetwork(type, id);
  }

  // Cache-only strategy: Only return cached data
  private async cacheOnlyStrategy(type: string, id: string | undefined): Promise<any> {
    const cachedData = await offlineService.getCachedData(type as any, id);
    if (cachedData.length > 0) {
      return cachedData[0];
    }
    throw new Error(`No cached data available for ${type}`);
  }

  // Save data with offline support
  async saveData(type: string, data: any, id?: string): Promise<any> {
    const options = this.syncStrategies[type] || { strategy: 'networkFirst', priority: 'normal' };
    
    if (navigator.onLine) {
      try {
        // Try to save to network first
        const result = await this.saveToNetwork(type, data, id);
        
        // Update cache with successful result
        await offlineService.cacheData(type as any, id || result.id, result);
        
        return result;
      } catch (error) {
        // Network save failed, queue for background sync
        console.warn(`Network save failed for ${type}, queuing for sync`);
        return this.saveOffline(type, data, options);
      }
    } else {
      // Offline, save locally and queue for sync
      return this.saveOffline(type, data, options);
    }
  }

  // Save data offline and queue for sync
  private async saveOffline(type: string, data: any, options: DataSyncOptions): Promise<any> {
    const tempId = `temp_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    
    // Store in offline cache with temporary ID
    await offlineService.cacheData(type as any, tempId, { ...data, id: tempId, _offline: true });
    
    // Queue for background sync based on priority
    if (options.priority === 'critical') {
      await backgroundSyncService.addCriticalOperation(`save-${type}`, data);
    } else {
      await backgroundSyncService.addNormalOperation(`save-${type}`, data);
    }
    
    return { ...data, id: tempId, _offline: true };
  }

  // Delete data with offline support
  async deleteData(type: string, id: string): Promise<void> {
    const options = this.syncStrategies[type] || { strategy: 'networkFirst', priority: 'normal' };
    
    if (navigator.onLine) {
      try {
        await this.deleteFromNetwork(type, id);
        // Remove from cache
        // Note: This would need implementation in offlineService
      } catch (error) {
        console.warn(`Network delete failed for ${type}:${id}, queuing for sync`);
        // Queue delete operation for background sync
        if (options.priority === 'critical') {
          await backgroundSyncService.addCriticalOperation(`delete-${type}`, { id });
        } else {
          await backgroundSyncService.addNormalOperation(`delete-${type}`, { id });
        }
      }
    } else {
      // Queue delete operation for when online
      if (options.priority === 'critical') {
        await backgroundSyncService.addCriticalOperation(`delete-${type}`, { id });
      } else {
        await backgroundSyncService.addNormalOperation(`delete-${type}`, { id });
      }
    }
  }

  // Fetch data from network
  private async fetchFromNetwork(type: string, id?: string): Promise<any> {
    const endpoint = this.getEndpoint(type, id);
    
    const response = await fetch(endpoint, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return response.json();
  }

  // Save data to network
  private async saveToNetwork(type: string, data: any, id?: string): Promise<any> {
    const endpoint = this.getEndpoint(type, id);
    const method = id ? 'PUT' : 'POST';
    
    const response = await fetch(endpoint, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(data)
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return response.json();
  }

  // Delete data from network
  private async deleteFromNetwork(type: string, id: string): Promise<void> {
    const endpoint = this.getEndpoint(type, id);
    
    const response = await fetch(endpoint, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
  }

  // Get API endpoint for data type
  private getEndpoint(type: string, id?: string): string {
    const baseEndpoints: Record<string, string> = {
      'customers': '/api/customers',
      'invoices': '/api/invoices',
      'inventory': '/api/inventory/items',
      'transactions': '/api/accounting/transactions',
      'settings': '/api/settings',
      'templates': '/api/invoice-templates',
      'dashboard': '/api/dashboard/kpis',
      'reports': '/api/reports',
      'translations': '/api/localization/translations',
      'categories': '/api/categories'
    };

    const baseEndpoint = baseEndpoints[type] || `/api/${type}`;
    return id ? `${baseEndpoint}/${id}` : baseEndpoint;
  }

  // Schedule background refresh
  private scheduleBackgroundRefresh(type: string, id: string | undefined, options: DataSyncOptions) {
    setTimeout(() => {
      if (navigator.onLine) {
        this.fetchFromNetwork(type, id)
          .then(data => offlineService.cacheData(type as any, id || 'list', data))
          .catch(error => console.warn(`Background refresh failed for ${type}:`, error));
      }
    }, 1000);
  }

  // Notify components about data updates
  private notifyDataUpdate(type: string, id: string | undefined, data: any) {
    window.dispatchEvent(new CustomEvent('data-updated', {
      detail: { type, id, data }
    }));
  }

  // Force refresh all cached data
  async refreshAllData(): Promise<void> {
    if (!navigator.onLine) {
      throw new Error('Cannot refresh data while offline');
    }

    const promises = Object.keys(this.syncStrategies).map(type => 
      this.getData(type, undefined, true).catch(error => 
        console.warn(`Failed to refresh ${type}:`, error)
      )
    );

    await Promise.allSettled(promises);
  }

  // Get sync status for all data types
  getSyncStatus(): Record<string, { cached: boolean; fresh: boolean; syncing: boolean }> {
    // This would need implementation to check actual cache status
    return {};
  }

  // Clear all cached data
  async clearAllCache(): Promise<void> {
    await offlineService.clearOldCache(0); // Clear all
  }
}

export const offlineFirstService = new OfflineFirstService();