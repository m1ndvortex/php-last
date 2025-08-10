<template>
  <div class="customer-report">
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
      class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6"
    >
      <div
        v-for="(item, key) in report.summary"
        :key="key"
        class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-lg"
      >
        <p class="text-sm font-medium text-teal-600 dark:text-teal-400">
          {{ item.label || formatLabel(key) }}
        </p>
        <p class="text-2xl font-bold text-teal-900 dark:text-teal-100">
          {{ item.formatted || formatValue(item.value) }}
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

    <!-- Data Tables -->
    <div v-if="report.data" class="space-y-6">
      <!-- Customer Aging -->
      <div
        v-if="
          report.data.customer_aging && report.data.customer_aging.length > 0
        "
      >
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t("reports.customer_aging_details") }}
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
                  {{ $t("reports.customer") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.contact") }}
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.total_overdue") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.days_overdue") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.aging_bucket") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.overdue_invoices") }}
                </th>
              </tr>
            </thead>
            <tbody
              class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
            >
              <tr
                v-for="customer in report.data.customer_aging"
                :key="customer.customer_id"
              >
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                >
                  {{ customer.customer_name }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                >
                  {{ customer.customer_phone || customer.customer_email }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-red-600 dark:text-red-400"
                >
                  {{ formatCurrency(customer.total_overdue) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white"
                >
                  {{ customer.days_overdue }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span :class="getAgingBucketClass(customer.aging_bucket)">
                    {{ customer.aging_bucket }}
                  </span>
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white"
                >
                  {{ customer.overdue_invoices_count }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Customer Purchase History -->
      <div
        v-if="
          report.data.customer_purchases &&
          report.data.customer_purchases.length > 0
        "
      >
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t("reports.customer_purchase_history") }}
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
                  {{ $t("reports.customer") }}
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.total_purchases") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.total_invoices") }}
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.average_order_value") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.last_purchase") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.frequency") }}
                </th>
              </tr>
            </thead>
            <tbody
              class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
            >
              <tr
                v-for="customer in report.data.customer_purchases"
                :key="customer.customer_id"
              >
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                >
                  {{ customer.customer_name }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white"
                >
                  {{ formatCurrency(customer.total_purchases) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white"
                >
                  {{ customer.total_invoices }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                >
                  {{ formatCurrency(customer.average_order_value) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white"
                >
                  {{ formatDate(customer.last_purchase_date) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white"
                >
                  {{ customer.purchase_frequency?.toFixed(1) }}/month
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Communication Log -->
      <div
        v-if="
          report.data.all_communications &&
          report.data.all_communications.length > 0
        "
      >
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t("reports.recent_communications") }}
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
                  {{ $t("reports.date") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.customer") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.type") }}
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.subject") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.status") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.sent_at") }}
                </th>
              </tr>
            </thead>
            <tbody
              class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
            >
              <tr
                v-for="communication in report.data.all_communications"
                :key="communication.id"
              >
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                >
                  {{ formatDate(communication.date) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                >
                  {{ communication.customer_name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span :class="getCommunicationTypeClass(communication.type)">
                    {{ communication.type.toUpperCase() }}
                  </span>
                </td>
                <td
                  class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-xs truncate"
                >
                  {{ communication.subject }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span
                    :class="getCommunicationStatusClass(communication.status)"
                  >
                    {{ communication.status }}
                  </span>
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white"
                >
                  {{
                    communication.sent_at
                      ? formatDate(communication.sent_at)
                      : "-"
                  }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Customer Analytics -->
      <div
        v-if="
          report.data.customer_analytics &&
          report.data.customer_analytics.length > 0
        "
      >
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t("reports.customer_analytics") }}
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
                  {{ $t("reports.customer") }}
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.lifetime_value") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.rfm_score") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.segment") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.recency_days") }}
                </th>
                <th
                  class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                >
                  {{ $t("reports.frequency") }}
                </th>
              </tr>
            </thead>
            <tbody
              class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
            >
              <tr
                v-for="customer in report.data.customer_analytics"
                :key="customer.customer_id"
              >
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                >
                  {{ customer.customer_name }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white"
                >
                  {{ formatCurrency(customer.lifetime_value) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white"
                >
                  {{ customer.rfm_score?.total || "-" }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span
                    :class="getCustomerSegmentClass(customer.customer_segment)"
                  >
                    {{ customer.customer_segment }}
                  </span>
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white"
                >
                  {{ customer.recency_days }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white"
                >
                  {{ customer.frequency_score }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Customer Segments -->
      <div
        v-if="
          report.data.segment_analysis &&
          report.data.segment_analysis.length > 0
        "
      >
        <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-4">
          {{ $t("reports.customer_segment_analysis") }}
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            v-for="segment in report.data.segment_analysis"
            :key="segment.segment"
            class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4"
          >
            <h5 class="font-medium text-gray-900 dark:text-white mb-2">
              {{ segment.segment }}
            </h5>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400"
                  >{{ $t("reports.customers") }}:</span
                >
                <span class="font-medium text-gray-900 dark:text-white">{{
                  segment.customer_count
                }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400"
                  >{{ $t("reports.total_value") }}:</span
                >
                <span class="font-medium text-gray-900 dark:text-white">{{
                  formatCurrency(segment.total_value)
                }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400"
                  >{{ $t("reports.avg_value") }}:</span
                >
                <span class="font-medium text-gray-900 dark:text-white">{{
                  formatCurrency(segment.average_value)
                }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400"
                  >{{ $t("reports.percentage") }}:</span
                >
                <span class="font-medium text-gray-900 dark:text-white"
                  >{{ segment.percentage.toFixed(1) }}%</span
                >
              </div>
            </div>
          </div>
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

const formatDate = (dateString: string): string => {
  if (!dateString) return "-";
  return new Date(dateString).toLocaleDateString();
};

const formatDateRange = (dateRange: { start: string; end: string }): string => {
  const start = new Date(dateRange.start).toLocaleDateString();
  const end = new Date(dateRange.end).toLocaleDateString();
  return `${start} - ${end}`;
};

const getAgingBucketClass = (bucket: string): string => {
  const baseClass = "px-2 py-1 text-xs font-medium rounded-full";
  switch (bucket.toLowerCase()) {
    case "current":
      return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`;
    case "1-30 days":
      return `${baseClass} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200`;
    case "31-60 days":
      return `${baseClass} bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200`;
    case "61-90 days":
      return `${baseClass} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`;
    case "90+ days":
      return `${baseClass} bg-red-200 text-red-900 dark:bg-red-800 dark:text-red-100`;
    default:
      return `${baseClass} bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200`;
  }
};

const getCommunicationTypeClass = (type: string): string => {
  const baseClass = "px-2 py-1 text-xs font-medium rounded-full";
  switch (type.toLowerCase()) {
    case "email":
      return `${baseClass} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200`;
    case "sms":
      return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`;
    case "whatsapp":
      return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`;
    case "phone":
      return `${baseClass} bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200`;
    default:
      return `${baseClass} bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200`;
  }
};

const getCommunicationStatusClass = (status: string): string => {
  const baseClass = "px-2 py-1 text-xs font-medium rounded-full";
  switch (status.toLowerCase()) {
    case "sent":
      return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`;
    case "delivered":
      return `${baseClass} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200`;
    case "opened":
      return `${baseClass} bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200`;
    case "failed":
      return `${baseClass} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`;
    case "pending":
      return `${baseClass} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200`;
    default:
      return `${baseClass} bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200`;
  }
};

const getCustomerSegmentClass = (segment: string): string => {
  const baseClass = "px-2 py-1 text-xs font-medium rounded-full";
  switch (segment.toLowerCase()) {
    case "champions":
      return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`;
    case "loyal customers":
      return `${baseClass} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200`;
    case "potential loyalists":
      return `${baseClass} bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200`;
    case "at risk":
      return `${baseClass} bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200`;
    case "lost customers":
      return `${baseClass} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`;
    default:
      return `${baseClass} bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200`;
  }
};
</script>
