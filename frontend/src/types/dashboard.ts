// Dashboard-specific types

export interface DashboardKPI {
  key: string;
  label: string;
  value: string | number;
  change?: number;
  changeType?: "increase" | "decrease" | "neutral";
  icon?: string;
  color?: string;
  format?: "currency" | "percentage" | "number" | "weight";
}

export interface DashboardWidget {
  id: string;
  type: "kpi" | "chart" | "alert" | "table" | "custom";
  title: string;
  position: {
    x: number;
    y: number;
    w: number;
    h: number;
  };
  data?: any;
  config?: {
    refreshInterval?: number;
    showHeader?: boolean;
    allowResize?: boolean;
    allowMove?: boolean;
  };
}

export interface DashboardLayout {
  id: string;
  name: string;
  widgets: DashboardWidget[];
  isDefault?: boolean;
}

export interface DashboardPreset {
  id: string;
  name: string;
  description: string;
  layout: DashboardLayout;
  role?: string;
}

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

export interface SalesChartData extends ChartData {
  period: "daily" | "weekly" | "monthly" | "yearly";
  currency: string;
}

export interface CategoryPerformance {
  category: string;
  sales: number;
  profit: number;
  margin: number;
  change: number;
}

export interface BusinessAlert {
  id: string;
  type:
    | "pending_cheque"
    | "low_stock"
    | "expiring_item"
    | "overdue_invoice"
    | "system";
  title: string;
  message: string;
  severity: "low" | "medium" | "high" | "critical";
  timestamp: string;
  read: boolean;
  actionUrl?: string;
  actionLabel?: string;
  data?: any;
}

export interface DashboardState {
  kpis: DashboardKPI[];
  widgets: DashboardWidget[];
  currentLayout: DashboardLayout;
  availablePresets: DashboardPreset[];
  activePreset: string;
  alerts: BusinessAlert[];
  isLoading: boolean;
  lastUpdated: string;
}
