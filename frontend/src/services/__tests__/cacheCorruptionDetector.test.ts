import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { cacheCorruptionDetector } from '../cacheCorruptionDetector'

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
  length: 0,
  key: vi.fn()
}

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

// Mock navigator.storage
Object.defineProperty(navigator, 'storage', {
  value: {
    estimate: vi.fn().mockResolvedValue({
      usage: 1024 * 1024, // 1MB
      quota: 10 * 1024 * 1024 // 10MB
    })
  }
})

// Mock crypto.subtle
Object.defineProperty(window, 'crypto', {
  value: {
    subtle: {
      digest: vi.fn().mockImplementation(() => {
        const hash = new Uint8Array([1, 2, 3, 4, 5, 6, 7, 8])
        return Promise.resolve(hash.buffer)
      })
    }
  }
})

describe('CacheCorruptionDetector', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorageMock.length = 0
    localStorageMock.key.mockReturnValue(null)
    cacheCorruptionDetector.clearCorruptionReports()
  })

  afterEach(() => {
    vi.clearAllTimers()
  })

  describe('Cache Entry Validation', () => {
    it('should validate correct cache entry structure', async () => {
      const validEntry = {
        key: 'test-key',
        value: { data: 'test' },
        timestamp: Date.now(),
        ttl: 3600000,
        checksum: '0102030405060708',
        version: '1.0.0'
      }

      localStorageMock.getItem.mockReturnValue(JSON.stringify(validEntry))

      const isCorrupted = await cacheCorruptionDetector.validateCacheEntry('cache_test-key')
      expect(isCorrupted).toBe(false)
    })

    it('should detect missing cache entry', async () => {
      localStorageMock.getItem.mockReturnValue(null)

      const isCorrupted = await cacheCorruptionDetector.validateCacheEntry('cache_missing')
      expect(isCorrupted).toBe(false) // Missing is not corrupted
    })

    it('should detect invalid JSON format', async () => {
      localStorageMock.getItem.mockReturnValue('invalid json {')

      const isCorrupted = await cacheCorruptionDetector.validateCacheEntry('cache_invalid')
      expect(isCorrupted).toBe(true)

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports).toHaveLength(1)
      expect(reports[0].type).toBe('invalid_format')
    })

    it('should detect missing required metadata', async () => {
      const invalidEntry = {
        value: { data: 'test' }
        // Missing key and timestamp
      }

      localStorageMock.getItem.mockReturnValue(JSON.stringify(invalidEntry))

      const isCorrupted = await cacheCorruptionDetector.validateCacheEntry('cache_no-metadata')
      expect(isCorrupted).toBe(true)

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports).toHaveLength(1)
      expect(reports[0].type).toBe('missing_metadata')
    })

    it('should detect expired cache entries', async () => {
      const expiredEntry = {
        key: 'test-key',
        value: { data: 'test' },
        timestamp: Date.now() - 7200000, // 2 hours ago
        ttl: 3600000, // 1 hour TTL
        version: '1.0.0'
      }

      localStorageMock.getItem.mockReturnValue(JSON.stringify(expiredEntry))

      const isCorrupted = await cacheCorruptionDetector.validateCacheEntry('cache_expired')
      expect(isCorrupted).toBe(true)

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports).toHaveLength(1)
      expect(reports[0].type).toBe('expired_data')
    })

    it('should detect checksum mismatch', async () => {
      const corruptedEntry = {
        key: 'test-key',
        value: { data: 'test' },
        timestamp: Date.now(),
        ttl: 3600000,
        checksum: 'wrong-checksum',
        version: '1.0.0'
      }

      localStorageMock.getItem.mockReturnValue(JSON.stringify(corruptedEntry))

      const isCorrupted = await cacheCorruptionDetector.validateCacheEntry('cache_corrupted')
      expect(isCorrupted).toBe(true)

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports).toHaveLength(1)
      expect(reports[0].type).toBe('checksum_mismatch')
    })

    it('should detect incompatible cache version', async () => {
      const oldVersionEntry = {
        key: 'test-key',
        value: { data: 'test' },
        timestamp: Date.now(),
        ttl: 3600000,
        version: '0.9.0' // Old version
      }

      localStorageMock.getItem.mockReturnValue(JSON.stringify(oldVersionEntry))

      const isCorrupted = await cacheCorruptionDetector.validateCacheEntry('cache_old-version')
      expect(isCorrupted).toBe(true)

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports).toHaveLength(1)
      expect(reports[0].type).toBe('invalid_format')
    })
  })

  describe('Health Scanning', () => {
    it('should perform comprehensive health scan', async () => {
      // Mock localStorage with various entries
      localStorageMock.length = 5
      localStorageMock.key
        .mockReturnValueOnce('cache_valid')
        .mockReturnValueOnce('cache_corrupted')
        .mockReturnValueOnce('session_data')
        .mockReturnValueOnce('auth_token')
        .mockReturnValueOnce('other_data')

      // Mock valid entry
      localStorageMock.getItem.mockImplementation((key) => {
        if (key === 'cache_valid') {
          return JSON.stringify({
            key: 'valid',
            value: { data: 'test' },
            timestamp: Date.now(),
            ttl: 3600000,
            checksum: '0102030405060708',
            version: '1.0.0'
          })
        }
        if (key === 'cache_corrupted') {
          return 'invalid json'
        }
        if (key === 'session_data' || key === 'auth_token') {
          return JSON.stringify({ valid: 'data' })
        }
        return null
      })

      const health = await cacheCorruptionDetector.performHealthScan()

      expect(health.totalEntries).toBe(4) // Only cache_, session_, auth_ entries
      expect(health.corruptedEntries).toBe(1) // Only cache_corrupted
      expect(health.healthPercentage).toBe(75) // 3/4 healthy
      expect(health.lastScanTime).toBeInstanceOf(Date)
      expect(health.storageUsage.used).toBe(1024 * 1024)
      expect(health.storageUsage.available).toBe(10 * 1024 * 1024)
      expect(health.storageUsage.percentage).toBe(10)
    })

    it('should handle empty cache gracefully', async () => {
      localStorageMock.length = 0

      const health = await cacheCorruptionDetector.performHealthScan()

      expect(health.totalEntries).toBe(0)
      expect(health.corruptedEntries).toBe(0)
      expect(health.healthPercentage).toBe(100)
    })

    it('should prevent concurrent scans', async () => {
      // Start first scan
      const scan1Promise = cacheCorruptionDetector.performHealthScan()
      
      // Start second scan immediately
      const scan2Promise = cacheCorruptionDetector.performHealthScan()

      const [result1, result2] = await Promise.all([scan1Promise, scan2Promise])

      // Both should return the same result (second scan should return cached result)
      expect(result1).toEqual(result2)
    })
  })

  describe('Automatic Recovery', () => {
    it('should recover expired data by removal', async () => {
      const expiredEntry = {
        key: 'test-key',
        value: { data: 'test' },
        timestamp: Date.now() - 7200000,
        ttl: 3600000,
        version: '1.0.0'
      }

      localStorageMock.getItem.mockReturnValue(JSON.stringify(expiredEntry))

      await cacheCorruptionDetector.validateCacheEntry('cache_expired')

      // Wait for recovery
      await new Promise(resolve => setTimeout(resolve, 10))

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports[0].recovered).toBe(true)
      expect(reports[0].recoveryMethod).toBe('removed_expired')
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('cache_expired')
    })

    it('should recover corrupted data from backup', async () => {
      const corruptedEntry = {
        key: 'test-key',
        value: { data: 'test' },
        timestamp: Date.now(),
        checksum: 'wrong-checksum',
        version: '1.0.0'
      }

      const backupEntry = {
        key: 'test-key',
        value: { data: 'test' },
        timestamp: Date.now(),
        checksum: '0102030405060708',
        version: '1.0.0'
      }

      localStorageMock.getItem.mockImplementation((key) => {
        if (key === 'cache_corrupted') {
          return JSON.stringify(corruptedEntry)
        }
        if (key === 'cache_corrupted_backup') {
          return JSON.stringify(backupEntry)
        }
        return null
      })

      await cacheCorruptionDetector.validateCacheEntry('cache_corrupted')

      // Wait for recovery
      await new Promise(resolve => setTimeout(resolve, 10))

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports[0].recovered).toBe(true)
      expect(reports[0].recoveryMethod).toBe('restored_from_backup')
    })

    it('should salvage data from invalid format', async () => {
      const salvageableData = '{"value": {"data": "test"}, "extra": "field",}'
      localStorageMock.getItem.mockReturnValue(salvageableData)

      await cacheCorruptionDetector.validateCacheEntry('cache_salvageable')

      // Wait for recovery
      await new Promise(resolve => setTimeout(resolve, 10))

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports[0].recovered).toBe(true)
      expect(reports[0].recoveryMethod).toBe('data_salvaged')
    })

    it('should restore missing metadata', async () => {
      const incompleteEntry = {
        value: { data: 'test' }
        // Missing metadata
      }

      localStorageMock.getItem.mockReturnValue(JSON.stringify(incompleteEntry))

      await cacheCorruptionDetector.validateCacheEntry('cache_incomplete')

      // Wait for recovery
      await new Promise(resolve => setTimeout(resolve, 10))

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports[0].recovered).toBe(true)
      expect(reports[0].recoveryMethod).toBe('metadata_restored')
    })
  })

  describe('Cache Cleanup', () => {
    it('should force cleanup of corrupted entries', async () => {
      localStorageMock.length = 3
      localStorageMock.key
        .mockReturnValueOnce('cache_valid')
        .mockReturnValueOnce('cache_corrupted')
        .mockReturnValueOnce('cache_expired')

      localStorageMock.getItem.mockImplementation((key) => {
        if (key === 'cache_valid') {
          return JSON.stringify({
            key: 'valid',
            value: { data: 'test' },
            timestamp: Date.now(),
            ttl: 3600000,
            checksum: '0102030405060708',
            version: '1.0.0'
          })
        }
        if (key === 'cache_corrupted') {
          return 'invalid json'
        }
        if (key === 'cache_expired') {
          return JSON.stringify({
            key: 'expired',
            value: { data: 'test' },
            timestamp: Date.now() - 7200000,
            ttl: 3600000,
            version: '1.0.0'
          })
        }
        return null
      })

      await cacheCorruptionDetector.forceCacheCleanup()

      expect(localStorageMock.removeItem).toHaveBeenCalledWith('cache_corrupted')
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('cache_expired')
      expect(localStorageMock.removeItem).not.toHaveBeenCalledWith('cache_valid')
    })

    it('should clear old cache entries to free space', async () => {
      const oldTimestamp = Date.now() - 86400000 - 1000 // More than 24 hours ago

      localStorageMock.length = 2
      localStorageMock.key
        .mockReturnValueOnce('cache_old')
        .mockReturnValueOnce('cache_recent')

      localStorageMock.getItem.mockImplementation((key) => {
        if (key === 'cache_old') {
          return JSON.stringify({
            timestamp: oldTimestamp,
            value: { data: 'old' }
          })
        }
        if (key === 'cache_recent') {
          return JSON.stringify({
            timestamp: Date.now(),
            value: { data: 'recent' }
          })
        }
        return null
      })

      // Trigger storage error recovery which calls clearOldCacheEntries
      await (cacheCorruptionDetector as any).clearOldCacheEntries()

      expect(localStorageMock.removeItem).toHaveBeenCalledWith('cache_old')
      expect(localStorageMock.removeItem).not.toHaveBeenCalledWith('cache_recent')
    })
  })

  describe('Backup Management', () => {
    it('should create backup of cache entry', async () => {
      const testData = JSON.stringify({ data: 'test' })
      localStorageMock.getItem.mockReturnValue(testData)

      await cacheCorruptionDetector.createBackup('cache_test')

      expect(localStorageMock.setItem).toHaveBeenCalledWith('cache_test_backup', testData)
    })

    it('should handle missing entry during backup', async () => {
      localStorageMock.getItem.mockReturnValue(null)

      await cacheCorruptionDetector.createBackup('cache_missing')

      expect(localStorageMock.setItem).not.toHaveBeenCalled()
    })
  })

  describe('Checksum Calculation', () => {
    it('should calculate checksum using crypto.subtle when available', async () => {
      const testData = { test: 'data' }
      
      const checksum = await (cacheCorruptionDetector as any).calculateChecksum(testData)
      
      expect(checksum).toBe('0102030405060708')
      expect(window.crypto.subtle.digest).toHaveBeenCalledWith(
        'SHA-256',
        expect.any(Uint8Array)
      )
    })

    it('should fallback to simple hash when crypto.subtle fails', async () => {
      const originalCrypto = window.crypto
      
      // Mock crypto.subtle to throw error
      window.crypto = {
        subtle: {
          digest: vi.fn().mockRejectedValue(new Error('Crypto not available'))
        }
      } as any

      const testData = { test: 'data' }
      const checksum = await (cacheCorruptionDetector as any).calculateChecksum(testData)
      
      expect(typeof checksum).toBe('string')
      expect(checksum.length).toBeGreaterThan(0)

      // Restore original crypto
      window.crypto = originalCrypto
    })

    it('should use simple hash when crypto.subtle not available', async () => {
      const originalCrypto = window.crypto
      delete (window as any).crypto

      const testData = { test: 'data' }
      const checksum = await (cacheCorruptionDetector as any).calculateChecksum(testData)
      
      expect(typeof checksum).toBe('string')
      expect(checksum.length).toBeGreaterThan(0)

      // Restore original crypto
      window.crypto = originalCrypto
    })
  })

  describe('Statistics and Reporting', () => {
    it('should provide cache health statistics', () => {
      const health = cacheCorruptionDetector.getCacheHealth()

      expect(health).toHaveProperty('totalEntries')
      expect(health).toHaveProperty('corruptedEntries')
      expect(health).toHaveProperty('healthPercentage')
      expect(health).toHaveProperty('lastScanTime')
      expect(health).toHaveProperty('storageUsage')
    })

    it('should maintain corruption reports history', async () => {
      localStorageMock.getItem.mockReturnValue('invalid json')

      await cacheCorruptionDetector.validateCacheEntry('cache_error1')
      await cacheCorruptionDetector.validateCacheEntry('cache_error2')

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports).toHaveLength(2)
      expect(reports[0].key).toBe('cache_error1')
      expect(reports[1].key).toBe('cache_error2')
    })

    it('should limit corruption reports to maximum count', async () => {
      localStorageMock.getItem.mockReturnValue('invalid json')

      // Generate more than 100 reports
      for (let i = 0; i < 150; i++) {
        await cacheCorruptionDetector.validateCacheEntry(`cache_error${i}`)
      }

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports).toHaveLength(100) // Should be limited to MAX_CORRUPTION_REPORTS
    })

    it('should clear corruption reports', async () => {
      localStorageMock.getItem.mockReturnValue('invalid json')
      await cacheCorruptionDetector.validateCacheEntry('cache_error')

      expect(cacheCorruptionDetector.getCorruptionReports()).toHaveLength(1)

      cacheCorruptionDetector.clearCorruptionReports()
      expect(cacheCorruptionDetector.getCorruptionReports()).toHaveLength(0)
    })
  })

  describe('Real-world Scenarios', () => {
    it('should handle browser storage quota exceeded', async () => {
      const quotaError = new Error('QuotaExceededError')
      quotaError.name = 'QuotaExceededError'
      
      localStorageMock.getItem.mockImplementation(() => {
        throw quotaError
      })

      const isCorrupted = await cacheCorruptionDetector.validateCacheEntry('cache_quota')
      expect(isCorrupted).toBe(true)

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports[0].type).toBe('storage_error')
    })

    it('should handle corrupted localStorage data in production', async () => {
      // Simulate real corrupted data scenarios
      const corruptedScenarios = [
        '{"key":"test","value":{"data":"test"},"timestamp":1234567890,"ttl":3600000,"checksum":"wrong","version":"1.0.0"}', // Wrong checksum
        '{"key":"test","value":{"data":"test"},"timestamp":' + (Date.now() - 7200000) + ',"ttl":3600000,"version":"1.0.0"}', // Expired
        '{"value":{"data":"test"}}', // Missing metadata
        '{"key":"test","value":{"data":"test"},}', // Invalid JSON
        '' // Empty string
      ]

      for (let i = 0; i < corruptedScenarios.length; i++) {
        localStorageMock.getItem.mockReturnValue(corruptedScenarios[i])
        await cacheCorruptionDetector.validateCacheEntry(`cache_scenario${i}`)
      }

      const reports = cacheCorruptionDetector.getCorruptionReports()
      expect(reports.length).toBeGreaterThan(0)
      expect(reports.every(r => r.recovered)).toBe(true) // All should be recovered
    })

    it('should handle periodic health monitoring', async () => {
      vi.useFakeTimers()

      // Mock some cache entries
      localStorageMock.length = 2
      localStorageMock.key
        .mockReturnValueOnce('cache_test1')
        .mockReturnValueOnce('cache_test2')

      localStorageMock.getItem.mockReturnValue(JSON.stringify({
        key: 'test',
        value: { data: 'test' },
        timestamp: Date.now(),
        ttl: 3600000,
        checksum: '0102030405060708',
        version: '1.0.0'
      }))

      // Fast-forward to trigger periodic scan
      vi.advanceTimersByTime(300000) // 5 minutes

      await new Promise(resolve => setTimeout(resolve, 10))

      const health = cacheCorruptionDetector.getCacheHealth()
      expect(health.totalEntries).toBe(2)
      expect(health.healthPercentage).toBe(100)

      vi.useRealTimers()
    })
  })
})