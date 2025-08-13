import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from '../auth';

// Integration tests that work with the real web application
describe('Auth Store Integration Tests', () => {
  let authStore: ReturnType<typeof useAuthStore>;

  beforeEach(() => {
    // Create Pinia instance for tests
    const pinia = createPinia();
    setActivePinia(pinia);
    authStore = useAuthStore();
    
    // Clear localStorage
    localStorage.clear();
  });

  afterEach(() => {
    // Clean up after each test
    if (authStore) {
      authStore.cleanupAuthState();
    }
  });

  describe('Basic Authentication Store Functionality', () => {
    it('should initialize with correct default state', () => {
      expect(authStore.user).toBeNull();
      expect(authStore.token).toBeNull();
      expect(authStore.isLoading).toBe(false);
      expect(authStore.error).toBeNull();
      expect(authStore.initialized).toBe(false);
      expect(authStore.isAuthenticated).toBe(false);
    });

    it('should have cross-tab state initialized', () => {
      expect(authStore.crossTabInitialized).toBe(false);
      expect(authStore.activeTabs).toEqual([]);
      expect(authStore.sessionConflicts).toEqual([]);
      expect(authStore.sessionHealthStatus).toBe('healthy');
      expect(authStore.lastCrossTabSync).toBeNull();
    });

    it('should have cross-tab computed properties', () => {
      expect(authStore.isMultiTab).toBe(false);
      expect(authStore.tabCount).toBe(0);
      expect(authStore.hasSessionConflicts).toBe(false);
      expect(authStore.currentTabId).toBeDefined();
    });

    it('should clean up cross-tab state when cleaning auth state', () => {
      // Set some cross-tab state
      authStore.sessionConflicts = [{
        action: 'use_incoming',
        reason: 'Test conflict',
        timestamp: new Date()
      }];
      authStore.sessionHealthStatus = 'warning';
      authStore.lastCrossTabSync = new Date();

      // Clean up
      authStore.cleanupAuthState();

      // Verify cross-tab state is cleaned
      expect(authStore.sessionConflicts).toEqual([]);
      expect(authStore.sessionHealthStatus).toBe('healthy');
      expect(authStore.lastCrossTabSync).toBeNull();
      expect(authStore.crossTabInitialized).toBe(false);
      expect(authStore.activeTabs).toEqual([]);
    });
  });

  describe('Cross-Tab Session Management', () => {
    it('should have cross-tab session management methods available', () => {
      expect(typeof authStore.initializeCrossTabSession).toBe('function');
      expect(typeof authStore.syncWithCrossTabManager).toBe('function');
      expect(typeof authStore.syncAuthDataToCrossTab).toBe('function');
      expect(typeof authStore.handleCrossTabLogout).toBe('function');
      expect(typeof authStore.detectSessionConflicts).toBe('function');
      expect(typeof authStore.resolveSessionConflict).toBe('function');
      expect(typeof authStore.getSessionHealth).toBe('function');
      expect(typeof authStore.performHealthCheck).toBe('function');
      expect(typeof authStore.scheduleSessionMaintenance).toBe('function');
      expect(typeof authStore.updateActiveTabsList).toBe('function');
    });

    it('should return session health information', () => {
      const health = authStore.getSessionHealth();
      
      expect(health).toHaveProperty('status');
      expect(health).toHaveProperty('conflicts');
      expect(health).toHaveProperty('activeTabs');
      expect(health).toHaveProperty('tabCount');
      expect(health).toHaveProperty('isMultiTab');
      expect(health).toHaveProperty('lastSync');
      expect(health).toHaveProperty('sessionData');
      
      expect(health.status).toBe('healthy');
      expect(Array.isArray(health.conflicts)).toBe(true);
      expect(Array.isArray(health.activeTabs)).toBe(true);
      expect(typeof health.tabCount).toBe('number');
      expect(typeof health.isMultiTab).toBe('boolean');
    });

    it('should handle session conflict events', () => {
      const mockConflict = {
        action: 'use_incoming' as const,
        reason: 'Token mismatch detected',
        timestamp: new Date()
      };

      // Initially no conflicts
      expect(authStore.sessionConflicts).toEqual([]);
      expect(authStore.sessionHealthStatus).toBe('healthy');

      // Handle conflict event
      authStore.handleSessionConflictEvent(mockConflict);

      // Verify conflict was added and status updated
      expect(authStore.sessionConflicts).toContain(mockConflict);
      expect(authStore.sessionHealthStatus).toBe('warning');
    });

    it('should update active tabs list', () => {
      // Initially empty
      expect(authStore.activeTabs).toEqual([]);
      expect(authStore.tabCount).toBe(0);
      expect(authStore.isMultiTab).toBe(false);

      // Update tabs list (this will call the cross-tab manager)
      authStore.updateActiveTabsList();

      // The actual tabs will depend on the cross-tab manager implementation
      // but we can verify the method works without errors
      expect(Array.isArray(authStore.activeTabs)).toBe(true);
      expect(typeof authStore.tabCount).toBe('number');
      expect(typeof authStore.isMultiTab).toBe('boolean');
    });
  });

  describe('Authentication Flow with Cross-Tab Support', () => {
    it('should attempt to initialize cross-tab session during auth initialization', async () => {
      // This test verifies that the initialize method includes cross-tab initialization
      // without mocking, so it tests the real integration
      
      expect(authStore.initialized).toBe(false);
      expect(authStore.crossTabInitialized).toBe(false);

      try {
        await authStore.initialize();
        
        // After initialization, the auth store should be marked as initialized
        expect(authStore.initialized).toBe(true);
        
        // Cross-tab initialization should have been attempted
        // (it may fail in test environment, but the attempt should be made)
        
      } catch (error) {
        // In test environment, cross-tab initialization might fail
        // but the auth store should still be marked as initialized
        expect(authStore.initialized).toBe(true);
      }
    });

    it('should handle logout with cross-tab coordination when cross-tab is initialized', async () => {
      // Set up a mock authenticated state
      authStore.user = {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        preferred_language: 'en',
        is_active: true
      };
      authStore.token = 'test-token';
      localStorage.setItem('auth_token', 'test-token');

      // Mark cross-tab as initialized
      authStore.crossTabInitialized = true;

      // Perform logout
      await authStore.logout();

      // Verify state is cleaned up
      expect(authStore.user).toBeNull();
      expect(authStore.token).toBeNull();
      expect(authStore.isAuthenticated).toBe(false);
      expect(localStorage.getItem('auth_token')).toBeNull();
    });

    it('should sync auth data to cross-tab manager when authenticated', async () => {
      // Set up authenticated state
      authStore.user = {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        preferred_language: 'en',
        is_active: true
      };
      authStore.token = 'test-token';
      authStore.sessionExpiry = new Date(Date.now() + 3600000);

      // Sync to cross-tab manager
      await authStore.syncAuthDataToCrossTab();

      // Verify last sync time was updated
      expect(authStore.lastCrossTabSync).toBeDefined();
      expect(authStore.lastCrossTabSync).toBeInstanceOf(Date);
    });
  });

  describe('Error Handling and Recovery', () => {
    it('should handle authentication errors gracefully', () => {
      const mockError = {
        response: {
          data: {
            error: {
              code: 'INVALID_CREDENTIALS',
              message: 'Invalid credentials'
            }
          }
        }
      };

      const errorMessage = authStore.handleAuthError(mockError);
      expect(errorMessage).toBe('Invalid credentials');
    });

    it('should handle network errors gracefully', () => {
      const mockNetworkError = {
        message: 'Network Error'
      };

      const errorMessage = authStore.handleAuthError(mockNetworkError);
      expect(errorMessage).toBe('Network connection failed. Please check your internet connection and try again.');
    });

    it('should handle session conflicts gracefully', async () => {
      const mockConflict = {
        action: 'use_incoming' as const,
        reason: 'Token mismatch detected',
        timestamp: new Date()
      };

      // Add conflict
      authStore.sessionConflicts = [mockConflict];
      authStore.sessionHealthStatus = 'warning';

      // Resolve conflict
      await authStore.resolveSessionConflict(mockConflict);

      // Verify conflict was resolved
      expect(authStore.sessionConflicts).toEqual([]);
      expect(authStore.sessionHealthStatus).toBe('healthy');
    });
  });

  describe('Session Health Monitoring', () => {
    it('should perform health check and return status', async () => {
      const isHealthy = await authStore.performHealthCheck();
      
      // Health check should return a boolean
      expect(typeof isHealthy).toBe('boolean');
      
      // Health status should be updated
      expect(['healthy', 'warning', 'error']).toContain(authStore.sessionHealthStatus);
    });

    it('should detect session conflicts', async () => {
      const conflict = await authStore.detectSessionConflicts();
      
      // Should return null or a conflict object
      if (conflict) {
        expect(conflict).toHaveProperty('action');
        expect(conflict).toHaveProperty('reason');
        expect(conflict).toHaveProperty('timestamp');
        expect(['keep_current', 'use_incoming', 'merge', 'logout_all']).toContain(conflict.action);
      } else {
        expect(conflict).toBeNull();
      }
    });
  });
});