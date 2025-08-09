<template>
  <div class="reports-interface">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ $t('reports.enterprise_reports') }}
      </h1>
      <div class="flex gap-3">
        <button
          @click="showScheduleModal = true"
          class="btn btn-secondary"
        >
          <i class="fas fa-clock mr-2"></i>
          {{ $t('reports.schedule_report') }}
        </button>
        <button
          @click="refreshReports"
          class="btn btn-primary"
          :disabled="loading"
        >
          <i class="fas fa-sync-alt mr-2" :class="{ 'animate-spin': loading }"></i>
          {{ $t('common.refresh') }}
        </button>
      </div>
    </div>

    <!-- Report Type Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8 px-6">
          <button
            v-for="type in reportTypes"
            :key="type.key"
            @click="activeReportType = type.key"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
              activeReportType === type.key
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            <i :class="type.icon" class="mr-2"></i>
            {{ $t(`reports.${type.name}`) }}
          </button>
        </nav>
      </div>

      <!-- Report Filters -->
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <!-- Report Subtype -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ $t('reports.report_type') }}
            </label>
            <select
              v-model="filters.subtype"
              class="form-select w-full"
            >
              <option
                v-for="subtype in currentReportSubtypes"
                :key="subtype"
                :value="subtype"
              >
                {{ $t(`reports.${subtype}`) }}
              </option>
            </select>
          </div>

          <!-- Date Range -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ $t('reports.date_range') }}
            </label>
            <select
              v-model="filters.dateRange"
              @change="updateDateRange"
              class="form-select w-full"
            >
              <option value="today">{{ $t('reports.today') }}</option>
              <option value="yesterday">{{ $t('reports.yesterday') }}</option>
              <option value="this_week">{{ $t('reports.this_week') }}</option>
              <option value="last_week">{{ $t('reports.last_week') }}</option>
              <option value="this_month">{{ $t('reports.this_month') }}</option>
              <option value="last_month">{{ $t('reports.last_month') }}</option>
              <option value="this_quarter">{{ $t('reports.this_quarter') }}</option>
              <option value="last_quarter">{{ $t('reports.last_quarter') }}</option>
              <option value="this_year">{{ $t('reports.this_year') }}</option>
              <option value="custom">{{ $t('reports.custom_range') }}</option>
            </select>
          </div>

          <!-- Custom Date Range -->
          <div v-if="filters.dateRange === 'custom'" class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ $t('reports.custom_date_range') }}
            </label>
            <div class="flex gap-2">
              <input
                v-model="filters.startDate"
                type="date"
                class="form-input flex-1"
              />
              <input
                v-model="filters.endDate"
                type="date"
                class="form-input flex-1"
              />
            </div>
          </div>

          <!-- Language -->
          <div v-if="filters.dateRange !== 'custom'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ $t('reports.language') }}
            </label>
            <select
              v-model="filters.language"
              class="form-select w-full"
            >
              <option value="en">English</option>
              <option value="fa">فارسی</option>
            </select>
          </div>
        </div>

        <!-- Additional Filters -->
        <div v-if="additionalFilters.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div
            v-for="filter in additionalFilters"
            :key="filter.key"
          >
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ $t(`reports.${filter.label}`) }}
            </label>
            <select
              v-model="filters.additional[filter.key]"
              class="form-select w-full"
            >
              <option value="">{{ $t('common.all') }}</option>
              <option
                v-for="option in filter.options"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Generate Button -->
        <div class="flex justify-between items-center">
          <div class="flex gap-2">
            <button
              @click="generateReport"
              :disabled="loading || !canGenerate"
              class="btn btn-primary"
            >
              <i class="fas fa-chart-bar mr-2"></i>
              {{ $t('reports.generate_report') }}
            </button>
            
            <button
              v-if="currentReport"
              @click="showExportModal = true"
              class="btn btn-secondary"
            >
              <i class="fas fa-download mr-2"></i>
              {{ $t('reports.export') }}
            </button>
          </div>

          <div v-if="loading" class="flex items-center text-sm text-gray-500">
            <i class="fas fa-spinner animate-spin mr-2"></i>
            {{ $t('reports.generating_report') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Report Display -->
    <div v-if="currentReport" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
      <ReportDisplay
        :report="currentReport"
        :loading="loading"
        @export="handleExport"
      />
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!loading"
      class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-12 text-center"
    >
      <i class="fas fa-chart-line text-4xl text-gray-400 mb-4"></i>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
        {{ $t('reports.no_report_generated') }}
      </h3>
      <p class="text-gray-500 dark:text-gray-400">
        {{ $t('reports.select_options_and_generate') }}
      </p>
    </div>

    <!-- Schedule Report Modal -->
    <ScheduleReportModal
      v-if="showScheduleModal"
      :report-types="reportTypes"
      @close="showScheduleModal = false"
      @scheduled="handleReportScheduled"
    />

    <!-- Export Modal -->
    <ExportReportModal
      v-if="showExportModal"
      :report="currentReport"
      @close="showExportModal = false"
      @exported="handleReportExported"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotifications } from '@/composables/useNotifications'
import ReportDisplay from './ReportDisplay.vue'
import ScheduleReportModal from './ScheduleReportModal.vue'
import ExportReportModal from './ExportReportModal.vue'
import { api } from '@/services/api'

const { t } = useI18n()
const { showNotification } = useNotifications()

// State
const loading = ref(false)
const showScheduleModal = ref(false)
const showExportModal = ref(false)
const activeReportType = ref('sales')
const currentReport = ref(null)

// Report types
const reportTypes = ref([
  {
    key: 'sales',
    name: 'sales_reports',
    icon: 'fas fa-chart-line',
    subtypes: ['summary', 'detailed', 'by_period', 'by_customer', 'by_product']
  },
  {
    key: 'inventory',
    name: 'inventory_reports',
    icon: 'fas fa-boxes',
    subtypes: ['stock_levels', 'movements', 'valuation', 'aging', 'reorder']
  },
  {
    key: 'financial',
    name: 'financial_reports',
    icon: 'fas fa-calculator',
    subtypes: ['profit_loss', 'balance_sheet', 'cash_flow', 'trial_balance']
  },
  {
    key: 'customer',
    name: 'customer_reports',
    icon: 'fas fa-users',
    subtypes: ['aging', 'purchase_history', 'communication_log', 'analytics']
  }
])

// Filters
const filters = ref({
  subtype: 'summary',
  dateRange: 'this_month',
  startDate: '',
  endDate: '',
  language: 'en',
  additional: {}
})

// Computed
const currentReportSubtypes = computed(() => {
  const type = reportTypes.value.find(t => t.key === activeReportType.value)
  return type?.subtypes || []
})

const additionalFilters = computed(() => {
  // Return additional filters based on report type
  switch (activeReportType.value) {
    case 'sales':
      return [
        {
          key: 'customer_id',
          label: 'customer',
          options: [] // Would be populated from API
        }
      ]
    case 'inventory':
      return [
        {
          key: 'category_id',
          label: 'category',
          options: []
        },
        {
          key: 'location_id',
          label: 'location',
          options: []
        }
      ]
    default:
      return []
  }
})

const canGenerate = computed(() => {
  return filters.value.subtype && 
         (filters.value.dateRange !== 'custom' || 
          (filters.value.startDate && filters.value.endDate))
})

// Methods
const updateDateRange = () => {
  if (filters.value.dateRange !== 'custom') {
    const ranges = getDateRanges()
    const range = ranges[filters.value.dateRange]
    if (range) {
      filters.value.startDate = range.start
      filters.value.endDate = range.end
    }
  }
}

const getDateRanges = () => {
  const today = new Date()
  const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()))
  const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)
  const startOfQuarter = new Date(today.getFullYear(), Math.floor(today.getMonth() / 3) * 3, 1)
  const startOfYear = new Date(today.getFullYear(), 0, 1)

  return {
    today: {
      start: new Date().toISOString().split('T')[0],
      end: new Date().toISOString().split('T')[0]
    },
    yesterday: {
      start: new Date(Date.now() - 86400000).toISOString().split('T')[0],
      end: new Date(Date.now() - 86400000).toISOString().split('T')[0]
    },
    this_week: {
      start: startOfWeek.toISOString().split('T')[0],
      end: new Date().toISOString().split('T')[0]
    },
    this_month: {
      start: startOfMonth.toISOString().split('T')[0],
      end: new Date().toISOString().split('T')[0]
    },
    this_quarter: {
      start: startOfQuarter.toISOString().split('T')[0],
      end: new Date().toISOString().split('T')[0]
    },
    this_year: {
      start: startOfYear.toISOString().split('T')[0],
      end: new Date().toISOString().split('T')[0]
    }
  }
}

const generateReport = async () => {
  loading.value = true
  try {
    const response = await api.post('/reports/generate', {
      type: activeReportType.value,
      subtype: filters.value.subtype,
      date_range: {
        start: filters.value.startDate,
        end: filters.value.endDate
      },
      filters: filters.value.additional,
      language: filters.value.language,
      format: 'json'
    })

    currentReport.value = response.data.data
    showNotification(t('reports.report_generated_successfully'), 'success')
  } catch (error) {
    console.error('Failed to generate report:', error)
    showNotification(t('reports.failed_to_generate_report'), 'error')
  } finally {
    loading.value = false
  }
}

const handleExport = (format: string) => {
  showExportModal.value = true
}

const handleReportScheduled = () => {
  showNotification(t('reports.report_scheduled_successfully'), 'success')
  showScheduleModal.value = false
}

const handleReportExported = () => {
  showNotification(t('reports.report_exported_successfully'), 'success')
  showExportModal.value = false
}

const refreshReports = () => {
  if (currentReport.value) {
    generateReport()
  }
}

// Watchers
watch(activeReportType, () => {
  filters.value.subtype = currentReportSubtypes.value[0] || ''
  filters.value.additional = {}
  currentReport.value = null
})

watch(() => filters.value.subtype, () => {
  currentReport.value = null
})

// Lifecycle
onMounted(() => {
  updateDateRange()
})
</script>

<style scoped>
.reports-interface {
  @apply p-6;
}

.btn {
  @apply px-4 py-2 rounded-lg font-medium transition-colors;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.form-select, .form-input {
  @apply block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white;
}
</style>