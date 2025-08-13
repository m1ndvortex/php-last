import axios from "axios";
import type {
  AxiosInstance,
  AxiosRequestConfig,
  AxiosResponse,
  AxiosError,
} from "axios";

// Extend AxiosRequestConfig to include metadata
declare module 'axios' {
  interface AxiosRequestConfig {
    metadata?: RequestMetadata;
  }
  interface InternalAxiosRequestConfig {
    metadata?: RequestMetadata;
  }
}
import type { Customer } from "@/types";
import router from "@/router";

// Enhanced error types for better categorization
export interface ApiError {
  success: false;
  error: {
    code: string;
    message: string;
    details?: any;
    retryable?: boolean;
    timestamp?: string;
  };
}

export interface RequestMetadata {
  retryCount: number;
  startTime: number;
  requestId: string;
  sessionId?: string;
}

// Request/Response logging service
class ApiLogger {
  private static instance: ApiLogger;
  private debugMode: boolean = import.meta.env.DEV;

  static getInstance(): ApiLogger {
    if (!ApiLogger.instance) {
      ApiLogger.instance = new ApiLogger();
    }
    return ApiLogger.instance;
  }

  private generateRequestId(): string {
    return `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  logRequest(config: AxiosRequestConfig & { metadata?: RequestMetadata }): void {
    if (!this.debugMode) return;

    const requestId = config.metadata?.requestId || this.generateRequestId();
    const sessionId = localStorage.getItem('session_id') || 'unknown';

    console.group(`🚀 API Request [${requestId}]`);
    console.log('URL:', `${config.method?.toUpperCase()} ${config.url}`);
    console.log('Headers:', this.sanitizeHeaders(config.headers));
    console.log('Data:', config.data);
    console.log('Session ID:', sessionId);
    console.log('Retry Count:', config.metadata?.retryCount || 0);
    console.log('Timestamp:', new Date().toISOString());
    console.groupEnd();
  }

  logResponse(response: AxiosResponse, requestId?: string): void {
    if (!this.debugMode) return;

    const duration = requestId && response.config.metadata?.startTime 
      ? Date.now() - response.config.metadata.startTime 
      : 0;

    console.group(`✅ API Response [${requestId || 'unknown'}]`);
    console.log('Status:', `${response.status} ${response.statusText}`);
    console.log('Duration:', `${duration}ms`);
    console.log('Headers:', response.headers);
    console.log('Data:', response.data);
    console.groupEnd();
  }

  logError(error: AxiosError, requestId?: string): void {
    const duration = requestId && error.config?.metadata?.startTime 
      ? Date.now() - error.config.metadata.startTime 
      : 0;

    console.group(`❌ API Error [${requestId || 'unknown'}]`);
    console.error('URL:', `${error.config?.method?.toUpperCase()} ${error.config?.url}`);
    console.error('Status:', error.response?.status || 'Network Error');
    console.error('Duration:', `${duration}ms`);
    console.error('Message:', error.message);
    console.error('Response Data:', error.response?.data);
    console.error('Retry Count:', error.config?.metadata?.retryCount || 0);
    console.error('Timestamp:', new Date().toISOString());
    console.groupEnd();
  }

  private sanitizeHeaders(headers: any): any {
    if (!headers) return {};
    
    const sanitized = { ...headers };
    // Remove sensitive headers from logs
    if (sanitized.Authorization) {
      sanitized.Authorization = 'Bearer [REDACTED]';
    }
    if (sanitized['X-XSRF-TOKEN']) {
      sanitized['X-XSRF-TOKEN'] = '[REDACTED]';
    }
    return sanitized;
  }
}

// Error categorization service
class ErrorCategorizer {
  static categorizeError(error: AxiosError): ApiError {
    const timestamp = new Date().toISOString();
    
    // Network errors (no response)
    if (!error.response) {
      return {
        success: false,
        error: {
          code: 'NETWORK_ERROR',
          message: 'Network connection failed. Please check your internet connection.',
          retryable: true,
          timestamp,
          details: {
            originalMessage: error.message,
            type: 'network'
          }
        }
      };
    }

    const status = error.response.status;
    const responseData = error.response.data as any;

    // Server provided error structure
    if (responseData?.error?.code) {
      return {
        success: false,
        error: {
          ...responseData.error,
          timestamp,
          retryable: responseData.error.retryable ?? this.isRetryableStatus(status)
        }
      };
    }

    // Categorize by HTTP status
    switch (status) {
      case 400:
        return {
          success: false,
          error: {
            code: 'BAD_REQUEST',
            message: responseData?.message || 'Invalid request. Please check your input.',
            retryable: false,
            timestamp,
            details: responseData
          }
        };

      case 401:
        return {
          success: false,
          error: {
            code: 'UNAUTHORIZED',
            message: responseData?.message || 'Authentication required. Please log in.',
            retryable: false,
            timestamp,
            details: responseData
          }
        };

      case 403:
        return {
          success: false,
          error: {
            code: 'FORBIDDEN',
            message: responseData?.message || 'Access denied. You don\'t have permission.',
            retryable: false,
            timestamp,
            details: responseData
          }
        };

      case 404:
        return {
          success: false,
          error: {
            code: 'NOT_FOUND',
            message: responseData?.message || 'The requested resource was not found.',
            retryable: false,
            timestamp,
            details: responseData
          }
        };

      case 422:
        return {
          success: false,
          error: {
            code: 'VALIDATION_ERROR',
            message: responseData?.message || 'Validation failed. Please check your input.',
            retryable: false,
            timestamp,
            details: responseData
          }
        };

      case 429:
        return {
          success: false,
          error: {
            code: 'RATE_LIMITED',
            message: responseData?.message || 'Too many requests. Please try again later.',
            retryable: true,
            timestamp,
            details: responseData
          }
        };

      case 500:
      case 502:
      case 503:
      case 504:
        return {
          success: false,
          error: {
            code: 'SERVER_ERROR',
            message: responseData?.message || 'Server error occurred. Please try again.',
            retryable: true,
            timestamp,
            details: responseData
          }
        };

      default:
        return {
          success: false,
          error: {
            code: 'UNKNOWN_ERROR',
            message: responseData?.message || 'An unexpected error occurred.',
            retryable: this.isRetryableStatus(status),
            timestamp,
            details: responseData
          }
        };
    }
  }

  private static isRetryableStatus(status: number): boolean {
    // Retry on server errors and rate limiting
    return status >= 500 || status === 429;
  }
}

// Session management service for API
class SessionManager {
  private static sessionId: string | null = null;
  private static sessionExpiry: Date | null = null;

  static getSessionId(): string {
    if (!this.sessionId) {
      this.sessionId = localStorage.getItem('session_id') || this.generateSessionId();
      localStorage.setItem('session_id', this.sessionId);
    }
    return this.sessionId;
  }

  static setSessionInfo(sessionId: string, expiryDate: Date): void {
    this.sessionId = sessionId;
    this.sessionExpiry = expiryDate;
    localStorage.setItem('session_id', sessionId);
    localStorage.setItem('session_expiry', expiryDate.toISOString());
  }

  static isSessionValid(): boolean {
    if (!this.sessionExpiry) {
      const storedExpiry = localStorage.getItem('session_expiry');
      if (storedExpiry) {
        this.sessionExpiry = new Date(storedExpiry);
      }
    }

    return this.sessionExpiry ? new Date() < this.sessionExpiry : false;
  }

  static clearSession(): void {
    this.sessionId = null;
    this.sessionExpiry = null;
    localStorage.removeItem('session_id');
    localStorage.removeItem('session_expiry');
  }

  private static generateSessionId(): string {
    return `sess_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }
}

// Create axios instance
const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "",
  timeout: 10000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
  withCredentials: true, // Important for Docker CORS
});

// CSRF initialization is handled by Laravel's existing session management

// Enhanced request interceptor with logging and session management
api.interceptors.request.use(
  (config) => {
    const logger = ApiLogger.getInstance();
    const requestId = logger['generateRequestId']();
    
    // Initialize request metadata
    config.metadata = {
      retryCount: config.metadata?.retryCount || 0,
      startTime: Date.now(),
      requestId,
      sessionId: SessionManager.getSessionId()
    };

    // Add auth token if available - get directly from localStorage to avoid circular dependency
    const token = localStorage.getItem("auth_token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // Add language header
    const locale = localStorage.getItem("preferred-language") || "en";
    config.headers["Accept-Language"] = locale;

    // Add session headers for session-aware request handling
    config.headers["X-Session-ID"] = SessionManager.getSessionId();
    
    // Add request ID for tracking
    config.headers["X-Request-ID"] = requestId;

    // Ensure credentials are included for CSRF
    config.withCredentials = true;

    // Add CSRF token from cookie if available
    const csrfToken = getCsrfTokenFromCookie();
    if (csrfToken) {
      config.headers["X-XSRF-TOKEN"] = csrfToken;
    }

    // Log the request
    logger.logRequest(config);

    return config;
  },
  (error: AxiosError) => {
    const logger = ApiLogger.getInstance();
    logger.logError(error);
    return Promise.reject(error);
  },
);

// Helper function to handle logout without circular dependency
const handleLogout = () => {
  localStorage.removeItem("auth_token");
  router.push("/login");
};

// Enhanced response interceptor with comprehensive error handling and retry logic
api.interceptors.response.use(
  (response: AxiosResponse) => {
    const logger = ApiLogger.getInstance();
    const requestId = response.config.metadata?.requestId;
    
    // Update session info if provided in response
    const sessionData = response.data?.session;
    if (sessionData?.id && sessionData?.expires_at) {
      SessionManager.setSessionInfo(sessionData.id, new Date(sessionData.expires_at));
    }
    
    // Log successful response
    logger.logResponse(response, requestId);
    
    return response;
  },
  async (error: AxiosError) => {
    const logger = ApiLogger.getInstance();
    const config = error.config as any;
    const requestId = config?.metadata?.requestId;
    
    // Initialize retry metadata if not present
    if (!config.metadata) {
      config.metadata = { 
        retryCount: 0, 
        startTime: Date.now(),
        requestId: requestId || logger['generateRequestId'](),
        sessionId: SessionManager.getSessionId()
      };
    }

    // Log the error
    logger.logError(error, requestId);

    // Categorize the error
    const categorizedError = ErrorCategorizer.categorizeError(error);

    // Handle authentication errors with intelligent token refresh
    if (error.response?.status === 401) {
      // Don't retry auth endpoints or if already retried for auth
      if (config.url?.includes('/auth/') || config.metadata.authRetried) {
        console.error("Authentication failed - clearing session");
        SessionManager.clearSession();
        handleLogout();
        return Promise.reject(categorizedError);
      }

      // Try to refresh token intelligently
      try {
        console.log("Attempting intelligent token refresh...");
        const refreshResponse = await api.post("api/auth/refresh", {}, {
          metadata: { ...config.metadata, authRetried: true }
        });
        
        if (refreshResponse.data.success) {
          const { token: newToken, session } = refreshResponse.data.data;
          localStorage.setItem("auth_token", newToken);
          
          // Update session info
          if (session?.id && session?.expires_at) {
            SessionManager.setSessionInfo(session.id, new Date(session.expires_at));
          }
          
          // Retry original request with new token
          config.headers.Authorization = `Bearer ${newToken}`;
          config.metadata.retryCount++;
          config.metadata.authRetried = true;
          
          console.log("Token refreshed successfully, retrying original request");
          return api.request(config);
        }
      } catch (refreshError) {
        console.error("Token refresh failed:", refreshError);
        SessionManager.clearSession();
        handleLogout();
        return Promise.reject(categorizedError);
      }
    }

    // Handle retryable errors with exponential backoff
    if (categorizedError.error.retryable && config.metadata.retryCount < 3) {
      const delay = Math.pow(2, config.metadata.retryCount) * 1000; // Exponential backoff: 1s, 2s, 4s
      const jitter = Math.random() * 1000; // Add jitter to prevent thundering herd
      const totalDelay = delay + jitter;
      
      console.log(`Retrying ${categorizedError.error.code} in ${Math.round(totalDelay)}ms... (attempt ${config.metadata.retryCount + 1}/3)`);
      
      config.metadata.retryCount++;
      await new Promise(resolve => setTimeout(resolve, totalDelay));
      return api.request(config);
    }

    // Handle session expiry
    if (categorizedError.error.code === 'SESSION_EXPIRED') {
      SessionManager.clearSession();
      handleLogout();
    }

    // Show user-friendly error notifications for specific error types
    if (shouldShowErrorNotification(categorizedError.error.code)) {
      showErrorNotification(categorizedError.error.message);
    }

    // For validation errors, preserve the original structure for form handling
    if (categorizedError.error.code === 'VALIDATION_ERROR') {
      const originalError = { ...error };
      originalError.response = {
        ...originalError.response!,
        data: {
          ...categorizedError,
          originalData: error.response?.data
        }
      };
      return Promise.reject(originalError);
    }

    return Promise.reject(categorizedError);
  },
);

// Helper function to determine if error should show notification
const shouldShowErrorNotification = (errorCode: string): boolean => {
  const silentErrors = [
    'VALIDATION_ERROR', // Handled by forms
    'UNAUTHORIZED',     // Handled by auth system
    'NOT_FOUND'        // Often expected in some contexts
  ];
  return !silentErrors.includes(errorCode);
};

// Helper function to show error notifications
const showErrorNotification = (message: string) => {
  // TODO: Implement toast notification system
  console.error("User Notification:", message);
  
  // For now, we'll use a simple approach that can be enhanced later
  if (typeof window !== 'undefined' && window.dispatchEvent) {
    window.dispatchEvent(new CustomEvent('api-error', { 
      detail: { message } 
    }));
  }
};

// Helper function to get CSRF token from cookie (simplified)
const getCsrfTokenFromCookie = (): string | null => {
  const name = "XSRF-TOKEN";
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    const token = parts.pop()?.split(";").shift();
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
      return api.post("api/auth/login", credentials);
    },

    logout: async () => {
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

    verify2FA: (data: { code: string }) =>
      api.post("api/auth/2fa/verify", data),

    disable2FA: (data: { password: string }) =>
      api.post("api/auth/2fa/disable", data),

    getBackupCodes: () => api.get("api/auth/2fa/backup-codes"),

    regenerateBackupCodes: () =>
      api.post("api/auth/2fa/backup-codes/regenerate"),

    // Enhanced Session Management with session-aware handling
    validateSession: async () => {
      try {
        const response = await api.post("api/auth/validate-session");
        
        // Update local session info if provided
        if (response.data.success && response.data.data?.session) {
          const sessionData = response.data.data.session;
          if (sessionData.id && sessionData.expires_at) {
            SessionManager.setSessionInfo(sessionData.id, new Date(sessionData.expires_at));
          }
        }
        
        return response;
      } catch (error) {
        console.error("Session validation failed:", error);
        throw error;
      }
    },

    extendSession: async () => {
      try {
        const response = await api.post("api/auth/extend-session");
        
        // Update local session info if provided
        if (response.data.success && response.data.data?.session) {
          const sessionData = response.data.data.session;
          if (sessionData.id && sessionData.expires_at) {
            SessionManager.setSessionInfo(sessionData.id, new Date(sessionData.expires_at));
          }
        }
        
        return response;
      } catch (error) {
        console.error("Session extension failed:", error);
        throw error;
      }
    },

    getSessions: () => api.get("api/auth/sessions"),

    revokeSession: (sessionId: string) =>
      api.delete(`api/auth/sessions/${sessionId}`),

    revokeAllSessions: async () => {
      try {
        const response = await api.delete("api/auth/sessions");
        // Clear local session info when all sessions are revoked
        SessionManager.clearSession();
        return response;
      } catch (error) {
        console.error("Session revocation failed:", error);
        throw error;
      }
    },

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

  // Customers
  customers: {
    getCustomers: (filters?: Record<string, any>) =>
      api.get("api/customers", { params: filters }),

    getCustomer: (id: number) => api.get(`api/customers/${id}`),

    createCustomer: (data: Partial<Customer>) =>
      api.post("api/customers", data),

    updateCustomer: (id: number, data: Partial<Customer>) =>
      api.put(`api/customers/${id}`, data),

    deleteCustomer: (id: number) => api.delete(`api/customers/${id}`),

    getAgingReport: (filters?: Record<string, any>) =>
      api.get("api/customers/aging-report", { params: filters }),

    getCRMPipeline: () => api.get("api/customers/crm-pipeline"),

    updateCRMStage: (id: number, data: { crm_stage: string; notes?: string }) =>
      api.put(`api/customers/${id}/crm-stage`, data),

    sendCommunication: (
      id: number,
      data: {
        type: string;
        subject?: string;
        message: string;
        metadata?: Record<string, any>;
      },
    ) => api.post(`api/customers/${id}/communicate`, data),

    getUpcomingBirthdays: () => api.get("api/customers/upcoming-birthdays"),

    getUpcomingAnniversaries: () =>
      api.get("api/customers/upcoming-anniversaries"),

    exportVCard: (id: number) => api.get(`api/customers/${id}/vcard`),
  },

  // Inventory
  inventory: {
    getItems: (filters?: Record<string, any>) =>
      api.get("api/inventory", { params: filters }),

    getItem: (id: number) => api.get(`api/inventory/${id}`),

    createItem: (data: FormData) =>
      api.post("api/inventory", data, {
        headers: { "Content-Type": "multipart/form-data" },
      }),

    updateItem: (id: number, data: FormData) =>
      api.put(`api/inventory/${id}`, data, {
        headers: { "Content-Type": "multipart/form-data" },
      }),

    deleteItem: (id: number) => api.delete(`api/inventory/${id}`),

    getMovements: (filters?: Record<string, any>) =>
      api.get("api/inventory/movements", { params: filters }),

    createMovement: (data: any) => api.post("api/inventory/movements", data),

    getCategories: () => api.get("api/categories"),

    getCategory: (id: number) => api.get(`api/categories/${id}`),

    createCategory: (data: any) =>
      api.post("api/categories", data, {
        headers: { "Content-Type": "multipart/form-data" },
      }),

    updateCategory: (id: number, data: any) =>
      api.put(`api/categories/${id}`, data, {
        headers: { "Content-Type": "multipart/form-data" },
      }),

    deleteCategory: (id: number) => api.delete(`api/categories/${id}`),

    reorderCategories: (data: any) => api.post("api/categories/reorder", data),

    getCategoryHierarchy: () => api.get("api/categories/hierarchy"),

    uploadCategoryImage: (id: number, data: FormData) =>
      api.post(`api/categories/${id}/image`, data, {
        headers: { "Content-Type": "multipart/form-data" },
      }),

    removeCategoryImage: (id: number) =>
      api.delete(`api/categories/${id}/image`),

    getGoldPurityOptions: () => api.get("api/categories/gold-purity-options"),

    getLocations: () => api.get("api/locations"),

    createLocation: (data: any) => api.post("api/locations", data),

    updateLocation: (id: number, data: any) =>
      api.put(`api/locations/${id}`, data),

    deleteLocation: (id: number) => api.delete(`api/locations/${id}`),

    // Stock Audit
    getAudits: (filters?: Record<string, any>) =>
      api.get("api/stock-audits", { params: filters }),

    getAudit: (id: number) => api.get(`api/stock-audits/${id}`),

    createAudit: (data: any) => api.post("api/stock-audits", data),

    updateAudit: (id: number, data: any) =>
      api.put(`api/stock-audits/${id}`, data),

    deleteAudit: (id: number) => api.delete(`api/stock-audits/${id}`),

    startAudit: (id: number) => api.post(`api/stock-audits/${id}/start`),

    completeAudit: (id: number) => api.post(`api/stock-audits/${id}/complete`),

    updateAuditItem: (auditId: number, itemId: number, data: any) =>
      api.put(`api/stock-audits/${auditId}/items/${itemId}`, data),

    // BOM (Bill of Materials)
    getBOMs: (filters?: Record<string, any>) =>
      api.get("api/bom", { params: filters }),

    getBOM: (id: number) => api.get(`api/bom/${id}`),

    createBOM: (data: any) => api.post("api/bom", data),

    updateBOM: (id: number, data: any) => api.put(`api/bom/${id}`, data),

    deleteBOM: (id: number) => api.delete(`api/bom/${id}`),

    getBOMForItem: (itemId: number) => api.get(`api/inventory/${itemId}/bom`),

    // Reports
    getLowStockItems: () => api.get("api/inventory/low-stock"),

    getExpiringItems: (days?: number) =>
      api.get("api/inventory/expiring", { params: { days } }),

    getInventoryValuation: (filters?: Record<string, any>) =>
      api.get("api/inventory-reports/inventory-analytics", { params: filters }),
  },

  // Invoices
  invoices: {
    getInvoices: (filters?: Record<string, any>) =>
      api.get("api/invoices", { params: filters }),

    getInvoice: (id: number) => api.get(`api/invoices/${id}`),

    createInvoice: (data: any) => api.post("api/invoices", data),

    updateInvoice: (id: number, data: any) =>
      api.put(`api/invoices/${id}`, data),

    deleteInvoice: (id: number) => api.delete(`api/invoices/${id}`),

    duplicateInvoice: (id: number) => api.post(`api/invoices/${id}/duplicate`),

    generatePDF: (id: number) => api.get(`api/invoices/${id}/pdf`),

    previewPDF: (id: number) => api.get(`api/invoices/${id}/preview`),

    sendInvoice: (id: number, data: { method: string }) =>
      api.post(`api/invoices/${id}/send`, data),

    // Templates
    getTemplates: () => api.get("api/invoice-templates"),

    getTemplate: (id: number) => api.get(`api/invoice-templates/${id}`),

    createTemplate: (data: any) => api.post("api/invoice-templates", data),

    updateTemplate: (id: number, data: any) =>
      api.put(`api/invoice-templates/${id}`, data),

    deleteTemplate: (id: number) => api.delete(`api/invoice-templates/${id}`),

    // Batch operations
    generateBatch: (invoiceIds: number[]) =>
      api.post("api/invoices/batch-pdf", { invoice_ids: invoiceIds }),

    downloadBatch: (invoiceIds: number[]) =>
      api.post("api/invoices/batch-download", { invoice_ids: invoiceIds }),

    sendBatch: (invoiceIds: number[], data: { method: string }) =>
      api.post("api/invoices/batch/send", { invoice_ids: invoiceIds, ...data }),

    // Recurring invoices
    getRecurringInvoices: () => api.get("api/recurring-invoices"),

    createRecurringInvoice: (data: any) =>
      api.post("api/recurring-invoices", data),

    updateRecurringInvoice: (id: number, data: any) =>
      api.put(`api/recurring-invoices/${id}`, data),

    deleteRecurringInvoice: (id: number) =>
      api.delete(`api/recurring-invoices/${id}`),
  },

  // Reports
  reports: {
    getTypes: () => api.get("api/reports/types"),

    generate: (data: {
      type: string;
      subtype: string;
      date_range: { start: string; end: string };
      filters?: Record<string, any>;
      language?: string;
      format?: string;
    }) => api.post("api/reports/generate", data),

    export: (data: { report_id: string; format: string }) =>
      api.post("api/reports/export", data),

    schedule: (data: {
      name: string;
      type: string;
      subtype: string;
      frequency: string;
      delivery_method: string;
      email_recipients?: string[];
      filters?: Record<string, any>;
    }) => api.post("api/reports/schedule", data),

    getScheduled: () => api.get("api/reports/scheduled"),

    deleteScheduled: (id: number) => api.delete(`api/reports/scheduled/${id}`),
  },
};

// Enhanced API service with session management utilities
export const enhancedApiService = {
  ...apiService,
  
  // Session management utilities
  session: {
    getId: () => SessionManager.getSessionId(),
    isValid: () => SessionManager.isSessionValid(),
    clear: () => SessionManager.clearSession(),
    setInfo: (sessionId: string, expiryDate: Date) => 
      SessionManager.setSessionInfo(sessionId, expiryDate)
  },
  
  // Logging utilities
  logging: {
    getInstance: () => ApiLogger.getInstance(),
    enableDebug: () => {
      const logger = ApiLogger.getInstance();
      logger['debugMode'] = true;
    },
    disableDebug: () => {
      const logger = ApiLogger.getInstance();
      logger['debugMode'] = false;
    }
  },
  
  // Error handling utilities
  errors: {
    categorize: (error: AxiosError) => ErrorCategorizer.categorizeError(error),
    isRetryable: (error: ApiError) => error.error.retryable === true,
    getErrorCode: (error: ApiError) => error.error.code,
    getErrorMessage: (error: ApiError) => error.error.message
  }
};

// Export direct HTTP methods
export const get = (url: string, config?: AxiosRequestConfig) =>
  api.get(url, config);
export const post = (url: string, data?: any, config?: AxiosRequestConfig) =>
  api.post(url, data, config);
export const put = (url: string, data?: any, config?: AxiosRequestConfig) =>
  api.put(url, data, config);
export const del = (url: string, config?: AxiosRequestConfig) =>
  api.delete(url, config);

// Export utility classes
export { ApiLogger, ErrorCategorizer, SessionManager };

export default api;
