import { ref, onMounted, onUnmounted } from 'vue'

interface PerformanceMetrics {
  loadTime: number
  renderTime: number
  interactionTime: number
  memoryUsage?: number
}

export function usePerformanceMonitoring(componentName: string) {
  const metrics = ref<PerformanceMetrics>({
    loadTime: 0,
    renderTime: 0,
    interactionTime: 0
  })

  const startTime = ref(0)
  const renderStartTime = ref(0)

  // Start performance measurement
  const startMeasurement = () => {
    startTime.value = performance.now()
    renderStartTime.value = performance.now()
  }

  // Measure load time
  const measureLoadTime = () => {
    if (startTime.value > 0) {
      metrics.value.loadTime = performance.now() - startTime.value
    }
  }

  // Measure render time
  const measureRenderTime = () => {
    if (renderStartTime.value > 0) {
      metrics.value.renderTime = performance.now() - renderStartTime.value
    }
  }

  // Measure interaction time
  const measureInteractionTime = (interactionStart: number) => {
    metrics.value.interactionTime = performance.now() - interactionStart
  }

  // Get memory usage (if available)
  const getMemoryUsage = () => {
    if ('memory' in performance) {
      const memory = (performance as any).memory
      return {
        used: memory.usedJSHeapSize,
        total: memory.totalJSHeapSize,
        limit: memory.jsHeapSizeLimit
      }
    }
    return null
  }

  // Log performance metrics
  const logMetrics = () => {
    const memoryInfo = getMemoryUsage()
    
    console.group(`🚀 Performance Metrics - ${componentName}`)
    console.log(`⏱️ Load Time: ${metrics.value.loadTime.toFixed(2)}ms`)
    console.log(`🎨 Render Time: ${metrics.value.renderTime.toFixed(2)}ms`)
    console.log(`👆 Interaction Time: ${metrics.value.interactionTime.toFixed(2)}ms`)
    
    if (memoryInfo) {
      console.log(`💾 Memory Usage: ${(memoryInfo.used / 1024 / 1024).toFixed(2)}MB`)
      console.log(`💾 Memory Total: ${(memoryInfo.total / 1024 / 1024).toFixed(2)}MB`)
    }
    
    console.groupEnd()
  }

  // Performance observer for monitoring
  let performanceObserver: PerformanceObserver | null = null

  const startPerformanceObserver = () => {
    if ('PerformanceObserver' in window) {
      performanceObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        entries.forEach((entry) => {
          if (entry.entryType === 'measure' && entry.name.includes(componentName)) {
            console.log(`📊 ${entry.name}: ${entry.duration.toFixed(2)}ms`)
          }
        })
      })

      try {
        performanceObserver.observe({ entryTypes: ['measure', 'navigation', 'paint'] })
      } catch (error) {
        console.warn('Performance observer not supported:', error)
      }
    }
  }

  const stopPerformanceObserver = () => {
    if (performanceObserver) {
      performanceObserver.disconnect()
      performanceObserver = null
    }
  }

  // Mark performance points
  const mark = (name: string) => {
    if ('performance' in window && 'mark' in performance) {
      performance.mark(`${componentName}-${name}`)
    }
  }

  // Measure between marks
  const measure = (name: string, startMark: string, endMark?: string) => {
    if ('performance' in window && 'measure' in performance) {
      try {
        performance.measure(
          `${componentName}-${name}`,
          `${componentName}-${startMark}`,
          endMark ? `${componentName}-${endMark}` : undefined
        )
      } catch (error) {
        console.warn('Performance measure failed:', error)
      }
    }
  }

  // Lifecycle hooks
  onMounted(() => {
    startMeasurement()
    startPerformanceObserver()
    mark('mounted')
    
    // Measure after next tick (when DOM is updated)
    setTimeout(() => {
      measureLoadTime()
      measureRenderTime()
      mark('rendered')
      measure('mount-to-render', 'mounted', 'rendered')
    }, 0)
  })

  onUnmounted(() => {
    stopPerformanceObserver()
    mark('unmounted')
    measure('total-lifecycle', 'mounted', 'unmounted')
    
    // Log final metrics in development
    if (import.meta.env.DEV) {
      logMetrics()
    }
  })

  return {
    metrics,
    measureInteractionTime,
    getMemoryUsage,
    logMetrics,
    mark,
    measure
  }
}

// Composable for monitoring API performance
export function useApiPerformanceMonitoring() {
  const apiMetrics = ref<Record<string, number>>({})

  const measureApiCall = async <T>(
    apiName: string,
    apiCall: () => Promise<T>
  ): Promise<T> => {
    const startTime = performance.now()
    
    try {
      const result = await apiCall()
      const endTime = performance.now()
      const duration = endTime - startTime
      
      apiMetrics.value[apiName] = duration
      
      // Log slow API calls
      if (duration > 1000) {
        console.warn(`🐌 Slow API call detected: ${apiName} took ${duration.toFixed(2)}ms`)
      }
      
      return result
    } catch (error) {
      const endTime = performance.now()
      const duration = endTime - startTime
      
      console.error(`❌ API call failed: ${apiName} (${duration.toFixed(2)}ms)`, error)
      throw error
    }
  }

  const getApiMetrics = () => {
    return { ...apiMetrics.value }
  }

  const clearApiMetrics = () => {
    apiMetrics.value = {}
  }

  return {
    apiMetrics,
    measureApiCall,
    getApiMetrics,
    clearApiMetrics
  }
}