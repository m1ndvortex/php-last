import { apiService } from "./api";
import { apiCache, staticDataCache } from "./cacheService";
import type { AxiosRequestConfig } from "axios";

interface CacheOptions {
  ttl?: number;
  cacheType?: "api" | "static";
  key?: string;
}

class EnhancedApiService {
  // Cached GET request
  async getCached<T = any>(
    url: string,
    config?: AxiosRequestConfig,
    cacheOptions?: CacheOptions,
  ): Promise<T> {
    const { ttl, cacheType = "api", key } = cacheOptions || {};

    const cacheKey = key || this.generateCacheKey(url, config?.params);
    const cache = cacheType === "static" ? staticDataCache : apiCache;

    // Try to get from cache first
    const cachedData = cache.get<T>(cacheKey);
    if (cachedData !== null) {
      return cachedData;
    }

    // Make API request
    const response = await apiService.get<T>(url, config);
    const data = response.data;

    // Cache the result
    cache.set(cacheKey, data, ttl);

    return data;
  }

  // Generate cache key from URL and params
  private generateCacheKey(url: string, params?: any): string {
    const paramString = params ? JSON.stringify(params) : "";
    return `${url}${paramString}`;
  }

  // Invalidate cache for a specific pattern
  invalidateCache(pattern: string): void {
    // This is a simple implementation - in a real app you might want more sophisticated cache invalidation
    if (pattern.includes("categories") || pattern.includes("locations")) {
      staticDataCache.clear();
    } else {
      apiCache.clear();
    }
  }

  // Enhanced inventory methods with caching
  inventory = {
    // Cache categories for 1 hour since they don't change often
    getCategories: () =>
      this.getCached(
        "api/categories",
        {},
        { cacheType: "static", ttl: 60 * 60 * 1000 },
      ),

    // Cache locations for 1 hour
    getLocations: () =>
      this.getCached(
        "api/locations",
        {},
        { cacheType: "static", ttl: 60 * 60 * 1000 },
      ),

    // Cache gold purity options for 1 hour
    getGoldPurityOptions: () =>
      this.getCached(
        "api/categories/gold-purity-options",
        {},
        { cacheType: "static", ttl: 60 * 60 * 1000 },
      ),

    // Cache inventory items for 2 minutes
    getItems: (filters?: Record<string, any>) =>
      this.getCached(
        "api/inventory/items",
        { params: filters },
        { ttl: 2 * 60 * 1000 },
      ),

    // Cache individual item for 5 minutes
    getItem: (id: number) =>
      this.getCached(`api/inventory/items/${id}`, {}, { ttl: 5 * 60 * 1000 }),

    // Cache low stock items for 5 minutes
    getLowStockItems: () =>
      this.getCached(
        "api/inventory/reports/low-stock",
        {},
        { ttl: 5 * 60 * 1000 },
      ),

    // Cache expiring items for 10 minutes
    getExpiringItems: (days?: number) =>
      this.getCached(
        "api/inventory/reports/expiring",
        { params: { days } },
        { ttl: 10 * 60 * 1000 },
      ),

    // Non-cached methods (mutations)
    createItem: (data: FormData) => {
      this.invalidateCache("inventory");
      return apiService.inventory.createItem(data);
    },

    updateItem: (id: number, data: FormData) => {
      this.invalidateCache("inventory");
      return apiService.inventory.updateItem(id, data);
    },

    deleteItem: (id: number) => {
      this.invalidateCache("inventory");
      return apiService.inventory.deleteItem(id);
    },
  };

  // Enhanced customer methods with caching
  customers = {
    // Cache customers list for 1 minute
    getCustomers: (filters?: Record<string, any>) =>
      this.getCached("api/customers", { params: filters }, { ttl: 60 * 1000 }),

    // Cache individual customer for 5 minutes
    getCustomer: (id: number) =>
      this.getCached(`api/customers/${id}`, {}, { ttl: 5 * 60 * 1000 }),

    // Cache aging report for 10 minutes
    getAgingReport: (filters?: Record<string, any>) =>
      this.getCached(
        "api/customers/aging-report",
        { params: filters },
        { ttl: 10 * 60 * 1000 },
      ),

    // Cache CRM pipeline for 5 minutes
    getCRMPipeline: () =>
      this.getCached("api/customers/crm-pipeline", {}, { ttl: 5 * 60 * 1000 }),

    // Non-cached methods (mutations)
    createCustomer: (data: any) => {
      this.invalidateCache("customers");
      return apiService.customers.createCustomer(data);
    },

    updateCustomer: (id: number, data: any) => {
      this.invalidateCache("customers");
      return apiService.customers.updateCustomer(id, data);
    },

    deleteCustomer: (id: number) => {
      this.invalidateCache("customers");
      return apiService.customers.deleteCustomer(id);
    },
  };

  // Enhanced dashboard methods with caching
  dashboard = {
    // Cache KPIs for 30 seconds (they update frequently)
    getKPIs: () => this.getCached("api/dashboard/kpis", {}, { ttl: 30 * 1000 }),

    // Cache widgets for 5 minutes
    getWidgets: () =>
      this.getCached("api/dashboard/widgets", {}, { ttl: 5 * 60 * 1000 }),
  };

  // Enhanced invoice methods with caching
  invoices = {
    // Cache invoices list for 1 minute
    getInvoices: (filters?: Record<string, any>) =>
      this.getCached("api/invoices", { params: filters }, { ttl: 60 * 1000 }),

    // Cache individual invoice for 5 minutes
    getInvoice: (id: number) =>
      this.getCached(`api/invoices/${id}`, {}, { ttl: 5 * 60 * 1000 }),

    // Cache templates for 30 minutes
    getTemplates: () =>
      this.getCached(
        "api/invoice-templates",
        {},
        { cacheType: "static", ttl: 30 * 60 * 1000 },
      ),

    // Cache individual template for 30 minutes
    getTemplate: (id: number) =>
      this.getCached(
        `api/invoice-templates/${id}`,
        {},
        { cacheType: "static", ttl: 30 * 60 * 1000 },
      ),

    // Non-cached methods (mutations)
    createInvoice: (data: any) => {
      this.invalidateCache("invoices");
      return apiService.invoices.createInvoice(data);
    },

    updateInvoice: (id: number, data: any) => {
      this.invalidateCache("invoices");
      return apiService.invoices.updateInvoice(id, data);
    },

    deleteInvoice: (id: number) => {
      this.invalidateCache("invoices");
      return apiService.invoices.deleteInvoice(id);
    },
  };

  // Prefetch commonly used data
  async prefetchCommonData(): Promise<void> {
    const prefetchPromises = [
      this.inventory.getCategories().catch(() => {}),
      this.inventory.getLocations().catch(() => {}),
      this.inventory.getGoldPurityOptions().catch(() => {}),
      this.invoices.getTemplates().catch(() => {}),
      this.dashboard.getKPIs().catch(() => {}),
    ];

    await Promise.allSettled(prefetchPromises);
  }

  // Get cache statistics
  getCacheStats() {
    return {
      api: apiCache.getStats(),
      static: staticDataCache.getStats(),
    };
  }

  // Clear all caches
  clearAllCaches() {
    apiCache.clear();
    staticDataCache.clear();
  }
}

export const enhancedApiService = new EnhancedApiService();
export default enhancedApiService;
