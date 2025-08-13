import { performanceApiService } from './performanceApiService';
import { enhancedApiService } from './enhancedApiService';
import type { AxiosRequestConfig } from 'axios';

/**
 * Performance-Enhanced API Service
 * Combines the existing enhanced API service with the new performance optimizations
 */
class PerformanceEnhancedApiService {
  constructor() {
    // Initialize performance optimizations
    this.configurePerformanceSettings();
  }

  private configurePerformanceSettings(): void {
    // Configure cache strategies for different endpoint types
    performanceApiService.setCacheStrategy('/api/categories', {
      ttl: 60 * 60 * 1000, // 1 hour for categories
      maxSize: 50,
      strategy: 'lru',
      invalidationRules: [
        { pattern: 'api/categories.*', triggers: ['category_update', 'category_create', 'category_delete'] }
      ],
      compressionEnabled: false
    });

    performanceApiService.setCacheStrategy('/api/inventory', {
      ttl: 2 * 60 * 1000, // 2 minutes for inventory
      maxSize: 200,
      strategy: 'lru',
      invalidationRules: [
        { pattern: 'api/inventory.*', triggers: ['inventory_update', 'inventory_create', 'inventory_delete'] }
      ],
      compressionEnabled: false
    });

    performanceApiService.setCacheStrategy('/api/customers', {
      ttl: 5 * 60 * 1000, // 5 minutes for customers
      maxSize: 100,
      strategy: 'lru',
      invalidationRules: [
        { pattern: 'api/customers.*', triggers: ['customer_update', 'customer_create', 'customer_delete'] }
      ],
      compressionEnabled: false
    });

    // Configure retry policy for better reliability
    performanceApiService.setRetryPolicy({
      maxRetries: 3,
      baseDelay: 1000,
      maxDelay: 10000,
      backoffMultiplier: 2,
      retryableStatuses: [429, 500, 502, 503, 504],
      retryableErrors: ['NETWORK_ERROR', 'TIMEOUT', 'SERVER_ERROR']
    });

    // Configure request deduplication
    performanceApiService.setDeduplicationConfig({
      enabled: true,
      windowMs: 5000, // 5 seconds
      maxConcurrent: 10
    });
  }

  // Enhanced inventory methods with performance optimizations
  inventory = {
    // Cached GET methods
    getCategories: () => performanceApiService.get('/api/categories', {
      cache: { ttl: 60 * 60 * 1000 } // 1 hour cache
    }),

    getLocations: () => performanceApiService.get('/api/locations', {
      cache: { ttl: 60 * 60 * 1000 } // 1 hour cache
    }),

    getGoldPurityOptions: () => performanceApiService.get('/api/categories/gold-purity-options', {
      cache: { ttl: 60 * 60 * 1000 } // 1 hour cache
    }),

    getItems: (filters?: Record<string, any>) => performanceApiService.get('/api/inventory', {
      params: filters,
      cache: { ttl: 2 * 60 * 1000 } // 2 minutes cache
    }),

    getItem: (id: number) => performanceApiService.get(`/api/inventory/${id}`, {
      cache: { ttl: 5 * 60 * 1000 } // 5 minutes cache
    }),

    getLowStockItems: () => performanceApiService.get('/api/inventory/low-stock', {
      cache: { ttl: 5 * 60 * 1000 } // 5 minutes cache
    }),

    getExpiringItems: (days?: number) => performanceApiService.get('/api/inventory/expiring', {
      params: { days },
      cache: { ttl: 10 * 60 * 1000 } // 10 minutes cache
    }),

    // Mutation methods (no cache, with invalidation)
    createItem: (data: FormData) => performanceApiService.post('/api/inventory', data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),

    updateItem: (id: number, data: FormData) => performanceApiService.put(`/api/inventory/${id}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),

    deleteItem: (id: number) => performanceApiService.delete(`/api/inventory/${id}`),

    createMovement: (data: any) => performanceApiService.post('/api/inventory/movements', data),

    // Category management
    createCategory: (data: any) => performanceApiService.post('/api/categories', data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),

    updateCategory: (id: number, data: any) => performanceApiService.put(`/api/categories/${id}`, data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),

    deleteCategory: (id: number) => performanceApiService.delete(`/api/categories/${id}`)
  };

  // Enhanced customer methods with performance optimizations
  customers = {
    getCustomers: (filters?: Record<string, any>) => performanceApiService.get('/api/customers', {
      params: filters,
      cache: { ttl: 60 * 1000 } // 1 minute cache
    }),

    getCustomer: (id: number) => performanceApiService.get(`/api/customers/${id}`, {
      cache: { ttl: 5 * 60 * 1000 } // 5 minutes cache
    }),

    getAgingReport: (filters?: Record<string, any>) => performanceApiService.get('/api/customers/aging-report', {
      params: filters,
      cache: { ttl: 10 * 60 * 1000 } // 10 minutes cache
    }),

    getCRMPipeline: () => performanceApiService.get('/api/customers/crm-pipeline', {
      cache: { ttl: 5 * 60 * 1000 } // 5 minutes cache
    }),

    // Mutation methods
    createCustomer: (data: any) => performanceApiService.post('/api/customers', data),
    updateCustomer: (id: number, data: any) => performanceApiService.put(`/api/customers/${id}`, data),
    deleteCustomer: (id: number) => performanceApiService.delete(`/api/customers/${id}`)
  };

  // Enhanced dashboard methods with performance optimizations
  dashboard = {
    getKPIs: () => performanceApiService.get('/api/dashboard/kpis', {
      cache: { ttl: 30 * 1000 } // 30 seconds cache
    }),

    getWidgets: () => performanceApiService.get('/api/dashboard/widgets', {
      cache: { ttl: 5 * 60 * 1000 } // 5 minutes cache
    })
  };

  // Enhanced invoice methods with performance optimizations
  invoices = {
    getInvoices: (filters?: Record<string, any>) => performanceApiService.get('/api/invoices', {
      params: filters,
      cache: { ttl: 60 * 1000 } // 1 minute cache
    }),

    getInvoice: (id: number) => performanceApiService.get(`/api/invoices/${id}`, {
      cache: { ttl: 5 * 60 * 1000 } // 5 minutes cache
    }),

    getTemplates: () => performanceApiService.get('/api/invoice-templates', {
      cache: { ttl: 30 * 60 * 1000 } // 30 minutes cache
    }),

    getTemplate: (id: number) => performanceApiService.get(`/api/invoice-templates/${id}`, {
      cache: { ttl: 30 * 60 * 1000 } // 30 minutes cache
    }),

    // Mutation methods
    createInvoice: (data: any) => performanceApiService.post('/api/invoices', data),
    updateInvoice: (id: number, data: any) => performanceApiService.put(`/api/invoices/${id}`, data),
    deleteInvoice: (id: number) => performanceApiService.delete(`/api/invoices/${id}`)
  };

  // Authentication methods (no caching for security)
  auth = {
    login: (credentials: { email: string; password: string; remember?: boolean }) =>
      performanceApiService.post('/api/auth/login', credentials, { 
        skipCache: true 
      } as AxiosRequestConfig & { skipCache?: boolean }),

    logout: () => performanceApiService.post('/api/auth/logout', {}, { 
      skipCache: true 
    } as AxiosRequestConfig & { skipCache?: boolean }),

    me: () => performanceApiService.get('/api/auth/user', { 
      skipCache: true 
    } as AxiosRequestConfig & { skipCache?: boolean }),

    refresh: () => performanceApiService.post('/api/auth/refresh', {}, { 
      skipCache: true 
    } as AxiosRequestConfig & { skipCache?: boolean }),

    validateSession: () => performanceApiService.post('/api/auth/validate-session', {}, { 
      skipCache: true 
    } as AxiosRequestConfig & { skipCache?: boolean }),

    extendSession: () => performanceApiService.post('/api/auth/extend-session', {}, { 
      skipCache: true 
    } as AxiosRequestConfig & { skipCache?: boolean })
  };

  // Performance monitoring and management
  performance = {
    getMetrics: () => performanceApiService.getPerformanceMetrics(),
    getCacheStats: () => performanceApiService.getCacheStats(),
    getDeduplicationStats: () => performanceApiService.getDeduplicationStats(),
    clearCache: () => performanceApiService.clearCache(),
    invalidateCache: (pattern: string) => performanceApiService.invalidateCache(pattern),
    resetMetrics: () => performanceApiService.resetMetrics(),
    
    // Preload commonly used data for better tab switching performance
    preloadCommonData: async () => {
      const commonEndpoints = [
        '/api/categories',
        '/api/locations',
        '/api/categories/gold-purity-options',
        '/api/dashboard/kpis',
        '/api/invoice-templates'
      ];
      
      await performanceApiService.preloadData(commonEndpoints);
    }
  };

  // Generic HTTP methods with performance optimizations
  get = <T>(url: string, config?: AxiosRequestConfig & { 
    cache?: { ttl?: number; key?: string };
    skipCache?: boolean;
    skipDeduplication?: boolean;
  }) => performanceApiService.get<T>(url, config);

  post = <T>(url: string, data?: any, config?: AxiosRequestConfig) => 
    performanceApiService.post<T>(url, data, config);

  put = <T>(url: string, data?: any, config?: AxiosRequestConfig) => 
    performanceApiService.put<T>(url, data, config);

  delete = <T>(url: string, config?: AxiosRequestConfig) => 
    performanceApiService.delete<T>(url, config);

  // Fallback to original enhanced API service for methods not yet optimized
  fallback = enhancedApiService;
}

// Create and export the singleton instance
export const performanceEnhancedApiService = new PerformanceEnhancedApiService();
export default performanceEnhancedApiService;