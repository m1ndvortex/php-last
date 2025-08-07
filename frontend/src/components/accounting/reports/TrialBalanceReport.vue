<template>
  <div class="p-6">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              {{ $t('accounting.account_code') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              {{ $t('accounting.account_name') }}
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              {{ $t('accounting.account_type') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              {{ $t('accounting.debit_balance') }}
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              {{ $t('accounting.credit_balance') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="account in reportData.accounts" :key="account.account_code">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">
              {{ account.account_code }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
              {{ account.account_name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white capitalize">
              {{ $t(`accounting.${account.account_type}`) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
              {{ account.debit_balance > 0 ? formatCurrency(account.debit_balance) : '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
              {{ account.credit_balance > 0 ? formatCurrency(account.credit_balance) : '-' }}
            </td>
          </tr>
        </tbody>
        <tfoot class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <td colspan="3" class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white">
              {{ $t('common.total') }}
            </td>
            <td class="px-6 py-3 text-sm font-bold text-right text-gray-900 dark:text-white">
              {{ formatCurrency(reportData.totals.total_debits) }}
            </td>
            <td class="px-6 py-3 text-sm font-bold text-right text-gray-900 dark:text-white">
              {{ formatCurrency(reportData.totals.total_credits) }}
            </td>
          </tr>
          <tr v-if="!reportData.totals.is_balanced">
            <td colspan="3" class="px-6 py-3 text-sm font-medium text-red-600 dark:text-red-400">
              {{ $t('accounting.difference') }}
            </td>
            <td colspan="2" class="px-6 py-3 text-sm font-bold text-right text-red-600 dark:text-red-400">
              {{ formatCurrency(reportData.totals.difference) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Balance Status -->
    <div class="mt-4 p-4 rounded-lg" :class="reportData.totals.is_balanced ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
      <div class="flex items-center">
        <CheckCircleIcon v-if="reportData.totals.is_balanced" class="h-5 w-5 text-green-400" />
        <ExclamationTriangleIcon v-else class="h-5 w-5 text-red-400" />
        <p class="ml-2 text-sm" :class="reportData.totals.is_balanced ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'">
          {{ reportData.totals.is_balanced ? $t('accounting.trial_balance_balanced') : $t('accounting.trial_balance_unbalanced') }}
        </p>
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
    accounts: Array<{
      account_code: string
      account_name: string
      account_type: string
      debit_balance: number
      credit_balance: number
      balance: number
    }>
    totals: {
      total_debits: number
      total_credits: number
      difference: number
      is_balanced: boolean
    }
  }
}

defineProps<Props>()

const { formatCurrency } = useNumberFormatter()
</script>