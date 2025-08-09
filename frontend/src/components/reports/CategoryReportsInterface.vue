<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div class="sm:flex-auto">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("reports.category_reports") }}
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("reports.category_reports_description") }}
        </p>
      </div>
    </div>

    <!-- Report Type Selector -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <button
            v-for="reportType in reportTypes"
            :key="reportType.key"
            @click="selectedReportType = reportType.key"
            :class="[
              'p-4 border-2 rounded-lg text-left transition-colors',
              selectedReportType === reportType.key
                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
            ]"
          >
            <div class="flex items-center">
              <component :is="reportType.icon" class="h-6 w-6 text-blue-500 mr-3" />
              <div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ $t(reportType.title) }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  {{ $t(reportType.description) }}
                </p>
              </div>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("reports.filters") }}
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Date Range -->
          <div class="md:col-span-2">
            <label class="form-label">{{ $t("reports.date_range") }}</label>
            <div class="flex space-x-2">
              <input
                v-model="filters.start_date"
                type="date"
                class="form-input flex-1"
                :placeholder="$t('reports.start_date')"
              />
              <input
                v-model="filters.end_date"
                type="date"
                class="form-input flex-1"
                :placeholder="$t('reports.end_date')"
              />
            </div>
          </div>

          <!-- Main Category -->
          <div>
            <label class="form-label">{{ $t("categories.main_category") }}</label>
            <select v-model="filters.main_category_id" class="form-input">
              <option value="">{{ $t("reports.all_main_categories") }}</option>
              <option
                v-for="category in mainCategories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.localized_name }}
              </option>
            </select>
          </div>

          <!-- Subcategory -->
          <div>
            <label class="form-label">{{ $t("categories.subcategory") }}</label>
            <select v-model="filters.category_id" class="form-input" :disabled="!availableSubcategories.length">
              <option value="">{{ $t("reports.all_subcategories") }}</option>
              <option
                v-for="category in availableSubcategories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.localized_name }}
              </option>
            </select>
          </div>

          <!-- Gold Purity Range (for purity analysis) -->
          <div v-if="selectedReportType === 'gold_purity_analysis'" class="md:col-span-2">
            <label class="form-label">{{ $t("reports.gold_purity_range") }}</label>
            <div class="flex space-x-2">
              <input
                v-model.number="filters.purity_range_min"
                type="number"
                step="0.1"
                min="1"
                max="24"
                class="form-input flex-1"
                :placeholder="$t('reports.min_purity')"
              />
              <input
                v-model.number="filters.purity_range_max"
                type="number"
                step="0.1"
                min="1"
                max="24"
                class="form-input flex-1"
                :placeholder="$t('reports.max_purity')"
              />
            </div>
          </div>

          <!-- Stock Threshold (for stock levels) -->
          <div v-if="selectedReportType === 'category_stock_levels'">
            <label class="form-label">{{ $t("reports.low_stock_threshold") }}</label>
            <input
              v-model.number="filters.low_stock_threshold"
              type="number"
              min="0"
              class="form-input"
              :placeholder="$t('reports.threshold_placeholder')"
            />
          </div>

          <!-- Group By (for sales performance) -->
          <div v-if="selectedReportType === 'category_sales_performance'">
            <label class="form-label">{{ $t("reports.group_by") }}</label>
            <select v-model="filters.group_by" class="form-input">
              <option value="both">{{ $t("reports.both_categories") }}</option>
              <option value="main_category">{{ $t("reports.main_categories_only") }}</option>
              <option value="subcategory">{{ $t("reports.subcategories_only") }}</option>
            </select>
          </div>
        </div>

        <div class="mt-4 flex justify-end space-x-3">
          <button @click="resetFilters" class="btn-secondary">
            {{ $t("common.reset") }}
          </button>
          <button @click="generateReport" :disabled="loading" class="btn-primary">
            <ChartBarIcon v-if="!loading" class="h-4 w-4 mr-2" />
            <div v-else class="animate-spin h-4 w-4 mr-2 border-2 border-white border-t-transparent rounded-full"></div>
            {{ $t("reports.generate_report") }}
          </button>
        </div>
      </div>
    </div>

    <!-- Report Results -->
    <div v-if="reportData" class="space-y-6">
      <!-- Category Hierarchy Report -->
      <div v-if="selectedReportType === 'category_hierarchy'" class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ $t("reports.category_hierarchy_report") }}
          </h3>
          
          <!-- Summary -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ reportData.summary.total_main_categories }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.main_categories") }}
              </div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ reportData.summary.total_items }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.total_items") }}
              </div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                {{ formatCurrency(reportData.summary.total_value) }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.total_value") }}
              </div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ reportData.summary.total_quantity }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.total_quantity") }}
              </div>
            </div>
          </div>

          <!-- Category Tree -->
          <div class="space-y-4">
            <div
              v-for="category in reportData.categories"
              :key="category.id"
              class="border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <div class="p-4 bg-gray-50 dark:bg-gray-700">
                <div class="flex justify-between items-center">
                  <h4 class="font-medium text-gray-900 dark:text-white">
                    {{ category.name }}
                  </h4>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ category.total_items }} {{ $t("reports.items") }} • 
                    {{ formatCurrency(category.total_value) }}
                  </div>
                </div>
              </div>
              
              <div v-if="category.subcategories.length" class="p-4 space-y-2">
                <div
                  v-for="subcategory in category.subcategories"
                  :key="subcategory.id"
                  class="flex justify-between items-center py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded"
                >
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ subcategory.name }}
                  </span>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ subcategory.total_items }} {{ $t("reports.items") }} • 
                    {{ formatCurrency(subcategory.total_value) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sales Performance Report -->
      <div v-if="selectedReportType === 'category_sales_performance'" class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ $t("reports.sales_performance_report") }}
          </h3>
          
          <!-- Summary -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ formatCurrency(reportData.summary.total_revenue) }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.total_revenue") }}
              </div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ formatCurrency(reportData.summary.total_profit) }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.total_profit") }}
              </div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                {{ reportData.summary.total_quantity_sold }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.quantity_sold") }}
              </div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ formatPercentage(reportData.summary.average_margin_percentage) }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.average_margin") }}
              </div>
            </div>
          </div>

          <!-- Performance Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="table-header">{{ $t("reports.category") }}</th>
                  <th class="table-header">{{ $t("reports.revenue") }}</th>
                  <th class="table-header">{{ $t("reports.profit") }}</th>
                  <th class="table-header">{{ $t("reports.margin") }}</th>
                  <th class="table-header">{{ $t("reports.quantity_sold") }}</th>
                  <th class="table-header">{{ $t("reports.orders") }}</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="item in reportData.performance_data" :key="item.category?.id || `${item.main_category?.id}-${item.subcategory?.id}`">
                  <td class="table-cell">
                    <div v-if="filters.group_by === 'both'">
                      <div class="font-medium">{{ item.main_category?.name || 'N/A' }}</div>
                      <div class="text-sm text-gray-500">{{ item.subcategory?.name || 'N/A' }}</div>
                    </div>
                    <div v-else class="font-medium">
                      {{ item.category?.name || 'N/A' }}
                    </div>
                  </td>
                  <td class="table-cell">{{ formatCurrency(item.total_revenue) }}</td>
                  <td class="table-cell">{{ formatCurrency(item.total_profit) }}</td>
                  <td class="table-cell">
                    <span :class="[
                      'px-2 py-1 text-xs rounded-full',
                      item.margin_percentage >= 20 ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' :
                      item.margin_percentage >= 10 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' :
                      'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                    ]">
                      {{ formatPercentage(item.margin_percentage) }}
                    </span>
                  </td>
                  <td class="table-cell">{{ item.total_quantity_sold }}</td>
                  <td class="table-cell">{{ item.total_orders }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Stock Levels Report -->
      <div v-if="selectedReportType === 'category_stock_levels'" class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ $t("reports.stock_levels_report") }}
          </h3>
          
          <!-- Summary -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                {{ reportData.summary.out_of_stock_count }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.out_of_stock") }}
              </div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                {{ reportData.summary.low_stock_count }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.low_stock") }}
              </div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ reportData.summary.adequate_stock_count }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.adequate_stock") }}
              </div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ formatCurrency(reportData.summary.total_value) }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.total_value") }}
              </div>
            </div>
          </div>

          <!-- Stock Level Tabs -->
          <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
            <nav class="-mb-px flex space-x-8">
              <button
                v-for="level in stockLevels"
                :key="level.key"
                @click="activeStockLevel = level.key"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm',
                  activeStockLevel === level.key
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
              >
                {{ $t(level.label) }} ({{ reportData.stock_levels[level.key]?.length || 0 }})
              </button>
            </nav>
          </div>

          <!-- Stock Items Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="table-header">{{ $t("inventory.item_name") }}</th>
                  <th class="table-header">{{ $t("inventory.sku") }}</th>
                  <th class="table-header">{{ $t("inventory.category") }}</th>
                  <th class="table-header">{{ $t("inventory.quantity") }}</th>
                  <th class="table-header">{{ $t("inventory.unit_price") }}</th>
                  <th class="table-header">{{ $t("inventory.total_value") }}</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="item in reportData.stock_levels[activeStockLevel]" :key="item.id">
                  <td class="table-cell">
                    <div class="font-medium">{{ item.name }}</div>
                    <div v-if="item.gold_purity" class="text-sm text-gray-500">
                      {{ formatGoldPurity(item.gold_purity) }}
                    </div>
                  </td>
                  <td class="table-cell">{{ item.sku }}</td>
                  <td class="table-cell">
                    <div>{{ item.main_category }}</div>
                    <div class="text-sm text-gray-500">{{ item.subcategory }}</div>
                  </td>
                  <td class="table-cell">
                    <span :class="[
                      'px-2 py-1 text-xs rounded-full',
                      item.quantity === 0 ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' :
                      item.quantity <= (filters.low_stock_threshold || 10) ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' :
                      'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                    ]">
                      {{ item.quantity }}
                    </span>
                  </td>
                  <td class="table-cell">
                    <span v-if="item.unit_price !== null && item.unit_price !== undefined">
                      {{ formatCurrency(item.unit_price) }}
                    </span>
                    <span v-else class="text-gray-500 dark:text-gray-400 italic">
                      {{ $t("inventory.price_on_request") }}
                    </span>
                  </td>
                  <td class="table-cell">{{ formatCurrency(item.total_value) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Gold Purity Analysis Report -->
      <div v-if="selectedReportType === 'gold_purity_analysis'" class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ $t("reports.gold_purity_analysis") }}
          </h3>
          
          <!-- Summary -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ reportData.summary.total_purity_groups }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.purity_groups") }}
              </div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ formatCurrency(reportData.summary.total_sales_revenue) }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.sales_revenue") }}
              </div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                {{ formatWeight(reportData.summary.total_weight) }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.total_weight") }}
              </div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
              <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ formatCurrency(reportData.summary.total_inventory_value) }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">
                {{ $t("reports.inventory_value") }}
              </div>
            </div>
          </div>

          <!-- Purity Groups Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="table-header">{{ $t("reports.gold_purity") }}</th>
                  <th class="table-header">{{ $t("reports.inventory_items") }}</th>
                  <th class="table-header">{{ $t("reports.total_weight") }}</th>
                  <th class="table-header">{{ $t("reports.inventory_value") }}</th>
                  <th class="table-header">{{ $t("reports.sales_revenue") }}</th>
                  <th class="table-header">{{ $t("reports.margin") }}</th>
                  <th class="table-header">{{ $t("reports.turnover_rate") }}</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="group in reportData.purity_groups" :key="group.purity">
                  <td class="table-cell">
                    <div class="font-medium">{{ group.display_name }}</div>
                    <div class="text-sm text-gray-500">{{ group.purity }}K</div>
                  </td>
                  <td class="table-cell">{{ group.inventory_count }}</td>
                  <td class="table-cell">{{ formatWeight(group.total_weight) }}</td>
                  <td class="table-cell">{{ formatCurrency(group.total_inventory_value) }}</td>
                  <td class="table-cell">{{ formatCurrency(group.sales_revenue) }}</td>
                  <td class="table-cell">
                    <span :class="[
                      'px-2 py-1 text-xs rounded-full',
                      group.margin_percentage >= 20 ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' :
                      group.margin_percentage >= 10 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' :
                      'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                    ]">
                      {{ formatPercentage(group.margin_percentage) }}
                    </span>
                  </td>
                  <td class="table-cell">
                    <span :class="[
                      'px-2 py-1 text-xs rounded-full',
                      group.turnover_rate >= 0.8 ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' :
                      group.turnover_rate >= 0.5 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' :
                      'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                    ]">
                      {{ formatPercentage(group.turnover_rate * 100) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Options -->
    <div v-if="reportData" class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t("reports.export_options") }}
          </h3>
          <div class="flex space-x-3">
            <button @click="exportReport('csv')" class="btn-secondary">
              <DocumentArrowDownIcon class="h-4 w-4 mr-2" />
              {{ $t("reports.export_csv") }}
            </button>
            <button @click="exportReport('pdf')" class="btn-secondary">
              <DocumentArrowDownIcon class="h-4 w-4 mr-2" />
              {{ $t("reports.export_pdf") }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useApi } from '@/composables/useApi'
import { useNotifications } from '@/composables/useNotifications'
import { useNumberFormatter } from '@/composables/useNumberFormatter'
import {
  ChartBarIcon,
  DocumentArrowDownIcon,
  ChartPieIcon,
  CubeIcon,
  ScaleIcon,
  BeakerIcon
} from '@heroicons/vue/24/outline'
import { apiService } from '@/services/api'

interface Category {
  id: number
  localized_name: string
  parent_id?: number | string
}

interface ReportData {
  summary: {
    total_main_categories: number
    total_items: number
    total_value: number
    total_quantity: number
    total_revenue: number
    total_profit: number
    total_quantity_sold: number
    average_margin_percentage: number
    out_of_stock_count: number
    low_stock_count: number
    adequate_stock_count: number
    total_purity_groups: number
    total_sales_revenue: number
    total_weight: number
    total_inventory_value: number
  }
  categories: any[]
  performance_data: any[]
  stock_levels: Record<string, any[]>
  purity_groups: any[]
}

const { t } = useI18n()
const { execute, data: reportData, loading } = useApi<ReportData>()
const { showSuccess, showError, showInfo } = useNotifications()
const { formatCurrency, formatPercentage } = useNumberFormatter()

// Data
const selectedReportType = ref('category_hierarchy')
const mainCategories = ref<Category[]>([])
const subcategories = ref<Category[]>([])
const activeStockLevel = ref('out_of_stock')

// Filters
const filters = ref({
  start_date: '',
  end_date: '',
  main_category_id: '',
  category_id: '',
  purity_range_min: null,
  purity_range_max: null,
  low_stock_threshold: 10,
  group_by: 'both',
  include_zero_stock: true
})

// Report types
const reportTypes = [
  {
    key: 'category_hierarchy',
    title: 'reports.category_hierarchy',
    description: 'reports.category_hierarchy_desc',
    icon: CubeIcon
  },
  {
    key: 'category_sales_performance',
    title: 'reports.sales_performance',
    description: 'reports.sales_performance_desc',
    icon: ChartBarIcon
  },
  {
    key: 'category_stock_levels',
    title: 'reports.stock_levels',
    description: 'reports.stock_levels_desc',
    icon: ScaleIcon
  },
  {
    key: 'gold_purity_analysis',
    title: 'reports.gold_purity_analysis',
    description: 'reports.gold_purity_analysis_desc',
    icon: BeakerIcon
  }
]

// Stock levels for tabs
const stockLevels = [
  { key: 'out_of_stock', label: 'reports.out_of_stock' },
  { key: 'low_stock', label: 'reports.low_stock' },
  { key: 'adequate_stock', label: 'reports.adequate_stock' }
]

// Computed
const availableSubcategories = computed(() => {
  if (!filters.value.main_category_id) return subcategories.value
  return subcategories.value.filter((cat: Category) => cat.parent_id == filters.value.main_category_id)
})

// Methods
const loadCategories = async () => {
  try {
    const response = await apiService.get('/api/categories')
    const allCategories = response.data

    mainCategories.value = allCategories.filter((cat: Category) => !cat.parent_id)
    subcategories.value = allCategories.filter((cat: Category) => cat.parent_id)
  } catch (error) {
    console.error('Error loading categories:', error)
    showError(t('errors.failed_to_load_categories'), 'error')
  }
}

const generateReport = async () => {
  const endpoint = `/api/inventory-reports/${selectedReportType.value.replace('_', '-')}`
  const params: Record<string, any> = { ...filters.value }
  
  // Clean up empty filters
  Object.keys(params).forEach((key: string) => {
    if (params[key] === '' || params[key] === null || params[key] === undefined) {
      delete params[key]
    }
  })

  await execute(() => apiService.get(endpoint, { params }))
  
  if (reportData.value) {
    showSuccess(t('reports.report_generated_successfully'), '')
  }
}

const resetFilters = () => {
  filters.value = {
    start_date: '',
    end_date: '',
    main_category_id: '',
    category_id: '',
    purity_range_min: null,
    purity_range_max: null,
    low_stock_threshold: 10,
    group_by: 'both',
    include_zero_stock: true
  }
  reportData.value = null
}

const exportReport = async (format: string) => {
  try {
    // Implementation for export functionality
    showInfo(t('reports.export_started'), '')
  } catch (error) {
    console.error('Error exporting report:', error)
    showError(t('errors.failed_to_export_report'), '')
  }
}

const formatGoldPurity = (purity: number) => {
  return `${purity}K`
}

const formatWeight = (weight: number) => {
  return `${weight.toFixed(2)}g`
}

// Watchers
watch(() => filters.value.main_category_id, () => {
  filters.value.category_id = ''
})

watch(selectedReportType, () => {
  reportData.value = null
})

// Lifecycle
onMounted(() => {
  loadCategories()
})
</script>

<style scoped>
.table-header {
  @apply px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider;
}

.table-cell {
  @apply px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white;
}
</style>