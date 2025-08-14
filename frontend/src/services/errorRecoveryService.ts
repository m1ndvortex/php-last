// Error recovery service - coordinates all recovery mechanisms
import { networkErrorDetector, type NetworkError } from './networkErrorDetector'
import { sessionConflictResolver, type SessionConflict } from './sessionConflictResolver'
import { cacheCorruptionDetector, type CorruptionReport } from './cacheCorruptionDetector'
import { authFallbackStrategies, type AuthContext, type FallbackResult } from './authFallbackStrategies'

export interface RecoveryOperation {
  id: string
  type: 'network' | 'session' | 'cache' | 'auth'
  status: 'pending' | 'in_progress' | 'completed' | 'failed'
  startTime: Date
  endTime?: Date
  error?: any
  result?: any
  retryCount: number
  maxRetries: number
}

export interface RecoveryStats {
  totalOperations: number
  successfulRecoveries: number
  failedRecoveries: number
  averageRecoveryTime: number
  recoveryByType: Record<string, number>
  recentOperations: RecoveryOperation[]
}

export interface ErrorRecoveryConfig {
  enableAutoRecovery: boolean
  maxRetries: number
  retryDelay: number
  enableNotifications: boolean
  logErrors: boolean
  enableFallbacks: boolean
}

class ErrorRecoveryService {
  private operations = new Map<string, RecoveryOperation>()
  private isInitialized = false
  
  private config: ErrorRecoveryConfig = {
    enableAutoRecovery: true,
    maxRetries: 3,
    retryDelay: 1000,
    enableNotifications: true,
    logErrors: true,
    enableFallbacks: true
  }

  constructor() {
    this.initialize()
  }

  private async initialize(): Promise<void> {
    if (this.isInitialized) {
      return
    }

    try {
      // Initialize all recovery components
      await this.initializeNetworkRecovery()
      await this.initializeSessionRecovery()
      await this.initializeCacheRecovery()
      await this.initializeAuthRecovery()

      this.isInitialized = true
      console.log('Error Recovery Service initialized successfully')
    } catch (error) {
      console.error('Failed to initialize Error Recovery Service:', error)
      throw error
    }
  }

  private async initializeNetworkRecovery(): Promise<void> {
    // Network recovery is handled by networkErrorDetector
    // We just need to monitor for network errors and coordinate recovery
    
    // Listen for network status changes
    window.addEventListener('online', () => {
      this.handleNetworkRecovery()
    })

    window.addEventListener('offline', () => {
      this.handleNetworkFailure()
    })
  }

  private async initializeSessionRecovery(): Promise<void> {
    // Session recovery is handled by sessionConflictResolver
    // Monitor for session conflicts and coordinate resolution
    
    // Subscribe to conflict notifications
    const checkConflicts = () => {
      const conflicts = sessionConflictResolver.getActiveConflicts()
      conflicts.forEach(conflict => {
        if (!conflict.resolved) {
          this.handleSessionConflict(conflict)
        }
      })
    }

    // Check for conflicts periodically
    setInterval(checkConflicts, 5000)
  }

  private async initializeCacheRecovery(): Promise<void> {
    // Cache recovery is handled by cacheCorruptionDetector
    // Monitor for cache corruption and coordinate cleanup
    
    // Perform initial health scan
    await cacheCorruptionDetector.performHealthScan()
    
    // Monitor cache health periodically
    setInterval(async () => {
      const health = await cacheCorruptionDetector.performHealthScan()
      
      if (health.healthPercentage < 80) {
        await this.handleCacheCorruption()
      }
    }, 300000) // Every 5 minutes
  }

  private async initializeAuthRecovery(): Promise<void> {
    // Auth recovery is handled by authFallbackStrategies
    // We coordinate the fallback execution
    
    // Monitor authentication errors globally
    this.setupGlobalErrorHandling()
  }

  private setupGlobalErrorHandling(): void {
    // Intercept fetch requests to handle auth errors
    const originalFetch = window.fetch
    
    window.fetch = async (...args) => {
      try {
        const response = await originalFetch(...args)
        
        if (response.status === 401 || response.status === 403) {
          await this.handleAuthError(response, args)
        }
        
        return response
      } catch (error) {
        await this.handleNetworkError(error, args)
        throw error
      }
    }

    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
      if (this.isAuthError(event.reason)) {
        this.handleAuthError(event.reason)
      } else if (this.isNetworkError(event.reason)) {
        this.handleNetworkError(event.reason)
      }
    })
  }

  public async recoverFromError(error: any, context?: any): Promise<RecoveryOperation> {
    const operationId = `recovery_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    
    const operation: RecoveryOperation = {
      id: operationId,
      type: this.classifyError(error),
      status: 'pending',
      startTime: new Date(),
      error,
      retryCount: 0,
      maxRetries: this.config.maxRetries
    }

    this.operations.set(operationId, operation)

    try {
      operation.status = 'in_progress'
      
      switch (operation.type) {
        case 'network':
          operation.result = await this.executeNetworkRecovery(error, context)
          break
        case 'session':
          operation.result = await this.executeSessionRecovery(error, context)
          break
        case 'cache':
          operation.result = await this.executeCacheRecovery(error, context)
          break
        case 'auth':
          operation.result = await this.executeAuthRecovery(error, context)
          break
        default:
          throw new Error(`Unknown error type: ${operation.type}`)
      }

      operation.status = 'completed'
      operation.endTime = new Date()

      if (this.config.logErrors) {
        console.log(`Recovery completed for ${operation.type} error:`, operation.result)
      }

    } catch (recoveryError) {
      operation.status = 'failed'
      operation.endTime = new Date()
      operation.error = recoveryError

      if (this.config.logErrors) {
        console.error(`Recovery failed for ${operation.type} error:`, recoveryError)
      }

      // Attempt retry if within limits
      if (operation.retryCount < operation.maxRetries && this.config.enableAutoRecovery) {
        operation.retryCount++
        
        setTimeout(() => {
          this.retryRecovery(operation)
        }, this.config.retryDelay * Math.pow(2, operation.retryCount))
      }
    }

    return operation
  }

  private classifyError(error: any): RecoveryOperation['type'] {
    if (this.isNetworkError(error)) {
      return 'network'
    }
    
    if (this.isAuthError(error)) {
      return 'auth'
    }
    
    if (this.isSessionError(error)) {
      return 'session'
    }
    
    if (this.isCacheError(error)) {
      return 'cache'
    }

    // Default to network for unknown errors
    return 'network'
  }

  private isNetworkError(error: any): boolean {
    return error && (
      error.code === 'NETWORK_ERROR' ||
      error.message?.includes('fetch') ||
      error.message?.includes('network') ||
      !navigator.onLine
    )
  }

  private isAuthError(error: any): boolean {
    return error && (
      error.response?.status === 401 ||
      error.response?.status === 403 ||
      error.message?.includes('authentication') ||
      error.message?.includes('unauthorized')
    )
  }

  private isSessionError(error: any): boolean {
    return error && (
      error.message?.includes('session') ||
      error.type === 'session_conflict' ||
      error.code === 'SESSION_EXPIRED'
    )
  }

  private isCacheError(error: any): boolean {
    return error && (
      error.message?.includes('cache') ||
      error.message?.includes('storage') ||
      error.name === 'QuotaExceededError'
    )
  }

  private async executeNetworkRecovery(error: any, context?: any): Promise<any> {
    const networkError = networkErrorDetector.detectError(error)
    
    if (networkErrorDetector.shouldRetry(networkError)) {
      return await networkErrorDetector.retryWithBackoff(
        () => this.retryOriginalOperation(context),
        networkError
      )
    }

    // Add to retry queue for when network comes back
    if (context?.operation) {
      networkErrorDetector.addToRetryQueue(
        `recovery_${Date.now()}`,
        () => this.retryOriginalOperation(context)
      )
    }

    return { recovered: false, queued: true }
  }

  private async executeSessionRecovery(error: any, context?: any): Promise<any> {
    const conflicts = sessionConflictResolver.getActiveConflicts()
    
    if (conflicts.length > 0) {
      // Let the conflict resolver handle it
      const conflict = conflicts[0]
      
      return new Promise((resolve) => {
        sessionConflictResolver.onConflictResolved(conflict.id, (resolution) => {
          resolve({ recovered: true, resolution })
        })
      })
    }

    return { recovered: false, reason: 'No active conflicts to resolve' }
  }

  private async executeCacheRecovery(error: any, context?: any): Promise<any> {
    // Force cache cleanup
    await cacheCorruptionDetector.forceCacheCleanup()
    
    // Perform health scan
    const health = await cacheCorruptionDetector.performHealthScan()
    
    return {
      recovered: health.healthPercentage > 80,
      healthPercentage: health.healthPercentage,
      corruptedEntries: health.corruptedEntries
    }
  }

  private async executeAuthRecovery(error: any, context?: any): Promise<any> {
    const authContext: AuthContext = {
      operation: context?.operation || 'validate',
      attemptCount: context?.attemptCount || 0,
      lastError: error,
      userAgent: navigator.userAgent,
      timestamp: new Date(),
      sessionData: context?.sessionData
    }

    const fallbackResult = await authFallbackStrategies.executeFallback(error, authContext)
    
    return {
      recovered: fallbackResult.success,
      strategy: fallbackResult.strategy,
      action: fallbackResult.action,
      message: fallbackResult.message,
      data: fallbackResult.data
    }
  }

  private async retryOriginalOperation(context: any): Promise<any> {
    if (!context?.operation) {
      throw new Error('No operation to retry')
    }

    // This would be implemented based on the specific operation type
    // For now, we'll just return a placeholder
    return { retried: true, context }
  }

  private async retryRecovery(operation: RecoveryOperation): Promise<void> {
    try {
      operation.status = 'in_progress'
      
      const result = await this.executeRecoveryByType(operation.type, operation.error)
      
      operation.result = result
      operation.status = 'completed'
      operation.endTime = new Date()
      
    } catch (error) {
      operation.status = 'failed'
      operation.endTime = new Date()
      
      if (operation.retryCount < operation.maxRetries) {
        operation.retryCount++
        setTimeout(() => {
          this.retryRecovery(operation)
        }, this.config.retryDelay * Math.pow(2, operation.retryCount))
      }
    }
  }

  private async executeRecoveryByType(type: RecoveryOperation['type'], error: any): Promise<any> {
    switch (type) {
      case 'network':
        return this.executeNetworkRecovery(error)
      case 'session':
        return this.executeSessionRecovery(error)
      case 'cache':
        return this.executeCacheRecovery(error)
      case 'auth':
        return this.executeAuthRecovery(error)
      default:
        throw new Error(`Unknown recovery type: ${type}`)
    }
  }

  private async handleNetworkRecovery(): Promise<void> {
    console.log('Network recovered, processing retry queue')
    // Network error detector will handle the retry queue automatically
  }

  private async handleNetworkFailure(): Promise<void> {
    console.log('Network failure detected')
    // Enable offline mode or show notification
  }

  private async handleSessionConflict(conflict: SessionConflict): Promise<void> {
    if (this.config.enableAutoRecovery) {
      await this.recoverFromError(conflict, { type: 'session_conflict' })
    }
  }

  private async handleCacheCorruption(): Promise<void> {
    if (this.config.enableAutoRecovery) {
      await this.recoverFromError(
        new Error('Cache corruption detected'),
        { type: 'cache_corruption' }
      )
    }
  }

  private async handleAuthError(error: any, context?: any): Promise<void> {
    if (this.config.enableAutoRecovery) {
      await this.recoverFromError(error, { ...context, type: 'auth_error' })
    }
  }

  private async handleNetworkError(error: any, context?: any): Promise<void> {
    if (this.config.enableAutoRecovery) {
      await this.recoverFromError(error, { ...context, type: 'network_error' })
    }
  }

  public getOperations(): RecoveryOperation[] {
    return Array.from(this.operations.values())
  }

  public getOperation(id: string): RecoveryOperation | undefined {
    return this.operations.get(id)
  }

  public getRecoveryStats(): RecoveryStats {
    const operations = Array.from(this.operations.values())
    const totalOperations = operations.length
    
    if (totalOperations === 0) {
      return {
        totalOperations: 0,
        successfulRecoveries: 0,
        failedRecoveries: 0,
        averageRecoveryTime: 0,
        recoveryByType: {},
        recentOperations: []
      }
    }

    const successfulRecoveries = operations.filter(op => op.status === 'completed').length
    const failedRecoveries = operations.filter(op => op.status === 'failed').length
    
    const recoveryByType: Record<string, number> = {}
    let totalRecoveryTime = 0
    let completedOperations = 0

    operations.forEach(operation => {
      recoveryByType[operation.type] = (recoveryByType[operation.type] || 0) + 1
      
      if (operation.endTime) {
        totalRecoveryTime += operation.endTime.getTime() - operation.startTime.getTime()
        completedOperations++
      }
    })

    const recentOperations = operations
      .filter(op => Date.now() - op.startTime.getTime() < 3600000) // Last hour
      .slice(-10)

    return {
      totalOperations,
      successfulRecoveries,
      failedRecoveries,
      averageRecoveryTime: completedOperations > 0 ? totalRecoveryTime / completedOperations : 0,
      recoveryByType,
      recentOperations
    }
  }

  public updateConfig(newConfig: Partial<ErrorRecoveryConfig>): void {
    this.config = { ...this.config, ...newConfig }
  }

  public getConfig(): ErrorRecoveryConfig {
    return { ...this.config }
  }

  public clearOperationHistory(): void {
    this.operations.clear()
  }

  public async performHealthCheck(): Promise<{
    network: any
    session: any
    cache: any
    auth: any
    overall: 'healthy' | 'degraded' | 'critical'
  }> {
    const network = networkErrorDetector.getNetworkStatus()
    const session = sessionConflictResolver.getActiveConflicts()
    const cache = await cacheCorruptionDetector.performHealthScan()
    const auth = authFallbackStrategies.getExecutionStats()

    let overall: 'healthy' | 'degraded' | 'critical' = 'healthy'

    if (!network.isOnline || cache.healthPercentage < 50 || session.length > 5) {
      overall = 'critical'
    } else if (cache.healthPercentage < 80 || session.length > 0 || auth.successRate < 80) {
      overall = 'degraded'
    }

    return {
      network,
      session: { activeConflicts: session.length },
      cache,
      auth,
      overall
    }
  }
}

export const errorRecoveryService = new ErrorRecoveryService()
export default errorRecoveryService