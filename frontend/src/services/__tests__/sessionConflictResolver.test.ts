import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

// Mock the auth store first
const mockAuthStore = {
  user: { id: 1, name: 'Test User' },
  token: 'test-token',
  sessionId: 'session-123',
  tokenExpiry: new Date(Date.now() + 3600000).toISOString(),
  refreshToken: 'refresh-token',
  updateSession: vi.fn(),
  updateUser: vi.fn(),
  logout: vi.fn(),
  $subscribe: vi.fn()
}

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => mockAuthStore
}))

// Mock cross-tab session manager
vi.mock('../crossTabSessionManager', () => ({
  crossTabSessionManager: {
    subscribeToSessionUpdates: vi.fn(),
    broadcastSessionUpdate: vi.fn(),
    broadcastLogout: vi.fn()
  }
}))

// Mock fetch
global.fetch = vi.fn()

// Import after mocks
import { sessionConflictResolver } from '../sessionConflictResolver'
import { crossTabSessionManager } from '../crossTabSessionManager'

describe('SessionConflictResolver', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    sessionConflictResolver.clearConflictHistory()
    
    // Reset mock auth store
    mockAuthStore.user = { id: 1, name: 'Test User' }
    mockAuthStore.token = 'test-token'
    mockAuthStore.sessionId = 'session-123'
    mockAuthStore.tokenExpiry = new Date(Date.now() + 3600000).toISOString()
  })

  afterEach(() => {
    vi.clearAllTimers()
  })

  describe('Conflict Detection', () => {
    it('should detect concurrent login conflicts', async () => {
      const incomingSession = {
        userId: 1,
        sessionId: 'session-456', // Different session ID
        token: 'different-token',
        expiresAt: new Date(Date.now() + 3600000).toISOString()
      }

      // Simulate session update that triggers conflict detection
      const subscribeCallback = (crossTabSessionManager.subscribeToSessionUpdates as any).mock.calls[0]?.[0]
      if (subscribeCallback) {
        await subscribeCallback(incomingSession)
      } else {
        // Directly call the detection method for testing
        await (sessionConflictResolver as any).detectSessionConflicts(incomingSession)
      }

      const conflicts = sessionConflictResolver.getActiveConflicts()
      expect(conflicts).toHaveLength(1)
      expect(conflicts[0].type).toBe('concurrent_login')
    })

    it('should detect token mismatch conflicts', async () => {
      const incomingSession = {
        userId: 1,
        sessionId: 'session-123', // Same session ID
        token: 'different-token', // Different token
        expiresAt: mockAuthStore.tokenExpiry,
        lastActivity: new Date().toISOString()
      }

      const subscribeCallback = (crossTabSessionManager.subscribeToSessionUpdates as any).mock.calls[0]?.[0]
      if (subscribeCallback) {
        await subscribeCallback(incomingSession)
      } else {
        await (sessionConflictResolver as any).detectSessionConflicts(incomingSession)
      }

      const conflicts = sessionConflictResolver.getActiveConflicts()
      expect(conflicts).toHaveLength(1)
      expect(conflicts[0].type).toBe('token_mismatch')
    })

    it('should detect session expiry conflicts', async () => {
      const incomingSession = {
        userId: 1,
        sessionId: 'session-123',
        token: mockAuthStore.token,
        expiresAt: new Date(Date.now() + 7200000).toISOString(), // 2 hours later
        lastActivity: new Date().toISOString()
      }

      const subscribeCallback = (crossTabSessionManager.subscribeToSessionUpdates as any).mock.calls[0]?.[0]
      if (subscribeCallback) {
        await subscribeCallback(incomingSession)
      } else {
        await (sessionConflictResolver as any).detectSessionConflicts(incomingSession)
      }

      const conflicts = sessionConflictResolver.getActiveConflicts()
      expect(conflicts).toHaveLength(1)
      expect(conflicts[0].type).toBe('session_expired')
    })

    it('should not detect conflicts for different users', async () => {
      const incomingSession = {
        userId: 2, // Different user
        sessionId: 'session-456',
        token: 'different-token',
        expiresAt: new Date(Date.now() + 3600000).toISOString()
      }

      const subscribeCallback = (crossTabSessionManager.subscribeToSessionUpdates as any).mock.calls[0]?.[0]
      if (subscribeCallback) {
        await subscribeCallback(incomingSession)
      }

      const conflicts = sessionConflictResolver.getActiveConflicts()
      expect(conflicts).toHaveLength(0)
    })
  })

  describe('Session Validation', () => {
    it('should validate session with backend successfully', async () => {
      (global.fetch as any).mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ valid: true })
      })

      // Trigger auth state change
      const subscribeCallback = mockAuthStore.$subscribe.mock.calls[0]?.[0]
      if (subscribeCallback) {
        subscribeCallback(
          { type: 'direct', payload: { token: 'new-token' } },
          mockAuthStore
        )
      }

      expect(global.fetch).toHaveBeenCalledWith('/api/auth/validate-session', {
        method: 'POST',
        headers: {
          'Authorization': 'Bearer test-token',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          sessionId: 'session-123',
          userId: 1
        })
      })
    })

    it('should handle session validation failure', async () => {
      (global.fetch as any).mockResolvedValueOnce({
        ok: false,
        json: () => Promise.resolve({ error: 'Session expired' })
      })

      const subscribeCallback = mockAuthStore.$subscribe.mock.calls[0]?.[0]
      if (subscribeCallback) {
        subscribeCallback(
          { type: 'direct', payload: { token: 'new-token' } },
          mockAuthStore
        )
      }

      // Wait for async processing
      await new Promise(resolve => setTimeout(resolve, 10))

      const conflicts = sessionConflictResolver.getActiveConflicts()
      expect(conflicts.some(c => c.type === 'session_expired')).toBe(true)
    })
  })

  describe('Conflict Resolution', () => {
    it('should resolve conflict with keep_current strategy', async () => {
      // Create a conflict first
      const conflictId = 'test-conflict'
      const conflict = {
        id: conflictId,
        type: 'concurrent_login' as const,
        description: 'Test conflict',
        timestamp: new Date(),
        conflictingData: {
          currentSession: mockAuthStore,
          conflictingSession: { sessionId: 'other-session' }
        },
        resolved: false
      }

      // Manually add conflict for testing
      ;(sessionConflictResolver as any).conflicts.set(conflictId, conflict)

      await sessionConflictResolver.resolveConflict(conflictId, {
        strategy: 'keep_current',
        appliedAt: new Date()
      })

      expect(conflict.resolved).toBe(true)
      expect(crossTabSessionManager.broadcastSessionUpdate).toHaveBeenCalled()
    })

    it('should resolve conflict with use_newer strategy', async () => {
      const conflictId = 'test-conflict'
      const newerSession = {
        token: 'newer-token',
        user: { id: 1, name: 'Updated User' },
        expiresAt: new Date(Date.now() + 7200000).toISOString()
      }

      const conflict = {
        id: conflictId,
        type: 'token_mismatch' as const,
        description: 'Test conflict',
        timestamp: new Date(),
        conflictingData: {
          currentSession: mockAuthStore,
          conflictingSession: newerSession
        },
        resolved: false
      }

      ;(sessionConflictResolver as any).conflicts.set(conflictId, conflict)

      await sessionConflictResolver.resolveConflict(conflictId, {
        strategy: 'use_newer',
        appliedAt: new Date()
      })

      expect(conflict.resolved).toBe(true)
      expect(mockAuthStore.updateSession).toHaveBeenCalledWith({
        token: 'newer-token',
        user: { id: 1, name: 'Updated User' },
        expiresAt: newerSession.expiresAt
      })
    })

    it('should resolve conflict with force_reauth strategy', async () => {
      const conflictId = 'test-conflict'
      const conflict = {
        id: conflictId,
        type: 'session_expired' as const,
        description: 'Test conflict',
        timestamp: new Date(),
        conflictingData: {
          currentSession: mockAuthStore,
          conflictingSession: {}
        },
        resolved: false
      }

      ;(sessionConflictResolver as any).conflicts.set(conflictId, conflict)

      await sessionConflictResolver.resolveConflict(conflictId, {
        strategy: 'force_reauth',
        appliedAt: new Date()
      })

      expect(conflict.resolved).toBe(true)
      expect(mockAuthStore.logout).toHaveBeenCalled()
      expect(crossTabSessionManager.broadcastLogout).toHaveBeenCalled()
    })

    it('should resolve conflict with merge_sessions strategy', async () => {
      const conflictId = 'test-conflict'
      const conflictingSession = {
        user: { id: 1, name: 'Updated User', email: 'updated@test.com' }
      }

      const conflict = {
        id: conflictId,
        type: 'concurrent_login' as const,
        description: 'Test conflict',
        timestamp: new Date(),
        conflictingData: {
          currentSession: mockAuthStore,
          conflictingSession
        },
        resolved: false
      }

      ;(sessionConflictResolver as any).conflicts.set(conflictId, conflict)

      await sessionConflictResolver.resolveConflict(conflictId, {
        strategy: 'merge_sessions',
        appliedAt: new Date()
      })

      expect(conflict.resolved).toBe(true)
      expect(mockAuthStore.updateUser).toHaveBeenCalledWith(
        expect.objectContaining({
          id: 1,
          name: 'Updated User',
          email: 'updated@test.com',
          lastActivity: expect.any(String)
        })
      )
    })
  })

  describe('Notifications', () => {
    it('should create notification for concurrent login', async () => {
      const incomingSession = {
        userId: 1,
        sessionId: 'session-456',
        token: 'different-token',
        expiresAt: new Date(Date.now() + 3600000).toISOString()
      }

      const subscribeCallback = (crossTabSessionManager.subscribeToSessionUpdates as any).mock.calls[0]?.[0]
      if (subscribeCallback) {
        await subscribeCallback(incomingSession)
      }

      const notifications = sessionConflictResolver.getActiveNotifications()
      expect(notifications).toHaveLength(1)
      expect(notifications[0].title).toBe('Multiple Sessions Detected')
      expect(notifications[0].actions).toHaveLength(3)
    })

    it('should auto-resolve notification after timeout', async () => {
      vi.useFakeTimers()

      const incomingSession = {
        userId: 1,
        sessionId: 'session-456',
        token: 'different-token',
        expiresAt: new Date(Date.now() + 3600000).toISOString()
      }

      const subscribeCallback = (crossTabSessionManager.subscribeToSessionUpdates as any).mock.calls[0]?.[0]
      if (subscribeCallback) {
        await subscribeCallback(incomingSession)
      }

      // Fast-forward time to trigger auto-resolution
      vi.advanceTimersByTime(30000)

      const conflicts = sessionConflictResolver.getActiveConflicts()
      expect(conflicts.every(c => c.resolved)).toBe(true)

      vi.useRealTimers()
    })

    it('should dismiss notification', () => {
      const notificationId = 'test-notification'
      ;(sessionConflictResolver as any).notifications.set(notificationId, {
        id: notificationId,
        title: 'Test Notification',
        message: 'Test message',
        type: 'info',
        actions: []
      })

      sessionConflictResolver.dismissNotification(notificationId)

      const notifications = sessionConflictResolver.getActiveNotifications()
      expect(notifications.find(n => n.id === notificationId)).toBeUndefined()
    })
  })

  describe('Conflict Resolution Callbacks', () => {
    it('should call resolution callback when conflict is resolved', async () => {
      const conflictId = 'test-conflict'
      const callback = vi.fn()

      sessionConflictResolver.onConflictResolved(conflictId, callback)

      const conflict = {
        id: conflictId,
        type: 'concurrent_login' as const,
        description: 'Test conflict',
        timestamp: new Date(),
        conflictingData: {
          currentSession: mockAuthStore,
          conflictingSession: {}
        },
        resolved: false
      }

      ;(sessionConflictResolver as any).conflicts.set(conflictId, conflict)

      const resolution = {
        strategy: 'keep_current' as const,
        appliedAt: new Date()
      }

      await sessionConflictResolver.resolveConflict(conflictId, resolution)

      expect(callback).toHaveBeenCalledWith(resolution)
    })
  })

  describe('History and Statistics', () => {
    it('should maintain conflict history', async () => {
      const incomingSession = {
        userId: 1,
        sessionId: 'session-456',
        token: 'different-token',
        expiresAt: new Date(Date.now() + 3600000).toISOString()
      }

      const subscribeCallback = (crossTabSessionManager.subscribeToSessionUpdates as any).mock.calls[0]?.[0]
      if (subscribeCallback) {
        await subscribeCallback(incomingSession)
      }

      const history = sessionConflictResolver.getConflictHistory()
      expect(history).toHaveLength(1)
      expect(history[0].type).toBe('concurrent_login')
    })

    it('should clear conflict history', () => {
      // Add a conflict first
      ;(sessionConflictResolver as any).conflicts.set('test', {
        id: 'test',
        type: 'concurrent_login',
        resolved: false
      })

      sessionConflictResolver.clearConflictHistory()

      const history = sessionConflictResolver.getConflictHistory()
      expect(history).toHaveLength(0)
    })
  })

  describe('Real-world Scenarios', () => {
    it('should handle user logging in from another device', async () => {
      const anotherDeviceSession = {
        userId: 1,
        sessionId: 'mobile-session-789',
        token: 'mobile-token',
        expiresAt: new Date(Date.now() + 3600000).toISOString(),
        userAgent: 'Mobile Safari',
        lastActivity: new Date().toISOString()
      }

      const subscribeCallback = (crossTabSessionManager.subscribeToSessionUpdates as any).mock.calls[0]?.[0]
      if (subscribeCallback) {
        await subscribeCallback(anotherDeviceSession)
      }

      const conflicts = sessionConflictResolver.getActiveConflicts()
      expect(conflicts).toHaveLength(1)
      expect(conflicts[0].type).toBe('concurrent_login')

      const notifications = sessionConflictResolver.getActiveNotifications()
      expect(notifications).toHaveLength(1)
      expect(notifications[0].actions).toHaveLength(3) // Keep, Use Other, Log Out Everywhere
    })

    it('should handle token refresh conflicts between tabs', async () => {
      const refreshedSession = {
        userId: 1,
        sessionId: 'session-123',
        token: 'refreshed-token-456',
        expiresAt: new Date(Date.now() + 3600000).toISOString(),
        lastActivity: new Date(Date.now() + 1000).toISOString() // Slightly newer
      }

      const subscribeCallback = (crossTabSessionManager.subscribeToSessionUpdates as any).mock.calls[0]?.[0]
      if (subscribeCallback) {
        await subscribeCallback(refreshedSession)
      }

      // Should auto-resolve to use newer token
      await new Promise(resolve => setTimeout(resolve, 10))

      const conflicts = sessionConflictResolver.getActiveConflicts()
      expect(conflicts.every(c => c.resolved)).toBe(true)
    })

    it('should handle session expiry from server validation', async () => {
      (global.fetch as any).mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: () => Promise.resolve({ error: 'Session expired', code: 'SESSION_EXPIRED' })
      })

      const subscribeCallback = mockAuthStore.$subscribe.mock.calls[0]?.[0]
      if (subscribeCallback) {
        subscribeCallback(
          { type: 'direct', payload: { token: 'expired-token' } },
          mockAuthStore
        )
      }

      await new Promise(resolve => setTimeout(resolve, 10))

      const notifications = sessionConflictResolver.getActiveNotifications()
      expect(notifications.some(n => n.title === 'Session Expired')).toBe(true)
    })
  })
})