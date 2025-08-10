<template>
  <div class="p-6">
    <div class="max-w-4xl mx-auto space-y-8">
      <!-- Operating Activities -->
      <div>
        <h3
          class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2"
        >
          {{ $t("accounting.operating_activities") }}
        </h3>

        <div class="space-y-2">
          <div class="flex justify-between items-center py-1">
            <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
              {{ $t("accounting.net_income") }}
            </span>
            <span class="text-sm text-gray-900 dark:text-white font-mono">
              {{ formatCurrency(reportData.operating_activities.net_income) }}
            </span>
          </div>

          <div class="flex justify-between items-center py-1">
            <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
              {{ $t("accounting.depreciation") }}
            </span>
            <span class="text-sm text-gray-900 dark:text-white font-mono">
              {{ formatCurrency(reportData.operating_activities.depreciation) }}
            </span>
          </div>

          <div class="flex justify-between items-center py-1">
            <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
              {{ $t("accounting.working_capital_changes") }}
            </span>
            <span class="text-sm text-gray-900 dark:text-white font-mono">
              {{
                formatCurrency(
                  reportData.operating_activities.working_capital_changes,
                )
              }}
            </span>
          </div>

          <div
            class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700 font-medium"
          >
            <span class="text-sm text-gray-800 dark:text-gray-200">
              {{ $t("accounting.net_cash_from_operating") }}
            </span>
            <span
              :class="[
                'text-sm font-mono font-bold',
                reportData.operating_activities.total >= 0
                  ? 'text-green-600 dark:text-green-400'
                  : 'text-red-600 dark:text-red-400',
              ]"
            >
              {{ formatCurrency(reportData.operating_activities.total) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Investing Activities -->
      <div>
        <h3
          class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2"
        >
          {{ $t("accounting.investing_activities") }}
        </h3>

        <div class="space-y-2">
          <div class="flex justify-between items-center py-1">
            <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
              {{ $t("accounting.asset_purchases") }}
            </span>
            <span class="text-sm text-gray-900 dark:text-white font-mono">
              {{
                formatCurrency(reportData.investing_activities.asset_purchases)
              }}
            </span>
          </div>

          <div class="flex justify-between items-center py-1">
            <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
              {{ $t("accounting.asset_sales") }}
            </span>
            <span class="text-sm text-gray-900 dark:text-white font-mono">
              {{ formatCurrency(reportData.investing_activities.asset_sales) }}
            </span>
          </div>

          <div
            class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700 font-medium"
          >
            <span class="text-sm text-gray-800 dark:text-gray-200">
              {{ $t("accounting.net_cash_from_investing") }}
            </span>
            <span
              :class="[
                'text-sm font-mono font-bold',
                reportData.investing_activities.total >= 0
                  ? 'text-green-600 dark:text-green-400'
                  : 'text-red-600 dark:text-red-400',
              ]"
            >
              {{ formatCurrency(reportData.investing_activities.total) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Financing Activities -->
      <div>
        <h3
          class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2"
        >
          {{ $t("accounting.financing_activities") }}
        </h3>

        <div class="space-y-2">
          <div class="flex justify-between items-center py-1">
            <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
              {{ $t("accounting.loan_proceeds") }}
            </span>
            <span class="text-sm text-gray-900 dark:text-white font-mono">
              {{
                formatCurrency(reportData.financing_activities.loan_proceeds)
              }}
            </span>
          </div>

          <div class="flex justify-between items-center py-1">
            <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
              {{ $t("accounting.loan_payments") }}
            </span>
            <span class="text-sm text-gray-900 dark:text-white font-mono">
              {{
                formatCurrency(reportData.financing_activities.loan_payments)
              }}
            </span>
          </div>

          <div class="flex justify-between items-center py-1">
            <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
              {{ $t("accounting.equity_transactions") }}
            </span>
            <span class="text-sm text-gray-900 dark:text-white font-mono">
              {{
                formatCurrency(
                  reportData.financing_activities.equity_transactions,
                )
              }}
            </span>
          </div>

          <div
            class="flex justify-between items-center py-2 border-t border-gray-200 dark:border-gray-700 font-medium"
          >
            <span class="text-sm text-gray-800 dark:text-gray-200">
              {{ $t("accounting.net_cash_from_financing") }}
            </span>
            <span
              :class="[
                'text-sm font-mono font-bold',
                reportData.financing_activities.total >= 0
                  ? 'text-green-600 dark:text-green-400'
                  : 'text-red-600 dark:text-red-400',
              ]"
            >
              {{ formatCurrency(reportData.financing_activities.total) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Net Cash Flow Summary -->
      <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-6">
        <div class="space-y-3">
          <div class="flex justify-between items-center">
            <span class="text-lg font-medium text-gray-900 dark:text-white">
              {{ $t("accounting.net_cash_flow") }}
            </span>
            <span
              :class="[
                'text-lg font-bold font-mono',
                reportData.net_cash_flow >= 0
                  ? 'text-green-600 dark:text-green-400'
                  : 'text-red-600 dark:text-red-400',
              ]"
            >
              {{ formatCurrency(reportData.net_cash_flow) }}
            </span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-700 dark:text-gray-300">
              {{ $t("accounting.beginning_cash") }}
            </span>
            <span class="text-sm text-gray-900 dark:text-white font-mono">
              {{ formatCurrency(reportData.beginning_cash) }}
            </span>
          </div>

          <div
            class="flex justify-between items-center border-t border-gray-200 dark:border-gray-700 pt-2"
          >
            <span class="text-lg font-bold text-gray-900 dark:text-white">
              {{ $t("accounting.ending_cash") }}
            </span>
            <span
              class="text-lg font-bold text-gray-900 dark:text-white font-mono"
            >
              {{ formatCurrency(reportData.ending_cash) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Cash Flow Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-8">
        <div
          :class="[
            'rounded-lg p-4',
            reportData.operating_activities.total >= 0
              ? 'bg-green-50 dark:bg-green-900/20'
              : 'bg-red-50 dark:bg-red-900/20',
          ]"
        >
          <div
            :class="[
              'text-sm font-medium',
              reportData.operating_activities.total >= 0
                ? 'text-green-800 dark:text-green-200'
                : 'text-red-800 dark:text-red-200',
            ]"
          >
            {{ $t("accounting.operating") }}
          </div>
          <div
            :class="[
              'text-xl font-bold font-mono',
              reportData.operating_activities.total >= 0
                ? 'text-green-900 dark:text-green-100'
                : 'text-red-900 dark:text-red-100',
            ]"
          >
            {{ formatCurrency(reportData.operating_activities.total) }}
          </div>
        </div>

        <div
          :class="[
            'rounded-lg p-4',
            reportData.investing_activities.total >= 0
              ? 'bg-green-50 dark:bg-green-900/20'
              : 'bg-red-50 dark:bg-red-900/20',
          ]"
        >
          <div
            :class="[
              'text-sm font-medium',
              reportData.investing_activities.total >= 0
                ? 'text-green-800 dark:text-green-200'
                : 'text-red-800 dark:text-red-200',
            ]"
          >
            {{ $t("accounting.investing") }}
          </div>
          <div
            :class="[
              'text-xl font-bold font-mono',
              reportData.investing_activities.total >= 0
                ? 'text-green-900 dark:text-green-100'
                : 'text-red-900 dark:text-red-100',
            ]"
          >
            {{ formatCurrency(reportData.investing_activities.total) }}
          </div>
        </div>

        <div
          :class="[
            'rounded-lg p-4',
            reportData.financing_activities.total >= 0
              ? 'bg-green-50 dark:bg-green-900/20'
              : 'bg-red-50 dark:bg-red-900/20',
          ]"
        >
          <div
            :class="[
              'text-sm font-medium',
              reportData.financing_activities.total >= 0
                ? 'text-green-800 dark:text-green-200'
                : 'text-red-800 dark:text-red-200',
            ]"
          >
            {{ $t("accounting.financing") }}
          </div>
          <div
            :class="[
              'text-xl font-bold font-mono',
              reportData.financing_activities.total >= 0
                ? 'text-green-900 dark:text-green-100'
                : 'text-red-900 dark:text-red-100',
            ]"
          >
            {{ formatCurrency(reportData.financing_activities.total) }}
          </div>
        </div>

        <div
          :class="[
            'rounded-lg p-4',
            reportData.net_cash_flow >= 0
              ? 'bg-blue-50 dark:bg-blue-900/20'
              : 'bg-red-50 dark:bg-red-900/20',
          ]"
        >
          <div
            :class="[
              'text-sm font-medium',
              reportData.net_cash_flow >= 0
                ? 'text-blue-800 dark:text-blue-200'
                : 'text-red-800 dark:text-red-200',
            ]"
          >
            {{ $t("accounting.net_change") }}
          </div>
          <div
            :class="[
              'text-xl font-bold font-mono',
              reportData.net_cash_flow >= 0
                ? 'text-blue-900 dark:text-blue-100'
                : 'text-red-900 dark:text-red-100',
            ]"
          >
            {{ formatCurrency(reportData.net_cash_flow) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useNumberFormatter } from "@/composables/useNumberFormatter";

interface Props {
  reportData: {
    period: {
      start_date: string;
      end_date: string;
    };
    operating_activities: {
      net_income: number;
      depreciation: number;
      working_capital_changes: number;
      total: number;
    };
    investing_activities: {
      asset_purchases: number;
      asset_sales: number;
      total: number;
    };
    financing_activities: {
      loan_proceeds: number;
      loan_payments: number;
      equity_transactions: number;
      total: number;
    };
    net_cash_flow: number;
    beginning_cash: number;
    ending_cash: number;
  };
}

defineProps<Props>();

const { formatCurrency } = useNumberFormatter();
</script>
