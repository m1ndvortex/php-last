import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { apiService } from "@/services/api";
import router from "@/router";
import { crossTabSessionManager } from "@/services/crossTabSessionManager";
import { ReliableLogoutManagerImpl } from "@/services/reliableLogoutManager";
import type { SessionData, ConflictResolution } from "@/services/crossTabSessionManager";
import type { LogoutResult } from "@/types/auth";

export interface User {
  id: number;
  name: string;
  email: string;
  preferred_language: string;
  role?: string;
  is_active: boolean;
  last_login_at?: string;
  two_factor_enabled?: boolean;
  session_timeout?: number;
}

export interface LoginCredentials {
  email: string;
  password: string;
  remember?: boolean;
}

export interface AuthResult {
  success: boolean;
  error?: string | null;
  requiresTwoFactor?: boolean;
}

export interface SessionInfo {
  expires_at: string;
  time_remaining_minutes: number;
  is_expiring_soon: boolean;
  server_time: string;
  can_extend: boolean;
}

export const useAuthStore = defineStore("auth", () => {
  // State
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem("auth_token"));
  const refreshToken = ref<string | null>(localStorage.getItem("refresh_token"));
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const initialized = ref(false);
  const sessionExpiry = ref<Date | null>(null);
  const lastActivity = ref<Date>(new Date());
  const sessionTimeout = ref<number | null>(null);
  const sessionSyncInterval = ref<number | null>(null);
  const activityListeners = ref<(() => void)[]>([]);
  const retryCount = ref(0);
  const maxRetries = 3;
  
  // Cross-tab session management state
  const crossTabInitialized = ref(false);
  const activeTabs = ref<string[]>([]);
  const sessionConflicts = ref<ConflictResolution[]>([]);
  const crossTabEventListeners = ref<(() => void)[]>([]);
  const sessionHealthStatus = ref<'healthy' | 'warning' | 'error'>('healthy');
  const lastCrossTabSync = ref<Date | null>(null);
  
  // Reliable logout manager
  const logoutManager = new ReliableLogoutManagerImpl(crossTabSessionManager);

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value);
  const userInitials = computed(() => {
    if (!user.value) return "";
    return user.value.name
      .split(" ")
      .map((name) => name.charAt(0))
      .join("")
      .toUpperCase()
      .slice(0, 2);
  });

  const sessionTimeRemaining = computed(() => {
    if (!sessionExpiry.value) return 0;
    const now = new Date();
    const remaining = Math.max(0, Math.floor((sessionExpiry.value.getTime() - now.getTime()) / (1000 * 60)));
    return remaining;
  });

  const isSessionExpiringSoon = computed(() => {
    return sessionTimeRemaining.value > 0 && sessionTimeRemaining.value <= 5;
  });

  // Cross-tab computed properties
  const isMultiTab = computed(() => activeTabs.value.length > 1);
  const tabCount = computed(() => activeTabs.value.length);
  const hasSessionConflicts = computed(() => sessionConflicts.value.length > 0);
  const currentTabId = computed(() => crossTabSessionManager.getSessionData().tabId);

  // Enhanced login with retry logic and better error handling
  const login = async (credentials: LoginCredentials): Promise<AuthResult> => {
    const attemptLogin = async (attempt: number): Promise<AuthResult> => {
      try {
        isLoading.value = true;
        error.value = null;

        const response = await apiService.auth.login(credentials);
        
        if (!response.data.success) {
          throw new Error(response.data.error?.message || "Login failed");
        }

        const { user: userData, token: authToken, session_expiry } = response.data.data;

        // Store auth data
        user.value = userData;
        token.value = authToken;
        localStorage.setItem("auth_token", authToken);

        // Set session expiry
        if (session_expiry) {
          sessionExpiry.value = new Date(session_expiry);
        }

        // Set user's preferred language
        if (userData.preferred_language) {
          localStorage.setItem("preferred-language", userData.preferred_language);
        }

        // Start session management
        await startSessionManagement();

        // Initialize cross-tab session management
        if (!crossTabInitialized.value) {
          await initializeCrossTabSession();
        } else {
          // Sync with existing cross-tab session
          await syncAuthDataToCrossTab();
        }

        // Reset retry count on success
        retryCount.value = 0;

        return { success: true };

      } catch (err: any) {
        console.error(`Login attempt ${attempt} failed:`, err);
        
        const errorData = err.response?.data?.error;
        const isRetryable = errorData?.retryable !== false;
        const isNetworkError = !err.response;
        
        // Handle specific error types
        if (errorData?.code === 'RATE_LIMITED') {
          error.value = errorData.message;
          return { success: false, error: error.value };
        }
        
        if (errorData?.code === 'INVALID_CREDENTIALS') {
          error.value = errorData.message;
          return { success: false, error: error.value };
        }

        // Retry logic for network errors and retryable errors
        if ((isNetworkError || isRetryable) && attempt < maxRetries) {
          const delay = Math.pow(2, attempt) * 1000; // Exponential backoff
          console.log(`Retrying login in ${delay}ms...`);
          await new Promise(resolve => setTimeout(resolve, delay));
          return attemptLogin(attempt + 1);
        }

        // Final error handling
        error.value = errorData?.message || err.message || "Login failed. Please try again.";
        return { success: false, error: error.value };
      } finally {
        isLoading.value = false;
      }
    };

    return attemptLogin(1);
  };

  // Initialize cross-tab session management
  const initializeCrossTabSession = async (): Promise<void> => {
    if (crossTabInitialized.value) return;

    try {
      console.log('[AuthStore] Initializing cross-tab session management');
      
      // Initialize the cross-tab session manager
      await crossTabSessionManager.initialize();
      
      // Set up cross-tab event listeners
      setupCrossTabEventListeners();
      
      // Sync current session with cross-tab manager
      await syncWithCrossTabManager();
      
      crossTabInitialized.value = true;
      console.log('[AuthStore] Cross-tab session management initialized');
      
    } catch (error) {
      console.error('[AuthStore] Failed to initialize cross-tab session:', error);
      throw error;
    }
  };

  // Setup cross-tab event listeners
  const setupCrossTabEventListeners = (): void => {
    // Listen for cross-tab logout events
    const handleCrossTabLogout = (event: CustomEvent) => {
      console.log('[AuthStore] Received cross-tab logout event');
      handleCrossTabLogoutEvent(event.detail.initiatingTab);
    };

    window.addEventListener('cross-tab-logout', handleCrossTabLogout as EventListener);
    crossTabEventListeners.value.push(() => {
      window.removeEventListener('cross-tab-logout', handleCrossTabLogout as EventListener);
    });

    // Listen for session conflicts
    const handleSessionConflict = (event: CustomEvent) => {
      console.log('[AuthStore] Received session conflict event');
      handleSessionConflictEvent(event.detail);
    };

    window.addEventListener('session-conflict', handleSessionConflict as EventListener);
    crossTabEventListeners.value.push(() => {
      window.removeEventListener('session-conflict', handleSessionConflict as EventListener);
    });
  };

  // Sync with cross-tab session manager
  const syncWithCrossTabManager = async (): Promise<void> => {
    try {
      // First, check if we have session data from other tabs
      const sessionData = crossTabSessionManager.getSessionData();
      
      if (sessionData.token && sessionData.userId && sessionData.isActive) {
        // We have valid session data from other tabs
        console.log('[AuthStore] Found valid session data from other tabs');
        
        // Update our auth state
        token.value = sessionData.token;
        sessionExpiry.value = sessionData.expiresAt;
        
        // Store token in localStorage
        if (sessionData.token) {
          localStorage.setItem("auth_token", sessionData.token);
        }
        
        // Fetch user data if we don't have it and no auth request is in progress
        if (!user.value && sessionData.userId && !authRequestInProgress.value) {
          await fetchUser();
        }
        
        // Start session management
        await startSessionManagement();
        
      } else if (isAuthenticated.value) {
        // We have local auth data, sync it to cross-tab manager
        console.log('[AuthStore] Syncing local auth data to cross-tab manager');
        await syncAuthDataToCrossTab();
      }
      
      // Update active tabs list
      updateActiveTabsList();
      
    } catch (error) {
      console.error('[AuthStore] Failed to sync with cross-tab manager:', error);
    }
  };

  // Sync current auth data to cross-tab manager
  const syncAuthDataToCrossTab = async (): Promise<void> => {
    if (!isAuthenticated.value) return;

    const sessionData: Partial<SessionData> = {
      userId: user.value?.id || null,
      token: token.value,
      expiresAt: sessionExpiry.value,
      isActive: isAuthenticated.value,
      metadata: {
        userAgent: navigator.userAgent,
        loginTime: user.value ? new Date() : null,
        refreshCount: 0
      }
    };

    crossTabSessionManager.updateSessionData(sessionData);
    lastCrossTabSync.value = new Date();
    console.log('[AuthStore] Synced auth data to cross-tab manager');
  };

  // Handle cross-tab logout event
  const handleCrossTabLogoutEvent = async (initiatingTab: string): Promise<void> => {
    console.log(`[AuthStore] Handling logout from tab ${initiatingTab}`);
    
    // Clear local auth state without calling backend (already done by initiating tab)
    cleanupAuthState();
    
    // Update active tabs list
    updateActiveTabsList();
    
    // Redirect to login page
    console.log('[AuthStore] Cross-tab logout - redirecting to login page');
    await router.push('/login');
  };

  // Handle session conflict event
  const handleSessionConflictEvent = (conflictData: ConflictResolution): void => {
    console.log('[AuthStore] Handling session conflict:', conflictData.reason);
    
    // Add to conflicts list
    sessionConflicts.value.push(conflictData);
    
    // Update session health status
    sessionHealthStatus.value = 'warning';
    
    // Auto-resolve if possible
    if (conflictData.action === 'use_incoming') {
      resolveSessionConflict(conflictData);
    }
  };

  // Resolve session conflict
  const resolveSessionConflict = async (resolution: ConflictResolution): Promise<void> => {
    try {
      console.log('[AuthStore] Resolving session conflict:', resolution.reason);
      
      await crossTabSessionManager.recoverFromConflict(resolution);
      
      // Re-sync with cross-tab manager
      await syncWithCrossTabManager();
      
      // Remove resolved conflict
      sessionConflicts.value = sessionConflicts.value.filter(c => c.timestamp !== resolution.timestamp);
      
      // Update session health status
      if (sessionConflicts.value.length === 0) {
        sessionHealthStatus.value = 'healthy';
      }
      
      console.log('[AuthStore] Session conflict resolved');
      
    } catch (error) {
      console.error('[AuthStore] Failed to resolve session conflict:', error);
      sessionHealthStatus.value = 'error';
    }
  };

  // Detect and handle session conflicts
  const detectSessionConflicts = async (): Promise<ConflictResolution | null> => {
    try {
      const conflict = await crossTabSessionManager.detectSessionConflicts();
      
      if (conflict) {
        console.log('[AuthStore] Session conflict detected:', conflict.reason);
        
        // Add to conflicts list
        sessionConflicts.value.push(conflict);
        sessionHealthStatus.value = 'warning';
        
        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('session-conflict', {
          detail: conflict
        }));
      }
      
      return conflict;
      
    } catch (error) {
      console.error('[AuthStore] Failed to detect session conflicts:', error);
      return null;
    }
  };

  // Update active tabs list
  const updateActiveTabsList = (): void => {
    activeTabs.value = crossTabSessionManager.getActiveTabs();
  };

  // Get session health information
  const getSessionHealth = () => {
    return {
      status: sessionHealthStatus.value,
      conflicts: sessionConflicts.value,
      activeTabs: activeTabs.value,
      tabCount: tabCount.value,
      isMultiTab: isMultiTab.value,
      lastSync: lastCrossTabSync.value,
      sessionData: crossTabSessionManager.getSessionData()
    };
  };

  // Perform session health check
  const performHealthCheck = async (): Promise<boolean> => {
    try {
      console.log('[AuthStore] Performing session health check');
      
      // Check for conflicts
      const conflict = await detectSessionConflicts();
      
      // Validate session with backend
      const isValid = await validateSession();
      
      // Update health status
      if (!isValid) {
        sessionHealthStatus.value = 'error';
        return false;
      } else if (conflict) {
        sessionHealthStatus.value = 'warning';
        return true;
      } else {
        sessionHealthStatus.value = 'healthy';
        return true;
      }
      
    } catch (error) {
      console.error('[AuthStore] Health check failed:', error);
      sessionHealthStatus.value = 'error';
      return false;
    }
  };

  // Schedule session maintenance
  const scheduleSessionMaintenance = (): void => {
    // Perform health check every 10 minutes (reduced from 2 minutes)
    const healthCheckInterval = setInterval(async () => {
      if (isAuthenticated.value) {
        await performHealthCheck();
      }
    }, 10 * 60 * 1000);

    // Sync with cross-tab manager every 5 minutes (reduced from 30 seconds)
    const syncInterval = setInterval(async () => {
      if (isAuthenticated.value && crossTabInitialized.value) {
        await syncAuthDataToCrossTab();
        updateActiveTabsList();
      }
    }, 5 * 60 * 1000);

    // Store intervals for cleanup
    crossTabEventListeners.value.push(() => {
      clearInterval(healthCheckInterval);
      clearInterval(syncInterval);
    });
  };

  // Handle cross-tab logout coordination
  const handleCrossTabLogout = async (): Promise<void> => {
    try {
      console.log('[AuthStore] Initiating cross-tab logout');
      
      // Request logout lock to prevent conflicts
      const lockAcquired = await crossTabSessionManager.requestSessionLock('logout');
      
      if (lockAcquired) {
        try {
          // Call backend logout
          if (token.value) {
            await apiService.auth.logout();
          }
          
          // Broadcast logout to other tabs
          crossTabSessionManager.broadcastLogout();
          
          // Clean up local state
          cleanupAuthState();
          
        } finally {
          // Release logout lock
          crossTabSessionManager.releaseSessionLock('logout');
        }
      } else {
        // Another tab is handling logout, just clean up local state
        cleanupAuthState();
      }
      
    } catch (error) {
      console.error('[AuthStore] Cross-tab logout failed:', error);
      // Clean up local state even if logout fails
      cleanupAuthState();
    }
  };

  // Enhanced logout with reliable logout manager
  const logout = async (): Promise<LogoutResult> => {
    console.log('[AuthStore] Starting logout process')
    
    try {
      // Stop session management first
      stopSessionManagement();

      // Use reliable logout manager for comprehensive logout
      const result = await logoutManager.initiateLogout();
      
      // Clean up local auth state
      cleanupAuthState();
      
      console.log('[AuthStore] Logout result:', result)
      
      // Always redirect to login page, regardless of backend success
      console.log('[AuthStore] Redirecting to login page')
      await router.push('/login');
      
      return result;
    } catch (err) {
      console.error("[AuthStore] Logout error:", err);
      // Always clear local state
      cleanupAuthState();
      
      // Still redirect to login page
      await router.push('/login');
      
      return {
        success: false,
        message: "Logout failed, but local cleanup completed",
        error: {
          type: 'logout_failed',
          message: err instanceof Error ? err.message : 'Unknown error',
          originalError: err
        }
      };
    }
  };

  // Clean up all authentication state
  const cleanupAuthState = (): void => {
    user.value = null;
    token.value = null;
    refreshToken.value = null;
    sessionExpiry.value = null;
    error.value = null;
    retryCount.value = 0;
    
    // Clear cross-tab state
    sessionConflicts.value = [];
    sessionHealthStatus.value = 'healthy';
    lastCrossTabSync.value = null;
    
    // Clear localStorage
    localStorage.removeItem("auth_token");
    localStorage.removeItem("refresh_token");
    
    // Stop session management
    stopSessionManagement();
    
    // Clean up cross-tab resources
    cleanupCrossTabSession();
  };

  // Clean up cross-tab session resources
  const cleanupCrossTabSession = (): void => {
    // Remove cross-tab event listeners
    crossTabEventListeners.value.forEach(cleanup => cleanup());
    crossTabEventListeners.value = [];
    
    // Reset cross-tab state
    crossTabInitialized.value = false;
    activeTabs.value = [];
    
    console.log('[AuthStore] Cross-tab session resources cleaned up');
  };

  // Circuit breaker state for preventing auth storms
  const authRequestInProgress = ref(false);
  const lastAuthFailure = ref<Date | null>(null);
  const authFailureCount = ref(0);

  // Enhanced fetchUser with retry logic and circuit breaker
  const fetchUser = async (): Promise<boolean> => {
    if (!token.value) return false;

    // Circuit breaker: prevent multiple concurrent auth requests
    if (authRequestInProgress.value) {
      console.log('[AuthStore] Auth request already in progress, skipping');
      return false;
    }

    // Circuit breaker: back off after repeated failures
    if (lastAuthFailure.value && authFailureCount.value >= 3) {
      const timeSinceFailure = Date.now() - lastAuthFailure.value.getTime();
      const backoffTime = Math.min(30000, Math.pow(2, authFailureCount.value) * 1000); // Max 30 seconds
      
      if (timeSinceFailure < backoffTime) {
        console.log(`[AuthStore] Circuit breaker active, waiting ${backoffTime - timeSinceFailure}ms`);
        return false;
      }
    }

    const attemptFetch = async (attempt: number): Promise<boolean> => {
      try {
        authRequestInProgress.value = true;
        isLoading.value = true;
        const response = await apiService.auth.me();
        
        if (response.data.success) {
          user.value = response.data.data.user;
          
          // Update session info if available
          if (response.data.data.session) {
            const sessionData = response.data.data.session;
            sessionExpiry.value = new Date(sessionData.expires_at);
          }
          
          // Reset failure count on success
          authFailureCount.value = 0;
          lastAuthFailure.value = null;
          
          return true;
        } else {
          throw new Error("Failed to fetch user data");
        }
      } catch (err: any) {
        console.error(`Fetch user attempt ${attempt} failed:`, err);
        
        // Track failures
        authFailureCount.value++;
        lastAuthFailure.value = new Date();
        
        // If unauthorized, clear auth state
        if (err.response?.status === 401) {
          cleanupAuthState();
          return false;
        }
        
        // Retry for network errors
        if (!err.response && attempt < maxRetries) {
          const delay = Math.pow(2, attempt) * 1000;
          await new Promise(resolve => setTimeout(resolve, delay));
          return attemptFetch(attempt + 1);
        }
        
        // If all retries failed, logout
        cleanupAuthState();
        return false;
      } finally {
        authRequestInProgress.value = false;
        isLoading.value = false;
      }
    };

    return attemptFetch(1);
  };

  // Enhanced token refresh with retry logic
  const refreshAuthToken = async (): Promise<boolean> => {
    if (!token.value) return false;

    const attemptRefresh = async (attempt: number): Promise<boolean> => {
      try {
        const response = await apiService.auth.refresh();
        
        if (response.data.success) {
          const { token: newToken, expires_at } = response.data.data;
          
          token.value = newToken;
          localStorage.setItem("auth_token", newToken);
          
          if (expires_at) {
            sessionExpiry.value = new Date(expires_at);
          }
          
          retryCount.value = 0;
          return true;
        } else {
          throw new Error("Token refresh failed");
        }
      } catch (err: any) {
        console.error(`Token refresh attempt ${attempt} failed:`, err);
        
        // If unauthorized, clear auth state
        if (err.response?.status === 401) {
          cleanupAuthState();
          return false;
        }
        
        // Retry for network errors
        if (!err.response && attempt < maxRetries) {
          const delay = Math.pow(2, attempt) * 1000;
          await new Promise(resolve => setTimeout(resolve, delay));
          return attemptRefresh(attempt + 1);
        }
        
        // If all retries failed, logout
        cleanupAuthState();
        return false;
      }
    };

    return attemptRefresh(1);
  };

  // Check if session validation is needed (for performance optimization)
  const needsSessionValidation = (): boolean => {
    if (!token.value || !isAuthenticated.value) return true;
    
    // Don't validate if session is not expiring soon and we validated recently
    const timeSinceLastActivity = Date.now() - lastActivity.value.getTime();
    const recentActivity = timeSinceLastActivity < 10 * 60 * 1000; // 10 minutes (increased from 5)
    
    return !recentActivity || isSessionExpiringSoon.value;
  };

  // Validate current session with backend
  const validateSession = async (): Promise<boolean> => {
    if (!token.value) return false;

    try {
      const response = await apiService.auth.validateSession();
      
      if (response.data.success) {
        const sessionData = response.data.data;
        sessionExpiry.value = new Date(sessionData.expires_at);
        // Update last activity to avoid frequent validations
        lastActivity.value = new Date();
        return sessionData.session_valid;
      } else {
        return false;
      }
    } catch (err: any) {
      console.error("Session validation failed:", err);
      
      if (err.response?.status === 401) {
        cleanupAuthState();
      }
      
      return false;
    }
  };

  // Extend current session
  const extendSession = async (): Promise<boolean> => {
    if (!token.value) return false;

    try {
      const response = await apiService.auth.extendSession();
      
      if (response.data.success) {
        const sessionData = response.data.data;
        sessionExpiry.value = new Date(sessionData.expires_at);
        return true;
      } else {
        return false;
      }
    } catch (err: any) {
      console.error("Session extension failed:", err);
      return false;
    }
  };

  // Synchronize session timeout with backend
  const syncSessionTimeout = async (): Promise<void> => {
    if (!isAuthenticated.value) return;

    try {
      const isValid = await validateSession();
      if (!isValid) {
        await logout();
        router.push('/login');
      }
    } catch (err) {
      console.error("Session sync failed:", err);
    }
  };

  // Update activity timestamp and extend session if needed
  const updateActivity = async (): Promise<void> => {
    lastActivity.value = new Date();
    
    // Extend session if it's expiring soon
    if (isSessionExpiringSoon.value && isAuthenticated.value) {
      await extendSession();
    }
  };

  // Start session management (timeouts, activity tracking, sync)
  const startSessionManagement = async (): Promise<void> => {
    // Stop any existing session management
    stopSessionManagement();

    // Set up activity listeners
    const events = [
      "mousedown",
      "mousemove",
      "keypress",
      "scroll",
      "touchstart",
      "click",
      "keydown"
    ];

    const activityHandler = () => {
      updateActivity();
    };

    events.forEach((event) => {
      document.addEventListener(event, activityHandler, true);
      activityListeners.value.push(() => {
        document.removeEventListener(event, activityHandler, true);
      });
    });

    // Set up periodic session validation (every 15 minutes - reduced frequency)
    sessionSyncInterval.value = window.setInterval(async () => {
      await syncSessionTimeout();
    }, 15 * 60 * 1000);

    // Set up session timeout warning
    const checkSessionExpiry = () => {
      if (isSessionExpiringSoon.value && isAuthenticated.value) {
        // Show warning notification
        console.warn("Session expiring soon!");
        // TODO: Show toast notification to user
      }
      
      if (sessionTimeRemaining.value <= 0 && isAuthenticated.value) {
        logout();
        router.push('/login');
      }
    };

    // Check session expiry every 2 minutes (reduced frequency)
    sessionTimeout.value = window.setInterval(checkSessionExpiry, 2 * 60 * 1000);
  };

  // Stop session management
  const stopSessionManagement = (): void => {
    // Clear intervals
    if (sessionTimeout.value) {
      clearInterval(sessionTimeout.value);
      sessionTimeout.value = null;
    }
    
    if (sessionSyncInterval.value) {
      clearInterval(sessionSyncInterval.value);
      sessionSyncInterval.value = null;
    }

    // Remove activity listeners
    activityListeners.value.forEach(cleanup => cleanup());
    activityListeners.value = [];
  };

  // Update user data
  const updateUser = (userData: Partial<User>): void => {
    if (user.value) {
      user.value = { ...user.value, ...userData };
    }
  };

  // Initialize auth state
  const initialize = async (): Promise<void> => {
    if (initialized.value || authRequestInProgress.value) return;

    try {
      // Initialize cross-tab session management first
      await initializeCrossTabSession();
      
      if (token.value && !authRequestInProgress.value) {
        const userFetched = await fetchUser();
        if (userFetched && isAuthenticated.value) {
          await startSessionManagement();
          // Schedule session maintenance
          scheduleSessionMaintenance();
        } else {
          cleanupAuthState();
        }
      } else {
        // Check if we have session data from other tabs
        await syncWithCrossTabManager();
      }
    } catch (err) {
      console.error("Auth initialization failed:", err);
      cleanupAuthState();
    } finally {
      initialized.value = true;
    }
  };

  // Handle authentication errors with user-friendly messages
  const handleAuthError = (err: any): string => {
    const errorData = err.response?.data?.error;
    
    if (errorData?.code) {
      switch (errorData.code) {
        case 'NETWORK_ERROR':
          return "Network connection failed. Please check your internet connection and try again.";
        case 'INVALID_CREDENTIALS':
          return "Invalid email or password. Please check your credentials and try again.";
        case 'SESSION_EXPIRED':
          return "Your session has expired. Please log in again.";
        case 'TOKEN_EXPIRED':
          return "Your login session has expired. Please log in again.";
        case 'RATE_LIMITED':
          return errorData.message || "Too many attempts. Please try again later.";
        case 'VALIDATION_ERROR':
          return errorData.message || "Please check your input and try again.";
        default:
          return errorData.message || "An unexpected error occurred. Please try again.";
      }
    }
    
    if (!err.response) {
      return "Network connection failed. Please check your internet connection and try again.";
    }
    
    return err.message || "An unexpected error occurred. Please try again.";
  };

  return {
    // State
    user,
    token,
    refreshToken,
    isLoading,
    error,
    initialized,
    sessionExpiry,
    lastActivity,
    authRequestInProgress,
    authFailureCount,
    
    // Cross-tab state
    crossTabInitialized,
    activeTabs,
    sessionConflicts,
    sessionHealthStatus,
    lastCrossTabSync,

    // Getters
    isAuthenticated,
    userInitials,
    sessionTimeRemaining,
    isSessionExpiringSoon,
    
    // Cross-tab getters
    isMultiTab,
    tabCount,
    hasSessionConflicts,
    currentTabId,

    // Actions
    login,
    logout,
    fetchUser,
    refreshAuthToken,
    validateSession,
    needsSessionValidation,
    extendSession,
    syncSessionTimeout,
    updateUser,
    updateActivity,
    initialize,
    startSessionManagement,
    stopSessionManagement,
    cleanupAuthState,
    handleAuthError,
    
    // Cross-tab actions
    initializeCrossTabSession,
    syncWithCrossTabManager,
    syncAuthDataToCrossTab,
    handleCrossTabLogout,
    detectSessionConflicts,
    resolveSessionConflict,
    getSessionHealth,
    performHealthCheck,
    scheduleSessionMaintenance,
    updateActiveTabsList,
  };
});
