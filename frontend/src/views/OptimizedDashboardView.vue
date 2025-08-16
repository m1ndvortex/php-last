<template>
  <div class="dashboard-container" :class="{ rtl: isRTL }">
    <!-- Show skeleton while loading -->
    <DashboardSkeleton v-if="isInitialLoading" />
    
    <!-- Main Dashboard Content -->
    <div v-else>
      <!-- Dashboard Header -->
      <ErrorBoundary
        :title="$t('errors.dashboard_header_error')"
        @retry="refreshDashboard"
      >
        <div class="dashboard-header">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 rtl:space-x-reverse">
              <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ $t("common.last_updated") }}: {{ memoizedLastUpdated }}
              </div>
            </div>

            <div class="flex items-center space-x-4 rtl:space-x-reverse">
              <!-- Refresh Button -->
              <button
                @click="refreshDashboard"
                :disabled="isRefreshing"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors rounded-md disabled:opacity-50"
                :title="$t('dashboard.refresh_dashboard')"
              >
                <ArrowPathIcon
                  class="w-5 h-5"
                  :class="{ 'animate-spin': isRefreshing }"
                />
              </button>

              <!-- Alert Count Badge -->
              <div v-if="memoizedAlertCount > 0" class="relative">
                <button
                  @click="showAlertsModal = true"
                  class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors rounded-md"
                >
                  <BellIcon class="w-5 h-5" />
                  <span
                    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"
                  >
                    {{ memoizedAlertCount }}
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </ErrorBoundary>

      <!-- KPI Overview Section -->
      <ErrorBoundary
        :title="$t('errors.kpi_section_error')"
        @retry="retryKPIs"
      >
        <div class="kpi-section mb-8">
          <div
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 auto-cols-fr"
          >
            <OptimizedKPIWidget
              v-for="kpi in memoizedKPIs"
              :key="kpi.key"
              :kpi="kpi"
              :is-loading="loadingStates.kpis"
              :has-error="kpiErrors[kpi.key]"
              @retry="retryKPI(kpi.key)"
            />
          </div>
        </div>
      </ErrorBoundary>

      <!-- Dashboard Widgets -->
      <ErrorBoundary
        :title="$t('errors.widgets_section_error')"
        @retry="retryWidgets"
      >
        <div class="widgets-section mb-8">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sales Chart -->
            <div class="lg:col-span-2">
              <OptimizedChartWidget
                :title="$t('dashboard.sales_overview')"
                chart-type="line"
                :chart-data="memoizedSalesChartData"
                :show-period-selector="true"
                :is-loading="loadingStates.salesChart"
                :has-error="chartError"
                @period-change="handlePeriodChange"
                @refresh="refreshSalesChart"
                @retry="retrySalesChart"
              />
            </div>

            <!-- Alerts Widget -->
            <OptimizedAlertWidget
              :title="$t('dashboard.business_alerts')"
              :alerts="memoizedAlerts"
              :max-visible="3"
              :is-loading="loadingStates.alerts"
              :has-error="alertsError"
              :has-more-alerts="dashboardStore.alertsMetadata.hasMore"
              :loading-more="loadingStates.loadingMoreAlerts"
              @mark-as-read="markAlertAsRead"
              @mark-all-as-read="markAllAlertsAsRead"
              @dismiss-alert="dismissAlert"
              @action-click="handleAlertAction"
              @show-more="loadMoreAlerts"
              @retry="retryAlerts"
            />
          </div>
        </div>
      </ErrorBoundary>

      <!-- Recent Activities Table -->
      <ErrorBoundary
        :title="$t('errors.activities_section_error')"
        @retry="retryActivities"
      >
        <div class="activities-section mb-8">
          <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
          >
            <h3
              class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4"
              :class="{ 'text-right': isRTL }"
            >
              {{ $t("dashboard.recent_activities") }}
            </h3>
            
            <!-- Activities Loading Skeleton -->
            <TableSkeleton v-if="loadingStates.activities" :rows="5" :columns="4" />
            
            <!-- Activities Error State -->
            <div v-else-if="activitiesError" class="text-center py-8">
              <ExclamationTriangleIcon class="w-12 h-12 mx-auto mb-4 text-red-500" />
              <p class="text-sm text-red-600 dark:text-red-400 mb-4">{{ $t('dashboard.activities_error') }}</p>
              <button
                @click="retryActivities"
                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
              >
                {{ $t('common.retry') }}
              </button>
            </div>
            
            <!-- Activities Table -->
            <div v-else class="overflow-x-auto">
              <table
                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                dir="auto"
              >
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th
                      class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      {{ $t("dashboard.table.activity") }}
                    </th>
                    <th
                      class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      {{ $t("dashboard.table.user") }}
                    </th>
                    <th
                      class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      {{ $t("dashboard.table.time") }}
                    </th>
                    <th
                      class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      {{ $t("dashboard.table.status") }}
                    </th>
                  </tr>
                </thead>
                <tbody
                  class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                >
                  <tr
                    v-for="activity in memoizedActivities"
                    :key="activity.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-left rtl:text-right"
                    >
                      {{ activity.description }}
                    </td>
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-left rtl:text-right"
                    >
                      {{ activity.user }}
                    </td>
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-left rtl:text-right"
                    >
                      {{ activity.time }}
                    </td>
                    <td
                      class="px-6 py-4 whitespace-nowrap text-left rtl:text-right"
                    >
                      <span
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                        :class="getStatusClass(activity.status)"
                      >
                        {{ activity.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </ErrorBoundary>

      <!-- Quick Actions -->
      <ErrorBoundary
        :title="$t('errors.quick_actions_error')"
        @retry="retryQuickActions"
      >
        <div class="quick-actions-section">
          <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
          >
            <h3
              class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4"
              :class="{ 'text-right': isRTL }"
            >
              {{ $t("dashboard.quick_actions") }}
            </h3>
            
            <!-- Quick Actions Loading -->
            <div v-if="loadingStates.quickActions" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
              <div
                v-for="i in 6"
                :key="`skeleton-${i}`"
                class="flex flex-col items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg min-h-[100px] justify-center animate-pulse"
              >
                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
                <div class="w-16 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
              </div>
            </div>
            
            <!-- Quick Actions Error -->
            <div v-else-if="quickActionsError" class="text-center py-8">
              <ExclamationTriangleIcon class="w-12 h-12 mx-auto mb-4 text-red-500" />
              <p class="text-sm text-red-600 dark:text-red-400 mb-4">{{ $t('dashboard.quick_actions_error') }}</p>
              <button
                @click="retryQuickActions"
                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
              >
                {{ $t('common.retry') }}
              </button>
            </div>
            
            <!-- Quick Actions Grid -->
            <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
              <button
                v-for="action in memoizedQuickActions"
                :key="action.key"
                @click="handleQuickAction(action)"
                :disabled="!action.enabled"
                class="relative flex flex-col items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors min-h-[100px] disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <div class="relative">
                  <component
                    :is="getIconComponent(action.icon)"
                    class="w-8 h-8 text-gray-600 dark:text-gray-400 mb-2 flex-shrink-0"
                  />
                  <span
                    v-if="action.badge && action.badge > 0"
                    class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"
                  >
                    {{ action.badge }}
                  </span>
                </div>
                <span
                  class="text-sm font-medium text-gray-900 dark:text-gray-100 text-center break-words"
                  >{{ action.label }}</span
                >
              </button>
            </div>
          </div>
        </div>
      </ErrorBoundary>
    </div>

    <!-- Alerts Modal -->
    <div
      v-if="showAlertsModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="showAlertsModal = false"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto"
        @click.stop
      >
        <div
          class="flex items-center justify-between mb-6"
          :class="{ 'flex-row-reverse': isRTL }"
        >
          <h3
            class="text-lg font-semibold text-gray-900 dark:text-gray-100"
            :class="{ 'text-right': isRTL }"
          >
            {{ $t("dashboard.business_alerts") }}
          </h3>
          <button
            @click="showAlertsModal = false"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <OptimizedAlertWidget
          :title="''"
          :alerts="memoizedAlerts"
          :max-visible="10"
          :is-loading="loadingStates.alerts"
          :has-error="alertsError"
          :has-more-alerts="dashboardStore.alertsMetadata.hasMore"
          :loading-more="loadingStates.loadingMoreAlerts"
          @mark-as-read="markAlertAsRead"
          @mark-all-as-read="markAllAlertsAsRead"
          @dismiss-alert="dismissAlert"
          @action-click="handleAlertAction"
          @show-more="loadMoreAlerts"
          @retry="retryAlerts"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive, shallowRef } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useDashboardStore } from "@/stores/dashboard";

// Icons
import {
  ArrowPathIcon,
  BellIcon,
  XMarkIcon,
  UserGroupIcon,
  ArchiveBoxIcon,
  DocumentTextIcon,
  CalculatorIcon,
  CogIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
} from "@heroicons/vue/24/outline";

// Optimized Components
import OptimizedKPIWidget from "@/components/dashboard/OptimizedKPIWidget.vue";
import OptimizedChartWidget from "@/components/dashboard/OptimizedChartWidget.vue";
import OptimizedAlertWidget from "@/components/dashboard/OptimizedAlertWidget.vue";
import ErrorBoundary from "@/components/ui/ErrorBoundary.vue";
import DashboardSkeleton from "@/components/dashboard/DashboardSkeleton.vue";
import TableSkeleton from "@/components/ui/TableSkeleton.vue";

const router = useRouter();
const { t, locale } = useI18n();
const dashboardStore = useDashboardStore();

// Reactive state
const showAlertsModal = ref(false);
const isInitialLoading = ref(true);
const isRefreshing = ref(false);
const selectedPeriod = ref('monthly');

// Error states for individual components
const kpiErrors = reactive<Record<string, boolean>>({});
const chartError = ref(false);
const alertsError = ref(false);
const activitiesError = ref(false);
const quickActionsError = ref(false);

// Loading states
const loadingStates = computed(() => dashboardStore.loadingStates);

// Memoized computed properties for better performance
const isRTL = computed(() => locale.value === "fa");

const memoizedLastUpdated = computed(() => {
  const lastUpdated = new Date(dashboardStore.lastUpdated || Date.now());
  return lastUpdated.toLocaleTimeString("en-US");
});

const memoizedKPIs = computed(() => dashboardStore.kpis);
const memoizedAlerts = computed(() => dashboardStore.alerts);
const memoizedActivities = computed(() => dashboardStore.recentActivities);
const memoizedQuickActions = computed(() => dashboardStore.quickActions);
const memoizedAlertCount = computed(() => dashboardStore.alertCount);

// Use shallowRef for chart data to avoid deep reactivity overhead
const memoizedSalesChartData = shallowRef(null);

// Component methods
const refreshDashboard = async () => {
  isRefreshing.value = true;
  try {
    await dashboardStore.refreshData({ forceRefresh: true });
    // Clear all error states on successful refresh
    Object.keys(kpiErrors).forEach(key => kpiErrors[key] = false);
    chartError.value = false;
    alertsError.value = false;
    activitiesError.value = false;
    quickActionsError.value = false;
  } catch (error) {
    console.error('Failed to refresh dashboard:', error);
  } finally {
    isRefreshing.value = false;
  }
};

const handlePeriodChange = async (period: string) => {
  selectedPeriod.value = period;
  await refreshSalesChart();
};

const refreshSalesChart = async () => {
  try {
    chartError.value = false;
    const chartData = await dashboardStore.fetchSalesChartData(selectedPeriod.value, true);
    memoizedSalesChartData.value = chartData;
  } catch (error) {
    console.error('Failed to refresh sales chart:', error);
    chartError.value = true;
  }
};

// Retry methods for individual components
const retryKPIs = async () => {
  try {
    Object.keys(kpiErrors).forEach(key => kpiErrors[key] = false);
    await dashboardStore.fetchKPIs(undefined, true);
  } catch (error) {
    console.error('Failed to retry KPIs:', error);
  }
};

const retryKPI = async (kpiKey: string) => {
  kpiErrors[kpiKey] = false;
  await retryKPIs();
};

const retrySalesChart = async () => {
  chartError.value = false;
  await refreshSalesChart();
};

const retryAlerts = async () => {
  try {
    alertsError.value = false;
    await dashboardStore.fetchAlerts(false, true);
  } catch (error) {
    console.error('Failed to retry alerts:', error);
    alertsError.value = true;
  }
};

const retryActivities = async () => {
  try {
    activitiesError.value = false;
    await dashboardStore.fetchRecentActivities(10, true);
  } catch (error) {
    console.error('Failed to retry activities:', error);
    activitiesError.value = true;
  }
};

const retryQuickActions = async () => {
  try {
    quickActionsError.value = false;
    await dashboardStore.fetchQuickActions(true);
  } catch (error) {
    console.error('Failed to retry quick actions:', error);
    quickActionsError.value = true;
  }
};

const retryWidgets = async () => {
  await Promise.all([
    retrySalesChart(),
    retryAlerts()
  ]);
};

// Alert management
const markAlertAsRead = async (alertId: string) => {
  try {
    await dashboardStore.markAlertAsRead(alertId);
  } catch (error) {
    console.error('Failed to mark alert as read:', error);
  }
};

const markAllAlertsAsRead = async () => {
  try {
    await dashboardStore.markAllAlertsAsRead();
  } catch (error) {
    console.error('Failed to mark all alerts as read:', error);
  }
};

const dismissAlert = async (alertId: string) => {
  try {
    await dashboardStore.dismissAlert(alertId);
  } catch (error) {
    console.error('Failed to dismiss alert:', error);
  }
};

const handleAlertAction = (alert: any) => {
  if (alert.actionUrl) {
    router.push(alert.actionUrl);
  }
  showAlertsModal.value = false;
};

const loadMoreAlerts = async () => {
  try {
    await dashboardStore.fetchAlerts(true);
  } catch (error) {
    console.error('Failed to load more alerts:', error);
  }
};

// Quick actions
const handleQuickAction = (action: any) => {
  if (action.route) {
    router.push(action.route);
  }
};

const getIconComponent = (iconName: string) => {
  const iconMap: Record<string, any> = {
    UserGroupIcon,
    ArchiveBoxIcon,
    DocumentTextIcon,
    ChartBarIcon,
    CalculatorIcon,
    CogIcon,
    ExclamationTriangleIcon,
  };
  return iconMap[iconName] || ChartBarIcon;
};

const getStatusClass = (status: string) => {
  switch (status) {
    case "completed":
      return "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200";
    case "pending":
      return "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200";
    case "failed":
      return "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200";
    default:
      return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200";
  }
};

// Initialize dashboard
onMounted(async () => {
  try {
    // Initialize dashboard data with progressive loading
    await dashboardStore.refreshData({ progressiveLoad: true });
    
    // Load sales chart data
    const chartData = await dashboardStore.fetchSalesChartData(selectedPeriod.value);
    memoizedSalesChartData.value = chartData;
    
    console.log("Optimized Dashboard mounted with real data");
  } catch (error) {
    console.error("Failed to initialize dashboard:", error);
  } finally {
    isInitialLoading.value = false;
  }
});
</script>

<style scoped>
.dashboard-container {
  /* Remove extra padding since AppLayout already provides it */
}

/* RTL improvements */
.rtl .grid {
  direction: rtl;
}

.rtl .text-left {
  text-align: right;
}

.rtl .text-right {
  text-align: left;
}

/* Better text wrapping for Persian */
.break-words {
  word-break: break-word;
  overflow-wrap: break-word;
  hyphens: auto;
}

/* Ensure proper spacing in RTL */
.rtl .space-x-4 > * + * {
  margin-left: 0;
  margin-right: 1rem;
}

.rtl .space-x-3 > * + * {
  margin-left: 0;
  margin-right: 0.75rem;
}

.dashboard-header {
  margin-bottom: 1.5rem;
}

.kpi-section {
  margin-bottom: 2rem;
}

.widgets-section {
  margin-bottom: 2rem;
}

.activities-section {
  margin-bottom: 2rem;
}

.quick-actions-section {
  margin-bottom: 2rem;
}

/* Animation for loading states */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .dashboard-container {
    padding: 1rem;
  }

  .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-6 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .grid.grid-cols-2.md\\:grid-cols-3.lg\\:grid-cols-6 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* Better KPI card layout for long text */
.kpi-section .grid > div {
  min-width: 0;
  overflow: hidden;
}

/* Table RTL improvements */
.rtl table {
  direction: rtl;
}

.rtl th,
.rtl td {
  text-align: right;
}

.rtl th:first-child,
.rtl td:first-child {
  padding-right: 1.5rem;
  padding-left: 1rem;
}

.rtl th:last-child,
.rtl td:last-child {
  padding-left: 1.5rem;
  padding-right: 1rem;
}

@media (max-width: 640px) {
  .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-6 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }

  .grid.grid-cols-2.md\\:grid-cols-3.lg\\:grid-cols-6 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }

  .grid.grid-cols-1.lg\\:grid-cols-3 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

/* Custom scrollbar for modal */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.dark .overflow-y-auto::-webkit-scrollbar-track {
  background: #374151;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
  background: #6b7280;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Hover effects */
.hover\\:shadow-md:hover {
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.hover\\:border-blue-500:hover {
  border-color: #3b82f6;
}

.hover\\:bg-blue-50:hover {
  background-color: #eff6ff;
}

.dark .hover\\:bg-blue-50:hover {
  background-color: rgba(59, 130, 246, 0.1);
}

.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

.transition-shadow {
  transition-property: box-shadow;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
</style>