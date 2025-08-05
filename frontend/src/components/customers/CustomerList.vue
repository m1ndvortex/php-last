<template>
  <div class="space-y-4">
    <!-- Search and Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Search -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("common.search") }}
          </label>
          <input
            v-model="searchQuery"
            @input="debouncedSearch"
            type="text"
            :placeholder="$t('customers.search_placeholder')"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>

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

        <!-- CRM Stage Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("customers.crm_stage") }}
          </label>
          <select
            v-model="filters.crm_stage"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option value="lead">{{ $t("customers.stages.lead") }}</option>
            <option value="prospect">
              {{ $t("customers.stages.prospect") }}
            </option>
            <option value="customer">
              {{ $t("customers.stages.customer") }}
            </option>
            <option value="inactive">
              {{ $t("customers.stages.inactive") }}
            </option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("common.status") }}
          </label>
          <select
            v-model="filters.is_active"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option value="1">{{ $t("common.active") }}</option>
            <option value="0">{{ $t("common.inactive") }}</option>
          </select>
        </div>
      </div>

      <!-- Clear Filters -->
      <div class="mt-4 flex justify-end">
        <button
          @click="clearFilters"
          class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
        >
          {{ $t("common.clear_filters") }}
        </button>
      </div>
    </div>

    <!-- Customer Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <!-- Loading State -->
      <div v-if="customersStore.loading.customers" class="p-8 text-center">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"
        ></div>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("common.loading") }}
        </p>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="customersStore.customers.length === 0"
        class="p-8 text-center"
      >
        <UsersIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("customers.no_customers") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("customers.no_customers_description") }}
        </p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th
                v-for="column in columns"
                :key="column.key"
                @click="column.sortable && sort(column.key)"
                :class="[
                  'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider',
                  column.sortable &&
                    'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600',
                ]"
              >
                <div class="flex items-center space-x-1">
                  <span>{{ $t(column.label) }}</span>
                  <ChevronUpDownIcon
                    v-if="column.sortable"
                    class="h-4 w-4"
                    :class="getSortIconClass(column.key)"
                  />
                </div>
              </th>
              <th class="relative px-6 py-3">
                <span class="sr-only">{{ $t("common.actions") }}</span>
              </th>
            </tr>
          </thead>
          <tbody
            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
          >
            <tr
              v-for="customer in customersStore.customers"
              :key="customer.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <!-- Name -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div
                      class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center"
                    >
                      <span
                        class="text-sm font-medium text-primary-800 dark:text-primary-200"
                      >
                        {{ getInitials(customer.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{ customer.name }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ customer.email }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- Phone -->
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ customer.phone || "-" }}
              </td>

              <!-- Type -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    getCustomerTypeClass(customer.customer_type),
                  ]"
                >
                  {{ $t(`customers.types.${customer.customer_type}`) }}
                </span>
              </td>

              <!-- CRM Stage -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    getCRMStageClass(customer.crm_stage),
                  ]"
                >
                  {{ $t(`customers.stages.${customer.crm_stage}`) }}
                </span>
              </td>

              <!-- Outstanding Balance -->
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                <span
                  :class="
                    customer.outstanding_balance &&
                    customer.outstanding_balance > 0
                      ? 'text-red-600 dark:text-red-400'
                      : ''
                  "
                >
                  {{ formatCurrency(customer.outstanding_balance || 0) }}
                </span>
              </td>

              <!-- Status -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    customer.is_active
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                      : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                  ]"
                >
                  {{
                    customer.is_active
                      ? $t("common.active")
                      : $t("common.inactive")
                  }}
                </span>
              </td>

              <!-- Actions -->
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
              >
                <div class="flex items-center justify-end space-x-2">
                  <button
                    @click="$emit('view-customer', customer)"
                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="$emit('edit-customer', customer)"
                    class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                  >
                    <PencilIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="exportVCard(customer)"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                  >
                    <ArrowDownTrayIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="$emit('delete-customer', customer)"
                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="customersStore.pagination.total > 0"
        class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6"
      >
        <div class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="previousPage"
              :disabled="customersStore.pagination.current_page === 1"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ $t("common.previous") }}
            </button>
            <button
              @click="nextPage"
              :disabled="
                customersStore.pagination.current_page ===
                customersStore.pagination.last_page
              "
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ $t("common.next") }}
            </button>
          </div>
          <div
            class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between"
          >
            <div>
              <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ $t("common.showing") }}
                <span class="font-medium">{{
                  (customersStore.pagination.current_page - 1) *
                    customersStore.pagination.per_page +
                  1
                }}</span>
                {{ $t("common.to") }}
                <span class="font-medium">{{
                  Math.min(
                    customersStore.pagination.current_page *
                      customersStore.pagination.per_page,
                    customersStore.pagination.total,
                  )
                }}</span>
                {{ $t("common.of") }}
                <span class="font-medium">{{
                  customersStore.pagination.total
                }}</span>
                {{ $t("common.results") }}
              </p>
            </div>
            <div>
              <nav
                class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
              >
                <button
                  @click="previousPage"
                  :disabled="customersStore.pagination.current_page === 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <ChevronLeftIcon class="h-5 w-5" />
                </button>
                <button
                  @click="nextPage"
                  :disabled="
                    customersStore.pagination.current_page ===
                    customersStore.pagination.last_page
                  "
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <ChevronRightIcon class="h-5 w-5" />
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
// Simple debounce implementation
const debounce = (func: Function, wait: number) => {
  let timeout: NodeJS.Timeout;
  return function executedFunction(...args: any[]) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};
import {
  UsersIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  ArrowDownTrayIcon,
  ChevronUpDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from "@heroicons/vue/24/outline";
import { useCustomersStore } from "@/stores/customers";
import type { Customer } from "@/types";

// Props & Emits
defineEmits<{
  "edit-customer": [customer: Customer];
  "view-customer": [customer: Customer];
  "delete-customer": [customer: Customer];
}>();

const customersStore = useCustomersStore();

// State
const searchQuery = ref("");
const filters = reactive({
  customer_type: "",
  crm_stage: "",
  is_active: "",
  preferred_language: "",
  lead_source: "",
});

// Table configuration
const columns = [
  { key: "name", label: "customers.name", sortable: true },
  { key: "phone", label: "customers.phone", sortable: false },
  { key: "customer_type", label: "customers.type", sortable: true },
  { key: "crm_stage", label: "customers.stage", sortable: true },
  {
    key: "outstanding_balance",
    label: "customers.outstanding",
    sortable: true,
  },
  { key: "is_active", label: "common.status", sortable: true },
];

// Methods
const debouncedSearch = debounce(() => {
  customersStore.updateFilters({ search: searchQuery.value });
  customersStore.fetchCustomers();
}, 300);

const applyFilters = () => {
  customersStore.updateFilters(filters);
  customersStore.fetchCustomers();
};

const clearFilters = () => {
  searchQuery.value = "";
  Object.keys(filters).forEach((key) => {
    filters[key as keyof typeof filters] = "";
  });
  customersStore.resetFilters();
  customersStore.fetchCustomers();
};

const sort = (column: string) => {
  const currentSort = customersStore.filters.sort_by;
  const currentDirection = customersStore.filters.sort_direction;

  let newDirection = "asc";
  if (currentSort === column && currentDirection === "asc") {
    newDirection = "desc";
  }

  customersStore.updateFilters({
    sort_by: column,
    sort_direction: newDirection,
  });
  customersStore.fetchCustomers();
};

const getSortIconClass = (column: string) => {
  const currentSort = customersStore.filters.sort_by;
  const currentDirection = customersStore.filters.sort_direction;

  if (currentSort !== column) {
    return "text-gray-400";
  }

  return currentDirection === "asc"
    ? "text-primary-600 transform rotate-180"
    : "text-primary-600";
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

const getCRMStageClass = (stage: string) => {
  const classes = {
    lead: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200",
    prospect: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    customer:
      "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    inactive: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
  };
  return (
    classes[stage as keyof typeof classes] ||
    "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200"
  );
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
};

const exportVCard = async (customer: Customer) => {
  try {
    await customersStore.exportVCard(customer.id);
  } catch (error) {
    console.error("Failed to export vCard:", error);
  }
};

const previousPage = () => {
  if (customersStore.pagination.current_page > 1) {
    customersStore.fetchCustomers({
      page: customersStore.pagination.current_page - 1,
    });
  }
};

const nextPage = () => {
  if (
    customersStore.pagination.current_page < customersStore.pagination.last_page
  ) {
    customersStore.fetchCustomers({
      page: customersStore.pagination.current_page + 1,
    });
  }
};

// Lifecycle
onMounted(() => {
  if (customersStore.customers.length === 0) {
    customersStore.fetchCustomers();
  }
});
</script>
