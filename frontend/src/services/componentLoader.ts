import { ref, computed, type Ref } from 'vue';
import { debounce } from '@/utils/performanceOptimizations';

// Create Vue reactivity functions that work in test environment
const createRef = <T>(value: T): Ref<T> => {
  try {
    return ref(value) as Ref<T>;
  } catch {
    // Fallback for test environment
    return ref(value) as Ref<T>;
  }
};

interface ComponentLoadConfig {
  priority: 'critical' | 'high' | 'medium' | 'low';
  loadDelay: number;
  retryAttempts: number;
  retryDelay: number;
  preloadCondition?: () => boolean;
  dependencies?: string[];
}

interface LoadedComponent {
  component: any;
  loadedAt: number;
  loadTime: number;
  config: ComponentLoadConfig;
}

interface LoadingState {
  isLoading: boolean;
  error: Error | null;
  progress: number;
  retryCount: number;
}

export class ComponentLoader {
  private loadedComponents = new Map<string, LoadedComponent>();
  private loadingStates = new Map<string, Ref<LoadingState>>();
  private loadQueue = new Map<string, Promise<any>>();
  private loadConfigs = new Map<string, ComponentLoadConfig>();

  constructor() {
    this.setupDefaultConfigs();
  }

  private setupDefaultConfigs() {
    // Critical components - load immediately
    this.loadConfigs.set('AppHeader', {
      priority: 'critical',
      loadDelay: 0,
      retryAttempts: 3,
      retryDelay: 500
    });

    this.loadConfigs.set('AppNavigation', {
      priority: 'critical',
      loadDelay: 0,
      retryAttempts: 3,
      retryDelay: 500
    });

    // High priority components
    this.loadConfigs.set('DashboardWidget', {
      priority: 'high',
      loadDelay: 100,
      retryAttempts: 2,
      retryDelay: 1000
    });

    this.loadConfigs.set('InvoiceList', {
      priority: 'high',
      loadDelay: 200,
      retryAttempts: 2,
      retryDelay: 1000
    });

    // Medium priority components
    this.loadConfigs.set('InventoryList', {
      priority: 'medium',
      loadDelay: 500,
      retryAttempts: 2,
      retryDelay: 1500
    });

    this.loadConfigs.set('CustomerList', {
      priority: 'medium',
      loadDelay: 600,
      retryAttempts: 2,
      retryDelay: 1500
    });

    // Low priority components
    this.loadConfigs.set('ReportChart', {
      priority: 'low',
      loadDelay: 1000,
      retryAttempts: 1,
      retryDelay: 2000,
      preloadCondition: () => this.shouldLoadNonCritical()
    });

    this.loadConfigs.set('SettingsPanel', {
      priority: 'low',
      loadDelay: 1500,
      retryAttempts: 1,
      retryDelay: 2000,
      preloadCondition: () => this.shouldLoadNonCritical()
    });
  }

  /**
   * Load a component with priority-based loading
   */
  async loadComponent(
    componentName: string,
    importFn: () => Promise<any>,
    config?: Partial<ComponentLoadConfig>
  ): Promise<any> {
    // Check if already loaded
    const cached = this.loadedComponents.get(componentName);
    if (cached) {
      return cached.component;
    }

    // Check if already loading
    const existingLoad = this.loadQueue.get(componentName);
    if (existingLoad) {
      return existingLoad;
    }

    // Get or create loading state
    const loadingState = this.getLoadingState(componentName);
    const baseConfig = this.loadConfigs.get(componentName) || {};
    const mergedConfig = { ...baseConfig, ...config };
    
    const componentConfig: ComponentLoadConfig = {
      priority: mergedConfig.priority || 'medium',
      loadDelay: mergedConfig.loadDelay || 0,
      retryAttempts: mergedConfig.retryAttempts || 3,
      retryDelay: mergedConfig.retryDelay || 1000,
      preloadCondition: mergedConfig.preloadCondition,
      dependencies: mergedConfig.dependencies || []
    };

    // Check preload condition
    if (componentConfig.preloadCondition && !componentConfig.preloadCondition()) {
      console.log(`⏸️ Skipping load for ${componentName} - preload condition not met`);
      return null;
    }

    // Create loading promise
    const loadPromise = this.performLoad(componentName, importFn, componentConfig, loadingState);
    this.loadQueue.set(componentName, loadPromise);

    try {
      const result = await loadPromise;
      return result;
    } finally {
      this.loadQueue.delete(componentName);
    }
  }

  /**
   * Perform the actual component loading with retry logic
   */
  private async performLoad(
    componentName: string,
    importFn: () => Promise<any>,
    config: ComponentLoadConfig,
    loadingState: Ref<LoadingState>
  ): Promise<any> {
    const startTime = performance.now();

    // Set loading state
    loadingState.value = {
      isLoading: true,
      error: null,
      progress: 0,
      retryCount: 0
    };

    // Wait for configured delay based on priority
    if (config.loadDelay > 0) {
      await new Promise(resolve => setTimeout(resolve, config.loadDelay));
    }

    // Load dependencies first
    if (config.dependencies) {
      await this.loadDependencies(config.dependencies);
      loadingState.value.progress = 30;
    }

    let lastError: Error | null = null;

    // Retry logic
    for (let attempt = 0; attempt <= config.retryAttempts; attempt++) {
      try {
        loadingState.value.retryCount = attempt;
        loadingState.value.progress = 50 + (attempt * 20);

        const component = await importFn();
        const loadTime = performance.now() - startTime;

        // Cache the loaded component
        this.loadedComponents.set(componentName, {
          component,
          loadedAt: Date.now(),
          loadTime,
          config
        });

        // Update loading state
        loadingState.value = {
          isLoading: false,
          error: null,
          progress: 100,
          retryCount: attempt
        };

        console.log(`✅ Component loaded: ${componentName} (${loadTime.toFixed(2)}ms, ${config.priority} priority)`);
        return component;

      } catch (error) {
        lastError = error as Error;
        console.warn(`❌ Failed to load component: ${componentName} (attempt ${attempt + 1})`, error);

        if (attempt < config.retryAttempts) {
          // Wait before retry
          await new Promise(resolve => setTimeout(resolve, config.retryDelay * (attempt + 1)));
        }
      }
    }

    // All retries failed
    loadingState.value = {
      isLoading: false,
      error: lastError,
      progress: 0,
      retryCount: config.retryAttempts
    };

    throw lastError || new Error(`Failed to load component: ${componentName}`);
  }

  /**
   * Load component dependencies
   */
  private async loadDependencies(dependencies: string[]): Promise<void> {
    const promises = dependencies.map(dep => {
      const importFn = this.getDependencyImportFunction(dep);
      if (importFn) {
        return this.loadComponent(dep, importFn);
      }
      return Promise.resolve();
    });

    await Promise.allSettled(promises);
  }

  /**
   * Get dependency import function
   */
  private getDependencyImportFunction(dependency: string): (() => Promise<any>) | null {
    const dependencyImports: Record<string, () => Promise<any>> = {
      'BaseModal': () => import('@/components/ui/BaseModal.vue'),
      'ConfirmationModal': () => import('@/components/ui/ConfirmationModal.vue'),
      'LoadingSpinner': () => import('@/components/ui/LoadingSpinner.vue'),
      'ErrorDisplay': () => import('@/components/ui/ErrorDisplay.vue')
    };

    return dependencyImports[dependency] || null;
  }

  /**
   * Get or create loading state for a component
   */
  private getLoadingState(componentName: string): Ref<LoadingState> {
    if (!this.loadingStates.has(componentName)) {
      this.loadingStates.set(componentName, createRef({
        isLoading: false,
        error: null,
        progress: 0,
        retryCount: 0
      }));
    }
    return this.loadingStates.get(componentName)!;
  }

  /**
   * Check if non-critical components should be loaded
   */
  private shouldLoadNonCritical(): boolean {
    // Check network conditions
    if ('connection' in navigator) {
      const connection = (navigator as any).connection;
      if (connection.saveData || connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g') {
        return false;
      }
    }

    // Check device memory
    if ('deviceMemory' in navigator) {
      const deviceMemory = (navigator as any).deviceMemory;
      if (deviceMemory < 4) { // Less than 4GB RAM
        return false;
      }
    }

    return true;
  }

  /**
   * Preload components based on priority
   */
  preloadCriticalComponents() {
    const criticalComponents = Array.from(this.loadConfigs.entries())
      .filter(([_, config]) => config.priority === 'critical')
      .map(([name]) => name);

    criticalComponents.forEach(componentName => {
      const importFn = this.getComponentImportFunction(componentName);
      if (importFn && !this.loadedComponents.has(componentName)) {
        this.loadComponent(componentName, importFn).catch(error => {
          console.warn(`Failed to preload critical component: ${componentName}`, error);
        });
      }
    });
  }

  /**
   * Preload high priority components during idle time
   */
  preloadHighPriorityComponents() {
    const preloadHighPriority = () => {
      const highPriorityComponents = Array.from(this.loadConfigs.entries())
        .filter(([_, config]) => config.priority === 'high')
        .map(([name]) => name);

      highPriorityComponents.forEach(componentName => {
        const importFn = this.getComponentImportFunction(componentName);
        if (importFn && !this.loadedComponents.has(componentName)) {
          this.loadComponent(componentName, importFn).catch(error => {
            console.warn(`Failed to preload high priority component: ${componentName}`, error);
          });
        }
      });
    };

    if ('requestIdleCallback' in window) {
      requestIdleCallback(preloadHighPriority, { timeout: 2000 });
    } else {
      setTimeout(preloadHighPriority, 500);
    }
  }

  /**
   * Get component import function
   */
  private getComponentImportFunction(componentName: string): (() => Promise<any>) | null {
    const componentImports: Record<string, () => Promise<any>> = {
      'AppHeader': () => import('@/components/layout/AppHeader.vue'),
      'AppNavigation': () => import('@/components/layout/AppNavigation.vue'),
      'DashboardWidget': () => import('@/components/dashboard/DashboardWidget.vue'),
      'InvoiceList': () => import('@/components/invoices/InvoiceList.vue'),
      'InventoryList': () => import('@/components/inventory/InventoryList.vue'),
      'CustomerList': () => import('@/components/customers/CustomerList.vue'),
      'ReportChart': () => import('@/components/reports/ReportChart.vue'),
      'SettingsPanel': () => import('@/components/settings/SettingsPanel.vue')
    };

    return componentImports[componentName] || null;
  }

  /**
   * Get loading state for a component (reactive)
   */
  getComponentLoadingState(componentName: string) {
    return computed(() => this.getLoadingState(componentName).value);
  }

  /**
   * Check if component is loaded
   */
  isComponentLoaded(componentName: string): boolean {
    return this.loadedComponents.has(componentName);
  }

  /**
   * Get loaded component
   */
  getLoadedComponent(componentName: string): any | null {
    const cached = this.loadedComponents.get(componentName);
    return cached ? cached.component : null;
  }

  /**
   * Clear component cache
   */
  clearCache(componentName?: string) {
    if (componentName) {
      this.loadedComponents.delete(componentName);
      this.loadingStates.delete(componentName);
    } else {
      this.loadedComponents.clear();
      this.loadingStates.clear();
    }
  }

  /**
   * Get loading statistics
   */
  getStats() {
    const totalComponents = this.loadConfigs.size;
    const loadedComponents = this.loadedComponents.size;
    const loadingComponents = this.loadQueue.size;

    const loadTimes = Array.from(this.loadedComponents.values()).map(c => c.loadTime);
    const averageLoadTime = loadTimes.length > 0 
      ? loadTimes.reduce((sum, time) => sum + time, 0) / loadTimes.length 
      : 0;

    return {
      totalComponents,
      loadedComponents,
      loadingComponents,
      loadProgress: (loadedComponents / totalComponents) * 100,
      averageLoadTime: Math.round(averageLoadTime),
      cacheSize: this.calculateCacheSize()
    };
  }

  private calculateCacheSize(): number {
    // Estimate cache size in KB
    return this.loadedComponents.size * 25; // Rough estimate
  }
}

// Singleton instance
export const componentLoader = new ComponentLoader();