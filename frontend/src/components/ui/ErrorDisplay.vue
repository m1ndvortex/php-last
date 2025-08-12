<template>
  <div v-if="error" class="error-display" :class="errorTypeClass">
    <div class="error-header">
      <Icon :name="errorIcon" class="error-icon" />
      <h4 class="error-title">{{ errorTitle }}</h4>
      <button 
        v-if="dismissible" 
        @click="dismiss" 
        class="error-dismiss"
        aria-label="Dismiss error"
      >
        <Icon name="x" />
      </button>
    </div>
    
    <div class="error-content">
      <p class="error-message">{{ error.message }}</p>
      
      <!-- Insufficient Inventory Details -->
      <div v-if="error.error === 'insufficient_inventory' && error.unavailable_items" class="error-details">
        <h5>{{ $t('errors.inventory.unavailable_items') }}:</h5>
        <ul class="unavailable-items-list">
          <li v-for="item in error.unavailable_items" :key="item.item_id" class="unavailable-item">
            <strong>{{ item.item_name || item.item_sku || `Item ${item.item_id}` }}</strong>
            <span class="quantity-info">
              {{ $t('inventory.requested') }}: {{ item.requested_quantity }}, 
              {{ $t('inventory.available') }}: {{ item.available_quantity }}
            </span>
          </li>
        </ul>
      </div>
      
      <!-- Validation Errors -->
      <div v-if="error.error === 'validation_failed' && error.errors" class="error-details">
        <h5>{{ $t('errors.validation_details') }}:</h5>
        <ul class="validation-errors-list">
          <li v-for="(messages, field) in error.errors" :key="field" class="validation-error">
            <strong>{{ formatFieldName(field) }}:</strong>
            <span>{{ messages.join(', ') }}</span>
          </li>
        </ul>
      </div>
      
      <!-- Pricing Error Details -->
      <div v-if="error.error === 'pricing_error' && error.pricing_data" class="error-details">
        <h5>{{ $t('errors.pricing.error_details') }}:</h5>
        <ul class="pricing-details-list">
          <li v-for="(value, key) in error.pricing_data" :key="key" class="pricing-detail">
            <strong>{{ formatFieldName(key) }}:</strong>
            <span>{{ value }}</span>
          </li>
        </ul>
      </div>
      
      <!-- Generic Error Details -->
      <div v-if="showDetails && error.details" class="error-details">
        <button @click="toggleDetails" class="details-toggle">
          {{ showDetailsExpanded ? $t('common.hide_details') : $t('common.show_details') }}
        </button>
        <div v-if="showDetailsExpanded" class="details-content">
          <p><strong>{{ $t('errors.error_type') }}:</strong> {{ error.details.type }}</p>
          <p><strong>{{ $t('errors.error_code') }}:</strong> {{ error.details.code }}</p>
          <p><strong>{{ $t('errors.timestamp') }}:</strong> {{ formatTimestamp(error.details.timestamp) }}</p>
          <p v-if="error.details.path"><strong>{{ $t('errors.request_path') }}:</strong> {{ error.details.path }}</p>
        </div>
      </div>
    </div>
    
    <!-- Action Buttons -->
    <div v-if="showActions" class="error-actions">
      <button 
        v-if="retryable" 
        @click="retry" 
        class="btn btn-primary btn-sm"
        :disabled="retrying"
      >
        <Icon v-if="retrying" name="loader" class="animate-spin" />
        {{ retrying ? $t('common.retrying') : $t('common.retry') }}
      </button>
      
      <button 
        v-if="reportable" 
        @click="reportError" 
        class="btn btn-secondary btn-sm"
      >
        {{ $t('errors.report_error') }}
      </button>
      
      <button 
        v-if="contactSupport" 
        @click="openSupport" 
        class="btn btn-outline btn-sm"
      >
        {{ $t('errors.contact_support') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Icon from './Icon.vue'

interface ErrorDetails {
  type: string
  code: number
  timestamp: string
  path?: string
  [key: string]: any
}

interface ApiError {
  success: boolean
  error: string
  message: string
  details?: ErrorDetails
  errors?: Record<string, string[]>
  unavailable_items?: Array<{
    item_id: number
    item_name?: string
    item_sku?: string
    requested_quantity: number
    available_quantity: number
    error: string
  }>
  pricing_data?: Record<string, any>
  inventory_data?: Record<string, any>
}

interface Props {
  error: ApiError | null
  dismissible?: boolean
  showDetails?: boolean
  showActions?: boolean
  retryable?: boolean
  reportable?: boolean
  contactSupport?: boolean
}

interface Emits {
  (e: 'dismiss'): void
  (e: 'retry'): void
  (e: 'report', error: ApiError): void
  (e: 'contact-support', error: ApiError): void
}

const props = withDefaults(defineProps<Props>(), {
  dismissible: true,
  showDetails: true,
  showActions: true,
  retryable: false,
  reportable: false,
  contactSupport: false
})

const emit = defineEmits<Emits>()
const { t } = useI18n()

const showDetailsExpanded = ref(false)
const retrying = ref(false)

const errorTypeClass = computed(() => {
  if (!props.error) return ''
  
  const errorTypeClasses: Record<string, string> = {
    'insufficient_inventory': 'error-warning',
    'pricing_error': 'error-warning',
    'validation_failed': 'error-warning',
    'unauthenticated': 'error-danger',
    'unauthorized': 'error-danger',
    'resource_not_found': 'error-info',
    'endpoint_not_found': 'error-info',
    'network_error': 'error-danger',
    'database_error': 'error-danger',
    'internal_server_error': 'error-danger'
  }
  
  return errorTypeClasses[props.error.error] || 'error-danger'
})

const errorIcon = computed(() => {
  if (!props.error) return 'alert-circle'
  
  const errorIcons: Record<string, string> = {
    'insufficient_inventory': 'package-x',
    'pricing_error': 'calculator',
    'validation_failed': 'alert-triangle',
    'unauthenticated': 'lock',
    'unauthorized': 'shield-x',
    'resource_not_found': 'search-x',
    'endpoint_not_found': 'link-2-off',
    'network_error': 'wifi-off',
    'database_error': 'database-x',
    'internal_server_error': 'server-x'
  }
  
  return errorIcons[props.error.error] || 'alert-circle'
})

const errorTitle = computed(() => {
  if (!props.error) return ''
  
  const errorTitles: Record<string, string> = {
    'insufficient_inventory': t('errors.inventory.insufficient_stock'),
    'pricing_error': t('errors.pricing.calculation_failed'),
    'validation_failed': t('errors.validation_failed'),
    'unauthenticated': t('errors.auth.invalid_credentials'),
    'unauthorized': t('errors.auth.insufficient_permissions'),
    'resource_not_found': t('errors.not_found'),
    'endpoint_not_found': t('errors.api.endpoint_not_found'),
    'network_error': t('errors.network_error'),
    'database_error': t('errors.database.connection_failed'),
    'internal_server_error': t('errors.server_error')
  }
  
  return errorTitles[props.error.error] || t('errors.something_went_wrong')
})

const formatFieldName = (field: string): string => {
  return field
    .replace(/_/g, ' ')
    .replace(/\./g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase())
}

const formatTimestamp = (timestamp: string): string => {
  try {
    return new Date(timestamp).toLocaleString()
  } catch {
    return timestamp
  }
}

const toggleDetails = () => {
  showDetailsExpanded.value = !showDetailsExpanded.value
}

const dismiss = () => {
  emit('dismiss')
}

const retry = async () => {
  retrying.value = true
  try {
    emit('retry')
  } finally {
    retrying.value = false
  }
}

const reportError = () => {
  if (props.error) {
    emit('report', props.error)
  }
}

const openSupport = () => {
  if (props.error) {
    emit('contact-support', props.error)
  }
}
</script>

<style scoped>
.error-display {
  @apply border rounded-lg p-4 mb-4;
}

.error-warning {
  @apply border-yellow-300 bg-yellow-50 text-yellow-800;
}

.error-danger {
  @apply border-red-300 bg-red-50 text-red-800;
}

.error-info {
  @apply border-blue-300 bg-blue-50 text-blue-800;
}

.error-header {
  @apply flex items-center justify-between mb-3;
}

.error-icon {
  @apply w-5 h-5 mr-2 flex-shrink-0;
}

.error-title {
  @apply font-semibold text-lg flex-1;
}

.error-dismiss {
  @apply p-1 hover:bg-black hover:bg-opacity-10 rounded transition-colors;
}

.error-message {
  @apply mb-3 leading-relaxed;
}

.error-details {
  @apply mt-3 p-3 bg-black bg-opacity-5 rounded border-l-4 border-current;
}

.error-details h5 {
  @apply font-semibold mb-2;
}

.unavailable-items-list,
.validation-errors-list,
.pricing-details-list {
  @apply space-y-2;
}

.unavailable-item,
.validation-error,
.pricing-detail {
  @apply flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2;
}

.quantity-info {
  @apply text-sm opacity-75;
}

.details-toggle {
  @apply text-sm underline hover:no-underline mb-2;
}

.details-content {
  @apply text-sm space-y-1 opacity-75;
}

.error-actions {
  @apply flex flex-wrap gap-2 mt-4 pt-3 border-t border-current border-opacity-20;
}

.btn {
  @apply px-3 py-1 rounded font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-sm {
  @apply text-sm;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}

.btn-secondary {
  @apply bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500;
}

.btn-outline {
  @apply border border-current text-current hover:bg-current hover:text-white focus:ring-current;
}

.btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>