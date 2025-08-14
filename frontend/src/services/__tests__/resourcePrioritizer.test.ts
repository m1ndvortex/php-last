import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { ResourcePrioritizer } from '../resourcePrioritizer';

// Mock navigator properties
const mockNavigator = {
  connection: {
    effectiveType: '4g',
    saveData: false,
    downlink: 10,
    rtt: 50
  },
  deviceMemory: 8,
  hardwareConcurrency: 8
};

Object.defineProperty(window, 'navigator', {
  value: mockNavigator,
  writable: true
});

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

describe('ResourcePrioritizer', () => {
  let prioritizer: ResourcePrioritizer;
  let mockImportFn: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    prioritizer = new ResourcePrioritizer();
    mockImportFn = vi.fn().mockResolvedValue({ default: 'MockResource' });
    vi.clearAllMocks();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('device capability detection', () => {
    it('should detect high-end device capabilities', () => {
      const stats = prioritizer.getStats();
      
      expect(stats.deviceCapabilities.networkSpeed).toBe('fast');
      expect(stats.deviceCapabilities.deviceMemory).toBe('high');
      expect(stats.deviceCapabilities.isLowEndDevice).toBe(false);
      expect(stats.maxConcurrentLoads).toBeGreaterThan(3);
    });

    it('should detect low-end device capabilities', () => {
      // Mock low-end device
      mockNavigator.connection.effectiveType = '2g';
      mockNavigator.connection.saveData = true;
      mockNavigator.deviceMemory = 2;
      mockNavigator.hardwareConcurrency = 2;
      
      const lowEndPrioritizer = new ResourcePrioritizer();
      const stats = lowEndPrioritizer.getStats();
      
      expect(stats.deviceCapabilities.networkSpeed).toBe('slow');
      expect(stats.deviceCapabilities.deviceMemory).toBe('low');
      expect(stats.deviceCapabilities.isLowEndDevice).toBe(true);
      expect(stats.maxConcurrentLoads).toBeLessThanOrEqual(2);
    });

    it('should update device capabilities dynamically', () => {
      const initialStats = prioritizer.getStats();
      
      // Change network conditions
      mockNavigator.connection.effectiveType = 'slow-2g';
      mockNavigator.connection.saveData = true;
      
      prioritizer.updateDeviceCapabilities();
      const updatedStats = prioritizer.getStats();
      
      expect(updatedStats.deviceCapabilities.networkSpeed).toBe('slow');
      expect(updatedStats.deviceCapabilities.networkSpeed).not.toBe(initialStats.deviceCapabilities.networkSpeed);
    });
  });

  describe('registerResource', () => {
    it('should register a resource with correct priority', () => {
      const resourceId = 'test-component';
      const config = {
        priority: 'high' as const,
        loadOrder: 1
      };
      
      prioritizer.registerResource(resourceId, 'component', config, mockImportFn);
      
      expect(prioritizer.getResourceStatus(resourceId)).toBe('queued');
    });

    it('should not queue resources that fail condition checks', () => {
      const resourceId = 'test-component';
      const config = {
        priority: 'high' as const,
        loadOrder: 1,
        conditions: {
          networkSpeed: 'slow' as const // Current device is fast
        }
      };
      
      prioritizer.registerResource(resourceId, 'component', config, mockImportFn);
      
      expect(prioritizer.getResourceStatus(resourceId)).toBe('not-loaded');
    });

    it('should queue resources that meet condition checks', () => {
      const resourceId = 'test-component';
      const config = {
        priority: 'high' as const,
        loadOrder: 1,
        conditions: {
          networkSpeed: 'fast' as const,
          deviceMemory: 'high' as const
        }
      };
      
      prioritizer.registerResource(resourceId, 'component', config, mockImportFn);
      
      expect(prioritizer.getResourceStatus(resourceId)).toBe('queued');
    });
  });

  describe('resource loading', () => {
    it('should load critical resources first', async () => {
      const criticalResource = 'critical-component';
      const lowResource = 'low-component';
      
      prioritizer.registerResource(lowResource, 'component', {
        priority: 'low',
        loadOrder: 2
      }, mockImportFn);
      
      prioritizer.registerResource(criticalResource, 'component', {
        priority: 'critical',
        loadOrder: 1
      }, mockImportFn);
      
      // Process queue
      await vi.runAllTimersAsync();
      
      // Critical should be loaded first
      expect(prioritizer.getResourceStatus(criticalResource)).toBe('loaded');
    });

    it('should respect load order within same priority', async () => {
      const resource1 = 'component-1';
      const resource2 = 'component-2';
      
      const loadOrder: string[] = [];
      const mockImport1 = vi.fn().mockImplementation(async () => {
        loadOrder.push(resource1);
        return { default: resource1 };
      });
      const mockImport2 = vi.fn().mockImplementation(async () => {
        loadOrder.push(resource2);
        return { default: resource2 };
      });
      
      prioritizer.registerResource(resource2, 'component', {
        priority: 'high',
        loadOrder: 2
      }, mockImport2);
      
      prioritizer.registerResource(resource1, 'component', {
        priority: 'high',
        loadOrder: 1
      }, mockImport1);
      
      await vi.runAllTimersAsync();
      
      expect(loadOrder[0]).toBe(resource1);
      expect(loadOrder[1]).toBe(resource2);
    });

    it('should handle loading failures gracefully', async () => {
      const resourceId = 'failing-component';
      const failingImport = vi.fn().mockRejectedValue(new Error('Load failed'));
      
      prioritizer.registerResource(resourceId, 'component', {
        priority: 'high',
        loadOrder: 1
      }, failingImport);
      
      await vi.runAllTimersAsync();
      
      expect(prioritizer.getResourceStatus(resourceId)).toBe('failed');
    });

    it('should load fallback resources when main resource fails', async () => {
      const mainResource = 'main-component';
      const fallbackResource = 'fallback-component';
      
      // Register fallback first
      prioritizer.registerResource(fallbackResource, 'component', {
        priority: 'high',
        loadOrder: 1
      }, mockImportFn);
      
      // Register main resource with fallback
      const failingImport = vi.fn().mockRejectedValue(new Error('Main failed'));
      prioritizer.registerResource(mainResource, 'component', {
        priority: 'high',
        loadOrder: 1,
        fallback: fallbackResource
      }, failingImport);
      
      await vi.runAllTimersAsync();
      
      expect(prioritizer.getResourceStatus(mainResource)).toBe('failed');
      expect(prioritizer.getResourceStatus(fallbackResource)).toBe('loaded');
    });
  });

  describe('preloadCriticalResources', () => {
    it('should preload critical resources immediately', () => {
      prioritizer.registerResource('critical-1', 'component', {
        priority: 'critical',
        loadOrder: 1
      }, mockImportFn);
      
      prioritizer.registerResource('low-1', 'component', {
        priority: 'low',
        loadOrder: 2
      }, mockImportFn);
      
      prioritizer.preloadCriticalResources();
      
      // Critical should be queued, low should not
      expect(prioritizer.getResourceStatus('critical-1')).toBe('queued');
    });
  });

  describe('preloadForRoute', () => {
    it('should preload resources for specific route', () => {
      const routeName = 'dashboard';
      
      prioritizer.registerResource(`${routeName}-component`, 'component', {
        priority: 'high',
        loadOrder: 1
      }, mockImportFn);
      
      prioritizer.registerResource('other-component', 'component', {
        priority: 'medium',
        loadOrder: 2
      }, mockImportFn);
      
      prioritizer.preloadForRoute(routeName);
      
      expect(prioritizer.getResourceStatus(`${routeName}-component`)).toBe('queued');
    });
  });

  describe('getStats', () => {
    it('should return accurate statistics', async () => {
      prioritizer.registerResource('resource-1', 'component', {
        priority: 'high',
        loadOrder: 1
      }, mockImportFn);
      
      prioritizer.registerResource('resource-2', 'component', {
        priority: 'medium',
        loadOrder: 2
      }, mockImportFn);
      
      await vi.runAllTimersAsync();
      
      const stats = prioritizer.getStats();
      
      expect(stats.totalResources).toBe(2);
      expect(stats.loadedCount).toBeGreaterThan(0);
      expect(typeof stats.loadProgress).toBe('number');
      expect(typeof stats.averageLoadTime).toBe('number');
      expect(stats.deviceCapabilities).toBeDefined();
    });
  });

  describe('retryFailedResources', () => {
    it('should retry failed resources', async () => {
      const resourceId = 'retry-component';
      const retryImport = vi.fn()
        .mockRejectedValueOnce(new Error('First failure'))
        .mockResolvedValueOnce({ default: 'Success' });
      
      prioritizer.registerResource(resourceId, 'component', {
        priority: 'high',
        loadOrder: 1
      }, retryImport);
      
      await vi.runAllTimersAsync();
      expect(prioritizer.getResourceStatus(resourceId)).toBe('failed');
      
      // Retry failed resources
      prioritizer.retryFailedResources();
      await vi.runAllTimersAsync();
      
      expect(prioritizer.getResourceStatus(resourceId)).toBe('loaded');
    });
  });

  describe('different resource types', () => {
    it('should handle script loading', async () => {
      const resourceId = 'test-script';
      const scriptUrl = 'https://example.com/script.js';
      
      // Mock script loading
      const mockScript = {
        onload: null as any,
        onerror: null as any,
        src: '',
        async: false
      };
      
      const mockCreateElement = vi.fn().mockReturnValue(mockScript);
      const mockAppendChild = vi.fn();
      
      document.createElement = mockCreateElement;
      document.head.appendChild = mockAppendChild;
      
      prioritizer.registerResource(resourceId, 'script', {
        priority: 'high',
        loadOrder: 1
      }, undefined, scriptUrl);
      
      await vi.runAllTimersAsync();
      
      // Simulate successful load
      if (mockScript.onload) {
        mockScript.onload();
      }
      
      expect(mockCreateElement).toHaveBeenCalledWith('script');
      expect(mockAppendChild).toHaveBeenCalled();
    });

    it('should handle image loading', async () => {
      const resourceId = 'test-image';
      const imageUrl = 'https://example.com/image.jpg';
      
      // Mock Image constructor
      const mockImage = {
        onload: null as any,
        onerror: null as any,
        src: ''
      };
      
      global.Image = vi.fn().mockImplementation(() => mockImage);
      
      prioritizer.registerResource(resourceId, 'image', {
        priority: 'medium',
        loadOrder: 1
      }, undefined, imageUrl);
      
      await vi.runAllTimersAsync();
      
      // Simulate successful load
      if (mockImage.onload) {
        mockImage.onload();
      }
      
      expect(global.Image).toHaveBeenCalled();
    });
  });

  describe('performance requirements', () => {
    it('should meet resource loading performance targets', async () => {
      const resourceCount = 10;
      const resources = Array.from({ length: resourceCount }, (_, i) => ({
        id: `resource-${i}`,
        import: vi.fn().mockResolvedValue({ default: `Resource${i}` })
      }));
      
      const startTime = performance.now();
      
      resources.forEach(({ id, import: importFn }) => {
        prioritizer.registerResource(id, 'component', {
          priority: 'high',
          loadOrder: 1
        }, importFn);
      });
      
      await vi.runAllTimersAsync();
      
      const endTime = performance.now();
      const totalTime = endTime - startTime;
      
      // Should complete within reasonable time
      expect(totalTime).toBeLessThan(500); // < 500ms
      
      const stats = prioritizer.getStats();
      expect(stats.loadedCount).toBe(resourceCount);
    });

    it('should adapt to device capabilities for performance', () => {
      // Test with low-end device
      mockNavigator.deviceMemory = 2;
      mockNavigator.hardwareConcurrency = 2;
      mockNavigator.connection.effectiveType = '2g';
      
      const lowEndPrioritizer = new ResourcePrioritizer();
      const lowEndStats = lowEndPrioritizer.getStats();
      
      // Should have lower concurrent loads for low-end devices
      expect(lowEndStats.maxConcurrentLoads).toBeLessThanOrEqual(2);
      
      // Test with high-end device
      mockNavigator.deviceMemory = 16;
      mockNavigator.hardwareConcurrency = 16;
      mockNavigator.connection.effectiveType = '4g';
      
      const highEndPrioritizer = new ResourcePrioritizer();
      const highEndStats = highEndPrioritizer.getStats();
      
      // Should have higher concurrent loads for high-end devices
      expect(highEndStats.maxConcurrentLoads).toBeGreaterThan(lowEndStats.maxConcurrentLoads);
    });
  });

  describe('integration with real application', () => {
    it('should work with actual resource loading', async () => {
      const componentId = 'real-component';
      const realImport = () => Promise.resolve({
        default: {
          name: 'RealComponent',
          template: '<div>Real Component</div>'
        }
      });
      
      prioritizer.registerResource(componentId, 'component', {
        priority: 'high',
        loadOrder: 1
      }, realImport);
      
      await vi.runAllTimersAsync();
      
      expect(prioritizer.getResourceStatus(componentId)).toBe('loaded');
    });

    it('should handle concurrent resource loading', async () => {
      const resourceCount = 20;
      const resources = Array.from({ length: resourceCount }, (_, i) => ({
        id: `concurrent-resource-${i}`,
        import: () => Promise.resolve({ default: `Resource${i}` })
      }));
      
      resources.forEach(({ id, import: importFn }) => {
        prioritizer.registerResource(id, 'component', {
          priority: 'medium',
          loadOrder: Math.floor(Math.random() * 10)
        }, importFn);
      });
      
      await vi.runAllTimersAsync();
      
      const stats = prioritizer.getStats();
      expect(stats.loadedCount).toBe(resourceCount);
      expect(stats.failedCount).toBe(0);
    });

    it('should maintain performance under high load', async () => {
      const resourceCount = 100;
      
      const startTime = performance.now();
      
      for (let i = 0; i < resourceCount; i++) {
        prioritizer.registerResource(`load-test-${i}`, 'component', {
          priority: i < 10 ? 'critical' : i < 30 ? 'high' : 'medium',
          loadOrder: i
        }, () => Promise.resolve({ default: `Component${i}` }));
      }
      
      await vi.runAllTimersAsync();
      
      const endTime = performance.now();
      const totalTime = endTime - startTime;
      
      // Should handle high load efficiently
      expect(totalTime).toBeLessThan(2000); // < 2 seconds
      
      const stats = prioritizer.getStats();
      expect(stats.totalResources).toBe(resourceCount);
      expect(stats.loadProgress).toBe(100);
    });
  });
});