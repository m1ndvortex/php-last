import type { RouteLocationNormalized } from 'vue-router';
import { debounce } from '@/utils/performanceOptimizations';

interface PreloadConfig {
  priority: 'high' | 'medium' | 'low';
  preloadDelay: number;
  cacheTime: number;
  dependencies?: string[];
}

interface PreloadedRoute {
  component: any;
  loadedAt: number;
  config: PreloadConfig;
}

export class RoutePreloader {
  private preloadedRoutes = new Map<string, PreloadedRoute>();
  private preloadQueue = new Set<string>();
  private isPreloading = false;
  private preloadConfigs = new Map<string, PreloadConfig>();

  constructor() {
    this.setupDefaultConfigs();
    this.setupIdlePreloading();
  }

  private setupDefaultConfigs() {
    // High priority routes - preload immediately
    this.preloadConfigs.set('dashboard', {
      priority: 'high',
      preloadDelay: 0,
      cacheTime: 5 * 60 * 1000, // 5 minutes
      dependencies: ['auth']
    });

    this.preloadConfigs.set('invoices', {
      priority: 'high',
      preloadDelay: 100,
      cacheTime: 3 * 60 * 1000, // 3 minutes
    });

    this.preloadConfigs.set('inventory', {
      priority: 'high',
      preloadDelay: 200,
      cacheTime: 3 * 60 * 1000,
    });

    // Medium priority routes
    this.preloadConfigs.set('customers', {
      priority: 'medium',
      preloadDelay: 500,
      cacheTime: 2 * 60 * 1000, // 2 minutes
    });

    this.preloadConfigs.set('reports', {
      priority: 'medium',
      preloadDelay: 1000,
      cacheTime: 2 * 60 * 1000,
    });

    // Low priority routes
    this.preloadConfigs.set('accounting', {
      priority: 'low',
      preloadDelay: 2000,
      cacheTime: 1 * 60 * 1000, // 1 minute
    });

    this.preloadConfigs.set('settings', {
      priority: 'low',
      preloadDelay: 3000,
      cacheTime: 1 * 60 * 1000,
    });
  }

  private setupIdlePreloading() {
    // Use requestIdleCallback for low-priority preloading
    const preloadOnIdle = () => {
      if ('requestIdleCallback' in window) {
        requestIdleCallback(() => {
          this.preloadLowPriorityRoutes();
        }, { timeout: 5000 });
      } else {
        setTimeout(() => {
          this.preloadLowPriorityRoutes();
        }, 2000);
      }
    };

    // Start idle preloading after initial load
    setTimeout(preloadOnIdle, 1000);
  }

  /**
   * Preload a specific route component
   */
  async preloadRoute(routeName: string, importFn: () => Promise<any>): Promise<void> {
    if (this.preloadedRoutes.has(routeName)) {
      const cached = this.preloadedRoutes.get(routeName)!;
      // Check if cache is still valid
      if (Date.now() - cached.loadedAt < cached.config.cacheTime) {
        return;
      }
    }

    if (this.preloadQueue.has(routeName)) {
      return; // Already queued
    }

    const config = this.preloadConfigs.get(routeName) || {
      priority: 'medium',
      preloadDelay: 500,
      cacheTime: 2 * 60 * 1000
    };

    this.preloadQueue.add(routeName);

    try {
      // Wait for configured delay
      if (config.preloadDelay > 0) {
        await new Promise(resolve => setTimeout(resolve, config.preloadDelay));
      }

      // Check dependencies first
      if (config.dependencies) {
        await this.preloadDependencies(config.dependencies);
      }

      const component = await importFn();
      
      this.preloadedRoutes.set(routeName, {
        component,
        loadedAt: Date.now(),
        config
      });

      console.log(`✅ Route preloaded: ${routeName} (${config.priority} priority)`);
    } catch (error) {
      console.warn(`❌ Failed to preload route: ${routeName}`, error);
    } finally {
      this.preloadQueue.delete(routeName);
    }
  }

  /**
   * Preload dependencies for a route
   */
  private async preloadDependencies(dependencies: string[]): Promise<void> {
    const promises = dependencies.map(dep => {
      // Handle different types of dependencies
      switch (dep) {
        case 'auth':
          return this.preloadAuthDependencies();
        default:
          return Promise.resolve();
      }
    });

    await Promise.allSettled(promises);
  }

  private async preloadAuthDependencies(): Promise<void> {
    // Preload auth-related components and data
    try {
      // This could preload user data, permissions, etc.
      console.log('🔐 Preloading auth dependencies');
    } catch (error) {
      console.warn('Failed to preload auth dependencies:', error);
    }
  }

  /**
   * Get preloaded route component
   */
  getPreloadedRoute(routeName: string): any | null {
    const cached = this.preloadedRoutes.get(routeName);
    if (!cached) return null;

    // Check if cache is still valid
    if (Date.now() - cached.loadedAt > cached.config.cacheTime) {
      this.preloadedRoutes.delete(routeName);
      return null;
    }

    return cached.component;
  }

  /**
   * Preload routes based on user navigation patterns
   */
  preloadBasedOnNavigation = debounce((currentRoute: string, previousRoutes: string[]) => {
    const predictions = this.predictNextRoutes(currentRoute, previousRoutes);
    
    predictions.forEach(({ route, probability }) => {
      if (probability > 0.3) { // Only preload if probability > 30%
        this.schedulePreload(route);
      }
    });
  }, 300);

  /**
   * Predict next routes based on navigation patterns
   */
  private predictNextRoutes(currentRoute: string, previousRoutes: string[]): Array<{ route: string; probability: number }> {
    const predictions: Array<{ route: string; probability: number }> = [];

    // Common navigation patterns
    const patterns: Record<string, Array<{ route: string; probability: number }>> = {
      'dashboard': [
        { route: 'invoices', probability: 0.6 },
        { route: 'inventory', probability: 0.5 },
        { route: 'customers', probability: 0.4 }
      ],
      'invoices': [
        { route: 'customers', probability: 0.7 },
        { route: 'inventory', probability: 0.5 },
        { route: 'dashboard', probability: 0.3 }
      ],
      'inventory': [
        { route: 'invoices', probability: 0.6 },
        { route: 'customers', probability: 0.4 },
        { route: 'reports', probability: 0.3 }
      ],
      'customers': [
        { route: 'invoices', probability: 0.8 },
        { route: 'reports', probability: 0.4 },
        { route: 'dashboard', probability: 0.3 }
      ]
    };

    const currentPredictions = patterns[currentRoute] || [];
    predictions.push(...currentPredictions);

    // Boost probability for recently visited routes
    previousRoutes.slice(-3).forEach(route => {
      const existing = predictions.find(p => p.route === route);
      if (existing) {
        existing.probability += 0.2;
      } else {
        predictions.push({ route, probability: 0.3 });
      }
    });

    return predictions.sort((a, b) => b.probability - a.probability);
  }

  /**
   * Schedule route preloading based on priority
   */
  private schedulePreload(routeName: string) {
    const config = this.preloadConfigs.get(routeName);
    if (!config) return;

    // Don't preload if already cached or queued
    if (this.preloadedRoutes.has(routeName) || this.preloadQueue.has(routeName)) {
      return;
    }

    // Get the route import function (this would be provided by the router)
    const importFn = this.getRouteImportFunction(routeName);
    if (importFn) {
      this.preloadRoute(routeName, importFn);
    }
  }

  /**
   * Get route import function - this would be integrated with the router
   */
  private getRouteImportFunction(routeName: string): (() => Promise<any>) | null {
    const importFunctions: Record<string, () => Promise<any>> = {
      'dashboard': () => import('@/views/DashboardView.vue'),
      'invoices': () => import('@/views/InvoicesView.vue'),
      'inventory': () => import('@/views/InventoryView.vue'),
      'customers': () => import('@/views/CustomersView.vue'),
      'reports': () => import('@/views/ReportsView.vue'),
      'accounting': () => import('@/views/AccountingView.vue'),
      'settings': () => import('@/views/SettingsView.vue'),
      'profile': () => import('@/views/ProfileView.vue')
    };

    return importFunctions[routeName] || null;
  }

  /**
   * Preload low priority routes during idle time
   */
  private preloadLowPriorityRoutes() {
    const lowPriorityRoutes = Array.from(this.preloadConfigs.entries())
      .filter(([_, config]) => config.priority === 'low')
      .map(([route]) => route);

    lowPriorityRoutes.forEach(route => {
      if (!this.preloadedRoutes.has(route) && !this.preloadQueue.has(route)) {
        this.schedulePreload(route);
      }
    });
  }

  /**
   * Clear expired cache entries
   */
  clearExpiredCache() {
    const now = Date.now();
    for (const [routeName, cached] of this.preloadedRoutes.entries()) {
      if (now - cached.loadedAt > cached.config.cacheTime) {
        this.preloadedRoutes.delete(routeName);
        console.log(`🗑️ Cleared expired cache for route: ${routeName}`);
      }
    }
  }

  /**
   * Get preloading statistics
   */
  getStats() {
    return {
      preloadedRoutes: this.preloadedRoutes.size,
      queuedRoutes: this.preloadQueue.size,
      cacheHitRate: this.calculateCacheHitRate(),
      memoryUsage: this.calculateMemoryUsage()
    };
  }

  private calculateCacheHitRate(): number {
    // This would be tracked over time in a real implementation
    return 0.75; // Placeholder
  }

  private calculateMemoryUsage(): number {
    // Estimate memory usage of preloaded routes
    return this.preloadedRoutes.size * 50; // Rough estimate in KB
  }
}

// Singleton instance
export const routePreloader = new RoutePreloader();