import { openDB, type IDBPDatabase } from "idb";

interface OfflineData {
  id: string;
  type: "customer" | "invoice" | "inventory" | "transaction";
  data: any;
  timestamp: number;
  synced: boolean;
}

interface PendingSync {
  id: string;
  method: "POST" | "PUT" | "DELETE";
  url: string;
  data: any;
  timestamp: number;
  retries: number;
}

class OfflineService {
  private db: IDBPDatabase | null = null;
  private readonly DB_NAME = "JewelryPlatformDB";
  private readonly DB_VERSION = 1;

  async init(): Promise<void> {
    this.db = await openDB(this.DB_NAME, this.DB_VERSION, {
      upgrade(db) {
        // Store for cached data
        if (!db.objectStoreNames.contains("offlineData")) {
          const offlineStore = db.createObjectStore("offlineData", {
            keyPath: "id",
          });
          offlineStore.createIndex("type", "type");
          offlineStore.createIndex("timestamp", "timestamp");
        }

        // Store for pending sync operations
        if (!db.objectStoreNames.contains("pendingSync")) {
          const syncStore = db.createObjectStore("pendingSync", {
            keyPath: "id",
          });
          syncStore.createIndex("timestamp", "timestamp");
        }

        // Store for form submissions
        if (!db.objectStoreNames.contains("offlineForms")) {
          const formsStore = db.createObjectStore("offlineForms", {
            keyPath: "id",
          });
          formsStore.createIndex("type", "type");
          formsStore.createIndex("timestamp", "timestamp");
        }
      },
    });
  }

  // Cache data for offline access
  async cacheData(
    type: OfflineData["type"],
    id: string,
    data: any,
  ): Promise<void> {
    if (!this.db) await this.init();

    const offlineData: OfflineData = {
      id: `${type}_${id}`,
      type,
      data,
      timestamp: Date.now(),
      synced: true,
    };

    await this.db!.put("offlineData", offlineData);
  }

  // Get cached data
  async getCachedData(type: OfflineData["type"], id?: string): Promise<any[]> {
    if (!this.db) await this.init();

    if (id) {
      const data = await this.db!.get("offlineData", `${type}_${id}`);
      return data ? [data.data] : [];
    }

    const tx = this.db!.transaction("offlineData", "readonly");
    const index = tx.store.index("type");
    const items = await index.getAll(type);
    return items.map((item) => item.data);
  }

  // Store pending sync operation
  async addPendingSync(
    method: PendingSync["method"],
    url: string,
    data: any,
  ): Promise<string> {
    if (!this.db) await this.init();

    const id = `sync_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    const pendingSync: PendingSync = {
      id,
      method,
      url,
      data,
      timestamp: Date.now(),
      retries: 0,
    };

    await this.db!.put("pendingSync", pendingSync);
    return id;
  }

  // Get all pending sync operations
  async getPendingSync(): Promise<PendingSync[]> {
    if (!this.db) await this.init();
    return await this.db!.getAll("pendingSync");
  }

  // Remove completed sync operation
  async removePendingSync(id: string): Promise<void> {
    if (!this.db) await this.init();
    await this.db!.delete("pendingSync", id);
  }

  // Store offline form submission
  async storeOfflineForm(type: string, formData: any): Promise<string> {
    if (!this.db) await this.init();

    const id = `form_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    const offlineForm = {
      id,
      type,
      data: formData,
      timestamp: Date.now(),
      synced: false,
    };

    await this.db!.put("offlineForms", offlineForm);
    return id;
  }

  // Get offline form submissions
  async getOfflineForms(type?: string): Promise<any[]> {
    if (!this.db) await this.init();

    if (type) {
      const tx = this.db!.transaction("offlineForms", "readonly");
      const index = tx.store.index("type");
      return await index.getAll(type);
    }

    return await this.db!.getAll("offlineForms");
  }

  // Mark form as synced
  async markFormSynced(id: string): Promise<void> {
    if (!this.db) await this.init();

    const form = await this.db!.get("offlineForms", id);
    if (form) {
      form.synced = true;
      await this.db!.put("offlineForms", form);
    }
  }

  // Clear old cached data
  async clearOldCache(maxAge: number = 7 * 24 * 60 * 60 * 1000): Promise<void> {
    if (!this.db) await this.init();

    const cutoff = Date.now() - maxAge;
    const tx = this.db!.transaction("offlineData", "readwrite");
    const index = tx.store.index("timestamp");
    const range = IDBKeyRange.upperBound(cutoff);

    for await (const cursor of index.iterate(range)) {
      await cursor.delete();
    }
  }

  // Get storage usage info
  async getStorageInfo(): Promise<{
    used: number;
    quota: number;
    percentage: number;
  }> {
    if ("storage" in navigator && "estimate" in navigator.storage) {
      const estimate = await navigator.storage.estimate();
      const used = estimate.usage || 0;
      const quota = estimate.quota || 0;
      const percentage = quota > 0 ? (used / quota) * 100 : 0;

      return { used, quota, percentage };
    }

    return { used: 0, quota: 0, percentage: 0 };
  }
}

export const offlineService = new OfflineService();
