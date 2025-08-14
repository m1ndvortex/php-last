// Network error detector - standalone service without Vue dependencies

export interface NetworkError {
  type: 'timeout' | 'offline' | 'server_error' | 'connection_failed'
  message: string
  timestamp: Date
  retryCount: number
  originalRequest?: any
}

export interface RetryConfig {
  maxRetries: number
  baseDelay: number
  maxDelay: number
  backoffMultiplier: number
  retryableStatuses: number[]
}

export interface NetworkStatus {
  isOnline: boolean
  connectionType: string
  effectiveType: string
  downlink: number
  rtt: number
}

class NetworkErrorDetector {
  private retryConfig: RetryConfig = {
    maxRetries: 3,
    baseDelay: 1000,
    maxDelay: 30000,
    backoffMultiplier: 2,
    retryableStatuses: [408, 429, 500, 502, 503, 504]
  }

  private networkStatus: NetworkStatus = {
    isOnline: navigator.onLine,
    connectionType: 'unknown',
    effectiveType: 'unknown',
    downlink: 0,
    rtt: 0
  }

  private errorHistory: NetworkError[] = []
  private retryQueue: Map<string, any> = new Map()

  constructor() {
    this.initializeNetworkMonitoring()
  }

  private initializeNetworkMonitoring(): void {
    // Monitor online/offline status
    window.addEventListener('online', () => {
      this.networkStatus.isOnline = true
      this.processRetryQueue()
    })

    window.addEventListener('offline', () => {
      this.networkStatus.isOnline = false
    })

    // Monitor connection quality if available
    if ('connection' in navigator) {
      const connection = (navigator as any).connection
      this.updateConnectionInfo(connection)
      
      connection.addEventListener('change', () => {
        this.updateConnectionInfo(connection)
      })
    }
  }

  private updateConnectionInfo(connection: any): void {
    this.networkStatus = {
      ...this.networkStatus,
      connectionType: connection.type || 'unknown',
      effectiveType: connection.effectiveType || 'unknown',
      downlink: connection.downlink || 0,
      rtt: connection.rtt || 0
    }
  }

  public detectError(error: any, request?: any): NetworkError {
    const networkError: NetworkError = {
      type: this.classifyError(error),
      message: error.message || 'Unknown network error',
      timestamp: new Date(),
      retryCount: 0,
      originalRequest: request
    }

    this.errorHistory.push(networkError)
    
    // Keep only last 100 errors
    if (this.errorHistory.length > 100) {
      this.errorHistory.shift()
    }

    return networkError
  }

  private classifyError(error: any): NetworkError['type'] {
    // Check current online status dynamically
    if (!navigator.onLine) {
      return 'offline'
    }

    if (error.code === 'ECONNABORTED' || error.message?.includes('timeout') || error.name === 'AbortError') {
      return 'timeout'
    }

    if (error.response?.status >= 500) {
      return 'server_error'
    }

    if (error.code === 'NETWORK_ERROR' || !error.response) {
      return 'connection_failed'
    }

    return 'connection_failed'
  }

  public shouldRetry(error: NetworkError, response?: any): boolean {
    if (error.retryCount >= this.retryConfig.maxRetries) {
      return false
    }

    // Check current online status dynamically
    if (!navigator.onLine) {
      return false
    }

    if (response?.status && !this.retryConfig.retryableStatuses.includes(response.status)) {
      return false
    }

    return true
  }

  public calculateRetryDelay(retryCount: number): number {
    const delay = Math.min(
      this.retryConfig.baseDelay * Math.pow(this.retryConfig.backoffMultiplier, retryCount),
      this.retryConfig.maxDelay
    )

    // Add jitter to prevent thundering herd
    return delay + Math.random() * 1000
  }

  public async retryWithBackoff<T>(
    operation: () => Promise<T>,
    error: NetworkError
  ): Promise<T> {
    if (!this.shouldRetry(error)) {
      throw error
    }

    error.retryCount++
    const delay = this.calculateRetryDelay(error.retryCount)

    await new Promise(resolve => setTimeout(resolve, delay))

    try {
      return await operation()
    } catch (retryError) {
      const newError = this.detectError(retryError, error.originalRequest)
      newError.retryCount = error.retryCount
      return this.retryWithBackoff(operation, newError)
    }
  }

  public addToRetryQueue(key: string, operation: () => Promise<any>): void {
    this.retryQueue.set(key, operation)
  }

  public removeFromRetryQueue(key: string): void {
    this.retryQueue.delete(key)
  }

  private async processRetryQueue(): Promise<void> {
    if (!navigator.onLine || this.retryQueue.size === 0) {
      return
    }

    const operations = Array.from(this.retryQueue.entries())
    this.retryQueue.clear()

    for (const [key, operation] of operations) {
      try {
        await operation()
      } catch (error) {
        console.warn(`Retry queue operation ${key} failed:`, error)
      }
    }
  }

  public getNetworkStatus(): NetworkStatus {
    // Update online status dynamically
    this.networkStatus.isOnline = navigator.onLine
    return { ...this.networkStatus }
  }

  public getErrorHistory(): NetworkError[] {
    return [...this.errorHistory]
  }

  public getErrorStats(): {
    totalErrors: number
    errorsByType: Record<string, number>
    averageRetryCount: number
    recentErrors: NetworkError[]
  } {
    const errorsByType: Record<string, number> = {}
    let totalRetries = 0

    this.errorHistory.forEach(error => {
      errorsByType[error.type] = (errorsByType[error.type] || 0) + 1
      totalRetries += error.retryCount
    })

    const recentErrors = this.errorHistory
      .filter(error => Date.now() - error.timestamp.getTime() < 300000) // Last 5 minutes
      .slice(-10)

    return {
      totalErrors: this.errorHistory.length,
      errorsByType,
      averageRetryCount: this.errorHistory.length > 0 ? totalRetries / this.errorHistory.length : 0,
      recentErrors
    }
  }

  public updateRetryConfig(config: Partial<RetryConfig>): void {
    this.retryConfig = { ...this.retryConfig, ...config }
  }

  public clearErrorHistory(): void {
    this.errorHistory.length = 0
  }
}

export const networkErrorDetector = new NetworkErrorDetector()
export default networkErrorDetector