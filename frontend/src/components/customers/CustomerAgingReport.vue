<template>
  <div class="space-y-6">
    <!-- Aging Report Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t("customers.aging_report") }}
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $t("customers.aging_description") }}
          </p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="exportReport"
            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
          >
            <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
            {{ $t("common.export") }}
          </button>
          <button
            @click="refreshReport"
            :disabled="customersStore.loading.aging"
            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
          >
            <ArrowPathIcon
              :class="[
                'h-4 w-4 mr-2',
                customersStore.loading.aging && 'animate-spin',
              ]"
            />
            {{ $t("common.refresh") }}
          </button>
        </div>
      </div>

      <!-- Report Filters -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Customer Type Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("customers.customer_type") }}
          </label>
          <select
            v-model="filters.customer_type"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option value="retail">{{ $t("customers.types.retail") }}</option>
            <option value="wholesale">
              {{ $t("customers.types.wholesale") }}
            </option>
            <option value="vip">{{ $t("customers.types.vip") }}</option>
          </select>
        </div>

        <!-- Language Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("customers.preferred_language") }}
          </label>
          <select
            v-model="filters.preferred_language"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option value="en">{{ $t("languages.english") }}</option>
            <option value="fa">{{ $t("languages.persian") }}</option>
          </select>
        </div>

        <!-- As of Date -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("reports.as_of_date") }}
          </label>
          <input
            v-model="filters.as_of_date"
            type="date"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>
      </div>

      <!-- Summary Stats -->
      <div v-if="agingReport" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatCurrency(agingReport.total_outstanding) }}
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t("customers.total_outstanding") }}
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ agingReport.total_customers }}
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t("customers.customers_with_balance") }}
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ formatCurrency(averageBalance) }}
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t("customers.average_balance") }}
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="customersStore.loading.aging"
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 text-center"
    >
      <div
        class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"
      ></div>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        {{ $t("common.loading") }}
      </p>
    </div>

    <!-- Aging Report Table -->
    <div
      v-else-if="agingReport"
      class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden"
    >
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("customers.aging_buckets") }}
        </h4>
      </div>

      <!-- Aging Buckets -->
      <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <div
          v-for="bucket in agingReport.buckets"
          :key="bucket.range"
          class="p-6"
        >
          <!-- Bucket Header -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
              <h5 class="text-base font-medium text-gray-900 dark:text-white">
                {{ bucket.range }}
              </h5>
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200"
              >
                {{ bucket.count }} {{ $t("customers.customers") }}
              </span>
            </div>
            <div class="text-right">
              <div class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ formatCurrency(bucket.total_amount) }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                {{
                  formatPercentage(
                    bucket.total_amount / agingReport.total_outstanding,
                  )
                }}
              </div>
            </div>
          </div>

          <!-- Customers in Bucket -->
          <div v-if="bucket.customers.length > 0" class="space-y-3">
            <div
              v-for="customer in bucket.customers"
              :key="customer.id"
              class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer"
              @click="viewCustomer(customer)"
            >
              <div class="flex items-center space-x-3">
                <!-- Customer Avatar -->
                <div class="flex-shrink-0 h-8 w-8">
                  <div
                    class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center"
                  >
                    <span
                      class="text-xs font-medium text-primary-800 dark:text-primary-200"
                    >
                      {{ getInitials(customer.name) }}
                    </span>
                  </div>
                </div>

                <!-- Customer Info -->
                <div class="flex-1 min-w-0">
                  <p
                    class="text-sm font-medium text-gray-900 dark:text-white truncate"
                  >
                    {{ customer.name }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ customer.email || customer.phone || "-" }}
                  </p>
                </div>

                <!-- Customer Type -->
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    getCustomerTypeClass(customer.customer_type),
                  ]"
                >
                  {{ $t(`customers.types.${customer.customer_type}`) }}
                </span>
              </div>

              <!-- Outstanding Balance -->
              <div class="text-right">
                <div
                  class="text-sm font-semibold text-red-600 dark:text-red-400"
                >
                  {{ formatCurrency(customer.outstanding_balance || 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ $t("customers.last_invoice") }}:
                  {{ formatDate(customer.last_invoice_date) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Empty Bucket -->
          <div v-else class="text-center py-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("customers.no_customers_in_bucket") }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 text-center"
    >
      <DocumentChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
        {{ $t("customers.no_aging_data") }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t("customers.no_aging_description") }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed, onMounted } from "vue";
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  DocumentChartBarIcon,
} from "@heroicons/vue/24/outline";
import { useCustomersStore } from "@/stores/customers";
import type { Customer } from "@/types";

const customersStore = useCustomersStore();

// State
const filters = reactive({
  customer_type: "",
  preferred_language: "",
  as_of_date: new Date().toISOString().split("T")[0],
});

// Computed
const agingReport = computed(() => customersStore.agingReport);

const averageBalance = computed(() => {
  if (!agingReport.value || agingReport.value.total_customers === 0) {
    return 0;
  }
  return (
    agingReport.value.total_outstanding / agingReport.value.total_customers
  );
});

// Methods
const refreshReport = async () => {
  await customersStore.fetchAgingReport(filters);
};

const applyFilters = () => {
  customersStore.fetchAgingReport(filters);
};

const exportReport = () => {
  // In a real implementation, this would export the report to CSV/PDF
  console.log("Exporting aging report...");

  if (!agingReport.value) return;

  // Create CSV content
  let csvContent =
    "Customer Name,Email,Phone,Type,Outstanding Balance,Days Outstanding\n";

  agingReport.value.buckets.forEach((bucket) => {
    bucket.customers.forEach((customer) => {
      csvContent += `"${customer.name}","${customer.email || ""}","${customer.phone || ""}","${customer.customer_type}","${customer.outstanding_balance || 0}","${bucket.range}"\n`;
    });
  });

  // Download CSV
  const blob = new Blob([csvContent], { type: "text/csv" });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = `customer-aging-report-${new Date().toISOString().split("T")[0]}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
};

const viewCustomer = (customer: Customer) => {
  // Emit event to parent or navigate to customer details
  console.log("View customer:", customer);
};

const getInitials = (name: string) => {
  return name
    .split(" ")
    .map((word) => word.charAt(0))
    .join("")
    .toUpperCase()
    .slice(0, 2);
};

const getCustomerTypeClass = (type: string) => {
  const classes = {
    retail: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    wholesale:
      "bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200",
    vip: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
  };
  return (
    classes[type as keyof typeof classes] ||
    "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200"
  );
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
};

const formatPercentage = (value: number) => {
  return `${Math.round(value * 100)}%`;
};

const formatDate = (dateString?: string) => {
  if (!dateString) return "-";
  return new Date(dateString).toLocaleDateString();
};

// Lifecycle
onMounted(() => {
  if (!agingReport.value) {
    customersStore.fetchAgingReport(filters);
  }
});
</script>
