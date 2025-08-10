<template>
  <div class="sales-report">
    <!-- Report Header -->
    <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
        {{ report.title }}
      </h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        {{ formatDateRange(report.date_range) }}
      </p>
    </div>

    <!-- Summary Cards -->
    <div v-if="report.summary" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div
        v-for="(item, key) in report.summary"
        :key="key"
        class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg"
      >
        <p class="text-sm font-medium text-blue-600 dark:text-blue-400">
          {{ item.label || formatLabel(key) }}
        </p>
        <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
          {{ item.formatted || formatValue(item.value) }}
        </p>
        <p v-if="item.change !== undefined" :class="getChangeClass(item.change)">
          {{ item.change > 0 ? '+' : '' }}{{ item.change.toFixed(1) }}% vs previous period
        </p>
      </div>
    </div>

    <!-- Charts -->
    <div v-if="report.charts" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <div
        v-for="(chart, chartKey) in report.charts"
        :key="chartKey"
        class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg"
      >
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ chart.title }}
        </h4>
        <ChartComponent :type="chart.type" :data="chart.datasets || chart.data || []" :title="chart.title" />
      </div>
    </div>

    <!-- Data Tables -->
    <div v-if="report.data" class="space-y-6">
      <!-- Top Customers -->
      <div v-if="report.data.top_customers && report.data.top_customers.length > 0">
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t('reports.top_customers') }}
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.customer') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.total_sales') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.orders') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="customer in report.data.top_customers" :key="customer.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ customer.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatCurrency(customer.total) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ customer.count }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Products -->
      <div v-if="report.data.top_products && report.data.top_products.length > 0">
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t('reports.top_products') }}
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.product') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.quantity') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.total_sales') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="product in report.data.top_products" :key="product.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ product.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ product.quantity }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatCurrency(product.total) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Daily Sales -->
      <div v-if="report.data.daily_sales && report.data.daily_sales.length > 0">
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t('reports.daily_sales') }}
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.date') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.total_sales') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.orders') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="day in report.data.daily_sales" :key="day.date">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatDate(day.date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatCurrency(day.total) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ day.count }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Detailed Sales Data -->
      <div v-if="report.subtype === 'detailed' && report.data.length > 0">
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t('reports.detailed_sales_data') }}
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.invoice_number') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.date') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.customer') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.amount') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.status') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="invoice in report.data" :key="invoice.invoice_number">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ invoice.invoice_number }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatDate(invoice.date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ invoice.customer }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatCurrency(invoice.total_amount) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusClass(invoice.status)">
                    {{ $t(`invoices.status_${invoice.status}`) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import ChartComponent from '@/components/ui/ChartComponent.vue'

const { t } = useI18n()

interface Props {
  report: any
}

defineProps<Props>()

const formatLabel = (key: string | number): string => {
  return String(key).replace(/_/g, ' ').replace(/\b\w/g, (l: string) => l.toUpperCase())
}

const formatValue = (value: any): string => {
  if (typeof value === 'number') {
    return value.toLocaleString()
  }
  return String(value)
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString()
}

const formatDateRange = (dateRange: { start: string; end: string }): string => {
  const start = new Date(dateRange.start).toLocaleDateString()
  const end = new Date(dateRange.end).toLocaleDateString()
  return `${start} - ${end}`
}

const getChangeClass = (change: number): string => {
  const baseClass = 'text-xs font-medium'
  if (change > 0) {
    return `${baseClass} text-green-600 dark:text-green-400`
  } else if (change < 0) {
    return `${baseClass} text-red-600 dark:text-red-400`
  }
  return `${baseClass} text-gray-600 dark:text-gray-400`
}

const getStatusClass = (status: string): string => {
  const baseClass = 'px-2 py-1 text-xs font-medium rounded-full'
  switch (status) {
    case 'paid':
      return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`
    case 'pending':
      return `${baseClass} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200`
    case 'overdue':
      return `${baseClass} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`
    default:
      return `${baseClass} bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200`
  }
}
</script>