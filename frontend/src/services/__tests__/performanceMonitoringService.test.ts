import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import performanceMonitoringService from '../performanceMonitoringService'
import type { 
  TabSwitchMetrics, 
  ApiResponseMetrics, 
  LoadingMetrics,
  PerformanceThresholds 
} from '../performanceMonitoringService'

describe('PerformanceMonitoringService', () => {
  beforeEach(() => {
    // Clear all metrics before each test
    performanceMonitoringService.clearAllMetrics()
    
    // Reset thresholds to enterprise-grade defaults
    performanceMonitoringService.setThresholds({
      tabSwitchTime: 100,
      apiResponseTime: 500,
      cacheHitRate: 0.85,
      loadingTime: 300
    })
    
    // Clear session storage
    sessionStorage.clear()
  })

  afterEach(() => {
    // Clean up after each test
    sessionStorage.clear()
  })

  describe('Tab Switch Tracking', () => {
    it('should start and end tab switch tracking correctly', async () => {
      const tabId = 'test-tab'
      const fromRoute = '/dashboard'
      const toRoute = '/inventory'
      
      // Start tracking
      const switchId = performanceMonitoringService.startTabSwitch(tabId, fromRoute)
      expect(switchId).toContain(tabId)
      
      // Verify session storage was used
      const storedData = sessionStorage.getItem(`tab-switch-${switchId}`)
      expect(storedData).toBeTruthy()
      
      const parsedData = JSON.parse(storedData!)
      expect(parsedData.tabId).toBe(tabId)
      expect(parsedData.fromRoute).toBe(fromRoute)
      expect(parsedData.startTime).toBeTypeOf('number')
      
      // Wait a small amount of time to ensure measurable difference
      await new Promise(resolve => setTimeout(resolve, 10))
      
      // End tracking
      const metrics = performanceMonitoringService.endTabSwitch(switchId, toRoute)
      
      expect(metrics).toBeDefined()
      expect(metrics?.tabId).toBe(tabId)
      expect(metrics?.switchTime).toBeGreaterThan(0)
      expect(metrics?.fromRoute).toBe(fromRoute)
      expect(metrics?.toRoute).toBe(toRoute)
      
      // Verify session storage was cleaned up
      const cleanedData = sessionStorage.getItem(`tab-switch-${switchId}`)
      expect(cleanedData).toBeNull()
    })

    it('should return null when ending non-existent switch', () => {
      const metrics = performanceMonitoringService.endTabSwitch('non-existent', '/test')
      expect(metrics).toBeNull()
    })

    it('should record tab switch metrics correctly', () => {
      const metrics: TabSwitchMetrics = {
        tabId: 'test-tab',
        switchTime: 80, // Below 100ms threshold
        timestamp: new Date(),
        fromRoute: '/dashboard',
        toRoute: '/inventory'
      }
      
      performanceMonitoringService.recordTabSwitchMetrics(metrics)
      
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.tabSwitching.totalSwitches).toBe(1)
      expect(report.tabSwitching.averageTime).toBe(80)
      expect(report.tabSwitching.fastestSwitch?.switchTime).toBe(80)
      expect(report.tabSwitching.slowestSwitch?.switchTime).toBe(80)
    })

    it('should detect threshold violations for tab switches', () => {
      const originalWarn = console.warn
      let warnCalled = false
      let warnMessage = ''
      
      // Capture console.warn calls
      console.warn = (message: string) => {
        warnCalled = true
        warnMessage = message
      }
      
      const slowMetrics: TabSwitchMetrics = {
        tabId: 'slow-tab',
        switchTime: 150, // Above 100ms threshold
        timestamp: new Date(),
        fromRoute: '/dashboard',
        toRoute: '/inventory'
      }
      
      performanceMonitoringService.recordTabSwitchMetrics(slowMetrics)
      
      expect(warnCalled).toBe(true)
      expect(warnMessage).toContain('Tab switch threshold violation')
      
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.tabSwitching.thresholdViolations).toBe(1)
      
      // Restore original console.warn
      console.warn = originalWarn
    })
  })

  describe('API Response Monitoring', () => {
    it('should record API response metrics correctly', () => {
      const metrics: ApiResponseMetrics = {
        endpoint: '/api/users',
        responseTime: 150,
        timestamp: new Date(),
        status: 200,
        cacheHit: false
      }
      
      performanceMonitoringService.recordApiResponseMetrics(metrics)
      
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.apiPerformance.totalRequests).toBe(1)
      expect(report.apiPerformance.averageResponseTime).toBe(150)
      expect(report.apiPerformance.fastestEndpoint?.responseTime).toBe(150)
      expect(report.apiPerformance.slowestEndpoint?.responseTime).toBe(150)
    })

    it('should detect threshold violations for API responses', () => {
      const originalWarn = console.warn
      let warnCalled = false
      let warnMessage = ''
      
      // Capture console.warn calls
      console.warn = (message: string) => {
        warnCalled = true
        warnMessage = message
      }
      
      const slowMetrics: ApiResponseMetrics = {
        endpoint: '/api/slow-endpoint',
        responseTime: 600, // Above 500ms threshold
        timestamp: new Date(),
        status: 200,
        cacheHit: false
      }
      
      performanceMonitoringService.recordApiResponseMetrics(slowMetrics)
      
      expect(warnCalled).toBe(true)
      expect(warnMessage).toContain('API response threshold violation')
      
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.apiPerformance.thresholdViolations).toBe(1)
      
      // Restore original console.warn
      console.warn = originalWarn
    })

    it('should calculate multiple API metrics correctly', () => {
      const metrics1: ApiResponseMetrics = {
        endpoint: '/api/fast',
        responseTime: 100,
        timestamp: new Date(),
        status: 200,
        cacheHit: true
      }
      
      const metrics2: ApiResponseMetrics = {
        endpoint: '/api/slow',
        responseTime: 500,
        timestamp: new Date(),
        status: 200,
        cacheHit: false
      }
      
      performanceMonitoringService.recordApiResponseMetrics(metrics1)
      performanceMonitoringService.recordApiResponseMetrics(metrics2)
      
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.apiPerformance.totalRequests).toBe(2)
      expect(report.apiPerformance.averageResponseTime).toBe(300)
      expect(report.apiPerformance.fastestEndpoint?.responseTime).toBe(100)
      expect(report.apiPerformance.slowestEndpoint?.responseTime).toBe(500)
    })
  })

  describe('Cache Performance Tracking', () => {
    it('should track cache hits correctly', () => {
      performanceMonitoringService.recordCacheHit('/api/users')
      performanceMonitoringService.recordCacheHit('/api/products')
      
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.cachePerformance.totalRequests).toBe(2)
      expect(report.cachePerformance.cacheHits).toBe(2)
      expect(report.cachePerformance.cacheMisses).toBe(0)
      expect(report.cachePerformance.hitRate).toBe(1.0)
    })

    it('should track cache misses correctly', () => {
      performanceMonitoringService.recordCacheMiss('/api/users')
      performanceMonitoringService.recordCacheMiss('/api/products')
      
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.cachePerformance.totalRequests).toBe(2)
      expect(report.cachePerformance.cacheHits).toBe(0)
      expect(report.cachePerformance.cacheMisses).toBe(2)
      expect(report.cachePerformance.hitRate).toBe(0.0)
    })

    it('should calculate mixed cache performance correctly', () => {
      // 3 hits, 2 misses = 60% hit rate
      performanceMonitoringService.recordCacheHit('/api/users')
      performanceMonitoringService.recordCacheHit('/api/products')
      performanceMonitoringService.recordCacheHit('/api/orders')
      performanceMonitoringService.recordCacheMiss('/api/reports')
      performanceMonitoringService.recordCacheMiss('/api/analytics')
      
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.cachePerformance.totalRequests).toBe(5)
      expect(report.cachePerformance.cacheHits).toBe(3)
      expect(report.cachePerformance.cacheMisses).toBe(2)
      expect(report.cachePerformance.hitRate).toBe(0.6)
    })

    it('should detect low cache hit rate threshold violations', () => {
      const originalWarn = console.warn
      let warnCalled = false
      let warnMessage = ''
      
      // Capture console.warn calls
      console.warn = (message: string) => {
        warnCalled = true
        warnMessage = message
      }
      
      // Create low hit rate scenario (20% hit rate, below 85% threshold)
      for (let i = 0; i < 2; i++) {
        performanceMonitoringService.recordCacheHit('/api/test')
      }
      for (let i = 0; i < 8; i++) {
        performanceMonitoringService.recordCacheMiss('/api/test')
      }
      
      // Should trigger warning since we have > 10 requests and hit rate < 80%
      performanceMonitoringService.recordCacheMiss('/api/test')
      
      expect(warnCalled).toBe(true)
      expect(warnMessage).toContain('Cache hit rate below threshold')
      
      // Restore original console.warn
      console.warn = originalWarn
    })
  })

  describe('Loading Performance Tracking', () => {
    it('should record loading metrics correctly', () => {
      const metrics: LoadingMetrics = {
        component: 'UserDashboard',
        loadTime: 250,
        timestamp: new Date(),
        isInitialLoad: true
      }
      
      performanceMonitoringService.recordLoadingMetrics(metrics)
      
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.loadingPerformance.totalLoads).toBe(1)
      expect(report.loadingPerformance.averageLoadTime).toBe(250)
      expect(report.loadingPerformance.fastestComponent?.loadTime).toBe(250)
      expect(report.loadingPerformance.slowestComponent?.loadTime).toBe(250)
    })

    it('should detect threshold violations for loading times', () => {
      const originalWarn = console.warn
      let warnCalled = false
      let warnMessage = ''
      
      // Capture console.warn calls
      console.warn = (message: string) => {
        warnCalled = true
        warnMessage = message
      }
      
      const slowMetrics: LoadingMetrics = {
        component: 'SlowComponent',
        loadTime: 400, // Above 300ms threshold
        timestamp: new Date(),
        isInitialLoad: false
      }
      
      performanceMonitoringService.recordLoadingMetrics(slowMetrics)
      
      expect(warnCalled).toBe(true)
      expect(warnMessage).toContain('Loading time threshold violation')
      
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.loadingPerformance.thresholdViolations).toBe(1)
      
      // Restore original console.warn
      console.warn = originalWarn
    })
  })

  describe('Performance Thresholds', () => {
    it('should set and get thresholds correctly', () => {
      const newThresholds: Partial<PerformanceThresholds> = {
        tabSwitchTime: 300,
        apiResponseTime: 1500
      }
      
      performanceMonitoringService.setThresholds(newThresholds)
      const thresholds = performanceMonitoringService.getThresholds()
      
      expect(thresholds.tabSwitchTime).toBe(300)
      expect(thresholds.apiResponseTime).toBe(1500)
      expect(thresholds.cacheHitRate).toBe(0.85) // Should remain unchanged
      expect(thresholds.loadingTime).toBe(300) // Should remain unchanged
    })
  })

  describe('Performance Reporting', () => {
    it('should generate empty report when no metrics exist', () => {
      const report = performanceMonitoringService.generatePerformanceReport()
      
      expect(report.tabSwitching.totalSwitches).toBe(0)
      expect(report.tabSwitching.averageTime).toBe(0)
      expect(report.tabSwitching.slowestSwitch).toBeNull()
      expect(report.tabSwitching.fastestSwitch).toBeNull()
      
      expect(report.apiPerformance.totalRequests).toBe(0)
      expect(report.apiPerformance.averageResponseTime).toBe(0)
      
      expect(report.loadingPerformance.totalLoads).toBe(0)
      expect(report.loadingPerformance.averageLoadTime).toBe(0)
      
      expect(report.cachePerformance.totalRequests).toBe(0)
      expect(report.cachePerformance.hitRate).toBe(0)
    })

    it('should generate optimization suggestions based on performance', () => {
      // Create scenario with poor performance
      const slowTabSwitch: TabSwitchMetrics = {
        tabId: 'slow-tab',
        switchTime: 85, // Above 80% of threshold (80ms > 80% of 100ms)
        timestamp: new Date(),
        fromRoute: '/dashboard',
        toRoute: '/inventory'
      }
      
      const slowApi: ApiResponseMetrics = {
        endpoint: '/api/slow',
        responseTime: 380, // Above 70% of threshold (350ms > 70% of 500ms)
        timestamp: new Date(),
        status: 200,
        cacheHit: false
      }
      
      performanceMonitoringService.recordTabSwitchMetrics(slowTabSwitch)
      performanceMonitoringService.recordApiResponseMetrics(slowApi)
      
      // Create low cache hit rate
      for (let i = 0; i < 3; i++) {
        performanceMonitoringService.recordCacheHit('/api/test')
      }
      for (let i = 0; i < 7; i++) {
        performanceMonitoringService.recordCacheMiss('/api/test')
      }
      
      const report = performanceMonitoringService.generatePerformanceReport()
      
      // Debug: log the actual suggestions
      console.log('Generated suggestions:', report.optimizationSuggestions)

      expect(report.optimizationSuggestions.length).toBeGreaterThan(0)
      expect(report.optimizationSuggestions.some(s => 
        s.includes('route preloading') || s.includes('tab switching')
      )).toBe(true)
      expect(report.optimizationSuggestions.some(s => 
        s.includes('request optimization') || s.includes('API')
      )).toBe(true)
      expect(report.optimizationSuggestions.some(s => 
        s.includes('cache coverage') || s.includes('cache')
      )).toBe(true)
    })
  })

  describe('Metrics Management', () => {
    it('should clear all metrics correctly', () => {
      // Add some metrics
      performanceMonitoringService.recordTabSwitchMetrics({
        tabId: 'test',
        switchTime: 100,
        timestamp: new Date(),
        fromRoute: '/a',
        toRoute: '/b'
      })
      
      performanceMonitoringService.recordApiResponseMetrics({
        endpoint: '/api/test',
        responseTime: 100,
        timestamp: new Date(),
        status: 200,
        cacheHit: false
      })
      
      performanceMonitoringService.recordCacheHit('/api/test')
      
      // Verify metrics exist
      let report = performanceMonitoringService.generatePerformanceReport()
      expect(report.tabSwitching.totalSwitches).toBe(1)
      expect(report.apiPerformance.totalRequests).toBe(1)
      expect(report.cachePerformance.totalRequests).toBe(1)
      
      // Clear metrics
      performanceMonitoringService.clearAllMetrics()
      
      // Verify metrics are cleared
      report = performanceMonitoringService.generatePerformanceReport()
      expect(report.tabSwitching.totalSwitches).toBe(0)
      expect(report.apiPerformance.totalRequests).toBe(0)
      expect(report.cachePerformance.totalRequests).toBe(0)
    })

    it('should export metrics correctly', () => {
      // Add some test metrics
      performanceMonitoringService.recordTabSwitchMetrics({
        tabId: 'test',
        switchTime: 100,
        timestamp: new Date(),
        fromRoute: '/a',
        toRoute: '/b'
      })
      
      performanceMonitoringService.recordApiResponseMetrics({
        endpoint: '/api/test',
        responseTime: 100,
        timestamp: new Date(),
        status: 200,
        cacheHit: false
      })
      
      const exported = performanceMonitoringService.exportMetrics()
      
      expect(exported.tabSwitchMetrics).toHaveLength(1)
      expect(exported.apiResponseMetrics).toHaveLength(1)
      expect(exported.cacheMetrics).toBeDefined()
      expect(exported.thresholds).toBeDefined()
      
      // Verify exported data matches current state
      expect(exported.tabSwitchMetrics[0].tabId).toBe('test')
      expect(exported.apiResponseMetrics[0].endpoint).toBe('/api/test')
    })

    it('should limit metrics history to prevent memory issues', () => {
      // Add more than maxMetricsHistory (1000) tab switch metrics
      for (let i = 0; i < 1100; i++) {
        performanceMonitoringService.recordTabSwitchMetrics({
          tabId: `test-${i}`,
          switchTime: 50 + (i % 50), // Keep times low to avoid threshold violations
          timestamp: new Date(),
          fromRoute: '/a',
          toRoute: '/b'
        })
      }
      
      const exported = performanceMonitoringService.exportMetrics()
      
      // Should be limited to 1000 entries
      expect(exported.tabSwitchMetrics).toHaveLength(1000)
      
      // Should keep the most recent entries
      expect(exported.tabSwitchMetrics[999].tabId).toBe('test-1099')
      expect(exported.tabSwitchMetrics[0].tabId).toBe('test-100')
    })
  })
})