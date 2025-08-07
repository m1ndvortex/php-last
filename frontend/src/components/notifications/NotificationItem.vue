<template>
  <div
    @click="$emit('click', notification)"
    class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors"
    :class="[
      !notification.read ? 'bg-blue-50 border-l-4 border-l-blue-500' : '',
      getPriorityClass(notification.priority)
    ]"
  >
    <div class="flex items-start space-x-3" :class="{ 'space-x-reverse': isRTL }">
      <!-- Icon -->
      <div class="flex-shrink-0 mt-0.5">
        <component
          :is="getNotificationIcon(notification.type, notification.subtype)"
          class="h-5 w-5"
          :class="getIconColor(notification.type, notification.priority)"
        />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <p class="text-sm font-medium text-gray-900 truncate">
            {{ notification.title }}
          </p>
          <div class="flex items-center space-x-2 ml-2" :class="{ 'space-x-reverse': isRTL }">
            <!-- Priority indicator -->
            <div
              v-if="notification.priority >= 3"
              class="w-2 h-2 rounded-full bg-red-500"
              :title="$t('notifications.priority.high')"
            ></div>
            <div
              v-else-if="notification.priority === 2"
              class="w-2 h-2 rounded-full bg-yellow-500"
              :title="$t('notifications.priority.medium')"
            ></div>
            
            <!-- Unread indicator -->
            <div
              v-if="!notification.read"
              class="w-2 h-2 rounded-full bg-blue-500"
              :title="$t('notifications.unread')"
            ></div>
          </div>
        </div>

        <p class="text-sm text-gray-600 mt-1 line-clamp-2">
          {{ notification.message }}
        </p>

        <!-- Metadata -->
        <div class="flex items-center justify-between mt-2">
          <span class="text-xs text-gray-500">
            {{ formatTime(notification.created_at) }}
          </span>
          
          <!-- Action buttons -->
          <div class="flex items-center space-x-1" :class="{ 'space-x-reverse': isRTL }">
            <button
              v-if="!notification.read"
              @click.stop="$emit('mark-read', notification.id)"
              class="text-xs text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-100"
            >
              {{ $t('notifications.markRead') }}
            </button>
            
            <button
              v-if="notification.action_url"
              class="text-xs text-gray-600 hover:text-gray-800 px-2 py-1 rounded hover:bg-gray-100"
            >
              {{ $t('notifications.viewDetails') }}
            </button>
          </div>
        </div>

        <!-- Additional data display for specific notification types -->
        <div v-if="shouldShowAdditionalData(notification)" class="mt-2 p-2 bg-gray-100 rounded text-xs">
          <div v-if="notification.type === 'stock'" class="space-y-1">
            <div v-if="notification.data?.current_quantity !== undefined">
              <span class="font-medium">{{ $t('notifications.data.currentStock') }}:</span>
              {{ notification.data.current_quantity }}
            </div>
            <div v-if="notification.data?.minimum_stock">
              <span class="font-medium">{{ $t('notifications.data.minimumStock') }}:</span>
              {{ notification.data.minimum_stock }}
            </div>
            <div v-if="notification.data?.location">
              <span class="font-medium">{{ $t('notifications.data.location') }}:</span>
              {{ notification.data.location }}
            </div>
          </div>
          
          <div v-else-if="notification.type === 'reminder'" class="space-y-1">
            <div v-if="notification.data?.age">
              <span class="font-medium">{{ $t('notifications.data.age') }}:</span>
              {{ notification.data.age }}
            </div>
            <div v-if="notification.data?.years_married">
              <span class="font-medium">{{ $t('notifications.data.yearsMarried') }}:</span>
              {{ notification.data.years_married }}
            </div>
          </div>
          
          <div v-else-if="notification.type === 'communication'" class="space-y-1">
            <div v-if="notification.data?.communication_type">
              <span class="font-medium">{{ $t('notifications.data.type') }}:</span>
              {{ $t(`communications.types.${notification.data.communication_type}`) }}
            </div>
            <div v-if="notification.data?.customer_name">
              <span class="font-medium">{{ $t('notifications.data.customer') }}:</span>
              {{ notification.data.customer_name }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useLocale } from '@/composables/useLocale'
import {
  ExclamationTriangleIcon,
  InformationCircleIcon,
  BellIcon,
  CubeIcon,
  ChatBubbleLeftRightIcon,
  HeartIcon,
  CogIcon,
  XCircleIcon
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

interface Props {
  notification: Notification
}

const props = defineProps<Props>()

const emit = defineEmits<{
  click: [notification: Notification]
  'mark-read': [notificationId: string]
}>()

const { t } = useI18n()
const { isRTL } = useLocale()

// Methods
const getNotificationIcon = (type: string, subtype: string) => {
  switch (type) {
    case 'stock':
      if (subtype === 'out_of_stock') return XCircleIcon
      if (subtype === 'expiring') return ExclamationTriangleIcon
      return CubeIcon
    case 'communication':
      return ChatBubbleLeftRightIcon
    case 'reminder':
      if (subtype.includes('birthday') || subtype.includes('anniversary')) return HeartIcon
      return BellIcon
    case 'system':
      return CogIcon
    default:
      return InformationCircleIcon
  }
}

const getIconColor = (type: string, priority: number) => {
  if (priority >= 3) return 'text-red-500'
  if (priority === 2) return 'text-yellow-500'
  
  switch (type) {
    case 'stock':
      return 'text-orange-500'
    case 'communication':
      return 'text-blue-500'
    case 'reminder':
      return 'text-pink-500'
    case 'system':
      return 'text-gray-500'
    default:
      return 'text-gray-400'
  }
}

const getPriorityClass = (priority: number) => {
  if (priority >= 3) return 'border-l-red-500'
  if (priority === 2) return 'border-l-yellow-500'
  return ''
}

const formatTime = (timestamp: string) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
  
  if (diffInMinutes < 1) return t('notifications.time.justNow')
  if (diffInMinutes < 60) return t('notifications.time.minutesAgo', { count: diffInMinutes })
  
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) return t('notifications.time.hoursAgo', { count: diffInHours })
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 7) return t('notifications.time.daysAgo', { count: diffInDays })
  
  return date.toLocaleDateString()
}

const shouldShowAdditionalData = (notification: Notification) => {
  return notification.data && Object.keys(notification.data).length > 0 && 
         ['stock', 'reminder', 'communication'].includes(notification.type)
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>