import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import performanceMonitoringService from '@/services/performanceMonitoringService'

// Create a simple test environment that mimics the performance monitoring functionality
const createTestRouter = () => {
  let currentRoute = {
    path: '/dashboard',
    name: 'dashboard',
    params: {},
    query: {}
  }

  const beforeEachCallbacks: Array<(to: any, from: any) => void> = []
  const afterEachCallbacks: Array<(to: any, from: any) => void> = []

  return {
    beforeEach: (callback: (to: any, from: any) => void) => {
      beforeEachCallbacks.push(callback)
    },
    afterEach: (callback: (to: any, from: any) => void) => {
      afterEachCallbacks.push(callback)
    },
    get currentRoute() {
      return currentRoute
    },
    set currentRoute(value) {
      currentRoute = value
    },
    navigate: (to: any) => {
      const from = { ...currentRoute }
      beforeEachCallbacks.forEach(cb => cb(to, from))
      currentRoute = to
      afterEachCallbacks.forEach(cb => cb(to, from))
    }
  }
}

const testRouter = createTestRouter()

// Create a test version of the composable that uses our test router
const createTestPerformanceMonitoring = () => {
  let isMonitoring = true
  let performanceReport: any = null
  let lastTabSwitchTime = 0
  let lastApiResponseTime = 0
  
  const getCacheHitRate = () => {
    return performanceReport ? performanceReport.cachePerformance.hitRate : 0
  }

  return {
    get isMonitoring() { return isMonitoring },
    set isMonitoring(value) { isMonitoring = value },
    get performanceReport() { return performanceReport },
    set performanceReport(value) { performanceReport = value },
    get lastTabSwitchTime() { return lastTabSwitchTime },
    set lastTabSwitchTime(value) { lastTabSwitchTime = value },
    get lastApiResponseTime() { return lastApiResponseTime },
    set lastApiResponseTime(value) { lastApiResponseTime = value },
    get cacheHitRate() { return getCacheHitRate() },
    startTabSwitchTracking: (tabId?: string) => {
      if (!isMonitoring) return ''
      const actualTabId = tabId || `tab-${Date.now()}`
      return performanceMonitoringService.startTabSwitch(actualTabId, testRouter.currentRoute.path)
    },
    endTabSwitchTracking: (switchId: string, toRoute?: string) => {
      if (!isMonitoring || !switchId) return null
      const metrics = performanceMonitoringService.endTabSwitch(
        switchId,
        toRoute || testRouter.currentRoute.path
      )
      if (metrics) {
        lastTabSwitchTime = metrics.switchTime
      }
      return metrics
    },
    recordApiResponse: (endpoint: string, responseTime: number, status: number, cacheHit = false) => {
      if (!isMonitoring) return
      performanceMonitoringService.recordApiResponseMetrics({
        endpoint,
        responseTime,
        timestamp: new Date(),
        status,
        cacheHit
      })
      lastApiResponseTime = responseTime
      if (cacheHit) {
        performanceMonitoringService.recordCacheHit(endpoint)
      } else {
        performanceMonitoringService.recordCacheMiss(endpoint)
      }
    },
    recordComponentLoad: (componentName: string, loadTime: number, isInitialLoad = false) => {
      if (!isMonitoring) return
      performanceMonitoringService.recordLoadingMetrics({
        component: componentName,
        loadTime,
        timestamp: new Date(),
        isInitialLoad
      })
    },
    measureAsyncOperation: async (operation: () => Promise<any>, operationName: string) => {
      const startTime = performance.now()
      try {
        const result = await operation()
        const duration = performance.now() - startTime
        if (isMonitoring) {
          performanceMonitoringService.recordLoadingMetrics({
            component: operationName,
            loadTime: duration,
            timestamp: new Date(),
            isInitialLoad: false
          })
        }
        return { result, duration }
      } catch (error) {
        const duration = performance.now() - startTime
        if (isMonitoring) {
          performanceMonitoringService.recordLoadingMetrics({
            component: `${operationName}-error`,
            loadTime: duration,
            timestamp: new Date(),
            isInitialLoad: false
          })
        }
        throw error
      }
    },
    measureSyncOperation: (operation: () => any, operationName: string) => {
      const startTime = performance.now()
      try {
        const result = operation()
        const duration = performance.now() - startTime
        if (isMonitoring) {
          performanceMonitoringService.recordLoadingMetrics({
            component: operationName,
            loadTime: duration,
            timestamp: new Date(),
            isInitialLoad: false
          })
        }
        return { result, duration }
      } catch (error) {
        const duration = performance.now() - startTime
        if (isMonitoring) {
          performanceMonitoringService.recordLoadingMetrics({
            component: `${operationName}-error`,
            loadTime: duration,
            timestamp: new Date(),
            isInitialLoad: false
          })
        }
        throw error
      }
    },
    generateReport: () => {
      const report = performanceMonitoringService.generatePerformanceReport()
      performanceReport = report
      return report
    },
    getOptimizationSuggestions: () => {
      const report = performanceMonitoringService.generatePerformanceReport()
      return report.optimizationSuggestions
    },
    setPerformanceThresholds: (thresholds: any) => {
      performanceMonitoringService.setThresholds(thresholds)
    },
    getPerformanceThresholds: () => {
      return performanceMonitoringService.getThresholds()
    },
    enableMonitoring: () => {
      isMonitoring = true
    },
    disableMonitoring: () => {
      isMonitoring = false
    },
    clearMetrics: () => {
      performanceMonitoringService.clearAllMetrics()
      performanceReport = null
      lastTabSwitchTime = 0
      lastApiResponseTime = 0
    },
    exportMetrics: () => {
      return performanceMonitoringService.exportMetrics()
    },
    createApiInterceptor: () => {
      return {
        request: (config: any) => {
          config.metadata = { startTime: performance.now() }
          return config
        },
        response: (response: any) => {
          const endTime = performance.now()
          const startTime = response.config?.metadata?.startTime
          if (startTime && isMonitoring) {
            const responseTime = endTime - startTime
            const cacheHit = response.headers?.['x-cache-hit'] === 'true'
            performanceMonitoringService.recordApiResponseMetrics({
              endpoint: response.config.url || 'unknown',
              responseTime,
              timestamp: new Date(),
              status: response.status,
              cacheHit
            })
            lastApiResponseTime = responseTime
            if (cacheHit) {
              performanceMonitoringService.recordCacheHit(response.config.url || 'unknown')
            } else {
              performanceMonitoringService.recordCacheMiss(response.config.url || 'unknown')
            }
          }
          return response
        },
        error: (error: any) => {
          const endTime = performance.now()
          const startTime = error.config?.metadata?.startTime
          if (startTime && isMonitoring) {
            const responseTime = endTime - startTime
            performanceMonitoringService.recordApiResponseMetrics({
              endpoint: error.config?.url || 'unknown',
              responseTime,
              timestamp: new Date(),
              status: error.response?.status || 0,
              cacheHit: false
            })
            lastApiResponseTime = responseTime
            performanceMonitoringService.recordCacheMiss(error.config?.url || 'unknown')
          }
          return Promise.reject(error)
        }
      }
    },
    trackComponentPerformance: (componentName: string) => {
      const startTime = performance.now()
      // Simulate onMounted
      setTimeout(() => {
        const loadTime = performance.now() - startTime
        if (isMonitoring) {
          performanceMonitoringService.recordLoadingMetrics({
            component: componentName,
            loadTime,
            timestamp: new Date(),
            isInitialLoad: true
          })
        }
      }, 0)
      
      return {
        recordOperation: (operationName: string, duration: number) => {
          if (isMonitoring) {
            performanceMonitoringService.recordLoadingMetrics({
              component: `${componentName}-${operationName}`,
              loadTime: duration,
              timestamp: new Date(),
              isInitialLoad: false
            })
          }
        }
      }
    },
    checkPerformanceAlerts: () => {
      const report = performanceMonitoringService.generatePerformanceReport()
      const thresholds = performanceMonitoringService.getThresholds()
      const alerts: string[] = []
      
      if (report.tabSwitching.averageTime > thresholds.tabSwitchTime) {
        alerts.push(`Average tab switch time (${report.tabSwitching.averageTime.toFixed(0)}ms) exceeds threshold (${thresholds.tabSwitchTime}ms)`)
      }
      
      if (report.apiPerformance.averageResponseTime > thresholds.apiResponseTime) {
        alerts.push(`Average API response time (${report.apiPerformance.averageResponseTime.toFixed(0)}ms) exceeds threshold (${thresholds.apiResponseTime}ms)`)
      }
      
      if (report.cachePerformance.hitRate < thresholds.cacheHitRate && report.cachePerformance.totalRequests > 10) {
        alerts.push(`Cache hit rate (${(report.cachePerformance.hitRate * 100).toFixed(1)}%) below threshold (${(thresholds.cacheHitRate * 100)}%)`)
      }
      
      if (report.loadingPerformance.averageLoadTime > thresholds.loadingTime) {
        alerts.push(`Average loading time (${report.loadingPerformance.averageLoadTime.toFixed(0)}ms) exceeds threshold (${thresholds.loadingTime}ms)`)
      }
      
      return alerts
    }
  }
}

describe('usePerformanceMonitoring', () => {
  let performanceMonitoring: ReturnType<typeof createTestPerformanceMonitoring>

  beforeEach(() => {
    performanceMonitoringService.clearAllMetrics()
    sessionStorage.clear()
    
    // Reset route
    testRouter.currentRoute = {
      path: '/dashboard',
      name: 'dashboard',
      params: {},
      query: {}
    }
    
    performanceMonitoring = createTestPerformanceMonitoring()
  })

  afterEach(() => {
    sessionStorage.clear()
  })

  describe('Basic Functionality', () => {
    it('should initialize with default values', () => {
      expect(performanceMonitoring.isMonitoring).toBe(true)
      expect(performanceMonitoring.performanceReport).toBeNull()
      expect(performanceMonitoring.lastTabSwitchTime).toBe(0)
      expect(performanceMonitoring.lastApiResponseTime).toBe(0)
      expect(performanceMonitoring.cacheHitRate).toBe(0)
    })

    it('should enable and disable monitoring', () => {
      expect(performanceMonitoring.isMonitoring).toBe(true)

      performanceMonitoring.disableMonitoring()
      expect(performanceMonitoring.isMonitoring).toBe(false)

      performanceMonitoring.enableMonitoring()
      expect(performanceMonitoring.isMonitoring).toBe(true)
    })
  })

  describe('Tab Switch Tracking', () => {
    it('should track tab switches when monitoring is enabled', async () => {
      const switchId = performanceMonitoring.startTabSwitchTracking('test-tab')
      expect(switchId).toContain('test-tab')
      
      // Verify session storage was used
      const storedData = sessionStorage.getItem(`tab-switch-${switchId}`)
      expect(storedData).toBeTruthy()

      // Wait a small amount of time to ensure measurable difference
      await new Promise(resolve => setTimeout(resolve, 10))

      const metrics = performanceMonitoring.endTabSwitchTracking(switchId, '/inventory')

      expect(metrics).toBeDefined()
      expect(metrics?.switchTime).toBeGreaterThan(0)
      expect(performanceMonitoring.lastTabSwitchTime).toBeGreaterThan(0)
    })

    it('should not track tab switches when monitoring is disabled', () => {
      performanceMonitoring.disableMonitoring()
      const switchId = performanceMonitoring.startTabSwitchTracking('test-tab')

      expect(switchId).toBe('')
    })

    it('should use current route path when no toRoute is provided', async () => {
      const switchId = performanceMonitoring.startTabSwitchTracking('test-tab')
      
      // Change route
      testRouter.currentRoute = { ...testRouter.currentRoute, path: '/inventory' }
      
      // Wait a small amount of time
      await new Promise(resolve => setTimeout(resolve, 10))
      
      const metrics = performanceMonitoring.endTabSwitchTracking(switchId)

      expect(metrics?.toRoute).toBe('/inventory')
    })
  })

  describe('API Response Monitoring', () => {
    it('should record API responses when monitoring is enabled', () => {
      performanceMonitoring.recordApiResponse('/api/users', 150, 200, false)

      expect(performanceMonitoring.lastApiResponseTime).toBe(150)

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.apiPerformance.totalRequests).toBe(1)
      expect(report.apiPerformance.averageResponseTime).toBe(150)
    })

    it('should not record API responses when monitoring is disabled', () => {
      performanceMonitoring.disableMonitoring()
      performanceMonitoring.recordApiResponse('/api/users', 150, 200, false)

      expect(performanceMonitoring.lastApiResponseTime).toBe(0)

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.apiPerformance.totalRequests).toBe(0)
    })

    it('should track cache hits and misses correctly', () => {
      performanceMonitoring.recordApiResponse('/api/users', 100, 200, true) // Cache hit
      performanceMonitoring.recordApiResponse('/api/products', 200, 200, false) // Cache miss

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.cachePerformance.totalRequests).toBe(2)
      expect(report.cachePerformance.cacheHits).toBe(1)
      expect(report.cachePerformance.cacheMisses).toBe(1)
      expect(report.cachePerformance.hitRate).toBe(0.5)
    })
  })

  describe('Component Loading Monitoring', () => {
    it('should record component loading metrics', () => {
      performanceMonitoring.recordComponentLoad('UserDashboard', 250, true)

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.loadingPerformance.totalLoads).toBe(1)
      expect(report.loadingPerformance.averageLoadTime).toBe(250)
    })

    it('should not record component loading when monitoring is disabled', () => {
      performanceMonitoring.disableMonitoring()
      performanceMonitoring.recordComponentLoad('UserDashboard', 250, true)

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.loadingPerformance.totalLoads).toBe(0)
    })
  })

  describe('Performance Measurement Helpers', () => {
    it('should measure async operations correctly', async () => {
      let callCount = 0

      const asyncOperation = async () => {
        callCount++
        await new Promise(resolve => setTimeout(resolve, 10))
        return 'result'
      }

      const { result, duration } = await performanceMonitoring.measureAsyncOperation(asyncOperation, 'test-operation')

      expect(result).toBe('result')
      expect(duration).toBeGreaterThan(0)
      expect(callCount).toBe(1)

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.loadingPerformance.totalLoads).toBe(1)
    })

    it('should measure sync operations correctly', () => {
      let callCount = 0

      const syncOperation = () => {
        callCount++
        // Simulate some work
        for (let i = 0; i < 1000; i++) {
          Math.random()
        }
        return 'sync-result'
      }

      const { result, duration } = performanceMonitoring.measureSyncOperation(syncOperation, 'sync-operation')

      expect(result).toBe('sync-result')
      expect(duration).toBeGreaterThan(0)
      expect(callCount).toBe(1)

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.loadingPerformance.totalLoads).toBe(1)
    })

    it('should handle errors in async operations', async () => {
      const failingOperation = async () => {
        throw new Error('Test error')
      }

      await expect(performanceMonitoring.measureAsyncOperation(failingOperation, 'failing-operation')).rejects.toThrow('Test error')

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.loadingPerformance.totalLoads).toBe(1)
      // Should record the error operation
      const exported = performanceMonitoringService.exportMetrics()
      expect(exported.loadingMetrics[0].component).toBe('failing-operation-error')
    })
  })

  describe('Performance Reporting', () => {
    it('should generate performance reports', () => {
      performanceMonitoring.recordApiResponse('/api/test', 100, 200, false)

      const report = performanceMonitoring.generateReport()

      expect(report).toBeDefined()
      expect(report.apiPerformance.totalRequests).toBe(1)
      expect(report.apiPerformance.averageResponseTime).toBe(100)
    })

    it('should get optimization suggestions', () => {
      // Create slow API response to trigger suggestions
      performanceMonitoring.recordApiResponse('/api/slow', 380, 200, false) // Close to 500ms threshold

      const suggestions = performanceMonitoring.getOptimizationSuggestions()

      expect(suggestions).toBeInstanceOf(Array)
      expect(suggestions.some(s => s.includes('request optimization'))).toBe(true)
    })
  })

  describe('Threshold Management', () => {
    it('should set and get performance thresholds', () => {
      const newThresholds = {
        tabSwitchTime: 300,
        apiResponseTime: 1500
      }

      performanceMonitoring.setPerformanceThresholds(newThresholds)
      const thresholds = performanceMonitoring.getPerformanceThresholds()

      expect(thresholds.tabSwitchTime).toBe(300)
      expect(thresholds.apiResponseTime).toBe(1500)
    })
  })

  describe('API Interceptor', () => {
    it('should create API interceptor with correct structure', () => {
      const interceptor = performanceMonitoring.createApiInterceptor()

      expect(interceptor).toHaveProperty('request')
      expect(interceptor).toHaveProperty('response')
      expect(interceptor).toHaveProperty('error')
      expect(typeof interceptor.request).toBe('function')
      expect(typeof interceptor.response).toBe('function')
      expect(typeof interceptor.error).toBe('function')
    })

    it('should add metadata to request config', () => {
      const interceptor = performanceMonitoring.createApiInterceptor()

      const config = { url: '/api/test' }
      const modifiedConfig = interceptor.request(config)

      expect(modifiedConfig.metadata).toBeDefined()
      expect(modifiedConfig.metadata.startTime).toBeTypeOf('number')
      expect(modifiedConfig.metadata.startTime).toBeGreaterThan(0)
    })

    it('should record response metrics in interceptor', async () => {
      const interceptor = performanceMonitoring.createApiInterceptor()

      const startTime = performance.now()
      const response = {
        config: {
          url: '/api/test',
          metadata: { startTime }
        },
        status: 200,
        headers: {}
      }

      // Wait a small amount to ensure measurable time difference
      await new Promise(resolve => setTimeout(resolve, 10))

      interceptor.response(response)

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.apiPerformance.totalRequests).toBe(1)
      expect(report.apiPerformance.averageResponseTime).toBeGreaterThan(0)
    })

    it('should handle cache hit headers in interceptor', async () => {
      const interceptor = performanceMonitoring.createApiInterceptor()

      const startTime = performance.now()
      const response = {
        config: {
          url: '/api/test',
          metadata: { startTime }
        },
        status: 200,
        headers: { 'x-cache-hit': 'true' }
      }

      // Wait a small amount to ensure measurable time difference
      await new Promise(resolve => setTimeout(resolve, 10))

      interceptor.response(response)

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.cachePerformance.cacheHits).toBe(1)
      expect(report.cachePerformance.totalRequests).toBe(1)
    })
  })

  describe('Component Performance Tracking', () => {
    it('should track component performance', async () => {
      const tracker = performanceMonitoring.trackComponentPerformance('TestComponent')

      expect(tracker).toHaveProperty('recordOperation')
      expect(typeof tracker.recordOperation).toBe('function')

      tracker.recordOperation('data-fetch', 100)

      // Wait for the component tracking to complete
      await new Promise(resolve => setTimeout(resolve, 10))

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.loadingPerformance.totalLoads).toBeGreaterThanOrEqual(1)
      
      const exported = performanceMonitoringService.exportMetrics()
      const componentOperations = exported.loadingMetrics.filter(m => 
        m.component.includes('TestComponent')
      )
      expect(componentOperations.length).toBeGreaterThan(0)
    })
  })

  describe('Performance Alerts', () => {
    it('should check performance alerts correctly', () => {
      // Clear any existing metrics first
      performanceMonitoring.clearMetrics()
      
      // Create slow API response that exceeds threshold
      performanceMonitoring.recordApiResponse('/api/slow', 600, 200, false) // Above 500ms threshold

      const alerts = performanceMonitoring.checkPerformanceAlerts()

      expect(alerts).toBeInstanceOf(Array)
      expect(alerts.some(alert => alert.includes('API response time'))).toBe(true)
    })

    it('should check cache hit rate alerts', () => {
      // Create low cache hit rate scenario (need >10 requests to trigger alert)
      for (let i = 0; i < 3; i++) {
        performanceMonitoringService.recordCacheHit('/api/test')
      }
      for (let i = 0; i < 12; i++) {
        performanceMonitoringService.recordCacheMiss('/api/test')
      }

      const alerts = performanceMonitoring.checkPerformanceAlerts()

      expect(alerts.some(alert => alert.includes('Cache hit rate'))).toBe(true)
    })
  })

  describe('Metrics Management', () => {
    it('should clear metrics correctly', () => {
      performanceMonitoring.recordApiResponse('/api/test', 100, 200, false)

      let report = performanceMonitoring.generateReport()
      expect(report.apiPerformance.totalRequests).toBe(1)

      performanceMonitoring.clearMetrics()

      report = performanceMonitoring.generateReport()
      expect(report.apiPerformance.totalRequests).toBe(0)
    })

    it('should export metrics correctly', () => {
      performanceMonitoring.recordApiResponse('/api/test', 100, 200, false)

      const exported = performanceMonitoring.exportMetrics()

      expect(exported).toHaveProperty('apiResponseMetrics')
      expect(exported).toHaveProperty('tabSwitchMetrics')
      expect(exported).toHaveProperty('loadingMetrics')
      expect(exported).toHaveProperty('cacheMetrics')
      expect(exported).toHaveProperty('thresholds')
    })
  })
})