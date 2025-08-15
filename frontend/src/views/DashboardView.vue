<template>
  <div class="dashboard-container" :class="{ rtl: isRTL }">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4 rtl:space-x-reverse">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t("common.last_updated") }}: {{ formatLastUpdated }}
          </div>
        </div>

        <div class="flex items-center space-x-4 rtl:space-x-reverse">
          <!-- Refresh Button -->
          <button
            @click="refreshDashboard"
            :disabled="isLoading"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors rounded-md disabled:opacity-50"
            :title="$t('dashboard.refresh_dashboard')"
          >
            <ArrowPathIcon
              class="w-5 h-5"
              :class="{ 'animate-spin': isLoading }"
            />
          </button>

          <!-- Alert Count Badge -->
          <div v-if="alertCount > 0" class="relative">
            <button
              @click="showAlertsModal = true"
              class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors rounded-md"
            >
              <BellIcon class="w-5 h-5" />
              <span
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"
              >
                {{ alertCount }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI Overview Section -->
    <div class="kpi-section mb-8">
      <div
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 auto-cols-fr"
      >
        <div
          v-for="kpi in kpis"
          :key="kpi.key"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow min-w-0"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0 pr-4 rtl:pr-0 rtl:pl-4">
              <p
                class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 break-words"
              >
                {{ kpi.label }}
              </p>
              <div class="space-y-1">
                <p
                  class="text-xl font-bold text-gray-900 dark:text-gray-100 truncate"
                >
                  {{ kpi.formattedValue }}
                </p>
                <div
                  v-if="kpi.change !== undefined"
                  class="flex items-center text-xs"
                  :class="kpi.changeClass"
                >
                  <component
                    :is="kpi.changeIcon"
                    class="w-3 h-3 mr-1 flex-shrink-0"
                  />
                  <span class="truncate">{{ Math.abs(kpi.change) }}%</span>
                </div>
              </div>
            </div>

            <div class="flex-shrink-0">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center"
                :class="kpi.iconBg"
              >
                <component
                  :is="kpi.icon"
                  class="w-5 h-5"
                  :class="kpi.iconColor"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dashboard Widgets -->
    <div class="widgets-section mb-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sales Chart -->
        <div
          class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
        >
          <div
            class="flex items-center justify-between mb-4"
            :class="{ 'flex-row-reverse': isRTL }"
          >
            <h3
              class="text-lg font-semibold text-gray-900 dark:text-gray-100"
              :class="{ 'text-right': isRTL }"
            >
              {{ $t("dashboard.sales_overview") }}
            </h3>
            <select
              v-model="selectedPeriod"
              @change="handlePeriodChange(selectedPeriod)"
              class="text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md"
            >
              <option value="monthly">{{ $t("dashboard.periods.monthly") }}</option>
              <option value="weekly">{{ $t("dashboard.periods.weekly") }}</option>
              <option value="yearly">{{ $t("dashboard.periods.yearly") }}</option>
            </select>
          </div>
          <div class="h-64">
            <SalesChart 
              :period="selectedPeriod" 
              @period-change="handlePeriodChange"
              @refresh="refreshSalesChart"
            />
          </div>
        </div>

        <!-- Alerts Widget -->
        <AlertWidget
          :title="$t('dashboard.business_alerts')"
          :alerts="alerts"
          :max-visible="3"
        />
      </div>
    </div>

    <!-- Recent Activities Table -->
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
        <div class="overflow-x-auto">
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
                v-for="activity in activities"
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

    <!-- Quick Actions -->
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
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <button
            v-for="action in quickActions"
            :key="action.key"
            @click="handleQuickAction(action)"
            :disabled="!action.enabled"
            class="relative flex flex-col items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors min-h-[100px] disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <div class="relative">
              <component
                :is="action.icon"
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
            <XMarkIcon class="w-6 w-6" />
          </button>
        </div>

        <div class="space-y-4">
          <div
            v-for="alert in alerts"
            :key="alert.id"
            class="flex items-start space-x-3 rtl:space-x-reverse p-4 rounded-lg border border-gray-200 dark:border-gray-600"
          >
            <div class="flex-shrink-0 mt-0.5">
              <ExclamationTriangleIcon class="w-5 h-5 text-yellow-500" />
            </div>
            <div class="flex-1" :class="{ 'text-right': isRTL }">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ alert.title }}
              </p>
              <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                {{ alert.message }}
              </p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                {{ formatTime(alert.timestamp) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
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
  CheckCircleIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  CurrencyDollarIcon,
  ScaleIcon,
  ClockIcon,
} from "@heroicons/vue/24/outline";

// Import the sales chart component
import SalesChart from "@/components/dashboard/SalesChart.vue";
import AlertWidget from "@/components/dashboard/AlertWidget.vue";

const router = useRouter();
const { t, locale } = useI18n();
const dashboardStore = useDashboardStore();

const showAlertsModal = ref(false);
const isRTL = computed(() => locale.value === "fa");
const isLoading = computed(() => dashboardStore.isLoading);
const lastUpdated = computed(() => new Date(dashboardStore.lastUpdated || Date.now()));

const formatLastUpdated = computed(() => {
  return lastUpdated.value.toLocaleTimeString("en-US");
});

// Dynamic KPI data from store
const kpis = computed(() => 
  dashboardStore.kpis.map(kpi => ({
    key: kpi.key,
    label: t(`dashboard.kpis.${kpi.key}`),
    value: kpi.value,
    formattedValue: kpi.formattedValue || formatKPIValue(kpi.value, kpi.format || 'number'),
    change: kpi.change || 0,
    changeClass: getChangeClass(kpi.changeType || 'neutral'),
    changeIcon: getChangeIcon(kpi.changeType || 'neutral'),
    icon: getKPIIcon(kpi.key),
    iconBg: getIconBg(kpi.color || 'gray'),
    iconColor: getIconColor(kpi.color || 'gray'),
  }))
);

// Helper functions for KPI display
const formatKPIValue = (value: any, format: string) => {
  if (typeof value === 'string') return value;
  
  switch (format) {
    case 'currency':
      return `$${value.toLocaleString()}`;
    case 'percentage':
      return `${value.toFixed(1)}%`;
    case 'weight':
      return `${value.toFixed(1)} kg`;
    default:
      return value.toString();
  }
};

const getChangeClass = (changeType: string) => {
  return changeType === 'increase' ? 'text-green-600' : 'text-red-600';
};

const getChangeIcon = (changeType: string) => {
  return changeType === 'increase' ? ArrowTrendingUpIcon : ArrowTrendingDownIcon;
};

const getKPIIcon = (key: string) => {
  const iconMap: Record<string, any> = {
    gold_sold: ScaleIcon,
    total_profit: CurrencyDollarIcon,
    average_price: CurrencyDollarIcon,
    returns: ExclamationTriangleIcon,
    gross_margin: ChartBarIcon,
    net_margin: ChartBarIcon,
  };
  return iconMap[key] || ChartBarIcon;
};

const getIconBg = (color: string) => {
  const bgMap: Record<string, string> = {
    yellow: 'bg-yellow-100',
    green: 'bg-green-100',
    blue: 'bg-blue-100',
    red: 'bg-red-100',
    purple: 'bg-purple-100',
    indigo: 'bg-indigo-100',
  };
  return bgMap[color] || 'bg-gray-100';
};

const getIconColor = (color: string) => {
  const colorMap: Record<string, string> = {
    yellow: 'text-yellow-600',
    green: 'text-green-600',
    blue: 'text-blue-600',
    red: 'text-red-600',
    purple: 'text-purple-600',
    indigo: 'text-indigo-600',
  };
  return colorMap[color] || 'text-gray-600';
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
    ClockIcon,
    CheckCircleIcon,
  };
  return iconMap[iconName] || ChartBarIcon;
};

// Dynamic data from store
const alerts = computed(() => dashboardStore.alerts);
const alertCount = computed(() => dashboardStore.alertCount);
const activities = computed(() => dashboardStore.recentActivities);
const quickActions = computed(() => dashboardStore.quickActions.map(action => ({
  ...action,
  icon: getIconComponent(action.icon),
})));

const selectedPeriod = ref('monthly');

const refreshDashboard = async () => {
  await dashboardStore.refreshData();
};

const handlePeriodChange = (period: string) => {
  selectedPeriod.value = period;
};

const refreshSalesChart = async () => {
  // This will be handled by the SalesChart component
  console.log('Refreshing sales chart...');
};

const handleQuickAction = (action: any) => {
  if (action.route) {
    router.push(action.route);
  }
};

const formatTime = (timestamp: string) => {
  const date = new Date(timestamp);
  const now = new Date();
  const diffInMinutes = Math.floor(
    (now.getTime() - date.getTime()) / (1000 * 60),
  );

  if (diffInMinutes < 1) {
    return "Just now";
  } else if (diffInMinutes < 60) {
    return `${diffInMinutes} minutes ago`;
  } else if (diffInMinutes < 1440) {
    const hours = Math.floor(diffInMinutes / 60);
    return `${hours} hours ago`;
  } else {
    const days = Math.floor(diffInMinutes / 1440);
    return `${days} days ago`;
  }
};

const getStatusClass = (status: string) => {
  switch (status) {
    case "completed":
      return "bg-green-100 text-green-800";
    case "pending":
      return "bg-yellow-100 text-yellow-800";
    case "failed":
      return "bg-red-100 text-red-800";
    default:
      return "bg-gray-100 text-gray-800";
  }
};

onMounted(async () => {
  // Initialize dashboard data from API
  await dashboardStore.initialize();
  console.log("Dashboard mounted with real data");
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

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
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
