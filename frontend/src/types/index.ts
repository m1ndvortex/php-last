// API Response types
export interface ApiResponse<T = any> {
  success: boolean;
  data: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

// Common types
export interface SelectOption {
  value: string | number;
  label: string;
  disabled?: boolean;
}

export interface TableColumn {
  key: string;
  label: string;
  sortable?: boolean;
  width?: string;
  align?: "left" | "center" | "right";
}

export interface FilterOption {
  key: string;
  label: string;
  type: "text" | "select" | "date" | "daterange" | "number";
  options?: SelectOption[];
  placeholder?: string;
}

// Form types
export interface FormField {
  name: string;
  label: string;
  type:
    | "text"
    | "email"
    | "password"
    | "number"
    | "select"
    | "textarea"
    | "checkbox"
    | "radio"
    | "date";
  required?: boolean;
  placeholder?: string;
  options?: SelectOption[];
  validation?: any;
}

export interface FormErrors {
  [key: string]: string[];
}

// Navigation types
export interface NavigationItem {
  name: string;
  href: string;
  icon: any;
  current?: boolean;
  children?: NavigationItem[];
}

// Language types
export interface Language {
  code: string;
  name: string;
  flag: string;
  dir: "ltr" | "rtl";
}

// Dashboard types
export interface KPI {
  id: string;
  title: string;
  value: string | number;
  change?: {
    value: number;
    type: "increase" | "decrease";
    period: string;
  };
  icon?: any;
  color?: string;
}

export interface Widget {
  id: string;
  type: "kpi" | "chart" | "table" | "alert";
  title: string;
  size: "small" | "medium" | "large";
  position: {
    x: number;
    y: number;
    w: number;
    h: number;
  };
  data?: any;
  config?: any;
}

// Chart types
export interface ChartData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    backgroundColor?: string | string[];
    borderColor?: string | string[];
    borderWidth?: number;
  }[];
}

export interface ChartOptions {
  responsive: boolean;
  maintainAspectRatio: boolean;
  plugins?: any;
  scales?: any;
}

// Date types
export interface DateRange {
  start: Date | null;
  end: Date | null;
}

// File types
export interface FileUpload {
  file: File;
  progress: number;
  status: "pending" | "uploading" | "success" | "error";
  error?: string;
}

// Notification types
export interface Notification {
  id: string;
  type: "success" | "error" | "warning" | "info";
  title: string;
  message: string;
  timestamp: Date;
  read: boolean;
  duration?: number;
  persistent?: boolean;
  action?: {
    label: string;
    handler: () => void | Promise<void>;
  };
  actions?: {
    label: string;
    action: () => void;
  }[];
}

// Modal types
export interface ModalOptions {
  title: string;
  message?: string;
  type?: "info" | "warning" | "error" | "success";
  confirmText?: string;
  cancelText?: string;
  onConfirm?: () => void | Promise<void>;
  onCancel?: () => void;
}

// Loading states
export interface LoadingState {
  [key: string]: boolean;
}

// Error types
export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status?: number;
}

// Validation types
export interface ValidationRule {
  required?: boolean;
  min?: number;
  max?: number;
  email?: boolean;
  pattern?: RegExp;
  custom?: (value: any) => boolean | string;
}

export interface ValidationErrors {
  [field: string]: string[];
}

// Theme types
export interface Theme {
  mode: "light" | "dark";
  primaryColor: string;
  secondaryColor?: string;
  accentColor?: string;
}

// Permission types
export interface Permission {
  id: string;
  name: string;
  description?: string;
}

export interface Role {
  id: string;
  name: string;
  permissions: Permission[];
}

// Export all types
export * from "./auth";
export * from "./business";
export * from "./router";
