import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { networkErrorDetector } from '../networkErrorDetector'

// Mock navigator.onLine
Object.defineProperty(navigator, 'onLine', {
  writable: true,
  value: true
})

// Mock navigator.connection
Object.defineProperty(navigator, 'connection', {
  writable: true,
  value: {
    type: 'wifi',
    effectiveType: '4g',
    downlink: 10,
    rtt: 50,
    addEventListener: vi.fn()
  }
})

describe('NetworkErrorDetector', () => {
  beforeEach(() => {
    // Reset network status
    navigator.onLine = true
    networkErrorDetector.clearErrorHistory()
    
    // Clear any existing retry queue
    const retryQueue = (networkErrorDetector as any).retryQueue
    if (retryQueue) {
      retryQueue.clear()
    }
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  describe('Error Detection', () => {
    it('should detect timeout errors correctly', () => {
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

    it('should detect offline errors correctly', () => {
      // Set navigator.onLine to false before creating the detector
      Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: false
      })
      
      const offlineError = {
        message: 'Network request failed'
      }

      const networkError = networkErrorDetector.detectError(offlineError)

      expect(networkError.type).toBe('offline')
      
      // Reset navigator.onLine
      Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: true
      })
    })

    it('should detect server errors correctly', () => {
      const serverError = {
        response: { status: 500 },
        message: 'Internal Server Error'
      }

      const networkError = networkErrorDetector.detectError(serverError)

      expect(networkError.type).toBe('server_error')
    })

    it('should detect connection failed errors correctly', () => {
      const connectionError = {
        code: 'NETWORK_ERROR',
        message: 'Network Error'
      }

      const networkError = networkErrorDetector.detectError(connectionError)

      expect(networkError.type).toBe('connection_failed')
    })
  })

  describe('Retry Logic', () => {
    it('should allow retry for retryable errors within limit', () => {
      const networkError = {
        type: 'timeout' as const,
        message: 'Timeout error',
        timestamp: new Date(),
        retryCount: 1
      }

      const response = { status: 500 }
      const shouldRetry = networkErrorDetector.shouldRetry(networkError, response)

      expect(shouldRetry).toBe(true)
    })

    it('should not allow retry when max retries exceeded', () => {
      const networkError = {
        type: 'timeout' as const,
        message: 'Timeout error',
        timestamp: new Date(),
        retryCount: 5
      }

      const shouldRetry = networkErrorDetector.shouldRetry(networkError)

      expect(shouldRetry).toBe(false)
    })

    it('should not allow retry when offline', () => {
      // Set navigator.onLine to false
      Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: false
      })
      
      const networkError = {
        type: 'timeout' as const,
        message: 'Timeout error',
        timestamp: new Date(),
        retryCount: 1
      }

      const shouldRetry = networkErrorDetector.shouldRetry(networkError)

      expect(shouldRetry).toBe(false)
      
      // Reset navigator.onLine
      Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: true
      })
    })

    it('should not allow retry for non-retryable status codes', () => {
      const networkError = {
        type: 'server_error' as const,
        message: 'Client error',
        timestamp: new Date(),
        retryCount: 1
      }

      const response = { status: 400 } // Bad Request - not retryable

      const shouldRetry = networkErrorDetector.shouldRetry(networkError, response)

      expect(shouldRetry).toBe(false)
    })
  })

  describe('Retry Delay Calculation', () => {
    it('should calculate exponential backoff delay correctly', () => {
      const delay1 = networkErrorDetector.calculateRetryDelay(0)
      const delay2 = networkErrorDetector.calculateRetryDelay(1)
      const delay3 = networkErrorDetector.calculateRetryDelay(2)

      expect(delay1).toBeGreaterThanOrEqual(1000) // Base delay + jitter
      expect(delay1).toBeLessThan(2000)
      
      expect(delay2).toBeGreaterThanOrEqual(2000) // 2x base delay + jitter
      expect(delay2).toBeLessThan(3000)
      
      expect(delay3).toBeGreaterThanOrEqual(4000) // 4x base delay + jitter
      expect(delay3).toBeLessThan(5000)
    })

    it('should respect maximum delay limit', () => {
      const delay = networkErrorDetector.calculateRetryDelay(10) // Very high retry count

      expect(delay).toBeLessThanOrEqual(31000) // Max delay (30000) + max jitter (1000)
    })
  })

  describe('Retry with Backoff', () => {
    it('should successfully retry operation after delay', async () => {
      let attemptCount = 0
      const operation = vi.fn().mockImplementation(() => {
        attemptCount++
        if (attemptCount === 1) {
          throw new Error('First attempt fails')
        }
        return Promise.resolve('Success')
      })

      const networkError = {
        type: 'timeout' as const,
        message: 'Timeout error',
        timestamp: new Date(),
        retryCount: 0
      }

      // Mock setTimeout to execute immediately
      vi.spyOn(global, 'setTimeout').mockImplementation((fn: any) => {
        fn()
        return 1 as any
      })

      const result = await networkErrorDetector.retryWithBackoff(operation, networkError)

      expect(result).toBe('Success')
      expect(operation).toHaveBeenCalledTimes(2)
      expect(networkError.retryCount).toBe(1)

      vi.restoreAllMocks()
    })

    it('should throw error when max retries exceeded', async () => {
      const operation = vi.fn().mockRejectedValue(new Error('Always fails'))

      const networkError = {
        type: 'timeout' as const,
        message: 'Timeout error',
        timestamp: new Date(),
        retryCount: 3 // Already at max
      }

      await expect(
        networkErrorDetector.retryWithBackoff(operation, networkError)
      ).rejects.toThrow('Timeout error')

      expect(operation).not.toHaveBeenCalled()
    })
  })

  describe('Retry Queue', () => {
    it('should add operations to retry queue', () => {
      const operation = vi.fn().mockResolvedValue('Success')
      
      networkErrorDetector.addToRetryQueue('test-operation', operation)
      
      // Verify operation was added (we can't directly access the queue, so we test behavior)
      expect(operation).not.toHaveBeenCalled() // Should not execute immediately
    })

    it('should remove operations from retry queue', () => {
      const operation = vi.fn().mockResolvedValue('Success')
      
      networkErrorDetector.addToRetryQueue('test-operation', operation)
      networkErrorDetector.removeFromRetryQueue('test-operation')
      
      // Operation should be removed from queue
      expect(operation).not.toHaveBeenCalled()
    })

    it('should process retry queue when network comes online', async () => {
      const operation1 = vi.fn().mockResolvedValue('Success 1')
      const operation2 = vi.fn().mockResolvedValue('Success 2')
      
      // Add operations to queue while offline
      navigator.onLine = false
      networkErrorDetector.addToRetryQueue('op1', operation1)
      networkErrorDetector.addToRetryQueue('op2', operation2)
      
      // Simulate network coming back online
      navigator.onLine = true
      
      // Trigger online event
      const onlineEvent = new Event('online')
      window.dispatchEvent(onlineEvent)
      
      // Wait for async processing
      await new Promise(resolve => setTimeout(resolve, 10))
      
      expect(operation1).toHaveBeenCalled()
      expect(operation2).toHaveBeenCalled()
    })
  })

  describe('Network Status Monitoring', () => {
    it('should return current network status', () => {
      const status = networkErrorDetector.getNetworkStatus()

      expect(status).toHaveProperty('isOnline')
      expect(status).toHaveProperty('connectionType')
      expect(status).toHaveProperty('effectiveType')
      expect(status).toHaveProperty('downlink')
      expect(status).toHaveProperty('rtt')
      expect(status.isOnline).toBe(true)
    })

    it('should update network status when connection changes', () => {
      // Get the connection object and update it
      const connection = (navigator as any).connection
      
      // Update connection properties
      connection.type = 'cellular'
      connection.effectiveType = '3g'
      connection.downlink = 2
      connection.rtt = 200

      // Find and call the change event handler
      const changeHandler = connection.addEventListener.mock.calls.find(
        (call: any) => call[0] === 'change'
      )?.[1]

      if (changeHandler) {
        changeHandler()
        
        const status = networkErrorDetector.getNetworkStatus()
        expect(status.connectionType).toBe('cellular')
        expect(status.effectiveType).toBe('3g')
      } else {
        // If no handler found, manually update and test
        ;(networkErrorDetector as any).updateConnectionInfo(connection)
        const status = networkErrorDetector.getNetworkStatus()
        expect(status.connectionType).toBe('cellular')
        expect(status.effectiveType).toBe('3g')
      }
    })
  })

  describe('Error History and Statistics', () => {
    it('should maintain error history', () => {
      const error1 = { message: 'Error 1' }
      const error2 = { message: 'Error 2' }

      networkErrorDetector.detectError(error1)
      networkErrorDetector.detectError(error2)

      const history = networkErrorDetector.getErrorHistory()
      expect(history).toHaveLength(2)
      expect(history[0].message).toBe('Error 1')
      expect(history[1].message).toBe('Error 2')
    })

    it('should limit error history to 100 entries', () => {
      // Add more than 100 errors
      for (let i = 0; i < 150; i++) {
        networkErrorDetector.detectError({ message: `Error ${i}` })
      }

      const history = networkErrorDetector.getErrorHistory()
      expect(history).toHaveLength(100)
      
      // Should keep the most recent errors
      expect(history[99].message).toBe('Error 149')
    })

    it('should provide error statistics', () => {
      // Add various types of errors
      networkErrorDetector.detectError({ code: 'ECONNABORTED' }) // timeout
      networkErrorDetector.detectError({ response: { status: 500 } }) // server_error
      networkErrorDetector.detectError({ code: 'NETWORK_ERROR' }) // connection_failed

      const stats = networkErrorDetector.getErrorStats()

      expect(stats.totalErrors).toBe(3)
      expect(stats.errorsByType).toHaveProperty('timeout')
      expect(stats.errorsByType).toHaveProperty('server_error')
      expect(stats.errorsByType).toHaveProperty('connection_failed')
      expect(stats.averageRetryCount).toBe(0) // No retries yet
      expect(stats.recentErrors).toHaveLength(3)
    })

    it('should clear error history', () => {
      networkErrorDetector.detectError({ message: 'Test error' })
      expect(networkErrorDetector.getErrorHistory()).toHaveLength(1)

      networkErrorDetector.clearErrorHistory()
      expect(networkErrorDetector.getErrorHistory()).toHaveLength(0)
    })
  })

  describe('Configuration', () => {
    it('should update retry configuration', () => {
      const newConfig = {
        maxRetries: 5,
        baseDelay: 2000,
        maxDelay: 60000,
        backoffMultiplier: 3
      }

      networkErrorDetector.updateRetryConfig(newConfig)

      // Test that new config is applied
      const delay = networkErrorDetector.calculateRetryDelay(1)
      expect(delay).toBeGreaterThanOrEqual(6000) // 3x base delay (2000) + jitter
    })
  })

  describe('Real Network Scenarios', () => {
    it('should handle real fetch timeout error', () => {
      const fetchTimeoutError = {
        name: 'AbortError',
        message: 'The operation was aborted due to timeout'
      }

      const networkError = networkErrorDetector.detectError(fetchTimeoutError)
      expect(networkError.type).toBe('timeout') // AbortError should be classified as timeout
    })

    it('should handle real network disconnection', () => {
      // Set navigator.onLine to false
      Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: false
      })
      
      const disconnectionError = {
        message: 'Failed to fetch'
      }

      const networkError = networkErrorDetector.detectError(disconnectionError)
      expect(networkError.type).toBe('offline')
      
      // Reset navigator.onLine
      Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: true
      })
    })

    it('should handle real server errors', () => {
      const serverError = {
        response: {
          status: 503,
          statusText: 'Service Unavailable'
        },
        message: 'Request failed with status code 503'
      }

      const networkError = networkErrorDetector.detectError(serverError)
      expect(networkError.type).toBe('server_error')
      
      const shouldRetry = networkErrorDetector.shouldRetry(networkError, serverError.response)
      expect(shouldRetry).toBe(true) // 503 is retryable
    })
  })
})