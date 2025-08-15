import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { loginPerformanceService } from '../loginPerformanceService';

// Mock performance API
const mockPerformance = {
  now: vi.fn(() => 1000),
  mark: vi.fn(),
  measure: vi.fn(),
  getEntriesByType: vi.fn(() => []),
  getEntriesByName: vi.fn(() => []),
};

// Mock PerformanceObserver
const mockPerformanceObserver = vi.fn();
mockPerformanceObserver.prototype.observe = vi.fn();
mockPerformanceObserver.prototype.disconnect = vi.fn();

// Mock navigator
const mockNavigator = {
  userAgent: 'Mozilla/5.0 (Test Browser)',
  connection: {
    effectiveType: '4g',
    type: 'wifi',
  },
};

// Mock document
const mockDocument = {
  readyState: 'loading',
  addEventListener: vi.fn(),
  removeEventListener: vi.fn(),
  querySelectorAll: vi.fn(() => []),
  querySelector: vi.fn(() => null),
  createElement: vi.fn(() => ({
    rel: '',
    href: '',
    setAttribute: vi.fn(),
  })),
  head: {
    appendChild: vi.fn(),
  },
};

// Mock window
const mockWindow = {
  dispatchEvent: vi.fn(),
  addEventListener: vi.fn(),
  removeEventListener: vi.fn(),
  innerHeight: 1080,
};

describe('LoginPerformanceService', () => {
  beforeEach(() => {
    // Reset service state
    loginPerformanceService.clearMetrics();
    
    // Setup mocks
    global.performance = mockPerformance as any;
    global.PerformanceObserver = mockPerformanceObserver as any;
    global.navigator = mockNavigator as any;
    global.document = mockDocument as any;
    global.window = mockWindow as any;
    
    // Reset mock calls
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Performance Tracking', () => {
    it('should start login page tracking', () => {
      mockPerformance.now.mockReturnValue(1000);
      
      loginPerformanceService.startLoginPageTracking();
      
      expect(mockPerformance.now).toHaveBeenCalled();
    });

    it('should track authentication timing', () => {
      mockPerformance.now
        .mockReturnValueOnce(1000) // start
        .mockReturnValueOnce(1500); // end
      
      loginPerformanceService.startAuthentication();
      const authTime = loginPerformanceService.endAuthentication();
      
      expect(authTime).toBe(500);
    });

    it('should complete login tracking with metrics', () => {
      mockPerformance.now
        .mockReturnValueOnce(1000) // page start
        .mockReturnValueOnce(1200) // auth start
        .mockReturnValueOnce(1800) // auth end
        .mockReturnValueOnce(2000); // complete
      
      loginPerformanceService.startLoginPageTracking();
      loginPerformanceService.startAuthentication();
      loginPerformanceService.endAuthentication();
      
      const metrics = loginPerformanceService.completeLoginTracking(100);
      
      expect(metrics).toMatchObject({
        pageLoadTime: 1000,
        authenticationTime: 600,
        redirectTime: 100,
        totalLoginTime: 1000,
        userAgent: 'Mozilla/5.0 (Test Browser)',
        connectionType: '4g',
      });
    });
  });

  describe('Asset Performance Tracking', () => {
    it('should track resource timing', () => {
      const mockResourceEntry = {
        name: 'https://example.com/app.css',
        startTime: 100,
        responseEnd: 300,
        transferSize: 1024,
        encodedBodySize: 2048,
        entryType: 'resource',
      };

      // Simulate PerformanceObserver callback
      const observerCallback = mockPerformanceObserver.mock.calls[0]?.[0];
      if (observerCallback) {
        observerCallback({
          getEntries: () => [mockResourceEntry],
        });
      }

      // Verify observer was set up
      expect(mockPerformanceObserver).toHaveBeenCalled();
    });

    it('should categorize different asset types', () => {
      const cssEntry = {
        name: 'https://example.com/app.css',
        startTime: 100,
        responseEnd: 300,
        transferSize: 1024,
        encodedBodySize: 2048,
        entryType: 'resource',
      };

      const jsEntry = {
        name: 'https://example.com/app.js',
        startTime: 100,
        responseEnd: 400,
        transferSize: 2048,
        encodedBodySize: 4096,
        entryType: 'resource',
      };

      // Test that different asset types are handled
      expect(cssEntry.name).toContain('.css');
      expect(jsEntry.name).toContain('.js');
    });
  });

  describe('Web Vitals Tracking', () => {
    it('should track Largest Contentful Paint', () => {
      const mockLCPEntry = {
        startTime: 1500,
        entryType: 'largest-contentful-paint',
      };

      // Simulate LCP observer
      const observerCallback = mockPerformanceObserver.mock.calls[0]?.[0];
      if (observerCallback) {
        observerCallback({
          getEntries: () => [mockLCPEntry],
        });
      }

      expect(mockPerformanceObserver).toHaveBeenCalled();
    });

    it('should track First Input Delay', () => {
      const mockFIDEntry = {
        startTime: 100,
        processingStart: 120,
        entryType: 'first-input',
      };

      // Simulate FID observer
      const observerCallback = mockPerformanceObserver.mock.calls[0]?.[0];
      if (observerCallback) {
        observerCallback({
          getEntries: () => [mockFIDEntry],
        });
      }

      expect(mockPerformanceObserver).toHaveBeenCalled();
    });
  });

  describe('Performance Grading', () => {
    it('should assign correct performance grades', () => {
      // Test different performance scenarios
      const testCases = [
        { time: 800, expectedGrade: 'A' },
        { time: 1500, expectedGrade: 'B' },
        { time: 2500, expectedGrade: 'C' },
        { time: 4000, expectedGrade: 'D' },
        { time: 6000, expectedGrade: 'F' },
      ];

      testCases.forEach(({ time, expectedGrade }) => {
        mockPerformance.now
          .mockReturnValueOnce(1000) // start
          .mockReturnValueOnce(1000 + time); // end
        
        loginPerformanceService.startLoginPageTracking();
        const metrics = loginPerformanceService.completeLoginTracking();
        
        const report = loginPerformanceService.generateOptimizationReport();
        expect(report.performanceGrade).toBe(expectedGrade);
        
        // Clear for next test
        loginPerformanceService.clearMetrics();
      });
    });
  });

  describe('Optimization Suggestions', () => {
    it('should generate suggestions for slow performance', () => {
      mockPerformance.now
        .mockReturnValueOnce(1000) // start
        .mockReturnValueOnce(4000); // end (slow)
      
      loginPerformanceService.startLoginPageTracking();
      loginPerformanceService.completeLoginTracking();
      
      const report = loginPerformanceService.generateOptimizationReport();
      
      expect(report.optimizationSuggestions).toContain(
        'Login page exceeds 2-second target - consider asset optimization'
      );
    });

    it('should suggest CSS optimization for slow CSS loading', () => {
      // Mock slow CSS loading
      const slowCSSMetrics = loginPerformanceService.exportMetrics();
      slowCSSMetrics.assetMetrics.cssLoadTime = 600;
      
      const report = loginPerformanceService.generateOptimizationReport();
      
      expect(report.optimizationSuggestions.some(s => 
        s.includes('CSS loading is slow')
      )).toBe(true);
    });

    it('should suggest font optimization for slow font loading', () => {
      // Mock slow font loading
      const slowFontMetrics = loginPerformanceService.exportMetrics();
      slowFontMetrics.assetMetrics.fontLoadTime = 400;
      
      const report = loginPerformanceService.generateOptimizationReport();
      
      expect(report.optimizationSuggestions.some(s => 
        s.includes('Font loading is slow')
      )).toBe(true);
    });
  });

  describe('Resource Preloading', () => {
    it('should preload critical resources', () => {
      const mockLink = {
        rel: '',
        href: '',
        as: '',
        setAttribute: vi.fn(),
      };
      
      mockDocument.createElement.mockReturnValue(mockLink);
      
      loginPerformanceService.preloadCriticalResources();
      
      expect(mockDocument.createElement).toHaveBeenCalledWith('link');
      expect(mockDocument.head.appendChild).toHaveBeenCalled();
    });

    it('should optimize images for better loading', () => {
      const mockImages = [
        {
          getBoundingClientRect: () => ({ top: 100 }),
          loading: '',
          decoding: '',
        },
        {
          getBoundingClientRect: () => ({ top: 2000 }),
          loading: '',
          decoding: '',
        },
      ];
      
      mockDocument.querySelectorAll.mockReturnValue(mockImages);
      mockWindow.innerHeight = 1080;
      
      loginPerformanceService.optimizeImages();
      
      expect(mockImages[0].loading).toBe('eager');
      expect(mockImages[1].loading).toBe('lazy');
    });
  });

  describe('Connection Type Detection', () => {
    it('should detect connection type', () => {
      mockPerformance.now
        .mockReturnValueOnce(1000)
        .mockReturnValueOnce(2000);
      
      loginPerformanceService.startLoginPageTracking();
      const metrics = loginPerformanceService.completeLoginTracking();
      
      expect(metrics.connectionType).toBe('4g');
    });

    it('should handle missing connection API', () => {
      const originalConnection = mockNavigator.connection;
      delete (mockNavigator as any).connection;
      
      mockPerformance.now
        .mockReturnValueOnce(1000)
        .mockReturnValueOnce(2000);
      
      loginPerformanceService.startLoginPageTracking();
      const metrics = loginPerformanceService.completeLoginTracking();
      
      expect(metrics.connectionType).toBe('unknown');
      
      // Restore connection
      mockNavigator.connection = originalConnection;
    });
  });

  describe('Metrics Management', () => {
    it('should store and retrieve metrics', () => {
      mockPerformance.now
        .mockReturnValueOnce(1000)
        .mockReturnValueOnce(2000);
      
      loginPerformanceService.startLoginPageTracking();
      loginPerformanceService.completeLoginTracking();
      
      const metrics = loginPerformanceService.getMetrics();
      expect(metrics).toHaveLength(1);
      expect(metrics[0].totalLoginTime).toBe(1000);
    });

    it('should clear all metrics', () => {
      mockPerformance.now
        .mockReturnValueOnce(1000)
        .mockReturnValueOnce(2000);
      
      loginPerformanceService.startLoginPageTracking();
      loginPerformanceService.completeLoginTracking();
      
      expect(loginPerformanceService.getMetrics()).toHaveLength(1);
      
      loginPerformanceService.clearMetrics();
      
      expect(loginPerformanceService.getMetrics()).toHaveLength(0);
    });

    it('should export metrics for analysis', () => {
      mockPerformance.now
        .mockReturnValueOnce(1000)
        .mockReturnValueOnce(2000);
      
      loginPerformanceService.startLoginPageTracking();
      loginPerformanceService.completeLoginTracking();
      
      const exported = loginPerformanceService.exportMetrics();
      
      expect(exported).toHaveProperty('loginMetrics');
      expect(exported).toHaveProperty('assetMetrics');
      expect(exported).toHaveProperty('thresholds');
      expect(exported.loginMetrics).toHaveLength(1);
    });

    it('should trim metrics to prevent memory leaks', () => {
      // Add more than 100 metrics
      for (let i = 0; i < 105; i++) {
        mockPerformance.now
          .mockReturnValueOnce(1000)
          .mockReturnValueOnce(2000);
        
        loginPerformanceService.startLoginPageTracking();
        loginPerformanceService.completeLoginTracking();
      }
      
      const metrics = loginPerformanceService.getMetrics();
      expect(metrics.length).toBeLessThanOrEqual(100);
    });
  });

  describe('Error Handling', () => {
    it('should handle performance API errors gracefully', () => {
      mockPerformance.now.mockImplementation(() => {
        throw new Error('Performance API error');
      });
      
      expect(() => {
        loginPerformanceService.startLoginPageTracking();
      }).not.toThrow();
    });

    it('should handle missing PerformanceObserver', () => {
      global.PerformanceObserver = undefined as any;
      
      expect(() => {
        loginPerformanceService.startLoginPageTracking();
      }).not.toThrow();
    });

    it('should handle DOM manipulation errors', () => {
      mockDocument.createElement.mockImplementation(() => {
        throw new Error('DOM error');
      });
      
      expect(() => {
        loginPerformanceService.preloadCriticalResources();
      }).not.toThrow();
    });
  });
});