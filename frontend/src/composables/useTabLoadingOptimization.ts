import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

// Create Vue reactivity functions that work in test environment
const createRef = <T>(value: T) => {
  try {
    return ref(value);
  } catch {
    // Fallback for test environment
    return {
      value,
      __v_isRef: true
    } as any;
  }
};
import { routePreloader } from '@/services/routePreloader';
import { componentLoader } from '@/services/componentLoader';
import { loadingStateManager } from '@/services/loadingStateManager';
import { resourcePrioritizer } from '@/services/resourcePrioritizer';

interface TabLoadingConfig {
  enablePreloading: boolean;
  enableSkeletons: boolean;
  enableProgressTracking: boolean;
  preloadDelay: number;
  maxPreloadRoutes: number;
  adaptiveLoading: boolean;
}

interface LoadingMetrics {
  tabSwitchTime: number;
  componentLoadTime: number;
  routeLoadTime: number;
  cacheHitRate: number;
  totalLoadTime: number;
}

export function useTabLoadingOptimization(config: Partial<TabLoadingConfig> = {}) {
  const route = useRoute();
  const router = useRouter();

  // Configuration with defaults
  const loadingConfig = createRef<TabLoadingConfig>({
    enablePreloading: true,
    enableSkeletons: true,
    enableProgressTracking: true,
    preloadDelay: 100,
    maxPreloadRoutes: 3,
    adaptiveLoading: true,
    ...config
  });

  // State
  const isOptimizing = createRef(false);
  const currentLoadingContext = createRef<string>('');
  const navigationHistory = createRef<string[]>([]);
  const loadingMetrics = createRef<LoadingMetrics>({
    tabSwitchTime: 0,
    componentLoadTime: 0,
    routeLoadTime: 0,
    cacheHitRate: 0,
    totalLoadTime: 0
  });

  // Performance tracking
  const navigationStartTime = createRef(0);
  const componentLoadStartTime = createRef(0);

  /**
   * Initialize tab loading optimization
   */
  const initialize = () => {
    if (loadingConfig.value.enablePreloading) {
      setupRoutePreloading();
      setupComponentPreloading();
    }

    if (loadingConfig.value.enableProgressTracking) {
      setupProgressTracking();
    }

    setupNavigationTracking();
    preloadCriticalResources();
  };

  /**
   * Setup route preloading based on navigation patterns
   */
  const setupRoutePreloading = () => {
    // Preload routes based on current route
    const preloadForCurrentRoute = () => {
      const currentRouteName = route.name as string;
      if (currentRouteName) {
        routePreloader.preloadBasedOnNavigation(
          currentRouteName,
          navigationHistory.value
        );
      }
    };

    // Preload on route change
    watch(() => route.name, preloadForCurrentRoute, { immediate: true });

    // Preload on idle
    if ('requestIdleCallback' in window) {
      requestIdleCallback(() => {
        preloadForCurrentRoute();
      }, { timeout: 2000 });
    }
  };

  /**
   * Setup component preloading
   */
  const setupComponentPreloading = () => {
    // Preload critical components immediately
    componentLoader.preloadCriticalComponents();

    // Preload high priority components on idle
    if ('requestIdleCallback' in window) {
      requestIdleCallback(() => {
        componentLoader.preloadHighPriorityComponents();
      }, { timeout: 1000 });
    }
  };

  /**
   * Setup progress tracking for loading states
   */
  const setupProgressTracking = () => {
    // Track route navigation performance
    router.beforeEach((to, from) => {
      navigationStartTime.value = performance.now();
      currentLoadingContext.value = `route-${String(to.name)}`;
      
      if (loadingConfig.value.enableSkeletons) {
        loadingStateManager.startLoading(
          currentLoadingContext.value,
          `Loading ${to.meta?.title || String(to.name)}...`,
          {
            showSkeleton: true,
            skeletonType: getSkeletonTypeForRoute(to.name as string),
            showProgress: true
          }
        );
      }
    });

    router.afterEach((to, from) => {
      const navigationTime = performance.now() - navigationStartTime.value;
      loadingMetrics.value.tabSwitchTime = navigationTime;
      
      // Update navigation history
      if (from.name) {
        navigationHistory.value.push(from.name as string);
        // Keep only last 10 routes
        if (navigationHistory.value.length > 10) {
          navigationHistory.value = navigationHistory.value.slice(-10);
        }
      }

      // Finish loading
      if (currentLoadingContext.value) {
        loadingStateManager.finishLoading(currentLoadingContext.value);
      }

      // Log performance
      console.log(`🚀 Tab switch completed: ${navigationTime.toFixed(2)}ms`);
      
      // Check if we meet the 500ms requirement
      if (navigationTime > 500) {
        console.warn(`⚠️ Tab switch exceeded 500ms target: ${navigationTime.toFixed(2)}ms`);
      }
    });
  };

  /**
   * Setup navigation tracking for analytics
   */
  const setupNavigationTracking = () => {
    let pageLoadStartTime = performance.now();

    // Track page visibility changes
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        pageLoadStartTime = performance.now();
      }
    });

    // Track performance metrics
    if ('PerformanceObserver' in window) {
      const observer = new PerformanceObserver((list) => {
        const entries = list.getEntries();
        entries.forEach((entry) => {
          if (entry.entryType === 'navigation') {
            const navEntry = entry as PerformanceNavigationTiming;
            loadingMetrics.value.totalLoadTime = navEntry.loadEventEnd - (navEntry as any).navigationStart;
          }
        });
      });

      try {
        observer.observe({ entryTypes: ['navigation'] });
      } catch (error) {
        console.warn('Performance observer not supported:', error);
      }
    }
  };

  /**
   * Preload critical resources for the application
   */
  const preloadCriticalResources = () => {
    // Register critical resources
    resourcePrioritizer.registerResource(
      'app-header',
      'component',
      { priority: 'critical', loadOrder: 1 },
      () => import('@/components/layout/AppHeader.vue')
    );

    resourcePrioritizer.registerResource(
      'app-navigation',
      'component',
      { priority: 'critical', loadOrder: 2 },
      () => import('@/components/layout/AppNavigation.vue')
    );

    // Preload critical resources
    resourcePrioritizer.preloadCriticalResources();
  };

  /**
   * Get skeleton type based on route
   */
  const getSkeletonTypeForRoute = (routeName: string): 'card' | 'table' | 'list' | 'chart' => {
    const skeletonMap: Record<string, 'card' | 'table' | 'list' | 'chart'> = {
      'dashboard': 'card',
      'invoices': 'table',
      'inventory': 'table',
      'customers': 'table',
      'reports': 'chart',
      'accounting': 'table',
      'settings': 'list'
    };

    return skeletonMap[routeName] || 'card';
  };

  /**
   * Preload specific route
   */
  const preloadRoute = async (routeName: string): Promise<void> => {
    const importFunctions: Record<string, () => Promise<any>> = {
      'dashboard': () => import('@/views/DashboardView.vue'),
      'invoices': () => import('@/views/InvoicesView.vue'),
      'inventory': () => import('@/views/InventoryView.vue'),
      'customers': () => import('@/views/CustomersView.vue'),
      'reports': () => import('@/views/ReportsView.vue'),
      'accounting': () => import('@/views/AccountingView.vue'),
      'settings': () => import('@/views/SettingsView.vue')
    };

    const importFn = importFunctions[routeName];
    if (importFn) {
      await routePreloader.preloadRoute(routeName, importFn);
    }
  };

  /**
   * Preload component
   */
  const preloadComponent = async (componentName: string): Promise<void> => {
    const importFunctions: Record<string, () => Promise<any>> = {
      'InvoiceList': () => import('@/components/invoices/InvoiceList.vue'),
      'InventoryList': () => import('@/components/inventory/InventoryList.vue'),
      'CustomerList': () => import('@/components/customers/CustomerList.vue'),
      'DashboardWidget': () => import('@/components/dashboard/DashboardWidget.vue')
    };

    const importFn = importFunctions[componentName];
    if (importFn) {
      await componentLoader.loadComponent(componentName, importFn);
    }
  };

  /**
   * Get loading state for current context
   */
  const getCurrentLoadingState = () => {
    return loadingStateManager.getLoadingStateReactive(currentLoadingContext.value);
  };

  /**
   * Get skeleton configuration for current route
   */
  const getCurrentSkeletonConfig = () => {
    return computed(() => {
      return loadingStateManager.getSkeletonConfig(currentLoadingContext.value);
    });
  };

  /**
   * Get progress configuration for current route
   */
  const getCurrentProgressConfig = () => {
    return computed(() => {
      return loadingStateManager.getProgressConfig(currentLoadingContext.value);
    });
  };

  /**
   * Check if tab switching meets performance requirements
   */
  const meetsPerformanceRequirements = computed(() => {
    return loadingMetrics.value.tabSwitchTime <= 500; // 500ms requirement
  });

  /**
   * Get optimization statistics
   */
  const getOptimizationStats = () => {
    return computed(() => {
      const routeStats = routePreloader.getStats();
      const componentStats = componentLoader.getStats();
      const resourceStats = resourcePrioritizer.getStats();
      const loadingStats = loadingStateManager.getStats();

      return {
        routes: routeStats,
        components: componentStats,
        resources: resourceStats,
        loading: loadingStats,
        metrics: loadingMetrics.value,
        meetsRequirements: meetsPerformanceRequirements.value
      };
    });
  };

  /**
   * Optimize for current device capabilities
   */
  const optimizeForDevice = () => {
    // Update device capabilities
    resourcePrioritizer.updateDeviceCapabilities();

    // Adjust configuration based on device
    const stats = resourcePrioritizer.getStats();
    if (stats.deviceCapabilities.isLowEndDevice) {
      loadingConfig.value.maxPreloadRoutes = 1;
      loadingConfig.value.preloadDelay = 500;
      loadingConfig.value.adaptiveLoading = true;
    }
  };

  /**
   * Clear optimization caches
   */
  const clearCaches = () => {
    componentLoader.clearCache();
    routePreloader.clearExpiredCache();
    loadingStateManager.clearAll();
  };

  // Lifecycle
  onMounted(() => {
    initialize();
    optimizeForDevice();
  });

  onUnmounted(() => {
    clearCaches();
  });

  return {
    // Configuration
    loadingConfig,
    
    // State
    isOptimizing,
    currentLoadingContext,
    navigationHistory,
    loadingMetrics,
    
    // Methods
    preloadRoute,
    preloadComponent,
    optimizeForDevice,
    clearCaches,
    
    // Computed
    getCurrentLoadingState,
    getCurrentSkeletonConfig,
    getCurrentProgressConfig,
    meetsPerformanceRequirements,
    getOptimizationStats
  };
}