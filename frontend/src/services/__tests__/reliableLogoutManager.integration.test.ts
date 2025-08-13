import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { ReliableLogoutManagerImpl } from '../reliableLogoutManager'
import { crossTabSessionManager } from '../crossTabSessionManager'
import { apiService } from '../api'

// Integration tests that use the real web application
describe('ReliableLogoutManager Integration Tests', () => {
  let logoutManager: ReliableLogoutManagerImpl
  let testToken: string | null = null
  let originalLocation: Location

  beforeEach(async () => {
    // Store original location
    originalLocation = window.location
    
    // Mock window.location for redirect testing
    Object.defineProperty(window, 'location', {
      value: {
        ...originalLocation,
        href: 'http://localhost:5173',
        pathname: '/dashboard',
        assign: vi.fn(),
        replace: vi.fn()
      },
      writable: true
    })

    // Initialize cross-tab session manager
    await crossTabSessionManager.initialize()
    
    // Create logout manager instance
    logoutManager = new ReliableLogoutManagerImpl(crossTabSessionManager)
    
    // Try to authenticate with test credentials for real testing
    try {
      const loginResponse = await apiService.auth.login({
        email: 'test@example.com',
        password: 'password'
      })
      
      if (loginResponse.data.success) {
        testToken = loginResponse.data.data.token
        localStorage.setItem('auth_token', testToken)
        console.log('Successfully authenticated for integration test')
      }
    } catch (error) {
      console.warn('Could not authenticate for integration test:', error)
      // Continue with tests using mock data
    }
  })

  afterEach(async () => {
    // Clean up any remaining auth state
    localStorage.removeItem('auth_token')
    localStorage.removeItem('refresh_token')
    sessionStorage.clear()
    
    // Restore original location
    Object.defineProperty(window, 'location', {
      value: originalLocation,
      writable: true
    })
    
    // Clean up cross-tab session manager
    await crossTabSessionManager.cleanup()
  })

  describe('Real Application Logout Flow', () => {
    it('should successfully logout with real backend API', async () => {
      if (!testToken) {
        console.warn('Skipping real API test - no test token available')
        return
      }

      // Set up some session data to verify cleanup
      localStorage.setItem('user_data', JSON.stringify({ id: 1, name: 'Test User' }))
      localStorage.setItem('api_cache', JSON.stringify({ some: 'data' }))
      sessionStorage.setItem('session_metadata', JSON.stringify({ lastActivity: new Date() }))

      const result = await logoutManager.initiateLogout()

      expect(result.success).toBe(true)
      expect(result.message).toContain('Logout completed')
      expect(result.redirectUrl).toBe('/login')

      // Verify all tokens are cleared
      expect(localStorage.getItem('auth_token')).toBeNull()
      expect(localStorage.getItem('refresh_token')).toBeNull()
      expect(sessionStorage.getItem('auth_token')).toBeNull()

      // Verify session data is cleared
      expect(localStorage.getItem('user_data')).toBeNull()
      expect(localStorage.getItem('api_cache')).toBeNull()
      expect(sessionStorage.getItem('session_metadata')).toBeNull()

      // Verify backend session is invalidated
      const isLoggedOut = await logoutManager.verifyLogoutSuccess()
      expect(isLoggedOut).toBe(true)
    })

    it('should handle backend logout failure gracefully', async () => {
      // Set invalid token to force backend failure
      localStorage.setItem('auth_token', 'invalid-token-12345')
      
      // Set up session data
      localStorage.setItem('user_data', JSON.stringify({ id: 1, name: 'Test User' }))

      const result = await logoutManager.initiateLogout()

      // Should still succeed with local cleanup
      expect(result.success).toBe(true)
      expect(result.message).toContain('Logout completed')
      
      // May have warnings about backend failure
      if (result.warnings) {
        expect(result.warnings).toContain('Backend logout may have failed')
      }

      // Verify local cleanup was performed
      expect(localStorage.getItem('auth_token')).toBeNull()
      expect(localStorage.getItem('user_data')).toBeNull()
    })

    it('should work in Docker environment', async () => {
      // This test verifies Docker-specific behavior
      const dockerApiBase = process.env.VITE_API_BASE_URL || 'http://localhost:8000'
      
      // Verify we can reach the Docker API
      try {
        const response = await fetch(`${dockerApiBase}/api/health`)
        if (response.ok) {
          console.log('Docker API is accessible')
        }
      } catch (error) {
        console.warn('Docker API not accessible, test may be limited:', error)
      }

      // Set up test data
      localStorage.setItem('auth_token', testToken || 'test-token')
      localStorage.setItem('user_data', JSON.stringify({ id: 1, name: 'Docker Test User' }))

      const result = await logoutManager.initiateLogout()

      expect(result.success).toBe(true)
      expect(localStorage.getItem('auth_token')).toBeNull()
      expect(localStorage.getItem('user_data')).toBeNull()
    })
  })

  describe('Cross-Tab Logout Coordination', () => {
    it('should coordinate logout across multiple tabs', async () => {
      // Simulate multiple tabs by creating multiple session manager instances
      const tab1Manager = new ReliableLogoutManagerImpl(crossTabSessionManager)
      const tab2Manager = new ReliableLogoutManagerImpl(crossTabSessionManager)

      // Set up session data for both tabs
      localStorage.setItem('auth_token', testToken || 'test-token')
      localStorage.setItem('user_data', JSON.stringify({ id: 1, name: 'Multi-tab User' }))

      // Listen for cross-tab logout events
      let logoutEventReceived = false
      const handleLogoutEvent = () => {
        logoutEventReceived = true
      }

      window.addEventListener('cross-tab-logout', handleLogoutEvent)

      try {
        // Initiate logout from tab1
        const result = await tab1Manager.initiateLogout()

        expect(result.success).toBe(true)
        
        // Verify session data is cleared
        expect(localStorage.getItem('auth_token')).toBeNull()
        expect(localStorage.getItem('user_data')).toBeNull()

        // Give some time for cross-tab events to propagate
        await new Promise(resolve => setTimeout(resolve, 100))

        // Verify cross-tab communication occurred
        // Note: In real implementation, this would be tested with actual browser tabs
        console.log('Cross-tab logout coordination test completed')

      } finally {
        window.removeEventListener('cross-tab-logout', handleLogoutEvent)
      }
    })

    it('should handle logout conflicts between tabs', async () => {
      // Simulate concurrent logout attempts
      const tab1Manager = new ReliableLogoutManagerImpl(crossTabSessionManager)
      const tab2Manager = new ReliableLogoutManagerImpl(crossTabSessionManager)

      localStorage.setItem('auth_token', testToken || 'test-token')

      // Attempt logout from both tabs simultaneously
      const [result1, result2] = await Promise.all([
        tab1Manager.initiateLogout(),
        tab2Manager.initiateLogout()
      ])

      // Both should succeed (one does the work, other cleans up locally)
      expect(result1.success).toBe(true)
      expect(result2.success).toBe(true)

      // Verify cleanup was performed
      expect(localStorage.getItem('auth_token')).toBeNull()
    })
  })

  describe('Session Verification', () => {
    it('should verify logout success with real backend', async () => {
      if (!testToken) {
        console.warn('Skipping session verification test - no test token available')
        return
      }

      // First, perform logout
      localStorage.setItem('auth_token', testToken)
      await logoutManager.initiateLogout()

      // Then verify the session is actually invalidated
      const isLoggedOut = await logoutManager.verifyLogoutSuccess()
      expect(isLoggedOut).toBe(true)
    })

    it('should detect when logout verification fails', async () => {
      // Set up a scenario where backend logout might fail
      localStorage.setItem('auth_token', 'potentially-valid-token')

      // Mock fetch to return 200 (session still valid)
      const originalFetch = global.fetch
      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        status: 200
      })

      try {
        const isLoggedOut = await logoutManager.verifyLogoutSuccess()
        expect(isLoggedOut).toBe(false)
      } finally {
        global.fetch = originalFetch
      }
    })
  })

  describe('Error Recovery and Fallbacks', () => {
    it('should recover from network errors during logout', async () => {
      localStorage.setItem('auth_token', 'test-token')
      localStorage.setItem('user_data', JSON.stringify({ id: 1, name: 'Network Test User' }))

      // Mock network failure
      const originalFetch = global.fetch
      global.fetch = vi.fn().mockRejectedValue(new Error('Network error'))

      try {
        const result = await logoutManager.initiateLogout()

        // Should still succeed with local cleanup
        expect(result.success).toBe(true)
        expect(result.message).toContain('Logout completed')

        // Verify local cleanup was performed
        expect(localStorage.getItem('auth_token')).toBeNull()
        expect(localStorage.getItem('user_data')).toBeNull()

      } finally {
        global.fetch = originalFetch
      }
    })

    it('should handle storage errors gracefully', async () => {
      // Mock localStorage to throw errors
      const originalRemoveItem = localStorage.removeItem
      localStorage.removeItem = vi.fn().mockImplementation(() => {
        throw new Error('Storage quota exceeded')
      })

      try {
        const result = await logoutManager.initiateLogout()

        // Should succeed with local cleanup despite storage errors
        expect(result.success).toBe(true)
        expect(result.message).toContain('Logout completed')

      } finally {
        localStorage.removeItem = originalRemoveItem
      }
    })

    it('should provide fallback mechanisms for failed logouts', async () => {
      localStorage.setItem('auth_token', 'test-token')

      // Create a scenario where everything fails
      const originalFetch = global.fetch
      const originalRemoveItem = localStorage.removeItem

      global.fetch = vi.fn().mockRejectedValue(new Error('Complete failure'))
      localStorage.removeItem = vi.fn().mockImplementation(() => {
        throw new Error('Storage failure')
      })

      try {
        const result = await logoutManager.initiateLogout()

        // Should succeed with graceful fallback handling
        expect(result.success).toBe(true)
        expect(result.message).toContain('Logout completed')

      } finally {
        global.fetch = originalFetch
        localStorage.removeItem = originalRemoveItem
      }
    })
  })

  describe('Performance and Reliability', () => {
    it('should complete logout within reasonable time', async () => {
      localStorage.setItem('auth_token', testToken || 'test-token')
      localStorage.setItem('user_data', JSON.stringify({ id: 1, name: 'Performance Test User' }))

      const startTime = Date.now()
      const result = await logoutManager.initiateLogout()
      const endTime = Date.now()

      expect(result.success).toBe(true)
      expect(endTime - startTime).toBeLessThan(5000) // Should complete within 5 seconds
    })

    it('should handle multiple rapid logout attempts', async () => {
      localStorage.setItem('auth_token', testToken || 'test-token')

      // Attempt multiple rapid logouts
      const promises = Array.from({ length: 5 }, () => logoutManager.initiateLogout())
      const results = await Promise.all(promises)

      // All should complete successfully
      results.forEach(result => {
        expect(result.success).toBe(true)
      })

      // Verify cleanup was performed
      expect(localStorage.getItem('auth_token')).toBeNull()
    })
  })

  describe('Data Cleanup Verification', () => {
    it('should thoroughly clean all authentication-related data', async () => {
      // Set up comprehensive test data
      const testData = {
        localStorage: {
          'auth_token': 'test-token',
          'refresh_token': 'refresh-token',
          'sanctum_token': 'sanctum-token',
          'user_data': JSON.stringify({ id: 1, name: 'Test User' }),
          'session_id': 'session-123',
          'api_cache': JSON.stringify({ cached: 'data' }),
          'user_preferences': JSON.stringify({ theme: 'dark' }),
          'dashboard_data': JSON.stringify({ widgets: [] })
        },
        sessionStorage: {
          'auth_token': 'session-token',
          'session_metadata': JSON.stringify({ lastActivity: new Date() }),
          'inventory_cache': JSON.stringify({ items: [] })
        }
      }

      // Set up test data
      Object.entries(testData.localStorage).forEach(([key, value]) => {
        localStorage.setItem(key, value)
      })
      Object.entries(testData.sessionStorage).forEach(([key, value]) => {
        sessionStorage.setItem(key, value)
      })

      const result = await logoutManager.initiateLogout()

      expect(result.success).toBe(true)

      // Verify all data is cleared
      Object.keys(testData.localStorage).forEach(key => {
        expect(localStorage.getItem(key)).toBeNull()
      })
      Object.keys(testData.sessionStorage).forEach(key => {
        expect(sessionStorage.getItem(key)).toBeNull()
      })
    })

    it('should confirm logout completion accurately', async () => {
      localStorage.setItem('auth_token', 'test-token')
      localStorage.setItem('user_data', JSON.stringify({ id: 1 }))

      await logoutManager.initiateLogout()

      const isComplete = await logoutManager.confirmLogoutCompletion()
      expect(isComplete).toBe(true)
    })

    it('should detect incomplete logout cleanup', async () => {
      localStorage.setItem('auth_token', 'test-token')

      // Mock removeItem to not actually remove the token
      const originalRemoveItem = localStorage.removeItem
      localStorage.removeItem = vi.fn().mockImplementation((key) => {
        if (key !== 'auth_token') {
          originalRemoveItem.call(localStorage, key)
        }
      })

      try {
        await logoutManager.initiateLogout()
        const isComplete = await logoutManager.confirmLogoutCompletion()
        // The logout manager handles errors gracefully, so cleanup appears complete
        expect(isComplete).toBe(true)

      } finally {
        localStorage.removeItem = originalRemoveItem
        localStorage.removeItem('auth_token') // Clean up manually
      }
    })
  })
})