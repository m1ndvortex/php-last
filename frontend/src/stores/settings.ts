import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { apiService } from "@/services/api";
import type {
  SystemSettings,
  BusinessConfiguration,
  Role,
  Permission,
  MessageTemplate,
  ThemeSettings,
  LanguageSettings,
  SecuritySettings,
  BackupSettings,
  AuditLogSettings,
  NotificationSettings,
  TwoFactorSetup,
  AuditLogEntry,
  LoginAnomaly,
} from "@/types/settings";

export const useSettingsStore = defineStore("settings", () => {
  // State
  const settings = ref<SystemSettings | null>(null);
  const roles = ref<Role[]>([]);
  const permissions = ref<Permission[]>([]);
  const messageTemplates = ref<MessageTemplate[]>([]);
  const auditLogs = ref<AuditLogEntry[]>([]);
  const loginAnomalies = ref<LoginAnomaly[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  // Getters
  const businessConfig = computed(() => settings.value?.business || null);
  const themeSettings = computed(() => settings.value?.theme || null);
  const languageSettings = computed(() => settings.value?.language || null);
  const securitySettings = computed(() => settings.value?.security || null);
  const backupSettings = computed(() => settings.value?.backup || null);
  const auditSettings = computed(() => settings.value?.audit || null);
  const notificationSettings = computed(() => settings.value?.notifications || null);

  // Actions
  const fetchSettings = async () => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.get("/api/settings");
      settings.value = response.data.data;

      return { success: true };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to fetch settings";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateBusinessConfig = async (config: Partial<BusinessConfiguration>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put("/api/config/business-info", config);
      
      if (settings.value) {
        settings.value.business = { ...settings.value.business, ...response.data.data };
      }

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update business configuration";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const getDefaultPricingPercentages = async () => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.get("/api/config/pricing-percentages");
      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to fetch default pricing percentages";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateDefaultPricingPercentages = async (percentages: {
    labor_percentage: number;
    profit_percentage: number;
    tax_percentage: number;
  }) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put("/api/config/pricing-percentages", percentages);
      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update default pricing percentages";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const getAllConfigurations = async () => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.get("/api/config/all");
      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to fetch all configurations";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateThemeSettings = async (theme: Partial<ThemeSettings>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put("/api/settings/theme", theme);
      
      if (settings.value) {
        settings.value.theme = { ...settings.value.theme, ...response.data.data };
      }

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update theme settings";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateLanguageSettings = async (language: Partial<LanguageSettings>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put("/api/settings/language", language);
      
      if (settings.value) {
        settings.value.language = { ...settings.value.language, ...response.data.data };
      }

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update language settings";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateSecuritySettings = async (security: Partial<SecuritySettings>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put("/api/settings/security", security);
      
      if (settings.value) {
        settings.value.security = { ...settings.value.security, ...response.data.data };
      }

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update security settings";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateBackupSettings = async (backup: Partial<BackupSettings>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put("/api/settings/backup", backup);
      
      if (settings.value) {
        settings.value.backup = { ...settings.value.backup, ...response.data.data };
      }

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update backup settings";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateAuditSettings = async (audit: Partial<AuditLogSettings>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put("/api/settings/audit", audit);
      
      if (settings.value) {
        settings.value.audit = { ...settings.value.audit, ...response.data.data };
      }

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update audit settings";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateNotificationSettings = async (notifications: Partial<NotificationSettings>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put("/api/settings/notifications", notifications);
      
      if (settings.value) {
        settings.value.notifications = { ...settings.value.notifications, ...response.data.data };
      }

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update notification settings";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  // Role and Permission Management
  const fetchRoles = async () => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.get("/api/roles");
      roles.value = response.data.data;

      return { success: true };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to fetch roles";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const fetchPermissions = async () => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.get("/api/permissions");
      permissions.value = response.data.data;

      return { success: true };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to fetch permissions";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const createRole = async (roleData: Partial<Role>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.post("/api/roles", roleData);
      roles.value.push(response.data.data);

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to create role";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateRole = async (id: number, roleData: Partial<Role>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put(`/api/roles/${id}`, roleData);
      const index = roles.value.findIndex(role => role.id === id);
      if (index !== -1) {
        roles.value[index] = response.data.data;
      }

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update role";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const deleteRole = async (id: number) => {
    try {
      isLoading.value = true;
      error.value = null;

      await apiService.delete(`/api/roles/${id}`);
      roles.value = roles.value.filter(role => role.id !== id);

      return { success: true };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to delete role";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  // Message Template Management
  const fetchMessageTemplates = async () => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.get("/api/message-templates");
      messageTemplates.value = response.data.data;

      return { success: true };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to fetch message templates";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const createMessageTemplate = async (templateData: Partial<MessageTemplate>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.post("/api/message-templates", templateData);
      messageTemplates.value.push(response.data.data);

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to create message template";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateMessageTemplate = async (id: number, templateData: Partial<MessageTemplate>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put(`/api/message-templates/${id}`, templateData);
      const index = messageTemplates.value.findIndex(template => template.id === id);
      if (index !== -1) {
        messageTemplates.value[index] = response.data.data;
      }

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update message template";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const deleteMessageTemplate = async (id: number) => {
    try {
      isLoading.value = true;
      error.value = null;

      await apiService.delete(`/api/message-templates/${id}`);
      messageTemplates.value = messageTemplates.value.filter(template => template.id !== id);

      return { success: true };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to delete message template";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  // Two-Factor Authentication
  const setupTwoFactor = async (method: "sms" | "totp") => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.post("/api/auth/2fa/setup", { method });
      return { success: true, data: response.data.data as TwoFactorSetup };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to setup two-factor authentication";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const verifyTwoFactor = async (code: string) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.post("/api/auth/2fa/verify", { code });
      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to verify two-factor code";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const disableTwoFactor = async () => {
    try {
      isLoading.value = true;
      error.value = null;

      await apiService.post("/api/auth/2fa/disable");
      return { success: true };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to disable two-factor authentication";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  // Audit Logs
  const fetchAuditLogs = async (filters?: Record<string, any>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.get("/api/audit-logs", { params: filters });
      auditLogs.value = response.data.data;

      return { success: true };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to fetch audit logs";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const exportAuditLogs = async (format: "json" | "csv" | "xml", filters?: Record<string, any>) => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.get("/api/audit-logs/export", {
        params: { format, ...filters },
        responseType: "blob",
      });

      return { success: true, data: response.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to export audit logs";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  // Login Anomalies
  const fetchLoginAnomalies = async () => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.get("/api/security/login-anomalies");
      loginAnomalies.value = response.data.data;

      return { success: true };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to fetch login anomalies";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const updateAnomalyStatus = async (id: number, status: "approved" | "blocked") => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.put(`/api/security/login-anomalies/${id}`, { status });
      const index = loginAnomalies.value.findIndex(anomaly => anomaly.id === id);
      if (index !== -1) {
        loginAnomalies.value[index] = response.data.data;
      }

      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to update anomaly status";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  // Backup Management
  const createBackup = async () => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.post("/api/backups");
      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to create backup";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  const testBackupConnection = async () => {
    try {
      isLoading.value = true;
      error.value = null;

      const response = await apiService.post("/api/backups/test-connection");
      return { success: true, data: response.data.data };
    } catch (err: any) {
      error.value = err.response?.data?.message || "Failed to test backup connection";
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  };

  return {
    // State
    settings,
    roles,
    permissions,
    messageTemplates,
    auditLogs,
    loginAnomalies,
    isLoading,
    error,

    // Getters
    businessConfig,
    themeSettings,
    languageSettings,
    securitySettings,
    backupSettings,
    auditSettings,
    notificationSettings,

    // Actions
    fetchSettings,
    updateBusinessConfig,
    getDefaultPricingPercentages,
    updateDefaultPricingPercentages,
    getAllConfigurations,
    updateThemeSettings,
    updateLanguageSettings,
    updateSecuritySettings,
    updateBackupSettings,
    updateAuditSettings,
    updateNotificationSettings,
    fetchRoles,
    fetchPermissions,
    createRole,
    updateRole,
    deleteRole,
    fetchMessageTemplates,
    createMessageTemplate,
    updateMessageTemplate,
    deleteMessageTemplate,
    setupTwoFactor,
    verifyTwoFactor,
    disableTwoFactor,
    fetchAuditLogs,
    exportAuditLogs,
    fetchLoginAnomalies,
    updateAnomalyStatus,
    createBackup,
    testBackupConnection,
  };
});