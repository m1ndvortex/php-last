import axios from "axios";
import type {
  AxiosInstance,
  AxiosRequestConfig,
  AxiosResponse,
  AxiosError,
} from "axios";
import type { Customer } from "@/types";
import { useAuthStore } from "@/stores/auth";
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
      api.get("api/inventory/items", { params: filters }),

    getItem: (id: number) => api.get(`api/inventory/items/${id}`),

    createItem: (data: FormData) =>
      api.post("api/inventory/items", data, {
        headers: { "Content-Type": "multipart/form-data" },
      }),

    updateItem: (id: number, data: FormData) =>
      api.put(`api/inventory/items/${id}`, data, {
        headers: { "Content-Type": "multipart/form-data" },
      }),

    deleteItem: (id: number) => api.delete(`api/inventory/items/${id}`),

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

    deleteCategory: (id: number) =>
      api.delete(`api/categories/${id}`),

    reorderCategories: (data: any) =>
      api.post("api/categories/reorder", data),

    getCategoryHierarchy: () => api.get("api/categories/hierarchy"),

    uploadCategoryImage: (id: number, data: FormData) =>
      api.post(`api/categories/${id}/image`, data, {
        headers: { "Content-Type": "multipart/form-data" },
      }),

    removeCategoryImage: (id: number) =>
      api.delete(`api/categories/${id}/image`),

    getGoldPurityOptions: () =>
      api.get("api/categories/gold-purity-options"),

    getLocations: () => api.get("api/locations"),

    createLocation: (data: any) => api.post("api/inventory/locations", data),

    updateLocation: (id: number, data: any) =>
      api.put(`api/inventory/locations/${id}`, data),

    deleteLocation: (id: number) => api.delete(`api/inventory/locations/${id}`),

    // Stock Audit
    getAudits: (filters?: Record<string, any>) =>
      api.get("api/inventory/audits", { params: filters }),

    getAudit: (id: number) => api.get(`api/inventory/audits/${id}`),

    createAudit: (data: any) => api.post("api/inventory/audits", data),

    updateAudit: (id: number, data: any) =>
      api.put(`api/inventory/audits/${id}`, data),

    deleteAudit: (id: number) => api.delete(`api/inventory/audits/${id}`),

    startAudit: (id: number) => api.post(`api/inventory/audits/${id}/start`),

    completeAudit: (id: number) =>
      api.post(`api/inventory/audits/${id}/complete`),

    updateAuditItem: (auditId: number, itemId: number, data: any) =>
      api.put(`api/inventory/audits/${auditId}/items/${itemId}`, data),

    // BOM (Bill of Materials)
    getBOMs: (filters?: Record<string, any>) =>
      api.get("api/inventory/bom", { params: filters }),

    getBOM: (id: number) => api.get(`api/inventory/bom/${id}`),

    createBOM: (data: any) => api.post("api/inventory/bom", data),

    updateBOM: (id: number, data: any) =>
      api.put(`api/inventory/bom/${id}`, data),

    deleteBOM: (id: number) => api.delete(`api/inventory/bom/${id}`),

    getBOMForItem: (itemId: number) =>
      api.get(`api/inventory/items/${itemId}/bom`),

    // Reports
    getLowStockItems: () => api.get("api/inventory/reports/low-stock"),

    getExpiringItems: (days?: number) =>
      api.get("api/inventory/reports/expiring", { params: { days } }),

    getInventoryValuation: (filters?: Record<string, any>) =>
      api.get("api/inventory/reports/valuation", { params: filters }),
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
};

// Export direct HTTP methods
export const get = (url: string, config?: AxiosRequestConfig) => api.get(url, config);
export const post = (url: string, data?: any, config?: AxiosRequestConfig) => api.post(url, data, config);
export const put = (url: string, data?: any, config?: AxiosRequestConfig) => api.put(url, data, config);
export const del = (url: string, config?: AxiosRequestConfig) => api.delete(url, config);

export default api;
