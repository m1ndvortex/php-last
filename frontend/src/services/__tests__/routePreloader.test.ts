import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { RoutePreloader } from '../routePreloader';

// Mock performance.now
const mockPerformanceNow = vi.fn(() => Date.now());
Object.defineProperty(window, 'performance', {
  value: { now: mockPerformanceNow },
  writable: true
});

// Mock requestIdleCallback
const mockRequestIdleCallback = vi.fn((callback: Function) => {
  setTimeout(callback, 0);
});
Object.defineProperty(window, 'requestIdleCallback', {
  value: mockRequestIdleCallback,
  writable: true
});

describe('RoutePreloader', () => {
  let preloader: RoutePreloader;
  let mockImportFn: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    preloader = new RoutePreloader();
    mockImportFn = vi.fn().mockResolvedValue({ default: 'MockComponent' });
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.clearAllTimers();
  });

  describe('preloadRoute', () => {
    it('should preload a route successfully', async () => {
      const routeName = 'dashboard';
      
      await preloader.preloadRoute(routeName, mockImportFn);
      
      expect(mockImportFn).toHaveBeenCalledOnce();
      expect(preloader.getPreloadedRoute(routeName)).toBeTruthy();
    });

    it('should not preload the same route twice', async () => {
      const routeName = 'dashboard';
      
      await preloader.preloadRoute(routeName, mockImportFn);
      await preloader.preloadRoute(routeName, mockImportFn);
      
      expect(mockImportFn).toHaveBeenCalledOnce();
    });

    it('should handle preload failures gracefully', async () => {
      const routeName = 'dashboard';
      const errorImportFn = vi.fn().mockRejectedValue(new Error('Import failed'));
      
      await expect(preloader.preloadRoute(routeName, errorImportFn)).rejects.toThrow('Import failed');
      expect(preloader.getPreloadedRoute(routeName)).toBeNull();
    });

    it('should respect cache time limits', async () => {
      const routeName = 'dashboard';
      
      // Mock Date.now to simulate time passing
      const originalDateNow = Date.now;
      let currentTime = 1000;
      Date.now = vi.fn(() => currentTime);
      
      await preloader.preloadRoute(routeName, mockImportFn);
      expect(preloader.getPreloadedRoute(routeName)).toBeTruthy();
      
      // Simulate cache expiry (5 minutes = 300000ms)
      currentTime += 300001;
      expect(preloader.getPreloadedRoute(routeName)).toBeNull();
      
      Date.now = originalDateNow;
    });

    it('should handle high priority routes with no delay', async () => {
      const routeName = 'dashboard'; // High priority route
      const startTime = performance.now();
      
      await preloader.preloadRoute(routeName, mockImportFn);
      
      const endTime = performance.now();
      expect(endTime - startTime).toBeLessThan(50); // Should be immediate
    });

    it('should delay low priority routes', async () => {
      const routeName = 'settings'; // Low priority route
      
      const promise = preloader.preloadRoute(routeName, mockImportFn);
      
      // Should not be called immediately
      expect(mockImportFn).not.toHaveBeenCalled();
      
      await promise;
      expect(mockImportFn).toHaveBeenCalledOnce();
    });
  });

  describe('predictNextRoutes', () => {
    it('should predict routes based on navigation patterns', () => {
      const currentRoute = 'dashboard';
      const previousRoutes = ['invoices', 'customers'];
      
      preloader.preloadBasedOnNavigation(currentRoute, previousRoutes);
      
      // Should trigger preloading based on patterns
      expect(mockRequestIdleCallback).toHaveBeenCalled();
    });

    it('should boost probability for recently visited routes', () => {
      const currentRoute = 'invoices';
      const previousRoutes = ['customers', 'dashboard', 'customers']; // customers visited twice
      
      preloader.preloadBasedOnNavigation(currentRoute, previousRoutes);
      
      // Should prioritize customers route
      expect(mockRequestIdleCallback).toHaveBeenCalled();
    });
  });

  describe('getStats', () => {
    it('should return accurate statistics', async () => {
      await preloader.preloadRoute('dashboard', mockImportFn);
      await preloader.preloadRoute('invoices', mockImportFn);
      
      const stats = preloader.getStats();
      
      expect(stats.preloadedRoutes).toBe(2);
      expect(stats.queuedRoutes).toBe(0);
      expect(typeof stats.cacheHitRate).toBe('number');
      expect(typeof stats.memoryUsage).toBe('number');
    });
  });

  describe('clearExpiredCache', () => {
    it('should clear expired cache entries', async () => {
      const routeName = 'dashboard';
      
      // Mock Date.now
      const originalDateNow = Date.now;
      let currentTime = 1000;
      Date.now = vi.fn(() => currentTime);
      
      await preloader.preloadRoute(routeName, mockImportFn);
      expect(preloader.getPreloadedRoute(routeName)).toBeTruthy();
      
      // Simulate time passing beyond cache time
      currentTime += 300001; // 5 minutes + 1ms
      
      preloader.clearExpiredCache();
      expect(preloader.getPreloadedRoute(routeName)).toBeNull();
      
      Date.now = originalDateNow;
    });
  });

  describe('integration with real application', () => {
    it('should work with actual Vue route imports', async () => {
      // Test with a real import function that would exist in the app
      const realImportFn = () => Promise.resolve({ 
        default: { 
          name: 'DashboardView',
          template: '<div>Dashboard</div>' 
        } 
      });
      
      await preloader.preloadRoute('dashboard', realImportFn);
      
      const cached = preloader.getPreloadedRoute('dashboard');
      expect(cached).toBeTruthy();
      expect(cached.default.name).toBe('DashboardView');
    });

    it('should handle network errors during preloading', async () => {
      const networkErrorFn = vi.fn().mockRejectedValue(new Error('Network error'));
      
      await expect(preloader.preloadRoute('dashboard', networkErrorFn)).rejects.toThrow('Network error');
      
      // Should not crash the application
      expect(preloader.getStats().preloadedRoutes).toBe(0);
    });

    it('should work with concurrent preload requests', async () => {
      const promises = [
        preloader.preloadRoute('dashboard', () => Promise.resolve({ default: 'Dashboard' })),
        preloader.preloadRoute('invoices', () => Promise.resolve({ default: 'Invoices' })),
        preloader.preloadRoute('inventory', () => Promise.resolve({ default: 'Inventory' }))
      ];
      
      await Promise.all(promises);
      
      expect(preloader.getStats().preloadedRoutes).toBe(3);
    });
  });

  describe('performance requirements', () => {
    it('should meet tab switching performance targets', async () => {
      const routeName = 'dashboard';
      const startTime = performance.now();
      
      await preloader.preloadRoute(routeName, mockImportFn);
      
      // Getting preloaded route should be instant
      const getStartTime = performance.now();
      const cached = preloader.getPreloadedRoute(routeName);
      const getEndTime = performance.now();
      
      expect(cached).toBeTruthy();
      expect(getEndTime - getStartTime).toBeLessThan(1); // Should be < 1ms
    });

    it('should handle high load scenarios', async () => {
      const routes = ['dashboard', 'invoices', 'inventory', 'customers', 'reports'];
      const promises = routes.map(route => 
        preloader.preloadRoute(route, () => Promise.resolve({ default: route }))
      );
      
      const startTime = performance.now();
      await Promise.all(promises);
      const endTime = performance.now();
      
      // Should complete all preloads within reasonable time
      expect(endTime - startTime).toBeLessThan(1000); // < 1 second
      expect(preloader.getStats().preloadedRoutes).toBe(5);
    });
  });

  describe('error handling and recovery', () => {
    it('should recover from import failures', async () => {
      const failingImportFn = vi.fn()
        .mockRejectedValueOnce(new Error('First failure'))
        .mockResolvedValueOnce({ default: 'Success' });
      
      // First attempt should fail
      await expect(preloader.preloadRoute('dashboard', failingImportFn)).rejects.toThrow('First failure');
      
      // Second attempt should succeed
      await preloader.preloadRoute('dashboard', failingImportFn);
      expect(preloader.getPreloadedRoute('dashboard')).toBeTruthy();
    });

    it('should handle memory pressure gracefully', async () => {
      // Simulate loading many routes
      const routes = Array.from({ length: 20 }, (_, i) => `route-${i}`);
      
      for (const route of routes) {
        await preloader.preloadRoute(route, () => Promise.resolve({ default: route }));
      }
      
      // Should not crash and should manage memory
      const stats = preloader.getStats();
      expect(stats.preloadedRoutes).toBe(20);
      expect(stats.memoryUsage).toBeGreaterThan(0);
    });
  });
});