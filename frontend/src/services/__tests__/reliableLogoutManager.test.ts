import { describe, it, expect, beforeEach, afterEach, vi, type MockedFunction } from 'vitest'
import { ReliableLogoutManagerImpl } from '../reliableLogoutManager'
import { CrossTabSessionManager } from '../crossTabSessionManager'
import type { LogoutError } from '../../types/auth'

// Mock fetch globally
const mockFetch = vi.fn() as MockedFunction<typeof fetch>
global.fetch = mockFetch

// Mock CrossTabSessionManager
const mockCrossTabManager = {
  broadcastLogout: vi.fn(),
  updateSessionData: vi.fn(),
  getSessionData: vi.fn(() => ({
    sessionId: 'test-session',
    userId: 1,
    token: 'test-token',
    expiresAt: new Date(Date.now() + 3600000),
    lastActivity: new Date(),
    tabId: 'test-tab-123',
    isActive: true,
    metadata: {
      userAgent: 'test-agent',
      loginTime: new Date(),
      refreshCount: 0
    }
  }))
} as unknown as CrossTabSessionManager

describe('ReliableLogoutManager', () => {
  let logoutManager: ReliableLogoutManagerImpl
  let originalLocalStorage: Storage
  let originalSessionStorage: Storage
  let originalDocument: Document

  beforeEach(() => {
    // Reset all mocks
    vi.clearAllMocks()
    
    // Create logout manager instance
    logoutManager = new ReliableLogoutManagerImpl(mockCrossTabManager)
    
    // Mock localStorage
    const localStorageMock = {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
      length: 0,
      key: vi.fn()
    }
    
    // Mock sessionStorage
    const sessionStorageMock = {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
      length: 0,
      key: vi.fn()
    }
    
    // Store originals
    originalLocalStorage = global.localStorage
    originalSessionStorage = global.sessionStorage
    originalDocument = global.document
    
    // Set mocks
    Object.defineProperty(global, 'localStorage', { value: localStorageMock })
    Object.defineProperty(global, 'sessionStorage', { value: sessionStorageMock })
    
    // Mock document.cookie
    Object.defineProperty(global.document, 'cookie', {
      writable: true,
      value: ''
    })
    
    // Mock indexedDB if it doesn't exist or replace if it does
    if (!global.indexedDB) {
      Object.defineProperty(global, 'indexedDB', {
        value: {
          databases: vi.fn(() => Promise.resolve([
            { name: 'app_cache_test' },
            { name: 'other_db' }
          ])),
          deleteDatabase: vi.fn()
        },
        configurable: true
      })
    } else {
      global.indexedDB.databases = vi.fn(() => Promise.resolve([
        { name: 'app_cache_test' },
        { name: 'other_db' }
      ]))
      global.indexedDB.deleteDatabase = vi.fn()
    }
    
    // Mock console methods
    vi.spyOn(console, 'log').mockImplementation(() => {})
    vi.spyOn(console, 'error').mockImplementation(() => {})
    vi.spyOn(console, 'warn').mockImplementation(() => {})
  })

  afterEach(() => {
    // Restore originals
    Object.defineProperty(global, 'localStorage', { value: originalLocalStorage })
    Object.defineProperty(global, 'sessionStorage', { value: originalSessionStorage })
    Object.defineProperty(global, 'document', { value: originalDocument })
    
    // Restore console
    vi.restoreAllMocks()
  })

  describe('initiateLogout', () => {
    it('should successfully complete logout process', async () => {
      // Mock successful backend logout
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ success: true })
      } as Response)
      
      // Mock successful session verification (401 means logged out)
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401
      } as Response)

      const result = await logoutManager.initiateLogout()

      expect(result.success).toBe(true)
      expect(result.message).toBe('Logout completed successfully')
      expect(result.redirectUrl).toBe('/login')
      expect(mockCrossTabManager.broadcastLogout).toHaveBeenCalled()
    })

    it('should handle backend logout failure gracefully', async () => {
      // Mock failed backend logout (all 3 retry attempts)
      mockFetch
        .mockRejectedValueOnce(new Error('Network error'))
        .mockRejectedValueOnce(new Error('Network error'))
        .mockRejectedValueOnce(new Error('Network error'))
      
      // Mock session verification that shows session is still valid (backend logout failed)
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200
      } as Response)

      const result = await logoutManager.initiateLogout()

      expect(result.success).toBe(true)
      expect(result.message).toBe('Logout completed (local cleanup successful)')
      expect(result.warnings).toContain('Backend logout may have failed')
    })

    it('should handle complete logout failure', async () => {
      // Mock localStorage.removeItem to throw error
      vi.mocked(localStorage.removeItem).mockImplementation(() => {
        throw new Error('Storage error')
      })

      const result = await logoutManager.initiateLogout()

      expect(result.success).toBe(false)
      expect(result.message).toBe('Logout failed, but local cleanup attempted')
      expect(result.error).toBeDefined()
      expect(result.error?.type).toBe('logout_failed')
    })
  })

  describe('broadcastLogout', () => {
    it('should broadcast logout message to all tabs', () => {
      logoutManager.broadcastLogout()

      expect(mockCrossTabManager.broadcastLogout).toHaveBeenCalled()
    })
  })

  describe('clearAllTokens', () => {
    it('should clear tokens from localStorage, sessionStorage, and cookies', async () => {
      await logoutManager.clearAllTokens()

      // Check localStorage calls
      expect(localStorage.removeItem).toHaveBeenCalledWith('auth_token')
      expect(localStorage.removeItem).toHaveBeenCalledWith('refresh_token')
      expect(localStorage.removeItem).toHaveBeenCalledWith('sanctum_token')

      // Check sessionStorage calls
      expect(sessionStorage.removeItem).toHaveBeenCalledWith('auth_token')
      expect(sessionStorage.removeItem).toHaveBeenCalledWith('refresh_token')
      expect(sessionStorage.removeItem).toHaveBeenCalledWith('sanctum_token')
    })

    it('should handle storage errors gracefully', async () => {
      vi.mocked(localStorage.removeItem).mockImplementation(() => {
        throw new Error('Storage error')
      })

      await expect(logoutManager.clearAllTokens()).rejects.toThrow('Storage error')
    })
  })

  describe('clearSessionData', () => {
    it('should clear session data from storage and cross-tab manager', async () => {
      await logoutManager.clearSessionData()

      // Check that session keys are removed
      const sessionKeys = [
        'user_data',
        'session_id',
        'session_metadata',
        'last_activity',
        'session_expires_at'
      ]

      sessionKeys.forEach(key => {
        expect(localStorage.removeItem).toHaveBeenCalledWith(key)
        expect(sessionStorage.removeItem).toHaveBeenCalledWith(key)
      })

      expect(mockCrossTabManager.updateSessionData).toHaveBeenCalledWith({
        sessionId: '',
        userId: null,
        token: null,
        expiresAt: null,
        isActive: false
      })
    })
  })

  describe('clearCachedData', () => {
    it('should clear cached data from storage and IndexedDB', async () => {
      await logoutManager.clearCachedData()

      // Check that cache keys are removed
      const cacheKeys = [
        'api_cache',
        'user_preferences',
        'dashboard_data',
        'inventory_cache',
        'customer_cache',
        'invoice_cache'
      ]

      cacheKeys.forEach(key => {
        expect(localStorage.removeItem).toHaveBeenCalledWith(key)
        expect(sessionStorage.removeItem).toHaveBeenCalledWith(key)
      })

      // Check IndexedDB cleanup
      expect(indexedDB.databases).toHaveBeenCalled()
      expect(indexedDB.deleteDatabase).toHaveBeenCalledWith('app_cache_test')
    })

    it('should handle IndexedDB errors gracefully', async () => {
      vi.mocked(indexedDB.databases).mockRejectedValueOnce(new Error('IndexedDB error'))

      // Should not throw error
      await expect(logoutManager.clearCachedData()).resolves.not.toThrow()
    })
  })

  describe('verifyLogoutSuccess', () => {
    it('should return true when session is invalidated (401)', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401
      } as Response)

      const result = await logoutManager.verifyLogoutSuccess()
      expect(result).toBe(true)
    })

    it('should return false when session is still valid (200)', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200
      } as Response)

      const result = await logoutManager.verifyLogoutSuccess()
      expect(result).toBe(false)
    })

    it('should return true for other status codes', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500
      } as Response)

      const result = await logoutManager.verifyLogoutSuccess()
      expect(result).toBe(true)
    })

    it('should handle network errors gracefully', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))

      const result = await logoutManager.verifyLogoutSuccess()
      expect(result).toBe(true) // Assume logout was successful locally
    })
  })

  describe('handleLogoutFailure', () => {
    it('should perform cleanup and broadcast failure', async () => {
      const error: LogoutError = {
        type: 'logout_failed',
        message: 'Test error',
        originalError: new Error('Original error')
      }

      await logoutManager.handleLogoutFailure(error)

      // Should still attempt cleanup
      expect(localStorage.removeItem).toHaveBeenCalled()
      expect(sessionStorage.removeItem).toHaveBeenCalled()
      expect(mockCrossTabManager.updateSessionData).toHaveBeenCalled()

      // Should broadcast failure by updating session data
      expect(mockCrossTabManager.updateSessionData).toHaveBeenCalledWith({
        isActive: false,
        token: null
      })
    })

    it('should handle cleanup errors during failure handling', async () => {
      const error: LogoutError = {
        type: 'logout_failed',
        message: 'Test error'
      }

      // Mock cleanup to fail
      vi.mocked(localStorage.removeItem).mockImplementation(() => {
        throw new Error('Cleanup error')
      })

      // Should not throw error
      await expect(logoutManager.handleLogoutFailure(error)).resolves.not.toThrow()
    })
  })

  describe('confirmLogoutCompletion', () => {
    it('should return true when all cleanup is verified', async () => {
      // Mock that all tokens and data are cleared
      vi.mocked(localStorage.getItem).mockReturnValue(null)
      vi.mocked(sessionStorage.getItem).mockReturnValue(null)

      const result = await logoutManager.confirmLogoutCompletion()
      expect(result).toBe(true)
    })

    it('should return false when tokens are not cleared', async () => {
      // Mock that some tokens still exist
      vi.mocked(localStorage.getItem).mockImplementation((key) => {
        if (key === 'auth_token') return 'still-exists'
        return null
      })

      const result = await logoutManager.confirmLogoutCompletion()
      expect(result).toBe(false)
    })

    it('should return false when session data is not cleared', async () => {
      // Mock that tokens are cleared but session data exists
      vi.mocked(localStorage.getItem).mockImplementation((key) => {
        if (key === 'user_data') return 'still-exists'
        return null
      })

      const result = await logoutManager.confirmLogoutCompletion()
      expect(result).toBe(false)
    })

    it('should handle verification errors gracefully', async () => {
      vi.mocked(localStorage.getItem).mockImplementation(() => {
        throw new Error('Storage error')
      })

      const result = await logoutManager.confirmLogoutCompletion()
      expect(result).toBe(false)
    })
  })

  describe('backend logout retry mechanism', () => {
    it('should retry backend logout on failure', async () => {
      // Mock first two attempts to fail, third to succeed
      mockFetch
        .mockRejectedValueOnce(new Error('Network error'))
        .mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce({
          ok: true,
          status: 200
        } as Response)
      
      // Mock successful verification
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401
      } as Response)

      const result = await logoutManager.initiateLogout()
      
      expect(result.success).toBe(true)
      expect(mockFetch).toHaveBeenCalledTimes(4) // 3 logout attempts + 1 verification
    })

    it('should give up after max retries', async () => {
      // Mock all attempts to fail
      mockFetch
        .mockRejectedValueOnce(new Error('Network error'))
        .mockRejectedValueOnce(new Error('Network error'))
        .mockRejectedValueOnce(new Error('Network error'))
      
      // Mock successful verification
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401
      } as Response)

      const result = await logoutManager.initiateLogout()
      
      expect(result.success).toBe(true) // Still successful due to local cleanup
      expect(result.warnings).toContain('Backend logout may have failed')
      expect(mockFetch).toHaveBeenCalledTimes(4) // 3 failed logout attempts + 1 verification
    })
  })

  describe('Docker environment compatibility', () => {
    it('should work with Docker API endpoints', async () => {
      // Mock successful Docker API response
      mockFetch.mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: () => Promise.resolve({ success: true })
      } as Response)
      
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401
      } as Response)

      const result = await logoutManager.initiateLogout()

      expect(result.success).toBe(true)
      expect(mockFetch).toHaveBeenCalledWith('/api/auth/logout', expect.objectContaining({
        method: 'POST',
        headers: expect.objectContaining({
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        })
      }))
    })

    it('should handle Docker network issues', async () => {
      // Mock Docker network timeout
      mockFetch.mockImplementation(() => 
        new Promise((_, reject) => 
          setTimeout(() => reject(new Error('Docker network timeout')), 100)
        )
      )

      // Also mock localStorage to fail to simulate complete failure
      vi.mocked(localStorage.removeItem).mockImplementation(() => {
        throw new Error('Storage cleanup failed')
      })

      const result = await logoutManager.initiateLogout()

      // Should fail due to storage cleanup failure
      expect(result.success).toBe(false)
      expect(result.message).toBe('Logout failed, but local cleanup attempted')
    })
  })
})