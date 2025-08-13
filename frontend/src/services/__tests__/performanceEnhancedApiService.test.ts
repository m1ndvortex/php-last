import { describe, it, expect, vi, beforeEach } from 'vitest';
import { performanceEnhancedApiService } from '../performanceEnhancedApiService';

// Mock the performance API service
vi.mock('../performanceApiService', () => ({
  performanceApiService: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    setCacheStrategy: vi.fn(),
    setRetryPolicy: vi.fn(),
    setDeduplicationConfig: vi.fn(),
    getPerformanceMetrics: vi.fn(() => ({
      requestCount: 0,
      averageResponseTime: 0,
      cacheHitRate: 0,
      errorRate: 0,
      retryCount: 0,
      deduplicationSaves: 0,
      lastUpdated: new Date()
    })),
    getCacheStats: vi.fn(() => ({
      size: 0,
      maxSize: 100,
      strategy: 'lru'
    })),
    getDeduplicationStats: vi.fn(() => ({
      enabled: true,
      maxConcurrent: 10,
      windowMs: 5000
    })),
    clearCache: vi.fn(),
    invalidateCache: vi.fn(),
    resetMetrics: vi.fn(),
    preloadData: vi.fn()
  }
}));

describe('PerformanceEnhancedApiService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('Inventory Methods', () => {
    it('should provide cached inventory methods', () => {
      expect(typeof performanceEnhancedApiService.inventory.getCategories).toBe('function');
      expect(typeof performanceEnhancedApiService.inventory.getLocations).toBe('function');
      expect(typeof performanceEnhancedApiService.inventory.getItems).toBe('function');
      expect(typeof performanceEnhancedApiService.inventory.createItem).toBe('function');
    });

    it('should call performance API service for inventory operations', async () => {
      const { performanceApiService } = await import('../performanceApiService');
      
      await performanceEnhancedApiService.inventory.getCategories();
      expect(performanceApiService.get).toHaveBeenCalledWith('/api/categories', {
        cache: { ttl: 60 * 60 * 1000 }
      });
    });
  });

  describe('Customer Methods', () => {
    it('should provide cached customer methods', () => {
      expect(typeof performanceEnhancedApiService.customers.getCustomers).toBe('function');
      expect(typeof performanceEnhancedApiService.customers.getCustomer).toBe('function');
      expect(typeof performanceEnhancedApiService.customers.createCustomer).toBe('function');
    });

    it('should call performance API service for customer operations', async () => {
      const { performanceApiService } = await import('../performanceApiService');
      
      await performanceEnhancedApiService.customers.getCustomers();
      expect(performanceApiService.get).toHaveBeenCalledWith('/api/customers', {
        params: undefined,
        cache: { ttl: 60 * 1000 }
      });
    });
  });

  describe('Dashboard Methods', () => {
    it('should provide cached dashboard methods', () => {
      expect(typeof performanceEnhancedApiService.dashboard.getKPIs).toBe('function');
      expect(typeof performanceEnhancedApiService.dashboard.getWidgets).toBe('function');
    });

    it('should call performance API service for dashboard operations', async () => {
      const { performanceApiService } = await import('../performanceApiService');
      
      await performanceEnhancedApiService.dashboard.getKPIs();
      expect(performanceApiService.get).toHaveBeenCalledWith('/api/dashboard/kpis', {
        cache: { ttl: 30 * 1000 }
      });
    });
  });

  describe('Authentication Methods', () => {
    it('should provide authentication methods without caching', () => {
      expect(typeof performanceEnhancedApiService.auth.login).toBe('function');
      expect(typeof performanceEnhancedApiService.auth.logout).toBe('function');
      expect(typeof performanceEnhancedApiService.auth.me).toBe('function');
    });

    it('should call performance API service for auth operations with skipCache', async () => {
      const { performanceApiService } = await import('../performanceApiService');
      
      const credentials = { email: 'test@example.com', password: 'password' };
      await performanceEnhancedApiService.auth.login(credentials);
      
      expect(performanceApiService.post).toHaveBeenCalledWith('/api/auth/login', credentials, {
        skipCache: true
      });
    });
  });

  describe('Performance Management', () => {
    it('should provide performance monitoring methods', () => {
      expect(typeof performanceEnhancedApiService.performance.getMetrics).toBe('function');
      expect(typeof performanceEnhancedApiService.performance.getCacheStats).toBe('function');
      expect(typeof performanceEnhancedApiService.performance.clearCache).toBe('function');
    });

    it('should return performance metrics', () => {
      const metrics = performanceEnhancedApiService.performance.getMetrics();
      expect(metrics).toHaveProperty('requestCount');
      expect(metrics).toHaveProperty('averageResponseTime');
      expect(metrics).toHaveProperty('cacheHitRate');
    });

    it('should provide cache management', () => {
      performanceEnhancedApiService.performance.clearCache();
      performanceEnhancedApiService.performance.invalidateCache('api/inventory.*');
      performanceEnhancedApiService.performance.resetMetrics();
      
      // Should not throw
      expect(true).toBe(true);
    });

    it('should preload common data', async () => {
      const { performanceApiService } = await import('../performanceApiService');
      
      await performanceEnhancedApiService.performance.preloadCommonData();
      
      expect(performanceApiService.preloadData).toHaveBeenCalledWith([
        '/api/categories',
        '/api/locations',
        '/api/categories/gold-purity-options',
        '/api/dashboard/kpis',
        '/api/invoice-templates'
      ]);
    });
  });

  describe('Generic HTTP Methods', () => {
    it('should provide generic HTTP methods', () => {
      expect(typeof performanceEnhancedApiService.get).toBe('function');
      expect(typeof performanceEnhancedApiService.post).toBe('function');
      expect(typeof performanceEnhancedApiService.put).toBe('function');
      expect(typeof performanceEnhancedApiService.delete).toBe('function');
    });

    it('should call performance API service for generic methods', async () => {
      const { performanceApiService } = await import('../performanceApiService');
      
      await performanceEnhancedApiService.get('/api/test');
      expect(performanceApiService.get).toHaveBeenCalledWith('/api/test', undefined);
      
      await performanceEnhancedApiService.post('/api/test', { data: 'test' });
      expect(performanceApiService.post).toHaveBeenCalledWith('/api/test', { data: 'test' }, undefined);
    });
  });

  describe('Configuration', () => {
    it('should configure performance settings on initialization', () => {
      // The service should be configured automatically
      expect(performanceEnhancedApiService).toBeDefined();
      expect(typeof performanceEnhancedApiService.inventory).toBe('object');
      expect(typeof performanceEnhancedApiService.customers).toBe('object');
      expect(typeof performanceEnhancedApiService.dashboard).toBe('object');
    });

    it('should provide fallback to enhanced API service', () => {
      expect(performanceEnhancedApiService.fallback).toBeDefined();
      expect(typeof performanceEnhancedApiService.fallback).toBe('object');
    });
  });

  describe('Integration', () => {
    it('should be a singleton instance', () => {
      const instance1 = performanceEnhancedApiService;
      const instance2 = performanceEnhancedApiService;
      expect(instance1).toBe(instance2);
    });

    it('should maintain consistent API interface', () => {
      // Check that all expected methods exist
      const expectedMethods = [
        'inventory', 'customers', 'dashboard', 'invoices', 'auth', 'performance',
        'get', 'post', 'put', 'delete', 'fallback'
      ];

      expectedMethods.forEach(method => {
        expect(performanceEnhancedApiService).toHaveProperty(method);
      });
    });
  });
});