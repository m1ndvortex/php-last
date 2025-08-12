import axios from "axios";
import type {
  AxiosInstance,
  AxiosRequestConfig,
  AxiosResponse,
  AxiosError,
} from "axios";
import type { Customer } from "@/types";
import router from "@/router";

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

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Add auth token if available - get directly from localStorage to avoid circular dependency
    const token = localStorage.getItem("auth_token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
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

// Helper function to handle logout without circular dependency
const handleLogout = () => {
  localStorage.removeItem("auth_token");
  router.push("/login");
};

// Response interceptor with enhanced error handling and retry logic
api.interceptors.response.use(
  (response: AxiosResponse) => {
    return response;
  },
  async (error: AxiosError) => {
    const config = error.config as any;
    
    // Initialize retry metadata if not present
    if (!config.metadata) {
      config.metadata = { retryCount: 0 };
    }

    // Enhanced error logging
    console.error("API Error Details:", {
      url: config?.url,
      method: config?.method,
      status: error.response?.status,
      statusText: error.response?.statusText,
      data: error.response?.data,
      message: error.message,
      retryCount: config.metadata.retryCount,
      timestamp: new Date().toISOString()
    });

    // Handle authentication errors with token refresh
    if (error.response?.status === 401) {
      const errorData = error.response.data as any;
      
      // Don't retry auth endpoints or if already retried
      if (config.url?.includes('/auth/') || config.metadata.retryCount > 0) {
        console.error("Authentication failed:", errorData);
        handleLogout();
        return Promise.reject(error);
      }

      // Try to refresh token
      try {
        const refreshResponse = await api.post("api/auth/refresh");
        if (refreshResponse.data.success) {
          const { token: newToken } = refreshResponse.data.data;
          localStorage.setItem("auth_token", newToken);
          
          // Retry original request with new token
          config.headers.Authorization = `Bearer ${newToken}`;
          config.metadata.retryCount++;
          return api.request(config);
        }
      } catch (refreshError) {
        console.error("Token refresh failed:", refreshError);
        handleLogout();
        return Promise.reject(error);
      }
    }

    // Handle network errors with retry logic
    if (!error.response && config.metadata.retryCount < 3) {
      const delay = Math.pow(2, config.metadata.retryCount) * 1000; // Exponential backoff
      console.log(`Retrying request in ${delay}ms... (attempt ${config.metadata.retryCount + 1})`);
      
      config.metadata.retryCount++;
      await new Promise(resolve => setTimeout(resolve, delay));
      return api.request(config);
    }

    // Handle other error status codes
    if (error.response) {
      const errorData = error.response.data as any;
      
      switch (error.response.status) {
        case 403:
          // Forbidden - show error message
          console.error("Access forbidden:", errorData);
          showErrorNotification(
            errorData?.error?.message || errorData?.message || "Access forbidden. You don't have permission to perform this action."
          );
          break;

        case 404:
          // Not found
          console.error("Resource not found:", errorData);
          showErrorNotification(
            errorData?.error?.message || errorData?.message || "The requested resource was not found."
          );
          break;

        case 422:
          // Validation errors - handle specially for form validation
          console.error("Validation errors:", errorData);
          if (errorData?.error === 'insufficient_inventory') {
            // Let the component handle insufficient inventory errors
            break;
          }
          showErrorNotification(
            errorData?.error?.message || errorData?.message || "The provided data is invalid."
          );
          break;

        case 429:
          // Too many requests
          console.error("Rate limit exceeded:", errorData);
          showErrorNotification(
            errorData?.error?.message || errorData?.message || "Too many requests. Please try again later."
          );
          break;

        case 500:
        case 502:
        case 503:
        case 504:
          // Server errors - retry if not already retried
          console.error("Server error:", errorData);
          if (config.metadata.retryCount < 2) {
            const delay = Math.pow(2, config.metadata.retryCount) * 1000;
            console.log(`Retrying server error in ${delay}ms...`);
            
            config.metadata.retryCount++;
            await new Promise(resolve => setTimeout(resolve, delay));
            return api.request(config);
          }
          
          showErrorNotification(
            errorData?.error?.message || errorData?.message || "Server error occurred. Please try again later."
          );
          break;

        default:
          console.error("API Error:", errorData);
          showErrorNotification(
            errorData?.error?.message || errorData?.message || "An unexpected error occurred."
          );
      }
    } else if (error.request) {
      // Network error - no response received
      console.error("Network error - no response:", error.request);
      showErrorNotification(
        "Network error. Please check your internet connection and try again."
      );
    } else {
      // Request setup error
      console.error("Request setup error:", error.message);
      showErrorNotification("An unexpected error occurred while making the request.");
    }

    return Promise.reject(error);
  },
);

// Helper function to show error notifications
const showErrorNotification = (message: string) => {
  // TODO: Implement toast notification system
  console.error(message);
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

    // Session Management
    validateSession: () => api.post("api/auth/validate-session"),

    extendSession: () => api.post("api/auth/extend-session"),

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

// Export direct HTTP methods
export const get = (url: string, config?: AxiosRequestConfig) =>
  api.get(url, config);
export const post = (url: string, data?: any, config?: AxiosRequestConfig) =>
  api.post(url, data, config);
export const put = (url: string, data?: any, config?: AxiosRequestConfig) =>
  api.put(url, data, config);
export const del = (url: string, config?: AxiosRequestConfig) =>
  api.delete(url, config);

export default api;
