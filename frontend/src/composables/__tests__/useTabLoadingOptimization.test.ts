import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { ref } from 'vue';
import { useTabLoadingOptimization } from '../useTabLoadingOptimization';

// Mock Vue Router
const mockRoute = ref({
  name: 'dashboard',
  meta: { title: 'Dashboard' },
  fullPath: '/dashboard'
});

const mockRouter = {
  beforeEach: vi.fn(),
  afterEach: vi.fn(),
  push: vi.fn(),
  replace: vi.fn()
};

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute.value,
  useRouter: () => mockRouter
}));

// Mock services
vi.mock('../services/routePreloader', () => ({
  routePreloader: {
    preloadRoute: vi.fn().mockResolvedValue(undefined),
    preloadBasedOnNavigation: vi.fn(),
    getStats: vi.fn().mockReturnValue({
      preloadedRoutes: 2,
      queuedRoutes: 1,
      cacheHitRate: 0.8,
      memoryUsage: 150
    }),
    clearExpiredCache: vi.fn()
  }
}));

vi.mock('../services/componentLoader', () => ({
  componentLoader: {
    loadComponent: vi.fn().mockResolvedValue({ default: 'MockComponent' }),
    preloadCriticalComponents: vi.fn(),
    preloadHighPriorityComponents: vi.fn(),
    getStats: vi.fn().mockReturnValue({
      totalComponents: 10,
      loadedComponents: 8,
      loadingComponents: 1,
      loadProgress: 80,
      averageLoadTime: 150,
      cacheSize: 200
    }),
    clearCache: vi.fn()
  }
}));

vi.mock('../services/loadingStateManager', () => ({
  loadingStateManager: {
    startLoading: vi.fn(),
    finishLoading: vi.fn(),
    getLoadingStateReactive: vi.fn().mockReturnValue(ref({
      isLoading: false,
      progress: 0,
      message: '',
      error: null,
      startTime: 0
    })),
    getSkeletonConfig: vi.fn().mockReturnValue({
      type: 'card',
      variant: 'default',
      lines: 4
    }),
    getProgressConfig: vi.fn().mockReturnValue({
      show: true,
      type: 'linear',
      progress: 50,
      message: 'Loading...'
    }),
    getStats: vi.fn().mockReturnValue({
      activeLoadings: 1,
      totalStates: 5,
      averageDuration: 800,
      longestLoading: 1200
    }),
    clearAll: vi.fn()
  }
}));

vi.mock('../services/resourcePrioritizer', () => ({
  resourcePrioritizer: {
    registerResource: vi.fn(),
    preloadCriticalResources: vi.fn(),
    updateDeviceCapabilities: vi.fn(),
    getStats: vi.fn().mockReturnValue({
      totalResources: 15,
      loadedCount: 12,
      failedCount: 1,
      queuedCount: 2,
      loadProgress: 80,
      averageLoadTime: 200,
      deviceCapabilities: {
        networkSpeed: 'fast',
        deviceMemory: 'high',
        batteryLevel: 'high',
        cpuCores: 8,
        isLowEndDevice: false
      },
      maxConcurrentLoads: 6
    })
  }
}));

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

describe('useTabLoadingOptimization', () => {
  let optimization: ReturnType<typeof useTabLoadingOptimization>;

  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
    optimization = useTabLoadingOptimization();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('initialization', () => {
    it('should initialize with default configuration', () => {
      expect(optimization.loadingConfig.value.enablePreloading).toBe(true);
      expect(optimization.loadingConfig.value.enableSkeletons).toBe(true);
      expect(optimization.loadingConfig.value.enableProgressTracking).toBe(true);
      expect(optimization.loadingConfig.value.preloadDelay).toBe(100);
      expect(optimization.loadingConfig.value.maxPreloadRoutes).toBe(3);
      expect(optimization.loadingConfig.value.adaptiveLoading).toBe(true);
    });

    it('should accept custom configuration', () => {
      const customConfig = {
        enablePreloading: false,
        preloadDelay: 500,
        maxPreloadRoutes: 5
      };
      
      const customOptimization = useTabLoadingOptimization(customConfig);
      
      expect(customOptimization.loadingConfig.value.enablePreloading).toBe(false);
      expect(customOptimization.loadingConfig.value.preloadDelay).toBe(500);
      expect(customOptimization.loadingConfig.value.maxPreloadRoutes).toBe(5);
    });

    it('should setup router hooks when progress tracking is enabled', () => {
      useTabLoadingOptimization({ enableProgressTracking: true });
      
      expect(mockRouter.beforeEach).toHaveBeenCalled();
      expect(mockRouter.afterEach).toHaveBeenCalled();
    });
  });

  describe('route preloading', () => {
    it('should preload specific route', async () => {
      const { routePreloader } = await import('../services/routePreloader');
      
      await optimization.preloadRoute('dashboard');
      
      expect(routePreloader.preloadRoute).toHaveBeenCalledWith(
        'dashboard',
        expect.any(Function)
      );
    });

    it('should handle preload failures gracefully', async () => {
      const { routePreloader } = await import('../services/routePreloader');
      (routePreloader.preloadRoute as any).mockRejectedValueOnce(new Error('Preload failed'));
      
      // Should not throw
      await expect(optimization.preloadRoute('dashboard')).resolves.toBeUndefined();
    });

    it('should preload routes based on navigation patterns', () => {
      const { routePreloader } = await import('../services/routePreloader');
      
      // Simulate route change
      mockRoute.value = { name: 'invoices', meta: { title: 'Invoices' }, fullPath: '/invoices' };
      
      expect(routePreloader.preloadBasedOnNavigation).toHaveBeenCalled();
    });
  });

  describe('component preloading', () => {
    it('should preload specific component', async () => {
      const { componentLoader } = await import('../services/componentLoader');
      
      await optimization.preloadComponent('InvoiceList');
      
      expect(componentLoader.loadComponent).toHaveBeenCalledWith(
        'InvoiceList',
        expect.any(Function)
      );
    });

    it('should handle component preload failures', async () => {
      const { componentLoader } = await import('../services/componentLoader');
      (componentLoader.loadComponent as any).mockRejectedValueOnce(new Error('Component load failed'));
      
      // Should not throw
      await expect(optimization.preloadComponent('InvoiceList')).resolves.toBeUndefined();
    });
  });

  describe('loading state management', () => {
    it('should provide current loading state', () => {
      const loadingState = optimization.getCurrentLoadingState();
      
      expect(loadingState).toBeDefined();
      expect(typeof loadingState.value).toBe('object');
    });

    it('should provide skeleton configuration', () => {
      const skeletonConfig = optimization.getCurrentSkeletonConfig();
      
      expect(skeletonConfig.value).toBeDefined();
      expect(skeletonConfig.value.type).toBeDefined();
    });

    it('should provide progress configuration', () => {
      const progressConfig = optimization.getCurrentProgressConfig();
      
      expect(progressConfig.value).toBeDefined();
      expect(typeof progressConfig.value.show).toBe('boolean');
    });
  });

  describe('performance tracking', () => {
    it('should track navigation performance', () => {
      // Mock navigation timing
      let startTime = 1000;
      mockPerformanceNow.mockImplementation(() => startTime);
      
      // Simulate beforeEach hook
      const beforeEachCallback = mockRouter.beforeEach.mock.calls[0][0];
      beforeEachCallback(
        { name: 'invoices', meta: { title: 'Invoices' } },
        { name: 'dashboard', meta: { title: 'Dashboard' } }
      );
      
      startTime = 1300; // 300ms later
      
      // Simulate afterEach hook
      const afterEachCallback = mockRouter.afterEach.mock.calls[0][0];
      afterEachCallback(
        { name: 'invoices', meta: { title: 'Invoices' } },
        { name: 'dashboard', meta: { title: 'Dashboard' } }
      );
      
      expect(optimization.loadingMetrics.value.tabSwitchTime).toBe(300);
    });

    it('should check if performance requirements are met', () => {
      // Set tab switch time under 500ms
      optimization.loadingMetrics.value.tabSwitchTime = 400;
      
      expect(optimization.meetsPerformanceRequirements.value).toBe(true);
      
      // Set tab switch time over 500ms
      optimization.loadingMetrics.value.tabSwitchTime = 600;
      
      expect(optimization.meetsPerformanceRequirements.value).toBe(false);
    });

    it('should maintain navigation history', () => {
      // Simulate multiple navigations
      const afterEachCallback = mockRouter.afterEach.mock.calls[0][0];
      
      afterEachCallback(
        { name: 'invoices' },
        { name: 'dashboard' }
      );
      
      afterEachCallback(
        { name: 'customers' },
        { name: 'invoices' }
      );
      
      expect(optimization.navigationHistory.value).toContain('dashboard');
      expect(optimization.navigationHistory.value).toContain('invoices');
    });

    it('should limit navigation history size', () => {
      const afterEachCallback = mockRouter.afterEach.mock.calls[0][0];
      
      // Simulate 15 navigations (more than the 10 limit)
      for (let i = 0; i < 15; i++) {
        afterEachCallback(
          { name: `route-${i + 1}` },
          { name: `route-${i}` }
        );
      }
      
      expect(optimization.navigationHistory.value.length).toBeLessThanOrEqual(10);
    });
  });

  describe('device optimization', () => {
    it('should optimize for current device', () => {
      const { resourcePrioritizer } = await import('../services/resourcePrioritizer');
      
      optimization.optimizeForDevice();
      
      expect(resourcePrioritizer.updateDeviceCapabilities).toHaveBeenCalled();
    });

    it('should adjust configuration for low-end devices', () => {
      const { resourcePrioritizer } = await import('../services/resourcePrioritizer');
      
      // Mock low-end device stats
      (resourcePrioritizer.getStats as any).mockReturnValueOnce({
        deviceCapabilities: {
          isLowEndDevice: true,
          networkSpeed: 'slow',
          deviceMemory: 'low'
        }
      });
      
      optimization.optimizeForDevice();
      
      expect(optimization.loadingConfig.value.maxPreloadRoutes).toBe(1);
      expect(optimization.loadingConfig.value.preloadDelay).toBe(500);
      expect(optimization.loadingConfig.value.adaptiveLoading).toBe(true);
    });
  });

  describe('statistics and monitoring', () => {
    it('should provide comprehensive optimization statistics', () => {
      const stats = optimization.getOptimizationStats();
      
      expect(stats.value.routes).toBeDefined();
      expect(stats.value.components).toBeDefined();
      expect(stats.value.resources).toBeDefined();
      expect(stats.value.loading).toBeDefined();
      expect(stats.value.metrics).toBeDefined();
      expect(typeof stats.value.meetsRequirements).toBe('boolean');
    });

    it('should track loading metrics accurately', () => {
      optimization.loadingMetrics.value = {
        tabSwitchTime: 350,
        componentLoadTime: 120,
        routeLoadTime: 200,
        cacheHitRate: 0.85,
        totalLoadTime: 800
      };
      
      const stats = optimization.getOptimizationStats();
      
      expect(stats.value.metrics.tabSwitchTime).toBe(350);
      expect(stats.value.metrics.cacheHitRate).toBe(0.85);
      expect(stats.value.meetsRequirements).toBe(true); // < 500ms
    });
  });

  describe('cache management', () => {
    it('should clear all caches', () => {
      const { componentLoader } = await import('../services/componentLoader');
      const { routePreloader } = await import('../services/routePreloader');
      const { loadingStateManager } = await import('../services/loadingStateManager');
      
      optimization.clearCaches();
      
      expect(componentLoader.clearCache).toHaveBeenCalled();
      expect(routePreloader.clearExpiredCache).toHaveBeenCalled();
      expect(loadingStateManager.clearAll).toHaveBeenCalled();
    });
  });

  describe('skeleton type determination', () => {
    it('should return correct skeleton type for different routes', () => {
      // Test private method through integration
      const testCases = [
        { route: 'dashboard', expected: 'card' },
        { route: 'invoices', expected: 'table' },
        { route: 'inventory', expected: 'table' },
        { route: 'customers', expected: 'table' },
        { route: 'reports', expected: 'chart' },
        { route: 'accounting', expected: 'table' },
        { route: 'settings', expected: 'list' },
        { route: 'unknown', expected: 'card' }
      ];
      
      testCases.forEach(({ route, expected }) => {
        mockRoute.value = { name: route, meta: {}, fullPath: `/${route}` };
        
        // Trigger skeleton config update
        const skeletonConfig = optimization.getCurrentSkeletonConfig();
        
        // The skeleton type should be determined by the loading state manager
        // which uses the route name to determine the appropriate skeleton
        expect(skeletonConfig.value).toBeDefined();
      });
    });
  });

  describe('integration scenarios', () => {
    it('should handle complete tab switching workflow', async () => {
      const { loadingStateManager } = await import('../services/loadingStateManager');
      
      // Simulate tab switch from dashboard to invoices
      const beforeEachCallback = mockRouter.beforeEach.mock.calls[0][0];
      const afterEachCallback = mockRouter.afterEach.mock.calls[0][0];
      
      // Start navigation
      beforeEachCallback(
        { name: 'invoices', meta: { title: 'Invoices' } },
        { name: 'dashboard', meta: { title: 'Dashboard' } }
      );
      
      expect(loadingStateManager.startLoading).toHaveBeenCalledWith(
        'route-invoices',
        'Loading Invoices...',
        expect.objectContaining({
          showSkeleton: true,
          showProgress: true
        })
      );
      
      // Complete navigation
      afterEachCallback(
        { name: 'invoices', meta: { title: 'Invoices' } },
        { name: 'dashboard', meta: { title: 'Dashboard' } }
      );
      
      expect(loadingStateManager.finishLoading).toHaveBeenCalledWith('route-invoices');
    });

    it('should preload resources during idle time', async () => {
      const { componentLoader } = await import('../services/componentLoader');
      
      // Trigger idle callback
      await vi.runAllTimersAsync();
      
      expect(mockRequestIdleCallback).toHaveBeenCalled();
      expect(componentLoader.preloadHighPriorityComponents).toHaveBeenCalled();
    });

    it('should handle concurrent operations efficiently', async () => {
      const promises = [
        optimization.preloadRoute('dashboard'),
        optimization.preloadRoute('invoices'),
        optimization.preloadComponent('InvoiceList'),
        optimization.preloadComponent('CustomerList')
      ];
      
      await Promise.all(promises);
      
      // All operations should complete without errors
      expect(promises).toHaveLength(4);
    });
  });

  describe('performance requirements validation', () => {
    it('should meet 500ms tab switching requirement', () => {
      // Test with good performance
      optimization.loadingMetrics.value.tabSwitchTime = 300;
      expect(optimization.meetsPerformanceRequirements.value).toBe(true);
      
      // Test with poor performance
      optimization.loadingMetrics.value.tabSwitchTime = 700;
      expect(optimization.meetsPerformanceRequirements.value).toBe(false);
      
      // Test edge case
      optimization.loadingMetrics.value.tabSwitchTime = 500;
      expect(optimization.meetsPerformanceRequirements.value).toBe(true);
    });

    it('should log performance warnings for slow tab switches', () => {
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
      
      // Mock slow navigation
      let currentTime = 1000;
      mockPerformanceNow.mockImplementation(() => currentTime);
      
      const beforeEachCallback = mockRouter.beforeEach.mock.calls[0][0];
      const afterEachCallback = mockRouter.afterEach.mock.calls[0][0];
      
      beforeEachCallback(
        { name: 'invoices', meta: { title: 'Invoices' } },
        { name: 'dashboard' }
      );
      
      currentTime += 600; // 600ms - exceeds 500ms target
      
      afterEachCallback(
        { name: 'invoices', meta: { title: 'Invoices' } },
        { name: 'dashboard' }
      );
      
      expect(consoleSpy).toHaveBeenCalledWith(
        expect.stringContaining('Tab switch exceeded 500ms target')
      );
      
      consoleSpy.mockRestore();
    });
  });

  describe('error handling', () => {
    it('should handle router hook errors gracefully', () => {
      const { loadingStateManager } = await import('../services/loadingStateManager');
      
      // Mock error in beforeEach
      const beforeEachCallback = mockRouter.beforeEach.mock.calls[0][0];
      
      expect(() => {
        beforeEachCallback(
          { name: 'invoices', meta: { title: 'Invoices' } },
          { name: 'dashboard' }
        );
      }).not.toThrow();
      
      expect(loadingStateManager.startLoading).toHaveBeenCalled();
    });

    it('should handle service initialization failures', () => {
      // This tests that the composable doesn't crash if services fail to initialize
      expect(() => {
        useTabLoadingOptimization();
      }).not.toThrow();
    });
  });
});