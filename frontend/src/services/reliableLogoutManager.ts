import { crossTabSessionManager } from './crossTabSessionManager'
import type { LogoutResult, LogoutError } from '../types/auth'

export interface ReliableLogoutManager {
  // Logout coordination
  initiateLogout(): Promise<LogoutResult>
  broadcastLogout(): void
  confirmLogoutCompletion(): Promise<boolean>
  
  // Cleanup operations
  clearAllTokens(): Promise<void>
  clearSessionData(): Promise<void>
  clearCachedData(): Promise<void>
  
  // Verification
  verifyLogoutSuccess(): Promise<boolean>
  handleLogoutFailure(error: LogoutError): Promise<void>
}

export class ReliableLogoutManagerImpl implements ReliableLogoutManager {
  private crossTabManager: typeof crossTabSessionManager
  private maxRetries = 3
  private retryDelay = 1000 // 1 second

  constructor(crossTabManager: typeof crossTabSessionManager) {
    this.crossTabManager = crossTabManager
  }

  async initiateLogout(): Promise<LogoutResult> {
    try {
      // Step 1: Broadcast logout intent to all tabs
      this.broadcastLogout()
      
      // Step 2: Clear all tokens and session data
      await this.clearAllTokens()
      await this.clearSessionData()
      await this.clearCachedData()
      
      // Step 3: Make API call to backend to invalidate session
      const backendLogoutSuccess = await this.performBackendLogout()
      
      // Step 4: Verify logout completion
      const isComplete = await this.confirmLogoutCompletion()
      
      // Step 5: Verify with backend that logout was successful
      const isVerified = await this.verifyLogoutSuccess()
      
      if (isComplete && backendLogoutSuccess && isVerified) {
        return {
          success: true,
          message: 'Logout completed successfully',
          redirectUrl: '/login'
        }
      } else {
        // Even if backend fails, continue with local cleanup
        return {
          success: true,
          message: backendLogoutSuccess ? 'Logout completed successfully' : 'Logout completed (local cleanup successful)',
          redirectUrl: '/login',
          warnings: backendLogoutSuccess ? [] : ['Backend logout may have failed']
        }
      }
    } catch (error) {
      const logoutError: LogoutError = {
        type: 'logout_failed',
        message: error instanceof Error ? error.message : 'Unknown logout error',
        originalError: error
      }
      
      await this.handleLogoutFailure(logoutError)
      
      // Check if this was a complete failure (including local cleanup)
      const isLocalCleanupFailed = error instanceof Error && 
        (error.message.includes('Storage') || error.message.includes('cleanup'))
      
      return {
        success: !isLocalCleanupFailed,
        message: isLocalCleanupFailed ? 'Logout failed, but local cleanup attempted' : 'Logout completed (local cleanup successful)',
        error: logoutError,
        redirectUrl: '/login'
      }
    }
  }

  broadcastLogout(): void {
    // Broadcast logout to all tabs using the public method
    this.crossTabManager.broadcastLogout()
  }
  

  
  private getApiBaseUrl(): string {
    // In Docker environment, use the backend service URL
    if (typeof window !== 'undefined') {
      // Browser environment - use relative URLs or environment variable
      return import.meta.env?.VITE_API_BASE_URL || window.location.origin
    }
    // Node.js test environment - use localhost
    return 'http://localhost:8000'
  }

  async confirmLogoutCompletion(): Promise<boolean> {
    try {
      // Check if tokens are cleared
      const tokensCleared = await this.verifyTokensCleared()
      
      // Check if session data is cleared
      const sessionCleared = await this.verifySessionCleared()
      
      // Check if cached data is cleared
      const cacheCleared = await this.verifyCacheCleared()
      
      return tokensCleared && sessionCleared && cacheCleared
    } catch (error) {
      console.error('Error confirming logout completion:', error)
      return false
    }
  }

  async clearAllTokens(): Promise<void> {
    try {
      // Clear from localStorage
      localStorage.removeItem('auth_token')
      localStorage.removeItem('refresh_token')
      localStorage.removeItem('sanctum_token')
      
      // Clear from sessionStorage
      sessionStorage.removeItem('auth_token')
      sessionStorage.removeItem('refresh_token')
      sessionStorage.removeItem('sanctum_token')
      
      // Clear from cookies if any
      document.cookie = 'auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
      document.cookie = 'refresh_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
      document.cookie = 'sanctum_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
      
      console.log('All tokens cleared successfully')
    } catch (error) {
      console.error('Error clearing tokens:', error)
      throw error
    }
  }

  async clearSessionData(): Promise<void> {
    try {
      // Clear session-related data from localStorage
      const sessionKeys = [
        'user_data',
        'session_id',
        'session_metadata',
        'last_activity',
        'session_expires_at'
      ]
      
      sessionKeys.forEach(key => {
        localStorage.removeItem(key)
        sessionStorage.removeItem(key)
      })
      
      // Update cross-tab session data to cleared state
      this.crossTabManager.updateSessionData({
        sessionId: '',
        userId: null,
        token: null,
        expiresAt: null,
        isActive: false
      })
      
      console.log('Session data cleared successfully')
    } catch (error) {
      console.error('Error clearing session data:', error)
      throw error
    }
  }

  async clearCachedData(): Promise<void> {
    try {
      // Clear application cache data
      const cacheKeys = [
        'api_cache',
        'user_preferences',
        'dashboard_data',
        'inventory_cache',
        'customer_cache',
        'invoice_cache'
      ]
      
      cacheKeys.forEach(key => {
        localStorage.removeItem(key)
        sessionStorage.removeItem(key)
      })
      
      // Clear IndexedDB if used
      if ('indexedDB' in window) {
        try {
          const databases = await indexedDB.databases()
          for (const db of databases) {
            if (db.name?.includes('app_cache')) {
              indexedDB.deleteDatabase(db.name)
            }
          }
        } catch (error) {
          console.warn('Could not clear IndexedDB:', error)
        }
      }
      
      console.log('Cached data cleared successfully')
    } catch (error) {
      console.error('Error clearing cached data:', error)
      throw error
    }
  }

  async verifyLogoutSuccess(): Promise<boolean> {
    try {
      // Get the base URL for API calls
      const baseUrl = this.getApiBaseUrl()
      
      // Verify with backend that session is invalidated
      const response = await fetch(`${baseUrl}/api/auth/verify-session`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })
      
      // If we get 401, logout was successful
      if (response.status === 401) {
        return true
      }
      
      // If we get 200, session is still valid (logout failed)
      if (response.status === 200) {
        return false
      }
      
      // For other status codes, assume logout was successful
      return true
    } catch (error) {
      // Network error - assume logout was successful locally
      console.warn('Could not verify logout with backend:', error)
      return true
    }
  }

  async handleLogoutFailure(error: LogoutError): Promise<void> {
    console.error('Logout failure:', error)
    
    try {
      // Still attempt local cleanup even if logout failed
      await this.clearAllTokens()
      await this.clearSessionData()
      await this.clearCachedData()
      
      // Broadcast logout failure to other tabs by updating session data
      this.crossTabManager.updateSessionData({
        isActive: false,
        token: null
      })
      
      // Log the error for debugging
      console.error('Logout failed but local cleanup completed:', error)
    } catch (cleanupError) {
      console.error('Critical error: Both logout and cleanup failed:', {
        originalError: error,
        cleanupError
      })
    }
  }

  private async performBackendLogout(): Promise<boolean> {
    let retries = 0
    
    while (retries < this.maxRetries) {
      try {
        const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')
        
        const baseUrl = this.getApiBaseUrl()
        
        const response = await fetch(`${baseUrl}/api/auth/logout`, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...(token && { 'Authorization': `Bearer ${token}` })
          }
        })
        
        if (response.ok || response.status === 401) {
          // 200 OK or 401 Unauthorized both indicate successful logout
          return true
        }
        
        throw new Error(`Backend logout failed with status: ${response.status}`)
      } catch (error) {
        retries++
        console.warn(`Backend logout attempt ${retries} failed:`, error)
        
        if (retries < this.maxRetries) {
          await new Promise(resolve => setTimeout(resolve, this.retryDelay * retries))
        }
      }
    }
    
    return false
  }

  private async verifyTokensCleared(): Promise<boolean> {
    const tokenKeys = ['auth_token', 'refresh_token', 'sanctum_token']
    
    for (const key of tokenKeys) {
      if (localStorage.getItem(key) || sessionStorage.getItem(key)) {
        return false
      }
    }
    
    return true
  }

  private async verifySessionCleared(): Promise<boolean> {
    const sessionKeys = [
      'user_data',
      'session_id',
      'session_metadata',
      'last_activity',
      'session_expires_at'
    ]
    
    for (const key of sessionKeys) {
      if (localStorage.getItem(key) || sessionStorage.getItem(key)) {
        return false
      }
    }
    
    return true
  }

  private async verifyCacheCleared(): Promise<boolean> {
    const cacheKeys = [
      'api_cache',
      'user_preferences',
      'dashboard_data',
      'inventory_cache',
      'customer_cache',
      'invoice_cache'
    ]
    
    for (const key of cacheKeys) {
      if (localStorage.getItem(key) || sessionStorage.getItem(key)) {
        return false
      }
    }
    
    return true
  }
}