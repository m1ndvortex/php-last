<template>
  <div class="p-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Assets -->
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
          {{ $t('accounting.assets') }}
        </h3>
        
        <!-- Current Assets -->
        <div class="mb-6">
          <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">
            {{ $t('accounting.current_assets') }}
          </h4>
          <div class="space-y-2">
            <div
              v-for="asset in reportData.assets.current_assets"
              :key="asset.code"
              class="flex justify-between items-center py-1"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
                {{ asset.name }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(asset.balance) }}
              </span>
            </div>
            <div class="flex justify-between items-center py-1 border-t border-gray-200 dark:border-gray-700 font-medium">
              <span class="text-sm text-gray-800 dark:text-gray-200 pl-2">
                {{ $t('accounting.total_current_assets') }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(reportData.assets.current_assets.reduce((sum, asset) => sum + asset.balance, 0)) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Fixed Assets -->
        <div class="mb-6">
          <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">
            {{ $t('accounting.fixed_assets') }}
          </h4>
          <div class="space-y-2">
            <div
              v-for="asset in reportData.assets.fixed_assets"
              :key="asset.code"
              class="flex justify-between items-center py-1"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
                {{ asset.name }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(asset.balance) }}
              </span>
            </div>
            <div class="flex justify-between items-center py-1 border-t border-gray-200 dark:border-gray-700 font-medium">
              <span class="text-sm text-gray-800 dark:text-gray-200 pl-2">
                {{ $t('accounting.total_fixed_assets') }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(reportData.assets.fixed_assets.reduce((sum, asset) => sum + asset.balance, 0)) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Total Assets -->
        <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-3">
          <div class="flex justify-between items-center font-bold text-lg">
            <span class="text-gray-900 dark:text-white">
              {{ $t('accounting.total_assets') }}
            </span>
            <span class="text-gray-900 dark:text-white font-mono">
              {{ formatCurrency(reportData.assets.total) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Liabilities and Equity -->
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
          {{ $t('accounting.liabilities_and_equity') }}
        </h3>
        
        <!-- Current Liabilities -->
        <div class="mb-6">
          <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">
            {{ $t('accounting.current_liabilities') }}
          </h4>
          <div class="space-y-2">
            <div
              v-for="liability in reportData.liabilities.current_liabilities"
              :key="liability.code"
              class="flex justify-between items-center py-1"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
                {{ liability.name }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(liability.balance) }}
              </span>
            </div>
            <div class="flex justify-between items-center py-1 border-t border-gray-200 dark:border-gray-700 font-medium">
              <span class="text-sm text-gray-800 dark:text-gray-200 pl-2">
                {{ $t('accounting.total_current_liabilities') }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(reportData.liabilities.current_liabilities.reduce((sum, liability) => sum + liability.balance, 0)) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Long-term Liabilities -->
        <div class="mb-6">
          <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">
            {{ $t('accounting.long_term_liabilities') }}
          </h4>
          <div class="space-y-2">
            <div
              v-for="liability in reportData.liabilities.long_term_liabilities"
              :key="liability.code"
              class="flex justify-between items-center py-1"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
                {{ liability.name }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(liability.balance) }}
              </span>
            </div>
            <div class="flex justify-between items-center py-1 border-t border-gray-200 dark:border-gray-700 font-medium">
              <span class="text-sm text-gray-800 dark:text-gray-200 pl-2">
                {{ $t('accounting.total_long_term_liabilities') }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(reportData.liabilities.long_term_liabilities.reduce((sum, liability) => sum + liability.balance, 0)) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Total Liabilities -->
        <div class="mb-6 border-t border-gray-200 dark:border-gray-700 pt-3">
          <div class="flex justify-between items-center font-medium">
            <span class="text-gray-800 dark:text-gray-200">
              {{ $t('accounting.total_liabilities') }}
            </span>
            <span class="text-gray-900 dark:text-white font-mono">
              {{ formatCurrency(reportData.liabilities.total) }}
            </span>
          </div>
        </div>

        <!-- Equity -->
        <div class="mb-6">
          <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">
            {{ $t('accounting.equity') }}
          </h4>
          <div class="space-y-2">
            <div
              v-for="equity in reportData.equity.accounts"
              :key="equity.code"
              class="flex justify-between items-center py-1"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
                {{ equity.name }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(equity.balance) }}
              </span>
            </div>
            <div class="flex justify-between items-center py-1 border-t border-gray-200 dark:border-gray-700 font-medium">
              <span class="text-sm text-gray-800 dark:text-gray-200 pl-2">
                {{ $t('accounting.total_equity') }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(reportData.equity.total) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Total Liabilities and Equity -->
        <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-3">
          <div class="flex justify-between items-center font-bold text-lg">
            <span class="text-gray-900 dark:text-white">
              {{ $t('accounting.total_liabilities_equity') }}
            </span>
            <span class="text-gray-900 dark:text-white font-mono">
              {{ formatCurrency(reportData.totals.total_liabilities_equity) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Balance Check -->
    <div class="mt-8 p-4 rounded-lg" :class="reportData.totals.is_balanced ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <CheckCircleIcon v-if="reportData.totals.is_balanced" class="h-5 w-5 text-green-400" />
          <ExclamationTriangleIcon v-else class="h-5 w-5 text-red-400" />
          <p class="ml-2 text-sm" :class="reportData.totals.is_balanced ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'">
            {{ reportData.totals.is_balanced ? $t('accounting.balance_sheet_balanced') : $t('accounting.balance_sheet_unbalanced') }}
          </p>
        </div>
        <div v-if="!reportData.totals.is_balanced" class="text-sm font-medium" :class="'text-red-800 dark:text-red-200'">
          {{ $t('accounting.difference') }}: {{ formatCurrency(Math.abs(reportData.totals.difference)) }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useNumberFormatter } from '@/composables/useNumberFormatter'

interface Props {
  reportData: {
    as_of_date: string
    assets: {
      current_assets: Array<{
        code: string
        name: string
        type: string
        subtype: string
        balance: number
      }>
      fixed_assets: Array<{
        code: string
        name: string
        type: string
        subtype: string
        balance: number
      }>
      total: number
    }
    liabilities: {
      current_liabilities: Array<{
        code: string
        name: string
        type: string
        subtype: string
        balance: number
      }>
      long_term_liabilities: Array<{
        code: string
        name: string
        type: string
        subtype: string
        balance: number
      }>
      total: number
    }
    equity: {
      accounts: Array<{
        code: string
        name: string
        type: string
        subtype: string
        balance: number
      }>
      total: number
    }
    totals: {
      total_assets: number
      total_liabilities_equity: number
      difference: number
      is_balanced: boolean
    }
  }
}

defineProps<Props>()

const { formatCurrency } = useNumberFormatter()
</script>