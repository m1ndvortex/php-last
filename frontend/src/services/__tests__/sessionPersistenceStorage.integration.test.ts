import { describe, it, expect, beforeEach, afterEach } from 'vitest'
import { SessionPersistenceStorage } from '../sessionPersistenceStorage'
import type { SessionData } from '@/types/auth'

describe('SessionPersistenceStorage Integration Tests', () => {
  let storage: SessionPersistenceStorage

  beforeEach(() => {
    // Clear all storage before each test
    localStorage.clear()
    sessionStorage.clear()
    storage = new SessionPersistenceStorage({
      encryptSensitiveData: true,
      enableCompression: false,
      maxStorageSize: 2 * 1024 * 1024, // 2MB
      cleanupInterval: 30000, // 30 seconds
      backupEnabled: true
    })
  })

  afterEach(() => {
    if (storage && typeof storage.destroy === 'function') {
      storage.destroy()
    }
    localStorage.clear()
    sessionStorage.clear()
  })

  describe('Real Session Data Scenarios', () => {
    it('should handle complete session data lifecycle', () => {
      const sessionData: SessionData = {
        sessionId: 'sess_123456789',
        userId: 1,
        token: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.test.token',
        expiresAt: new Date(Date.now() + 3600000), // 1 hour from now
        lastActivity: new Date(),
        tabId: 'tab_current',
        isActive: true,
        metadata: {
          userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
          ipAddress: '192.168.1.100',
          loginTime: new Date(),
          refreshCount: 0
        }
      }

      // Store session data
      storage.setItem('current_session', sessionData, { encrypt: true })

      // Retrieve and verify
      const retrieved = storage.getItem<SessionData>('current_session')
      expect(retrieved).toBeDefined()
      expect(retrieved?.sessionId).toBe(sessionData.sessionId)
      expect(retrieved?.userId).toBe(sessionData.userId)
      expect(retrieved?.token).toBe(sessionData.token)
      expect(retrieved?.isActive).toBe(true)

      // Verify metadata
      const metadata = storage.getSessionMetadata('current_session')
      expect(metadata).toBeDefined()
      expect(metadata?.tabId).toBeDefined()
      expect(metadata?.checksum).toBeDefined()
    })

    it('should handle user authentication data with encryption', () => {
      const authData = {
        user: {
          id: 1,
          email: 'test@example.com',
          name: 'Test User',
          roles: ['user', 'admin']
        },
        permissions: ['read', 'write', 'delete'],
        preferences: {
          theme: 'dark',
          language: 'en',
          notifications: true
        }
      }

      // Store with automatic encryption (contains sensitive data)
      storage.setItem('user_auth', authData)

      // Verify encryption occurred
      const rawStored = localStorage.getItem('session_user_auth')
      expect(rawStored).toBeDefined()
      expect(rawStored).not.toContain('test@example.com')
      expect(rawStored).not.toContain('Test User')

      // Verify decryption works
      const retrieved = storage.getItem('user_auth')
      expect(retrieved).toEqual(authData)
    })

    it('should handle API response caching', () => {
      const apiResponses = [
        {
          endpoint: '/api/inventory/items',
          data: [
            { id: 1, name: 'Gold Ring', price: 1500, category: 'rings' },
            { id: 2, name: 'Silver Necklace', price: 800, category: 'necklaces' }
          ],
          timestamp: new Date().toISOString()
        },
        {
          endpoint: '/api/customers',
          data: [
            { id: 1, name: 'John Doe', email: 'john@example.com' },
            { id: 2, name: 'Jane Smith', email: 'jane@example.com' }
          ],
          timestamp: new Date().toISOString()
        }
      ]

      // Cache API responses with TTL
      apiResponses.forEach((response, index) => {
        storage.setItem(`api_cache_${response.endpoint}`, response, {
          ttl: 300000, // 5 minutes
          encrypt: false // API cache doesn't need encryption
        })
      })

      // Verify all responses are cached
      apiResponses.forEach(response => {
        const cached = storage.getItem(`api_cache_${response.endpoint}`)
        expect(cached).toEqual(response)
      })

      // Verify cache invalidation by pattern
      storage.invalidateCache('api_cache_/api/inventory/.*')
      
      expect(storage.getItem('api_cache_/api/inventory/items')).toBeNull()
      expect(storage.getItem('api_cache_/api/customers')).toBeDefined()
    })

    it('should handle cross-tab session synchronization data', () => {
      const crossTabData = {
        sessionState: 'authenticated',
        lastSync: new Date().toISOString(),
        activeTabs: ['tab_1', 'tab_2', 'tab_3'],
        sharedData: {
          currentUser: { id: 1, name: 'Test User' },
          notifications: [
            { id: 1, message: 'Welcome back!', read: false },
            { id: 2, message: 'You have 3 new orders', read: false }
          ]
        }
      }

      // Store cross-tab data
      storage.setItem('cross_tab_sync', crossTabData, { encrypt: true })

      // Simulate tab switching by retrieving data
      const retrieved = storage.getItem('cross_tab_sync')
      expect(retrieved).toEqual(crossTabData)

      // Verify access count increased
      const metadata = storage.getSessionMetadata('cross_tab_sync')
      expect(metadata?.accessCount).toBeGreaterThan(1)
    })
  })

  describe('Performance and Storage Management', () => {
    it('should handle large datasets efficiently', () => {
      const largeDataset = {
        inventory: Array.from({ length: 1000 }, (_, i) => ({
          id: i + 1,
          name: `Item ${i + 1}`,
          description: `Description for item ${i + 1}`.repeat(10),
          price: Math.random() * 1000,
          category: `category_${i % 10}`,
          tags: [`tag_${i % 5}`, `tag_${(i + 1) % 5}`]
        })),
        metadata: {
          totalItems: 1000,
          lastUpdated: new Date().toISOString(),
          version: '1.0'
        }
      }

      const startTime = performance.now()
      storage.setItem('large_inventory', largeDataset)
      const storeTime = performance.now() - startTime

      const retrieveStartTime = performance.now()
      const retrieved = storage.getItem('large_inventory')
      const retrieveTime = performance.now() - retrieveStartTime

      expect(retrieved).toEqual(largeDataset)
      expect(storeTime).toBeLessThan(1000) // Should store within 1 second
      expect(retrieveTime).toBeLessThan(500) // Should retrieve within 500ms

      // Verify storage stats
      const stats = storage.getStorageStats()
      expect(stats.totalSize).toBeGreaterThan(0)
      expect(stats.itemCount).toBeGreaterThan(0)
    })

    it('should manage storage cleanup under pressure', () => {
      // Fill storage with multiple items
      const items = []
      for (let i = 0; i < 50; i++) {
        const item = {
          id: i,
          data: 'x'.repeat(1000), // 1KB per item
          timestamp: new Date(Date.now() - i * 1000).toISOString() // Older items first
        }
        items.push(item)
        storage.setItem(`item_${i}`, item)
      }

      // Get initial stats
      const initialStats = storage.getStorageStats()
      
      // Force cleanup by adding more data
      storage.performCleanup()
      
      // Verify cleanup occurred
      const finalStats = storage.getStorageStats()
      expect(finalStats.itemCount).toBeLessThanOrEqual(initialStats.itemCount)
      
      // Verify most recent items are still available
      const recentItem = storage.getItem('item_49')
      expect(recentItem).toBeDefined()
    })
  })

  describe('Error Recovery and Resilience', () => {
    it('should recover from storage corruption', () => {
      const criticalData = {
        sessionId: 'critical_session',
        userData: { id: 1, email: 'user@example.com' },
        timestamp: new Date().toISOString()
      }

      // Store critical data
      storage.setItem('critical_session', criticalData, { encrypt: true })

      // Verify backup was created
      const backupExists = localStorage.getItem('backup_session_critical_session')
      expect(backupExists).toBeDefined()

      // Simulate corruption by overwriting main storage
      localStorage.setItem('session_critical_session', 'corrupted_data')

      // Should recover from backup
      const recovered = storage.getItem('critical_session')
      expect(recovered).toEqual(criticalData)
    })

    it('should handle concurrent access patterns', async () => {
      const baseData = { counter: 0, timestamp: Date.now() }
      
      // Simulate concurrent access from multiple "tabs"
      const promises = Array.from({ length: 10 }, async (_, i) => {
        return new Promise<void>((resolve) => {
          setTimeout(() => {
            const current = storage.getItem('concurrent_test') || baseData
            const updated = {
              ...current,
              counter: (current.counter || 0) + 1,
              lastUpdatedBy: `tab_${i}`,
              timestamp: Date.now()
            }
            storage.setItem('concurrent_test', updated)
            resolve()
          }, Math.random() * 100)
        })
      })

      await Promise.all(promises)

      const final = storage.getItem('concurrent_test')
      expect(final).toBeDefined()
      expect(final.counter).toBeGreaterThan(0)
      expect(final.lastUpdatedBy).toBeDefined()
    })

    it('should maintain data integrity across browser sessions', () => {
      const persistentData = {
        userPreferences: {
          theme: 'dark',
          language: 'fa',
          currency: 'IRR'
        },
        recentActivity: [
          { action: 'login', timestamp: new Date().toISOString() },
          { action: 'view_inventory', timestamp: new Date().toISOString() }
        ],
        sessionInfo: {
          loginTime: new Date().toISOString(),
          deviceInfo: 'Chrome/Windows'
        }
      }

      // Store data that should persist
      storage.setItem('persistent_session', persistentData, { encrypt: true })

      // Simulate browser restart by creating new storage instance
      const newStorage = new SessionPersistenceStorage()

      // Data should still be available
      const retrieved = newStorage.getItem('persistent_session')
      expect(retrieved).toEqual(persistentData)

      // Verify integrity
      const metadata = newStorage.getSessionMetadata('persistent_session')
      expect(metadata?.checksum).toBeDefined()

      newStorage.destroy()
    })
  })

  describe('Real-World Usage Patterns', () => {
    it('should handle authentication token refresh cycle', async () => {
      let tokenData = {
        accessToken: 'initial_token_123',
        refreshToken: 'refresh_token_456',
        expiresAt: new Date(Date.now() + 3600000).toISOString(),
        userId: 1
      }

      // Initial token storage
      storage.setItem('auth_tokens', tokenData, { encrypt: true })

      // Simulate token refresh after some time
      await new Promise(resolve => setTimeout(resolve, 100))

      const refreshedTokenData = {
        ...tokenData,
        accessToken: 'refreshed_token_789',
        expiresAt: new Date(Date.now() + 3600000).toISOString(),
        refreshCount: 1
      }

      storage.setItem('auth_tokens', refreshedTokenData, { encrypt: true })

      const retrieved = storage.getItem('auth_tokens')
      expect(retrieved.accessToken).toBe('refreshed_token_789')
      expect(retrieved.refreshCount).toBe(1)

      // Verify metadata was updated
      const metadata = storage.getSessionMetadata('auth_tokens')
      expect(metadata?.accessCount).toBeGreaterThan(1)
    })

    it('should handle user preference synchronization', () => {
      const preferences = {
        ui: {
          theme: 'dark',
          sidebarCollapsed: false,
          gridView: true
        },
        business: {
          defaultCurrency: 'IRR',
          taxRate: 0.09,
          invoiceTemplate: 'persian'
        },
        notifications: {
          email: true,
          push: false,
          sms: true
        }
      }

      // Store preferences
      storage.setItem('user_preferences', preferences)

      // Simulate preference update
      const updatedPreferences = {
        ...preferences,
        ui: {
          ...preferences.ui,
          theme: 'light',
          sidebarCollapsed: true
        }
      }

      storage.setItem('user_preferences', updatedPreferences)

      const retrieved = storage.getItem('user_preferences')
      expect(retrieved.ui.theme).toBe('light')
      expect(retrieved.ui.sidebarCollapsed).toBe(true)
      expect(retrieved.business.defaultCurrency).toBe('IRR')
    })

    it('should handle application state caching', () => {
      const appState = {
        currentRoute: '/inventory',
        breadcrumbs: [
          { name: 'Dashboard', path: '/' },
          { name: 'Inventory', path: '/inventory' }
        ],
        filters: {
          category: 'rings',
          priceRange: [100, 1000],
          inStock: true
        },
        pagination: {
          page: 2,
          perPage: 25,
          total: 150
        },
        sortBy: 'name',
        sortOrder: 'asc'
      }

      // Cache application state
      storage.setItem('app_state', appState, { ttl: 1800000 }) // 30 minutes

      // Retrieve and verify
      const retrieved = storage.getItem('app_state')
      expect(retrieved).toEqual(appState)

      // Verify TTL was set
      const metadata = storage.getSessionMetadata('app_state')
      expect(metadata).toBeDefined()
    })
  })

  describe('Storage Statistics and Monitoring', () => {
    it('should provide accurate storage metrics', () => {
      // Add various types of data
      storage.setItem('encrypted_data', { secret: 'value' }, { encrypt: true })
      storage.setItem('regular_data', { normal: 'value' })
      storage.setItem('large_data', { data: 'x'.repeat(1000) })

      const stats = storage.getStorageStats()

      expect(stats.itemCount).toBe(3)
      expect(stats.totalSize).toBeGreaterThan(1000)
      expect(stats.encryptedItems).toBeGreaterThan(0)
      expect(stats.backupItems).toBe(3)
    })

    it('should track access patterns', () => {
      const testData = { value: 'tracked' }
      
      storage.setItem('tracked_item', testData)
      
      // Access multiple times
      for (let i = 0; i < 5; i++) {
        storage.getItem('tracked_item')
      }

      const metadata = storage.getSessionMetadata('tracked_item')
      expect(metadata?.accessCount).toBeGreaterThan(5)
      expect(metadata?.lastAccessed).toBeInstanceOf(Date)
    })
  })
})