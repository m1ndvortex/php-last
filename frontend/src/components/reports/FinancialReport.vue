<template>
  <div class="financial-report">
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
    <div
      v-if="report.summary"
      class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6"
    >
      <div
        v-for="(item, key) in report.summary"
        :key="key"
        class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg"
      >
        <p class="text-sm font-medium text-purple-600 dark:text-purple-400">
          {{ item.label || formatLabel(key) }}
        </p>
        <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
          {{ item.formatted || formatValue(item.value) }}
        </p>
        <p
          v-if="item.change !== undefined"
          :class="getChangeClass(item.change)"
        >
          {{ item.change > 0 ? "+" : "" }}{{ item.change.toFixed(1) }}% vs
          previous period
        </p>
        <p
          v-if="item.margin !== undefined"
          class="text-xs text-purple-600 dark:text-purple-400 mt-1"
        >
          Margin: {{ item.margin.toFixed(1) }}%
        </p>
      </div>
    </div>

    <!-- Financial Ratios -->
    <div
      v-if="report.ratios"
      class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6"
    >
      <div
        v-for="(ratio, key) in report.ratios"
        :key="key"
        class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg"
      >
        <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
          {{ ratio.label || formatLabel(key) }}
        </p>
        <p class="text-xl font-bold text-indigo-900 dark:text-indigo-100">
          {{ ratio.formatted || formatValue(ratio.value) }}
        </p>
      </div>
    </div>

    <!-- Charts -->
    <div
      v-if="report.charts"
      class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6"
    >
      <div
        v-for="(chart, chartKey) in report.charts"
        :key="chartKey"
        class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg"
      >
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ chart.title }}
        </h4>
        <ChartComponent
          :type="chart.type"
          :data="chart.datasets || chart.data || []"
          :title="chart.title"
        />
      </div>
    </div>

    <!-- Financial Data Tables -->
    <div v-if="report.data" class="space-y-6">
      <!-- Profit & Loss Statement -->
      <div v-if="report.subtype === 'profit_loss' && report.data.calculations">
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t("reports.profit_loss_breakdown") }}
        </h4>
        <div
          class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
        >
          <div class="p-6">
            <div class="space-y-4">
              <div
                class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-2"
              >
                <span class="font-medium text-gray-900 dark:text-white">{{
                  $t("reports.revenue")
                }}</span>
                <span class="font-medium text-gray-900 dark:text-white">{{
                  formatCurrency(report.data.revenue?.total || 0)
                }}</span>
              </div>
              <div class="flex justify-between items-center pl-4">
                <span class="text-gray-600 dark:text-gray-400">{{
                  $t("reports.cost_of_goods_sold")
                }}</span>
                <span class="text-gray-600 dark:text-gray-400">{{
                  formatCurrency(report.data.cost_of_goods_sold?.total || 0)
                }}</span>
              </div>
              <div
                class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-2 font-medium"
              >
                <span class="text-gray-900 dark:text-white">{{
                  $t("reports.gross_profit")
                }}</span>
                <span class="text-gray-900 dark:text-white">{{
                  formatCurrency(report.data.calculations.gross_profit)
                }}</span>
              </div>
              <div class="flex justify-between items-center pl-4">
                <span class="text-gray-600 dark:text-gray-400">{{
                  $t("reports.operating_expenses")
                }}</span>
                <span class="text-gray-600 dark:text-gray-400">{{
                  formatCurrency(report.data.operating_expenses?.total || 0)
                }}</span>
              </div>
              <div
                class="flex justify-between items-center border-t-2 border-gray-300 dark:border-gray-600 pt-2 font-bold text-lg"
              >
                <span class="text-gray-900 dark:text-white">{{
                  $t("reports.net_profit")
                }}</span>
                <span
                  :class="
                    report.data.calculations.net_profit >= 0
                      ? 'text-green-600 dark:text-green-400'
                      : 'text-red-600 dark:text-red-400'
                  "
                >
                  {{ formatCurrency(report.data.calculations.net_profit) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Balance Sheet -->
      <div v-if="report.subtype === 'balance_sheet' && report.data.assets">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Assets -->
          <div>
            <h4
              class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4"
            >
              {{ $t("reports.assets") }}
            </h4>
            <div
              class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
            >
              <div class="p-6">
                <div class="space-y-3">
                  <div
                    class="font-medium text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2"
                  >
                    {{ $t("reports.current_assets") }}
                  </div>
                  <div
                    v-for="asset in report.data.assets.current_assets.accounts"
                    :key="asset.id"
                    class="flex justify-between pl-4"
                  >
                    <span class="text-gray-600 dark:text-gray-400">{{
                      asset.name
                    }}</span>
                    <span class="text-gray-600 dark:text-gray-400">{{
                      formatCurrency(asset.balance)
                    }}</span>
                  </div>
                  <div
                    class="flex justify-between font-medium border-b border-gray-200 dark:border-gray-700 pb-2"
                  >
                    <span class="text-gray-900 dark:text-white">{{
                      $t("reports.total_current_assets")
                    }}</span>
                    <span class="text-gray-900 dark:text-white">{{
                      formatCurrency(report.data.assets.current_assets.total)
                    }}</span>
                  </div>

                  <div
                    class="font-medium text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 pt-4"
                  >
                    {{ $t("reports.fixed_assets") }}
                  </div>
                  <div
                    v-for="asset in report.data.assets.fixed_assets.accounts"
                    :key="asset.id"
                    class="flex justify-between pl-4"
                  >
                    <span class="text-gray-600 dark:text-gray-400">{{
                      asset.name
                    }}</span>
                    <span class="text-gray-600 dark:text-gray-400">{{
                      formatCurrency(asset.balance)
                    }}</span>
                  </div>
                  <div
                    class="flex justify-between font-medium border-b border-gray-200 dark:border-gray-700 pb-2"
                  >
                    <span class="text-gray-900 dark:text-white">{{
                      $t("reports.total_fixed_assets")
                    }}</span>
                    <span class="text-gray-900 dark:text-white">{{
                      formatCurrency(report.data.assets.fixed_assets.total)
                    }}</span>
                  </div>

                  <div
                    class="flex justify-between font-bold text-lg border-t-2 border-gray-300 dark:border-gray-600 pt-2"
                  >
                    <span class="text-gray-900 dark:text-white">{{
                      $t("reports.total_assets")
                    }}</span>
                    <span class="text-gray-900 dark:text-white">{{
                      formatCurrency(report.data.assets.total)
                    }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Liabilities & Equity -->
          <div>
            <h4
              class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4"
            >
              {{ $t("reports.liabilities_equity") }}
            </h4>
            <div
              class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
            >
              <div class="p-6">
                <div class="space-y-3">
                  <div
                    class="font-medium text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2"
                  >
                    {{ $t("reports.liabilities") }}
                  </div>
                  <div
                    v-for="liability in report.data.liabilities
                      .current_liabilities.accounts"
                    :key="liability.id"
                    class="flex justify-between pl-4"
                  >
                    <span class="text-gray-600 dark:text-gray-400">{{
                      liability.name
                    }}</span>
                    <span class="text-gray-600 dark:text-gray-400">{{
                      formatCurrency(liability.balance)
                    }}</span>
                  </div>
                  <div
                    class="flex justify-between font-medium border-b border-gray-200 dark:border-gray-700 pb-2"
                  >
                    <span class="text-gray-900 dark:text-white">{{
                      $t("reports.total_liabilities")
                    }}</span>
                    <span class="text-gray-900 dark:text-white">{{
                      formatCurrency(report.data.liabilities.total)
                    }}</span>
                  </div>

                  <div
                    class="font-medium text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2 pt-4"
                  >
                    {{ $t("reports.equity") }}
                  </div>
                  <div
                    v-for="equity in report.data.equity.accounts"
                    :key="equity.id"
                    class="flex justify-between pl-4"
                  >
                    <span class="text-gray-600 dark:text-gray-400">{{
                      equity.name
                    }}</span>
                    <span class="text-gray-600 dark:text-gray-400">{{
                      formatCurrency(equity.balance)
                    }}</span>
                  </div>
                  <div
                    v-if="report.data.equity.current_period_profit"
                    class="flex justify-between pl-4"
                  >
                    <span class="text-gray-600 dark:text-gray-400">{{
                      $t("reports.current_period_profit")
                    }}</span>
                    <span class="text-gray-600 dark:text-gray-400">{{
                      formatCurrency(report.data.equity.current_period_profit)
                    }}</span>
                  </div>
                  <div
                    class="flex justify-between font-medium border-b border-gray-200 dark:border-gray-700 pb-2"
                  >
                    <span class="text-gray-900 dark:text-white">{{
                      $t("reports.total_equity")
                    }}</span>
                    <span class="text-gray-900 dark:text-white">{{
                      formatCurrency(report.data.equity.total)
                    }}</span>
                  </div>

                  <div
                    class="flex justify-between font-bold text-lg border-t-2 border-gray-300 dark:border-gray-600 pt-2"
                  >
                    <span class="text-gray-900 dark:text-white">{{
                      $t("reports.total_liabilities_equity")
                    }}</span>
                    <span class="text-gray-900 dark:text-white">{{
                      formatCurrency(
                        (report.data.liabilities.total || 0) +
                          (report.data.equity.total || 0),
                      )
                    }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Trial Balance -->
      <div v-if="report.subtype === 'trial_balance' && report.data.accounts">
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t("reports.trial_balance_accounts") }}
        </h4>
        <div class="overflow-x-auto">
          <table
            class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
          >
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.account_code") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.account_name") }}
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.debit") }}
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.credit") }}
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.balance") }}
                </th>
              </tr>
            </thead>
            <tbody
              class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
            >
              <tr
                v-for="account in report.data.accounts"
                :key="account.account_code"
              >
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                >
                  {{ account.account_code }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                >
                  {{ account.account_name }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                >
                  {{
                    account.balance_type === "debit"
                      ? formatCurrency(account.balance)
                      : "-"
                  }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                >
                  {{
                    account.balance_type === "credit"
                      ? formatCurrency(account.balance)
                      : "-"
                  }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white"
                >
                  {{ formatCurrency(account.balance) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-700">
              <tr class="font-bold">
                <td
                  colspan="2"
                  class="px-6 py-4 text-sm text-gray-900 dark:text-white"
                >
                  {{ $t("reports.totals") }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                >
                  {{
                    formatCurrency(report.data.totals?.debit_balance_total || 0)
                  }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                >
                  {{
                    formatCurrency(
                      report.data.totals?.credit_balance_total || 0,
                    )
                  }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                >
                  -
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Monthly Breakdown -->
      <div
        v-if="
          report.data.monthly_breakdown &&
          report.data.monthly_breakdown.length > 0
        "
      >
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t("reports.monthly_breakdown") }}
        </h4>
        <div class="overflow-x-auto">
          <table
            class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
          >
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.month") }}
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.revenue") }}
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.gross_profit") }}
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.net_profit") }}
                </th>
              </tr>
            </thead>
            <tbody
              class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
            >
              <tr
                v-for="month in report.data.monthly_breakdown"
                :key="month.month"
              >
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                >
                  {{ month.month_name || month.month }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                >
                  {{ formatCurrency(month.revenue) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                >
                  {{ formatCurrency(month.gross_profit) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right"
                  :class="
                    month.net_profit >= 0
                      ? 'text-green-600 dark:text-green-400'
                      : 'text-red-600 dark:text-red-400'
                  "
                >
                  {{ formatCurrency(month.net_profit) }}
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
import { useI18n } from "vue-i18n";
import ChartComponent from "@/components/ui/ChartComponent.vue";

const { t } = useI18n();

interface Props {
  report: any;
}

defineProps<Props>();

const formatLabel = (key: string | number): string => {
  return String(key)
    .replace(/_/g, " ")
    .replace(/\b\w/g, (l: string) => l.toUpperCase());
};

const formatValue = (value: any): string => {
  if (typeof value === "number") {
    return value.toLocaleString();
  }
  return String(value);
};

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
};

const formatDateRange = (dateRange: { start: string; end: string }): string => {
  const start = new Date(dateRange.start).toLocaleDateString();
  const end = new Date(dateRange.end).toLocaleDateString();
  return `${start} - ${end}`;
};

const getChangeClass = (change: number): string => {
  const baseClass = "text-xs font-medium";
  if (change > 0) {
    return `${baseClass} text-green-600 dark:text-green-400`;
  } else if (change < 0) {
    return `${baseClass} text-red-600 dark:text-red-400`;
  }
  return `${baseClass} text-gray-600 dark:text-gray-400`;
};
</script>
