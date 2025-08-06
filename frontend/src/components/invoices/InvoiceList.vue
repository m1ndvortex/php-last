<template>
  <div class="space-y-4">
    <!-- Filters -->
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
            v-model="localFilters.search"
            type="text"
            :placeholder="$t('invoices.search_placeholder')"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            @input="debouncedSearch"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("invoices.status") }}
          </label>
          <select
            v-model="localFilters.status"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            @change="applyFilters"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option value="draft">{{ $t("invoices.status_draft") }}</option>
            <option value="sent">{{ $t("invoices.status_sent") }}</option>
            <option value="paid">{{ $t("invoices.status_paid") }}</option>
            <option value="overdue">{{ $t("invoices.status_overdue") }}</option>
            <option value="cancelled">
              {{ $t("invoices.status_cancelled") }}
            </option>
          </select>
        </div>

        <!-- Language Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("common.language") }}
          </label>
          <select
            v-model="localFilters.language"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            @change="applyFilters"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option value="en">{{ $t("common.english") }}</option>
            <option value="fa">{{ $t("common.persian") }}</option>
          </select>
        </div>

        <!-- Date Range -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("common.date_range") }}
          </label>
          <div class="grid grid-cols-2 gap-2">
            <DatePicker
              v-model="localFilters.date_from"
              :placeholder="$t('common.from_date')"
              @update:modelValue="applyFilters"
            />
            <DatePicker
              v-model="localFilters.date_to"
              :placeholder="$t('common.to_date')"
              @update:modelValue="applyFilters"
            />
          </div>
        </div>
      </div>

      <!-- Filter Actions -->
      <div class="mt-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-500 dark:text-gray-400">
            {{
              $t("common.showing_results", {
                from: invoicesStore.pagination.from || 0,
                to: invoicesStore.pagination.to || 0,
                total: invoicesStore.pagination.total,
              })
            }}
          </span>
        </div>
        <div class="flex space-x-2">
          <button
            @click="resetFilters"
            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
          >
            {{ $t("common.reset_filters") }}
          </button>
          <button
            v-if="selectedInvoices.length > 0"
            @click="showBatchActions = !showBatchActions"
            class="px-3 py-1 text-sm bg-primary-100 text-primary-700 rounded-md hover:bg-primary-200 dark:bg-primary-900 dark:text-primary-300"
          >
            {{ $t("invoices.batch_actions") }} ({{ selectedInvoices.length }})
          </button>
        </div>
      </div>

      <!-- Batch Actions -->
      <div
        v-if="showBatchActions && selectedInvoices.length > 0"
        class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-md"
      >
        <div class="flex space-x-2">
          <button
            @click="batchGeneratePDF"
            :disabled="invoicesStore.loading.batch"
            class="px-3 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            {{ $t("invoices.batch_generate_pdf") }}
          </button>
          <button
            @click="batchSend('email')"
            :disabled="invoicesStore.loading.batch"
            class="px-3 py-1 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
          >
            {{ $t("invoices.batch_send_email") }}
          </button>
          <button
            @click="batchSend('whatsapp')"
            :disabled="invoicesStore.loading.batch"
            class="px-3 py-1 text-sm bg-green-500 text-white rounded-md hover:bg-green-600 disabled:opacity-50"
          >
            {{ $t("invoices.batch_send_whatsapp") }}
          </button>
        </div>
      </div>
    </div>

    <!-- Invoice Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  @change="toggleSelectAll"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
              </th>
              <th
                v-for="column in columns"
                :key="column.key"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                @click="column.sortable && sort(column.key)"
              >
                <div class="flex items-center space-x-1">
                  <span>{{ $t(column.label) }}</span>
                  <ChevronUpDownIcon
                    v-if="column.sortable"
                    class="h-4 w-4"
                    :class="{
                      'text-primary-500':
                        invoicesStore.filters.sort_by === column.key,
                    }"
                  />
                </div>
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
            <tr
              v-for="invoice in invoicesStore.invoices"
              :key="invoice.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4">
                <input
                  type="checkbox"
                  :checked="selectedInvoices.includes(invoice.id)"
                  @change="toggleSelect(invoice.id)"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ invoice.invoice_number }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-white">
                  {{ invoice.customer?.name || $t("common.no_customer") }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-white">
                  {{ formatDate(invoice.issue_date) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-white">
                  {{ formatDate(invoice.due_date) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ formatCurrency(invoice.total_amount) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusClass(invoice.status)"
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                >
                  {{ $t(`invoices.status_${invoice.status}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="
                    invoice.language === 'fa'
                      ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                      : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                  "
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                >
                  {{
                    invoice.language === "fa"
                      ? $t("common.persian")
                      : $t("common.english")
                  }}
                </span>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
              >
                <div class="flex items-center justify-end space-x-2">
                  <button
                    @click="$emit('view-invoice', invoice)"
                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="$emit('generate-pdf', invoice)"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                  >
                    <DocumentArrowDownIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="$emit('edit-invoice', invoice)"
                    class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300"
                  >
                    <PencilIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="$emit('duplicate-invoice', invoice)"
                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                  >
                    <DocumentDuplicateIcon class="h-4 w-4" />
                  </button>
                  <div class="relative">
                    <button
                      @click="toggleSendMenu(invoice.id)"
                      class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                    >
                      <PaperAirplaneIcon class="h-4 w-4" />
                    </button>
                    <div
                      v-if="showSendMenu === invoice.id"
                      class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-10 border border-gray-200 dark:border-gray-700"
                    >
                      <div class="py-1">
                        <button
                          @click="sendInvoice(invoice, 'email')"
                          class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                          {{ $t("invoices.send_email") }}
                        </button>
                        <button
                          @click="sendInvoice(invoice, 'whatsapp')"
                          class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                          {{ $t("invoices.send_whatsapp") }}
                        </button>
                        <button
                          @click="sendInvoice(invoice, 'sms')"
                          class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                          {{ $t("invoices.send_sms") }}
                        </button>
                      </div>
                    </div>
                  </div>
                  <button
                    @click="$emit('delete-invoice', invoice)"
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

      <!-- Loading State -->
      <div v-if="invoicesStore.loading.invoices" class="p-8 text-center">
        <div class="inline-flex items-center">
          <svg
            class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-500"
            xmlns="http://www.w3.org/2000/svg"
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

      <!-- Empty State -->
      <div
        v-else-if="invoicesStore.invoices.length === 0"
        class="p-8 text-center"
      >
        <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("invoices.no_invoices") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("invoices.no_invoices_description") }}
        </p>
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="invoicesStore.pagination.last_page > 1"
      class="flex items-center justify-between"
    >
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="previousPage"
          :disabled="invoicesStore.pagination.current_page === 1"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ $t("common.previous") }}
        </button>
        <button
          @click="nextPage"
          :disabled="
            invoicesStore.pagination.current_page ===
            invoicesStore.pagination.last_page
          "
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ $t("common.next") }}
        </button>
      </div>
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700 dark:text-gray-300">
            {{
              $t("common.pagination_info", {
                from: invoicesStore.pagination.from || 0,
                to: invoicesStore.pagination.to || 0,
                total: invoicesStore.pagination.total,
              })
            }}
          </p>
        </div>
        <div>
          <nav
            class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
          >
            <button
              @click="previousPage"
              :disabled="invoicesStore.pagination.current_page === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronLeftIcon class="h-5 w-5" />
            </button>
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                page === invoicesStore.pagination.current_page
                  ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
                  : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
              ]"
            >
              {{ page }}
            </button>
            <button
              @click="nextPage"
              :disabled="
                invoicesStore.pagination.current_page ===
                invoicesStore.pagination.last_page
              "
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronRightIcon class="h-5 w-5" />
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { debounce } from "lodash-es";
import {
  EyeIcon,
  PencilIcon,
  TrashIcon,
  DocumentArrowDownIcon,
  DocumentDuplicateIcon,
  DocumentTextIcon,
  PaperAirplaneIcon,
  ChevronUpDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from "@heroicons/vue/24/outline";
import { useInvoicesStore } from "@/stores/invoices";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { useCalendarConversion } from "@/composables/useCalendarConversion";
import DatePicker from "@/components/localization/DatePicker.vue";
import type { Invoice } from "@/types";

// Emits
const emit = defineEmits<{
  "view-invoice": [invoice: Invoice];
  "edit-invoice": [invoice: Invoice];
  "delete-invoice": [invoice: Invoice];
  "duplicate-invoice": [invoice: Invoice];
  "generate-pdf": [invoice: Invoice];
  "send-invoice": [invoice: Invoice, method: "email" | "whatsapp" | "sms"];
}>();

const invoicesStore = useInvoicesStore();
const { formatCurrency } = useNumberFormatter();
const { formatDate } = useCalendarConversion();

// State
const localFilters = ref({ ...invoicesStore.filters });
const selectedInvoices = ref<number[]>([]);
const showBatchActions = ref(false);
const showSendMenu = ref<number | null>(null);

// Table columns
const columns = [
  { key: "invoice_number", label: "invoices.invoice_number", sortable: true },
  { key: "customer_name", label: "invoices.customer", sortable: true },
  { key: "issue_date", label: "invoices.issue_date", sortable: true },
  { key: "due_date", label: "invoices.due_date", sortable: true },
  { key: "total_amount", label: "invoices.total_amount", sortable: true },
  { key: "status", label: "invoices.status", sortable: true },
  { key: "language", label: "common.language", sortable: false },
];

// Computed
const allSelected = computed(() => {
  return (
    invoicesStore.invoices.length > 0 &&
    selectedInvoices.value.length === invoicesStore.invoices.length
  );
});

const visiblePages = computed(() => {
  const current = invoicesStore.pagination.current_page;
  const last = invoicesStore.pagination.last_page;
  const pages = [];

  const start = Math.max(1, current - 2);
  const end = Math.min(last, current + 2);

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});

// Methods
const debouncedSearch = debounce(() => {
  applyFilters();
}, 300);

const applyFilters = () => {
  invoicesStore.updateFilters(localFilters.value);
  invoicesStore.fetchInvoices();
};

const resetFilters = () => {
  localFilters.value = {
    search: "",
    customer_id: "",
    status: "",
    language: "",
    date_from: "",
    date_to: "",
    template_id: "",
    tags: "",
    sort_by: "created_at",
    sort_direction: "desc",
  };
  invoicesStore.resetFilters();
  invoicesStore.fetchInvoices();
};

const sort = (column: string) => {
  const currentSort = invoicesStore.filters.sort_by;
  const currentDirection = invoicesStore.filters.sort_direction;

  let newDirection = "asc";
  if (currentSort === column && currentDirection === "asc") {
    newDirection = "desc";
  }

  localFilters.value.sort_by = column;
  localFilters.value.sort_direction = newDirection;
  applyFilters();
};

const toggleSelect = (invoiceId: number) => {
  const index = selectedInvoices.value.indexOf(invoiceId);
  if (index > -1) {
    selectedInvoices.value.splice(index, 1);
  } else {
    selectedInvoices.value.push(invoiceId);
  }
};

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedInvoices.value = [];
  } else {
    selectedInvoices.value = invoicesStore.invoices.map(
      (invoice) => invoice.id,
    );
  }
};

const toggleSendMenu = (invoiceId: number) => {
  showSendMenu.value = showSendMenu.value === invoiceId ? null : invoiceId;
};

const sendInvoice = (
  invoice: Invoice,
  method: "email" | "whatsapp" | "sms",
) => {
  emit("send-invoice", invoice, method);
  showSendMenu.value = null;
};

const batchGeneratePDF = async () => {
  try {
    await invoicesStore.generateBatchInvoices(selectedInvoices.value);
    selectedInvoices.value = [];
    showBatchActions.value = false;
  } catch (error) {
    console.error("Failed to generate batch PDFs:", error);
  }
};

const batchSend = async (method: "email" | "whatsapp" | "sms") => {
  try {
    await invoicesStore.sendBatchInvoices(selectedInvoices.value, method);
    selectedInvoices.value = [];
    showBatchActions.value = false;
  } catch (error) {
    console.error("Failed to send batch invoices:", error);
  }
};

const getStatusClass = (status: string) => {
  const classes = {
    draft: "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300",
    sent: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
    paid: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    overdue: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
    cancelled: "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300",
  };
  return classes[status as keyof typeof classes] || classes.draft;
};

// Pagination
const previousPage = () => {
  if (invoicesStore.pagination.current_page > 1) {
    goToPage(invoicesStore.pagination.current_page - 1);
  }
};

const nextPage = () => {
  if (
    invoicesStore.pagination.current_page < invoicesStore.pagination.last_page
  ) {
    goToPage(invoicesStore.pagination.current_page + 1);
  }
};

const goToPage = (page: number) => {
  invoicesStore.fetchInvoices({ page });
};

// Close send menu when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  if (!target.closest(".relative")) {
    showSendMenu.value = null;
  }
};

// Lifecycle
onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

// Watch for filter changes
watch(
  () => invoicesStore.filters,
  (newFilters) => {
    localFilters.value = { ...newFilters };
  },
  { deep: true },
);
</script>
