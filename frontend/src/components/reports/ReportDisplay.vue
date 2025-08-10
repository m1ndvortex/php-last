<template>
  <div class="report-display">
    <!-- Report Header -->
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
      <div class="flex justify-between items-start">
        <div>
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            {{ report.title }}
          </h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ formatDateRange(report.date_range) }} •
            {{ $t("reports.generated_at") }}:
            {{ formatDateTime(report.generated_at) }}
          </p>
        </div>
        <div class="flex gap-2">
          <button
            @click="$emit('export', 'pdf')"
            class="btn btn-sm btn-secondary"
          >
            <i class="fas fa-file-pdf mr-1"></i>
            PDF
          </button>
          <button
            @click="$emit('export', 'excel')"
            class="btn btn-sm btn-secondary"
          >
            <i class="fas fa-file-excel mr-1"></i>
            Excel
          </button>
          <button
            @click="$emit('export', 'csv')"
            class="btn btn-sm btn-secondary"
          >
            <i class="fas fa-file-csv mr-1"></i>
            CSV
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-12 text-center">
      <i class="fas fa-spinner animate-spin text-3xl text-gray-400 mb-4"></i>
      <p class="text-gray-500">{{ $t("reports.loading_report") }}</p>
    </div>

    <!-- Report Content -->
    <div v-else class="p-6">
      <!-- Sales Report -->
      <SalesReport v-if="report.type === 'sales'" :report="report" />

      <!-- Inventory Report -->
      <InventoryReport
        v-else-if="report.type === 'inventory'"
        :report="report"
      />

      <!-- Financial Report -->
      <FinancialReport
        v-else-if="report.type === 'financial'"
        :report="report"
      />

      <!-- Customer Report -->
      <CustomerReport v-else-if="report.type === 'customer'" :report="report" />

      <!-- Fallback Generic Report Display -->
      <div v-else class="generic-report">
        <!-- Summary Cards -->
        <div
          v-if="report.summary"
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8"
        >
          <div
            v-for="(item, key) in report.summary"
            :key="key"
            class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 rounded-lg"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-blue-600 dark:text-blue-400">
                  {{ item.label }}
                </p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                  {{ item.formatted || formatValue(item.value, item.format) }}
                </p>
                <p
                  v-if="item.change !== undefined"
                  class="text-xs"
                  :class="getChangeClass(item.change)"
                >
                  <i :class="getChangeIcon(item.change)" class="mr-1"></i>
                  {{ Math.abs(item.change).toFixed(1) }}%
                  {{ $t("reports.vs_previous_period") }}
                </p>
              </div>
              <div class="text-blue-500 dark:text-blue-400">
                <i :class="getSummaryIcon(String(key))" class="text-2xl"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts -->
        <div
          v-if="report.charts && Object.keys(report.charts).length > 0"
          class="mb-8"
        >
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            {{ $t("reports.charts_and_analytics") }}
          </h3>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div
              v-for="(chart, key) in report.charts"
              :key="key"
              class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg"
            >
              <h4
                class="text-md font-medium text-gray-900 dark:text-white mb-3"
              >
                {{ chart.title }}
              </h4>
              <div class="h-64">
                <ChartComponent
                  :type="chart.type"
                  :data="chart"
                  :options="getChartOptions(chart.type)"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Data Tables -->
        <div v-if="report.data">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            {{ $t("reports.detailed_data") }}
          </h3>

          <!-- Dynamic table based on report type -->
          <div class="overflow-x-auto">
            <table
              class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
            >
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th
                    v-for="column in getTableColumns()"
                    :key="column.key"
                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                  >
                    {{ column.label }}
                  </th>
                </tr>
              </thead>
              <tbody
                class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
              >
                <tr
                  v-for="(row, index) in getTableData()"
                  :key="index"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <td
                    v-for="column in getTableColumns()"
                    :key="column.key"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100"
                  >
                    <span v-if="column.type === 'currency'">
                      {{ formatCurrency(row[column.key]) }}
                    </span>
                    <span v-else-if="column.type === 'date'">
                      {{ formatDate(row[column.key]) }}
                    </span>
                    <span v-else-if="column.type === 'number'">
                      {{ formatNumber(row[column.key]) }}
                    </span>
                    <span v-else-if="column.type === 'percentage'">
                      {{ formatPercentage(row[column.key]) }}
                    </span>
                    <span v-else>
                      {{ row[column.key] }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination if needed -->
          <div
            v-if="shouldShowPagination"
            class="mt-4 flex justify-between items-center"
          >
            <div class="text-sm text-gray-500">
              {{
                $t("reports.showing_results", {
                  start: (currentPage - 1) * pageSize + 1,
                  end: Math.min(currentPage * pageSize, totalRows),
                  total: totalRows,
                })
              }}
            </div>
            <div class="flex gap-2">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="btn btn-sm btn-secondary"
              >
                {{ $t("common.previous") }}
              </button>
              <button
                @click="nextPage"
                :disabled="currentPage * pageSize >= totalRows"
                class="btn btn-sm btn-secondary"
              >
                {{ $t("common.next") }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Report Notes/Footer -->
      <div
        v-if="report.notes"
        class="mt-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg"
      >
        <h4
          class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2"
        >
          {{ $t("reports.notes") }}
        </h4>
        <p class="text-sm text-yellow-700 dark:text-yellow-300">
          {{ report.notes }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import ChartComponent from "@/components/ui/ChartComponent.vue";
import SalesReport from "./SalesReport.vue";
import InventoryReport from "./InventoryReport.vue";
import FinancialReport from "./FinancialReport.vue";
import CustomerReport from "./CustomerReport.vue";

const { t, locale } = useI18n();

// Props
const props = defineProps<{
  report: any;
  loading?: boolean;
}>();

// Emits
defineEmits<{
  export: [format: string];
}>();

// State
const currentPage = ref(1);
const pageSize = ref(50);

// Computed
const totalRows = computed(() => {
  const data = getTableData();
  return Array.isArray(data) ? data.length : 0;
});

const shouldShowPagination = computed(() => {
  return totalRows.value > pageSize.value;
});

// Methods
const formatDateRange = (dateRange: any) => {
  if (!dateRange) return "";
  const start = new Date(dateRange.start).toLocaleDateString(locale.value);
  const end = new Date(dateRange.end).toLocaleDateString(locale.value);
  return `${start} - ${end}`;
};

const formatDateTime = (dateTime: string) => {
  return new Date(dateTime).toLocaleString(locale.value);
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString(locale.value);
};

const formatCurrency = (amount: number) => {
  if (amount === null || amount === undefined) {
    return t("inventory.price_on_request");
  }
  return new Intl.NumberFormat(locale.value, {
    style: "currency",
    currency: locale.value === "fa" ? "IRR" : "USD",
  }).format(amount);
};

const formatNumber = (number: number) => {
  return new Intl.NumberFormat(locale.value).format(number);
};

const formatPercentage = (percentage: number) => {
  return `${percentage.toFixed(1)}%`;
};

const formatValue = (value: any, format?: string) => {
  if (format === "currency") return formatCurrency(value);
  if (format === "percentage") return formatPercentage(value);
  if (format === "number") return formatNumber(value);
  return value;
};

const getChangeClass = (change: number) => {
  if (change > 0) return "text-green-600 dark:text-green-400";
  if (change < 0) return "text-red-600 dark:text-red-400";
  return "text-gray-500 dark:text-gray-400";
};

const getChangeIcon = (change: number) => {
  if (change > 0) return "fas fa-arrow-up";
  if (change < 0) return "fas fa-arrow-down";
  return "fas fa-minus";
};

const getSummaryIcon = (key: string) => {
  const icons: Record<string, string> = {
    total_sales: "fas fa-dollar-sign",
    total_revenue: "fas fa-chart-line",
    total_invoices: "fas fa-file-invoice",
    total_customers: "fas fa-users",
    total_items: "fas fa-boxes",
    total_value: "fas fa-coins",
    net_profit: "fas fa-chart-pie",
    gross_profit: "fas fa-percentage",
  };
  return icons[key] || "fas fa-chart-bar";
};

const getChartOptions = (type: string) => {
  const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: "bottom" as const,
      },
    },
  };

  if (type === "line" || type === "area") {
    return {
      ...baseOptions,
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    };
  }

  return baseOptions;
};

const getTableColumns = () => {
  // Dynamic columns based on report type and data
  if (!props.report.data) return [];

  const data = getTableData();
  if (!Array.isArray(data) || data.length === 0) return [];

  const firstRow = data[0];
  const columns: any[] = [];

  // Define column configurations for different report types
  const columnConfigs: Record<string, any> = {
    sales: {
      customer_name: { label: t("reports.customer"), type: "text" },
      invoice_number: { label: t("reports.invoice_number"), type: "text" },
      date: { label: t("reports.date"), type: "date" },
      total_amount: { label: t("reports.amount"), type: "currency" },
      status: { label: t("reports.status"), type: "text" },
    },
    inventory: {
      name: { label: t("reports.item_name"), type: "text" },
      sku: { label: t("reports.sku"), type: "text" },
      category: { label: t("reports.category"), type: "text" },
      quantity: { label: t("reports.quantity"), type: "number" },
      unit_price: { label: t("reports.unit_price"), type: "currency" },
      total_value: { label: t("reports.total_value"), type: "currency" },
    },
    financial: {
      account_name: { label: t("reports.account"), type: "text" },
      account_code: { label: t("reports.code"), type: "text" },
      debit_total: { label: t("reports.debit"), type: "currency" },
      credit_total: { label: t("reports.credit"), type: "currency" },
      balance: { label: t("reports.balance"), type: "currency" },
    },
    customer: {
      customer_name: { label: t("reports.customer"), type: "text" },
      total_purchases: {
        label: t("reports.total_purchases"),
        type: "currency",
      },
      total_invoices: { label: t("reports.invoice_count"), type: "number" },
      last_purchase_date: { label: t("reports.last_purchase"), type: "date" },
    },
  };

  const config = columnConfigs[props.report.type] || {};

  // Generate columns based on available data and configuration
  Object.keys(firstRow).forEach((key) => {
    if (config[key]) {
      columns.push({
        key,
        label: config[key].label,
        type: config[key].type,
      });
    } else {
      // Fallback for unknown columns
      columns.push({
        key,
        label: key.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase()),
        type: "text",
      });
    }
  });

  return columns;
};

const getTableData = () => {
  if (!props.report.data) return [];

  // Handle different data structures
  if (Array.isArray(props.report.data)) {
    return props.report.data;
  }

  // For reports with nested data structure, get the main data array
  const dataKeys = Object.keys(props.report.data);
  const mainDataKey = dataKeys.find(
    (key) =>
      Array.isArray(props.report.data[key]) &&
      props.report.data[key].length > 0,
  );

  if (mainDataKey) {
    return props.report.data[mainDataKey];
  }

  return [];
};

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const nextPage = () => {
  if (currentPage.value * pageSize.value < totalRows.value) {
    currentPage.value++;
  }
};
</script>

<style scoped>
.report-display {
  @apply bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden;
}

.btn {
  @apply px-4 py-2 rounded-lg font-semibold transition-all duration-200 shadow-sm hover:shadow-md;
}

.btn-sm {
  @apply px-3 py-1.5 text-sm;
}

.btn-secondary {
  @apply bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 hover:from-gray-200 hover:to-gray-300 dark:from-gray-700 dark:to-gray-600 dark:text-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-500 transform hover:scale-105 active:scale-95;
}

/* Enhanced header styling */
.p-6.border-b {
  @apply bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 border-b-2 border-blue-200 dark:border-gray-600;
}

/* Summary cards enhancement */
.bg-gradient-to-r.from-blue-50 {
  @apply shadow-lg border border-blue-200 dark:border-blue-800 hover:shadow-xl transition-all duration-300 transform hover:scale-105;
}

/* Chart container styling */
.bg-gray-50 {
  @apply shadow-inner border border-gray-200 dark:border-gray-600 hover:shadow-lg transition-all duration-300;
}

/* Table styling */
.min-w-full {
  @apply shadow-lg rounded-lg overflow-hidden;
}

.bg-gray-50.dark\:bg-gray-700 {
  @apply bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600;
}

thead th {
  @apply font-bold tracking-wider text-xs;
}

tbody tr {
  @apply hover:bg-blue-50 dark:hover:bg-gray-600 transition-colors duration-200;
}

tbody td {
  @apply font-medium;
}

/* Pagination styling */
.mt-4.flex {
  @apply bg-gray-50 dark:bg-gray-700 p-4 rounded-lg;
}

/* Notes section styling */
.bg-yellow-50 {
  @apply shadow-lg border-l-4 border-yellow-400 hover:shadow-xl transition-all duration-300;
}

/* Export buttons styling */
.flex.gap-2 button {
  @apply shadow-md hover:shadow-lg;
}

/* Typography enhancements */
h2 {
  @apply tracking-tight font-bold;
}

h3 {
  @apply tracking-wide font-semibold;
}

h4 {
  @apply tracking-wide font-medium;
}

/* Icon styling */
i {
  @apply drop-shadow-sm;
}

/* Loading state */
.text-3xl.text-gray-400 {
  @apply text-blue-400 dark:text-blue-300;
}

/* Professional spacing */
.p-6 {
  @apply space-y-6;
}

.mb-8 {
  @apply space-y-4;
}

/* Enhanced grid layouts */
.grid {
  @apply gap-6;
}

/* Chart height consistency */
.h-64 {
  @apply shadow-inner rounded-lg bg-white dark:bg-gray-800 p-2;
}

/* Professional color scheme */
.text-blue-600 {
  @apply text-blue-700 dark:text-blue-300;
}

.text-blue-900 {
  @apply text-blue-800 dark:text-blue-200;
}

/* Enhanced hover states */
.hover\:bg-gray-50:hover {
  @apply bg-blue-50 dark:bg-gray-600;
}
</style>
