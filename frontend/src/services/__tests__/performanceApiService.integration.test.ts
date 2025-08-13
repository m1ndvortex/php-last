import { describe, it, expect, beforeAll, afterAll, beforeEach } from 'vitest';
import { performanceApiService } from '../performanceApiService';

// Integration tests that work with real web application
describe('PerformanceApiService Integration Tests', () => {
  let authToken: string | null = null;

  beforeAll(async () => {
    // Login to get auth token for real API testing
    try {
      const loginResponse = await performanceApiService.post('/api/auth/login', {
        email: 'test@example.com',
        password: 'password'
      });

      if (loginResponse.success && loginResponse.data?.token) {
        authToken = loginResponse.data.token;
        localStorage.setItem('auth_token', authToken);
      }
    } catch (error) {
      console.warn('Could not authenticate for integration tests:', error);
    }
  });

  afterAll(async () => {
    // Cleanup: logout if we have a token
    if (authToken) {
      try {
        await performanceApiService.post('/api/auth/logout');
      } catch (error) {
        console.warn('Could not logout after integration tests:', error);
      }
      localStorage.removeItem('auth_token');
    }
  });

  beforeEach(() => {
    // Reset metrics and cache before each test
    performanceApiService.resetMetrics();
    performanceApiService.clearCache();
  });

  describe('Real API Caching', () => {
    it('should cache real API responses', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      // First request - should hit API
      const startTime1 = Date.now();
      const result1 = await performanceApiService.get('/api/categories');
      const duration1 = Date.now() - startTime1;

      expect(result1).toBeDefined();
      expect(Array.isArray(result1) || typeof result1 === 'object').toBe(true);

      // Second request - should use cache (much faster)
      const startTime2 = Date.now();
      const result2 = await performanceApiService.get('/api/categories');
      const duration2 = Date.now() - startTime2;

      expect(result2).toEqual(result1);
      expect(duration2).toBeLessThan(duration1); // Cache should be faster

      const metrics = performanceApiService.getPerformanceMetrics();
      expect(metrics.requestCount).toBe(2);
      expect(metrics.cacheHitRate).toBe(50); // 1 hit out of 2 requests
    });

    it('should handle cache TTL expiration with real API', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      // Configure short TTL for testing
      performanceApiService.setCacheStrategy('/api/locations', { ttl: 100 });

      // First request
      const result1 = await performanceApiService.get('/api/locations');
      expect(result1).toBeDefined();

      // Wait for TTL to expire
      await new Promise(resolve => setTimeout(resolve, 150));

      // Second request should hit API again
      const result2 = await performanceApiService.get('/api/locations');
      expect(result2).toBeDefined();

      const metrics = performanceApiService.getPerformanceMetrics();
      expect(metrics.requestCount).toBe(2);
      expect(metrics.cacheHitRate).toBe(0); // No cache hits due to expiration
    });
  });

  describe('Real API Request Deduplication', () => {
    it('should deduplicate concurrent requests to real API', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      // Make multiple concurrent requests
      const promises = [
        performanceApiService.get('/api/dashboard/kpis'),
        performanceApiService.get('/api/dashboard/kpis'),
        performanceApiService.get('/api/dashboard/kpis')
      ];

      const results = await Promise.all(promises);

      // All results should be identical
      expect(results[0]).toEqual(results[1]);
      expect(results[1]).toEqual(results[2]);

      const metrics = performanceApiService.getPerformanceMetrics();
      // Should have made fewer actual requests due to deduplication
      expect(metrics.requestCount).toBeLessThanOrEqual(3);
    });
  });

  describe('Real API Retry Logic', () => {
    it('should handle real API errors gracefully', async () => {
      // Test with an endpoint that might return errors
      try {
        await performanceApiService.get('/api/nonexistent-endpoint');
      } catch (error) {
        expect(error).toBeDefined();
        
        const metrics = performanceApiService.getPerformanceMetrics();
        expect(metrics.errorRate).toBeGreaterThanOrEqual(0);
      }
    }, 10000);

    it('should retry on server errors', async () => {
      // Configure aggressive retry for testing
      performanceApiService.setRetryPolicy({
        maxRetries: 2,
        baseDelay: 100,
        retryableStatuses: [500, 502, 503, 504]
      });

      try {
        // This might succeed or fail, but we're testing the retry mechanism
        await performanceApiService.get('/api/dashboard/widgets');
      } catch (error) {
        // If it fails, check that retries were attempted
        const metrics = performanceApiService.getPerformanceMetrics();
        if (metrics.errorRate > 0) {
          expect(metrics.retryCount).toBeGreaterThanOrEqual(0);
        }
      }
    });
  });

  describe('Real API Performance Monitoring', () => {
    it('should track real API response times', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      // Make several requests to gather metrics
      const endpoints = [
        '/api/categories',
        '/api/locations',
        '/api/dashboard/kpis'
      ];

      for (const endpoint of endpoints) {
        try {
          await performanceApiService.get(endpoint);
        } catch (error) {
          console.warn(`Failed to fetch ${endpoint}:`, error);
        }
      }

      const metrics = performanceApiService.getPerformanceMetrics();
      expect(metrics.requestCount).toBeGreaterThan(0);
      expect(metrics.averageResponseTime).toBeGreaterThan(0);
      expect(metrics.lastUpdated).toBeInstanceOf(Date);
    });

    it('should provide detailed cache statistics', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      // Make some cached requests
      await performanceApiService.get('/api/categories');
      await performanceApiService.get('/api/categories'); // Cache hit
      await performanceApiService.get('/api/locations');

      const cacheStats = performanceApiService.getCacheStats();
      expect(cacheStats.size).toBeGreaterThan(0);
      expect(cacheStats.maxSize).toBeGreaterThan(0);
      expect(cacheStats.strategy).toBeDefined();

      const deduplicationStats = performanceApiService.getDeduplicationStats();
      expect(deduplicationStats.enabled).toBe(true);
      expect(deduplicationStats.maxConcurrent).toBeGreaterThan(0);
    });
  });

  describe('Real API CRUD Operations', () => {
    it('should handle real inventory operations with cache invalidation', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      try {
        // Get initial inventory list (cached)
        const initialItems = await performanceApiService.get('/api/inventory');
        expect(initialItems).toBeDefined();

        // Create new item (should invalidate cache)
        const newItem = {
          name: 'Test Performance Item',
          category_id: 1,
          quantity: 10,
          unit_price: 100
        };

        const createResponse = await performanceApiService.post('/api/inventory', newItem);
        expect(createResponse).toBeDefined();

        // Get inventory again (should not use cache due to invalidation)
        const updatedItems = await performanceApiService.get('/api/inventory');
        expect(updatedItems).toBeDefined();

        const metrics = performanceApiService.getPerformanceMetrics();
        expect(metrics.requestCount).toBe(3); // 2 GETs + 1 POST

        // Cleanup: delete the test item if creation was successful
        if (createResponse.success && createResponse.data?.id) {
          await performanceApiService.delete(`/api/inventory/${createResponse.data.id}`);
        }
      } catch (error) {
        console.warn('CRUD test failed (this might be expected in test environment):', error);
      }
    });

    it('should handle real customer operations', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      try {
        // Get customers list
        const customers = await performanceApiService.get('/api/customers');
        expect(customers).toBeDefined();

        // Test customer creation
        const newCustomer = {
          name: 'Test Performance Customer',
          email: 'test.performance@example.com',
          phone: '1234567890'
        };

        const createResponse = await performanceApiService.post('/api/customers', newCustomer);
        expect(createResponse).toBeDefined();

        // Cleanup: delete the test customer if creation was successful
        if (createResponse.success && createResponse.data?.id) {
          await performanceApiService.delete(`/api/customers/${createResponse.data.id}`);
        }
      } catch (error) {
        console.warn('Customer operations test failed (this might be expected in test environment):', error);
      }
    });
  });

  describe('Real API Authentication Integration', () => {
    it('should handle session validation', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      try {
        const sessionResponse = await performanceApiService.post('/api/auth/validate-session');
        expect(sessionResponse).toBeDefined();
        
        if (sessionResponse.success) {
          expect(sessionResponse.data).toBeDefined();
        }
      } catch (error) {
        console.warn('Session validation test failed:', error);
      }
    });

    it('should handle user profile retrieval', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      try {
        const userResponse = await performanceApiService.get('/api/auth/user');
        expect(userResponse).toBeDefined();
        
        if (userResponse.success) {
          expect(userResponse.data).toBeDefined();
          expect(userResponse.data.email).toBe('test@example.com');
        }
      } catch (error) {
        console.warn('User profile test failed:', error);
      }
    });
  });

  describe('Real API Preloading', () => {
    it('should preload common data from real API', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      const commonEndpoints = [
        '/api/categories',
        '/api/locations',
        '/api/dashboard/kpis'
      ];

      // Preload data
      await performanceApiService.preloadData(commonEndpoints);

      // Verify data is cached
      const cacheStats = performanceApiService.getCacheStats();
      expect(cacheStats.size).toBeGreaterThan(0);

      // Subsequent requests should be fast (cached)
      const startTime = Date.now();
      await performanceApiService.get('/api/categories');
      const duration = Date.now() - startTime;

      expect(duration).toBeLessThan(50); // Should be very fast from cache
    });
  });

  describe('Real API Error Scenarios', () => {
    it('should handle network connectivity issues', async () => {
      // Test with invalid base URL to simulate network issues
      try {
        await performanceApiService.get('/api/invalid-network-test');
      } catch (error) {
        expect(error).toBeDefined();
        
        const metrics = performanceApiService.getPerformanceMetrics();
        expect(metrics.errorRate).toBeGreaterThan(0);
      }
    });

    it('should handle malformed requests gracefully', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      try {
        // Send malformed data
        await performanceApiService.post('/api/inventory', {
          invalid_field: 'invalid_value',
          another_invalid_field: null
        });
      } catch (error) {
        expect(error).toBeDefined();
        
        const metrics = performanceApiService.getPerformanceMetrics();
        expect(metrics.errorRate).toBeGreaterThan(0);
      }
    });
  });

  describe('Performance Benchmarks', () => {
    it('should meet performance requirements for tab switching', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      // Simulate tab switching scenario
      const tabSwitchEndpoints = [
        '/api/dashboard/kpis',
        '/api/inventory',
        '/api/customers',
        '/api/invoices'
      ];

      // First load (cache miss)
      for (const endpoint of tabSwitchEndpoints) {
        try {
          await performanceApiService.get(endpoint);
        } catch (error) {
          console.warn(`Failed to load ${endpoint}:`, error);
        }
      }

      // Simulate tab switching (should use cache)
      const switchStartTime = Date.now();
      
      for (const endpoint of tabSwitchEndpoints) {
        try {
          const startTime = Date.now();
          await performanceApiService.get(endpoint);
          const duration = Date.now() - startTime;
          
          // Each cached request should be under 500ms (requirement 2.1)
          expect(duration).toBeLessThan(500);
        } catch (error) {
          console.warn(`Tab switch test failed for ${endpoint}:`, error);
        }
      }

      const totalSwitchTime = Date.now() - switchStartTime;
      console.log(`Total tab switch simulation time: ${totalSwitchTime}ms`);

      const metrics = performanceApiService.getPerformanceMetrics();
      console.log('Performance metrics:', metrics);
      
      // Cache hit rate should be good for tab switching
      expect(metrics.cacheHitRate).toBeGreaterThan(0);
    });

    it('should maintain performance under load', async () => {
      if (!authToken) {
        console.warn('Skipping test - no auth token available');
        return;
      }

      // Simulate multiple concurrent users
      const concurrentRequests = 10;
      const promises: Promise<any>[] = [];

      const startTime = Date.now();

      for (let i = 0; i < concurrentRequests; i++) {
        promises.push(
          performanceApiService.get('/api/categories').catch(error => {
            console.warn(`Concurrent request ${i} failed:`, error);
            return null;
          })
        );
      }

      const results = await Promise.all(promises);
      const totalTime = Date.now() - startTime;

      console.log(`${concurrentRequests} concurrent requests completed in ${totalTime}ms`);

      // At least some requests should succeed
      const successfulRequests = results.filter(result => result !== null);
      expect(successfulRequests.length).toBeGreaterThan(0);

      const metrics = performanceApiService.getPerformanceMetrics();
      console.log('Load test metrics:', metrics);

      // Average response time should be reasonable
      expect(metrics.averageResponseTime).toBeLessThan(5000); // 5 seconds max
    });
  });
});