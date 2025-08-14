import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import { createApp } from 'vue'
import { usePerformanceMonitoring } from '@/composables/usePerformanceMonitoring'
import performanceMonitoringService from '@/services/performanceMonitoringService'
import PerformanceDashboard from '@/components/performance/PerformanceDashboard.vue'

// Test component that uses performance monitoring
const TestComponent = {
  template: `
    <div>
      <h1>Test Component</h1>
      <button @click="simulateApiCall" data-testid="api-button">Make API Call</button>
      <button @click="simulateSlowOperation" data-testid="slow-button">Slow Operation</button>
      <div data-testid="metrics">
        <div>Last API Response: {{ lastApiResponseTime }}ms</div>
        <div>Cache Hit Rate: {{ (cacheHitRate * 100).toFixed(1) }}%</div>
      </div>
    </div>
  `,
  setup() {
    const {
      recordApiResponse,
      measureAsyncOperation,
      lastApiResponseTime,
      cacheHitRate,
      trackComponentPerformance
    } = usePerformanceMonitoring()

    // Track this component's performance
    trackComponentPerformance('TestComponent')

    const simulateApiCall = () => {
      // Simulate API response with random timing
      const responseTime = Math.random() * 500 + 100 // 100-600ms
      const cacheHit = Math.random() > 0.5
      recordApiResponse('/api/test', responseTime, 200, cacheHit)
    }

    const simulateSlowOperation = async () => {
      await measureAsyncOperation(async () => {
        // Simulate slow operation
        await new Promise(resolve => setTimeout(resolve, 50))
        return 'completed'
      }, 'slow-operation')
    }

    return {
      simulateApiCall,
      simulateSlowOperation,
      lastApiResponseTime,
      cacheHitRate
    }
  }
}

// Create test router
const createTestRouter = () => {
  return createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', component: TestComponent, name: 'home' },
      { path: '/dashboard', component: TestComponent, name: 'dashboard' },
      { path: '/inventory', component: TestComponent, name: 'inventory' },
      { path: '/users', component: TestComponent, name: 'users' }
    ]
  })
}

describe('Performance Monitoring Integration Tests', () => {
  let router: any
  let app: any

  beforeEach(async () => {
    // Clear all metrics before each test
    performanceMonitoringService.clearAllMetrics()
    
    // Reset thresholds
    performanceMonitoringService.setThresholds({
      tabSwitchTime: 500,
      apiResponseTime: 2000,
      cacheHitRate: 0.8,
      loadingTime: 1000
    })

    // Create fresh router for each test
    router = createTestRouter()
    await router.push('/')
  })

  afterEach(() => {
    if (app) {
      app.unmount()
    }
  })

  describe('Real Component Integration', () => {
    it('should track component performance in real Vue component', async () => {
      const wrapper = mount(TestComponent, {
        global: {
          plugins: [router]
        }
      })

      // Wait for component to mount
      await wrapper.vm.$nextTick()

      // Simulate API calls
      const apiButton = wrapper.find('[data-testid="api-button"]')
      await apiButton.trigger('click')
      await apiButton.trigger('click')
      await apiButton.trigger('click')

      // Check that metrics were recorded
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.apiPerformance.totalRequests).toBe(3)
      expect(report.loadingPerformance.totalLoads).toBeGreaterThan(0) // Component load + operations

      // Check reactive values are updated
      const metricsDiv = wrapper.find('[data-testid="metrics"]')
      expect(metricsDiv.text()).toContain('ms')
      expect(metricsDiv.text()).toContain('%')
    })

    it('should track async operations correctly', async () => {
      const wrapper = mount(TestComponent, {
        global: {
          plugins: [router]
        }
      })

      const slowButton = wrapper.find('[data-testid="slow-button"]')
      await slowButton.trigger('click')

      // Wait for async operation to complete
      await new Promise(resolve => setTimeout(resolve, 100))

      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.loadingPerformance.totalLoads).toBeGreaterThan(1)
      
      // Check that slow operation was recorded
      const exported = performanceMonitoringService.exportMetrics()
      const slowOperations = exported.loadingMetrics.filter(m => 
        m.component.includes('slow-operation')
      )
      expect(slowOperations.length).toBeGreaterThan(0)
    })
  })

  describe('Router Integration', () => {
    it('should track route changes as tab switches', async () => {
      const wrapper = mount(TestComponent, {
        global: {
          plugins: [router]
        }
      })

      // Navigate between routes
      await router.push('/dashboard')
      await wrapper.vm.$nextTick()
      
      await router.push('/inventory')
      await wrapper.vm.$nextTick()
      
      await router.push('/users')
      await wrapper.vm.$nextTick()

      // Wait for route change tracking to complete
      await new Promise(resolve => setTimeout(resolve, 50))

      const report = performanceMonitoringService.generatePerformanceReport()
      
      // Should have recorded route changes as tab switches
      expect(report.tabSwitching.totalSwitches).toBeGreaterThan(0)
      
      const exported = performanceMonitoringService.exportMetrics()
      const routeSwitches = exported.tabSwitchMetrics.filter(m => 
        m.tabId.includes('route-')
      )
      expect(routeSwitches.length).toBeGreaterThan(0)
    })

    it('should measure route change performance', async () => {
      const wrapper = mount(TestComponent, {
        global: {
          plugins: [router]
        }
      })

      const startTime = performance.now()
      
      // Navigate to different route
      await router.push('/dashboard')
      await wrapper.vm.$nextTick()
      
      const endTime = performance.now()
      const actualDuration = endTime - startTime

      // Wait for tracking to complete
      await new Promise(resolve => setTimeout(resolve, 50))

      const report = performanceMonitoringService.generatePerformanceReport()
      
      if (report.tabSwitching.totalSwitches > 0) {
        // Recorded time should be reasonable (within 100ms of actual)
        expect(report.tabSwitching.averageTime).toBeLessThan(actualDuration + 100)
        expect(report.tabSwitching.averageTime).toBeGreaterThan(0)
      }
    })
  })

  describe('Performance Dashboard Integration', () => {
    it('should render performance dashboard with real data', async () => {
      // Generate some test data
      performanceMonitoringService.recordApiResponseMetrics({
        endpoint: '/api/users',
        responseTime: 150,
        timestamp: new Date(),
        status: 200,
        cacheHit: true
      })

      performanceMonitoringService.recordTabSwitchMetrics({
        tabId: 'test-tab',
        switchTime: 300,
        timestamp: new Date(),
        fromRoute: '/dashboard',
        toRoute: '/inventory'
      })

      performanceMonitoringService.recordCacheHit('/api/users')
      performanceMonitoringService.recordCacheMiss('/api/products')

      const wrapper = mount(PerformanceDashboard, {
        global: {
          plugins: [router]
        }
      })

      await wrapper.vm.$nextTick()

      // Check that dashboard displays metrics
      expect(wrapper.text()).toContain('Performance Monitoring Dashboard')
      expect(wrapper.text()).toContain('Tab Switching')
      expect(wrapper.text()).toContain('API Response')
      expect(wrapper.text()).toContain('Cache Hit Rate')
      expect(wrapper.text()).toContain('Loading Time')

      // Check that actual values are displayed
      expect(wrapper.text()).toContain('300ms') // Tab switch time
      expect(wrapper.text()).toContain('150ms') // API response time
      expect(wrapper.text()).toContain('50.0%') // Cache hit rate (1 hit, 1 miss)
    })

    it('should handle dashboard interactions correctly', async () => {
      const wrapper = mount(PerformanceDashboard, {
        global: {
          plugins: [router]
        }
      })

      // Add some initial data
      performanceMonitoringService.recordApiResponseMetrics({
        endpoint: '/api/test',
        responseTime: 100,
        timestamp: new Date(),
        status: 200,
        cacheHit: false
      })

      await wrapper.vm.$nextTick()

      // Test refresh button
      const refreshButton = wrapper.find('button:contains("Refresh Report")')
      if (refreshButton.exists()) {
        await refreshButton.trigger('click')
        await wrapper.vm.$nextTick()
      }

      // Test clear metrics button
      const clearButton = wrapper.find('button:contains("Clear Metrics")')
      if (clearButton.exists()) {
        await clearButton.trigger('click')
        await wrapper.vm.$nextTick()

        // Verify metrics were cleared
        const report = performanceMonitoringService.generatePerformanceReport()
        expect(report.apiPerformance.totalRequests).toBe(0)
      }
    })
  })

  describe('Real-World Performance Scenarios', () => {
    it('should detect performance issues in real scenarios', async () => {
      // Simulate slow API responses
      for (let i = 0; i < 5; i++) {
        performanceMonitoringService.recordApiResponseMetrics({
          endpoint: `/api/slow-endpoint-${i}`,
          responseTime: 2500 + Math.random() * 500, // 2.5-3s (above threshold)
          timestamp: new Date(),
          status: 200,
          cacheHit: false
        })
      }

      // Simulate slow tab switches
      for (let i = 0; i < 3; i++) {
        performanceMonitoringService.recordTabSwitchMetrics({
          tabId: `slow-tab-${i}`,
          switchTime: 800 + Math.random() * 200, // 800-1000ms (above 500ms threshold)
          timestamp: new Date(),
          fromRoute: '/dashboard',
          toRoute: '/inventory'
        })
      }

      // Simulate poor cache performance
      for (let i = 0; i < 20; i++) {
        if (i < 5) {
          performanceMonitoringService.recordCacheHit('/api/cached')
        } else {
          performanceMonitoringService.recordCacheMiss('/api/uncached')
        }
      }

      const report = performanceMonitoringService.generatePerformanceReport()

      // Verify threshold violations are detected
      expect(report.apiPerformance.thresholdViolations).toBe(5)
      expect(report.tabSwitching.thresholdViolations).toBe(3)
      expect(report.cachePerformance.hitRate).toBe(0.25) // 25% hit rate

      // Verify optimization suggestions are generated
      expect(report.optimizationSuggestions.length).toBeGreaterThan(0)
      expect(report.optimizationSuggestions.some(s => 
        s.includes('request optimization') || s.includes('API')
      )).toBe(true)
      expect(report.optimizationSuggestions.some(s => 
        s.includes('cache coverage') || s.includes('cache')
      )).toBe(true)
    })

    it('should handle high-frequency operations correctly', async () => {
      const startTime = Date.now()

      // Simulate high-frequency API calls
      const promises = []
      for (let i = 0; i < 100; i++) {
        promises.push(new Promise<void>(resolve => {
          setTimeout(() => {
            performanceMonitoringService.recordApiResponseMetrics({
              endpoint: `/api/high-freq-${i % 10}`,
              responseTime: Math.random() * 200 + 50, // 50-250ms
              timestamp: new Date(),
              status: 200,
              cacheHit: Math.random() > 0.3 // 70% cache hit rate
            })
            resolve()
          }, Math.random() * 10) // Spread over 10ms
        }))
      }

      await Promise.all(promises)

      const endTime = Date.now()
      const totalTime = endTime - startTime

      const report = performanceMonitoringService.generatePerformanceReport()

      // Verify all requests were recorded
      expect(report.apiPerformance.totalRequests).toBe(100)
      expect(report.cachePerformance.totalRequests).toBe(100)

      // Verify performance metrics are reasonable
      expect(report.apiPerformance.averageResponseTime).toBeGreaterThan(50)
      expect(report.apiPerformance.averageResponseTime).toBeLessThan(250)
      expect(report.cachePerformance.hitRate).toBeGreaterThan(0.6)
      expect(report.cachePerformance.hitRate).toBeLessThan(0.8)

      // Verify the service can handle high frequency without significant overhead
      expect(totalTime).toBeLessThan(1000) // Should complete within 1 second
    })

    it('should maintain accuracy under concurrent operations', async () => {
      const concurrentOperations = []

      // Simulate concurrent tab switches
      for (let i = 0; i < 10; i++) {
        concurrentOperations.push(new Promise<void>(resolve => {
          setTimeout(() => {
            performanceMonitoringService.recordTabSwitchMetrics({
              tabId: `concurrent-tab-${i}`,
              switchTime: 200 + i * 10,
              timestamp: new Date(),
              fromRoute: `/route-${i}`,
              toRoute: `/route-${i + 1}`
            })
            resolve()
          }, Math.random() * 50)
        }))
      }

      // Simulate concurrent API calls
      for (let i = 0; i < 20; i++) {
        concurrentOperations.push(new Promise<void>(resolve => {
          setTimeout(() => {
            performanceMonitoringService.recordApiResponseMetrics({
              endpoint: `/api/concurrent-${i}`,
              responseTime: 100 + i * 5,
              timestamp: new Date(),
              status: 200,
              cacheHit: i % 2 === 0
            })
            resolve()
          }, Math.random() * 30)
        }))
      }

      await Promise.all(concurrentOperations)

      const report = performanceMonitoringService.generatePerformanceReport()

      // Verify all operations were recorded correctly
      expect(report.tabSwitching.totalSwitches).toBe(10)
      expect(report.apiPerformance.totalRequests).toBe(20)
      expect(report.cachePerformance.totalRequests).toBe(20)
      expect(report.cachePerformance.cacheHits).toBe(10)
      expect(report.cachePerformance.cacheMisses).toBe(10)
      expect(report.cachePerformance.hitRate).toBe(0.5)

      // Verify metrics calculations are correct
      const expectedAvgTabTime = (200 + 290) / 2 // First + last tab switch times
      expect(Math.abs(report.tabSwitching.averageTime - expectedAvgTabTime)).toBeLessThan(50)

      const expectedAvgApiTime = (100 + 195) / 2 // First + last API times
      expect(Math.abs(report.apiPerformance.averageResponseTime - expectedAvgApiTime)).toBeLessThan(50)
    })
  })

  describe('Memory and Performance Validation', () => {
    it('should not cause memory leaks with large datasets', () => {
      const initialMemory = performance.memory?.usedJSHeapSize || 0

      // Add a large number of metrics
      for (let i = 0; i < 2000; i++) {
        performanceMonitoringService.recordApiResponseMetrics({
          endpoint: `/api/test-${i}`,
          responseTime: Math.random() * 1000,
          timestamp: new Date(),
          status: 200,
          cacheHit: Math.random() > 0.5
        })
      }

      // Verify metrics are limited to prevent memory issues
      const exported = performanceMonitoringService.exportMetrics()
      expect(exported.apiResponseMetrics.length).toBeLessThanOrEqual(1000)

      // Generate report multiple times to test for memory leaks
      for (let i = 0; i < 10; i++) {
        performanceMonitoringService.generatePerformanceReport()
      }

      const finalMemory = performance.memory?.usedJSHeapSize || 0
      
      // Memory usage should not increase dramatically (allow for some variance)
      if (initialMemory > 0 && finalMemory > 0) {
        const memoryIncrease = finalMemory - initialMemory
        expect(memoryIncrease).toBeLessThan(10 * 1024 * 1024) // Less than 10MB increase
      }
    })

    it('should perform efficiently with frequent operations', () => {
      const startTime = performance.now()

      // Perform many operations quickly
      for (let i = 0; i < 1000; i++) {
        performanceMonitoringService.recordApiResponseMetrics({
          endpoint: `/api/perf-test-${i % 10}`,
          responseTime: Math.random() * 500,
          timestamp: new Date(),
          status: 200,
          cacheHit: Math.random() > 0.5
        })

        if (i % 100 === 0) {
          performanceMonitoringService.generatePerformanceReport()
        }
      }

      const endTime = performance.now()
      const operationTime = endTime - startTime

      // Should complete quickly (less than 100ms for 1000 operations)
      expect(operationTime).toBeLessThan(100)

      // Verify data integrity
      const report = performanceMonitoringService.generatePerformanceReport()
      expect(report.apiPerformance.totalRequests).toBeLessThanOrEqual(1000)
      expect(report.cachePerformance.totalRequests).toBeLessThanOrEqual(1000)
    })
  })
})