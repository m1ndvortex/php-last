<template>
  <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("dashboard.category_performance") }}
        </h3>
        <div class="flex items-center space-x-2">
          <select v-model="viewType" class="text-sm border-gray-300 dark:border-gray-600 rounded-md">
            <option value="main">{{ $t("dashboard.main_categories") }}</option>
            <option value="sub">{{ $t("dashboard.subcategories") }}</option>
            <option value="combined">{{ $t("dashboard.combined") }}</option>
          </select>
          <button @click="refreshData" :disabled="loading" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <ArrowPathIcon :class="['h-5 w-5', loading && 'animate-spin']" />
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-48">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-8">
        <div class="text-red-500 dark:text-red-400 mb-2">
          {{ $t("dashboard.error_loading_data") }}
        </div>
        <button @click="refreshData" class="text-sm text-blue-500 hover:text-blue-600 dark:text-blue-400">
          {{ $t("common.retry") }}
        </button>
      </div>

      <!-- Content -->
      <div v-else-if="displayData.length > 0" class="space-y-4">
        <!-- Top Performers -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
            <div class="text-sm font-medium text-green-800 dark:text-green-400 mb-1">
              {{ $t("dashboard.top_revenue") }}
            </div>
            <div v-if="topRevenue" class="space-y-1">
              <div class="font-semibold text-green-900 dark:text-green-300">
                {{ getCategoryName(topRevenue) }}
              </div>
              <div class="text-lg font-bold text-green-600 dark:text-green-400">
                {{ formatCurrency(topRevenue.total_revenue) }}
              </div>
            </div>
          </div>

          <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
            <div class="text-sm font-medium text-blue-800 dark:text-blue-400 mb-1">
              {{ $t("dashboard.top_profit") }}
            </div>
            <div v-if="topProfit" class="space-y-1">
              <div class="font-semibold text-blue-900 dark:text-blue-300">
                {{ getCategoryName(topProfit) }}
              </div>
              <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                {{ formatCurrency(topProfit.profit) }}
              </div>
            </div>
          </div>

          <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg">
            <div class="text-sm font-medium text-purple-800 dark:text-purple-400 mb-1">
              {{ $t("dashboard.best_margin") }}
            </div>
            <div v-if="bestMargin" class="space-y-1">
              <div class="font-semibold text-purple-900 dark:text-purple-300">
                {{ getCategoryName(bestMargin) }}
              </div>
              <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                {{ formatPercentage(bestMargin.margin_percentage) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Performance Chart -->
        <div class="h-64">
          <canvas ref="chartCanvas"></canvas>
        </div>

        <!-- Category List -->
        <div class="max-h-48 overflow-y-auto">
          <div class="space-y-2">
            <div
              v-for="category in displayData.slice(0, 10)"
              :key="getCategoryKey(category)"
              class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded"
            >
              <div class="flex-1 min-w-0">
                <div class="font-medium text-sm text-gray-900 dark:text-white truncate">
                  {{ getCategoryName(category) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ category.total_orders }} {{ $t("dashboard.orders") }} • 
                  {{ category.total_quantity }} {{ $t("dashboard.items_sold") }}
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ formatCurrency(category.total_revenue) }}
                </div>
                <div class="text-xs" :class="[
                  category.margin_percentage >= 20 ? 'text-green-600 dark:text-green-400' :
                  category.margin_percentage >= 10 ? 'text-yellow-600 dark:text-yellow-400' :
                  'text-red-600 dark:text-red-400'
                ]">
                  {{ formatPercentage(category.margin_percentage) }} {{ $t("dashboard.margin") }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- View All Link -->
        <div class="text-center pt-2">
          <router-link
            to="/reports?tab=categories"
            class="text-sm text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300"
          >
            {{ $t("dashboard.view_detailed_reports") }} →
          </router-link>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("dashboard.no_category_data") }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useApi } from '@/composables/useApi'
import { useNumberFormatter } from '@/composables/useNumberFormatter'
import { useLocale } from '@/composables/useLocale'
import { ArrowPathIcon, ChartBarIcon } from '@heroicons/vue/24/outline'
import Chart from 'chart.js/auto'
import { apiService } from '@/services/api'

interface CategoryData {
  id: number
  name: string
  name_persian?: string
  total_revenue: number
  profit: number
  margin_percentage: number
  total_orders: number
  total_quantity: number
}

interface CategoryPerformanceData {
  main_categories: CategoryData[]
  subcategories: CategoryData[]
  combined: CategoryData[]
}

const { t } = useI18n()
const { execute, data, loading, error } = useApi<CategoryPerformanceData>()
const { formatCurrency, formatPercentage } = useNumberFormatter()
const { locale } = useLocale()

// Data
const viewType = ref('main')
const chartCanvas = ref<HTMLCanvasElement | null>(null)
const chart = ref<Chart | null>(null)

// Computed
const displayData = computed(() => {
  if (!data.value) return []
  
  switch (viewType.value) {
    case 'main':
      return data.value.main_categories || []
    case 'sub':
      return data.value.subcategories || []
    case 'combined':
      return data.value.combined || []
    default:
      return []
  }
})

const topRevenue = computed(() => {
  if (!displayData.value.length) return null
  return displayData.value.reduce((max: CategoryData, category: CategoryData) => 
    category.total_revenue > (max?.total_revenue || 0) ? category : max
  )
})

const topProfit = computed(() => {
  if (!displayData.value.length) return null
  return displayData.value.reduce((max: CategoryData, category: CategoryData) => 
    category.profit > (max?.profit || 0) ? category : max
  )
})

const bestMargin = computed(() => {
  if (!displayData.value.length) return null
  return displayData.value.reduce((max: CategoryData, category: CategoryData) => 
    category.margin_percentage > (max?.margin_percentage || 0) ? category : max
  )
})

// Methods
const refreshData = async () => {
  await execute(() => apiService.get('/api/dashboard/category-performance'))
}

const getCategoryName = (category: CategoryData) => {
  if (!category) return 'N/A'
  
  if (locale.value === 'fa' && category.name_persian) {
    return category.name_persian
  }
  return category.name || 'N/A'
}

const getCategoryKey = (category: CategoryData) => {
  return category.id || `${category.name}-${Math.random()}`
}

const createChart = () => {
  if (!chartCanvas.value || !displayData.value.length) return

  const ctx = chartCanvas.value.getContext('2d')
  if (!ctx) return
  
  // Destroy existing chart
  if (chart.value) {
    chart.value.destroy()
  }

  const chartData = displayData.value.slice(0, 8) // Top 8 categories
  
  chart.value = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartData.map((category: CategoryData) => getCategoryName(category)),
      datasets: [
        {
          label: t('dashboard.revenue'),
          data: chartData.map((category: CategoryData) => category.total_revenue),
          backgroundColor: 'rgba(59, 130, 246, 0.5)',
          borderColor: 'rgba(59, 130, 246, 1)',
          borderWidth: 1,
          yAxisID: 'y'
        },
        {
          label: t('dashboard.profit'),
          data: chartData.map((category: CategoryData) => category.profit),
          backgroundColor: 'rgba(16, 185, 129, 0.5)',
          borderColor: 'rgba(16, 185, 129, 1)',
          borderWidth: 1,
          yAxisID: 'y'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            usePointStyle: true,
            padding: 20
          }
        }
      },
      scales: {
        x: {
          ticks: {
            maxRotation: 45,
            minRotation: 0
          }
        },
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          beginAtZero: true
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
  })
}

// Watchers
watch([displayData, viewType], () => {
  nextTick(() => {
    createChart()
  })
})

// Lifecycle
onMounted(() => {
  refreshData()
})

onUnmounted(() => {
  if (chart.value) {
    chart.value.destroy()
  }
})
</script>