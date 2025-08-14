import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { ComponentLoader } from '../componentLoader';

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

// Mock navigator properties for device detection
Object.defineProperty(navigator, 'connection', {
  value: {
    effectiveType: '4g',
    saveData: false
  },
  writable: true
});

Object.defineProperty(navigator, 'deviceMemory', {
  value: 8,
  writable: true
});

describe('ComponentLoader', () => {
  let loader: ComponentLoader;
  let mockImportFn: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    loader = new ComponentLoader();
    mockImportFn = vi.fn().mockResolvedValue({ default: 'MockComponent' });
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.clearAllTimers();
  });

  describe('loadComponent', () => {
    it('should load a component successfully', async () => {
      const componentName = 'TestComponent';
      
      const result = await loader.loadComponent(componentName, mockImportFn);
      
      expect(mockImportFn).toHaveBeenCalledOnce();
      expect(result).toEqual({ default: 'MockComponent' });
      expect(loader.isComponentLoaded(componentName)).toBe(true);
    });

    it('should return cached component on subsequent calls', async () => {
      const componentName = 'TestComponent';
      
      const result1 = await loader.loadComponent(componentName, mockImportFn);
      const result2 = await loader.loadComponent(componentName, mockImportFn);
      
      expect(mockImportFn).toHaveBeenCalledOnce();
      expect(result1).toBe(result2);
    });

    it('should handle loading failures with retry logic', async () => {
      const componentName = 'TestComponent';
      const failingImportFn = vi.fn()
        .mockRejectedValueOnce(new Error('First failure'))
        .mockRejectedValueOnce(new Error('Second failure'))
        .mockResolvedValueOnce({ default: 'Success' });
      
      const result = await loader.loadComponent(componentName, failingImportFn, {
        retryAttempts: 2,
        retryDelay: 10
      });
      
      expect(failingImportFn).toHaveBeenCalledTimes(3);
      expect(result).toEqual({ default: 'Success' });
    });

    it('should fail after exhausting retry attempts', async () => {
      const componentName = 'TestComponent';
      const failingImportFn = vi.fn().mockRejectedValue(new Error('Persistent failure'));
      
      await expect(loader.loadComponent(componentName, failingImportFn, {
        retryAttempts: 1,
        retryDelay: 10
      })).rejects.toThrow('Persistent failure');
      
      expect(failingImportFn).toHaveBeenCalledTimes(2); // Initial + 1 retry
    });

    it('should respect priority-based loading delays', async () => {
      const criticalComponent = 'CriticalComponent';
      const lowPriorityComponent = 'LowPriorityComponent';
      
      const criticalStartTime = performance.now();
      await loader.loadComponent(criticalComponent, mockImportFn, {
        priority: 'critical',
        loadDelay: 0
      });
      const criticalEndTime = performance.now();
      
      const lowPriorityStartTime = performance.now();
      await loader.loadComponent(lowPriorityComponent, mockImportFn, {
        priority: 'low',
        loadDelay: 100
      });
      const lowPriorityEndTime = performance.now();
      
      // Critical should load faster
      expect(criticalEndTime - criticalStartTime).toBeLessThan(lowPriorityEndTime - lowPriorityStartTime);
    });

    it('should skip loading when preload condition is not met', async () => {
      const componentName = 'TestComponent';
      
      const result = await loader.loadComponent(componentName, mockImportFn, {
        preloadCondition: () => false
      });
      
      expect(mockImportFn).not.toHaveBeenCalled();
      expect(result).toBeNull();
    });

    it('should load dependencies before main component', async () => {
      const componentName = 'TestComponent';
      const dependencyImportFn = vi.fn().mockResolvedValue({ default: 'Dependency' });
      
      // Mock dependency loading
      const originalGetDependencyImportFunction = (loader as any).getDependencyImportFunction;
      (loader as any).getDependencyImportFunction = vi.fn().mockReturnValue(dependencyImportFn);
      
      await loader.loadComponent(componentName, mockImportFn, {
        dependencies: ['TestDependency']
      });
      
      expect(dependencyImportFn).toHaveBeenCalled();
      expect(mockImportFn).toHaveBeenCalled();
      
      // Restore original method
      (loader as any).getDependencyImportFunction = originalGetDependencyImportFunction;
    });
  });

  describe('getComponentLoadingState', () => {
    it('should track loading state correctly', async () => {
      const componentName = 'TestComponent';
      const loadingState = loader.getComponentLoadingState(componentName);
      
      expect(loadingState.value.isLoading).toBe(false);
      
      const loadPromise = loader.loadComponent(componentName, () => 
        new Promise(resolve => setTimeout(() => resolve({ default: 'Component' }), 50))
      );
      
      // Should be loading
      expect(loadingState.value.isLoading).toBe(true);
      expect(loadingState.value.progress).toBeGreaterThan(0);
      
      await loadPromise;
      
      // Should be completed
      expect(loadingState.value.isLoading).toBe(false);
      expect(loadingState.value.progress).toBe(100);
      expect(loadingState.value.error).toBeNull();
    });

    it('should track error state correctly', async () => {
      const componentName = 'TestComponent';
      const loadingState = loader.getComponentLoadingState(componentName);
      const error = new Error('Load failed');
      
      try {
        await loader.loadComponent(componentName, vi.fn().mockRejectedValue(error), {
          retryAttempts: 0
        });
      } catch (e) {
        // Expected to fail
      }
      
      expect(loadingState.value.isLoading).toBe(false);
      expect(loadingState.value.error).toBe(error);
      expect(loadingState.value.progress).toBe(0);
    });
  });

  describe('preloadCriticalComponents', () => {
    it('should preload critical components', () => {
      const originalGetComponentImportFunction = (loader as any).getComponentImportFunction;
      (loader as any).getComponentImportFunction = vi.fn().mockReturnValue(mockImportFn);
      
      loader.preloadCriticalComponents();
      
      expect((loader as any).getComponentImportFunction).toHaveBeenCalled();
      
      // Restore original method
      (loader as any).getComponentImportFunction = originalGetComponentImportFunction;
    });
  });

  describe('preloadHighPriorityComponents', () => {
    it('should preload high priority components during idle time', () => {
      const originalGetComponentImportFunction = (loader as any).getComponentImportFunction;
      (loader as any).getComponentImportFunction = vi.fn().mockReturnValue(mockImportFn);
      
      loader.preloadHighPriorityComponents();
      
      expect(mockRequestIdleCallback).toHaveBeenCalled();
      
      // Restore original method
      (loader as any).getComponentImportFunction = originalGetComponentImportFunction;
    });
  });

  describe('getStats', () => {
    it('should return accurate statistics', async () => {
      await loader.loadComponent('Component1', mockImportFn);
      await loader.loadComponent('Component2', mockImportFn);
      
      const stats = loader.getStats();
      
      expect(stats.loadedComponents).toBe(2);
      expect(stats.loadProgress).toBeGreaterThan(0);
      expect(typeof stats.averageLoadTime).toBe('number');
      expect(typeof stats.cacheSize).toBe('number');
    });
  });

  describe('clearCache', () => {
    it('should clear specific component cache', async () => {
      const componentName = 'TestComponent';
      
      await loader.loadComponent(componentName, mockImportFn);
      expect(loader.isComponentLoaded(componentName)).toBe(true);
      
      loader.clearCache(componentName);
      expect(loader.isComponentLoaded(componentName)).toBe(false);
    });

    it('should clear all cache when no component specified', async () => {
      await loader.loadComponent('Component1', mockImportFn);
      await loader.loadComponent('Component2', mockImportFn);
      
      expect(loader.getStats().loadedComponents).toBe(2);
      
      loader.clearCache();
      expect(loader.getStats().loadedComponents).toBe(0);
    });
  });

  describe('device capability adaptation', () => {
    it('should adapt loading behavior for low-end devices', async () => {
      // Mock low-end device
      Object.defineProperty(navigator, 'deviceMemory', {
        value: 2,
        writable: true
      });
      
      Object.defineProperty(navigator, 'connection', {
        value: {
          effectiveType: '2g',
          saveData: true
        },
        writable: true
      });
      
      const newLoader = new ComponentLoader();
      
      // Should skip non-critical components on low-end devices
      const result = await newLoader.loadComponent('TestComponent', mockImportFn, {
        priority: 'low',
        preloadCondition: () => (newLoader as any).shouldLoadNonCritical()
      });
      
      expect(result).toBeNull(); // Should not load on low-end device
    });

    it('should load normally on high-end devices', async () => {
      // Mock high-end device
      Object.defineProperty(navigator, 'deviceMemory', {
        value: 16,
        writable: true
      });
      
      Object.defineProperty(navigator, 'connection', {
        value: {
          effectiveType: '4g',
          saveData: false
        },
        writable: true
      });
      
      const newLoader = new ComponentLoader();
      
      const result = await newLoader.loadComponent('TestComponent', mockImportFn, {
        priority: 'low',
        preloadCondition: () => (newLoader as any).shouldLoadNonCritical()
      });
      
      expect(result).toBeTruthy(); // Should load on high-end device
    });
  });

  describe('integration with real application', () => {
    it('should work with actual Vue component imports', async () => {
      const realImportFn = () => Promise.resolve({
        default: {
          name: 'TestComponent',
          template: '<div>Test</div>',
          setup() {
            return {};
          }
        }
      });
      
      const result = await loader.loadComponent('TestComponent', realImportFn);
      
      expect(result.default.name).toBe('TestComponent');
      expect(result.default.template).toBe('<div>Test</div>');
    });

    it('should handle concurrent component loading', async () => {
      const components = ['Component1', 'Component2', 'Component3'];
      const promises = components.map(name => 
        loader.loadComponent(name, () => Promise.resolve({ default: name }))
      );
      
      const results = await Promise.all(promises);
      
      expect(results).toHaveLength(3);
      expect(loader.getStats().loadedComponents).toBe(3);
    });

    it('should maintain performance under load', async () => {
      const componentCount = 50;
      const components = Array.from({ length: componentCount }, (_, i) => `Component${i}`);
      
      const startTime = performance.now();
      
      const promises = components.map(name => 
        loader.loadComponent(name, () => Promise.resolve({ default: name }))
      );
      
      await Promise.all(promises);
      
      const endTime = performance.now();
      const totalTime = endTime - startTime;
      
      // Should complete within reasonable time
      expect(totalTime).toBeLessThan(2000); // < 2 seconds
      expect(loader.getStats().loadedComponents).toBe(componentCount);
    });
  });

  describe('performance requirements', () => {
    it('should meet component loading performance targets', async () => {
      const componentName = 'FastComponent';
      
      const startTime = performance.now();
      await loader.loadComponent(componentName, mockImportFn, {
        priority: 'critical',
        loadDelay: 0
      });
      const endTime = performance.now();
      
      // Critical components should load quickly
      expect(endTime - startTime).toBeLessThan(100); // < 100ms
    });

    it('should handle memory efficiently', async () => {
      const initialStats = loader.getStats();
      
      // Load many components
      for (let i = 0; i < 20; i++) {
        await loader.loadComponent(`Component${i}`, () => 
          Promise.resolve({ default: `Component${i}` })
        );
      }
      
      const finalStats = loader.getStats();
      
      // Memory usage should be reasonable
      expect(finalStats.cacheSize).toBeGreaterThan(initialStats.cacheSize);
      expect(finalStats.cacheSize).toBeLessThan(1000); // < 1MB estimated
    });
  });
});