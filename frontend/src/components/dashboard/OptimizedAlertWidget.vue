<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
        {{ title }}
      </h3>
      <div class="flex items-center space-x-2 rtl:space-x-reverse">
        <span
          v-if="!isLoading && memoizedUnreadCount > 0"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
        >
          {{ memoizedUnreadCount }}
        </span>
        <button
          v-if="!isLoading && alerts.length > 0"
          @click="markAllAsRead"
          class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors"
        >
          {{ $t("dashboard.alerts.mark_all_read") }}
        </button>
      </div>
    </div>

    <div class="space-y-3 max-h-80 overflow-y-auto">
      <!-- Loading Skeleton -->
      <div v-if="isLoading" class="space-y-3">
        <div
          v-for="i in 3"
          :key="`skeleton-${i}`"
          class="flex items-start space-x-3 rtl:space-x-reverse p-3 rounded-lg border border-gray-200 dark:border-gray-700 animate-pulse"
        >
          <div class="w-5 h-5 bg-gray-200 dark:bg-gray-700 rounded mt-0.5"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
          </div>
          <div class="flex space-x-1 rtl:space-x-reverse">
            <div class="w-6 h-6 bg-gray-200 dark:bg-gray-700 rounded"></div>
            <div class="w-6 h-6 bg-gray-200 dark:bg-gray-700 rounded"></div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="hasError" class="text-center py-8">
        <ExclamationTriangleIcon class="w-12 h-12 mx-auto mb-4 text-red-500" />
        <p class="text-sm text-red-600 dark:text-red-400 mb-4">{{ $t('dashboard.alerts_error') }}</p>
        <button
          @click="$emit('retry')"
          class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          {{ $t('common.retry') }}
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="alerts.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
        <CheckCircleIcon class="w-12 h-12 mx-auto mb-2 text-green-500" />
        <p>{{ $t("dashboard.alerts.no_alerts") }}</p>
      </div>

      <!-- Alerts List -->
      <div
        v-else
        v-for="alert in memoizedVisibleAlerts"
        :key="alert.id"
        class="flex items-start space-x-3 rtl:space-x-reverse p-3 rounded-lg border transition-colors"
        :class="[
          alert.read
            ? 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600'
            : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-500',
          memoizedSeverityClasses[alert.severity],
        ]"
      >
        <div class="flex-shrink-0 mt-0.5">
          <component
            :is="getAlertIcon(alert.type)"
            class="w-5 h-5"
            :class="memoizedSeverityIconClasses[alert.severity]"
          />
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p
                class="text-sm font-medium text-right rtl:text-right ltr:text-left"
                :class="alert.read ? 'text-gray-600 dark:text-gray-400' : 'text-gray-900 dark:text-gray-100'"
              >
                {{ alert.title }}
              </p>
              <p
                class="text-sm mt-1 text-right rtl:text-right ltr:text-left"
                :class="alert.read ? 'text-gray-500 dark:text-gray-500' : 'text-gray-700 dark:text-gray-300'"
              >
                {{ alert.message }}
              </p>
              <p
                class="text-xs text-gray-400 dark:text-gray-500 mt-1 text-right rtl:text-right ltr:text-left"
              >
                {{ formatTimestamp(alert.timestamp) }}
              </p>
            </div>

            <div
              class="flex items-center space-x-1 rtl:space-x-reverse ml-2 rtl:ml-0 rtl:mr-2"
            >
              <button
                v-if="!alert.read"
                @click="markAsRead(alert.id)"
                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                :title="$t('dashboard.alerts.mark_read')"
              >
                <CheckIcon class="w-4 h-4" />
              </button>

              <button
                @click="dismissAlert(alert.id)"
                class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                :title="$t('dashboard.alerts.dismiss')"
              >
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div v-if="alert.actionUrl && alert.actionLabel" class="mt-2">
            <button
              @click="handleAction(alert)"
              class="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium transition-colors"
            >
              {{ alert.actionLabel }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!isLoading && !hasError && (alerts.length > visibleCount || hasMoreAlerts)" class="mt-4 text-center">
      <button
        @click="showMore"
        class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium transition-colors"
        :disabled="loadingMore"
      >
        <span v-if="loadingMore">{{ $t("common.loading") }}...</span>
        <span v-else>
          {{ $t("dashboard.alerts.show_more") }}
          <span v-if="alerts.length > visibleCount">
            ({{ alerts.length - visibleCount }})
          </span>
          <span v-else-if="hasMoreAlerts">
            ({{ $t("dashboard.alerts.more_available") }})
          </span>
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, toRefs } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import type { BusinessAlert } from "@/types/dashboard";

import {
  CheckCircleIcon,
  InformationCircleIcon,
  CurrencyDollarIcon,
  ArchiveBoxIcon,
  ClockIcon,
  DocumentTextIcon,
  CheckIcon,
  XMarkIcon,
  CogIcon,
  ExclamationTriangleIcon,
} from "@heroicons/vue/24/outline";

interface Props {
  title: string;
  alerts: BusinessAlert[];
  maxVisible?: number;
  isLoading?: boolean;
  hasError?: boolean;
  hasMoreAlerts?: boolean;
  loadingMore?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  maxVisible: 5,
  isLoading: false,
  hasError: false,
  hasMoreAlerts: false,
  loadingMore: false,
});

const emit = defineEmits<{
  markAsRead: [alertId: string];
  markAllAsRead: [];
  dismissAlert: [alertId: string];
  actionClick: [alert: BusinessAlert];
  showMore: [];
  retry: [];
}>();

const { t } = useI18n();
const router = useRouter();

// Use toRefs for better reactivity performance
const { alerts } = toRefs(props);

const visibleCount = ref(props.maxVisible);

// Memoized computed properties for better performance
const memoizedVisibleAlerts = computed(() => 
  alerts.value.slice(0, visibleCount.value)
);

const memoizedUnreadCount = computed(
  () => alerts.value.filter((alert) => !alert.read).length,
);

const memoizedSeverityClasses = computed(() => ({
  low: "border-l-4 rtl:border-l-0 rtl:border-r-4 border-l-blue-400 rtl:border-r-blue-400 dark:border-l-blue-500 dark:rtl:border-r-blue-500",
  medium: "border-l-4 rtl:border-l-0 rtl:border-r-4 border-l-yellow-400 rtl:border-r-yellow-400 dark:border-l-yellow-500 dark:rtl:border-r-yellow-500",
  high: "border-l-4 rtl:border-l-0 rtl:border-r-4 border-l-orange-400 rtl:border-r-orange-400 dark:border-l-orange-500 dark:rtl:border-r-orange-500",
  critical: "border-l-4 rtl:border-l-0 rtl:border-r-4 border-l-red-400 rtl:border-r-red-400 dark:border-l-red-500 dark:rtl:border-r-red-500",
}));

const memoizedSeverityIconClasses = computed(() => ({
  low: "text-blue-500 dark:text-blue-400",
  medium: "text-yellow-500 dark:text-yellow-400",
  high: "text-orange-500 dark:text-orange-400",
  critical: "text-red-500 dark:text-red-400",
}));

const getAlertIcon = (type: string) => {
  switch (type) {
    case "pending_cheque":
      return CurrencyDollarIcon;
    case "low_stock":
      return ArchiveBoxIcon;
    case "expiring_item":
      return ClockIcon;
    case "overdue_invoice":
      return DocumentTextIcon;
    case "system":
      return CogIcon;
    default:
      return InformationCircleIcon;
  }
};

const formatTimestamp = (timestamp: string) => {
  const date = new Date(timestamp);
  const now = new Date();
  const diffInMinutes = Math.floor(
    (now.getTime() - date.getTime()) / (1000 * 60),
  );

  if (diffInMinutes < 1) {
    return t("dashboard.alerts.just_now");
  } else if (diffInMinutes < 60) {
    return t("dashboard.alerts.minutes_ago", { count: diffInMinutes });
  } else if (diffInMinutes < 1440) {
    const hours = Math.floor(diffInMinutes / 60);
    return t("dashboard.alerts.hours_ago", { count: hours });
  } else {
    const days = Math.floor(diffInMinutes / 1440);
    return t("dashboard.alerts.days_ago", { count: days });
  }
};

const markAsRead = (alertId: string) => {
  emit('markAsRead', alertId);
};

const markAllAsRead = () => {
  emit('markAllAsRead');
};

const dismissAlert = (alertId: string) => {
  emit('dismissAlert', alertId);
};

const handleAction = (alert: BusinessAlert) => {
  emit('actionClick', alert);
  if (alert.actionUrl) {
    router.push(alert.actionUrl);
  }
  markAsRead(alert.id);
};

const showMore = () => {
  visibleCount.value = Math.min(visibleCount.value + props.maxVisible, alerts.value.length);
  emit('showMore');
};
</script>

<style scoped>
/* RTL Support for Alert Widget */
[dir="rtl"] .space-x-2 > * + * {
  margin-left: 0 !important;
  margin-right: 0.5rem !important;
}

[dir="rtl"] .space-x-3 > * + * {
  margin-left: 0 !important;
  margin-right: 0.75rem !important;
}

[dir="rtl"] .space-x-1 > * + * {
  margin-left: 0 !important;
  margin-right: 0.25rem !important;
}

[dir="rtl"] .ml-2 {
  margin-left: 0 !important;
  margin-right: 0.5rem !important;
}

[dir="rtl"] .border-l-4 {
  border-left-width: 0 !important;
  border-right-width: 4px !important;
}

/* RTL text alignment for alert content */
[dir="rtl"] .space-y-3 > div p {
  text-align: right !important;
}

/* Ensure proper RTL text alignment for all Persian text */
[dir="rtl"] p,
[dir="rtl"] span,
[dir="rtl"] div {
  text-align: right !important;
}

/* Custom scrollbar */
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
</style>