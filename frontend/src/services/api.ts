import axios from "axios";
import type {
  AxiosInstance,
  AxiosRequestConfig,
  AxiosResponse,
  AxiosError,
} from "axios";
import { useAuthStore } from "@/stores/auth";
import router from "@/router";

// Create axios instance
const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost",
  timeout: 10000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
});

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Add auth token if available
    const authStore = useAuthStore();
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`;
    }

    // Add language header
    const locale = localStorage.getItem("preferred-language") || "en";
    config.headers["Accept-Language"] = locale;

    // Ensure credentials are included for CSRF
    config.withCredentials = true;

    // Add CSRF token from cookie if available
    const csrfToken = getCsrfTokenFromCookie();
    if (csrfToken) {
      config.headers["X-XSRF-TOKEN"] = csrfToken;
    }

    return config;
  },
  (error: AxiosError) => {
    return Promise.reject(error);
  },
);

// Response interceptor
api.interceptors.response.use(
  (response: AxiosResponse) => {
    return response;
  },
  (error: AxiosError) => {
    const authStore = useAuthStore();

    // Handle different error status codes
    if (error.response) {
      switch (error.response.status) {
        case 401:
          // Unauthorized - clear auth and redirect to login
          authStore.logout();
          router.push("/login");
          break;

        case 403:
          // Forbidden - show error message
          console.error("Access forbidden:", error.response.data);
          break;

        case 404:
          // Not found
          console.error("Resource not found:", error.response.data);
          break;

        case 422:
          // Validation errors
          console.error("Validation errors:", error.response.data);
          break;

        case 429:
          // Too many requests
          console.error("Rate limit exceeded:", error.response.data);
          break;

        case 500:
        case 502:
        case 503:
        case 504:
          // Server errors
          console.error("Server error:", error.response.data);
          // Show user-friendly error message
          showErrorNotification(
            "Server error occurred. Please try again later.",
          );
          break;

        default:
          console.error("API Error:", error.response.data);
      }
    } else if (error.request) {
      // Network error
      console.error("Network error:", error.request);
      showErrorNotification("Network error. Please check your connection.");
    } else {
      // Other error
      console.error("Error:", error.message);
      showErrorNotification("An unexpected error occurred.");
    }

    return Promise.reject(error);
  },
);

// Helper function to show error notifications
const showErrorNotification = (message: string) => {
  // TODO: Implement toast notification system
  console.error(message);
};

// Helper function to get CSRF cookie
const getCsrfCookie = async (): Promise<void> => {
  try {
    await axios.get("http://localhost/sanctum/csrf-cookie", {
      withCredentials: true,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      }
    });
  } catch (error) {
    console.error("Failed to get CSRF cookie:", error);
    throw error;
  }
};

// Helper function to get CSRF token from cookie
const getCsrfTokenFromCookie = (): string | null => {
  const name = "XSRF-TOKEN";
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    const token = parts.pop()?.split(';').shift();
    return token ? decodeURIComponent(token) : null;
  }
  return null;
};

// API methods
export const apiService = {
  // Generic methods
  get: <T = any>(
    url: string,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<T>> => api.get(url, config),

  post: <T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<T>> => api.post(url, data, config),

  put: <T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<T>> => api.put(url, data, config),

  patch: <T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<T>> => api.patch(url, data, config),

  delete: <T = any>(
    url: string,
    config?: AxiosRequestConfig,
  ): Promise<AxiosResponse<T>> => api.delete(url, config),

  // Authentication
  auth: {
    login: async (credentials: {
      email: string;
      password: string;
      remember?: boolean;
    }) => {
      // Try to get CSRF cookie before login for proper security
      try {
        await getCsrfCookie();
      } catch (error) {
        console.warn("CSRF cookie request failed, proceeding with login:", error);
        // Continue with login even if CSRF cookie fails
        // This maintains backward compatibility while we work on CORS issues
      }
      return api.post("api/auth/login", credentials);
    },

    logout: async () => {
      // Try to ensure CSRF cookie is available for logout
      try {
        await getCsrfCookie();
      } catch (error) {
        console.warn("CSRF cookie request failed for logout, proceeding:", error);
      }
      return api.post("api/auth/logout");
    },

    me: () => api.get("api/auth/user"),

    refresh: () => api.post("api/auth/refresh"),

    updateProfile: (data: {
      name: string;
      email: string;
      preferred_language: string;
    }) => api.put("api/auth/profile", data),

    changePassword: (data: {
      current_password: string;
      password: string;
      password_confirmation: string;
    }) => api.put("api/auth/password", data),

    // Two-Factor Authentication
    enable2FA: () => api.post("api/auth/2fa/enable"),

    verify2FA: (data: { code: string }) => api.post("api/auth/2fa/verify", data),

    disable2FA: (data: { password: string }) =>
      api.post("api/auth/2fa/disable", data),

    getBackupCodes: () => api.get("api/auth/2fa/backup-codes"),

    regenerateBackupCodes: () => api.post("api/auth/2fa/backup-codes/regenerate"),

    // Session Management
    getSessions: () => api.get("api/auth/sessions"),

    revokeSession: (sessionId: string) =>
      api.delete(`api/auth/sessions/${sessionId}`),

    revokeAllSessions: () => api.delete("api/auth/sessions"),

    // Password Reset
    forgotPassword: (data: { email: string }) =>
      api.post("api/auth/forgot-password", data),

    resetPassword: (data: {
      token: string;
      email: string;
      password: string;
      password_confirmation: string;
    }) => api.post("api/auth/reset-password", data),
  },

  // Dashboard
  dashboard: {
    getKPIs: () => api.get("api/dashboard/kpis"),

    getWidgets: () => api.get("api/dashboard/widgets"),

    saveWidgetLayout: (layout: any) =>
      api.post("api/dashboard/widgets/layout", { layout }),
  },

  // Localization
  localization: {
    getTranslations: (locale: string) =>
      api.get(`api/localization/translations/${locale}`),

    switchLanguage: (locale: string) =>
      api.post("api/localization/switch-language", { locale }),
  },
};

export default api;
