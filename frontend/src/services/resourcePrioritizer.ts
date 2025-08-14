import { debounce, throttle } from '@/utils/performanceOptimizations';

interface ResourceConfig {
  priority: 'critical' | 'high' | 'medium' | 'low';
  loadOrder: number;
  dependencies?: string[];
  conditions?: {
    networkSpeed?: 'fast' | 'slow' | 'any';
    deviceMemory?: 'high' | 'medium' | 'low' | 'any';
    batteryLevel?: 'high' | 'medium' | 'low' | 'any';
  };
  fallback?: string;
}

interface ResourceItem {
  id: string;
  type: 'component' | 'route' | 'data' | 'image' | 'script' | 'style';
  url?: string;
  importFn?: () => Promise<any>;
  config: ResourceConfig;
  loadedAt?: number;
  loadTime?: number;
  error?: Error;
}

interface LoadingQueue {
  critical: ResourceItem[];
  high: ResourceItem[];
  medium: ResourceItem[];
  low: ResourceItem[];
}

interface DeviceCapabilities {
  networkSpeed: 'fast' | 'slow';
  deviceMemory: 'high' | 'medium' | 'low';
  batteryLevel: 'high' | 'medium' | 'low';
  cpuCores: number;
  isLowEndDevice: boolean;
}

export class ResourcePrioritizer {
  private resources = new Map<string, ResourceItem>();
  private loadingQueue: LoadingQueue = {
    critical: [],
    high: [],
    medium: [],
    low: []
  };
  private loadedResources = new Set<string>();
  private failedResources = new Set<string>();
  private isProcessingQueue = false;
  private deviceCapabilities: DeviceCapabilities;
  private maxConcurrentLoads = 3;

  constructor() {
    this.deviceCapabilities = this.detectDeviceCapabilities();
    this.setupResourceConfigs();
    this.startQueueProcessor();
  }

  /**
   * Detect device capabilities for adaptive loading
   */
  private detectDeviceCapabilities(): DeviceCapabilities {
    let networkSpeed: 'fast' | 'slow' = 'fast';
    let deviceMemory: 'high' | 'medium' | 'low' = 'medium';
    let batteryLevel: 'high' | 'medium' | 'low' = 'high';
    let cpuCores = 4;

    // Detect network speed
    if ('connection' in navigator) {
      const connection = (navigator as any).connection;
      const effectiveType = connection.effectiveType;
      
      if (effectiveType === 'slow-2g' || effectiveType === '2g' || connection.saveData) {
        networkSpeed = 'slow';
      }
    }

    // Detect device memory
    if ('deviceMemory' in navigator) {
      const memory = (navigator as any).deviceMemory;
      if (memory >= 8) {
        deviceMemory = 'high';
      } else if (memory >= 4) {
        deviceMemory = 'medium';
      } else {
        deviceMemory = 'low';
      }
    }

    // Detect battery level
    if ('getBattery' in navigator) {
      (navigator as any).getBattery().then((battery: any) => {
        if (battery.level > 0.5) {
          batteryLevel = 'high';
        } else if (battery.level > 0.2) {
          batteryLevel = 'medium';
        } else {
          batteryLevel = 'low';
        }
      });
    }

    // Detect CPU cores
    if ('hardwareConcurrency' in navigator) {
      cpuCores = navigator.hardwareConcurrency || 4;
    }

    const isLowEndDevice = deviceMemory === 'low' || cpuCores < 4 || networkSpeed === 'slow';

    return {
      networkSpeed,
      deviceMemory,
      batteryLevel,
      cpuCores,
      isLowEndDevice
    };
  }

  /**
   * Setup default resource configurations
   */
  private setupResourceConfigs() {
    // Adjust max concurrent loads based on device capabilities
    if (this.deviceCapabilities.isLowEndDevice) {
      this.maxConcurrentLoads = 2;
    } else if (this.deviceCapabilities.cpuCores >= 8) {
      this.maxConcurrentLoads = 6;
    }
  }

  /**
   * Register a resource for prioritized loading
   */
  registerResource(
    id: string,
    type: ResourceItem['type'],
    config: ResourceConfig,
    importFn?: () => Promise<any>,
    url?: string
  ): void {
    const resource: ResourceItem = {
      id,
      type,
      config,
      importFn,
      url
    };

    this.resources.set(id, resource);
    
    // Add to appropriate queue based on priority
    if (this.shouldLoadResource(resource)) {
      this.addToQueue(resource);
    }

    console.log(`📋 Resource registered: ${id} (${config.priority} priority)`);
  }

  /**
   * Check if resource should be loaded based on conditions
   */
  private shouldLoadResource(resource: ResourceItem): boolean {
    const { conditions } = resource.config;
    
    if (!conditions) return true;

    // Check network speed condition
    if (conditions.networkSpeed && conditions.networkSpeed !== 'any') {
      if (conditions.networkSpeed !== this.deviceCapabilities.networkSpeed) {
        return false;
      }
    }

    // Check device memory condition
    if (conditions.deviceMemory && conditions.deviceMemory !== 'any') {
      if (conditions.deviceMemory !== this.deviceCapabilities.deviceMemory) {
        return false;
      }
    }

    // Check battery level condition
    if (conditions.batteryLevel && conditions.batteryLevel !== 'any') {
      if (conditions.batteryLevel !== this.deviceCapabilities.batteryLevel) {
        return false;
      }
    }

    return true;
  }

  /**
   * Add resource to appropriate loading queue
   */
  private addToQueue(resource: ResourceItem): void {
    const priority = resource.config.priority;
    
    // Check if already in queue or loaded
    if (this.isResourceInQueue(resource.id) || this.loadedResources.has(resource.id)) {
      return;
    }

    // Insert in order based on loadOrder
    const queue = this.loadingQueue[priority];
    const insertIndex = queue.findIndex(item => item.config.loadOrder > resource.config.loadOrder);
    
    if (insertIndex === -1) {
      queue.push(resource);
    } else {
      queue.splice(insertIndex, 0, resource);
    }

    console.log(`📥 Resource queued: ${resource.id} (${priority} priority, order: ${resource.config.loadOrder})`);
  }

  /**
   * Check if resource is already in any queue
   */
  private isResourceInQueue(resourceId: string): boolean {
    return Object.values(this.loadingQueue).some(queue =>
      queue.some((item: any) => item.id === resourceId)
    );
  }

  /**
   * Start the queue processor
   */
  private startQueueProcessor(): void {
    const processQueue = async () => {
      if (this.isProcessingQueue) return;
      
      this.isProcessingQueue = true;
      
      try {
        await this.processLoadingQueue();
      } catch (error) {
        console.error('Error processing loading queue:', error);
      } finally {
        this.isProcessingQueue = false;
      }
    };

    // Process queue every 1 second (reduced from 100ms)
    setInterval(processQueue, 1000);

    // Also process on idle
    if ('requestIdleCallback' in window) {
      const scheduleIdleProcessing = () => {
        requestIdleCallback(() => {
          processQueue().then(() => {
            scheduleIdleProcessing();
          });
        }, { timeout: 1000 });
      };
      scheduleIdleProcessing();
    }
  }

  /**
   * Process the loading queue based on priority
   */
  private async processLoadingQueue(): Promise<void> {
    const currentlyLoading = this.getCurrentlyLoadingCount();
    
    if (currentlyLoading >= this.maxConcurrentLoads) {
      return; // Already at max capacity
    }

    // Process queues in priority order
    const priorities: (keyof LoadingQueue)[] = ['critical', 'high', 'medium', 'low'];
    
    for (const priority of priorities) {
      const queue = this.loadingQueue[priority];
      const availableSlots = this.maxConcurrentLoads - this.getCurrentlyLoadingCount();
      
      if (availableSlots <= 0) break;
      
      // Load resources from this priority level
      const resourcesToLoad = queue.splice(0, availableSlots);
      
      for (const resource of resourcesToLoad) {
        this.loadResource(resource).catch(error => {
          console.error(`Failed to load resource: ${resource.id}`, error);
        });
      }
      
      if (resourcesToLoad.length > 0) {
        break; // Focus on current priority level
      }
    }
  }

  /**
   * Get count of currently loading resources
   */
  private getCurrentlyLoadingCount(): number {
    // This would track active loading promises in a real implementation
    return 0; // Placeholder
  }

  /**
   * Load a specific resource
   */
  private async loadResource(resource: ResourceItem): Promise<void> {
    const startTime = performance.now();
    
    try {
      // Check dependencies first
      if (resource.config.dependencies) {
        await this.loadDependencies(resource.config.dependencies);
      }

      let result: any;

      // Load based on resource type
      switch (resource.type) {
        case 'component':
        case 'route':
          if (resource.importFn) {
            result = await resource.importFn();
          }
          break;
        
        case 'script':
          if (resource.url) {
            result = await this.loadScript(resource.url);
          }
          break;
        
        case 'style':
          if (resource.url) {
            result = await this.loadStylesheet(resource.url);
          }
          break;
        
        case 'image':
          if (resource.url) {
            result = await this.loadImage(resource.url);
          }
          break;
        
        case 'data':
          if (resource.importFn) {
            result = await resource.importFn();
          }
          break;
      }

      const loadTime = performance.now() - startTime;
      
      // Update resource
      resource.loadedAt = Date.now();
      resource.loadTime = loadTime;
      
      this.loadedResources.add(resource.id);
      
      console.log(`✅ Resource loaded: ${resource.id} (${loadTime.toFixed(2)}ms)`);
      
    } catch (error) {
      resource.error = error as Error;
      this.failedResources.add(resource.id);
      
      // Try fallback if available
      if (resource.config.fallback) {
        console.warn(`⚠️ Loading fallback for: ${resource.id}`);
        await this.loadFallback(resource.config.fallback);
      }
      
      throw error;
    }
  }

  /**
   * Load resource dependencies
   */
  private async loadDependencies(dependencies: string[]): Promise<void> {
    const dependencyPromises = dependencies.map(depId => {
      const dependency = this.resources.get(depId);
      if (dependency && !this.loadedResources.has(depId)) {
        return this.loadResource(dependency);
      }
      return Promise.resolve();
    });

    await Promise.allSettled(dependencyPromises);
  }

  /**
   * Load fallback resource
   */
  private async loadFallback(fallbackId: string): Promise<void> {
    const fallback = this.resources.get(fallbackId);
    if (fallback && !this.loadedResources.has(fallbackId)) {
      await this.loadResource(fallback);
    }
  }

  /**
   * Load external script
   */
  private loadScript(url: string): Promise<void> {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = url;
      script.async = true;
      script.onload = () => resolve();
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  /**
   * Load external stylesheet
   */
  private loadStylesheet(url: string): Promise<void> {
    return new Promise((resolve, reject) => {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = url;
      link.onload = () => resolve();
      link.onerror = reject;
      document.head.appendChild(link);
    });
  }

  /**
   * Load image
   */
  private loadImage(url: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = reject;
      img.src = url;
    });
  }

  /**
   * Preload critical resources immediately
   */
  preloadCriticalResources(): void {
    const criticalResources = Array.from(this.resources.values())
      .filter(resource => resource.config.priority === 'critical')
      .sort((a, b) => a.config.loadOrder - b.config.loadOrder);

    criticalResources.forEach(resource => {
      if (!this.loadedResources.has(resource.id) && !this.isResourceInQueue(resource.id)) {
        this.addToQueue(resource);
      }
    });
  }

  /**
   * Preload resources for a specific route
   */
  preloadForRoute(routeName: string): void {
    const routeResources = Array.from(this.resources.values())
      .filter(resource => 
        resource.id.includes(routeName) || 
        resource.config.priority === 'high'
      );

    routeResources.forEach(resource => {
      if (!this.loadedResources.has(resource.id) && this.shouldLoadResource(resource)) {
        this.addToQueue(resource);
      }
    });
  }

  /**
   * Get resource loading status
   */
  getResourceStatus(resourceId: string): 'not-loaded' | 'queued' | 'loading' | 'loaded' | 'failed' {
    if (this.loadedResources.has(resourceId)) return 'loaded';
    if (this.failedResources.has(resourceId)) return 'failed';
    if (this.isResourceInQueue(resourceId)) return 'queued';
    return 'not-loaded';
  }

  /**
   * Get loading statistics
   */
  getStats() {
    const totalResources = this.resources.size;
    const loadedCount = this.loadedResources.size;
    const failedCount = this.failedResources.size;
    const queuedCount = Object.values(this.loadingQueue).reduce((sum, queue) => sum + queue.length, 0);

    const loadTimes = Array.from(this.resources.values())
      .filter(resource => resource.loadTime)
      .map(resource => resource.loadTime!);

    const averageLoadTime = loadTimes.length > 0
      ? loadTimes.reduce((sum, time) => sum + time, 0) / loadTimes.length
      : 0;

    return {
      totalResources,
      loadedCount,
      failedCount,
      queuedCount,
      loadProgress: (loadedCount / totalResources) * 100,
      averageLoadTime: Math.round(averageLoadTime),
      deviceCapabilities: this.deviceCapabilities,
      maxConcurrentLoads: this.maxConcurrentLoads
    };
  }

  /**
   * Clear failed resources and retry
   */
  retryFailedResources(): void {
    const failedResourceIds = Array.from(this.failedResources);
    
    failedResourceIds.forEach(resourceId => {
      const resource = this.resources.get(resourceId);
      if (resource) {
        this.failedResources.delete(resourceId);
        resource.error = undefined;
        
        if (this.shouldLoadResource(resource)) {
          this.addToQueue(resource);
        }
      }
    });
  }

  /**
   * Update device capabilities (for dynamic adaptation)
   */
  updateDeviceCapabilities(): void {
    this.deviceCapabilities = this.detectDeviceCapabilities();
    console.log('📱 Device capabilities updated:', this.deviceCapabilities);
  }
}

// Singleton instance
export const resourcePrioritizer = new ResourcePrioritizer();