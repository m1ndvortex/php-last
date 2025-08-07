<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t('accounting.financial_reports') }}
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $t('accounting.financial_reports_description') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Report Selection -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="report in reportTypes"
        :key="report.type"
        @click="selectReport(report.type)"
        :class="[
          'cursor-pointer rounded-lg border-2 p-6 transition-colors',
          selectedReportType === report.type
            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-400'
            : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
        ]"
      >
        <div class="flex items-center">
          <component :is="report.icon" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
          <div class="ml-4">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white">
              {{ $t(report.title) }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t(report.description) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Parameters -->
    <div v-if="selectedReportType" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
        {{ $t('accounting.report_parameters') }}
      </h4>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Date Range for Period Reports -->
        <div v-if="isPeriodReport">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('common.date_from') }}
          </label>
          <input
            v-model="reportParams.start_date"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>
        <div v-if="isPeriodReport">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('common.date_to') }}
          </label>
          <input
            v-model="reportParams.end_date"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>
        
        <!-- As of Date for Point-in-Time Reports -->
        <div v-if="!isPeriodReport">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('accounting.as_of_date') }}
          </label>
          <input
            v-model="reportParams.as_of_date"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>

        <!-- Currency -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('accounting.currency') }}
          </label>
          <select
            v-model="reportParams.currency"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          >
            <option value="USD">USD - US Dollar</option>
            <option value="EUR">EUR - Euro</option>
            <option value="IRR">IRR - Iranian Rial</option>
            <option value="AED">AED - UAE Dirham</option>
          </select>
        </div>
      </div>

      <div class="flex justify-end space-x-3">
        <button
          @click="generateReport"
          :disabled="accountingStore.loading"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
        >
          <DocumentChartBarIcon class="w-4 h-4 mr-2" />
          <span v-if="accountingStore.loading">{{ $t('common.generating') }}...</span>
          <span v-else>{{ $t('accounting.generate_report') }}</span>
        </button>
      </div>
    </div>

    <!-- Report Display -->
    <div v-if="accountingStore.currentReport" class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h4 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t(`accounting.${accountingStore.currentReport.type}`) }}
          </h4>
          <div class="flex space-x-2">
            <button
              @click="exportReport('pdf')"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
            >
              {{ $t('common.export_pdf') }}
            </button>
            <button
              @click="exportReport('excel')"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
            >
              {{ $t('common.export_excel') }}
            </button>
          </div>
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ getReportPeriodText() }}
        </p>
      </div>

      <!-- Trial Balance -->
      <TrialBalanceReport
        v-if="accountingStore.currentReport.type === 'trial_balance'"
        :report-data="accountingStore.currentReport.data"
      />

      <!-- Balance Sheet -->
      <BalanceSheetReport
        v-else-if="accountingStore.currentReport.type === 'balance_sheet'"
        :report-data="accountingStore.currentReport.data"
      />

      <!-- Income Statement -->
      <IncomeStatementReport
        v-else-if="accountingStore.currentReport.type === 'income_statement'"
        :report-data="accountingStore.currentReport.data"
      />

      <!-- Cash Flow Statement -->
      <CashFlowReport
        v-else-if="accountingStore.currentReport.type === 'cash_flow'"
        :report-data="accountingStore.currentReport.data"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  DocumentChartBarIcon,
  ScaleIcon,
  BanknotesIcon,
  CurrencyDollarIcon,
  ArrowTrendingUpIcon
} from '@heroicons/vue/24/outline'
import { useAccountingStore } from '@/stores/accounting'
import { useLocale } from '@/composables/useLocale'
import TrialBalanceReport from './reports/TrialBalanceReport.vue'
import BalanceSheetReport from './reports/BalanceSheetReport.vue'
import IncomeStatementReport from './reports/IncomeStatementReport.vue'
import CashFlowReport from './reports/CashFlowReport.vue'

const accountingStore = useAccountingStore()
const { formatDate } = useLocale()

const selectedReportType = ref<string>('')
const reportParams = ref({
  start_date: new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0], // Start of year
  end_date: new Date().toISOString().split('T')[0], // Today
  as_of_date: new Date().toISOString().split('T')[0], // Today
  currency: 'USD'
})

const reportTypes = [
  {
    type: 'trial_balance',
    title: 'accounting.trial_balance',
    description: 'accounting.trial_balance_description',
    icon: ScaleIcon,
    isPeriod: false
  },
  {
    type: 'balance_sheet',
    title: 'accounting.balance_sheet',
    description: 'accounting.balance_sheet_description',
    icon: BanknotesIcon,
    isPeriod: false
  },
  {
    type: 'income_statement',
    title: 'accounting.income_statement',
    description: 'accounting.income_statement_description',
    icon: CurrencyDollarIcon,
    isPeriod: true
  },
  {
    type: 'cash_flow',
    title: 'accounting.cash_flow_statement',
    description: 'accounting.cash_flow_description',
    icon: ArrowTrendingUpIcon,
    isPeriod: true
  }
]

const isPeriodReport = computed(() => {
  const reportType = reportTypes.find(r => r.type === selectedReportType.value)
  return reportType?.isPeriod || false
})

const selectReport = (type: string) => {
  selectedReportType.value = type
}

const generateReport = async () => {
  if (!selectedReportType.value) return

  const params = isPeriodReport.value
    ? {
        start_date: reportParams.value.start_date,
        end_date: reportParams.value.end_date,
        currency: reportParams.value.currency
      }
    : {
        as_of_date: reportParams.value.as_of_date,
        currency: reportParams.value.currency
      }

  try {
    await accountingStore.generateReport(selectedReportType.value, params)
  } catch (error) {
    console.error('Failed to generate report:', error)
  }
}

const getReportPeriodText = () => {
  if (!accountingStore.currentReport) return ''

  if (accountingStore.currentReport.period) {
    return `${formatDate(accountingStore.currentReport.period.start_date)} - ${formatDate(accountingStore.currentReport.period.end_date)}`
  } else if (accountingStore.currentReport.as_of_date) {
    return `As of ${formatDate(accountingStore.currentReport.as_of_date)}`
  }

  return ''
}

const exportReport = async (format: 'pdf' | 'excel') => {
  if (!accountingStore.currentReport) return

  try {
    // This would call an API endpoint to export the report
    console.log(`Exporting ${accountingStore.currentReport.type} as ${format}`)
    // await api.post(`/accounting/reports/${accountingStore.currentReport.type}/export`, {
    //   format,
    //   ...reportParams.value
    // })
  } catch (error) {
    console.error('Failed to export report:', error)
  }
}

onMounted(() => {
  // Select trial balance by default
  selectedReportType.value = 'trial_balance'
})
</script>