import { ref, computed, reactive, type Ref } from 'vue';
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

const createReactive = <T extends object>(value: T): T => {
  try {
    return reactive(value) as T;
  } catch {
    // Fallback for test environment
    return reactive(value) as T;
  }
};

interface LoadingState {
  isLoading: boolean;
  progress: number;
  message: string;
  error: Error | null;
  startTime: number;
  estimatedTime?: number;
}

interface LoadingConfig {
  showSkeleton: boolean;
  showProgress: boolean;
  showMessage: boolean;
  minDisplayTime: number;
  maxDisplayTime: number;
  skeletonType: 'card' | 'table' | 'list' | 'chart' | 'custom';
  progressType: 'linear' | 'circular' | 'dots';
}

interface SkeletonConfig {
  type: 'card' | 'table' | 'list' | 'chart' | 'custom';
  variant?: string;
  lines?: number;
  columns?: number;
  rows?: number;
  showHeader?: boolean;
  showFooter?: boolean;
}

export class LoadingStateManager {
  private loadingStates = new Map<string, Ref<LoadingState>>();
  private loadingConfigs = new Map<string, LoadingConfig>();
  private globalLoading = createRef(false);
  private activeLoadings = createReactive(new Set<string>());

  constructor() {
    this.setupDefaultConfigs();
  }

  private setupDefaultConfigs() {
    // Route loading configs
    this.loadingConfigs.set('route', {
      showSkeleton: true,
      showProgress: true,
      showMessage: true,
      minDisplayTime: 200,
      maxDisplayTime: 5000,
      skeletonType: 'card',
      progressType: 'linear'
    });

    // Component loading configs
    this.loadingConfigs.set('component', {
      showSkeleton: true,
      showProgress: false,
      showMessage: false,
      minDisplayTime: 100,
      maxDisplayTime: 3000,
      skeletonType: 'card',
      progressType: 'dots'
    });

    // API loading configs
    this.loadingConfigs.set('api', {
      showSkeleton: false,
      showProgress: true,
      showMessage: true,
      minDisplayTime: 300,
      maxDisplayTime: 10000,
      skeletonType: 'card',
      progressType: 'circular'
    });

    // Data loading configs
    this.loadingConfigs.set('data', {
      showSkeleton: true,
      showProgress: false,
      showMessage: false,
      minDisplayTime: 150,
      maxDisplayTime: 5000,
      skeletonType: 'table',
      progressType: 'linear'
    });
  }

  /**
   * Start loading for a specific context
   */
  startLoading(
    context: string,
    message = 'Loading...',
    config?: Partial<LoadingConfig>
  ): void {
    const loadingState = this.getLoadingState(context);
    const baseConfig = this.loadingConfigs.get('component') || {};
    const mergedConfig = { ...baseConfig, ...config };
    
    const loadingConfig: LoadingConfig = {
      showSkeleton: mergedConfig.showSkeleton || false,
      showProgress: mergedConfig.showProgress || true,
      showMessage: mergedConfig.showMessage || true,
      minDisplayTime: mergedConfig.minDisplayTime || 300,
      maxDisplayTime: mergedConfig.maxDisplayTime || 10000,
      skeletonType: mergedConfig.skeletonType || 'card',
      progressType: mergedConfig.progressType || 'circular'
    };

    loadingState.value = {
      isLoading: true,
      progress: 0,
      message,
      error: null,
      startTime: Date.now(),
      estimatedTime: this.estimateLoadingTime(context)
    };

    this.activeLoadings.add(context);
    this.updateGlobalLoading();

    // Store config for this loading session
    this.loadingConfigs.set(context, loadingConfig);

    console.log(`🔄 Loading started: ${context} - ${message}`);
  }

  /**
   * Update loading progress
   */
  updateProgress(context: string, progress: number, message?: string): void {
    const loadingState = this.getLoadingState(context);
    
    if (loadingState.value.isLoading) {
      loadingState.value.progress = Math.min(Math.max(progress, 0), 100);
      
      if (message) {
        loadingState.value.message = message;
      }

      // Update estimated time based on progress
      if (progress > 0 && loadingState.value.startTime) {
        const elapsed = Date.now() - loadingState.value.startTime;
        const estimatedTotal = (elapsed / progress) * 100;
        loadingState.value.estimatedTime = estimatedTotal;
      }
    }
  }

  /**
   * Finish loading for a specific context
   */
  finishLoading(context: string, error?: Error): void {
    const loadingState = this.getLoadingState(context);
    const config = this.loadingConfigs.get(context);

    if (!loadingState.value.isLoading) return;

    const elapsed = Date.now() - loadingState.value.startTime;
    const minDisplayTime = config?.minDisplayTime || 200;

    const completeLoading = () => {
      loadingState.value = {
        ...loadingState.value,
        isLoading: false,
        progress: error ? 0 : 100,
        error: error || null
      };

      this.activeLoadings.delete(context);
      this.updateGlobalLoading();

      if (error) {
        console.error(`❌ Loading failed: ${context}`, error);
      } else {
        console.log(`✅ Loading completed: ${context} (${elapsed}ms)`);
      }
    };

    // Ensure minimum display time for better UX
    if (elapsed < minDisplayTime) {
      setTimeout(completeLoading, minDisplayTime - elapsed);
    } else {
      completeLoading();
    }
  }

  /**
   * Set loading error
   */
  setError(context: string, error: Error): void {
    const loadingState = this.getLoadingState(context);
    
    loadingState.value = {
      ...loadingState.value,
      isLoading: false,
      error,
      progress: 0
    };

    this.activeLoadings.delete(context);
    this.updateGlobalLoading();
  }

  /**
   * Get loading state for a context
   */
  private getLoadingState(context: string): Ref<LoadingState> {
    if (!this.loadingStates.has(context)) {
      this.loadingStates.set(context, createRef({
        isLoading: false,
        progress: 0,
        message: '',
        error: null,
        startTime: 0
      }));
    }
    return this.loadingStates.get(context)!;
  }

  /**
   * Get reactive loading state
   */
  getLoadingStateReactive(context: string) {
    return computed(() => this.getLoadingState(context).value);
  }

  /**
   * Check if any loading is active
   */
  isAnyLoading(): boolean {
    return this.activeLoadings.size > 0;
  }

  /**
   * Get global loading state
   */
  getGlobalLoadingState() {
    return computed(() => this.globalLoading.value);
  }

  /**
   * Update global loading state
   */
  private updateGlobalLoading(): void {
    this.globalLoading.value = this.activeLoadings.size > 0;
  }

  /**
   * Get skeleton configuration for a context
   */
  getSkeletonConfig(context: string): SkeletonConfig {
    const config = this.loadingConfigs.get(context);
    const loadingState = this.getLoadingState(context).value;

    if (!config || !config.showSkeleton || !loadingState.isLoading) {
      return { type: 'card' };
    }

    // Determine skeleton config based on context and type
    switch (config.skeletonType) {
      case 'table':
        return {
          type: 'table',
          columns: 6,
          rows: 5,
          showHeader: true
        };
      case 'list':
        return {
          type: 'list',
          lines: 8
        };
      case 'chart':
        return {
          type: 'chart',
          showHeader: true,
          showFooter: false
        };
      case 'card':
      default:
        return {
          type: 'card',
          variant: 'default',
          lines: 4,
          showHeader: true,
          showFooter: false
        };
    }
  }

  /**
   * Get progress configuration for a context
   */
  getProgressConfig(context: string) {
    const config = this.loadingConfigs.get(context);
    const loadingState = this.getLoadingState(context).value;

    return {
      show: config?.showProgress && loadingState.isLoading,
      type: config?.progressType || 'linear',
      progress: loadingState.progress,
      message: config?.showMessage ? loadingState.message : '',
      estimatedTime: loadingState.estimatedTime
    };
  }

  /**
   * Estimate loading time based on context and historical data
   */
  private estimateLoadingTime(context: string): number {
    // This would use historical data in a real implementation
    const estimates: Record<string, number> = {
      'route': 800,
      'component': 400,
      'api': 1200,
      'data': 600,
      'dashboard': 1000,
      'invoices': 800,
      'inventory': 900,
      'customers': 700,
      'reports': 1500,
      'accounting': 1200
    };

    return estimates[context] || 800;
  }

  /**
   * Create a loading wrapper for async operations
   */
  withLoading<T>(
    context: string,
    operation: () => Promise<T>,
    message = 'Loading...',
    config?: Partial<LoadingConfig>
  ): Promise<T> {
    return new Promise(async (resolve, reject) => {
      this.startLoading(context, message, config);

      try {
        const result = await operation();
        this.finishLoading(context);
        resolve(result);
      } catch (error) {
        this.finishLoading(context, error as Error);
        reject(error);
      }
    });
  }

  /**
   * Create a loading wrapper with progress updates
   */
  withProgressLoading<T>(
    context: string,
    operation: (updateProgress: (progress: number, message?: string) => void) => Promise<T>,
    initialMessage = 'Loading...',
    config?: Partial<LoadingConfig>
  ): Promise<T> {
    return new Promise(async (resolve, reject) => {
      this.startLoading(context, initialMessage, config);

      const updateProgress = (progress: number, message?: string) => {
        this.updateProgress(context, progress, message);
      };

      try {
        const result = await operation(updateProgress);
        this.finishLoading(context);
        resolve(result);
      } catch (error) {
        this.finishLoading(context, error as Error);
        reject(error);
      }
    });
  }

  /**
   * Batch loading operations
   */
  async batchLoading<T>(
    operations: Array<{
      context: string;
      operation: () => Promise<T>;
      message?: string;
      config?: Partial<LoadingConfig>;
    }>
  ): Promise<T[]> {
    const promises = operations.map(({ context, operation, message, config }) =>
      this.withLoading(context, operation, message, config)
    );

    return Promise.all(promises);
  }

  /**
   * Clear all loading states
   */
  clearAll(): void {
    this.loadingStates.clear();
    this.activeLoadings.clear();
    this.globalLoading.value = false;
  }

  /**
   * Get loading statistics
   */
  getStats() {
    const activeCount = this.activeLoadings.size;
    const totalStates = this.loadingStates.size;
    
    const activeDurations = Array.from(this.activeLoadings).map(context => {
      const state = this.getLoadingState(context).value;
      return state.startTime ? Date.now() - state.startTime : 0;
    });

    const averageDuration = activeDurations.length > 0
      ? activeDurations.reduce((sum, duration) => sum + duration, 0) / activeDurations.length
      : 0;

    return {
      activeLoadings: activeCount,
      totalStates,
      averageDuration: Math.round(averageDuration),
      longestLoading: Math.max(...activeDurations, 0)
    };
  }
}

// Singleton instance
export const loadingStateManager = new LoadingStateManager();