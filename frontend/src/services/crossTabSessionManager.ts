// Core session manager - no Vue dependencies for better testability

export interface SessionData {
  sessionId: string;
  userId: number | null;
  token: string | null;
  expiresAt: Date | null;
  lastActivity: Date;
  tabId: string;
  isActive: boolean;
  metadata: {
    userAgent: string;
    loginTime: Date | null;
    refreshCount: number;
  };
}

export interface ConflictResolution {
  action: 'keep_current' | 'use_incoming' | 'merge' | 'logout_all';
  reason: string;
  timestamp: Date;
}

export interface TabInfo {
  tabId: string;
  lastSeen: Date;
  isActive: boolean;
  sessionId: string;
}

export interface SessionSyncMessage {
  type: 'session_update' | 'logout' | 'tab_register' | 'tab_unregister' | 'conflict_resolution' | 'heartbeat';
  data: any;
  tabId: string;
  timestamp: Date;
  sessionId: string;
}

class CrossTabSessionManager {
  private static instance: CrossTabSessionManager;
  private broadcastChannel: BroadcastChannel | null = null;
  private tabId: string;
  private sessionData: SessionData = {
    sessionId: '',
    userId: null,
    token: null,
    expiresAt: null,
    lastActivity: new Date(),
    tabId: '',
    isActive: false,
    metadata: {
      userAgent: navigator.userAgent,
      loginTime: null,
      refreshCount: 0,
    }
  };
  private activeTabs = new Map<string, TabInfo>();
  private sessionLocks = new Map<string, { tabId: string; timestamp: Date }>();
  private heartbeatInterval: number | null = null;
  private cleanupInterval: number | null = null;
  private isInitialized = false;
  private eventListeners: Array<() => void> = [];

  private constructor() {
    this.tabId = this.generateTabId();
    this.sessionData.tabId = this.tabId;
  }

  static getInstance(): CrossTabSessionManager {
    if (!CrossTabSessionManager.instance) {
      CrossTabSessionManager.instance = new CrossTabSessionManager();
    }
    return CrossTabSessionManager.instance;
  }

  // Initialize the cross-tab session manager
  async initialize(): Promise<void> {
    if (this.isInitialized) return;

    try {
      console.log(`[CrossTabSessionManager] Initializing for tab ${this.tabId}`);
      
      // Initialize BroadcastChannel
      this.initializeBroadcastChannel();
      
      // Load existing session data from localStorage
      await this.loadSessionFromStorage();
      
      // Register this tab
      this.registerTab();
      
      // Start heartbeat and cleanup intervals
      this.startHeartbeat();
      this.startCleanup();
      
      // Set up beforeunload handler
      this.setupBeforeUnloadHandler();
      
      this.isInitialized = true;
      console.log(`[CrossTabSessionManager] Initialized successfully for tab ${this.tabId}`);
      
    } catch (error) {
      console.error('[CrossTabSessionManager] Initialization failed:', error);
      throw error;
    }
  }

  // Initialize BroadcastChannel for cross-tab communication
  private initializeBroadcastChannel(): void {
    if (typeof BroadcastChannel === 'undefined') {
      console.warn('[CrossTabSessionManager] BroadcastChannel not supported, falling back to localStorage events');
      this.setupLocalStorageFallback();
      return;
    }

    try {
      this.broadcastChannel = new BroadcastChannel('session-sync');
      this.broadcastChannel.addEventListener('message', this.handleBroadcastMessage.bind(this));
      console.log('[CrossTabSessionManager] BroadcastChannel initialized');
    } catch (error) {
      console.error('[CrossTabSessionManager] Failed to initialize BroadcastChannel:', error);
      this.setupLocalStorageFallback();
    }
  }

  // Fallback to localStorage events for cross-tab communication
  private setupLocalStorageFallback(): void {
    const handleStorageEvent = (event: StorageEvent) => {
      if (event.key === 'session-sync-message' && event.newValue) {
        try {
          const message: SessionSyncMessage = JSON.parse(event.newValue);
          if (message.tabId !== this.tabId) {
            this.handleSyncMessage(message);
          }
        } catch (error) {
          console.error('[CrossTabSessionManager] Failed to parse localStorage message:', error);
        }
      }
    };

    window.addEventListener('storage', handleStorageEvent);
    this.eventListeners.push(() => {
      window.removeEventListener('storage', handleStorageEvent);
    });
  }

  // Handle BroadcastChannel messages
  private handleBroadcastMessage(event: MessageEvent<SessionSyncMessage>): void {
    const message = event.data;
    if (message.tabId !== this.tabId) {
      this.handleSyncMessage(message);
    }
  }

  // Handle session sync messages from other tabs
  private async handleSyncMessage(message: SessionSyncMessage): Promise<void> {
    console.log(`[CrossTabSessionManager] Received message from tab ${message.tabId}:`, message.type);

    switch (message.type) {
      case 'session_update':
        await this.handleSessionUpdate(message);
        break;
      case 'logout':
        await this.handleCrossTabLogout(message);
        break;
      case 'tab_register':
        this.handleTabRegister(message);
        break;
      case 'tab_unregister':
        this.handleTabUnregister(message);
        break;
      case 'conflict_resolution':
        await this.handleConflictResolution(message);
        break;
      case 'heartbeat':
        this.handleHeartbeat(message);
        break;
      default:
        console.warn(`[CrossTabSessionManager] Unknown message type: ${message.type}`);
    }
  }

  // Broadcast session update to other tabs
  broadcastSessionUpdate(sessionData: Partial<SessionData>): void {
    const message: SessionSyncMessage = {
      type: 'session_update',
      data: sessionData,
      tabId: this.tabId,
      timestamp: new Date(),
      sessionId: this.sessionData.sessionId
    };

    this.broadcastMessage(message);
    console.log(`[CrossTabSessionManager] Broadcasted session update from tab ${this.tabId}`);
  }

  // Subscribe to session updates
  subscribeToSessionUpdates(callback: (data: SessionData) => void): () => void {
    const unsubscribe = () => {
      // Remove callback from internal list if we implement one
    };

    // For now, we'll use reactive data binding
    // In a real implementation, you might want to maintain a list of callbacks
    return unsubscribe;
  }

  // Register this tab
  registerTab(): void {
    const tabInfo: TabInfo = {
      tabId: this.tabId,
      lastSeen: new Date(),
      isActive: true,
      sessionId: this.sessionData.sessionId
    };

    this.activeTabs.set(this.tabId, tabInfo);

    const message: SessionSyncMessage = {
      type: 'tab_register',
      data: tabInfo,
      tabId: this.tabId,
      timestamp: new Date(),
      sessionId: this.sessionData.sessionId
    };

    this.broadcastMessage(message);
    console.log(`[CrossTabSessionManager] Registered tab ${this.tabId}`);
  }

  // Unregister this tab
  unregisterTab(): void {
    this.activeTabs.delete(this.tabId);

    const message: SessionSyncMessage = {
      type: 'tab_unregister',
      data: { tabId: this.tabId },
      tabId: this.tabId,
      timestamp: new Date(),
      sessionId: this.sessionData.sessionId
    };

    this.broadcastMessage(message);
    console.log(`[CrossTabSessionManager] Unregistered tab ${this.tabId}`);
  }

  // Get list of active tabs
  getActiveTabs(): string[] {
    return Array.from(this.activeTabs.keys());
  }

  // Request session lock for critical operations
  async requestSessionLock(operation: string): Promise<boolean> {
    const lockKey = `lock_${operation}`;
    const existingLock = this.sessionLocks.get(lockKey);

    // Check if lock is already held by another tab
    if (existingLock && existingLock.tabId !== this.tabId) {
      const lockAge = Date.now() - existingLock.timestamp.getTime();
      // Auto-release locks older than 30 seconds
      if (lockAge < 30000) {
        console.log(`[CrossTabSessionManager] Lock ${operation} is held by tab ${existingLock.tabId}`);
        return false;
      }
    }

    // Acquire lock
    this.sessionLocks.set(lockKey, {
      tabId: this.tabId,
      timestamp: new Date()
    });

    console.log(`[CrossTabSessionManager] Acquired lock ${operation} for tab ${this.tabId}`);
    return true;
  }

  // Release session lock
  releaseSessionLock(operation: string): void {
    const lockKey = `lock_${operation}`;
    const existingLock = this.sessionLocks.get(lockKey);

    if (existingLock && existingLock.tabId === this.tabId) {
      this.sessionLocks.delete(lockKey);
      console.log(`[CrossTabSessionManager] Released lock ${operation} for tab ${this.tabId}`);
    }
  }

  // Detect session conflicts
  async detectSessionConflicts(): Promise<ConflictResolution | null> {
    const currentSession = this.getSessionData();
    const storedSession = this.loadSessionFromLocalStorage();

    if (!storedSession || !currentSession.token) {
      return null;
    }

    // Check for token mismatch
    if (storedSession.token !== currentSession.token && storedSession.token && currentSession.token) {
      return {
        action: 'use_incoming',
        reason: 'Token mismatch detected - using most recent token',
        timestamp: new Date()
      };
    }

    // Check for session expiry conflicts
    if (storedSession.expiresAt && currentSession.expiresAt) {
      const storedExpiry = new Date(storedSession.expiresAt);
      const currentExpiry = new Date(currentSession.expiresAt);
      
      if (Math.abs(storedExpiry.getTime() - currentExpiry.getTime()) > 60000) { // 1 minute difference
        return {
          action: 'use_incoming',
          reason: 'Session expiry mismatch - using most recent expiry',
          timestamp: new Date()
        };
      }
    }

    return null;
  }

  // Recover from session conflict
  async recoverFromConflict(resolution: ConflictResolution): Promise<void> {
    console.log(`[CrossTabSessionManager] Recovering from conflict: ${resolution.reason}`);

    switch (resolution.action) {
      case 'use_incoming':
        await this.loadSessionFromStorage();
        break;
      case 'keep_current':
        await this.saveSessionToStorage();
        break;
      case 'logout_all':
        await this.handleLogoutAll();
        break;
      case 'merge':
        // Implement merge logic if needed
        break;
    }

    // Broadcast conflict resolution to other tabs
    const message: SessionSyncMessage = {
      type: 'conflict_resolution',
      data: resolution,
      tabId: this.tabId,
      timestamp: new Date(),
      sessionId: this.sessionData.sessionId
    };

    this.broadcastMessage(message);
  }

  // Update session data
  updateSessionData(data: Partial<SessionData>): void {
    Object.assign(this.sessionData, data);
    this.sessionData.lastActivity = new Date();
    
    // Save to localStorage
    this.saveSessionToStorage();
    
    // Broadcast to other tabs
    this.broadcastSessionUpdate(data);
  }

  // Get current session data
  getSessionData(): SessionData {
    return { ...this.sessionData };
  }

  // Handle session update from another tab
  private async handleSessionUpdate(message: SessionSyncMessage): Promise<void> {
    const incomingData = message.data as Partial<SessionData>;
    
    // Check for conflicts
    const conflict = await this.detectSessionConflicts();
    if (conflict) {
      await this.recoverFromConflict(conflict);
      return;
    }

    // Update session data
    Object.assign(this.sessionData, incomingData);
    this.sessionData.lastActivity = new Date();
    
    console.log(`[CrossTabSessionManager] Updated session from tab ${message.tabId}`);
  }

  // Handle cross-tab logout
  private async handleCrossTabLogout(message: SessionSyncMessage): Promise<void> {
    console.log(`[CrossTabSessionManager] Received logout signal from tab ${message.tabId}`);
    
    // Clear session data
    this.clearSessionData();
    
    // Notify the application about logout
    window.dispatchEvent(new CustomEvent('cross-tab-logout', {
      detail: { initiatingTab: message.tabId }
    }));
  }

  // Handle tab registration from another tab
  private handleTabRegister(message: SessionSyncMessage): void {
    const tabInfo = message.data as TabInfo;
    this.activeTabs.set(tabInfo.tabId, tabInfo);
    console.log(`[CrossTabSessionManager] Tab ${tabInfo.tabId} registered`);
  }

  // Handle tab unregistration from another tab
  private handleTabUnregister(message: SessionSyncMessage): void {
    const { tabId } = message.data;
    this.activeTabs.delete(tabId);
    console.log(`[CrossTabSessionManager] Tab ${tabId} unregistered`);
  }

  // Handle conflict resolution from another tab
  private async handleConflictResolution(message: SessionSyncMessage): Promise<void> {
    const resolution = message.data as ConflictResolution;
    console.log(`[CrossTabSessionManager] Received conflict resolution from tab ${message.tabId}:`, resolution.reason);
    
    // Apply the resolution if it's newer than our last update
    if (resolution.timestamp > this.sessionData.lastActivity) {
      await this.recoverFromConflict(resolution);
    }
  }

  // Handle heartbeat from another tab
  private handleHeartbeat(message: SessionSyncMessage): void {
    const tabInfo = this.activeTabs.get(message.tabId);
    if (tabInfo) {
      tabInfo.lastSeen = new Date();
      tabInfo.isActive = true;
    }
  }

  // Broadcast logout to all tabs
  broadcastLogout(): void {
    const message: SessionSyncMessage = {
      type: 'logout',
      data: { reason: 'user_initiated' },
      tabId: this.tabId,
      timestamp: new Date(),
      sessionId: this.sessionData.sessionId
    };

    this.broadcastMessage(message);
    
    // Also clear local session data
    this.clearSessionData();
    
    console.log(`[CrossTabSessionManager] Broadcasted logout from tab ${this.tabId}`);
  }

  // Handle logout for all tabs
  private async handleLogoutAll(): Promise<void> {
    this.clearSessionData();
    this.broadcastLogout();
  }

  // Clear session data
  private clearSessionData(): void {
    this.sessionData.sessionId = '';
    this.sessionData.userId = null;
    this.sessionData.token = null;
    this.sessionData.expiresAt = null;
    this.sessionData.isActive = false;
    this.sessionData.metadata.loginTime = null;
    this.sessionData.metadata.refreshCount = 0;
    
    // Clear from localStorage
    localStorage.removeItem('session-data');
    localStorage.removeItem('auth_token');
  }

  // Load session data from localStorage
  private loadSessionFromLocalStorage(): SessionData | null {
    try {
      const stored = localStorage.getItem('session-data');
      if (stored) {
        const data = JSON.parse(stored);
        return {
          ...data,
          expiresAt: data.expiresAt ? new Date(data.expiresAt) : null,
          lastActivity: new Date(data.lastActivity),
          metadata: {
            ...data.metadata,
            loginTime: data.metadata.loginTime ? new Date(data.metadata.loginTime) : null
          }
        };
      }
    } catch (error) {
      console.error('[CrossTabSessionManager] Failed to load session from localStorage:', error);
    }
    return null;
  }

  // Load session data from storage
  private async loadSessionFromStorage(): Promise<void> {
    const stored = this.loadSessionFromLocalStorage();
    if (stored) {
      Object.assign(this.sessionData, stored);
      this.sessionData.tabId = this.tabId; // Ensure correct tab ID
      console.log(`[CrossTabSessionManager] Loaded session from storage for tab ${this.tabId}`);
    }
  }

  // Save session data to localStorage
  private saveSessionToStorage(): void {
    try {
      const dataToStore = {
        ...this.sessionData,
        expiresAt: this.sessionData.expiresAt?.toISOString() || null,
        lastActivity: this.sessionData.lastActivity.toISOString(),
        metadata: {
          ...this.sessionData.metadata,
          loginTime: this.sessionData.metadata.loginTime?.toISOString() || null
        }
      };
      localStorage.setItem('session-data', JSON.stringify(dataToStore));
    } catch (error) {
      console.error('[CrossTabSessionManager] Failed to save session to localStorage:', error);
    }
  }

  // Broadcast message to other tabs
  private broadcastMessage(message: SessionSyncMessage): void {
    if (this.broadcastChannel) {
      this.broadcastChannel.postMessage(message);
    } else {
      // Fallback to localStorage
      localStorage.setItem('session-sync-message', JSON.stringify(message));
      // Clear the message after a short delay to trigger storage event
      setTimeout(() => {
        localStorage.removeItem('session-sync-message');
      }, 100);
    }
  }

  // Start heartbeat to keep tab alive
  private startHeartbeat(): void {
    this.heartbeatInterval = window.setInterval(() => {
      const message: SessionSyncMessage = {
        type: 'heartbeat',
        data: { tabId: this.tabId },
        tabId: this.tabId,
        timestamp: new Date(),
        sessionId: this.sessionData.sessionId
      };
      this.broadcastMessage(message);
    }, 2 * 60 * 1000); // Every 2 minutes (reduced from 30 seconds)
  }

  // Start cleanup interval for inactive tabs
  private startCleanup(): void {
    this.cleanupInterval = window.setInterval(() => {
      const now = new Date();
      const inactiveThreshold = 2 * 60 * 1000; // 2 minutes

      for (const [tabId, tabInfo] of this.activeTabs.entries()) {
        if (now.getTime() - tabInfo.lastSeen.getTime() > inactiveThreshold) {
          this.activeTabs.delete(tabId);
          console.log(`[CrossTabSessionManager] Cleaned up inactive tab ${tabId}`);
        }
      }

      // Clean up old locks
      for (const [lockKey, lock] of this.sessionLocks.entries()) {
        if (now.getTime() - lock.timestamp.getTime() > 60000) { // 1 minute
          this.sessionLocks.delete(lockKey);
          console.log(`[CrossTabSessionManager] Cleaned up expired lock ${lockKey}`);
        }
      }
    }, 60000); // Every minute
  }

  // Setup beforeunload handler
  private setupBeforeUnloadHandler(): void {
    const handleBeforeUnload = () => {
      this.unregisterTab();
      this.cleanup();
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    this.eventListeners.push(() => {
      window.removeEventListener('beforeunload', handleBeforeUnload);
    });
  }

  // Generate unique tab ID
  private generateTabId(): string {
    return `tab_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  // Cleanup resources
  cleanup(): void {
    if (this.heartbeatInterval) {
      clearInterval(this.heartbeatInterval);
      this.heartbeatInterval = null;
    }

    if (this.cleanupInterval) {
      clearInterval(this.cleanupInterval);
      this.cleanupInterval = null;
    }

    if (this.broadcastChannel) {
      this.broadcastChannel.close();
      this.broadcastChannel = null;
    }

    // Remove event listeners
    this.eventListeners.forEach(cleanup => cleanup());
    this.eventListeners = [];

    // Clear active tabs
    this.activeTabs.clear();
    
    // Clear session locks
    this.sessionLocks.clear();

    this.isInitialized = false;
    console.log(`[CrossTabSessionManager] Cleaned up for tab ${this.tabId}`);
  }
}

// Export singleton instance
export const crossTabSessionManager = CrossTabSessionManager.getInstance();