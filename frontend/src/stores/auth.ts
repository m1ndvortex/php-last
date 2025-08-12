import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { apiService } from "@/services/api";
import router from "@/router";

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

        const { user: userData, token: authToken, session_expiry, server_time } = response.data.data;

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

  // Enhanced logout with proper cleanup
  const logout = async (): Promise<void> => {
    try {
      // Stop session management first
      stopSessionManagement();

      // Call backend logout if we have a token
      if (token.value) {
        try {
          await apiService.auth.logout();
        } catch (err) {
          console.error("Backend logout error:", err);
          // Continue with cleanup even if backend call fails
        }
      }
    } catch (err) {
      console.error("Logout error:", err);
    } finally {
      // Always clear local state
      cleanupAuthState();
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
    
    // Clear localStorage
    localStorage.removeItem("auth_token");
    localStorage.removeItem("refresh_token");
    
    // Stop session management
    stopSessionManagement();
  };

  // Enhanced fetchUser with retry logic
  const fetchUser = async (): Promise<boolean> => {
    if (!token.value) return false;

    const attemptFetch = async (attempt: number): Promise<boolean> => {
      try {
        isLoading.value = true;
        const response = await apiService.auth.me();
        
        if (response.data.success) {
          user.value = response.data.data.user;
          
          // Update session info if available
          if (response.data.data.session) {
            const sessionData = response.data.data.session;
            sessionExpiry.value = new Date(sessionData.expires_at);
          }
          
          return true;
        } else {
          throw new Error("Failed to fetch user data");
        }
      } catch (err: any) {
        console.error(`Fetch user attempt ${attempt} failed:`, err);
        
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

  // Validate current session with backend
  const validateSession = async (): Promise<boolean> => {
    if (!token.value) return false;

    try {
      const response = await apiService.auth.validateSession();
      
      if (response.data.success) {
        const sessionData = response.data.data;
        sessionExpiry.value = new Date(sessionData.expires_at);
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

    // Set up periodic session validation (every 5 minutes)
    sessionSyncInterval.value = window.setInterval(async () => {
      await syncSessionTimeout();
    }, 5 * 60 * 1000);

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

    // Check session expiry every minute
    sessionTimeout.value = window.setInterval(checkSessionExpiry, 60 * 1000);
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
    if (initialized.value) return;

    try {
      if (token.value) {
        const userFetched = await fetchUser();
        if (userFetched && isAuthenticated.value) {
          await startSessionManagement();
        } else {
          cleanupAuthState();
        }
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

    // Getters
    isAuthenticated,
    userInitials,
    sessionTimeRemaining,
    isSessionExpiringSoon,

    // Actions
    login,
    logout,
    fetchUser,
    refreshAuthToken,
    validateSession,
    extendSession,
    syncSessionTimeout,
    updateUser,
    updateActivity,
    initialize,
    startSessionManagement,
    stopSessionManagement,
    cleanupAuthState,
    handleAuthError,
  };
});
