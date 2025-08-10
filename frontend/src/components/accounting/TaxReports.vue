<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0"
      >
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t("accounting.tax_reports") }}
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $t("accounting.tax_reports_description") }}
          </p>
        </div>
        <div class="flex space-x-3">
          <button
            @click="showTaxReportModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            {{ $t("accounting.generate_tax_report") }}
          </button>
        </div>
      </div>
    </div>

    <!-- Tax Report Types -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="reportType in taxReportTypes"
        :key="reportType.type"
        @click="generateTaxReport(reportType.type)"
        class="cursor-pointer bg-white dark:bg-gray-800 shadow rounded-lg p-6 hover:shadow-md transition-shadow border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600"
      >
        <div class="flex items-center">
          <component
            :is="reportType.icon"
            class="h-8 w-8 text-blue-600 dark:text-blue-400"
          />
          <div class="ml-4">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white">
              {{ $t(reportType.title) }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t(reportType.description) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Generated Tax Reports -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h4 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t("accounting.generated_tax_reports") }}
          </h4>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("common.total") }}: {{ taxReports.length }}
            </span>
          </div>
        </div>
      </div>

      <div v-if="loading" class="p-6 text-center">
        <div class="inline-flex items-center">
          <svg
            class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          {{ $t("common.loading") }}
        </div>
      </div>

      <div v-else-if="taxReports.length === 0" class="p-12 text-center">
        <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("accounting.no_tax_reports") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("accounting.no_tax_reports_description") }}
        </p>
        <div class="mt-6">
          <button
            @click="showTaxReportModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            {{ $t("accounting.generate_first_tax_report") }}
          </button>
        </div>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.report_type") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.period") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.total_sales") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.total_tax") }}
              </th>
              <th
                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.status") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.actions") }}
              </th>
            </tr>
          </thead>
          <tbody
            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
          >
            <tr v-for="report in taxReports" :key="report.id">
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white capitalize"
              >
                {{ $t(`accounting.${report.report_type}`) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ formatDate(report.period_start) }} -
                {{ formatDate(report.period_end) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
              >
                {{ formatCurrency(report.total_sales) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
              >
                {{ formatCurrency(report.total_tax) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    report.status === 'approved'
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                      : report.status === 'submitted'
                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                  ]"
                >
                  {{ $t(`accounting.${report.status}`) }}
                </span>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
              >
                <div class="flex items-center justify-end space-x-2">
                  <button
                    @click="viewTaxReport(report)"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                  >
                    {{ $t("common.view") }}
                  </button>
                  <button
                    @click="downloadTaxReport(report)"
                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                  >
                    {{ $t("common.download") }}
                  </button>
                  <button
                    v-if="report.status === 'draft'"
                    @click="submitTaxReport(report)"
                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                  >
                    {{ $t("accounting.submit") }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tax Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CurrencyDollarIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ $t("accounting.total_tax_collected") }}
            </p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ formatCurrency(taxSummary.totalCollected) }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <DocumentTextIcon class="h-8 w-8 text-blue-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ $t("accounting.reports_submitted") }}
            </p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ taxSummary.reportsSubmitted }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ClockIcon class="h-8 w-8 text-yellow-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ $t("accounting.pending_reports") }}
            </p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ taxSummary.pendingReports }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              {{ $t("accounting.approved_reports") }}
            </p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ taxSummary.approvedReports }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tax Report Modal -->
    <TaxReportModal
      v-if="showTaxReportModal"
      @close="showTaxReportModal = false"
      @generated="handleTaxReportGenerated"
    />

    <!-- Tax Report Details Modal -->
    <TaxReportDetailsModal
      v-if="showTaxReportDetailsModal"
      :report="viewingTaxReport"
      @close="showTaxReportDetailsModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import {
  PlusIcon,
  DocumentTextIcon,
  CurrencyDollarIcon,
  ClockIcon,
  CheckCircleIcon,
  CalculatorIcon,
  DocumentChartBarIcon,
  BanknotesIcon,
} from "@heroicons/vue/24/outline";
import { useLocale } from "@/composables/useLocale";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { TaxReport } from "@/stores/accounting";
import TaxReportModal from "./TaxReportModal.vue";
import TaxReportDetailsModal from "./TaxReportDetailsModal.vue";

const { formatDate } = useLocale();
const { formatCurrency } = useNumberFormatter();

const loading = ref(false);
const showTaxReportModal = ref(false);
const showTaxReportDetailsModal = ref(false);
const viewingTaxReport = ref<TaxReport | null>(null);

// Mock data - in real implementation, this would come from the API
const taxReports = ref<TaxReport[]>([
  {
    id: 1,
    report_type: "vat_return",
    period_start: "2024-01-01",
    period_end: "2024-03-31",
    total_sales: 150000,
    total_tax: 15000,
    status: "approved",
    generated_at: "2024-04-01T10:00:00Z",
    data: {},
  },
  {
    id: 2,
    report_type: "sales_tax",
    period_start: "2024-04-01",
    period_end: "2024-06-30",
    total_sales: 180000,
    total_tax: 18000,
    status: "submitted",
    generated_at: "2024-07-01T10:00:00Z",
    data: {},
  },
  {
    id: 3,
    report_type: "income_tax",
    period_start: "2024-01-01",
    period_end: "2024-12-31",
    total_sales: 500000,
    total_tax: 75000,
    status: "draft",
    generated_at: "2024-12-31T10:00:00Z",
    data: {},
  },
]);

const taxReportTypes = [
  {
    type: "vat_return",
    title: "accounting.vat_return",
    description: "accounting.vat_return_description",
    icon: CalculatorIcon,
  },
  {
    type: "sales_tax",
    title: "accounting.sales_tax_report",
    description: "accounting.sales_tax_description",
    icon: DocumentChartBarIcon,
  },
  {
    type: "income_tax",
    title: "accounting.income_tax_report",
    description: "accounting.income_tax_description",
    icon: BanknotesIcon,
  },
];

const taxSummary = computed(() => {
  return {
    totalCollected: taxReports.value.reduce(
      (sum, report) => sum + report.total_tax,
      0,
    ),
    reportsSubmitted: taxReports.value.filter(
      (r) => r.status === "submitted" || r.status === "approved",
    ).length,
    pendingReports: taxReports.value.filter((r) => r.status === "draft").length,
    approvedReports: taxReports.value.filter((r) => r.status === "approved")
      .length,
  };
});

const generateTaxReport = async (reportType: string) => {
  loading.value = true;
  try {
    // In real implementation, this would call an API
    console.log(`Generating ${reportType} tax report`);
    await new Promise((resolve) => setTimeout(resolve, 1000));
  } catch (error) {
    console.error("Failed to generate tax report:", error);
  } finally {
    loading.value = false;
  }
};

const viewTaxReport = (report: TaxReport) => {
  viewingTaxReport.value = report;
  showTaxReportDetailsModal.value = true;
};

const downloadTaxReport = async (report: TaxReport) => {
  try {
    // In real implementation, this would download the report
    console.log(`Downloading tax report ${report.id}`);
  } catch (error) {
    console.error("Failed to download tax report:", error);
  }
};

const submitTaxReport = async (report: TaxReport) => {
  try {
    // In real implementation, this would submit the report
    console.log(`Submitting tax report ${report.id}`);
    report.status = "submitted";
  } catch (error) {
    console.error("Failed to submit tax report:", error);
  }
};

const handleTaxReportGenerated = (report: TaxReport) => {
  taxReports.value.unshift(report);
  showTaxReportModal.value = false;
};

onMounted(() => {
  // Load tax reports
});
</script>
