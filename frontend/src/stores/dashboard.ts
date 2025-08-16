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

// Enhanced caching service for dashboard data
class DashboardCache {
  private cache = new Map<string, { data: any; timestamp: number; ttl: number }>();
  
  set(key: string, data: any, ttl: number = 300000): void { // 5 minutes default TTL
    this.cache.set(key, {
      data,
      timestamp: Date.now(),
      ttl
    });
  }
  
  get(key: string): any | null {
    const cached = this.cache.get(key);
    if (!cached) return null;
    
    if (Date.now() - cached.timestamp > cached.ttl) {
      this.cache.delete(key);
      return null;
    }
    
    return cached.data;
  }
  
  invalidate(pattern?: string): void {
    if (!pattern) {
      this.cache.clear();
      return;
    }
    
    for (const key of this.cache.keys()) {
      if (key.includes(pattern)) {
        this.cache.delete(key);
      }
    }
  }
  
  has(key: string): boolean {
    const cached = this.cache.get(key);
    if (!cached) return false;
    
    return Date.now() - cached.timestamp <= cached.ttl;
  }
}

// Parallel data loader for dashboard components
class ParallelDataLoader {
  private loadingPromises = new Map<string, Promise<any>>();
  
  async loadInParallel<T>(
    loaders: Record<string, () => Promise<T>>,
    options: { 
      timeout?: number;
      retryCount?: number;
      showCriticalFirst?: boolean;
    } = {}
  ): Promise<Record<string, T | null>> {
    const { timeout = 10000, retryCount = 2, showCriticalFirst = true } = options;
    
    // Define critical data that should load first
    const criticalKeys = showCriticalFirst ? ['kpis', 'alerts'] : [];
    const regularKeys = Object.keys(loaders).filter(key => !criticalKeys.includes(key));
    
    const results: Record<string, T | null> = {};
    
    // Load critical data first
    if (criticalKeys.length > 0) {
      const criticalLoaders = criticalKeys.reduce((acc, key) => {
        if (loaders[key]) acc[key] = loaders[key];
        return acc;
      }, {} as Record<string, () => Promise<T>>);
      
      const criticalResults = await this.executeLoaders(criticalLoaders, timeout, retryCount);
      Object.assign(results, criticalResults);
    }
    
    // Load regular data in parallel
    if (regularKeys.length > 0) {
      const regularLoaders = regularKeys.reduce((acc, key) => {
        acc[key] = loaders[key];
        return acc;
      }, {} as Record<string, () => Promise<T>>);
      
      const regularResults = await this.executeLoaders(regularLoaders, timeout, retryCount);
      Object.assign(results, regularResults);
    }
    
    return results;
  }
  
  private async executeLoaders<T>(
    loaders: Record<string, () => Promise<T>>,
    timeout: number,
    retryCount: number
  ): Promise<Record<string, T | null>> {
    const promises = Object.entries(loaders).map(async ([key, loader]) => {
      try {
        const result = await this.withRetry(
          () => this.withTimeout(loader(), timeout),
          retryCount
        );
        return [key, result] as [string, T];
      } catch (error) {
        console.warn(`Failed to load ${key}:`, error);
        return [key, null] as [string, T | null];
      }
    });
    
    const results = await Promise.all(promises);
    return Object.fromEntries(results);
  }
  
  private async withTimeout<T>(promise: Promise<T>, timeout: number): Promise<T> {
    return Promise.race([
      promise,
      new Promise<never>((_, reject) =>
        setTimeout(() => reject(new Error('Timeout')), timeout)
      )
    ]);
  }
  
  private async withRetry<T>(
    fn: () => Promise<T>,
    retryCount: number,
    delay: number = 1000
  ): Promise<T> {
    try {
      return await fn();
    } catch (error) {
      if (retryCount <= 0) throw error;
      
      await new Promise(resolve => setTimeout(resolve, delay));
      return this.withRetry(fn, retryCount - 1, delay * 2);
    }
  }
}

// Formatting utilities
const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
};

const formatWeight = (value: number): string => {
  return `${value.toFixed(1)} kg`;
};

const formatPercentage = (value: number): string => {
  return `${value.toFixed(1)}%`;
};

export const useDashboardStore = defineStore("dashboard", () => {
  // Enhanced state with loading states for individual components
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
  const recentActivities = ref<any[]>([]);
  const quickActions = ref<any[]>([]);
  
  // Enhanced loading states for progressive loading
  const loadingStates = ref({
    kpis: false,
    alerts: false,
    activities: false,
    quickActions: false,
    widgets: false,
    salesChart: false,
    overall: false
  });
  
  // Legacy compatibility properties
  const isLoading = computed(() => loadingStates.value.overall);
  const alertsMetadata = ref({
    total: 0,
    hasMore: false,
    currentOffset: 0,
  });
  const activityStats = ref<any>({});
  const quickActionStats = ref<any>({});
  
  const lastUpdated = ref<string>("");
  const dataFreshness = ref<Record<string, string>>({});
  
  // Initialize cache and loader instances
  const cache = new DashboardCache();
  const loader = new ParallelDataLoader();

  // Enhanced getters with loading state awareness
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
  
  // Enhanced computed properties for loading states
  const isAnyLoading = computed(() => 
    Object.values(loadingStates.value).some(loading => loading)
  );
  
  const isCriticalDataLoaded = computed(() => 
    !loadingStates.value.kpis && !loadingStates.value.alerts
  );
  
  const isFullyLoaded = computed(() => 
    !Object.values(loadingStates.value).some(loading => loading)
  );
  
  const loadingProgress = computed(() => {
    const totalStates = Object.keys(loadingStates.value).length;
    const loadedStates = Object.values(loadingStates.value).filter(loading => !loading).length;
    return Math.round((loadedStates / totalStates) * 100);
  });

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

  // Enhanced actions with intelligent caching and error handling
  const fetchKPIs = async (dateRange?: { start: string; end: string }, forceRefresh = false) => {
    const cacheKey = `kpis_${JSON.stringify(dateRange || {})}`;
    
    // Check cache first unless force refresh
    if (!forceRefresh && cache.has(cacheKey)) {
      const cachedData = cache.get(cacheKey);
      if (cachedData) {
        kpis.value = cachedData;
        return;
      }
    }
    
    try {
      loadingStates.value.kpis = true;
      const response = await apiService.dashboard.getKPIs(dateRange);
      
      if (response.data.success && response.data.data) {
        // Transform backend data to frontend format with enhanced formatting
        const backendData = response.data.data;
        const transformedKPIs = [
          {
            key: "gold_sold",
            label: "Gold Sold",
            value: backendData.gold_sold || 0,
            formattedValue: formatWeight(backendData.gold_sold || 0),
            change: calculateChange(backendData.gold_sold, backendData.gold_sold_previous),
            changeType: getChangeType(backendData.gold_sold, backendData.gold_sold_previous),
            format: "weight" as const,
            color: "yellow",
          },
          {
            key: "total_profit",
            label: "Total Profit",
            value: backendData.total_profits || 0,
            formattedValue: formatCurrency(backendData.total_profits || 0),
            change: calculateChange(backendData.total_profits, backendData.total_profits_previous),
            changeType: getChangeType(backendData.total_profits, backendData.total_profits_previous),
            format: "currency" as const,
            color: "green",
          },
          {
            key: "average_price",
            label: "Average Price",
            value: backendData.average_price || 0,
            formattedValue: formatCurrency(backendData.average_price || 0),
            change: calculateChange(backendData.average_price, backendData.average_price_previous),
            changeType: getChangeType(backendData.average_price, backendData.average_price_previous),
            format: "currency" as const,
            color: "blue",
          },
          {
            key: "returns",
            label: "Returns",
            value: backendData.returns || 0,
            formattedValue: formatCurrency(backendData.returns || 0),
            change: calculateChange(backendData.returns, backendData.returns_previous),
            changeType: getChangeType(backendData.returns, backendData.returns_previous, true),
            format: "currency" as const,
            color: "red",
          },
          {
            key: "gross_margin",
            label: "Gross Margin",
            value: backendData.gross_margin || 0,
            formattedValue: formatPercentage(backendData.gross_margin || 0),
            change: calculateChange(backendData.gross_margin, backendData.gross_margin_previous),
            changeType: getChangeType(backendData.gross_margin, backendData.gross_margin_previous),
            format: "percentage" as const,
            color: "purple",
          },
          {
            key: "net_margin",
            label: "Net Margin",
            value: backendData.net_margin || 0,
            formattedValue: formatPercentage(backendData.net_margin || 0),
            change: calculateChange(backendData.net_margin, backendData.net_margin_previous),
            changeType: getChangeType(backendData.net_margin, backendData.net_margin_previous),
            format: "percentage" as const,
            color: "indigo",
          },
        ];
        
        kpis.value = transformedKPIs;
        
        // Cache the transformed data
        cache.set(cacheKey, transformedKPIs, 300000); // 5 minutes cache
        dataFreshness.value.kpis = new Date().toISOString();
      } else {
        // Fallback to default data if API response is not in expected format
        console.warn("KPI API response not in expected format, using fallback");
        kpis.value = getDefaultKPIs();
      }
    } catch (error) {
      console.error("Failed to fetch KPIs from API:", error);
      // Use cached data if available, otherwise fallback to defaults
      const cachedData = cache.get(cacheKey);
      if (cachedData) {
        console.info("Using cached KPI data due to API error");
        kpis.value = cachedData;
      } else {
        console.info("Using default KPI data due to API error and no cache");
        kpis.value = getDefaultKPIs();
      }
    } finally {
      loadingStates.value.kpis = false;
    }
  };

  const fetchSalesChartData = async (period: string = 'month', forceRefresh = false) => {
    const cacheKey = `sales_chart_${period}`;
    
    // Check cache first unless force refresh
    if (!forceRefresh && cache.has(cacheKey)) {
      const cachedData = cache.get(cacheKey);
      if (cachedData) {
        return cachedData;
      }
    }
    
    try {
      loadingStates.value.salesChart = true;
      const response = await apiService.dashboard.getSalesChart(period);
      
      if (response.data.success && response.data.data) {
        const salesData = response.data.data;
        
        // Transform backend data to Chart.js format
        const chartData = {
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
        
        // Cache the chart data
        cache.set(cacheKey, chartData, 600000); // 10 minutes cache
        dataFreshness.value.salesChart = new Date().toISOString();
        
        return chartData;
      }
    } catch (error) {
      console.error("Failed to fetch sales chart data:", error);
      // Use cached data if available
      const cachedData = cache.get(cacheKey);
      if (cachedData) {
        console.info("Using cached sales chart data due to API error");
        return cachedData;
      }
    } finally {
      loadingStates.value.salesChart = false;
    }
    
    // Return default chart data as fallback
    return generateSalesChartData(period as "daily" | "weekly" | "monthly" | "yearly");
  };

  const fetchAlerts = async (loadMore = false, forceRefresh = false) => {
    const cacheKey = `alerts_${loadMore ? alerts.value.length : 0}`;
    
    // Check cache first unless force refresh or loading more
    if (!forceRefresh && !loadMore && cache.has(cacheKey)) {
      const cachedData = cache.get(cacheKey);
      if (cachedData) {
        alerts.value = cachedData.alerts;
        return;
      }
    }
    
    try {
      loadingStates.value.alerts = true;
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
          // Cache the initial alerts
          cache.set(cacheKey, { alerts: newAlerts }, 300000); // 5 minutes cache
        }

        // Update alerts metadata
        alertsMetadata.value = {
          total: response.data.data.total || 0,
          hasMore: response.data.data.has_more || false,
          currentOffset: offset + newAlerts.length,
        };

        dataFreshness.value.alerts = new Date().toISOString();
      } else {
        if (!loadMore) {
          alerts.value = getDefaultAlerts();
        }
      }
    } catch (error) {
      console.error("Failed to fetch alerts from API:", error);
      // Use cached data if available
      if (!loadMore) {
        const cachedData = cache.get(cacheKey);
        if (cachedData) {
          console.info("Using cached alerts data due to API error");
          alerts.value = cachedData.alerts;
        } else {
          console.info("Using default alerts data due to API error and no cache");
          alerts.value = getDefaultAlerts();
        }
      }
    } finally {
      loadingStates.value.alerts = false;
    }
  };

  const fetchRecentActivities = async (limit = 10, forceRefresh = false) => {
    const cacheKey = `activities_${limit}`;
    
    // Check cache first unless force refresh
    if (!forceRefresh && cache.has(cacheKey)) {
      const cachedData = cache.get(cacheKey);
      if (cachedData) {
        recentActivities.value = cachedData;
        return;
      }
    }
    
    try {
      loadingStates.value.activities = true;
      const response = await apiService.dashboard.getRecentActivities({ limit });
      
      if (response.data.success && response.data.data?.activities) {
        recentActivities.value = response.data.data.activities;
        
        // Cache the activities
        cache.set(cacheKey, response.data.data.activities, 180000); // 3 minutes cache
        dataFreshness.value.activities = new Date().toISOString();
      } else {
        recentActivities.value = getDefaultActivities();
      }
    } catch (error) {
      console.error("Failed to fetch recent activities from API:", error);
      // Use cached data if available
      const cachedData = cache.get(cacheKey);
      if (cachedData) {
        console.info("Using cached activities data due to API error");
        recentActivities.value = cachedData;
      } else {
        console.info("Using default activities data due to API error and no cache");
        recentActivities.value = getDefaultActivities();
      }
    } finally {
      loadingStates.value.activities = false;
    }
  };

  const fetchQuickActions = async (forceRefresh = false) => {
    const cacheKey = 'quick_actions';
    
    // Check cache first unless force refresh
    if (!forceRefresh && cache.has(cacheKey)) {
      const cachedData = cache.get(cacheKey);
      if (cachedData) {
        quickActions.value = cachedData;
        return;
      }
    }
    
    try {
      loadingStates.value.quickActions = true;
      const response = await apiService.dashboard.getQuickActions();
      
      if (response.data.success && response.data.data?.actions) {
        quickActions.value = response.data.data.actions;
        
        // Cache the quick actions
        cache.set(cacheKey, response.data.data.actions, 600000); // 10 minutes cache
        dataFreshness.value.quickActions = new Date().toISOString();
      } else {
        quickActions.value = getDefaultQuickActions();
      }
    } catch (error) {
      console.error("Failed to fetch quick actions from API:", error);
      // Use cached data if available
      const cachedData = cache.get(cacheKey);
      if (cachedData) {
        console.info("Using cached quick actions data due to API error");
        quickActions.value = cachedData;
      } else {
        console.info("Using default quick actions data due to API error and no cache");
        quickActions.value = getDefaultQuickActions();
      }
    } finally {
      loadingStates.value.quickActions = false;
    }
  };

  // Load more alerts
  const loadMoreAlerts = async () => {
    await fetchAlerts(true, false); // Load more, don't force refresh
  };

  // Enhanced parallel data loading with progressive loading
  const refreshData = async (options: { 
    forceRefresh?: boolean;
    progressiveLoad?: boolean;
    dateRange?: { start: string; end: string };
  } = {}) => {
    const { forceRefresh = false, progressiveLoad = true, dateRange } = options;
    
    loadingStates.value.overall = true;
    
    try {
      if (progressiveLoad) {
        // Load critical data first (KPIs and alerts)
        await loader.loadInParallel({
          kpis: () => fetchKPIs(dateRange, forceRefresh),
          alerts: () => fetchAlerts(false, forceRefresh),
        }, { showCriticalFirst: true });
        
        // Then load secondary data
        await loader.loadInParallel({
          activities: () => fetchRecentActivities(10, forceRefresh),
          quickActions: () => fetchQuickActions(forceRefresh),
        }, { showCriticalFirst: false });
      } else {
        // Load all data in parallel
        await loader.loadInParallel({
          kpis: () => fetchKPIs(dateRange, forceRefresh),
          alerts: () => fetchAlerts(false, forceRefresh),
          activities: () => fetchRecentActivities(10, forceRefresh),
          quickActions: () => fetchQuickActions(forceRefresh),
        });
      }
      
      lastUpdated.value = new Date().toISOString();
    } catch (error) {
      console.error("Error during dashboard data refresh:", error);
    } finally {
      loadingStates.value.overall = false;
    }
  };

  // Cache management
  const invalidateCache = (pattern?: string) => {
    cache.invalidate(pattern);
    if (pattern) {
      console.info(`Cache invalidated for pattern: ${pattern}`);
    } else {
      console.info("All cache invalidated");
    }
  };

  const getCacheStats = () => {
    return {
      dataFreshness: dataFreshness.value,
      lastUpdated: lastUpdated.value,
      loadingStates: loadingStates.value,
    };
  };

  // Alert management
  const markAlertAsRead = async (alertId: string) => {
    try {
      await apiService.dashboard.markAlertAsRead(alertId);
      const alert = alerts.value.find((a) => a.id === alertId);
      if (alert) {
        alert.read = true;
      }
      // Invalidate alerts cache
      invalidateCache('alerts');
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
      // Use the bulk mark all as read endpoint
      await apiService.dashboard.markAllAlertsAsRead();
      alerts.value.forEach((alert) => (alert.read = true));
      // Invalidate alerts cache
      invalidateCache('alerts');
    } catch (error) {
      console.error('Failed to mark all alerts as read:', error);
      // Still update locally for better UX
      alerts.value.forEach((alert) => (alert.read = true));
    }
  };

  const dismissAlert = async (alertId: string) => {
    try {
      // Use the dismiss endpoint
      await apiService.dashboard.dismissAlert(alertId);
      const index = alerts.value.findIndex((a) => a.id === alertId);
      if (index > -1) {
        alerts.value.splice(index, 1);
      }
      // Invalidate alerts cache
      invalidateCache('alerts');
    } catch (error) {
      console.error('Failed to dismiss alert:', error);
      // Still remove locally for better UX
      const index = alerts.value.findIndex((a) => a.id === alertId);
      if (index > -1) {
        alerts.value.splice(index, 1);
      }
    }
  };

  // Default data functions (same as original)
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

  const getDefaultActivities = () => [
    {
      id: "1",
      type: "invoice_created",
      description: "Invoice #INV-001 created",
      user: "Admin",
      time: "2 minutes ago",
      timestamp: new Date(Date.now() - 2 * 60 * 1000).toISOString(),
      status: "completed",
    },
    {
      id: "2",
      type: "customer_added",
      description: "Customer John Doe added",
      user: "Admin",
      time: "5 minutes ago",
      timestamp: new Date(Date.now() - 5 * 60 * 1000).toISOString(),
      status: "completed",
    },
    {
      id: "3",
      type: "inventory_updated",
      description: "Gold Ring inventory updated",
      user: "Admin",
      time: "10 minutes ago",
      timestamp: new Date(Date.now() - 10 * 60 * 1000).toISOString(),
      status: "completed",
    },
    {
      id: "4",
      type: "payment_received",
      description: "Payment received for INV-002",
      user: "System",
      time: "15 minutes ago",
      timestamp: new Date(Date.now() - 15 * 60 * 1000).toISOString(),
      status: "completed",
    },
    {
      id: "5",
      type: "stock_alert",
      description: "Stock alert for Silver Necklace",
      user: "System",
      time: "20 minutes ago",
      timestamp: new Date(Date.now() - 20 * 60 * 1000).toISOString(),
      status: "pending",
    },
  ];

  const getDefaultQuickActions = () => [
    {
      key: "add_customer",
      label: "Add Customer",
      icon: "UserGroupIcon",
      route: "/customers/new",
      description: "Create a new customer record",
      enabled: true,
      badge: null,
    },
    {
      key: "add_inventory",
      label: "Add Item",
      icon: "ArchiveBoxIcon",
      route: "/inventory/new",
      description: "Add new inventory item",
      enabled: true,
      badge: null,
    },
    {
      key: "create_invoice",
      label: "Create Invoice",
      icon: "DocumentTextIcon",
      route: "/invoices/new",
      description: "Generate a new invoice",
      enabled: true,
      badge: null,
    },
    {
      key: "view_reports",
      label: "View Reports",
      icon: "ChartBarIcon",
      route: "/reports",
      description: "Access business reports",
      enabled: true,
      badge: null,
    },
    {
      key: "accounting",
      label: "Accounting",
      icon: "CalculatorIcon",
      route: "/accounting",
      description: "Manage financial records",
      enabled: true,
      badge: null,
    },
    {
      key: "settings",
      label: "Settings",
      icon: "CogIcon",
      route: "/settings",
      description: "Configure system settings",
      enabled: true,
      badge: null,
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

  // Legacy compatibility methods

  const fetchWidgets = async () => {
    try {
      loadingStates.value.widgets = true;
      
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
      console.warn("Failed to fetch widgets from API, using default data:", error);
      widgets.value = getDefaultWidgets();
      currentLayout.value.widgets = getDefaultWidgets();
    } finally {
      loadingStates.value.widgets = false;
    }
  };

  const saveWidgetLayout = async (layout: DashboardLayout) => {
    try {
      await apiService.dashboard.saveWidgetLayout(layout);
      currentLayout.value = layout;
    } catch (error) {
      console.warn("Failed to save widget layout to API, saving locally:", error);
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

  // Initialize
  const initialize = async (options: { progressiveLoad?: boolean } = {}) => {
    console.info("Initializing enhanced dashboard store...");
    initializePresets();
    await refreshData({ 
      progressiveLoad: options.progressiveLoad ?? true 
    });
  };

  return {
    // State
    kpis,
    widgets,
    currentLayout,
    availablePresets,
    activePreset,
    alerts,
    recentActivities,
    quickActions,
    loadingStates,
    lastUpdated,
    dataFreshness,
    
    // Legacy compatibility
    isLoading,
    alertsMetadata,
    activityStats,
    quickActionStats,

    // Getters
    unreadAlerts,
    criticalAlerts,
    alertCount,
    criticalAlertCount,
    getKPIByKey,
    getWidgetById,
    isAnyLoading,
    isCriticalDataLoaded,
    isFullyLoaded,
    loadingProgress,

    // Actions
    fetchKPIs,
    fetchSalesChartData,
    fetchAlerts,
    fetchRecentActivities,
    fetchQuickActions,
    refreshData,
    markAlertAsRead,
    markAllAlertsAsRead,
    dismissAlert,
    invalidateCache,
    getCacheStats,
    initialize,
    
    // Legacy compatibility methods
    loadMoreAlerts,
    fetchWidgets,
    saveWidgetLayout,
    updateWidgetPosition,
    addWidget,
    removeWidget,
    switchPreset,
  };
});