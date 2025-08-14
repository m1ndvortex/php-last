<template>
  <div class="performance-dashboard">
    <div class="dashboard-header">
      <h2 class="text-2xl font-bold mb-4">Performance Monitoring Dashboard</h2>
      <div class="controls flex gap-4 mb-6">
        <button 
          @click="refreshReport"
          class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
        >
          Refresh Report
        </button>
        <button 
          @click="clearAllMetrics"
          class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
        >
          Clear Metrics
        </button>
        <button 
          @click="exportData"
          class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
        >
          Export Data
        </button>
      </div>
    </div>

    <!-- Performance Alerts -->
    <div v-if="alerts.length > 0" class="alerts mb-6">
      <h3 class="text-lg font-semibold mb-2 text-red-600">Performance Alerts</h3>
      <div class="space-y-2">
        <div 
          v-for="alert in alerts" 
          :key="alert"
          class="p-3 bg-red-100 border border-red-300 rounded text-red-700"
        >
          {{ alert }}
        </div>
      </div>
    </div>

    <!-- Optimization Suggestions -->
    <div v-if="suggestions.length > 0" class="suggestions mb-6">
      <h3 class="text-lg font-semibold mb-2 text-yellow-600">Optimization Suggestions</h3>
      <div class="space-y-2">
        <div 
          v-for="suggestion in suggestions" 
          :key="suggestion"
          class="p-3 bg-yellow-100 border border-yellow-300 rounded text-yellow-700"
        >
          {{ suggestion }}
        </div>
      </div>
    </div>

    <!-- Performance Metrics Grid -->
    <div class="metrics-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Tab Switching Performance -->
      <div class="metric-card bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-2">Tab Switching</h3>
        <div class="metric-value text-3xl font-bold mb-2" :class="getMetricColor(report?.tabSwitching.averageTime, thresholds.tabSwitchTime)">
          {{ formatTime(report?.tabSwitching.averageTime) }}
        </div>
        <div class="metric-details text-sm text-gray-600">
          <div>Total Switches: {{ report?.tabSwitching.totalSwitches || 0 }}</div>
          <div>Threshold Violations: {{ report?.tabSwitching.thresholdViolations || 0 }}</div>
          <div>Fastest: {{ formatTime(report?.tabSwitching.fastestSwitch?.switchTime) }}</div>
          <div>Slowest: {{ formatTime(report?.tabSwitching.slowestSwitch?.switchTime) }}</div>
        </div>
      </div>

      <!-- API Performance -->
      <div class="metric-card bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-2">API Response</h3>
        <div class="metric-value text-3xl font-bold mb-2" :class="getMetricColor(report?.apiPerformance.averageResponseTime, thresholds.apiResponseTime)">
          {{ formatTime(report?.apiPerformance.averageResponseTime) }}
        </div>
        <div class="metric-details text-sm text-gray-600">
          <div>Total Requests: {{ report?.apiPerformance.totalRequests || 0 }}</div>
          <div>Threshold Violations: {{ report?.apiPerformance.thresholdViolations || 0 }}</div>
          <div>Fastest: {{ formatTime(report?.apiPerformance.fastestEndpoint?.responseTime) }}</div>
          <div>Slowest: {{ formatTime(report?.apiPerformance.slowestEndpoint?.responseTime) }}</div>
        </div>
      </div>

      <!-- Cache Performance -->
      <div class="metric-card bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-2">Cache Hit Rate</h3>
        <div class="metric-value text-3xl font-bold mb-2" :class="getCacheMetricColor(report?.cachePerformance.hitRate)">
          {{ formatPercentage(report?.cachePerformance.hitRate) }}
        </div>
        <div class="metric-details text-sm text-gray-600">
          <div>Total Requests: {{ report?.cachePerformance.totalRequests || 0 }}</div>
          <div>Cache Hits: {{ report?.cachePerformance.cacheHits || 0 }}</div>
          <div>Cache Misses: {{ report?.cachePerformance.cacheMisses || 0 }}</div>
          <div>Last Updated: {{ formatDate(report?.cachePerformance.lastUpdated) }}</div>
        </div>
      </div>

      <!-- Loading Performance -->
      <div class="metric-card bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-2">Loading Time</h3>
        <div class="metric-value text-3xl font-bold mb-2" :class="getMetricColor(report?.loadingPerformance.averageLoadTime, thresholds.loadingTime)">
          {{ formatTime(report?.loadingPerformance.averageLoadTime) }}
        </div>
        <div class="metric-details text-sm text-gray-600">
          <div>Total Loads: {{ report?.loadingPerformance.totalLoads || 0 }}</div>
          <div>Threshold Violations: {{ report?.loadingPerformance.thresholdViolations || 0 }}</div>
          <div>Fastest: {{ formatTime(report?.loadingPerformance.fastestComponent?.loadTime) }}</div>
          <div>Slowest: {{ formatTime(report?.loadingPerformance.slowestComponent?.loadTime) }}</div>
        </div>
      </div>
    </div>

    <!-- Detailed Performance Charts -->
    <div class="charts-section">
      <h3 class="text-xl font-semibold mb-4">Performance Trends</h3>
      
      <!-- Tab Switch History -->
      <div class="chart-container mb-6">
        <h4 class="text-lg font-medium mb-2">Recent Tab Switches</h4>
        <div class="chart bg-white p-4 rounded-lg shadow">
          <div v-if="recentTabSwitches.length === 0" class="text-gray-500 text-center py-8">
            No tab switch data available
          </div>
          <div v-else class="space-y-2">
            <div 
              v-for="(switchData, index) in recentTabSwitches.slice(-10)" 
              :key="index"
              class="flex justify-between items-center p-2 border-b"
            >
              <div class="flex-1">
                <span class="font-medium">{{ switchData.fromRoute }} → {{ switchData.toRoute }}</span>
                <span class="text-sm text-gray-500 ml-2">{{ formatDate(switchData.timestamp) }}</span>
              </div>
              <div class="text-right">
                <span :class="getMetricColor(switchData.switchTime, thresholds.tabSwitchTime)" class="font-bold">
                  {{ formatTime(switchData.switchTime) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- API Response History -->
      <div class="chart-container mb-6">
        <h4 class="text-lg font-medium mb-2">Recent API Responses</h4>
        <div class="chart bg-white p-4 rounded-lg shadow">
          <div v-if="recentApiResponses.length === 0" class="text-gray-500 text-center py-8">
            No API response data available
          </div>
          <div v-else class="space-y-2">
            <div 
              v-for="(response, index) in recentApiResponses.slice(-10)" 
              :key="index"
              class="flex justify-between items-center p-2 border-b"
            >
              <div class="flex-1">
                <span class="font-medium">{{ response.endpoint }}</span>
                <span class="text-sm text-gray-500 ml-2">{{ formatDate(response.timestamp) }}</span>
                <span v-if="response.cacheHit" class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded ml-2">
                  Cache Hit
                </span>
              </div>
              <div class="text-right">
                <span :class="getMetricColor(response.responseTime, thresholds.apiResponseTime)" class="font-bold">
                  {{ formatTime(response.responseTime) }}
                </span>
                <div class="text-sm text-gray-500">{{ response.status }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Thresholds Configuration -->
    <div class="thresholds-config bg-white p-6 rounded-lg shadow">
      <h3 class="text-lg font-semibold mb-4">Performance Thresholds</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Tab Switch Time (ms)</label>
          <input 
            v-model.number="editableThresholds.tabSwitchTime"
            type="number"
            class="w-full px-3 py-2 border border-gray-300 rounded"
            @change="updateThresholds"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">API Response Time (ms)</label>
          <input 
            v-model.number="editableThresholds.apiResponseTime"
            type="number"
            class="w-full px-3 py-2 border border-gray-300 rounded"
            @change="updateThresholds"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Cache Hit Rate (%)</label>
          <input 
            v-model.number="cacheHitRatePercent"
            type="number"
            min="0"
            max="100"
            class="w-full px-3 py-2 border border-gray-300 rounded"
            @change="updateCacheThreshold"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Loading Time (ms)</label>
          <input 
            v-model.number="editableThresholds.loadingTime"
            type="number"
            class="w-full px-3 py-2 border border-gray-300 rounded"
            @change="updateThresholds"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePerformanceMonitoring } from '@/composables/usePerformanceMonitoring'
import type { PerformanceReport, TabSwitchMetrics, ApiResponseMetrics } from '@/services/performanceMonitoringService'

const {
  generateReport,
  getOptimizationSuggestions,
  checkPerformanceAlerts,
  getPerformanceThresholds,
  setPerformanceThresholds,
  clearMetrics,
  exportMetrics
} = usePerformanceMonitoring()

const report = ref<PerformanceReport | null>(null)
const suggestions = ref<string[]>([])
const alerts = ref<string[]>([])
const thresholds = ref(getPerformanceThresholds())
const editableThresholds = ref({ ...thresholds.value })

const cacheHitRatePercent = computed({
  get: () => Math.round(editableThresholds.value.cacheHitRate * 100),
  set: (value: number) => {
    editableThresholds.value.cacheHitRate = value / 100
  }
})

const recentTabSwitches = computed(() => {
  const metrics = exportMetrics()
  return metrics.tabSwitchMetrics.slice(-20).reverse()
})

const recentApiResponses = computed(() => {
  const metrics = exportMetrics()
  return metrics.apiResponseMetrics.slice(-20).reverse()
})

let refreshInterval: number | null = null

const refreshReport = () => {
  report.value = generateReport()
  suggestions.value = getOptimizationSuggestions()
  alerts.value = checkPerformanceAlerts()
}

const clearAllMetrics = () => {
  clearMetrics()
  refreshReport()
}

const exportData = () => {
  const data = exportMetrics()
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `performance-metrics-${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const updateThresholds = () => {
  setPerformanceThresholds(editableThresholds.value)
  thresholds.value = getPerformanceThresholds()
  refreshReport()
}

const updateCacheThreshold = () => {
  updateThresholds()
}

const formatTime = (time?: number): string => {
  if (time === undefined || time === null) return 'N/A'
  return `${Math.round(time)}ms`
}

const formatPercentage = (rate?: number): string => {
  if (rate === undefined || rate === null) return 'N/A'
  return `${Math.round(rate * 100)}%`
}

const formatDate = (date?: Date): string => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleTimeString()
}

const getMetricColor = (value?: number, threshold?: number): string => {
  if (value === undefined || threshold === undefined) return 'text-gray-500'
  
  if (value <= threshold * 0.7) return 'text-green-600'
  if (value <= threshold * 0.9) return 'text-yellow-600'
  if (value <= threshold) return 'text-orange-600'
  return 'text-red-600'
}

const getCacheMetricColor = (rate?: number): string => {
  if (rate === undefined) return 'text-gray-500'
  
  if (rate >= 0.9) return 'text-green-600'
  if (rate >= 0.8) return 'text-yellow-600'
  if (rate >= 0.6) return 'text-orange-600'
  return 'text-red-600'
}

onMounted(() => {
  refreshReport()
  // Auto-refresh every 30 seconds
  refreshInterval = window.setInterval(refreshReport, 30000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>

<style scoped>
.performance-dashboard {
  @apply p-6 bg-gray-50 min-h-screen;
}

.metric-card {
  @apply transition-shadow duration-200;
}

.metric-card:hover {
  @apply shadow-lg;
}

.metric-value {
  @apply transition-colors duration-200;
}

.chart-container {
  @apply transition-all duration-200;
}
</style>