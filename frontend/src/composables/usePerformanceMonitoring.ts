import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import performanceMonitoringService, { 
  type TabSwitchMetrics, 
  type ApiResponseMetrics, 
  type LoadingMetrics,
  type PerformanceReport,
  type PerformanceThresholds
} from '@/services/performanceMonitoringService'

export function usePerformanceMonitoring(componentName?: string) {
  const router = useRouter()
  const route = useRoute()
  
  const isMonitoring = ref(true)
  const currentSwitchId = ref<string | null>(null)
  const performanceMarks = new Map<string, number>()
  
  // Performance metrics reactive refs
  const performanceReport = ref<PerformanceReport | null>(null)
  const lastTabSwitchTime = ref<number>(0)
  const lastApiResponseTime = ref<number>(0)
  const cacheHitRate = computed(() => {
    const report = performanceReport.value
    return report ? report.cachePerformance.hitRate : 0
  })

  // Performance marking methods
  const mark = (markName: string): void => {
    if (!isMonitoring.value) return
    const timestamp = performance.now()
    performanceMarks.set(markName, timestamp)
    
    // Also use native performance API if available
    if (performance.mark) {
      performance.mark(`${componentName || 'component'}-${markName}`)
    }
  }

  const measure = (measureName: string, startMark: string, endMark?: string): number => {
    if (!isMonitoring.value) return 0
    
    const startTime = performanceMarks.get(startMark)
    const endTime = endMark ? performanceMarks.get(endMark) : performance.now()
    
    if (startTime === undefined || endTime === undefined) {
      console.warn(`Performance marks not found: ${startMark} or ${endMark}`)
      return 0
    }
    
    const duration = endTime - startTime
    
    // Record as loading metrics if it's a component measurement
    if (componentName) {
      performanceMonitoringService.recordLoadingMetrics({
        component: componentName,
        loadTime: duration,
        timestamp: new Date(),
        isInitialLoad: measureName.includes('initial') || measureName.includes('mount')
      })
    }
    
    // Use native performance API if available
    if (performance.measure) {
      try {
        performance.measure(
          `${componentName || 'component'}-${measureName}`,
          `${componentName || 'component'}-${startMark}`,
          endMark ? `${componentName || 'component'}-${endMark}` : undefined
        )
      } catch (error) {
        // Ignore errors from performance API
      }
    }
    
    return duration
  }

  // Tab switching monitoring
  const startTabSwitchTracking = (tabId?: string): string => {
    if (!isMonitoring.value) return ''
    
    const actualTabId = tabId || `tab-${Date.now()}`
    const switchId = performanceMonitoringService.startTabSwitch(
      actualTabId, 
      route.path
    )
    currentSwitchId.value = switchId
    return switchId
  }

  const endTabSwitchTracking = (toRoute?: string): TabSwitchMetrics | null => {
    if (!isMonitoring.value || !currentSwitchId.value) return null
    
    const metrics = performanceMonitoringService.endTabSwitch(
      currentSwitchId.value,
      toRoute || route.path
    )
    
    if (metrics) {
      lastTabSwitchTime.value = metrics.switchTime
    }
    
    currentSwitchId.value = null
    return metrics
  }

  // API response monitoring
  const recordApiResponse = (
    endpoint: string, 
    responseTime: number, 
    status: number, 
    cacheHit: boolean = false
  ): void => {
    if (!isMonitoring.value) return
    
    const metrics: ApiResponseMetrics = {
      endpoint,
      responseTime,
      timestamp: new Date(),
      status,
      cacheHit
    }
    
    performanceMonitoringService.recordApiResponseMetrics(metrics)
    lastApiResponseTime.value = responseTime
    
    // Update cache metrics
    if (cacheHit) {
      performanceMonitoringService.recordCacheHit(endpoint)
    } else {
      performanceMonitoringService.recordCacheMiss(endpoint)
    }
  }

  // Component loading monitoring
  const recordComponentLoad = (
    componentName: string, 
    loadTime: number, 
    isInitialLoad: boolean = false
  ): void => {
    if (!isMonitoring.value) return
    
    const metrics: LoadingMetrics = {
      component: componentName,
      loadTime,
      timestamp: new Date(),
      isInitialLoad
    }
    
    performanceMonitoringService.recordLoadingMetrics(metrics)
  }

  // Performance measurement helpers
  const measureAsyncOperation = async <T>(
    operation: () => Promise<T>,
    operationName: string
  ): Promise<{ result: T; duration: number }> => {
    const startTime = performance.now()
    
    try {
      const result = await operation()
      const duration = performance.now() - startTime
      
      // Record as component load time
      recordComponentLoad(operationName, duration)
      
      return { result, duration }
    } catch (error) {
      const duration = performance.now() - startTime
      recordComponentLoad(`${operationName}-error`, duration)
      throw error
    }
  }

  const measureSyncOperation = <T>(
    operation: () => T,
    operationName: string
  ): { result: T; duration: number } => {
    const startTime = performance.now()
    
    try {
      const result = operation()
      const duration = performance.now() - startTime
      
      recordComponentLoad(operationName, duration)
      
      return { result, duration }
    } catch (error) {
      const duration = performance.now() - startTime
      recordComponentLoad(`${operationName}-error`, duration)
      throw error
    }
  }

  // Performance reporting
  const generateReport = (): PerformanceReport => {
    const report = performanceMonitoringService.generatePerformanceReport()
    performanceReport.value = report
    return report
  }

  const getOptimizationSuggestions = (): string[] => {
    const report = generateReport()
    return report.optimizationSuggestions
  }

  // Threshold management
  const setPerformanceThresholds = (thresholds: Partial<PerformanceThresholds>): void => {
    performanceMonitoringService.setThresholds(thresholds)
  }

  const getPerformanceThresholds = (): PerformanceThresholds => {
    return performanceMonitoringService.getThresholds()
  }

  // Monitoring control
  const enableMonitoring = (): void => {
    isMonitoring.value = true
  }

  const disableMonitoring = (): void => {
    isMonitoring.value = false
  }

  const clearMetrics = (): void => {
    performanceMonitoringService.clearAllMetrics()
    performanceReport.value = null
    lastTabSwitchTime.value = 0
    lastApiResponseTime.value = 0
  }

  // Auto-tracking for route changes
  const setupAutoTracking = () => {
    let routeChangeStartTime: number | null = null
    
    // Track route changes as tab switches
    router.beforeEach((to, from) => {
      if (isMonitoring.value && from.path !== to.path) {
        routeChangeStartTime = performance.now()
        startTabSwitchTracking(`route-${String(to.name) || to.path}`)
      }
    })
    
    router.afterEach((to, from) => {
      if (isMonitoring.value && routeChangeStartTime && from.path !== to.path) {
        endTabSwitchTracking(to.path)
        routeChangeStartTime = null
      }
    })
  }

  // Performance monitoring for API interceptors
  const createApiInterceptor = () => {
    return {
      request: (config: any) => {
        config.metadata = { startTime: performance.now() }
        return config
      },
      response: (response: any) => {
        const endTime = performance.now()
        const startTime = response.config?.metadata?.startTime
        
        if (startTime && isMonitoring.value) {
          const responseTime = endTime - startTime
          const cacheHit = response.headers?.['x-cache-hit'] === 'true'
          
          recordApiResponse(
            response.config.url || 'unknown',
            responseTime,
            response.status,
            cacheHit
          )
        }
        
        return response
      },
      error: (error: any) => {
        const endTime = performance.now()
        const startTime = error.config?.metadata?.startTime
        
        if (startTime && isMonitoring.value) {
          const responseTime = endTime - startTime
          
          recordApiResponse(
            error.config?.url || 'unknown',
            responseTime,
            error.response?.status || 0,
            false
          )
        }
        
        return Promise.reject(error)
      }
    }
  }

  // Component performance tracking
  const trackComponentPerformance = (componentName: string) => {
    const startTime = performance.now()
    
    onMounted(() => {
      const loadTime = performance.now() - startTime
      recordComponentLoad(componentName, loadTime, true)
    })
    
    return {
      recordOperation: (operationName: string, duration: number) => {
        recordComponentLoad(`${componentName}-${operationName}`, duration)
      }
    }
  }

  // Performance alerts
  const checkPerformanceAlerts = () => {
    const report = generateReport()
    const thresholds = getPerformanceThresholds()
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

  // Export metrics
  const exportMetrics = () => {
    return performanceMonitoringService.exportMetrics()
  }

  // Setup auto-tracking on mount
  onMounted(() => {
    setupAutoTracking()
  })

  return {
    // State
    isMonitoring,
    performanceReport,
    lastTabSwitchTime,
    lastApiResponseTime,
    cacheHitRate,
    
    // Performance marking
    mark,
    measure,
    
    // Tab switching
    startTabSwitchTracking,
    endTabSwitchTracking,
    
    // API monitoring
    recordApiResponse,
    createApiInterceptor,
    
    // Component monitoring
    recordComponentLoad,
    trackComponentPerformance,
    measureAsyncOperation,
    measureSyncOperation,
    
    // Reporting
    generateReport,
    getOptimizationSuggestions,
    checkPerformanceAlerts,
    
    // Configuration
    setPerformanceThresholds,
    getPerformanceThresholds,
    
    // Control
    enableMonitoring,
    disableMonitoring,
    clearMetrics,
    exportMetrics
  }
}