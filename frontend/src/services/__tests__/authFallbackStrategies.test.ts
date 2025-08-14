import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { authFallbackStrategies } from '../authFallbackStrategies'
import { networkErrorDetector } from '../networkErrorDetector'

// Mock dependencies
vi.mock('../networkErrorDetector', () => ({
  networkErrorDetector: {
    shouldRetry: vi.fn(),
    detectError: vi.fn(),
    calculateRetryDelay: vi.fn(),
    getNetworkStatus: vi.fn()
  }
}))

// Mock auth store
const mockAuthStore = {
  user: { id: 1, name: 'Test User' },
  token: 'test-token',
  refreshToken: 'refresh-token',
  updateTokens: vi.fn(),
  restoreSession: vi.fn(),
  logout: vi.fn()
}

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => mockAuthStore
}))

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn()
}

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

// Mock fetch
global.fetch = vi.fn()

describe('AuthFallbackStrategies', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    authFallbackStrategies.clearExecutionHistory()
    
    // Reset network detector mocks
    ;(networkErrorDetector.shouldRetry as any).mockReturnValue(true)
    ;(networkErrorDetector.detectError as any).mockReturnValue({
      type: 'timeout',
      message: 'Network timeout',
      timestamp: new Date(),
      retryCount: 0
    })
    ;(networkErrorDetector.calculateRetryDelay as any).mockReturnValue(1000)
    ;(networkErrorDetector.getNetworkStatus as any).mockReturnValue({
      isOnline: true,
      connectionType: 'wifi',
      effectiveType: '4g'
    })
  })

  afterEach(() => {
    vi.clearAllTimers()
  })

  describe('Strategy Registration and Selection', () => {
    it('should register custom fallback strategy', () => {
      const customStrategy = {
        name: 'custom_test',
        priority: 1,
        condition: () => true,
        execute: async () => ({ success: true, strategy: 'custom_test', action: 'retry' as const }),
        description: 'Test strategy'
      }

      authFallbackStrategies.registerStrategy(customStrategy)

      const strategies = authFallbackStrategies.getStrategies()
      expect(strategies.some(s => s.name === 'custom_test')).toBe(true)
    })

    it('should sort strategies by priority', () => {
      const strategies = authFallbackStrategies.getStrategies()
      
      for (let i = 1; i < strategies.length; i++) {
        expect(strategies[i].priority).toBeGreaterThanOrEqual(strategies[i - 1].priority)
      }
    })

    it('should select first applicable strategy', async () => {
      const error = { response: { status: 500 } }
      const context = {
        operation: 'login' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.success).toBe(false)
      expect(result.strategy).toBe('network_retry')
      expect(result.action).toBe('retry')
    })
  })

  describe('Network Retry Strategy', () => {
    it('should execute network retry for retryable errors', async () => {
      const error = { code: 'ECONNABORTED', message: 'timeout' }
      const context = {
        operation: 'login' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      ;(networkErrorDetector.shouldRetry as any).mockReturnValue(true)
      ;(networkErrorDetector.calculateRetryDelay as any).mockReturnValue(2000)

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.success).toBe(false)
      expect(result.strategy).toBe('network_retry')
      expect(result.action).toBe('retry')
      expect(result.nextAttemptDelay).toBe(2000)
      expect(result.message).toContain('Retrying in 2 seconds')
    })

    it('should not retry when max attempts exceeded', async () => {
      const error = { code: 'ECONNABORTED', message: 'timeout' }
      const context = {
        operation: 'login' as const,
        attemptCount: 5, // Exceeds max retries
        userAgent: 'test',
        timestamp: new Date()
      }

      ;(networkErrorDetector.shouldRetry as any).mockReturnValue(false)

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.strategy).not.toBe('network_retry')
    })
  })

  describe('Offline Mode Strategy', () => {
    it('should use cached session when offline', async () => {
      const error = { message: 'Network unavailable' }
      const context = {
        operation: 'validate' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      const cachedSession = {
        user: { id: 1, name: 'Cached User' },
        token: 'cached-token',
        expiresAt: new Date(Date.now() + 3600000).toISOString()
      }

      ;(networkErrorDetector.getNetworkStatus as any).mockReturnValue({
        isOnline: false
      })

      localStorageMock.getItem.mockReturnValue(JSON.stringify(cachedSession))

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.success).toBe(true)
      expect(result.strategy).toBe('offline_mode')
      expect(result.action).toBe('cache')
      expect(result.data).toEqual(cachedSession)
    })

    it('should fail when no valid cached session available offline', async () => {
      const error = { message: 'Network unavailable' }
      const context = {
        operation: 'validate' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      ;(networkErrorDetector.getNetworkStatus as any).mockReturnValue({
        isOnline: false
      })

      localStorageMock.getItem.mockReturnValue(null) // No cached session

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.success).toBe(false)
      expect(result.strategy).toBe('offline_mode')
      expect(result.action).toBe('offline')
    })

    it('should not use offline mode for login operations', async () => {
      const error = { message: 'Network unavailable' }
      const context = {
        operation: 'login' as const, // Login operation
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      ;(networkErrorDetector.getNetworkStatus as any).mockReturnValue({
        isOnline: false
      })

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.strategy).not.toBe('offline_mode')
    })
  })

  describe('Token Refresh Strategy', () => {
    it('should refresh token for 401 errors', async () => {
      const error = { response: { status: 401 } }
      const context = {
        operation: 'validate' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      ;(global.fetch as any).mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          access_token: 'new-token',
          refresh_token: 'new-refresh-token',
          expires_at: new Date(Date.now() + 3600000).toISOString()
        })
      })

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.success).toBe(true)
      expect(result.strategy).toBe('token_refresh')
      expect(result.action).toBe('retry')
      expect(mockAuthStore.updateTokens).toHaveBeenCalled()
    })

    it('should fail when refresh token is not available', async () => {
      const error = { response: { status: 401 } }
      const context = {
        operation: 'validate' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      // No refresh token available
      mockAuthStore.refreshToken = null
      localStorageMock.getItem.mockReturnValue(null)

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.strategy).not.toBe('token_refresh')
    })

    it('should handle refresh token failure', async () => {
      const error = { response: { status: 401 } }
      const context = {
        operation: 'validate' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      ;(global.fetch as any).mockResolvedValueOnce({
        ok: false,
        status: 401
      })

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.success).toBe(true) // Strategy executes but refresh fails
      expect(result.strategy).toBe('token_refresh')
      expect(result.action).toBe('redirect')
    })

    it('should not refresh for refresh operations', async () => {
      const error = { response: { status: 401 } }
      const context = {
        operation: 'refresh' as const, // Already a refresh operation
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.strategy).not.toBe('token_refresh')
    })
  })

  describe('Session Recovery Strategy', () => {
    it('should recover session from backup', async () => {
      const error = { response: { status: 401 } }
      const context = {
        operation: 'validate' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      const backupSession = {
        token: 'backup-token',
        sessionId: 'backup-session',
        userId: 1,
        expiresAt: new Date(Date.now() + 3600000).toISOString()
      }

      localStorageMock.getItem.mockReturnValue(JSON.stringify(backupSession))

      ;(global.fetch as any).mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ valid: true })
      })

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.success).toBe(true)
      expect(result.strategy).toBe('session_recovery')
      expect(result.action).toBe('retry')
      expect(mockAuthStore.restoreSession).toHaveBeenCalledWith(backupSession)
    })

    it('should fail when no recoverable session exists', async () => {
      const error = { response: { status: 401 } }
      const context = {
        operation: 'validate' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      localStorageMock.getItem.mockReturnValue(null) // No backup

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.strategy).not.toBe('session_recovery')
    })
  })

  describe('Graceful Degradation Strategy', () => {
    it('should enable limited mode for server errors after max retries', async () => {
      const error = { response: { status: 500 } }
      const context = {
        operation: 'login' as const,
        attemptCount: 5, // Exceeds retry limit
        userAgent: 'test',
        timestamp: new Date()
      }

      ;(networkErrorDetector.shouldRetry as any).mockReturnValue(false)

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.success).toBe(true)
      expect(result.strategy).toBe('graceful_degradation')
      expect(result.action).toBe('cache')
      expect(result.data.limited).toBe(true)
    })
  })

  describe('Manual Intervention Strategy', () => {
    it('should require manual intervention for persistent failures', async () => {
      const error = { response: { status: 500 } }
      const context = {
        operation: 'login' as const,
        attemptCount: 10, // Many attempts
        userAgent: 'test',
        timestamp: new Date()
      }

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.success).toBe(false)
      expect(result.strategy).toBe('manual_intervention')
      expect(result.action).toBe('manual')
      expect(result.message).toContain('Manual intervention required')
    })

    it('should require manual intervention for 403 login errors', async () => {
      const error = { response: { status: 403 } }
      const context = {
        operation: 'login' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result.success).toBe(false)
      expect(result.strategy).toBe('manual_intervention')
      expect(result.action).toBe('manual')
    })
  })

  describe('Execution History and Statistics', () => {
    it('should maintain execution history', async () => {
      const error = { code: 'ECONNABORTED' }
      const context = {
        operation: 'login' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      await authFallbackStrategies.executeFallback(error, context)

      const history = authFallbackStrategies.getExecutionHistory()
      expect(history).toHaveLength(1)
      expect(history[0].strategy).toBe('network_retry')
      expect(history[0].context).toEqual(context)
    })

    it('should provide execution statistics', async () => {
      // Execute multiple fallbacks
      const errors = [
        { code: 'ECONNABORTED' },
        { response: { status: 401 } },
        { response: { status: 500 } }
      ]

      for (const error of errors) {
        await authFallbackStrategies.executeFallback(error, {
          operation: 'validate' as const,
          attemptCount: 1,
          userAgent: 'test',
          timestamp: new Date()
        })
      }

      const stats = authFallbackStrategies.getExecutionStats()

      expect(stats.totalExecutions).toBe(3)
      expect(stats.successRate).toBeGreaterThanOrEqual(0)
      expect(stats.strategyCounts).toHaveProperty('network_retry')
      expect(stats.averageDuration).toBeGreaterThan(0)
    })

    it('should handle empty execution history', () => {
      const stats = authFallbackStrategies.getExecutionStats()

      expect(stats.totalExecutions).toBe(0)
      expect(stats.successRate).toBe(0)
      expect(stats.strategyCounts).toEqual({})
      expect(stats.averageDuration).toBe(0)
    })

    it('should clear execution history', async () => {
      await authFallbackStrategies.executeFallback({ code: 'test' }, {
        operation: 'login' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      })

      expect(authFallbackStrategies.getExecutionHistory()).toHaveLength(1)

      authFallbackStrategies.clearExecutionHistory()
      expect(authFallbackStrategies.getExecutionHistory()).toHaveLength(0)
    })
  })

  describe('Concurrent Execution Prevention', () => {
    it('should prevent concurrent fallback executions', async () => {
      const error = { code: 'ECONNABORTED' }
      const context = {
        operation: 'login' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      // Start two executions simultaneously
      const promise1 = authFallbackStrategies.executeFallback(error, context)
      const promise2 = authFallbackStrategies.executeFallback(error, context)

      const [result1, result2] = await Promise.all([promise1, promise2])

      // One should succeed, one should be rejected as busy
      const busyResult = result1.strategy === 'busy' ? result1 : result2
      expect(busyResult.strategy).toBe('busy')
      expect(busyResult.success).toBe(false)
    })
  })

  describe('Real-world Scenarios', () => {
    it('should handle network timeout during login', async () => {
      const timeoutError = {
        code: 'ECONNABORTED',
        message: 'timeout of 5000ms exceeded'
      }

      const context = {
        operation: 'login' as const,
        attemptCount: 1,
        userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        timestamp: new Date()
      }

      const result = await authFallbackStrategies.executeFallback(timeoutError, context)

      expect(result.strategy).toBe('network_retry')
      expect(result.action).toBe('retry')
      expect(result.nextAttemptDelay).toBeGreaterThan(0)
    })

    it('should handle server maintenance (503 error)', async () => {
      const maintenanceError = {
        response: { status: 503 },
        message: 'Service Temporarily Unavailable'
      }

      const context = {
        operation: 'validate' as const,
        attemptCount: 4, // Multiple attempts
        userAgent: 'test',
        timestamp: new Date()
      }

      ;(networkErrorDetector.shouldRetry as any).mockReturnValue(false) // Max retries exceeded

      const result = await authFallbackStrategies.executeFallback(maintenanceError, context)

      expect(result.strategy).toBe('graceful_degradation')
      expect(result.success).toBe(true)
      expect(result.data.limited).toBe(true)
    })

    it('should handle expired session with successful refresh', async () => {
      const expiredError = {
        response: { status: 401 },
        message: 'Token expired'
      }

      const context = {
        operation: 'validate' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      ;(global.fetch as any).mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          access_token: 'refreshed-token',
          refresh_token: 'new-refresh-token',
          expires_at: new Date(Date.now() + 3600000).toISOString()
        })
      })

      const result = await authFallbackStrategies.executeFallback(expiredError, context)

      expect(result.strategy).toBe('token_refresh')
      expect(result.success).toBe(true)
      expect(result.action).toBe('retry')
    })

    it('should handle complete network disconnection', async () => {
      const networkError = {
        message: 'Failed to fetch'
      }

      const context = {
        operation: 'validate' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      ;(networkErrorDetector.getNetworkStatus as any).mockReturnValue({
        isOnline: false
      })

      const cachedSession = {
        user: { id: 1, name: 'User' },
        token: 'cached-token',
        expiresAt: new Date(Date.now() + 3600000).toISOString()
      }

      localStorageMock.getItem.mockReturnValue(JSON.stringify(cachedSession))

      const result = await authFallbackStrategies.executeFallback(networkError, context)

      expect(result.strategy).toBe('offline_mode')
      expect(result.success).toBe(true)
      expect(result.action).toBe('cache')
    })
  })
})