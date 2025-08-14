import { describe, it, expect, beforeEach, vi } from 'vitest'
import { networkErrorDetector } from '../networkErrorDetector'
import { cacheCorruptionDetector } from '../cacheCorruptionDetector'
import { authFallbackStrategies } from '../authFallbackStrategies'
import { sessionConflictResolver } from '../sessionConflictResolver'
import { errorRecoveryService } from '../errorRecoveryService'

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn()
}

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

// Mock fetch
global.fetch = vi.fn()

describe('Error Recovery Basic Integration', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorageMock.getItem.mockReturnValue(null)
    ;(global.fetch as any).mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true })
    })
  })

  describe('Network Error Detection', () => {
    it('should detect and classify network errors', () => {
      const timeoutError = {
        code: 'ECONNABORTED',
        message: 'timeout of 5000ms exceeded'
      }

      const networkError = networkErrorDetector.detectError(timeoutError)

      expect(networkError.type).toBe('timeout')
      expect(networkError.message).toBe('timeout of 5000ms exceeded')
      expect(networkError.retryCount).toBe(0)
      expect(networkError.timestamp).toBeInstanceOf(Date)
    })

    it('should calculate retry delays with exponential backoff', () => {
      const delay1 = networkErrorDetector.calculateRetryDelay(0)
      const delay2 = networkErrorDetector.calculateRetryDelay(1)
      const delay3 = networkErrorDetector.calculateRetryDelay(2)

      expect(delay1).toBeGreaterThanOrEqual(1000)
      expect(delay2).toBeGreaterThanOrEqual(2000)
      expect(delay3).toBeGreaterThanOrEqual(4000)
    })

    it('should provide network status information', () => {
      const status = networkErrorDetector.getNetworkStatus()

      expect(status).toHaveProperty('isOnline')
      expect(status).toHaveProperty('connectionType')
      expect(status).toHaveProperty('effectiveType')
      expect(typeof status.isOnline).toBe('boolean')
    })
  })

  describe('Cache Corruption Detection', () => {
    it('should validate cache entry structure', async () => {
      const validEntry = {
        key: 'test-key',
        value: { data: 'test' },
        timestamp: Date.now(),
        ttl: 3600000,
        version: '1.0.0'
      }

      localStorageMock.getItem.mockReturnValue(JSON.stringify(validEntry))

      const isCorrupted = await cacheCorruptionDetector.validateCacheEntry('cache_test-key')
      expect(isCorrupted).toBe(false)
    })

    it('should detect invalid JSON format', async () => {
      localStorageMock.getItem.mockReturnValue('invalid json {')

      const isCorrupted = await cacheCorruptionDetector.validateCacheEntry('cache_invalid')
      expect(isCorrupted).toBe(true)

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports.length).toBeGreaterThan(0)
      expect(reports[reports.length - 1].type).toBe('invalid_format')
    })

    it('should provide cache health information', () => {
      const health = cacheCorruptionDetector.getCacheHealth()

      expect(health).toHaveProperty('totalEntries')
      expect(health).toHaveProperty('corruptedEntries')
      expect(health).toHaveProperty('healthPercentage')
      expect(health).toHaveProperty('lastScanTime')
    })
  })

  describe('Auth Fallback Strategies', () => {
    it('should register and sort strategies by priority', () => {
      const strategies = authFallbackStrategies.getStrategies()
      
      expect(strategies.length).toBeGreaterThan(0)
      
      // Check that strategies are sorted by priority
      for (let i = 1; i < strategies.length; i++) {
        expect(strategies[i].priority).toBeGreaterThanOrEqual(strategies[i - 1].priority)
      }
    })

    it('should execute fallback strategies', async () => {
      const error = { message: 'Test error' }
      const context = {
        operation: 'test' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      const result = await authFallbackStrategies.executeFallback(error, context)

      expect(result).toHaveProperty('success')
      expect(result).toHaveProperty('strategy')
      expect(result).toHaveProperty('action')
      expect(typeof result.success).toBe('boolean')
    })

    it('should maintain execution history', async () => {
      const error = { message: 'Test error' }
      const context = {
        operation: 'test' as const,
        attemptCount: 1,
        userAgent: 'test',
        timestamp: new Date()
      }

      await authFallbackStrategies.executeFallback(error, context)

      const history = authFallbackStrategies.getExecutionHistory()
      expect(history.length).toBeGreaterThan(0)
    })
  })

  describe('Session Conflict Resolution', () => {
    it('should provide conflict management interface', () => {
      const conflicts = sessionConflictResolver.getActiveConflicts()
      const notifications = sessionConflictResolver.getActiveNotifications()
      const history = sessionConflictResolver.getConflictHistory()

      expect(Array.isArray(conflicts)).toBe(true)
      expect(Array.isArray(notifications)).toBe(true)
      expect(Array.isArray(history)).toBe(true)
    })

    it('should handle notification dismissal', () => {
      // Add a test notification
      ;(sessionConflictResolver as any).notifications.set('test-notification', {
        id: 'test-notification',
        title: 'Test Notification',
        message: 'Test message',
        type: 'info',
        actions: []
      })

      sessionConflictResolver.dismissNotification('test-notification')

      const notifications = sessionConflictResolver.getActiveNotifications()
      expect(notifications.find(n => n.id === 'test-notification')).toBeUndefined()
    })

    it('should clear conflict history', () => {
      sessionConflictResolver.clearConflictHistory()
      
      const history = sessionConflictResolver.getConflictHistory()
      expect(history).toHaveLength(0)
    })
  })

  describe('Error Recovery Service Integration', () => {
    it('should coordinate error recovery operations', async () => {
      const error = { message: 'Integration test error' }
      
      const operation = await errorRecoveryService.recoverFromError(error)

      expect(operation).toHaveProperty('id')
      expect(operation).toHaveProperty('type')
      expect(operation).toHaveProperty('status')
      expect(operation).toHaveProperty('startTime')
      expect(operation.startTime).toBeInstanceOf(Date)
    })

    it('should provide recovery statistics', async () => {
      const error = { message: 'Stats test error' }
      await errorRecoveryService.recoverFromError(error)

      const stats = errorRecoveryService.getRecoveryStats()

      expect(stats).toHaveProperty('totalOperations')
      expect(stats).toHaveProperty('successfulRecoveries')
      expect(stats).toHaveProperty('failedRecoveries')
      expect(stats).toHaveProperty('averageRecoveryTime')
      expect(stats).toHaveProperty('recoveryByType')
      expect(stats.totalOperations).toBeGreaterThan(0)
    })

    it('should perform health checks', async () => {
      const health = await errorRecoveryService.performHealthCheck()

      expect(health).toHaveProperty('network')
      expect(health).toHaveProperty('session')
      expect(health).toHaveProperty('cache')
      expect(health).toHaveProperty('auth')
      expect(health).toHaveProperty('overall')
      expect(['healthy', 'degraded', 'critical']).toContain(health.overall)
    })

    it('should allow configuration updates', () => {
      const newConfig = {
        enableAutoRecovery: false,
        maxRetries: 5,
        retryDelay: 2000
      }

      errorRecoveryService.updateConfig(newConfig)

      const config = errorRecoveryService.getConfig()
      expect(config.enableAutoRecovery).toBe(false)
      expect(config.maxRetries).toBe(5)
      expect(config.retryDelay).toBe(2000)
    })

    it('should maintain operation history', async () => {
      const error1 = { message: 'History test error 1' }
      const error2 = { message: 'History test error 2' }

      await errorRecoveryService.recoverFromError(error1)
      await errorRecoveryService.recoverFromError(error2)

      const operations = errorRecoveryService.getOperations()
      expect(operations.length).toBeGreaterThanOrEqual(2)
    })

    it('should clear operation history', () => {
      errorRecoveryService.clearOperationHistory()
      
      const operations = errorRecoveryService.getOperations()
      expect(operations).toHaveLength(0)
    })
  })

  describe('Real Application Integration', () => {
    it('should handle real network errors', async () => {
      const networkError = {
        code: 'ECONNABORTED',
        message: 'timeout of 5000ms exceeded'
      }

      const operation = await errorRecoveryService.recoverFromError(networkError, {
        operation: 'api_call',
        url: '/api/test'
      })

      expect(operation.type).toBe('network')
      expect(['completed', 'failed', 'in_progress']).toContain(operation.status)
    })

    it('should handle authentication errors', async () => {
      const authError = {
        response: { status: 401 },
        message: 'Unauthorized'
      }

      const operation = await errorRecoveryService.recoverFromError(authError, {
        operation: 'validate'
      })

      expect(operation.type).toBe('auth')
      expect(['completed', 'failed', 'in_progress']).toContain(operation.status)
    })

    it('should handle cache errors', async () => {
      const cacheError = new Error('QuotaExceededError')
      cacheError.name = 'QuotaExceededError'

      const operation = await errorRecoveryService.recoverFromError(cacheError)

      expect(operation.type).toBe('cache')
      expect(['completed', 'failed', 'in_progress']).toContain(operation.status)
    })

    it('should work with real browser APIs', () => {
      // Test that the services can work with real browser APIs
      expect(typeof navigator.onLine).toBe('boolean')
      expect(typeof localStorage.getItem).toBe('function')
      expect(typeof window.fetch).toBe('function')
      
      // Test network status detection
      const networkStatus = networkErrorDetector.getNetworkStatus()
      expect(networkStatus.isOnline).toBe(navigator.onLine)
    })
  })
})