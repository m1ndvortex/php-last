// Performance monitoring service - framework agnostic

export interface TabSwitchMetrics {
  tabId: string
  switchTime: number
  timestamp: Date
  fromRoute: string
  toRoute: string
}

export interface ApiResponseMetrics {
  endpoint: string
  responseTime: number
  timestamp: Date
  status: number
  cacheHit: boolean
}

export interface CacheMetrics {
  totalRequests: number
  cacheHits: number
  cacheMisses: number
  hitRate: number
  lastUpdated: Date
}

export interface LoadingMetrics {
  component: string
  loadTime: number
  timestamp: Date
  isInitialLoad: boolean
}

export interface PerformanceThresholds {
  tabSwitchTime: number // 100ms default - optimized for better performance
  apiResponseTime: number // 1500ms default - tightened for better UX
  cacheHitRate: number // 0.8 (80%) default
  loadingTime: number // 500ms default - faster loading expectations
}

export interface PerformanceReport {
  tabSwitching: {
    averageTime: number
    slowestSwitch: TabSwitchMetrics | null
    fastestSwitch: TabSwitchMetrics | null
    totalSwitches: number
    thresholdViolations: number
  }
  apiPerformance: {
    averageResponseTime: number
    slowestEndpoint: ApiResponseMetrics | null
    fastestEndpoint: ApiResponseMetrics | null
    totalRequests: number
    thresholdViolations: number
  }
  cachePerformance: CacheMetrics
  loadingPerformance: {
    averageLoadTime: number
    slowestComponent: LoadingMetrics | null
    fastestComponent: LoadingMetrics | null
    totalLoads: number
    thresholdViolations: number
  }
  optimizationSuggestions: string[]
}

class PerformanceMonitoringService {
  private tabSwitchMetrics: TabSwitchMetrics[] = []
  private apiResponseMetrics: ApiResponseMetrics[] = []
  private loadingMetrics: LoadingMetrics[] = []
  private cacheMetrics: CacheMetrics = {
    totalRequests: 0,
    cacheHits: 0,
    cacheMisses: 0,
    hitRate: 0,
    lastUpdated: new Date()
  }

  private thresholds: PerformanceThresholds = {
    tabSwitchTime: 100, // 100ms - enterprise-grade tab switching (like Google/Facebook)
    apiResponseTime: 500, // 500ms - enterprise-grade API responses (like top web apps)
    cacheHitRate: 0.85, // 85% - realistic cache hit rate for enterprise apps
    loadingTime: 300 // 300ms - enterprise-grade loading expectations
  }

  private maxMetricsHistory = 1000 // Keep last 1000 entries

  // Tab switching performance tracking
  startTabSwitch(tabId: string, fromRoute: string): string {
    const switchId = `${tabId}-${Date.now()}`
    const startTime = performance.now()
    
    // Store start time in session storage for persistence across tab switches
    sessionStorage.setItem(`tab-switch-${switchId}`, JSON.stringify({
      startTime,
      tabId,
      fromRoute,
      timestamp: new Date().toISOString()
    }))
    
    return switchId
  }

  endTabSwitch(switchId: string, toRoute: string): TabSwitchMetrics | null {
    const startData = sessionStorage.getItem(`tab-switch-${switchId}`)
    if (!startData) return null

    const { startTime, tabId, fromRoute } = JSON.parse(startData)
    const endTime = performance.now()
    const switchTime = endTime - startTime

    const metrics: TabSwitchMetrics = {
      tabId,
      switchTime,
      timestamp: new Date(),
      fromRoute,
      toRoute
    }

    this.recordTabSwitchMetrics(metrics)
    sessionStorage.removeItem(`tab-switch-${switchId}`)
    
    return metrics
  }

  recordTabSwitchMetrics(metrics: TabSwitchMetrics): void {
    this.tabSwitchMetrics.push(metrics)
    this.trimMetricsHistory(this.tabSwitchMetrics)
    
    // Check threshold violation
    if (metrics.switchTime > this.thresholds.tabSwitchTime) {
      console.warn(`Tab switch threshold violation: ${metrics.switchTime}ms > ${this.thresholds.tabSwitchTime}ms`)
    }
  }

  // API response time monitoring
  recordApiResponseMetrics(metrics: ApiResponseMetrics): void {
    this.apiResponseMetrics.push(metrics)
    this.trimMetricsHistory(this.apiResponseMetrics)
    
    // Check threshold violation
    if (metrics.responseTime > this.thresholds.apiResponseTime) {
      console.warn(`API response threshold violation: ${metrics.responseTime}ms > ${this.thresholds.apiResponseTime}ms`)
    }
  }

  // Cache performance tracking
  recordCacheHit(endpoint: string): void {
    this.cacheMetrics.totalRequests++
    this.cacheMetrics.cacheHits++
    this.updateCacheHitRate()
  }

  recordCacheMiss(endpoint: string): void {
    this.cacheMetrics.totalRequests++
    this.cacheMetrics.cacheMisses++
    this.updateCacheHitRate()
  }

  private updateCacheHitRate(): void {
    this.cacheMetrics.hitRate = this.cacheMetrics.totalRequests > 0 
      ? this.cacheMetrics.cacheHits / this.cacheMetrics.totalRequests 
      : 0
    this.cacheMetrics.lastUpdated = new Date()
    
    // Check threshold violation
    if (this.cacheMetrics.hitRate < this.thresholds.cacheHitRate && this.cacheMetrics.totalRequests > 10) {
      console.warn(`Cache hit rate below threshold: ${(this.cacheMetrics.hitRate * 100).toFixed(1)}% < ${(this.thresholds.cacheHitRate * 100)}%`)
    }
  }

  // Loading time analytics
  recordLoadingMetrics(metrics: LoadingMetrics): void {
    this.loadingMetrics.push(metrics)
    this.trimMetricsHistory(this.loadingMetrics)
    
    // Check threshold violation
    if (metrics.loadTime > this.thresholds.loadingTime) {
      console.warn(`Loading time threshold violation: ${metrics.loadTime}ms > ${this.thresholds.loadingTime}ms`)
    }
  }

  // Performance thresholds management
  setThresholds(newThresholds: Partial<PerformanceThresholds>): void {
    this.thresholds = { ...this.thresholds, ...newThresholds }
  }

  getThresholds(): PerformanceThresholds {
    return { ...this.thresholds }
  }

  // Performance reporting
  generatePerformanceReport(): PerformanceReport {
    const tabSwitchingReport = this.generateTabSwitchingReport()
    const apiPerformanceReport = this.generateApiPerformanceReport()
    const loadingPerformanceReport = this.generateLoadingPerformanceReport()
    const optimizationSuggestions = this.generateOptimizationSuggestions()

    return {
      tabSwitching: tabSwitchingReport,
      apiPerformance: apiPerformanceReport,
      cachePerformance: { ...this.cacheMetrics },
      loadingPerformance: loadingPerformanceReport,
      optimizationSuggestions
    }
  }

  private generateTabSwitchingReport() {
    if (this.tabSwitchMetrics.length === 0) {
      return {
        averageTime: 0,
        slowestSwitch: null,
        fastestSwitch: null,
        totalSwitches: 0,
        thresholdViolations: 0
      }
    }

    const times = this.tabSwitchMetrics.map(m => m.switchTime)
    const averageTime = times.reduce((sum, time) => sum + time, 0) / times.length
    const slowestSwitch = this.tabSwitchMetrics.reduce((slowest, current) => 
      current.switchTime > slowest.switchTime ? current : slowest
    )
    const fastestSwitch = this.tabSwitchMetrics.reduce((fastest, current) => 
      current.switchTime < fastest.switchTime ? current : fastest
    )
    const thresholdViolations = this.tabSwitchMetrics.filter(m => 
      m.switchTime > this.thresholds.tabSwitchTime
    ).length

    return {
      averageTime,
      slowestSwitch,
      fastestSwitch,
      totalSwitches: this.tabSwitchMetrics.length,
      thresholdViolations
    }
  }

  private generateApiPerformanceReport() {
    if (this.apiResponseMetrics.length === 0) {
      return {
        averageResponseTime: 0,
        slowestEndpoint: null,
        fastestEndpoint: null,
        totalRequests: 0,
        thresholdViolations: 0
      }
    }

    const times = this.apiResponseMetrics.map(m => m.responseTime)
    const averageResponseTime = times.reduce((sum, time) => sum + time, 0) / times.length
    const slowestEndpoint = this.apiResponseMetrics.reduce((slowest, current) => 
      current.responseTime > slowest.responseTime ? current : slowest
    )
    const fastestEndpoint = this.apiResponseMetrics.reduce((fastest, current) => 
      current.responseTime < fastest.responseTime ? current : fastest
    )
    const thresholdViolations = this.apiResponseMetrics.filter(m => 
      m.responseTime > this.thresholds.apiResponseTime
    ).length

    return {
      averageResponseTime,
      slowestEndpoint,
      fastestEndpoint,
      totalRequests: this.apiResponseMetrics.length,
      thresholdViolations
    }
  }

  private generateLoadingPerformanceReport() {
    if (this.loadingMetrics.length === 0) {
      return {
        averageLoadTime: 0,
        slowestComponent: null,
        fastestComponent: null,
        totalLoads: 0,
        thresholdViolations: 0
      }
    }

    const times = this.loadingMetrics.map(m => m.loadTime)
    const averageLoadTime = times.reduce((sum, time) => sum + time, 0) / times.length
    const slowestComponent = this.loadingMetrics.reduce((slowest, current) => 
      current.loadTime > slowest.loadTime ? current : slowest
    )
    const fastestComponent = this.loadingMetrics.reduce((fastest, current) => 
      current.loadTime < fastest.loadTime ? current : fastest
    )
    const thresholdViolations = this.loadingMetrics.filter(m => 
      m.loadTime > this.thresholds.loadingTime
    ).length

    return {
      averageLoadTime,
      slowestComponent,
      fastestComponent,
      totalLoads: this.loadingMetrics.length,
      thresholdViolations
    }
  }

  private generateOptimizationSuggestions(): string[] {
    const suggestions: string[] = []
    
    // Generate reports directly to avoid circular dependency
    const tabSwitchingReport = this.generateTabSwitchingReport()
    const apiPerformanceReport = this.generateApiPerformanceReport()
    const loadingPerformanceReport = this.generateLoadingPerformanceReport()

    // Tab switching suggestions
    if (tabSwitchingReport.averageTime > this.thresholds.tabSwitchTime * 0.8) {
      suggestions.push('Consider implementing route preloading to improve tab switching performance')
    }
    if (tabSwitchingReport.thresholdViolations > tabSwitchingReport.totalSwitches * 0.1) {
      suggestions.push('High number of slow tab switches detected - review component loading strategies')
    }

    // API performance suggestions
    if (apiPerformanceReport.averageResponseTime > this.thresholds.apiResponseTime * 0.7) {
      suggestions.push('API response times are approaching threshold - consider implementing request optimization')
    }
    if (apiPerformanceReport.thresholdViolations > apiPerformanceReport.totalRequests * 0.05) {
      suggestions.push('Frequent API timeout violations - review endpoint performance and caching strategies')
    }

    // Cache performance suggestions
    if (this.cacheMetrics.hitRate < this.thresholds.cacheHitRate) {
      suggestions.push(`Cache hit rate is ${(this.cacheMetrics.hitRate * 100).toFixed(1)}% - consider expanding cache coverage`)
    }
    if (this.cacheMetrics.hitRate < 0.5 && this.cacheMetrics.totalRequests > 50) {
      suggestions.push('Very low cache hit rate detected - review cache invalidation strategies')
    }

    // Loading performance suggestions
    if (loadingPerformanceReport.averageLoadTime > this.thresholds.loadingTime * 0.8) {
      suggestions.push('Component loading times are high - consider implementing lazy loading and code splitting')
    }

    return suggestions
  }

  // Utility methods
  private trimMetricsHistory<T>(metricsArray: T[]): void {
    if (metricsArray.length > this.maxMetricsHistory) {
      metricsArray.splice(0, metricsArray.length - this.maxMetricsHistory)
    }
  }

  // Clear all metrics (useful for testing)
  clearAllMetrics(): void {
    this.tabSwitchMetrics = []
    this.apiResponseMetrics = []
    this.loadingMetrics = []
    this.cacheMetrics.totalRequests = 0
    this.cacheMetrics.cacheHits = 0
    this.cacheMetrics.cacheMisses = 0
    this.cacheMetrics.hitRate = 0
    this.cacheMetrics.lastUpdated = new Date()
  }

  // Export metrics for analysis
  exportMetrics() {
    return {
      tabSwitchMetrics: [...this.tabSwitchMetrics],
      apiResponseMetrics: [...this.apiResponseMetrics],
      loadingMetrics: [...this.loadingMetrics],
      cacheMetrics: { ...this.cacheMetrics },
      thresholds: { ...this.thresholds }
    }
  }
}

// Create singleton instance
export const performanceMonitoringService = new PerformanceMonitoringService()
export default performanceMonitoringService