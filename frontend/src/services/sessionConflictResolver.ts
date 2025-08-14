// Session conflict resolver - uses Vue composables where needed
import { useAuthStore } from '@/stores/auth'
import { crossTabSessionManager } from './crossTabSessionManager'

export interface SessionConflict {
  id: string
  type: 'concurrent_login' | 'token_mismatch' | 'session_expired' | 'duplicate_session'
  description: string
  timestamp: Date
  conflictingData: {
    currentSession: any
    conflictingSession: any
  }
  resolution?: ConflictResolution
  resolved: boolean
}

export interface ConflictResolution {
  strategy: 'keep_current' | 'use_newer' | 'merge_sessions' | 'force_reauth' | 'user_choice'
  appliedAt: Date
  userChoice?: boolean
}

export interface ConflictNotification {
  id: string
  title: string
  message: string
  type: 'warning' | 'error' | 'info'
  actions: ConflictAction[]
  autoResolveIn?: number
}

export interface ConflictAction {
  label: string
  action: () => Promise<void>
  primary?: boolean
  destructive?: boolean
}

class SessionConflictResolver {
  private conflicts = new Map<string, SessionConflict>()
  private notifications = new Map<string, ConflictNotification>()
  private resolutionCallbacks: Map<string, (resolution: ConflictResolution) => void> = new Map()

  constructor() {
    this.initializeConflictDetection()
  }

  private initializeConflictDetection(): void {
    // Listen for cross-tab session updates
    crossTabSessionManager.subscribeToSessionUpdates((sessionData) => {
      this.detectSessionConflicts(sessionData)
    })

    // Delay auth store subscription to avoid initialization issues
    setTimeout(() => {
      try {
        const authStore = useAuthStore()
        authStore.$subscribe((mutation, state) => {
          if (mutation.type === 'direct') {
            this.validateSessionConsistency(state)
          }
        })
      } catch (error) {
        console.warn('Failed to subscribe to auth store:', error)
      }
    }, 0)
  }

  private async detectSessionConflicts(incomingSession: any): Promise<void> {
    try {
      const authStore = useAuthStore()
      const currentSession = authStore.user

      if (!currentSession || !incomingSession) {
        return
      }

      // Check for concurrent login
      if (incomingSession.userId === currentSession.id && 
          incomingSession.sessionId !== (authStore as any).sessionId) {
        await this.handleConcurrentLogin(currentSession, incomingSession)
      }

      // Check for token mismatch
      if (incomingSession.token && authStore.token && 
          incomingSession.token !== authStore.token &&
          incomingSession.userId === currentSession.id) {
        await this.handleTokenMismatch(currentSession, incomingSession)
      }

      // Check for session expiry conflicts
      if (incomingSession.expiresAt && (authStore as any).tokenExpiry) {
        const incomingExpiry = new Date(incomingSession.expiresAt)
        const currentExpiry = new Date((authStore as any).tokenExpiry)
        
        if (Math.abs(incomingExpiry.getTime() - currentExpiry.getTime()) > 60000) { // 1 minute difference
          await this.handleExpiryMismatch(currentSession, incomingSession)
        }
      }
    } catch (error) {
      console.warn('Failed to detect session conflicts:', error)
    }
  }

  private async validateSessionConsistency(authState: any): Promise<void> {
    if (!authState.user || !authState.token) {
      return
    }

    try {
      // Validate session with backend
      const response = await fetch('/api/auth/validate-session', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authState.token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          sessionId: (authState as any).sessionId,
          userId: authState.user.id
        })
      })

      if (!response.ok) {
        const errorData = await response.json()
        await this.handleSessionValidationFailure(authState, errorData)
      }
    } catch (error) {
      console.warn('Session validation failed:', error)
      // Don't create conflict for network errors
    }
  }

  private async handleConcurrentLogin(currentSession: any, incomingSession: any): Promise<void> {
    const conflictId = `concurrent_${Date.now()}`
    
    const conflict: SessionConflict = {
      id: conflictId,
      type: 'concurrent_login',
      description: 'Multiple login sessions detected for the same user',
      timestamp: new Date(),
      conflictingData: {
        currentSession,
        conflictingSession: incomingSession
      },
      resolved: false
    }

    this.conflicts.set(conflictId, conflict)

    const notification: ConflictNotification = {
      id: conflictId,
      title: 'Multiple Sessions Detected',
      message: 'You are logged in from another location. What would you like to do?',
      type: 'warning',
      actions: [
        {
          label: 'Keep This Session',
          action: async () => {
            await this.resolveConflict(conflictId, {
              strategy: 'keep_current',
              appliedAt: new Date(),
              userChoice: true
            })
          },
          primary: true
        },
        {
          label: 'Use Other Session',
          action: async () => {
            await this.resolveConflict(conflictId, {
              strategy: 'use_newer',
              appliedAt: new Date(),
              userChoice: true
            })
          }
        },
        {
          label: 'Log Out Everywhere',
          action: async () => {
            await this.resolveConflict(conflictId, {
              strategy: 'force_reauth',
              appliedAt: new Date(),
              userChoice: true
            })
          },
          destructive: true
        }
      ],
      autoResolveIn: 30000 // Auto-resolve in 30 seconds
    }

    this.notifications.set(conflictId, notification)

    // Auto-resolve after timeout
    setTimeout(() => {
      if (!conflict.resolved) {
        this.resolveConflict(conflictId, {
          strategy: 'keep_current',
          appliedAt: new Date()
        })
      }
    }, notification.autoResolveIn!)
  }

  private async handleTokenMismatch(currentSession: any, incomingSession: any): Promise<void> {
    const conflictId = `token_mismatch_${Date.now()}`
    
    const conflict: SessionConflict = {
      id: conflictId,
      type: 'token_mismatch',
      description: 'Authentication token mismatch detected between tabs',
      timestamp: new Date(),
      conflictingData: {
        currentSession,
        conflictingSession: incomingSession
      },
      resolved: false
    }

    this.conflicts.set(conflictId, conflict)

    // For token mismatches, automatically use the newer token
    const currentTime = new Date(currentSession.lastActivity || 0).getTime()
    const incomingTime = new Date(incomingSession.lastActivity || 0).getTime()

    const resolution: ConflictResolution = {
      strategy: incomingTime > currentTime ? 'use_newer' : 'keep_current',
      appliedAt: new Date()
    }

    await this.resolveConflict(conflictId, resolution)
  }

  private async handleExpiryMismatch(currentSession: any, incomingSession: any): Promise<void> {
    const conflictId = `expiry_mismatch_${Date.now()}`
    
    const conflict: SessionConflict = {
      id: conflictId,
      type: 'session_expired',
      description: 'Session expiry time mismatch detected',
      timestamp: new Date(),
      conflictingData: {
        currentSession,
        conflictingSession: incomingSession
      },
      resolved: false
    }

    this.conflicts.set(conflictId, conflict)

    // Use the later expiry time
    const currentExpiry = new Date(currentSession.expiresAt || 0).getTime()
    const incomingExpiry = new Date(incomingSession.expiresAt || 0).getTime()

    const resolution: ConflictResolution = {
      strategy: incomingExpiry > currentExpiry ? 'use_newer' : 'keep_current',
      appliedAt: new Date()
    }

    await this.resolveConflict(conflictId, resolution)
  }

  private async handleSessionValidationFailure(authState: any, errorData: any): Promise<void> {
    const conflictId = `validation_failure_${Date.now()}`
    
    const conflict: SessionConflict = {
      id: conflictId,
      type: 'session_expired',
      description: 'Session validation failed with backend',
      timestamp: new Date(),
      conflictingData: {
        currentSession: authState,
        conflictingSession: errorData
      },
      resolved: false
    }

    this.conflicts.set(conflictId, conflict)

    const notification: ConflictNotification = {
      id: conflictId,
      title: 'Session Expired',
      message: 'Your session has expired. Please log in again.',
      type: 'error',
      actions: [
        {
          label: 'Log In Again',
          action: async () => {
            await this.resolveConflict(conflictId, {
              strategy: 'force_reauth',
              appliedAt: new Date(),
              userChoice: true
            })
          },
          primary: true
        }
      ]
    }

    this.notifications.set(conflictId, notification)
  }

  public async resolveConflict(conflictId: string, resolution: ConflictResolution): Promise<void> {
    const conflict = this.conflicts.get(conflictId)
    if (!conflict || conflict.resolved) {
      return
    }

    conflict.resolution = resolution
    conflict.resolved = true

    const authStore = useAuthStore()

    try {
      switch (resolution.strategy) {
        case 'keep_current':
          // Broadcast current session to other tabs
          crossTabSessionManager.broadcastSessionUpdate({
            sessionId: (authStore as any).sessionId,
            userId: authStore.user?.id,
            token: authStore.token,
            expiresAt: (authStore as any).tokenExpiry,
            lastActivity: new Date()
          })
          break

        case 'use_newer':
          // Update current session with newer data
          const newerSession = conflict.conflictingData.conflictingSession
          if ((authStore as any).updateSession) {
            await (authStore as any).updateSession({
              token: newerSession.token,
              user: newerSession.user || authStore.user,
              expiresAt: newerSession.expiresAt
            })
          }
          break

        case 'merge_sessions':
          // Merge session data (use newer timestamps, keep current user data)
          const mergedData = {
            ...authStore.user,
            ...conflict.conflictingData.conflictingSession.user,
            lastActivity: new Date()
          }
          if ((authStore as any).updateUser) {
            (authStore as any).updateUser(mergedData)
          }
          break

        case 'force_reauth':
          // Force re-authentication
          await authStore.logout()
          crossTabSessionManager.broadcastLogout()
          // Redirect to login will be handled by auth store
          break

        case 'user_choice':
          // User choice was already applied in the action
          break
      }

      // Remove notification
      this.notifications.delete(conflictId)

      // Call resolution callback if exists
      const callback = this.resolutionCallbacks.get(conflictId)
      if (callback) {
        callback(resolution)
        this.resolutionCallbacks.delete(conflictId)
      }

    } catch (error) {
      console.error('Failed to resolve session conflict:', error)
      conflict.resolved = false
      throw error
    }
  }

  public getActiveConflicts(): SessionConflict[] {
    return Array.from(this.conflicts.values()).filter(c => !c.resolved)
  }

  public getActiveNotifications(): ConflictNotification[] {
    return Array.from(this.notifications.values())
  }

  public dismissNotification(notificationId: string): void {
    this.notifications.delete(notificationId)
  }

  public onConflictResolved(conflictId: string, callback: (resolution: ConflictResolution) => void): void {
    this.resolutionCallbacks.set(conflictId, callback)
  }

  public getConflictHistory(): SessionConflict[] {
    return Array.from(this.conflicts.values())
  }

  public clearConflictHistory(): void {
    this.conflicts.clear()
    this.notifications.clear()
    this.resolutionCallbacks.clear()
  }
}

export const sessionConflictResolver = new SessionConflictResolver()
export default sessionConflictResolver