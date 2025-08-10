<template>
  <div class="inventory-report">
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
    <div v-if="report.summary" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div
        v-for="(item, key) in report.summary"
        :key="key"
        class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg"
      >
        <p class="text-sm font-medium text-green-600 dark:text-green-400">
          {{ item.label || formatLabel(key) }}
        </p>
        <p class="text-2xl font-bold text-green-900 dark:text-green-100">
          {{ item.formatted || formatValue(item.value) }}
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
      <!-- Stock Levels -->
      <div v-if="report.data.all_items && report.data.all_items.length > 0">
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t('reports.inventory_items') }}
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.sku') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.category') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.quantity') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.value') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.status') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="item in report.data.all_items" :key="item.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.sku }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.category }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.quantity }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatCurrency(item.total_value) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStockStatusClass(item.status)">
                    {{ item.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Low Stock Items -->
      <div v-if="report.data.low_stock_items && report.data.low_stock_items.length > 0">
        <h4 class="text-md font-medium text-red-700 dark:text-red-300 mb-4">
          {{ $t('reports.low_stock_items') }}
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-red-50 dark:bg-red-900/20">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-red-500 dark:text-red-300 uppercase">
                  {{ $t('reports.sku') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-red-500 dark:text-red-300 uppercase">
                  {{ $t('reports.name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-red-500 dark:text-red-300 uppercase">
                  {{ $t('reports.current_quantity') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-red-500 dark:text-red-300 uppercase">
                  {{ $t('reports.reorder_level') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="item in report.data.low_stock_items" :key="item.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.sku }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 font-medium">
                  {{ item.quantity }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.reorder_level }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Inventory Movements -->
      <div v-if="report.data.movements && report.data.movements.length > 0">
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t('reports.recent_movements') }}
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.date') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.item') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.type') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.quantity_change') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                  {{ $t('reports.reference') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="movement in report.data.movements" :key="movement.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatDate(movement.date) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ movement.item_name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ movement.movement_type }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm" :class="getQuantityChangeClass(movement.quantity_change)">
                  {{ movement.quantity_change > 0 ? '+' : '' }}{{ movement.quantity_change }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ movement.reference || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reorder Items -->
      <div v-if="report.data.reorder_items && report.data.reorder_items.length > 0">
        <h4 class="text-md font-medium text-orange-700 dark:text-orange-300 mb-4">
          {{ $t('reports.items_needing_reorder') }}
        </h4>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-orange-50 dark:bg-orange-900/20">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-orange-500 dark:text-orange-300 uppercase">
                  {{ $t('reports.sku') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-orange-500 dark:text-orange-300 uppercase">
                  {{ $t('reports.name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-orange-500 dark:text-orange-300 uppercase">
                  {{ $t('reports.current_quantity') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-orange-500 dark:text-orange-300 uppercase">
                  {{ $t('reports.suggested_quantity') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-orange-500 dark:text-orange-300 uppercase">
                  {{ $t('reports.estimated_cost') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-orange-500 dark:text-orange-300 uppercase">
                  {{ $t('reports.priority') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="item in report.data.reorder_items" :key="item.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.sku }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.current_quantity }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ item.suggested_reorder_quantity }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatCurrency(item.estimated_cost) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getPriorityClass(item.priority)">
                    {{ item.priority }}
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

const getStockStatusClass = (status: string): string => {
  const baseClass = 'px-2 py-1 text-xs font-medium rounded-full'
  switch (status.toLowerCase()) {
    case 'in stock':
      return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`
    case 'low stock':
      return `${baseClass} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200`
    case 'out of stock':
      return `${baseClass} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`
    default:
      return `${baseClass} bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200`
  }
}

const getQuantityChangeClass = (change: number): string => {
  if (change > 0) {
    return 'text-green-600 dark:text-green-400 font-medium'
  } else if (change < 0) {
    return 'text-red-600 dark:text-red-400 font-medium'
  }
  return 'text-gray-600 dark:text-gray-400'
}

const getPriorityClass = (priority: string): string => {
  const baseClass = 'px-2 py-1 text-xs font-medium rounded-full'
  switch (priority.toLowerCase()) {
    case 'critical':
      return `${baseClass} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`
    case 'high':
      return `${baseClass} bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200`
    case 'medium':
      return `${baseClass} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200`
    case 'low':
      return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`
    default:
      return `${baseClass} bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200`
  }
}
</script>