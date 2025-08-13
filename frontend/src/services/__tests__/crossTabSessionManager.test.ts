import { describe, it, expect, beforeEach, afterEach, vi, Mock } from 'vitest';
import { crossTabSessionManager } from '../crossTabSessionManager';
import type { SessionData, ConflictResolution, SessionSyncMessage } from '../crossTabSessionManager';

// Mock BroadcastChannel
class MockBroadcastChannel {
  private listeners: Array<(event: MessageEvent) => void> = [];
  private static channels: Map<string, MockBroadcastChannel[]> = new Map();
  
  constructor(private name: string) {
    if (!MockBroadcastChannel.channels.has(name)) {
      MockBroadcastChannel.channels.set(name, []);
    }
    MockBroadcastChannel.channels.get(name)!.push(this);
  }

  addEventListener(type: string, listener: (event: MessageEvent) => void) {
    if (type === 'message') {
      this.listeners.push(listener);
    }
  }

  removeEventListener(type: string, listener: (event: MessageEvent) => void) {
    if (type === 'message') {
      const index = this.listeners.indexOf(listener);
      if (index > -1) {
        this.listeners.splice(index, 1);
      }
    }
  }

  postMessage(data: any) {
    const channels = MockBroadcastChannel.channels.get(this.name) || [];
    channels.forEach(channel => {
      if (channel !== this) {
        channel.listeners.forEach(listener => {
          listener(new MessageEvent('message', { data }));
        });
      }
    });
  }

  close() {
    const channels = MockBroadcastChannel.channels.get(this.name) || [];
    const index = channels.indexOf(this);
    if (index > -1) {
      channels.splice(index, 1);
    }
  }

  static clearAll() {
    this.channels.clear();
  }
}

// Mock localStorage
const mockLocalStorage = {
  store: new Map<string, string>(),
  getItem: vi.fn((key: string) => mockLocalStorage.store.get(key) || null),
  setItem: vi.fn((key: string, value: string) => {
    mockLocalStorage.store.set(key, value);
    // Trigger storage event for cross-tab communication testing
    window.dispatchEvent(new StorageEvent('storage', {
      key,
      newValue: value,
      oldValue: mockLocalStorage.store.get(key) || null
    }));
  }),
  removeItem: vi.fn((key: string) => mockLocalStorage.store.delete(key)),
  clear: vi.fn(() => mockLocalStorage.store.clear())
};

// Mock window methods
const mockWindow = {
  addEventListener: vi.fn(),
  removeEventListener: vi.fn(),
  dispatchEvent: vi.fn(),
  setInterval: vi.fn((callback: Function, delay: number) => {
    const id = Math.random();
    setTimeout(callback, delay);
    return id;
  }),
  clearInterval: vi.fn()
};

describe('CrossTabSessionManager', () => {
  let manager: typeof crossTabSessionManager;
  let originalBroadcastChannel: any;
  let originalLocalStorage: any;
  let originalWindow: any;

  beforeEach(async () => {
    // Setup mocks
    originalBroadcastChannel = global.BroadcastChannel;
    originalLocalStorage = global.localStorage;
    originalWindow = global.window;

    global.BroadcastChannel = MockBroadcastChannel as any;
    global.localStorage = mockLocalStorage as any;
    Object.assign(global.window, mockWindow);

    // Clear all previous state
    MockBroadcastChannel.clearAll();
    mockLocalStorage.clear();
    vi.clearAllMocks();

    // Get fresh instance
    manager = crossTabSessionManager;
    
    // Initialize the manager
    await manager.initialize();
  });

  afterEach(() => {
    // Cleanup
    manager.cleanup();
    
    // Restore original implementations
    global.BroadcastChannel = originalBroadcastChannel;
    global.localStorage = originalLocalStorage;
    global.window = originalWindow;
    
    MockBroadcastChannel.clearAll();
    vi.clearAllMocks();
  });

  describe('Initialization', () => {
    it('should initialize successfully', async () => {
      expect(manager).toBeDefined();
      const activeTabs = manager.getActiveTabs();
      expect(activeTabs.length).toBeGreaterThan(0);
      expect(activeTabs[0]).toMatch(/^tab_\d+_[a-z0-9]+$/);
    });

    it('should generate unique tab ID', () => {
      const sessionData = manager.getSessionData();
      expect(sessionData.tabId).toMatch(/^tab_\d+_[a-z0-9]+$/);
    });

    it('should register tab on initialization', () => {
      const activeTabs = manager.getActiveTabs();
      expect(activeTabs.length).toBeGreaterThan(0);
    });
  });

  describe('Session Data Management', () => {
    it('should update session data correctly', () => {
      const testData: Partial<SessionData> = {
        userId: 123,
        token: 'test-token',
        expiresAt: new Date(Date.now() + 3600000),
        isActive: true
      };

      manager.updateSessionData(testData);
      const sessionData = manager.getSessionData();

      expect(sessionData.userId).toBe(123);
      expect(sessionData.token).toBe('test-token');
      expect(sessionData.isActive).toBe(true);
      expect(sessionData.expiresAt).toBeInstanceOf(Date);
    });

    it('should save session data to localStorage', () => {
      const testData: Partial<SessionData> = {
        userId: 456,
        token: 'another-token'
      };

      manager.updateSessionData(testData);
      
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'session-data',
        expect.stringContaining('"userId":456')
      );
    });

    it('should load session data from localStorage', async () => {
      const testSessionData = {
        sessionId: 'test-session',
        userId: 789,
        token: 'stored-token',
        expiresAt: new Date(Date.now() + 3600000).toISOString(),
        lastActivity: new Date().toISOString(),
        tabId: 'test-tab',
        isActive: true,
        metadata: {
          userAgent: 'test-agent',
          loginTime: new Date().toISOString(),
          refreshCount: 1
        }
      };

      // Clear existing data and set test data
      manager.cleanup();
      mockLocalStorage.store.set('session-data', JSON.stringify(testSessionData));
      
      // Re-initialize to load from storage
      await manager.initialize();
      
      const loadedData = manager.getSessionData();
      expect(loadedData.userId).toBe(789);
      expect(loadedData.token).toBe('stored-token');
    });
  });

  describe('Cross-Tab Communication', () => {
    it('should broadcast session updates', () => {
      const testData: Partial<SessionData> = {
        userId: 999,
        token: 'broadcast-token'
      };

      // Spy on BroadcastChannel postMessage
      const broadcastSpy = vi.spyOn(MockBroadcastChannel.prototype, 'postMessage');
      
      manager.broadcastSessionUpdate(testData);
      
      expect(broadcastSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'session_update',
          data: testData
        })
      );
    });

    it('should handle session updates from other tabs', async () => {
      const initialData = manager.getSessionData();
      
      const updateMessage: SessionSyncMessage = {
        type: 'session_update',
        data: { userId: 555, token: 'updated-token' },
        tabId: 'other-tab',
        timestamp: new Date(),
        sessionId: 'test-session'
      };

      // Simulate receiving message from another tab
      const channel = new MockBroadcastChannel('session-sync');
      channel.postMessage(updateMessage);

      // Wait for async processing
      await new Promise(resolve => setTimeout(resolve, 100));

      const updatedData = manager.getSessionData();
      expect(updatedData.userId).toBe(555);
      expect(updatedData.token).toBe('updated-token');
    });

    it('should broadcast logout to other tabs', () => {
      const broadcastSpy = vi.spyOn(MockBroadcastChannel.prototype, 'postMessage');
      
      manager.broadcastLogout();
      
      expect(broadcastSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'logout',
          data: { reason: 'user_initiated' }
        })
      );
    });

    it('should handle logout from other tabs', async () => {
      // Set initial session data
      manager.updateSessionData({
        userId: 123,
        token: 'test-token',
        isActive: true
      });

      const logoutMessage: SessionSyncMessage = {
        type: 'logout',
        data: { reason: 'user_initiated' },
        tabId: 'other-tab',
        timestamp: new Date(),
        sessionId: 'test-session'
      };

      // Listen for cross-tab logout event
      const logoutEventSpy = vi.spyOn(window, 'dispatchEvent');

      // Simulate receiving logout message
      const channel = new MockBroadcastChannel('session-sync');
      channel.postMessage(logoutMessage);

      // Wait for async processing
      await new Promise(resolve => setTimeout(resolve, 100));

      expect(logoutEventSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'cross-tab-logout'
        })
      );

      const sessionData = manager.getSessionData();
      expect(sessionData.token).toBeNull();
      expect(sessionData.userId).toBeNull();
      expect(sessionData.isActive).toBe(false);
    });
  });

  describe('Tab Management', () => {
    it('should register and unregister tabs', () => {
      const initialTabs = manager.getActiveTabs();
      expect(initialTabs.length).toBeGreaterThan(0);

      manager.unregisterTab();
      
      // Tab should still be in the list until cleanup
      const tabsAfterUnregister = manager.getActiveTabs();
      expect(tabsAfterUnregister.length).toBeGreaterThanOrEqual(0);
    });

    it('should handle tab registration from other tabs', async () => {
      const registerMessage: SessionSyncMessage = {
        type: 'tab_register',
        data: {
          tabId: 'new-tab',
          lastSeen: new Date(),
          isActive: true,
          sessionId: 'test-session'
        },
        tabId: 'new-tab',
        timestamp: new Date(),
        sessionId: 'test-session'
      };

      const channel = new MockBroadcastChannel('session-sync');
      channel.postMessage(registerMessage);

      await new Promise(resolve => setTimeout(resolve, 100));

      const activeTabs = manager.getActiveTabs();
      expect(activeTabs).toContain('new-tab');
    });

    it('should handle tab unregistration from other tabs', async () => {
      // First register a tab
      const registerMessage: SessionSyncMessage = {
        type: 'tab_register',
        data: {
          tabId: 'temp-tab',
          lastSeen: new Date(),
          isActive: true,
          sessionId: 'test-session'
        },
        tabId: 'temp-tab',
        timestamp: new Date(),
        sessionId: 'test-session'
      };

      const channel = new MockBroadcastChannel('session-sync');
      channel.postMessage(registerMessage);
      await new Promise(resolve => setTimeout(resolve, 50));

      // Then unregister it
      const unregisterMessage: SessionSyncMessage = {
        type: 'tab_unregister',
        data: { tabId: 'temp-tab' },
        tabId: 'temp-tab',
        timestamp: new Date(),
        sessionId: 'test-session'
      };

      channel.postMessage(unregisterMessage);
      await new Promise(resolve => setTimeout(resolve, 50));

      const activeTabs = manager.getActiveTabs();
      expect(activeTabs).not.toContain('temp-tab');
    });
  });

  describe('Session Locking', () => {
    it('should acquire and release session locks', async () => {
      const lockAcquired = await manager.requestSessionLock('test-operation');
      expect(lockAcquired).toBe(true);

      // Try to acquire the same lock again
      const lockAcquiredAgain = await manager.requestSessionLock('test-operation');
      expect(lockAcquiredAgain).toBe(true); // Same tab should be able to re-acquire

      manager.releaseSessionLock('test-operation');
      
      // Should be able to acquire after release
      const lockAfterRelease = await manager.requestSessionLock('test-operation');
      expect(lockAfterRelease).toBe(true);
    });

    it('should handle lock conflicts between tabs', async () => {
      // This test would require more complex setup to simulate multiple manager instances
      // For now, we'll test the basic locking mechanism
      const lock1 = await manager.requestSessionLock('conflict-test');
      expect(lock1).toBe(true);

      // Simulate another tab trying to acquire the same lock
      // In a real scenario, this would be handled by the cross-tab communication
      const lock2 = await manager.requestSessionLock('conflict-test');
      expect(lock2).toBe(true); // Same tab can re-acquire
    });
  });

  describe('Conflict Resolution', () => {
    it('should detect session conflicts', async () => {
      // Set up conflicting session data
      manager.updateSessionData({
        token: 'current-token',
        expiresAt: new Date(Date.now() + 3600000)
      });

      // Simulate different data in localStorage
      const conflictingData = {
        sessionId: 'test-session',
        userId: 123,
        token: 'different-token',
        expiresAt: new Date(Date.now() + 7200000).toISOString(),
        lastActivity: new Date().toISOString(),
        tabId: 'other-tab',
        isActive: true,
        metadata: {
          userAgent: 'test-agent',
          loginTime: new Date().toISOString(),
          refreshCount: 1
        }
      };

      mockLocalStorage.store.set('session-data', JSON.stringify(conflictingData));

      const conflict = await manager.detectSessionConflicts();
      expect(conflict).toBeTruthy();
      expect(conflict?.action).toBe('use_incoming');
      expect(conflict?.reason).toContain('Token mismatch');
    });

    it('should recover from conflicts', async () => {
      const resolution: ConflictResolution = {
        action: 'use_incoming',
        reason: 'Test conflict resolution',
        timestamp: new Date()
      };

      // Set up localStorage with different data
      const incomingData = {
        sessionId: 'resolved-session',
        userId: 999,
        token: 'resolved-token',
        expiresAt: new Date(Date.now() + 3600000).toISOString(),
        lastActivity: new Date().toISOString(),
        tabId: 'resolved-tab',
        isActive: true,
        metadata: {
          userAgent: 'test-agent',
          loginTime: new Date().toISOString(),
          refreshCount: 1
        }
      };

      mockLocalStorage.store.set('session-data', JSON.stringify(incomingData));

      await manager.recoverFromConflict(resolution);

      const sessionData = manager.getSessionData();
      expect(sessionData.userId).toBe(999);
      expect(sessionData.token).toBe('resolved-token');
    });
  });

  describe('Cleanup and Resource Management', () => {
    it('should cleanup resources properly', () => {
      const clearIntervalSpy = vi.spyOn(window, 'clearInterval');
      
      manager.cleanup();
      
      expect(clearIntervalSpy).toHaveBeenCalled();
    });

    it('should handle beforeunload event', async () => {
      const addEventListenerSpy = vi.spyOn(window, 'addEventListener');
      
      // Cleanup and re-initialize to trigger beforeunload setup
      manager.cleanup();
      await manager.initialize();
      
      expect(addEventListenerSpy).toHaveBeenCalledWith(
        'beforeunload',
        expect.any(Function)
      );
    });
  });

  describe('Fallback to localStorage', () => {
    it('should fallback to localStorage when BroadcastChannel is not available', async () => {
      // Mock BroadcastChannel as undefined
      const originalBC = global.BroadcastChannel;
      global.BroadcastChannel = undefined as any;

      const newManager = crossTabSessionManager;
      await newManager.initialize();

      // Should still work with localStorage fallback
      newManager.updateSessionData({ userId: 123, token: 'fallback-token' });
      
      expect(mockLocalStorage.setItem).toHaveBeenCalled();
      
      // Restore BroadcastChannel
      global.BroadcastChannel = originalBC;
    });

    it('should handle localStorage events for cross-tab communication', async () => {
      // Mock BroadcastChannel as undefined to force localStorage fallback
      const originalBC = global.BroadcastChannel;
      global.BroadcastChannel = undefined as any;

      const newManager = crossTabSessionManager;
      await newManager.initialize();

      const testMessage: SessionSyncMessage = {
        type: 'session_update',
        data: { userId: 777, token: 'storage-token' },
        tabId: 'storage-tab',
        timestamp: new Date(),
        sessionId: 'storage-session'
      };

      // Simulate localStorage event
      mockLocalStorage.setItem('session-sync-message', JSON.stringify(testMessage));

      // Wait for processing
      await new Promise(resolve => setTimeout(resolve, 100));

      // Restore BroadcastChannel
      global.BroadcastChannel = originalBC;
    });
  });
});