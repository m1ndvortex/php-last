// Settings and configuration types

export interface BusinessConfiguration {
  id?: number;
  business_name: string;
  business_name_persian?: string;
  business_address?: string;
  business_address_persian?: string;
  business_phone?: string;
  business_email?: string;
  business_website?: string;
  tax_number?: string;
  registration_number?: string;
  logo_path?: string;
  default_currency: string;
  default_language: "en" | "fa";
  default_tax_rate: number;
  default_labor_percentage: number;
  default_profit_percentage: number;
  invoice_prefix: string;
  invoice_starting_number: number;
  fiscal_year_start: string;
  timezone: string;
  date_format: string;
  time_format: string;
  number_format: string;
  created_at?: string;
  updated_at?: string;
}

export interface Role {
  id: number;
  name: string;
  display_name: string;
  description?: string;
  permissions: Permission[];
  is_system: boolean;
  created_at: string;
  updated_at: string;
}

export interface Permission {
  id: number;
  name: string;
  display_name: string;
  description?: string;
  module: string;
  created_at: string;
  updated_at: string;
}

export interface MessageTemplate {
  id: number;
  name: string;
  type: "email" | "sms" | "whatsapp";
  subject?: string;
  content: string;
  content_persian?: string;
  variables: string[];
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface ThemeSettings {
  mode: "light" | "dark" | "system";
  primary_color: string;
  secondary_color: string;
  accent_color: string;
  sidebar_style: "expanded" | "collapsed" | "overlay";
  header_style: "fixed" | "static";
  font_size: "small" | "medium" | "large";
  border_radius: "none" | "small" | "medium" | "large";
}

export interface LanguageSettings {
  default_language: "en" | "fa";
  rtl_enabled: boolean;
  date_format: string;
  time_format: string;
  number_format: string;
  currency_format: string;
  calendar_type: "gregorian" | "jalali";
}

export interface SecuritySettings {
  two_factor_enabled: boolean;
  two_factor_method: "sms" | "totp" | "both";
  session_timeout: number; // in minutes
  max_login_attempts: number;
  lockout_duration: number; // in minutes
  password_min_length: number;
  password_require_uppercase: boolean;
  password_require_lowercase: boolean;
  password_require_numbers: boolean;
  password_require_symbols: boolean;
  ip_whitelist_enabled: boolean;
  ip_whitelist: string[];
  audit_log_retention: number; // in days
  login_anomaly_detection: boolean;
}

export interface BackupSettings {
  auto_backup_enabled: boolean;
  backup_frequency: "daily" | "weekly" | "monthly";
  backup_time: string; // HH:mm format
  backup_retention: number; // in days
  backup_location: "local" | "cloud";
  cloud_provider?: "aws" | "google" | "azure";
  cloud_credentials?: Record<string, any>;
  backup_encryption: boolean;
  backup_compression: boolean;
  include_files: boolean;
  exclude_tables: string[];
}

export interface AuditLogSettings {
  enabled: boolean;
  log_level: "basic" | "detailed" | "verbose";
  retention_days: number;
  log_user_actions: boolean;
  log_system_events: boolean;
  log_api_requests: boolean;
  log_database_changes: boolean;
  log_file_operations: boolean;
  alert_on_suspicious_activity: boolean;
  export_format: "json" | "csv" | "xml";
}

export interface NotificationSettings {
  email_notifications: boolean;
  sms_notifications: boolean;
  whatsapp_notifications: boolean;
  push_notifications: boolean;
  notification_types: {
    low_stock: boolean;
    expiring_items: boolean;
    overdue_invoices: boolean;
    birthday_reminders: boolean;
    anniversary_reminders: boolean;
    payment_received: boolean;
    system_alerts: boolean;
  };
  quiet_hours: {
    enabled: boolean;
    start_time: string;
    end_time: string;
  };
}

export interface SystemSettings {
  business: BusinessConfiguration;
  theme: ThemeSettings;
  language: LanguageSettings;
  security: SecuritySettings;
  backup: BackupSettings;
  audit: AuditLogSettings;
  notifications: NotificationSettings;
}

export interface SettingsTab {
  id: string;
  name: string;
  icon: string;
  component: string;
  permissions?: string[];
}

export interface TwoFactorSetup {
  secret: string;
  qr_code: string;
  backup_codes: string[];
}

export interface AuditLogEntry {
  id: number;
  user_id?: number;
  action: string;
  model_type?: string;
  model_id?: number;
  old_values?: Record<string, any>;
  new_values?: Record<string, any>;
  ip_address: string;
  user_agent: string;
  created_at: string;
  user?: {
    id: number;
    name: string;
    email: string;
  };
}

export interface LoginAnomaly {
  id: number;
  user_id: number;
  ip_address: string;
  user_agent: string;
  location?: string;
  risk_score: number;
  anomaly_type:
    | "unusual_location"
    | "unusual_time"
    | "unusual_device"
    | "multiple_attempts";
  status: "pending" | "approved" | "blocked";
  created_at: string;
  user: {
    id: number;
    name: string;
    email: string;
  };
}
