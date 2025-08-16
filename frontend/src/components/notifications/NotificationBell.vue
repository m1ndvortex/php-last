<template>
  <div class="relative">
    <!-- Notification Bell Button -->
    <button
      @click="toggleNotifications"
      class="p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 relative transition-colors duration-200"
      :class="{ 'text-primary-500': isOpen }"
      :title="$t('notifications.bell_title')"
    >
      <BellIcon class="h-5 w-5" />
      
      <!-- Badge for unread count -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center animate-pulse"
      >
        {{ unreadCount > 9 ? "9+" : unreadCount }}
      </span>
    </button>

    <!-- Notification Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95 translate-y-1"
      enter-to-class="transform opacity-100 scale-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="transform opacity-100 scale-100 translate-y-0"
      leave-to-class="transform opacity-0 scale-95 translate-y-1"
    >
      <div
        v-if="isOpen"
        @click.stop
        :class="[
          'absolute top-full mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 origin-top-right z-50',
          isRTL ? 'left-0' : 'right-0',
        ]"
      >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
              {{ $t('notifications.title') }}
            </h3>
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
              <span
                v-if="unreadCount > 0"
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
              >
                {{ unreadCount }} {{ $t('notifications.unread') }}
              </span>
              <button
                v-if="unreadCount > 0"
                @click="markAllAsRead"
                class="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium transition-colors"
                :disabled="isMarkingAllRead"
              >
                <span v-if="isMarkingAllRead">{{ $t('notifications.marking_read') }}</span>
                <span v-else>{{ $t('notifications.mark_all_read') }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <!-- Loading State -->
          <div v-if="isLoading" class="p-4">
            <div class="animate-pulse space-y-3">
              <div v-for="i in 3" :key="i" class="flex space-x-3 rtl:space-x-reverse">
                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                <div class="flex-1 space-y-2">
                  <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                  <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else-if="notifications.length === 0" class="p-6 text-center">
            <CheckCircleIcon class="w-12 h-12 mx-auto mb-3 text-green-500" />
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t('notifications.no_notifications') }}
            </p>
          </div>

          <!-- Notifications -->
          <div v-else class="py-2">
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer border-l-4"
              :class="[
                notification.read 
                  ? 'border-l-transparent bg-gray-50/50 dark:bg-gray-800/50' 
                  : 'border-l-primary-500 bg-white dark:bg-gray-800',
                getSeverityClasses(notification.severity)
              ]"
              @click="handleNotificationClick(notification)"
            >
              <div class="flex items-start space-x-3 rtl:space-x-reverse">
                <!-- Icon -->
                <div class="flex-shrink-0 mt-0.5">
                  <component
                    :is="getNotificationIcon(notification.type)"
                    class="w-5 h-5"
                    :class="getSeverityIconClasses(notification.severity)"
                  />
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <p
                        class="text-sm font-medium"
                        :class="notification.read 
                          ? 'text-gray-600 dark:text-gray-400' 
                          : 'text-gray-900 dark:text-white'"
                      >
                        {{ notification.title }}
                      </p>
                      <p
                        class="text-sm mt-1"
                        :class="notification.read 
                          ? 'text-gray-500 dark:text-gray-500' 
                          : 'text-gray-700 dark:text-gray-300'"
                      >
                        {{ notification.message }}
                      </p>
                      <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        {{ formatTimestamp(notification.timestamp) }}
                      </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-1 rtl:space-x-reverse ml-2 rtl:ml-0 rtl:mr-2">
                      <button
                        v-if="!notification.read"
                        @click.stop="markAsRead(notification.id)"
                        class="p-1 text-gray-400 hover:text-green-600 dark:hover:text-green-400 transition-colors"
                        :title="$t('notifications.mark_read')"
                        :disabled="markingAsRead.has(notification.id)"
                      >
                        <CheckIcon class="w-4 h-4" />
                      </button>

                      <button
                        @click.stop="dismissNotification(notification.id)"
                        class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                        :title="$t('notifications.dismiss')"
                        :disabled="dismissing.has(notification.id)"
                      >
                        <XMarkIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </div>

                  <!-- Action Button -->
                  <div v-if="notification.actionUrl && notification.actionLabel" class="mt-2">
                    <button
                      @click.stop="handleActionClick(notification)"
                      class="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium transition-colors"
                    >
                      {{ notification.actionLabel }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="notifications.length > 0" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <button
              v-if="hasMore"
              @click="loadMore"
              class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 font-medium transition-colors"
              :disabled="isLoadingMore"
            >
              <span v-if="isLoadingMore">{{ $t('notifications.loading_more') }}</span>
              <span v-else>{{ $t('notifications.load_more') }}</span>
            </button>
            <button
              @click="viewAllNotifications"
              class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300 font-medium transition-colors"
            >
              {{ $t('notifications.view_all') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import {
  BellIcon,
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
  GiftIcon,
} from '@heroicons/vue/24/outline';
import { useDashboardStore } from '@/stores/dashboard';
import type { BusinessAlert } from '@/types/dashboard';

const { t, locale } = useI18n();
const router = useRouter();
const dashboardStore = useDashboardStore();

// State
const isOpen = ref(false);
const isLoading = ref(false);
const isLoadingMore = ref(false);
const isMarkingAllRead = ref(false);
const markingAsRead = ref(new Set<string>());
const dismissing = ref(new Set<string>());
const refreshInterval = ref<NodeJS.Timeout | null>(null);

// Computed
const isRTL = computed(() => locale.value === 'fa');
const notifications = computed(() => dashboardStore.alerts);
const unreadCount = computed(() => dashboardStore.alertCount);
const hasMore = computed(() => dashboardStore.alertsMetadata.hasMore);

// Methods
const toggleNotifications = async () => {
  isOpen.value = !isOpen.value;
  
  if (isOpen.value && notifications.value.length === 0) {
    await loadNotifications();
  }
};

const loadNotifications = async () => {
  try {
    isLoading.value = true;
    await dashboardStore.fetchAlerts(false, true); // Force refresh
  } catch (error) {
    console.error('Failed to load notifications:', error);
  } finally {
    isLoading.value = false;
  }
};

const loadMore = async () => {
  try {
    isLoadingMore.value = true;
    await dashboardStore.fetchAlerts(true); // Load more
  } catch (error) {
    console.error('Failed to load more notifications:', error);
  } finally {
    isLoadingMore.value = false;
  }
};

const markAsRead = async (notificationId: string) => {
  try {
    markingAsRead.value.add(notificationId);
    await dashboardStore.markAlertAsRead(notificationId);
  } catch (error) {
    console.error('Failed to mark notification as read:', error);
  } finally {
    markingAsRead.value.delete(notificationId);
  }
};

const markAllAsRead = async () => {
  try {
    isMarkingAllRead.value = true;
    await dashboardStore.markAllAlertsAsRead();
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error);
  } finally {
    isMarkingAllRead.value = false;
  }
};

const dismissNotification = async (notificationId: string) => {
  try {
    dismissing.value.add(notificationId);
    await dashboardStore.dismissAlert(notificationId);
  } catch (error) {
    console.error('Failed to dismiss notification:', error);
  } finally {
    dismissing.value.delete(notificationId);
  }
};

const handleNotificationClick = (notification: BusinessAlert) => {
  if (!notification.read) {
    markAsRead(notification.id);
  }
  
  if (notification.actionUrl) {
    handleActionClick(notification);
  }
};

const handleActionClick = (notification: BusinessAlert) => {
  if (notification.actionUrl) {
    router.push(notification.actionUrl);
    isOpen.value = false;
  }
};

const viewAllNotifications = () => {
  router.push('/notifications');
  isOpen.value = false;
};

const getNotificationIcon = (type: string) => {
  switch (type) {
    case 'low_stock':
    case 'critical_stock':
      return ArchiveBoxIcon;
    case 'overdue_payment':
      return CurrencyDollarIcon;
    case 'expiring_item':
      return ClockIcon;
    case 'overdue_invoice':
      return DocumentTextIcon;
    case 'birthday_reminder':
      return GiftIcon;
    case 'system':
      return CogIcon;
    default:
      return InformationCircleIcon;
  }
};

const getSeverityClasses = (severity: string) => {
  switch (severity) {
    case 'critical':
      return 'border-l-red-500';
    case 'high':
      return 'border-l-orange-500';
    case 'medium':
      return 'border-l-yellow-500';
    case 'low':
      return 'border-l-blue-500';
    default:
      return 'border-l-gray-500';
  }
};

const getSeverityIconClasses = (severity: string) => {
  switch (severity) {
    case 'critical':
      return 'text-red-500';
    case 'high':
      return 'text-orange-500';
    case 'medium':
      return 'text-yellow-500';
    case 'low':
      return 'text-blue-500';
    default:
      return 'text-gray-500';
  }
};

const formatTimestamp = (timestamp: string) => {
  const date = new Date(timestamp);
  const now = new Date();
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

  if (diffInMinutes < 1) {
    return t('notifications.just_now');
  } else if (diffInMinutes < 60) {
    return t('notifications.minutes_ago', { count: diffInMinutes });
  } else if (diffInMinutes < 1440) {
    const hours = Math.floor(diffInMinutes / 60);
    return t('notifications.hours_ago', { count: hours });
  } else {
    const days = Math.floor(diffInMinutes / 1440);
    return t('notifications.days_ago', { count: days });
  }
};

const handleClickOutside = (event: Event) => {
  const target = event.target as Element;
  if (isOpen.value && !target.closest('.relative')) {
    isOpen.value = false;
  }
};

const startAutoRefresh = () => {
  // Refresh notifications every 30 seconds when dropdown is open
  refreshInterval.value = setInterval(() => {
    if (isOpen.value) {
      loadNotifications();
    }
  }, 30000);
};

const stopAutoRefresh = () => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value);
    refreshInterval.value = null;
  }
};

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  startAutoRefresh();
  
  // Load initial notifications
  loadNotifications();
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  stopAutoRefresh();
});

// Watch for dropdown state changes
watch(isOpen, (newValue) => {
  if (newValue) {
    // Refresh notifications when opening
    loadNotifications();
  }
});
</script>

<style scoped>
/* Custom scrollbar for notifications list */
.max-h-96::-webkit-scrollbar {
  width: 6px;
}

.max-h-96::-webkit-scrollbar-track {
  background: transparent;
}

.max-h-96::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
  background-color: rgba(156, 163, 175, 0.7);
}

/* RTL Support */
[dir="rtl"] .space-x-reverse > * + * {
  margin-left: 0 !important;
  margin-right: 0.75rem !important;
}

[dir="rtl"] .space-x-1 > * + * {
  margin-left: 0 !important;
  margin-right: 0.25rem !important;
}

[dir="rtl"] .space-x-2 > * + * {
  margin-left: 0 !important;
  margin-right: 0.5rem !important;
}

[dir="rtl"] .space-x-3 > * + * {
  margin-left: 0 !important;
  margin-right: 0.75rem !important;
}

[dir="rtl"] .ml-2 {
  margin-left: 0 !important;
  margin-right: 0.5rem !important;
}

[dir="rtl"] .border-l-4 {
  border-left-width: 0 !important;
  border-right-width: 4px !important;
}
</style>