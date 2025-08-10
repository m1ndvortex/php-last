<template>
  <div class="p-6">
    <div class="max-w-4xl mx-auto">
      <!-- Revenue Section -->
      <div class="mb-8">
        <h3
          class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2"
        >
          {{ $t("accounting.revenue") }}
        </h3>

        <!-- Operating Revenue -->
        <div class="mb-4">
          <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">
            {{ $t("accounting.operating_revenue") }}
          </h4>
          <div class="space-y-2">
            <div
              v-for="revenue in reportData.revenue.operating_revenue"
              :key="revenue.code"
              class="flex justify-between items-center py-1"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
                {{ revenue.name }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(revenue.balance) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Other Revenue -->
        <div v-if="reportData.revenue.other_revenue.length > 0" class="mb-4">
          <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">
            {{ $t("accounting.other_revenue") }}
          </h4>
          <div class="space-y-2">
            <div
              v-for="revenue in reportData.revenue.other_revenue"
              :key="revenue.code"
              class="flex justify-between items-center py-1"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
                {{ revenue.name }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(revenue.balance) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Total Revenue -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <div class="flex justify-between items-center font-bold">
            <span class="text-gray-900 dark:text-white">
              {{ $t("accounting.total_revenue") }}
            </span>
            <span class="text-gray-900 dark:text-white font-mono">
              {{ formatCurrency(reportData.revenue.total) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Expenses Section -->
      <div class="mb-8">
        <h3
          class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2"
        >
          {{ $t("accounting.expenses") }}
        </h3>

        <!-- Operating Expenses -->
        <div class="mb-4">
          <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">
            {{ $t("accounting.operating_expenses") }}
          </h4>
          <div class="space-y-2">
            <div
              v-for="expense in reportData.expenses.operating_expenses"
              :key="expense.code"
              class="flex justify-between items-center py-1"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
                {{ expense.name }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(expense.balance) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Other Expenses -->
        <div v-if="reportData.expenses.other_expenses.length > 0" class="mb-4">
          <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3">
            {{ $t("accounting.other_expenses") }}
          </h4>
          <div class="space-y-2">
            <div
              v-for="expense in reportData.expenses.other_expenses"
              :key="expense.code"
              class="flex justify-between items-center py-1"
            >
              <span class="text-sm text-gray-700 dark:text-gray-300 pl-4">
                {{ expense.name }}
              </span>
              <span class="text-sm text-gray-900 dark:text-white font-mono">
                {{ formatCurrency(expense.balance) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Total Expenses -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
          <div class="flex justify-between items-center font-bold">
            <span class="text-gray-900 dark:text-white">
              {{ $t("accounting.total_expenses") }}
            </span>
            <span class="text-gray-900 dark:text-white font-mono">
              {{ formatCurrency(reportData.expenses.total) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Net Income Section -->
      <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-6">
        <div class="flex justify-between items-center text-xl font-bold mb-4">
          <span class="text-gray-900 dark:text-white">
            {{ $t("accounting.net_income") }}
          </span>
          <span
            :class="[
              'font-mono',
              reportData.net_income >= 0
                ? 'text-green-600 dark:text-green-400'
                : 'text-red-600 dark:text-red-400',
            ]"
          >
            {{ formatCurrency(reportData.net_income) }}
          </span>
        </div>

        <!-- Gross Margin -->
        <div
          class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400"
        >
          <span>{{ $t("accounting.gross_margin") }}</span>
          <span class="font-mono">{{
            formatPercentage(reportData.gross_margin)
          }}</span>
        </div>
      </div>

      <!-- Key Metrics -->
      <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
          <div class="text-sm font-medium text-blue-800 dark:text-blue-200">
            {{ $t("accounting.total_revenue") }}
          </div>
          <div
            class="text-2xl font-bold text-blue-900 dark:text-blue-100 font-mono"
          >
            {{ formatCurrency(reportData.revenue.total) }}
          </div>
        </div>

        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
          <div class="text-sm font-medium text-red-800 dark:text-red-200">
            {{ $t("accounting.total_expenses") }}
          </div>
          <div
            class="text-2xl font-bold text-red-900 dark:text-red-100 font-mono"
          >
            {{ formatCurrency(reportData.expenses.total) }}
          </div>
        </div>

        <div
          :class="[
            'rounded-lg p-4',
            reportData.net_income >= 0
              ? 'bg-green-50 dark:bg-green-900/20'
              : 'bg-red-50 dark:bg-red-900/20',
          ]"
        >
          <div
            :class="[
              'text-sm font-medium',
              reportData.net_income >= 0
                ? 'text-green-800 dark:text-green-200'
                : 'text-red-800 dark:text-red-200',
            ]"
          >
            {{ $t("accounting.net_income") }}
          </div>
          <div
            :class="[
              'text-2xl font-bold font-mono',
              reportData.net_income >= 0
                ? 'text-green-900 dark:text-green-100'
                : 'text-red-900 dark:text-red-100',
            ]"
          >
            {{ formatCurrency(reportData.net_income) }}
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
    revenue: {
      operating_revenue: Array<{
        code: string;
        name: string;
        type: string;
        subtype: string;
        balance: number;
      }>;
      other_revenue: Array<{
        code: string;
        name: string;
        type: string;
        subtype: string;
        balance: number;
      }>;
      total: number;
    };
    expenses: {
      operating_expenses: Array<{
        code: string;
        name: string;
        type: string;
        subtype: string;
        balance: number;
      }>;
      other_expenses: Array<{
        code: string;
        name: string;
        type: string;
        subtype: string;
        balance: number;
      }>;
      total: number;
    };
    net_income: number;
    gross_margin: number;
  };
}

defineProps<Props>();

const { formatCurrency } = useNumberFormatter();

const formatPercentage = (value: number) => {
  return `${value.toFixed(2)}%`;
};
</script>
