<template>
  <div class="space-y-6">
    <!-- Header with Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t('accounting.audit_logs') }}
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $t('accounting.audit_logs_description') }}
          </p>
        </div>
        <div class="flex space-x-3">
          <button
            @click="exportAuditLogs"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
          >
            <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
            {{ $t('common.export') }}
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('common.date_from') }}
          </label>
          <input
            v-model="accountingStore.auditLogFilter.date_from"
            @change="accountingStore.fetchAuditLogs()"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('common.date_to') }}
          </label>
          <input
            v-model="accountingStore.auditLogFilter.date_to"
            @change="accountingStore.fetchAuditLogs()"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('accounting.action') }}
          </label>
          <select
            v-model="accountingStore.auditLogFilter.action"
            @change="accountingStore.fetchAuditLogs()"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          >
            <option value="">{{ $t('common.all') }}</option>
            <option value="created">{{ $t('accounting.created') }}</option>
            <option value="updated">{{ $t('accounting.updated') }}</option>
            <option value="deleted">{{ $t('accounting.deleted') }}</option>
            <option value="locked">{{ $t('accounting.locked') }}</option>
            <option value="unlocked">{{ $t('accounting.unlocked') }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('accounting.entity_type') }}
          </label>
          <select
            v-model="accountingStore.auditLogFilter.auditable_type"
            @change="accountingStore.fetchAuditLogs()"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          >
            <option value="">{{ $t('common.all') }}</option>
            <option value="Transaction">{{ $t('accounting.transaction') }}</option>
            <option value="Account">{{ $t('accounting.account') }}</option>
            <option value="Asset">{{ $t('accounting.asset') }}</option>
            <option value="CostCenter">{{ $t('accounting.cost_center') }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('common.search') }}
          </label>
          <input
            v-model="accountingStore.auditLogFilter.search"
            @input="accountingStore.fetchAuditLogs()"
            type="text"
            :placeholder="$t('accounting.search_audit_logs')"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h4 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t('accounting.audit_log_entries') }}
          </h4>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t('common.total') }}: {{ accountingStore.auditLogs.length }}
            </span>
          </div>
        </div>
      </div>

      <div v-if="accountingStore.loading" class="p-6 text-center">
        <div class="inline-flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ $t('common.loading') }}
        </div>
      </div>

      <div v-else-if="accountingStore.auditLogs.length === 0" class="p-12 text-center">
        <ShieldCheckIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t('accounting.no_audit_logs') }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t('accounting.no_audit_logs_description') }}
        </p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('common.date_time') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('common.user') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('accounting.action') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('accounting.entity') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('accounting.ip_address') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t('common.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="log in accountingStore.auditLogs" :key="log.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                {{ formatDateTime(log.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                      <UserIcon class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                    </div>
                  </div>
                  <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ log.user?.name || 'System' }}
                    </p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    getActionColor(log.action)
                  ]"
                >
                  {{ $t(`accounting.${log.action}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                <div>
                  <p class="font-medium">{{ log.auditable_type }}</p>
                  <p class="text-gray-500 dark:text-gray-400">ID: {{ log.auditable_id }}</p>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono">
                {{ log.ip_address || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="viewAuditLogDetails(log)"
                  class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                >
                  {{ $t('common.view_details') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Audit Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <PlusCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ $t('accounting.created_entries') }}
            </p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ auditStats.created }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <PencilIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ $t('accounting.updated_entries') }}
            </p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ auditStats.updated }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <TrashIcon class="h-8 w-8 text-red-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ $t('accounting.deleted_entries') }}
            </p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ auditStats.deleted }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <LockClosedIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ $t('accounting.locked_entries') }}
            </p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ auditStats.locked }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Audit Log Details Modal -->
    <AuditLogDetailsModal
      v-if="showAuditLogDetailsModal"
      :audit-log="viewingAuditLog"
      @close="showAuditLogDetailsModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  ArrowDownTrayIcon,
  ShieldCheckIcon,
  UserIcon,
  PlusCircleIcon,
  PencilIcon,
  TrashIcon,
  LockClosedIcon
} from '@heroicons/vue/24/outline'
import { useAccountingStore, type AuditLogEntry } from '@/stores/accounting'
import { useLocale } from '@/composables/useLocale'
import AuditLogDetailsModal from './AuditLogDetailsModal.vue'

const accountingStore = useAccountingStore()
const { formatDate } = useLocale()

const showAuditLogDetailsModal = ref(false)
const viewingAuditLog = ref<AuditLogEntry | null>(null)

const formatDateTime = (dateString: string) => {
  const date = new Date(dateString)
  return `${formatDate(dateString)} ${date.toLocaleTimeString()}`
}

const getActionColor = (action: string) => {
  switch (action) {
    case 'created':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    case 'updated':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
    case 'deleted':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    case 'locked':
    case 'unlocked':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
  }
}

const auditStats = computed(() => {
  const logs = accountingStore.auditLogs
  return {
    created: logs.filter(log => log.action === 'created').length,
    updated: logs.filter(log => log.action === 'updated').length,
    deleted: logs.filter(log => log.action === 'deleted').length,
    locked: logs.filter(log => log.action === 'locked' || log.action === 'unlocked').length
  }
})

const viewAuditLogDetails = (log: AuditLogEntry) => {
  viewingAuditLog.value = log
  showAuditLogDetailsModal.value = true
}

const exportAuditLogs = async () => {
  try {
    // In real implementation, this would export the audit logs
    console.log('Exporting audit logs')
  } catch (error) {
    console.error('Failed to export audit logs:', error)
  }
}

onMounted(() => {
  accountingStore.fetchAuditLogs()
})
</script>