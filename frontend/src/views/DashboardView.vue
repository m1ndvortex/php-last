<template>
  <div class="dashboard-container" :class="{ 'rtl': isRTL }">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4 rtl:space-x-reverse">
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t('common.last_updated') }}: {{ formatLastUpdated }}
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
              <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                {{ alertCount }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI Overview Section -->
    <div class="kpi-section mb-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 auto-cols-fr">
        <div
          v-for="kpi in kpis"
          :key="kpi.key"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow min-w-0"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0 pr-4 rtl:pr-0 rtl:pl-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 break-words">
                {{ kpi.label }}
              </p>
              <div class="space-y-1">
                <p class="text-xl font-bold text-gray-900 dark:text-gray-100 truncate">
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
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-4" :class="{ 'flex-row-reverse': isRTL }">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" :class="{ 'text-right': isRTL }">{{ $t('dashboard.sales_overview') }}</h3>
            <select class="text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md">
              <option>{{ $t('dashboard.periods.monthly') }}</option>
              <option>{{ $t('dashboard.periods.weekly') }}</option>
              <option>{{ $t('dashboard.periods.daily') }}</option>
            </select>
          </div>
          <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
            <div class="text-center">
              <ChartBarIcon class="w-12 h-12 text-gray-400 mx-auto mb-2" />
              <p class="text-gray-500 dark:text-gray-400">{{ $t('dashboard.sales_chart') }}</p>
              <p class="text-sm text-gray-400 dark:text-gray-500">{{ $t('dashboard.chart_integration') }}</p>
            </div>
          </div>
        </div>

        <!-- Alerts Widget -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-4" :class="{ 'flex-row-reverse': isRTL }">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" :class="{ 'text-right': isRTL }">{{ $t('dashboard.business_alerts') }}</h3>
            <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
              {{ alertCount }}
            </span>
          </div>
          <div class="space-y-3">
            <div
              v-for="alert in alerts.slice(0, 3)"
              :key="alert.id"
              class="flex items-start space-x-3 rtl:space-x-reverse p-3 rounded-lg border border-gray-200 dark:border-gray-600"
            >
              <div class="flex-shrink-0 mt-0.5">
                <ExclamationTriangleIcon class="w-5 h-5 text-yellow-500" />
              </div>
              <div class="flex-1 min-w-0" :class="{ 'text-right': isRTL }">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ alert.title }}</p>
                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ alert.message }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatTime(alert.timestamp) }}</p>
              </div>
            </div>
            <div v-if="alerts.length === 0" class="text-center py-4">
              <CheckCircleIcon class="w-8 h-8 text-green-500 mx-auto mb-2" />
              <p class="text-gray-500 dark:text-gray-400">{{ $t('dashboard.no_alerts') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activities Table -->
    <div class="activities-section mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4" :class="{ 'text-right': isRTL }">{{ $t('dashboard.recent_activities') }}</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" dir="auto">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ $t('dashboard.table.activity') }}</th>
                <th class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ $t('dashboard.table.user') }}</th>
                <th class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ $t('dashboard.table.time') }}</th>
                <th class="px-6 py-3 text-left rtl:text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ $t('dashboard.table.status') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="activity in activities"
                :key="activity.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-left rtl:text-right">{{ activity.description }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-left rtl:text-right">{{ activity.user }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-left rtl:text-right">{{ activity.time }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-left rtl:text-right">
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
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4" :class="{ 'text-right': isRTL }">{{ $t('dashboard.quick_actions') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <button
            v-for="action in quickActions"
            :key="action.key"
            @click="handleQuickAction(action)"
            class="flex flex-col items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors min-h-[100px]"
          >
            <component :is="action.icon" class="w-8 h-8 text-gray-600 dark:text-gray-400 mb-2 flex-shrink-0" />
            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 text-center break-words">{{ action.label }}</span>
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
        <div class="flex items-center justify-between mb-6" :class="{ 'flex-row-reverse': isRTL }">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" :class="{ 'text-right': isRTL }">{{ $t('dashboard.business_alerts') }}</h3>
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
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ alert.title }}</p>
              <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ alert.message }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatTime(alert.timestamp) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';

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
  ScaleIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const { t, locale } = useI18n();

const showAlertsModal = ref(false);
const isRTL = computed(() => locale.value === 'fa');
const isLoading = ref(false);
const lastUpdated = ref(new Date());

const formatLastUpdated = computed(() => {
  return lastUpdated.value.toLocaleTimeString('en-US');
});

// Sample KPI data
const kpis = computed(() => [
  {
    key: 'gold_sold',
    label: t('dashboard.kpis.gold_sold'),
    value: 12.5,
    formattedValue: '12.5 kg',
    change: 8.2,
    changeClass: 'text-green-600',
    changeIcon: ArrowTrendingUpIcon,
    icon: ScaleIcon,
    iconBg: 'bg-yellow-100',
    iconColor: 'text-yellow-600'
  },
  {
    key: 'total_profit',
    label: t('dashboard.kpis.total_profit'),
    value: 45230,
    formattedValue: '$45,230',
    change: 12.5,
    changeClass: 'text-green-600',
    changeIcon: ArrowTrendingUpIcon,
    icon: CurrencyDollarIcon,
    iconBg: 'bg-green-100',
    iconColor: 'text-green-600'
  },
  {
    key: 'average_price',
    label: t('dashboard.kpis.average_price'),
    value: 1850,
    formattedValue: '$1,850',
    change: -2.1,
    changeClass: 'text-red-600',
    changeIcon: ArrowTrendingDownIcon,
    icon: CurrencyDollarIcon,
    iconBg: 'bg-blue-100',
    iconColor: 'text-blue-600'
  },
  {
    key: 'returns',
    label: t('dashboard.kpis.returns'),
    value: 2.3,
    formattedValue: '2.3%',
    change: -0.5,
    changeClass: 'text-green-600',
    changeIcon: ArrowTrendingDownIcon,
    icon: ExclamationTriangleIcon,
    iconBg: 'bg-red-100',
    iconColor: 'text-red-600'
  },
  {
    key: 'gross_margin',
    label: t('dashboard.kpis.gross_margin'),
    value: 35.2,
    formattedValue: '35.2%',
    change: 1.8,
    changeClass: 'text-green-600',
    changeIcon: ArrowTrendingUpIcon,
    icon: ChartBarIcon,
    iconBg: 'bg-purple-100',
    iconColor: 'text-purple-600'
  },
  {
    key: 'net_margin',
    label: t('dashboard.kpis.net_margin'),
    value: 28.7,
    formattedValue: '28.7%',
    change: 2.3,
    changeClass: 'text-green-600',
    changeIcon: ArrowTrendingUpIcon,
    icon: ChartBarIcon,
    iconBg: 'bg-indigo-100',
    iconColor: 'text-indigo-600'
  }
]);

// Sample alerts data
const alerts = computed(() => [
  {
    id: '1',
    title: t('dashboard.alerts.pending_cheque'),
    message: t('dashboard.alerts.cheque_due', { number: 'CH-2024-001' }),
    timestamp: new Date().toISOString(),
    read: false
  },
  {
    id: '2',
    title: t('dashboard.alerts.low_stock'),
    message: t('dashboard.alerts.low_stock_message', { count: 5 }),
    timestamp: new Date().toISOString(),
    read: false
  },
  {
    id: '3',
    title: t('dashboard.alerts.items_expiring'),
    message: t('dashboard.alerts.items_expiring_message', { count: 3 }),
    timestamp: new Date().toISOString(),
    read: false
  }
]);

const alertCount = computed(() => alerts.value.filter(alert => !alert.read).length);

// Sample activities data
const activities = computed(() => [
  {
    id: 1,
    description: t('dashboard.activities.invoice_created', { number: 'INV-001' }),
    user: 'Admin',
    time: '2 ' + t('dashboard.minutes_ago'),
    status: t('status.completed')
  },
  {
    id: 2,
    description: t('dashboard.activities.customer_added', { name: 'John Doe' }),
    user: 'Admin',
    time: '5 ' + t('dashboard.minutes_ago'),
    status: t('status.completed')
  },
  {
    id: 3,
    description: t('dashboard.activities.inventory_updated', { item: 'Gold Ring' }),
    user: 'Admin',
    time: '10 ' + t('dashboard.minutes_ago'),
    status: t('status.completed')
  },
  {
    id: 4,
    description: t('dashboard.activities.payment_received', { number: 'INV-002' }),
    user: 'System',
    time: '15 ' + t('dashboard.minutes_ago'),
    status: t('status.completed')
  },
  {
    id: 5,
    description: t('dashboard.activities.stock_alert', { item: 'Silver Necklace' }),
    user: 'System',
    time: '20 ' + t('dashboard.minutes_ago'),
    status: t('status.pending')
  }
]);

const quickActions = computed(() => [
  {
    key: 'add_customer',
    label: t('dashboard.actions.add_customer'),
    icon: UserGroupIcon,
    route: '/customers/new'
  },
  {
    key: 'add_inventory',
    label: t('dashboard.actions.add_item'),
    icon: ArchiveBoxIcon,
    route: '/inventory/new'
  },
  {
    key: 'create_invoice',
    label: t('dashboard.actions.create_invoice'),
    icon: DocumentTextIcon,
    route: '/invoices/new'
  },
  {
    key: 'view_reports',
    label: t('dashboard.actions.view_reports'),
    icon: ChartBarIcon,
    route: '/reports'
  },
  {
    key: 'accounting',
    label: t('dashboard.actions.accounting'),
    icon: CalculatorIcon,
    route: '/accounting'
  },
  {
    key: 'settings',
    label: t('dashboard.actions.settings'),
    icon: CogIcon,
    route: '/settings'
  }
]);

const refreshDashboard = async () => {
  isLoading.value = true;
  // Simulate API call
  setTimeout(() => {
    isLoading.value = false;
    lastUpdated.value = new Date(); // Update the timestamp
  }, 1000);
};

const handleQuickAction = (action: any) => {
  if (action.route) {
    router.push(action.route);
  }
};

const formatTime = (timestamp: string) => {
  const date = new Date(timestamp);
  const now = new Date();
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));
  
  if (diffInMinutes < 1) {
    return 'Just now';
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
    case 'completed':
      return 'bg-green-100 text-green-800';
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    case 'failed':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

onMounted(() => {
  // Initialize dashboard data
  lastUpdated.value = new Date();
  console.log('Dashboard mounted');
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

.rtl th, .rtl td {
  text-align: right;
}

.rtl th:first-child, .rtl td:first-child {
  padding-right: 1.5rem;
  padding-left: 1rem;
}

.rtl th:last-child, .rtl td:last-child {
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
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
