// Auth fallback strategies - uses auth store when needed
import { useAuthStore } from '@/stores/auth'
import { networkErrorDetector } from './networkErrorDetector'
import { sessionConflictResolver } from './sessionConflictResolver'

export interface FallbackStrategy {
  name: string
  priority: number
  condition: (error: any, context: AuthContext) => boolean
  execute: (error: any, context: AuthContext) => Promise<FallbackResult>
  description: string
}

export interface AuthContext {
  operation: 'login' | 'logout' | 'refresh' | 'validate' | 'register'
  attemptCount: number
  lastError?: any
  userAgent: string
  timestamp: Date
  sessionData?: any
}

export interface FallbackResult {
  success: boolean
  strategy: string
  action: 'retry' | 'redirect' | 'cache' | 'offline' | 'manual'
  data?: any
  message?: string
  nextAttemptDelay?: number
}

export interface FallbackExecution {
  id: string
  strategy: string
  context: AuthContext
  result: FallbackResult
  timestamp: Date
  duration: number
}

class AuthFallbackStrategies {
  private strategies: FallbackStrategy[] = []
  private executions = new Map<string, FallbackExecution>()
  private isExecuting = false

  constructor() {
    this.initializeStrategies()
  }

  private initializeStrategies(): void {
    // Network-based fallback strategies
    this.registerStrategy({
      name: 'network_retry',
      priority: 1,
      condition: (error, context) => {
        return networkErrorDetector.shouldRetry(
          networkErrorDetector.detectError(error),
          error.response
        ) && context.attemptCount < 3
      },
      execute: async (error, context) => {
        const networkError = networkErrorDetector.detectError(error)
        const delay = networkErrorDetector.calculateRetryDelay(context.attemptCount)
        
        return {
          success: false,
          strategy: 'network_retry',
          action: 'retry',
          message: `Retrying in ${Math.round(delay / 1000)} seconds due to network error`,
          nextAttemptDelay: delay
        }
      },
      description: 'Retry authentication with exponential backoff for network errors'
    })

    // Offline mode fallback
    this.registerStrategy({
      name: 'offline_mode',
      priority: 2,
      condition: (error, context) => {
        const networkStatus = networkErrorDetector.getNetworkStatus()
        return !networkStatus.isOnline && context.operation !== 'login'
      },
      execute: async (error, context) => {
        const cachedSession = this.getCachedSession()
        
        if (cachedSession && this.isSessionValid(cachedSession)) {
          return {
            success: true,
            strategy: 'offline_mode',
            action: 'cache',
            data: cachedSession,
            message: 'Using cached session data (offline mode)'
          }
        }

        return {
          success: false,
          strategy: 'offline_mode',
          action: 'offline',
          message: 'No valid cached session available for offline use'
        }
      },
      description: 'Use cached session data when offline'
    })

    // Token refresh fallback
    this.registerStrategy({
      name: 'token_refresh',
      priority: 3,
      condition: (error, context) => {
        return error.response?.status === 401 && 
               context.operation !== 'refresh' &&
               this.hasRefreshToken()
      },
      execute: async (error, context) => {
        try {
          const refreshResult = await this.attemptTokenRefresh()
          
          if (refreshResult.success) {
            return {
              success: true,
              strategy: 'token_refresh',
              action: 'retry',
              data: refreshResult.data,
              message: 'Token refreshed successfully'
            }
          }
        } catch (refreshError) {
          console.warn('Token refresh failed:', refreshError)
        }

        return {
          success: false,
          strategy: 'token_refresh',
          action: 'redirect',
          message: 'Token refresh failed, redirecting to login'
        }
      },
      description: 'Attempt to refresh expired tokens automatically'
    })

    // Session recovery fallback
    this.registerStrategy({
      name: 'session_recovery',
      priority: 4,
      condition: (error, context) => {
        return error.response?.status === 401 && 
               context.operation === 'validate' &&
               this.hasRecoverableSession()
      },
      execute: async (error, context) => {
        try {
          const recoveryResult = await this.attemptSessionRecovery()
          
          if (recoveryResult.success) {
            return {
              success: true,
              strategy: 'session_recovery',
              action: 'retry',
              data: recoveryResult.data,
              message: 'Session recovered from backup'
            }
          }
        } catch (recoveryError) {
          console.warn('Session recovery failed:', recoveryError)
        }

        return {
          success: false,
          strategy: 'session_recovery',
          action: 'redirect',
          message: 'Session recovery failed'
        }
      },
      description: 'Recover session from backup storage'
    })

    // Graceful degradation fallback
    this.registerStrategy({
      name: 'graceful_degradation',
      priority: 5,
      condition: (error, context) => {
        return error.response?.status >= 500 && context.attemptCount >= 3
      },
      execute: async (error, context) => {
        // Enable limited functionality mode
        const limitedSession = this.createLimitedSession()
        
        return {
          success: true,
          strategy: 'graceful_degradation',
          action: 'cache',
          data: limitedSession,
          message: 'Server unavailable, running in limited mode'
        }
      },
      description: 'Enable limited functionality when server is unavailable'
    })

    // Manual intervention fallback
    this.registerStrategy({
      name: 'manual_intervention',
      priority: 10,
      condition: (error, context) => {
        return context.attemptCount >= 5 || 
               (error.response?.status === 403 && context.operation === 'login')
      },
      execute: async (error, context) => {
        return {
          success: false,
          strategy: 'manual_intervention',
          action: 'manual',
          message: 'Manual intervention required. Please contact support or try again later.'
        }
      },
      description: 'Require manual intervention for persistent failures'
    })

    // Sort strategies by priority
    this.strategies.sort((a, b) => a.priority - b.priority)
  }

  public registerStrategy(strategy: FallbackStrategy): void {
    this.strategies.push(strategy)
    this.strategies.sort((a, b) => a.priority - b.priority)
  }

  public async executeFallback(error: any, context: AuthContext): Promise<FallbackResult> {
    if (this.isExecuting) {
      return {
        success: false,
        strategy: 'busy',
        action: 'retry',
        message: 'Another fallback is currently executing'
      }
    }

    this.isExecuting = true
    const executionId = `fallback_${Date.now()}`
    const startTime = Date.now()

    try {
      // Find the first applicable strategy
      const applicableStrategy = this.strategies.find(strategy => 
        strategy.condition(error, context)
      )

      if (!applicableStrategy) {
        const result: FallbackResult = {
          success: false,
          strategy: 'none_applicable',
          action: 'manual',
          message: 'No applicable fallback strategy found'
        }

        this.recordExecution(executionId, 'none_applicable', context, result, startTime)
        return result
      }

      // Execute the strategy
      const result = await applicableStrategy.execute(error, context)
      this.recordExecution(executionId, applicableStrategy.name, context, result, startTime)

      return result

    } catch (executionError) {
      console.error('Fallback execution failed:', executionError)
      
      const result: FallbackResult = {
        success: false,
        strategy: 'execution_error',
        action: 'manual',
        message: `Fallback execution failed: ${executionError instanceof Error ? executionError.message : 'Unknown error'}`
      }

      this.recordExecution(executionId, 'execution_error', context, result, startTime)
      return result

    } finally {
      this.isExecuting = false
    }
  }

  private recordExecution(
    id: string,
    strategy: string,
    context: AuthContext,
    result: FallbackResult,
    startTime: number
  ): void {
    const execution: FallbackExecution = {
      id,
      strategy,
      context: { ...context },
      result: { ...result },
      timestamp: new Date(),
      duration: Date.now() - startTime
    }

    this.executions.set(id, execution)

    // Keep only last 50 executions
    if (this.executions.size > 50) {
      const oldestKey = Array.from(this.executions.keys())[0]
      this.executions.delete(oldestKey)
    }
  }

  private getCachedSession(): any {
    try {
      const cached = localStorage.getItem('auth_session_backup')
      return cached ? JSON.parse(cached) : null
    } catch (error) {
      return null
    }
  }

  private isSessionValid(session: any): boolean {
    if (!session || !session.expiresAt) {
      return false
    }

    const expiryTime = new Date(session.expiresAt).getTime()
    const now = Date.now()
    
    // Consider valid if expires more than 5 minutes from now
    return expiryTime > (now + 300000)
  }

  private hasRefreshToken(): boolean {
    const authStore = useAuthStore()
    return !!authStore.refreshToken || !!localStorage.getItem('refresh_token')
  }

  private async attemptTokenRefresh(): Promise<{ success: boolean; data?: any }> {
    const authStore = useAuthStore()
    const refreshToken = authStore.refreshToken || localStorage.getItem('refresh_token')

    if (!refreshToken) {
      return { success: false }
    }

    try {
      const response = await fetch('/api/auth/refresh', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${refreshToken}`
        }
      })

      if (response.ok) {
        const data = await response.json()
        
        // Update auth store with new tokens
        if ((authStore as any).updateTokens) {
          await (authStore as any).updateTokens({
            token: data.access_token,
            refreshToken: data.refresh_token,
            expiresAt: data.expires_at
          })
        }

        return { success: true, data }
      }

      return { success: false }
    } catch (error) {
      console.error('Token refresh request failed:', error)
      return { success: false }
    }
  }

  private hasRecoverableSession(): boolean {
    const backup = localStorage.getItem('auth_session_backup')
    return !!backup
  }

  private async attemptSessionRecovery(): Promise<{ success: boolean; data?: any }> {
    try {
      const backup = this.getCachedSession()
      
      if (!backup || !this.isSessionValid(backup)) {
        return { success: false }
      }

      // Validate the backup session with the server
      const response = await fetch('/api/auth/validate-session', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${backup.token}`
        },
        body: JSON.stringify({
          sessionId: backup.sessionId,
          userId: backup.userId
        })
      })

      if (response.ok) {
        const authStore = useAuthStore()
        if ((authStore as any).restoreSession) {
          await (authStore as any).restoreSession(backup)
        }
        return { success: true, data: backup }
      }

      return { success: false }
    } catch (error) {
      console.error('Session recovery failed:', error)
      return { success: false }
    }
  }

  private createLimitedSession(): any {
    return {
      user: {
        id: 'limited',
        name: 'Limited User',
        email: 'limited@local'
      },
      token: 'limited_access_token',
      limited: true,
      expiresAt: new Date(Date.now() + 3600000).toISOString() // 1 hour
    }
  }

  public getStrategies(): FallbackStrategy[] {
    return [...this.strategies]
  }

  public getExecutionHistory(): FallbackExecution[] {
    return Array.from(this.executions.values())
  }

  public getExecutionStats(): {
    totalExecutions: number
    successRate: number
    strategyCounts: Record<string, number>
    averageDuration: number
  } {
    const executions = Array.from(this.executions.values())
    const totalExecutions = executions.length
    
    if (totalExecutions === 0) {
      return {
        totalExecutions: 0,
        successRate: 0,
        strategyCounts: {},
        averageDuration: 0
      }
    }

    const successfulExecutions = executions.filter(e => e.result.success).length
    const successRate = (successfulExecutions / totalExecutions) * 100

    const strategyCounts: Record<string, number> = {}
    let totalDuration = 0

    executions.forEach(execution => {
      strategyCounts[execution.strategy] = (strategyCounts[execution.strategy] || 0) + 1
      totalDuration += execution.duration
    })

    return {
      totalExecutions,
      successRate,
      strategyCounts,
      averageDuration: totalDuration / totalExecutions
    }
  }

  public clearExecutionHistory(): void {
    this.executions.clear()
  }
}

export const authFallbackStrategies = new AuthFallbackStrategies()
export default authFallbackStrategies