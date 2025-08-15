<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ title }}
      </h3>
      <div class="flex items-center space-x-2 rtl:space-x-reverse">
        <span
          v-if="unreadCount > 0"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
        >
          {{ unreadCount }}
        </span>
        <button
          v-if="alerts.length > 0"
          @click="markAllAsRead"
          class="text-sm text-gray-500 hover:text-gray-700 transition-colors"
        >
          {{ $t("dashboard.alerts.mark_all_read") }}
        </button>
      </div>
    </div>

    <div class="space-y-3 max-h-80 overflow-y-auto">
      <div v-if="alerts.length === 0" class="text-center py-8 text-gray-500">
        <CheckCircleIcon class="w-12 h-12 mx-auto mb-2 text-green-500" />
        <p>{{ $t("dashboard.alerts.no_alerts") }}</p>
      </div>

      <div
        v-for="alert in visibleAlerts"
        :key="alert.id"
        class="flex items-start space-x-3 rtl:space-x-reverse p-3 rounded-lg border transition-colors"
        :class="[
          alert.read
            ? 'bg-gray-50 border-gray-200'
            : 'bg-white border-gray-300',
          severityClasses[alert.severity],
        ]"
      >
        <div class="flex-shrink-0 mt-0.5">
          <component
            :is="getAlertIcon(alert.type)"
            class="w-5 h-5"
            :class="severityIconClasses[alert.severity]"
          />
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p
                class="text-sm font-medium text-right rtl:text-right ltr:text-left"
                :class="alert.read ? 'text-gray-600' : 'text-gray-900'"
              >
                {{ alert.title }}
              </p>
              <p
                class="text-sm mt-1 text-right rtl:text-right ltr:text-left"
                :class="alert.read ? 'text-gray-500' : 'text-gray-700'"
              >
                {{ alert.message }}
              </p>
              <p
                class="text-xs text-gray-400 mt-1 text-right rtl:text-right ltr:text-left"
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
                class="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                :title="$t('dashboard.alerts.mark_read')"
              >
                <CheckIcon class="w-4 h-4" />
              </button>

              <button
                @click="dismissAlert(alert.id)"
                class="p-1 text-gray-400 hover:text-red-600 transition-colors"
                :title="$t('dashboard.alerts.dismiss')"
              >
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div v-if="alert.actionUrl && alert.actionLabel" class="mt-2">
            <button
              @click="handleAction(alert)"
              class="text-xs text-primary-600 hover:text-primary-800 font-medium transition-colors"
            >
              {{ alert.actionLabel }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="alerts.length > visibleCount || dashboardStore.alertsMetadata.hasMore" class="mt-4 text-center">
      <button
        @click="showMore"
        class="text-sm text-primary-600 hover:text-primary-800 font-medium transition-colors"
      >
        {{ $t("dashboard.alerts.show_more") }}
        <span v-if="alerts.length > visibleCount">
          ({{ alerts.length - visibleCount }})
        </span>
        <span v-else-if="dashboardStore.alertsMetadata.hasMore">
          ({{ dashboardStore.alertsMetadata.total - alerts.length }} more)
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { useDashboardStore } from "@/stores/dashboard";
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
} from "@heroicons/vue/24/outline";

interface Props {
  title: string;
  alerts: BusinessAlert[];
  maxVisible?: number;
}

const props = withDefaults(defineProps<Props>(), {
  maxVisible: 5,
});

const { t } = useI18n();
const router = useRouter();
const dashboardStore = useDashboardStore();

const visibleCount = ref(props.maxVisible);

const visibleAlerts = computed(() => 
  props.alerts.slice(0, visibleCount.value)
);

const unreadCount = computed(
  () => props.alerts.filter((alert) => !alert.read).length,
);

const severityClasses = {
  low: "border-l-4 rtl:border-l-0 rtl:border-r-4 border-l-blue-400 rtl:border-r-blue-400",
  medium:
    "border-l-4 rtl:border-l-0 rtl:border-r-4 border-l-yellow-400 rtl:border-r-yellow-400",
  high: "border-l-4 rtl:border-l-0 rtl:border-r-4 border-l-orange-400 rtl:border-r-orange-400",
  critical:
    "border-l-4 rtl:border-l-0 rtl:border-r-4 border-l-red-400 rtl:border-r-red-400",
};

const severityIconClasses = {
  low: "text-blue-500",
  medium: "text-yellow-500",
  high: "text-orange-500",
  critical: "text-red-500",
};

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

const markAsRead = async (alertId: string) => {
  try {
    await dashboardStore.markAlertAsRead(alertId);
  } catch (error) {
    console.error('Failed to mark alert as read:', error);
  }
};

const markAllAsRead = async () => {
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

const handleAction = (alert: BusinessAlert) => {
  if (alert.actionUrl) {
    router.push(alert.actionUrl);
  }
  markAsRead(alert.id);
};

const showMore = async () => {
  // First try to load more from the server
  if (dashboardStore.alertsMetadata.hasMore) {
    await dashboardStore.loadMoreAlerts();
  }
  // Then show more locally
  visibleCount.value = Math.min(visibleCount.value + props.maxVisible, props.alerts.length);
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
</style>
