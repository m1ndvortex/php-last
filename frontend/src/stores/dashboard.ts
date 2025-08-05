import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { apiService } from "@/services/api";
import {
  generateSalesChartData,
  generateCategoryDistributionData,
  generateProfitLossData,
  generateCashFlowData,
  generateSalesFunnelData,
  generateCustomerAcquisitionData,
} from "@/utils/chartData";
import type {
  DashboardKPI,
  DashboardWidget,
  DashboardLayout,
  DashboardPreset,
  BusinessAlert,
} from "@/types/dashboard";

export const useDashboardStore = defineStore("dashboard", () => {
  // State
  const kpis = ref<DashboardKPI[]>([]);
  const widgets = ref<DashboardWidget[]>([]);
  const currentLayout = ref<DashboardLayout>({
    id: "default",
    name: "Default Layout",
    widgets: [],
  });
  const availablePresets = ref<DashboardPreset[]>([]);
  const activePreset = ref<string>("default");
  const alerts = ref<BusinessAlert[]>([]);
  const isLoading = ref(false);
  const lastUpdated = ref<string>("");

  // Getters
  const unreadAlerts = computed(() =>
    alerts.value.filter((alert) => !alert.read),
  );
  const criticalAlerts = computed(() =>
    alerts.value.filter((alert) => alert.severity === "critical"),
  );
  const alertCount = computed(() => unreadAlerts.value.length);
  const criticalAlertCount = computed(() => criticalAlerts.value.length);

  const getKPIByKey = computed(
    () => (key: string) => kpis.value.find((kpi) => kpi.key === key),
  );

  const getWidgetById = computed(
    () => (id: string) => widgets.value.find((widget) => widget.id === id),
  );

  // Actions
  const fetchKPIs = async () => {
    try {
      isLoading.value = true;
      const response = await apiService.dashboard.getKPIs();
      kpis.value = response.data.kpis || [];
      lastUpdated.value = new Date().toISOString();
    } catch (error) {
      console.warn("Failed to fetch KPIs from API, using default data:", error);
      // Set default KPIs for demo - this ensures the dashboard works even if backend is not ready
      kpis.value = getDefaultKPIs();
      lastUpdated.value = new Date().toISOString();
    } finally {
      isLoading.value = false;
    }
  };

  const fetchWidgets = async () => {
    try {
      isLoading.value = true;
      const response = await apiService.dashboard.getWidgets();
      widgets.value = response.data.widgets || [];
      if (response.data.layout) {
        currentLayout.value = response.data.layout;
      }
    } catch (error) {
      console.warn(
        "Failed to fetch widgets from API, using default data:",
        error,
      );
      // Set default widgets for demo - this ensures the dashboard works even if backend is not ready
      widgets.value = getDefaultWidgets();
      currentLayout.value.widgets = getDefaultWidgets();
    } finally {
      isLoading.value = false;
    }
  };

  const fetchAlerts = async () => {
    try {
      const response = await apiService.get("/api/dashboard/alerts");
      alerts.value = response.data.alerts || [];
    } catch (error) {
      console.warn(
        "Failed to fetch alerts from API, using default data:",
        error,
      );
      // Set default alerts for demo - this ensures the dashboard works even if backend is not ready
      alerts.value = getDefaultAlerts();
    }
  };

  const saveWidgetLayout = async (layout: DashboardLayout) => {
    try {
      await apiService.dashboard.saveWidgetLayout(layout);
      currentLayout.value = layout;
    } catch (error) {
      console.warn(
        "Failed to save widget layout to API, saving locally:",
        error,
      );
      // Save to localStorage as fallback when API is not available
      localStorage.setItem("dashboard_layout", JSON.stringify(layout));
      currentLayout.value = layout;
    }
  };

  const updateWidgetPosition = (
    widgetId: string,
    position: { x: number; y: number; w: number; h: number },
  ) => {
    const widget = widgets.value.find((w) => w.id === widgetId);
    if (widget) {
      widget.position = position;
    }
  };

  const addWidget = (widget: DashboardWidget) => {
    widgets.value.push(widget);
  };

  const removeWidget = (widgetId: string) => {
    const index = widgets.value.findIndex((w) => w.id === widgetId);
    if (index > -1) {
      widgets.value.splice(index, 1);
    }
  };

  const switchPreset = async (presetId: string) => {
    const preset = availablePresets.value.find((p) => p.id === presetId);
    if (preset) {
      activePreset.value = presetId;
      currentLayout.value = preset.layout;
      widgets.value = preset.layout.widgets;
      await saveWidgetLayout(preset.layout);
    }
  };

  const markAlertAsRead = (alertId: string) => {
    const alert = alerts.value.find((a) => a.id === alertId);
    if (alert) {
      alert.read = true;
    }
  };

  const markAllAlertsAsRead = () => {
    alerts.value.forEach((alert) => (alert.read = true));
  };

  const dismissAlert = (alertId: string) => {
    const index = alerts.value.findIndex((a) => a.id === alertId);
    if (index > -1) {
      alerts.value.splice(index, 1);
    }
  };

  const refreshData = async () => {
    await Promise.all([fetchKPIs(), fetchWidgets(), fetchAlerts()]);
  };

  const initializePresets = () => {
    availablePresets.value = [
      {
        id: "default",
        name: "Default View",
        description: "Standard dashboard with all widgets",
        layout: {
          id: "default",
          name: "Default Layout",
          widgets: getDefaultWidgets(),
        },
      },
      {
        id: "accountant",
        name: "Accountant View",
        description: "Financial focused dashboard",
        role: "accountant",
        layout: {
          id: "accountant",
          name: "Accountant Layout",
          widgets: getAccountantWidgets(),
        },
      },
      {
        id: "sales",
        name: "Sales View",
        description: "Sales and customer focused dashboard",
        role: "sales",
        layout: {
          id: "sales",
          name: "Sales Layout",
          widgets: getSalesWidgets(),
        },
      },
    ];
  };

  // Default data functions
  const getDefaultKPIs = (): DashboardKPI[] => [
    {
      key: "gold_sold",
      label: "Gold Sold",
      value: "12.5",
      change: 8.2,
      changeType: "increase",
      format: "weight",
      color: "yellow",
    },
    {
      key: "total_profit",
      label: "Total Profit",
      value: 45230,
      change: 12.5,
      changeType: "increase",
      format: "currency",
      color: "green",
    },
    {
      key: "average_price",
      label: "Average Price",
      value: 1850,
      change: -2.1,
      changeType: "decrease",
      format: "currency",
      color: "blue",
    },
    {
      key: "returns",
      label: "Returns",
      value: 2.3,
      change: -0.5,
      changeType: "decrease",
      format: "percentage",
      color: "red",
    },
    {
      key: "gross_margin",
      label: "Gross Margin",
      value: 35.2,
      change: 1.8,
      changeType: "increase",
      format: "percentage",
      color: "purple",
    },
    {
      key: "net_margin",
      label: "Net Margin",
      value: 28.7,
      change: 2.3,
      changeType: "increase",
      format: "percentage",
      color: "indigo",
    },
  ];

  const getDefaultWidgets = (): DashboardWidget[] => [
    {
      id: "sales-chart",
      type: "chart",
      title: "Sales Overview",
      position: { x: 0, y: 0, w: 8, h: 4 },
      data: {
        chartType: "line",
        chartData: generateSalesChartData("monthly"),
      },
    },
    {
      id: "alerts",
      type: "alert",
      title: "Business Alerts",
      position: { x: 8, y: 0, w: 4, h: 4 },
    },
    {
      id: "category-distribution",
      type: "chart",
      title: "Category Distribution",
      position: { x: 0, y: 4, w: 6, h: 3 },
      data: {
        chartType: "doughnut",
        chartData: generateCategoryDistributionData(),
      },
    },
    {
      id: "recent-activities",
      type: "table",
      title: "Recent Activities",
      position: { x: 6, y: 4, w: 6, h: 3 },
    },
  ];

  const getAccountantWidgets = (): DashboardWidget[] => [
    {
      id: "profit-loss-chart",
      type: "chart",
      title: "Profit & Loss",
      position: { x: 0, y: 0, w: 6, h: 4 },
      data: {
        chartType: "bar",
        chartData: generateProfitLossData(),
      },
    },
    {
      id: "cash-flow-chart",
      type: "chart",
      title: "Cash Flow",
      position: { x: 6, y: 0, w: 6, h: 4 },
      data: {
        chartType: "line",
        chartData: generateCashFlowData(),
      },
    },
    {
      id: "pending-transactions",
      type: "table",
      title: "Pending Transactions",
      position: { x: 0, y: 4, w: 8, h: 3 },
    },
    {
      id: "financial-alerts",
      type: "alert",
      title: "Financial Alerts",
      position: { x: 8, y: 4, w: 4, h: 3 },
    },
  ];

  const getSalesWidgets = (): DashboardWidget[] => [
    {
      id: "sales-funnel",
      type: "chart",
      title: "Sales Funnel",
      position: { x: 0, y: 0, w: 4, h: 4 },
      data: {
        chartType: "bar",
        chartData: generateSalesFunnelData(),
      },
    },
    {
      id: "customer-acquisition",
      type: "chart",
      title: "Customer Acquisition",
      position: { x: 4, y: 0, w: 4, h: 4 },
      data: {
        chartType: "bar",
        chartData: generateCustomerAcquisitionData(),
      },
    },
    {
      id: "top-customers",
      type: "table",
      title: "Top Customers",
      position: { x: 8, y: 0, w: 4, h: 4 },
    },
    {
      id: "sales-activities",
      type: "table",
      title: "Recent Sales",
      position: { x: 0, y: 4, w: 8, h: 3 },
    },
    {
      id: "customer-alerts",
      type: "alert",
      title: "Customer Alerts",
      position: { x: 8, y: 4, w: 4, h: 3 },
    },
  ];

  const getDefaultAlerts = (): BusinessAlert[] => [
    {
      id: "1",
      type: "pending_cheque",
      title: "Pending Cheque",
      message: "Cheque #CH-2024-001 due tomorrow",
      severity: "high",
      timestamp: new Date().toISOString(),
      read: false,
      actionUrl: "/accounting/cheques",
      actionLabel: "View Cheques",
    },
    {
      id: "2",
      type: "low_stock",
      title: "Low Stock Alert",
      message: "5 items are running low on stock",
      severity: "medium",
      timestamp: new Date().toISOString(),
      read: false,
      actionUrl: "/inventory",
      actionLabel: "View Inventory",
    },
    {
      id: "3",
      type: "expiring_item",
      title: "Items Expiring Soon",
      message: "3 items will expire within 7 days",
      severity: "medium",
      timestamp: new Date().toISOString(),
      read: false,
      actionUrl: "/inventory/expiring",
      actionLabel: "View Items",
    },
  ];

  // Initialize
  const initialize = async () => {
    initializePresets();
    await refreshData();
  };

  return {
    // State
    kpis,
    widgets,
    currentLayout,
    availablePresets,
    activePreset,
    alerts,
    isLoading,
    lastUpdated,

    // Getters
    unreadAlerts,
    criticalAlerts,
    alertCount,
    criticalAlertCount,
    getKPIByKey,
    getWidgetById,

    // Actions
    fetchKPIs,
    fetchWidgets,
    fetchAlerts,
    saveWidgetLayout,
    updateWidgetPosition,
    addWidget,
    removeWidget,
    switchPreset,
    markAlertAsRead,
    markAllAlertsAsRead,
    dismissAlert,
    refreshData,
    initialize,
  };
});
