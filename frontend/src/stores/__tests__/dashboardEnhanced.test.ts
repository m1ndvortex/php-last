import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useDashboardStore } from '../dashboard';
import { apiService } from '@/services/api';

// Mock the API service
vi.mock('@/services/api', () => ({
  apiService: {
    dashboard: {
      getKPIs: vi.fn(),
      getSalesChart: vi.fn(),
      getAlerts: vi.fn(),
      getRecentActivities: vi.fn(),
      getQuickActions: vi.fn(),
      markAlertAsRead: vi.fn(),
    }
  }
}));

describe('Enhanced Dashboard Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Initialization', () => {
    it('should initialize with default state', () => {
      const store = useDashboardStore();
      
      expect(store.kpis).toEqual([]);
      expect(store.alerts).toEqual([]);
      expect(store.recentActivities).toEqual([]);
      expect(store.quickActions).toEqual([]);
      expect(store.isAnyLoading).toBe(false);
      expect(store.loadingProgress).toBe(100); // All states are false initially
    });

    it('should have correct computed properties', () => {
      const store = useDashboardStore();
      
      expect(store.alertCount).toBe(0);
      expect(store.criticalAlertCount).toBe(0);
      expect(store.isCriticalDataLoaded).toBe(true);
      expect(store.isFullyLoaded).toBe(true);
    });
  });

  describe('KPI Fetching', () => {
    it('should fetch KPIs successfully', async () => {
      const mockKPIData = {
        gold_sold: 15.5,
        total_profits: 50000,
        average_price: 2000,
        returns: 1000,
        gross_margin: 40.5,
        net_margin: 32.1,
        gold_sold_previous: 12.0,
        total_profits_previous: 45000,
        average_price_previous: 1900,
        returns_previous: 1200,
        gross_margin_previous: 38.0,
        net_margin_previous: 30.0,
      };

      vi.mocked(apiService.dashboard.getKPIs).mockResolvedValue({
        data: {
          success: true,
          data: mockKPIData
        }
      } as any);

      const store = useDashboardStore();
      await store.fetchKPIs();

      expect(store.kpis).toHaveLength(6);
      expect(store.kpis[0].key).toBe('gold_sold');
      expect(store.kpis[0].value).toBe(15.5);
      expect(store.kpis[0].formattedValue).toBe('15.5 kg');
      expect(store.loadingStates.kpis).toBe(false);
    });

    it('should handle KPI fetch errors gracefully', async () => {
      vi.mocked(apiService.dashboard.getKPIs).mockRejectedValue(new Error('API Error'));

      const store = useDashboardStore();
      await store.fetchKPIs();

      // Should fall back to default KPIs
      expect(store.kpis).toHaveLength(6);
      expect(store.loadingStates.kpis).toBe(false);
    });

    it('should use cached KPIs when available', async () => {
      const mockKPIData = {
        gold_sold: 15.5,
        total_profits: 50000,
        average_price: 2000,
        returns: 1000,
        gross_margin: 40.5,
        net_margin: 32.1,
      };

      vi.mocked(apiService.dashboard.getKPIs).mockResolvedValue({
        data: {
          success: true,
          data: mockKPIData
        }
      } as any);

      const store = useDashboardStore();
      
      // First call should fetch from API
      await store.fetchKPIs();
      expect(apiService.dashboard.getKPIs).toHaveBeenCalledTimes(1);

      // Second call should use cache
      await store.fetchKPIs();
      expect(apiService.dashboard.getKPIs).toHaveBeenCalledTimes(1);

      // Force refresh should bypass cache
      await store.fetchKPIs(undefined, true);
      expect(apiService.dashboard.getKPIs).toHaveBeenCalledTimes(2);
    });
  });

  describe('Alerts Management', () => {
    it('should fetch alerts successfully', async () => {
      const mockAlerts = [
        {
          id: 1,
          type: 'low_stock',
          title: 'Low Stock Alert',
          message: 'Item running low',
          severity: 'medium',
          timestamp: new Date().toISOString(),
          read: false,
          action_url: '/inventory',
          action_label: 'View Inventory'
        }
      ];

      vi.mocked(apiService.dashboard.getAlerts).mockResolvedValue({
        data: {
          success: true,
          data: {
            alerts: mockAlerts,
            total: 1,
            has_more: false
          }
        }
      } as any);

      const store = useDashboardStore();
      await store.fetchAlerts();

      expect(store.alerts).toHaveLength(1);
      expect(store.alerts[0].id).toBe('1');
      expect(store.alerts[0].type).toBe('low_stock');
      expect(store.alertCount).toBe(1);
      expect(store.loadingStates.alerts).toBe(false);
    });

    it('should mark alert as read', async () => {
      vi.mocked(apiService.dashboard.markAlertAsRead).mockResolvedValue({
        data: { success: true }
      } as any);

      const store = useDashboardStore();
      store.alerts = [
        {
          id: '1',
          type: 'low_stock',
          title: 'Test Alert',
          message: 'Test message',
          severity: 'medium',
          timestamp: new Date().toISOString(),
          read: false,
        }
      ];

      await store.markAlertAsRead('1');

      expect(store.alerts[0].read).toBe(true);
      expect(apiService.dashboard.markAlertAsRead).toHaveBeenCalledWith('1');
    });
  });

  describe('Parallel Data Loading', () => {
    it('should load data in parallel with progressive loading', async () => {
      // Mock all API calls
      vi.mocked(apiService.dashboard.getKPIs).mockResolvedValue({
        data: { success: true, data: {} }
      } as any);
      
      vi.mocked(apiService.dashboard.getAlerts).mockResolvedValue({
        data: { success: true, data: { alerts: [] } }
      } as any);
      
      vi.mocked(apiService.dashboard.getRecentActivities).mockResolvedValue({
        data: { success: true, data: { activities: [] } }
      } as any);
      
      vi.mocked(apiService.dashboard.getQuickActions).mockResolvedValue({
        data: { success: true, data: { actions: [] } }
      } as any);

      const store = useDashboardStore();
      
      // Test progressive loading
      await store.refreshData({ progressiveLoad: true });

      expect(apiService.dashboard.getKPIs).toHaveBeenCalled();
      expect(apiService.dashboard.getAlerts).toHaveBeenCalled();
      expect(apiService.dashboard.getRecentActivities).toHaveBeenCalled();
      expect(apiService.dashboard.getQuickActions).toHaveBeenCalled();
      expect(store.loadingStates.overall).toBe(false);
    });

    it('should handle loading state correctly during refresh', async () => {
      // Mock API calls with delay
      vi.mocked(apiService.dashboard.getKPIs).mockImplementation(() => 
        new Promise(resolve => setTimeout(() => resolve({
          data: { success: true, data: {} }
        } as any), 100))
      );

      const store = useDashboardStore();
      
      const refreshPromise = store.refreshData();
      
      // Should be loading initially
      expect(store.loadingStates.overall).toBe(true);
      
      await refreshPromise;
      
      // Should not be loading after completion
      expect(store.loadingStates.overall).toBe(false);
    });
  });

  describe('Cache Management', () => {
    it('should invalidate cache correctly', async () => {
      const store = useDashboardStore();
      
      // First, populate cache
      vi.mocked(apiService.dashboard.getKPIs).mockResolvedValue({
        data: { success: true, data: {} }
      } as any);
      
      await store.fetchKPIs();
      expect(apiService.dashboard.getKPIs).toHaveBeenCalledTimes(1);
      
      // Should use cache on second call
      await store.fetchKPIs();
      expect(apiService.dashboard.getKPIs).toHaveBeenCalledTimes(1);
      
      // Invalidate cache
      store.invalidateCache('kpis');
      
      // Should fetch from API again
      await store.fetchKPIs();
      expect(apiService.dashboard.getKPIs).toHaveBeenCalledTimes(2);
    });

    it('should provide cache stats', () => {
      const store = useDashboardStore();
      const stats = store.getCacheStats();
      
      expect(stats).toHaveProperty('dataFreshness');
      expect(stats).toHaveProperty('lastUpdated');
      expect(stats).toHaveProperty('loadingStates');
    });
  });

  describe('Error Handling', () => {
    it('should handle network errors gracefully', async () => {
      vi.mocked(apiService.dashboard.getKPIs).mockRejectedValue(new Error('Network Error'));
      vi.mocked(apiService.dashboard.getAlerts).mockRejectedValue(new Error('Network Error'));

      const store = useDashboardStore();
      
      // Should not throw errors
      await expect(store.refreshData()).resolves.not.toThrow();
      
      // Should have fallback data
      expect(store.kpis.length).toBeGreaterThan(0);
      expect(store.alerts.length).toBeGreaterThan(0);
    });

    it('should use cached data when API fails', async () => {
      const mockKPIData = { gold_sold: 15.5, total_profits: 50000 };
      
      // First successful call
      vi.mocked(apiService.dashboard.getKPIs).mockResolvedValueOnce({
        data: { success: true, data: mockKPIData }
      } as any);
      
      const store = useDashboardStore();
      await store.fetchKPIs();
      
      const firstCallResult = store.kpis[0].value;
      
      // Second call fails
      vi.mocked(apiService.dashboard.getKPIs).mockRejectedValueOnce(new Error('API Error'));
      
      // Force refresh to bypass cache initially, but should fall back to cache on error
      await store.fetchKPIs(undefined, true);
      
      // Should still have the cached data
      expect(store.kpis[0].value).toBe(firstCallResult);
    });
  });

  describe('Data Formatting', () => {
    it('should format KPI values correctly', async () => {
      const mockKPIData = {
        gold_sold: 15.567,
        total_profits: 50000.99,
        gross_margin: 40.567,
      };

      vi.mocked(apiService.dashboard.getKPIs).mockResolvedValue({
        data: { success: true, data: mockKPIData }
      } as any);

      const store = useDashboardStore();
      await store.fetchKPIs();

      const goldSoldKPI = store.kpis.find(kpi => kpi.key === 'gold_sold');
      const profitKPI = store.kpis.find(kpi => kpi.key === 'total_profit');
      const marginKPI = store.kpis.find(kpi => kpi.key === 'gross_margin');

      expect(goldSoldKPI?.formattedValue).toBe('15.6 kg');
      expect(profitKPI?.formattedValue).toBe('$50,001');
      expect(marginKPI?.formattedValue).toBe('40.6%');
    });
  });
});