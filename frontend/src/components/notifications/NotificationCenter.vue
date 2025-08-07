<template>
  <div class="notification-center">
    <!-- Notification Bell Icon -->
    <div class="relative">
      <button
        @click="toggleNotifications"
        class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
        :class="{ 'text-blue-600': hasUnread }"
      >
        <BellIcon class="h-6 w-6" />
        <span
          v-if="unreadCount > 0"
          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
        >
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
      </button>

      <!-- Notification Dropdown -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <div
          v-if="showNotifications"
          class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
          :class="{ 'left-0': isRTL }"
        >
          <!-- Header -->
          <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ $t('notifications.title') }}
            </h3>
            <div class="flex items-center space-x-2" :class="{ 'space-x-reverse': isRTL }">
              <button
                @click="markAllAsRead"
                v-if="unreadCount > 0"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                {{ $t('notifications.markAllRead') }}
              </button>
              <button
                @click="refreshNotifications"
                class="p-1 text-gray-400 hover:text-gray-600"
                :disabled="loading"
              >
                <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': loading }" />
              </button>
            </div>
          </div>

          <!-- Filter Tabs -->
          <div class="px-4 py-2 border-b border-gray-100">
            <div class="flex space-x-1" :class="{ 'space-x-reverse': isRTL }">
              <button
                v-for="filter in filters"
                :key="filter.key"
                @click="activeFilter = filter.key"
                class="px-3 py-1 text-sm rounded-md transition-colors"
                :class="[
                  activeFilter === filter.key
                    ? 'bg-blue-100 text-blue-700'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                ]"
              >
                {{ filter.label }}
                <span
                  v-if="filter.count > 0"
                  class="ml-1 px-1.5 py-0.5 text-xs rounded-full"
                  :class="[
                    activeFilter === filter.key
                      ? 'bg-blue-200 text-blue-800'
                      : 'bg-gray-200 text-gray-600'
                  ]"
                >
                  {{ filter.count }}
                </span>
              </button>
            </div>
          </div>

          <!-- Notifications List -->
          <div class="max-h-96 overflow-y-auto">
            <div v-if="loading && notifications.length === 0" class="p-4 text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
              <p class="mt-2 text-sm text-gray-500">{{ $t('notifications.loading') }}</p>
            </div>

            <div v-else-if="filteredNotifications.length === 0" class="p-8 text-center">
              <BellSlashIcon class="h-12 w-12 text-gray-300 mx-auto mb-3" />
              <p class="text-gray-500">{{ $t('notifications.empty') }}</p>
            </div>

            <div v-else>
              <NotificationItem
                v-for="notification in filteredNotifications"
                :key="notification.id"
                :notification="notification"
                @click="handleNotificationClick"
                @mark-read="markAsRead"
              />
            </div>
          </div>

          <!-- Footer -->
          <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <button
              @click="viewAllNotifications"
              class="w-full text-sm text-blue-600 hover:text-blue-800 font-medium"
            >
              {{ $t('notifications.viewAll') }}
            </button>
          </div>
        </div>
      </Transition>
    </div>

    <!-- Click outside to close -->
    <div
      v-if="showNotifications"
      @click="showNotifications = false"
      class="fixed inset-0 z-40"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useLocale } from '@/composables/useLocale'
import { useApi } from '@/composables/useApi'
import { useNotifications } from '@/composables/useNotifications'
import NotificationItem from './NotificationItem.vue'
import {
  BellIcon,
  BellSlashIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

interface Notification {
  id: string
  type: string
  subtype: string
  title: string
  message: string
  priority: number
  read: boolean
  created_at: string
  action_url?: string
  data?: any
}

const router = useRouter()
const { t } = useI18n()
const { isRTL } = useLocale()
const { get, post } = useApi()
const { showNotification } = useNotifications()

// State
const showNotifications = ref(false)
const loading = ref(false)
const notifications = ref<Notification[]>([])
const stats = ref<any>({})
const activeFilter = ref('all')
const pollInterval = ref<NodeJS.Timeout | null>(null)
const lastCheck = ref<string | null>(null)

// Computed
const unreadCount = computed(() => stats.value.unread || 0)
const hasUnread = computed(() => unreadCount.value > 0)

const filters = computed(() => [
  {
    key: 'all',
    label: t('notifications.filters.all'),
    count: stats.value.total || 0
  },
  {
    key: 'stock',
    label: t('notifications.filters.stock'),
    count: stats.value.by_type?.stock?.total || 0
  },
  {
    key: 'communication',
    label: t('notifications.filters.communication'),
    count: stats.value.by_type?.communication?.total || 0
  },
  {
    key: 'reminder',
    label: t('notifications.filters.reminder'),
    count: stats.value.by_type?.reminder?.total || 0
  },
  {
    key: 'system',
    label: t('notifications.filters.system'),
    count: stats.value.by_type?.system?.total || 0
  }
])

const filteredNotifications = computed(() => {
  if (activeFilter.value === 'all') {
    return notifications.value
  }
  return notifications.value.filter(n => n.type === activeFilter.value)
})

// Methods
const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value) {
    loadNotifications()
  }
}

const loadNotifications = async () => {
  try {
    loading.value = true
    
    const [notificationsResponse, statsResponse] = await Promise.all([
      get('/notifications', {
        params: { limit: 50 }
      }),
      get('/notifications/stats')
    ])

    if (notificationsResponse.success) {
      notifications.value = notificationsResponse.data.notifications
    }

    if (statsResponse.success) {
      stats.value = statsResponse.data
    }

  } catch (error) {
    console.error('Failed to load notifications:', error)
    showNotification({
      type: 'error',
      title: t('notifications.error.loadFailed'),
      message: t('notifications.error.loadFailedMessage')
    })
  } finally {
    loading.value = false
  }
}

const refreshNotifications = () => {
  loadNotifications()
}

const markAsRead = async (notificationId: string) => {
  try {
    const response = await post(`/notifications/${notificationId}/read`)
    
    if (response.success) {
      // Update local state
      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification) {
        notification.read = true
      }
      
      // Update stats
      if (stats.value.unread > 0) {
        stats.value.unread--
      }
    }
  } catch (error) {
    console.error('Failed to mark notification as read:', error)
  }
}

const markAllAsRead = async () => {
  try {
    const response = await post('/notifications/mark-all-read', {
      type: activeFilter.value === 'all' ? undefined : activeFilter.value
    })
    
    if (response.success) {
      // Update local state
      notifications.value.forEach(notification => {
        if (activeFilter.value === 'all' || notification.type === activeFilter.value) {
          notification.read = true
        }
      })
      
      // Update stats
      if (activeFilter.value === 'all') {
        stats.value.unread = 0
        Object.keys(stats.value.by_type || {}).forEach(type => {
          stats.value.by_type[type].unread = 0
        })
      } else {
        const typeStats = stats.value.by_type?.[activeFilter.value]
        if (typeStats) {
          stats.value.unread -= typeStats.unread
          typeStats.unread = 0
        }
      }
      
      showNotification({
        type: 'success',
        title: t('notifications.success.markedAllRead')
      })
    }
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error)
    showNotification({
      type: 'error',
      title: t('notifications.error.markAllReadFailed')
    })
  }
}

const handleNotificationClick = (notification: Notification) => {
  // Mark as read if not already
  if (!notification.read) {
    markAsRead(notification.id)
  }
  
  // Navigate to action URL if available
  if (notification.action_url) {
    router.push(notification.action_url)
    showNotifications.value = false
  }
}

const viewAllNotifications = () => {
  router.push('/notifications')
  showNotifications.value = false
}

const pollRealTimeNotifications = async () => {
  try {
    const response = await get('/notifications/realtime', {
      params: { last_check: lastCheck.value }
    })
    
    if (response.success && response.data.notifications.length > 0) {
      // Add new notifications to the beginning of the list
      const newNotifications = response.data.notifications
      notifications.value = [...newNotifications, ...notifications.value]
      
      // Update last check time
      lastCheck.value = response.data.timestamp
      
      // Update stats (simplified - in real app you'd recalculate)
      stats.value.total = (stats.value.total || 0) + newNotifications.length
      stats.value.unread = (stats.value.unread || 0) + newNotifications.filter((n: Notification) => !n.read).length
      
      // Show toast for high priority notifications
      newNotifications.forEach((notification: Notification) => {
        if (notification.priority >= 3) {
          showNotification({
            type: 'warning',
            title: notification.title,
            message: notification.message,
            duration: 5000
          })
        }
      })
    }
  } catch (error) {
    console.error('Failed to poll real-time notifications:', error)
  }
}

const startPolling = () => {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
  }
  
  pollInterval.value = setInterval(pollRealTimeNotifications, 30000) // Poll every 30 seconds
}

const stopPolling = () => {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
    pollInterval.value = null
  }
}

// Lifecycle
onMounted(() => {
  loadNotifications()
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})

// Watch for filter changes
watch(activeFilter, () => {
  if (showNotifications.value) {
    // Could load filtered notifications from server if needed
  }
})
</script>

<style scoped>
.notification-center {
  position: relative;
}

/* Custom scrollbar for notifications list */
.max-h-96::-webkit-scrollbar {
  width: 4px;
}

.max-h-96::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.max-h-96::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>