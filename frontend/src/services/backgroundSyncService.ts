import { offlineService } from './offlineService';

interface SyncOperation {
  id: string;
  type: 'critical' | 'normal' | 'low';
  operation: string;
  data: any;
  retries: number;
  maxRetries: number;
  nextRetry: number;
}

class BackgroundSyncService {
  private syncQueue: SyncOperation[] = [];
  private isProcessing = false;
  private syncInterval: number | null = null;

  constructor() {
    this.initializeBackgroundSync();
  }

  private async initializeBackgroundSync() {
    // Register background sync if supported
    if ('serviceWorker' in navigator && 'sync' in (window as any).ServiceWorkerRegistration.prototype) {
      try {
        const registration = await navigator.serviceWorker.ready;
        await (registration as any).sync.register('background-sync');
        console.log('Background sync registered');
      } catch (error) {
        console.error('Background sync registration failed:', error);
        this.fallbackToPeriodicSync();
      }
    } else {
      this.fallbackToPeriodicSync();
    }

    // Listen for online events
    window.addEventListener('online', () => {
      this.processSyncQueue();
    });
  }

  private fallbackToPeriodicSync() {
    // Fallback to periodic sync for browsers that don't support background sync
    this.syncInterval = window.setInterval(() => {
      if (navigator.onLine) {
        this.processSyncQueue();
      }
    }, 30000); // Every 30 seconds
  }

  // Add critical operation to sync queue
  async addCriticalOperation(operation: string, data: any): Promise<string> {
    const id = `critical_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    
    const syncOp: SyncOperation = {
      id,
      type: 'critical',
      operation,
      data,
      retries: 0,
      maxRetries: 5,
      nextRetry: Date.now()
    };

    this.syncQueue.push(syncOp);
    
    // Store in IndexedDB for persistence
    await offlineService.addPendingSync('POST', this.getOperationEndpoint(operation), data);
    
    // Try immediate sync if online
    if (navigator.onLine) {
      this.processSyncQueue();
    }

    return id;
  }

  // Add normal operation to sync queue
  async addNormalOperation(operation: string, data: any): Promise<string> {
    const id = `normal_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    
    const syncOp: SyncOperation = {
      id,
      type: 'normal',
      operation,
      data,
      retries: 0,
      maxRetries: 3,
      nextRetry: Date.now()
    };

    this.syncQueue.push(syncOp);
    await offlineService.addPendingSync('POST', this.getOperationEndpoint(operation), data);
    
    return id;
  }

  // Process sync queue
  private async processSyncQueue() {
    if (this.isProcessing || !navigator.onLine) return;
    
    this.isProcessing = true;
    
    try {
      // Sort by priority (critical first) and retry time
      const sortedQueue = this.syncQueue
        .filter(op => Date.now() >= op.nextRetry)
        .sort((a, b) => {
          if (a.type === 'critical' && b.type !== 'critical') return -1;
          if (a.type !== 'critical' && b.type === 'critical') return 1;
          return a.nextRetry - b.nextRetry;
        });

      for (const operation of sortedQueue) {
        try {
          await this.executeOperation(operation);
          this.removeFromQueue(operation.id);
        } catch (error) {
          console.error(`Sync operation ${operation.id} failed:`, error);
          await this.handleOperationFailure(operation);
        }
      }
    } finally {
      this.isProcessing = false;
    }
  }

  // Execute individual operation
  private async executeOperation(operation: SyncOperation): Promise<void> {
    const endpoint = this.getOperationEndpoint(operation.operation);
    
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(operation.data)
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    // Handle successful sync
    this.notifyOperationSuccess(operation);
  }

  // Handle operation failure
  private async handleOperationFailure(operation: SyncOperation) {
    operation.retries++;
    
    if (operation.retries >= operation.maxRetries) {
      // Max retries reached, remove from queue
      this.removeFromQueue(operation.id);
      this.notifyOperationFailure(operation);
    } else {
      // Schedule retry with exponential backoff
      const backoffDelay = Math.min(1000 * Math.pow(2, operation.retries), 300000); // Max 5 minutes
      operation.nextRetry = Date.now() + backoffDelay;
    }
  }

  // Get endpoint for operation type
  private getOperationEndpoint(operation: string): string {
    const endpoints: Record<string, string> = {
      'save-invoice': '/api/invoices',
      'save-customer': '/api/customers',
      'save-inventory': '/api/inventory/items',
      'save-transaction': '/api/accounting/transactions',
      'send-communication': '/api/communications',
      'backup-data': '/api/backup',
      'sync-offline-data': '/api/sync/offline-data'
    };
    
    return endpoints[operation] || '/api/sync/generic';
  }

  // Remove operation from queue
  private removeFromQueue(operationId: string) {
    this.syncQueue = this.syncQueue.filter(op => op.id !== operationId);
  }

  // Notify operation success
  private notifyOperationSuccess(operation: SyncOperation) {
    // Dispatch custom event for UI updates
    window.dispatchEvent(new CustomEvent('sync-operation-success', {
      detail: { operation: operation.operation, id: operation.id }
    }));
  }

  // Notify operation failure
  private notifyOperationFailure(operation: SyncOperation) {
    // Dispatch custom event for UI updates
    window.dispatchEvent(new CustomEvent('sync-operation-failure', {
      detail: { operation: operation.operation, id: operation.id, error: 'Max retries exceeded' }
    }));
  }

  // Get queue status
  getQueueStatus() {
    return {
      total: this.syncQueue.length,
      critical: this.syncQueue.filter(op => op.type === 'critical').length,
      normal: this.syncQueue.filter(op => op.type === 'normal').length,
      low: this.syncQueue.filter(op => op.type === 'low').length,
      failed: this.syncQueue.filter(op => op.retries >= op.maxRetries).length
    };
  }

  // Force sync all operations
  async forceSyncAll(): Promise<void> {
    if (navigator.onLine) {
      await this.processSyncQueue();
    }
  }

  // Clear failed operations
  clearFailedOperations() {
    this.syncQueue = this.syncQueue.filter(op => op.retries < op.maxRetries);
  }

  // Destroy service
  destroy() {
    if (this.syncInterval) {
      clearInterval(this.syncInterval);
      this.syncInterval = null;
    }
  }
}

export const backgroundSyncService = new BackgroundSyncService();