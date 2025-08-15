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

  // Helper functions
  const calculateChange = (current: number, previous?: number): number => {
    if (!previous || previous === 0) return 0;
    return ((current - previous) / previous) * 100;
  };

  const getChangeType = (current: number, previous?: number, inverse = false): "increase" | "decrease" => {
    if (!previous) return "increase";
    const isIncrease = current > previous;
    return inverse ? (isIncrease ? "decrease" : "increase") : (isIncrease ? "increase" : "decrease");
  };

  // Actions
  const fetchKPIs = async () => {
    try {
      isLoading.value = true;
      const response = await apiService.dashboard.getKPIs();
      
      if (response.data.success && response.data.data) {
        // Transform backend data to frontend format
        const backendData = response.data.data;
        kpis.value = [
          {
            key: "gold_sold",
            label: "Gold Sold",
            value: backendData.gold_sold || 0,
            formattedValue: `${(backendData.gold_sold || 0).toFixed(1)} kg`,
            change: calculateChange(backendData.gold_sold, backendData.gold_sold_previous),
            changeType: getChangeType(backendData.gold_sold, backendData.gold_sold_previous),
            format: "weight",
            color: "yellow",
          },
          {
            key: "total_profit",
            label: "Total Profit",
            value: backendData.total_profits || 0,
            formattedValue: `$${(backendData.total_profits || 0).toLocaleString()}`,
            change: calculateChange(backendData.total_profits, backendData.total_profits_previous),
            changeType: getChangeType(backendData.total_profits, backendData.total_profits_previous),
            format: "currency",
            color: "green",
          },
          {
            key: "average_price",
            label: "Average Price",
            value: backendData.average_price || 0,
            formattedValue: `$${(backendData.average_price || 0).toLocaleString()}`,
            change: calculateChange(backendData.average_price, backendData.average_price_previous),
            changeType: getChangeType(backendData.average_price, backendData.average_price_previous),
            format: "currency",
            color: "blue",
          },
          {
            key: "returns",
            label: "Returns",
            value: backendData.returns || 0,
            formattedValue: `$${(backendData.returns || 0).toLocaleString()}`,
            change: calculateChange(backendData.returns, backendData.returns_previous),
            changeType: getChangeType(backendData.returns, backendData.returns_previous, true), // Inverse for returns
            format: "currency",
            color: "red",
          },
          {
            key: "gross_margin",
            label: "Gross Margin",
            value: backendData.gross_margin || 0,
            formattedValue: `${(backendData.gross_margin || 0).toFixed(1)}%`,
            change: calculateChange(backendData.gross_margin, backendData.gross_margin_previous),
            changeType: getChangeType(backendData.gross_margin, backendData.gross_margin_previous),
            format: "percentage",
            color: "purple",
          },
          {
            key: "net_margin",
            label: "Net Margin",
            value: backendData.net_margin || 0,
            formattedValue: `${(backendData.net_margin || 0).toFixed(1)}%`,
            change: calculateChange(backendData.net_margin, backendData.net_margin_previous),
            changeType: getChangeType(backendData.net_margin, backendData.net_margin_previous),
            format: "percentage",
            color: "indigo",
          },
        ];
      } else {
        // Fallback to default data if API response is not in expected format
        kpis.value = getDefaultKPIs();
      }
      
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

  const fetchSalesChartData = async (period: string = 'month') => {
    try {
      const response = await apiService.dashboard.getSalesChart(period);
      
      if (response.data.success && response.data.data) {
        const salesData = response.data.data;
        
        // Transform backend data to Chart.js format
        return {
          labels: salesData.map((item: any) => item.label),
          datasets: [
            {
              label: 'Sales',
              data: salesData.map((item: any) => item.sales),
              borderColor: 'rgb(59, 130, 246)',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              tension: 0.4,
            }
          ]
        };
      }
    } catch (error) {
      console.warn("Failed to fetch sales chart data:", error);
    }
    
    // Return default chart data
    return generateSalesChartData(period as "daily" | "weekly" | "monthly" | "yearly");
  };

  const fetchWidgets = async () => {
    try {
      isLoading.value = true;
      
      // Fetch sales chart data
      const salesChartData = await fetchSalesChartData('month');
      
      // Create widgets with real data
      widgets.value = [
        {
          id: "sales-chart",
          type: "chart",
          title: "Sales Overview",
          position: { x: 0, y: 0, w: 8, h: 4 },
          data: {
            chartType: "line",
            chartData: salesChartData,
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
      
      currentLayout.value.widgets = widgets.value;
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

  const alertsMetadata = ref({
    total: 0,
    hasMore: false,
    currentOffset: 0,
  });

  const fetchAlerts = async (loadMore = false) => {
    try {
      const offset = loadMore ? alerts.value.length : 0;
      const response = await apiService.dashboard.getAlerts({ 
        limit: 10, 
        offset 
      });
      
      if (response.data.success && response.data.data?.alerts) {
        // Transform backend alerts to frontend format
        const newAlerts = response.data.data.alerts.map((alert: any) => ({
          id: alert.id.toString(),
          type: alert.type || 'general',
          title: alert.title,
          message: alert.message,
          severity: alert.severity || 'medium',
          timestamp: alert.timestamp || new Date().toISOString(),
          read: alert.read || false,
          actionUrl: alert.action_url,
          actionLabel: alert.action_label,
        }));

        if (loadMore) {
          alerts.value = [...alerts.value, ...newAlerts];
        } else {
          alerts.value = newAlerts;
        }

        alertsMetadata.value = {
          total: response.data.data.total || 0,
          hasMore: response.data.data.has_more || false,
          currentOffset: offset + newAlerts.length,
        };
      } else {
        if (!loadMore) {
          alerts.value = getDefaultAlerts();
        }
      }
    } catch (error) {
      console.warn(
        "Failed to fetch alerts from API, using default data:",
        error,
      );
      // Set default alerts for demo - this ensures the dashboard works even if backend is not ready
      if (!loadMore) {
        alerts.value = getDefaultAlerts();
      }
    }
  };

  const loadMoreAlerts = async () => {
    if (alertsMetadata.value.hasMore) {
      await fetchAlerts(true);
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

  const markAlertAsRead = async (alertId: string) => {
    try {
      await apiService.dashboard.markAlertAsRead(alertId);
      const alert = alerts.value.find((a) => a.id === alertId);
      if (alert) {
        alert.read = true;
      }
    } catch (error) {
      console.error('Failed to mark alert as read:', error);
      // Still update locally for better UX
      const alert = alerts.value.find((a) => a.id === alertId);
      if (alert) {
        alert.read = true;
      }
    }
  };

  const markAllAlertsAsRead = async () => {
    try {
      // Mark all alerts as read via API calls
      const unreadAlerts = alerts.value.filter(alert => !alert.read);
      await Promise.all(
        unreadAlerts.map(alert => apiService.dashboard.markAlertAsRead(alert.id))
      );
      alerts.value.forEach((alert) => (alert.read = true));
    } catch (error) {
      console.error('Failed to mark all alerts as read:', error);
      // Still update locally for better UX
      alerts.value.forEach((alert) => (alert.read = true));
    }
  };

  const dismissAlert = async (alertId: string) => {
    try {
      // For now, just mark as read since we don't have a dismiss endpoint
      await markAlertAsRead(alertId);
      const index = alerts.value.findIndex((a) => a.id === alertId);
      if (index > -1) {
        alerts.value.splice(index, 1);
      }
    } catch (error) {
      console.error('Failed to dismiss alert:', error);
      // Still remove locally for better UX
      const index = alerts.value.findIndex((a) => a.id === alertId);
      if (index > -1) {
        alerts.value.splice(index, 1);
      }
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
    alertsMetadata,
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
    loadMoreAlerts,
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
