<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div v-if="invoice" class="bg-white dark:bg-gray-800">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                  {{ $t("invoices.invoice_details") }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  {{ invoice.invoice_number }}
                </p>
              </div>
              <div class="flex items-center space-x-3">
                <!-- Status Badge -->
                <span
                  :class="getStatusClass(invoice.status)"
                  class="inline-flex px-3 py-1 text-sm font-semibold rounded-full"
                >
                  {{ $t(`invoices.status_${invoice.status}`) }}
                </span>
                
                <!-- Language Badge -->
                <span
                  :class="invoice.language === 'fa' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'"
                  class="inline-flex px-3 py-1 text-sm font-semibold rounded-full"
                >
                  {{ invoice.language === 'fa' ? $t("common.persian") : $t("common.english") }}
                </span>

                <button
                  @click="$emit('close')"
                  class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  <XMarkIcon class="h-6 w-6" />
                </button>
              </div>
            </div>
          </div>

          <!-- Content -->
          <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Invoice Information -->
              <div class="space-y-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                    {{ $t("invoices.invoice_information") }}
                  </h4>
                  <dl class="space-y-2">
                    <div class="flex justify-between">
                      <dt class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t("invoices.invoice_number") }}:
                      </dt>
                      <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ invoice.invoice_number }}
                      </dd>
                    </div>
                    <div class="flex justify-between">
                      <dt class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t("invoices.issue_date") }}:
                      </dt>
                      <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ formatDate(invoice.issue_date) }}
                      </dd>
                    </div>
                    <div class="flex justify-between">
                      <dt class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t("invoices.due_date") }}:
                      </dt>
                      <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ formatDate(invoice.due_date) }}
                      </dd>
                    </div>
                    <div class="flex justify-between">
                      <dt class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t("common.created_at") }}:
                      </dt>
                      <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ formatDate(invoice.created_at) }}
                      </dd>
                    </div>
                  </dl>
                </div>

                <!-- Customer Information -->
                <div v-if="invoice.customer">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                    {{ $t("invoices.customer_information") }}
                  </h4>
                  <dl class="space-y-2">
                    <div class="flex justify-between">
                      <dt class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t("customers.name") }}:
                      </dt>
                      <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ invoice.customer.name }}
                      </dd>
                    </div>
                    <div v-if="invoice.customer.email" class="flex justify-between">
                      <dt class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t("customers.email") }}:
                      </dt>
                      <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ invoice.customer.email }}
                      </dd>
                    </div>
                    <div v-if="invoice.customer.phone" class="flex justify-between">
                      <dt class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t("customers.phone") }}:
                      </dt>
                      <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ invoice.customer.phone }}
                      </dd>
                    </div>
                    <div v-if="invoice.customer.address" class="flex justify-between">
                      <dt class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t("customers.address") }}:
                      </dt>
                      <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ invoice.customer.address }}
                      </dd>
                    </div>
                  </dl>
                </div>

                <!-- Notes -->
                <div v-if="invoice.notes">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                    {{ $t("invoices.notes") }}
                  </h4>
                  <p class="text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded-md">
                    {{ invoice.notes }}
                  </p>
                </div>
              </div>

              <!-- Invoice Items -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                  {{ $t("invoices.items") }}
                </h4>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                      <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                          {{ $t("invoices.item_description") }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                          {{ $t("invoices.quantity") }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                          {{ $t("invoices.unit_price") }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                          {{ $t("invoices.total") }}
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="item in invoice.items" :key="item.id">
                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">
                          {{ item.description }}
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white text-right">
                          {{ formatNumber(item.quantity) }}
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-white text-right">
                          {{ formatCurrency(item.unit_price) }}
                        </td>
                        <td class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-white text-right">
                          {{ formatCurrency(item.total_price) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Totals -->
                <div class="mt-4 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $t("invoices.subtotal") }}:
                      </span>
                      <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ formatCurrency(invoice.subtotal) }}
                      </span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $t("invoices.tax") }}:
                      </span>
                      <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ formatCurrency(invoice.tax_amount) }}
                      </span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-600 pt-2">
                      <div class="flex justify-between">
                        <span class="text-base font-medium text-gray-900 dark:text-white">
                          {{ $t("invoices.total") }}:
                        </span>
                        <span class="text-base font-bold text-gray-900 dark:text-white">
                          {{ formatCurrency(invoice.total_amount) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
              <!-- Send Options -->
              <div class="relative">
                <button
                  @click="showSendMenu = !showSendMenu"
                  class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                  <PaperAirplaneIcon class="h-4 w-4 mr-2" />
                  {{ $t("invoices.send") }}
                  <ChevronDownIcon class="h-4 w-4 ml-1" />
                </button>
                <div
                  v-if="showSendMenu"
                  class="absolute bottom-full mb-2 left-0 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-10 border border-gray-200 dark:border-gray-700"
                >
                  <div class="py-1">
                    <button
                      @click="sendInvoice('email')"
                      class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                      {{ $t("invoices.send_email") }}
                    </button>
                    <button
                      @click="sendInvoice('whatsapp')"
                      class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                      {{ $t("invoices.send_whatsapp") }}
                    </button>
                    <button
                      @click="sendInvoice('sms')"
                      class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                      {{ $t("invoices.send_sms") }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- PDF Actions -->
              <button
                @click="$emit('generate-pdf', invoice)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
              >
                <DocumentArrowDownIcon class="h-4 w-4 mr-2" />
                {{ $t("invoices.download_pdf") }}
              </button>

              <!-- Preview -->
              <button
                @click="showPreview = true"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
              >
                <EyeIcon class="h-4 w-4 mr-2" />
                {{ $t("invoices.preview") }}
              </button>
            </div>

            <div class="flex items-center space-x-2">
              <!-- Edit -->
              <button
                @click="$emit('edit', invoice)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
              >
                <PencilIcon class="h-4 w-4 mr-2" />
                {{ $t("common.edit") }}
              </button>

              <!-- Duplicate -->
              <button
                @click="$emit('duplicate', invoice)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
              >
                <DocumentDuplicateIcon class="h-4 w-4 mr-2" />
                {{ $t("common.duplicate") }}
              </button>

              <!-- Delete -->
              <button
                @click="$emit('delete', invoice)"
                class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:bg-gray-700 dark:text-red-400 dark:border-red-600 dark:hover:bg-red-900/20"
              >
                <TrashIcon class="h-4 w-4 mr-2" />
                {{ $t("common.delete") }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PDF Preview Modal -->
    <PDFPreviewModal
      v-if="showPreview"
      :invoice="invoice"
      @close="showPreview = false"
      @download="$emit('generate-pdf', invoice)"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import {
  XMarkIcon,
  PaperAirplaneIcon,
  DocumentArrowDownIcon,
  EyeIcon,
  PencilIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  ChevronDownIcon,
} from "@heroicons/vue/24/outline";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { useCalendarConversion } from "@/composables/useCalendarConversion";
import type { Invoice } from "@/types";

// Components
import PDFPreviewModal from "./PDFPreviewModal.vue";

// Props
interface Props {
  invoice: Invoice | null;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
  close: [];
  edit: [invoice: Invoice];
  delete: [invoice: Invoice];
  duplicate: [invoice: Invoice];
  "generate-pdf": [invoice: Invoice];
  send: [invoice: Invoice, method: "email" | "whatsapp" | "sms"];
}>();

const { formatCurrency, formatNumber } = useNumberFormatter();
const { formatDate } = useCalendarConversion();

// State
const showSendMenu = ref(false);
const showPreview = ref(false);

// Methods
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

const sendInvoice = (method: "email" | "whatsapp" | "sms") => {
  if (props.invoice) {
    emit("send", props.invoice, method);
  }
  showSendMenu.value = false;
};

// Close send menu when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  if (!target.closest('.relative')) {
    showSendMenu.value = false;
  }
};

// Lifecycle
document.addEventListener('click', handleClickOutside);
</script>