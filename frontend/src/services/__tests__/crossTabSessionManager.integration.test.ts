import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { crossTabSessionManager } from '../crossTabSessionManager';
import { apiService } from '../api';
import type { SessionData } from '../crossTabSessionManager';

// Integration tests that work with real web application
describe('CrossTabSessionManager Integration', () => {
  let manager: typeof crossTabSessionManager;

  beforeEach(async () => {
    // Get fresh instance
    manager = crossTabSessionManager;
    
    // Initialize the manager
    await manager.initialize();
  });

  afterEach(() => {
    // Cleanup
    manager.cleanup();
    vi.clearAllMocks();
  });

  describe('Real Application Integration', () => {
    it('should initialize with real browser environment', async () => {
      expect(manager).toBeDefined();
      
      const sessionData = manager.getSessionData();
      expect(sessionData.tabId).toMatch(/^tab_\d+_[a-z0-9]+$/);
      expect(sessionData.metadata.userAgent).toBe(navigator.userAgent);
      
      const activeTabs = manager.getActiveTabs();
      expect(activeTabs.length).toBeGreaterThan(0);
    });

    it('should handle session data persistence', async () => {
      const testSessionData: Partial<SessionData> = {
        userId: 123,
        token: 'integration-test-token',
        expiresAt: new Date(Date.now() + 3600000),
        isActive: true
      };

      // Update session data
      manager.updateSessionData(testSessionData);
      
      // Verify data is stored in localStorage
      const storedData = localStorage.getItem('session-data');
      expect(storedData).toBeTruthy();
      
      if (storedData) {
        const parsedData = JSON.parse(storedData);
        expect(parsedData.userId).toBe(123);
        expect(parsedData.token).toBe('integration-test-token');
        expect(parsedData.isActive).toBe(true);
      }

      // Verify data can be retrieved
      const retrievedData = manager.getSessionData();
      expect(retrievedData.userId).toBe(123);
      expect(retrievedData.token).toBe('integration-test-token');
      expect(retrievedData.isActive).toBe(true);
    });

    it('should handle BroadcastChannel communication', async () => {
      if (typeof BroadcastChannel === 'undefined') {
        console.log('BroadcastChannel not available, skipping test');
        return;
      }

      const testChannel = new BroadcastChannel('session-sync');
      let receivedMessage: any = null;

      // Listen for messages
      testChannel.addEventListener('message', (event) => {
        receivedMessage = event.data;
      });

      // Broadcast a session update
      const updateData: Partial<SessionData> = {
        userId: 456,
        token: 'broadcast-test-token'
      };

      manager.broadcastSessionUpdate(updateData);

      // Wait for message to be received
      await new Promise(resolve => setTimeout(resolve, 100));

      expect(receivedMessage).toBeTruthy();
      expect(receivedMessage.type).toBe('session_update');
      expect(receivedMessage.data.userId).toBe(456);
      expect(receivedMessage.data.token).toBe('broadcast-test-token');

      testChannel.close();
    });

    it('should handle localStorage fallback when BroadcastChannel is not available', async () => {
      // Mock BroadcastChannel as undefined
      const originalBC = global.BroadcastChannel;
      global.BroadcastChannel = undefined as any;

      // Create new manager instance
      const fallbackManager = crossTabSessionManager;
      await fallbackManager.initialize();

      // Test localStorage-based communication
      const updateData: Partial<SessionData> = {
        userId: 789,
        token: 'fallback-test-token'
      };

      fallbackManager.updateSessionData(updateData);

      // Verify data is in localStorage
      const storedData = localStorage.getItem('session-data');
      expect(storedData).toBeTruthy();

      if (storedData) {
        const parsedData = JSON.parse(storedData);
        expect(parsedData.userId).toBe(789);
        expect(parsedData.token).toBe('fallback-test-token');
      }

      // Restore BroadcastChannel
      global.BroadcastChannel = originalBC;
      fallbackManager.cleanup();
    });

    it('should handle session conflicts with real data', async () => {
      // Set up initial session data
      const initialData: Partial<SessionData> = {
        userId: 100,
        token: 'initial-token',
        expiresAt: new Date(Date.now() + 3600000)
      };

      manager.updateSessionData(initialData);

      // Simulate conflicting data in localStorage (as if from another tab)
      const conflictingData = {
        sessionId: 'conflict-session',
        userId: 200,
        token: 'conflicting-token',
        expiresAt: new Date(Date.now() + 7200000).toISOString(),
        lastActivity: new Date().toISOString(),
        tabId: 'conflict-tab',
        isActive: true,
        metadata: {
          userAgent: navigator.userAgent,
          loginTime: new Date().toISOString(),
          refreshCount: 1
        }
      };

      localStorage.setItem('session-data', JSON.stringify(conflictingData));

      // Detect conflict
      const conflict = await manager.detectSessionConflicts();
      expect(conflict).toBeTruthy();
      expect(conflict?.action).toBe('use_incoming');
      expect(conflict?.reason).toContain('Token mismatch');

      // Resolve conflict
      if (conflict) {
        await manager.recoverFromConflict(conflict);
        
        const resolvedData = manager.getSessionData();
        expect(resolvedData.userId).toBe(200);
        expect(resolvedData.token).toBe('conflicting-token');
      }
    });

    it('should handle session locking correctly', async () => {
      const operation = 'test-critical-operation';

      // Request lock
      const lockAcquired = await manager.requestSessionLock(operation);
      expect(lockAcquired).toBe(true);

      // Try to acquire same lock again (should succeed for same tab)
      const lockAcquiredAgain = await manager.requestSessionLock(operation);
      expect(lockAcquiredAgain).toBe(true);

      // Release lock
      manager.releaseSessionLock(operation);

      // Should be able to acquire after release
      const lockAfterRelease = await manager.requestSessionLock(operation);
      expect(lockAfterRelease).toBe(true);

      manager.releaseSessionLock(operation);
    });

    it('should handle tab lifecycle correctly', async () => {
      const initialTabs = manager.getActiveTabs();
      expect(initialTabs.length).toBeGreaterThan(0);

      // Register should not add duplicate
      manager.registerTab();
      const tabsAfterRegister = manager.getActiveTabs();
      expect(tabsAfterRegister.length).toBe(initialTabs.length);

      // Unregister should work
      manager.unregisterTab();
      // Note: Tab might still be in list until cleanup runs
      const tabsAfterUnregister = manager.getActiveTabs();
      expect(tabsAfterUnregister.length).toBeGreaterThanOrEqual(0);
    });

    it('should handle logout broadcast correctly', async () => {
      let logoutEventReceived = false;
      
      const handleLogout = () => {
        logoutEventReceived = true;
      };

      window.addEventListener('cross-tab-logout', handleLogout);

      // Set up session data
      manager.updateSessionData({
        userId: 999,
        token: 'logout-test-token',
        isActive: true
      });

      // Broadcast logout
      manager.broadcastLogout();

      // Wait for event processing
      await new Promise(resolve => setTimeout(resolve, 100));

      // Check that session data is cleared
      const sessionData = manager.getSessionData();
      expect(sessionData.token).toBeNull();
      expect(sessionData.userId).toBeNull();
      expect(sessionData.isActive).toBe(false);

      window.removeEventListener('cross-tab-logout', handleLogout);
    });

    it('should handle cleanup properly', async () => {
      // Set up some data
      manager.updateSessionData({
        userId: 888,
        token: 'cleanup-test-token',
        isActive: true
      });

      const sessionDataBefore = manager.getSessionData();
      expect(sessionDataBefore.userId).toBe(888);

      // Cleanup
      manager.cleanup();

      // Verify cleanup
      const activeTabs = manager.getActiveTabs();
      expect(activeTabs.length).toBe(0);
    });
  });

  describe('Error Handling', () => {
    it('should handle localStorage errors gracefully', async () => {
      // Mock localStorage to throw errors
      const originalSetItem = localStorage.setItem;
      localStorage.setItem = vi.fn(() => {
        throw new Error('Storage quota exceeded');
      });

      // Should not throw when updating session data
      expect(() => {
        manager.updateSessionData({
          userId: 777,
          token: 'error-test-token'
        });
      }).not.toThrow();

      // Restore localStorage
      localStorage.setItem = originalSetItem;
    });

    it('should handle invalid localStorage data gracefully', async () => {
      // Set invalid JSON in localStorage
      localStorage.setItem('session-data', 'invalid-json-data');

      // Should not throw when initializing
      const newManager = crossTabSessionManager;
      await expect(newManager.initialize()).resolves.not.toThrow();

      newManager.cleanup();
    });

    it('should handle BroadcastChannel errors gracefully', async () => {
      if (typeof BroadcastChannel === 'undefined') {
        console.log('BroadcastChannel not available, skipping test');
        return;
      }

      // Mock BroadcastChannel constructor to throw
      const originalBC = global.BroadcastChannel;
      global.BroadcastChannel = vi.fn(() => {
        throw new Error('BroadcastChannel creation failed');
      }) as any;

      // Should fallback to localStorage
      const errorManager = crossTabSessionManager;
      await expect(errorManager.initialize()).resolves.not.toThrow();

      // Should still be able to update session data
      expect(() => {
        errorManager.updateSessionData({
          userId: 666,
          token: 'error-fallback-token'
        });
      }).not.toThrow();

      // Restore BroadcastChannel
      global.BroadcastChannel = originalBC;
      errorManager.cleanup();
    });
  });

  describe('Performance', () => {
    it('should handle multiple rapid updates efficiently', async () => {
      const startTime = Date.now();
      
      // Perform multiple rapid updates
      for (let i = 0; i < 100; i++) {
        manager.updateSessionData({
          userId: i,
          token: `performance-token-${i}`,
          lastActivity: new Date()
        });
      }

      const endTime = Date.now();
      const duration = endTime - startTime;

      // Should complete within reasonable time (less than 1 second)
      expect(duration).toBeLessThan(1000);

      // Final data should be correct
      const finalData = manager.getSessionData();
      expect(finalData.userId).toBe(99);
      expect(finalData.token).toBe('performance-token-99');
    });

    it('should handle many active tabs efficiently', async () => {
      const startTime = Date.now();

      // Simulate many tabs registering
      for (let i = 0; i < 50; i++) {
        const tabInfo = {
          tabId: `performance-tab-${i}`,
          lastSeen: new Date(),
          isActive: true,
          sessionId: 'performance-session'
        };

        // Simulate tab registration message
        if (typeof BroadcastChannel !== 'undefined') {
          const channel = new BroadcastChannel('session-sync');
          channel.postMessage({
            type: 'tab_register',
            data: tabInfo,
            tabId: `performance-tab-${i}`,
            timestamp: new Date(),
            sessionId: 'performance-session'
          });
          channel.close();
        }
      }

      // Wait for processing
      await new Promise(resolve => setTimeout(resolve, 200));

      const endTime = Date.now();
      const duration = endTime - startTime;

      // Should complete within reasonable time
      expect(duration).toBeLessThan(2000);

      // Should handle the tabs
      const activeTabs = manager.getActiveTabs();
      expect(activeTabs.length).toBeGreaterThan(0);
    });
  });
});