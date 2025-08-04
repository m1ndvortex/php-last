import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { apiService } from "@/services/api";

export interface User {
  id: number;
  name: string;
  email: string;
  preferred_language: string;
  role?: string;
  is_active: boolean;
  last_login_at?: string;
  two_factor_enabled?: boolean;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export const useAuthStore = defineStore("auth", () => {
  // State
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem("auth_token"));
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const initialized = ref(false);
  const sessionTimeout = ref<number | null>(null);
  const lastActivity = ref<Date>(new Date());

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

  // Actions
  const login = async (credentials: LoginCredentials) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.auth.login(credentials);
      const { user: userData, token: authToken } = response.data.data;

      // Store auth data
      user.value = userData;
      token.value = authToken;
      localStorage.setItem("auth_token", authToken);

      // Set user's preferred language
      if (userData.preferred_language) {
        localStorage.setItem("preferred-language", userData.preferred_language);
      }

      return { success: true };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Login failed";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const logout = async () => {
    try {
      if (token.value) {
        await apiService.auth.logout();
      }
    } catch (err) {
      console.error("Logout error:", err);
    } finally {
      // Clear auth data
      user.value = null;
      token.value = null;
      localStorage.removeItem("auth_token");
    }
  };

  const fetchUser = async () => {
    if (!token.value) return;

    try {
      isLoading.value = true;
      const response = await apiService.auth.me();
      user.value = response.data;
    } catch (err) {
      console.error("Fetch user error:", err);
      // If token is invalid, logout
      logout();
    } finally {
      isLoading.value = false;
    }
  };

  const refreshToken = async () => {
    try {
      const response = await apiService.auth.refresh();
      const { token: newToken } = response.data;

      token.value = newToken;
      localStorage.setItem("auth_token", newToken);

      return true;
    } catch (err) {
      console.error("Token refresh error:", err);
      logout();
      return false;
    }
  };

  const updateUser = (userData: Partial<User>) => {
    if (user.value) {
      user.value = { ...user.value, ...userData };
    }
  };

  // Session management
  const updateActivity = () => {
    lastActivity.value = new Date();
  };

  const startSessionTimeout = () => {
    // Clear existing timeout
    if (sessionTimeout.value) {
      clearTimeout(sessionTimeout.value);
    }

    // Set 30 minute timeout (configurable)
    const timeoutDuration = 30 * 60 * 1000; // 30 minutes
    sessionTimeout.value = window.setTimeout(() => {
      logout();
      // Show session expired message
      error.value = "Your session has expired. Please log in again.";
    }, timeoutDuration);
  };

  const clearSessionTimeout = () => {
    if (sessionTimeout.value) {
      clearTimeout(sessionTimeout.value);
      sessionTimeout.value = null;
    }
  };

  // Initialize auth state
  const initialize = async () => {
    if (initialized.value) return;

    if (token.value) {
      await fetchUser();
      if (isAuthenticated.value) {
        startSessionTimeout();

        // Set up activity listeners
        const events = [
          "mousedown",
          "mousemove",
          "keypress",
          "scroll",
          "touchstart",
        ];
        const activityHandler = () => {
          updateActivity();
          startSessionTimeout(); // Reset timeout on activity
        };

        events.forEach((event) => {
          document.addEventListener(event, activityHandler, true);
        });
      }
    }

    initialized.value = true;
  };

  // Enhanced logout with session cleanup
  const enhancedLogout = async () => {
    clearSessionTimeout();
    await logout();
  };

  return {
    // State
    user,
    token,
    isLoading,
    error,
    initialized,
    lastActivity,

    // Getters
    isAuthenticated,
    userInitials,

    // Actions
    login,
    logout: enhancedLogout,
    fetchUser,
    refreshToken,
    updateUser,
    initialize,
    updateActivity,
    startSessionTimeout,
    clearSessionTimeout,
  };
});
