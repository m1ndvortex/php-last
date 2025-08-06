<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div
      class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
    >
      <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="$emit('close')"
      ></div>

      <div
        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full"
      >
        <!-- Header -->
        <div
          class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 border-b border-gray-200 dark:border-gray-700"
        >
          <div class="flex items-center justify-between">
            <h3
              class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
            >
              {{ $t("invoices.pdf_preview") }} - {{ invoice?.invoice_number }}
            </h3>
            <div class="flex items-center space-x-3">
              <button
                @click="invoice && $emit('download', invoice)"
                :disabled="!invoice"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <DocumentArrowDownIcon class="h-4 w-4 mr-2" />
                {{ $t("common.download") }}
              </button>
              <button
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>
          </div>
        </div>

        <!-- PDF Preview Content -->
        <div
          class="bg-gray-100 dark:bg-gray-900 p-6 max-h-[80vh] overflow-y-auto"
        >
          <div class="max-w-4xl mx-auto">
            <!-- PDF Preview Container -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
              <div v-if="loading" class="flex items-center justify-center h-96">
                <div class="text-center">
                  <svg
                    class="animate-spin h-8 w-8 text-primary-500 mx-auto mb-4"
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
                  <p class="text-gray-600 dark:text-gray-400">
                    {{ $t("invoices.generating_preview") }}
                  </p>
                </div>
              </div>

              <!-- Mock PDF Preview -->
              <div v-else class="p-8 space-y-6">
                <!-- Invoice Header -->
                <div class="flex justify-between items-start">
                  <div>
                    <div
                      class="w-32 h-16 bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center mb-4"
                    >
                      <span class="text-xs text-gray-500">{{
                        $t("invoices.logo_placeholder")
                      }}</span>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                      {{ invoice?.language === "fa" ? "فاکتور" : "INVOICE" }}
                    </h1>
                  </div>
                  <div class="text-right">
                    <div class="text-sm text-gray-600 space-y-1">
                      <p>
                        <strong>{{
                          invoice?.language === "fa"
                            ? "شماره فاکتور:"
                            : "Invoice #:"
                        }}</strong>
                        {{ invoice?.invoice_number }}
                      </p>
                      <p>
                        <strong>{{
                          invoice?.language === "fa"
                            ? "تاریخ صدور:"
                            : "Issue Date:"
                        }}</strong>
                        {{
                          invoice?.issue_date
                            ? formatDate(invoice.issue_date)
                            : ""
                        }}
                      </p>
                      <p>
                        <strong>{{
                          invoice?.language === "fa"
                            ? "تاریخ سررسید:"
                            : "Due Date:"
                        }}</strong>
                        {{
                          invoice?.due_date ? formatDate(invoice.due_date) : ""
                        }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Customer Information -->
                <div class="grid grid-cols-2 gap-8">
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">
                      {{
                        invoice?.language === "fa"
                          ? "صادر شده برای:"
                          : "Bill To:"
                      }}
                    </h3>
                    <div class="text-sm text-gray-700">
                      <p class="font-medium">{{ invoice?.customer?.name }}</p>
                      <p v-if="invoice?.customer?.email">
                        {{ invoice.customer.email }}
                      </p>
                      <p v-if="invoice?.customer?.phone">
                        {{ invoice.customer.phone }}
                      </p>
                      <p v-if="invoice?.customer?.address">
                        {{ invoice.customer.address }}
                      </p>
                    </div>
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">
                      {{
                        invoice?.language === "fa" ? "صادر شده از:" : "From:"
                      }}
                    </h3>
                    <div class="text-sm text-gray-700">
                      <p class="font-medium">
                        {{ $t("business.company_name") }}
                      </p>
                      <p>{{ $t("business.company_address") }}</p>
                      <p>{{ $t("business.company_phone") }}</p>
                      <p>{{ $t("business.company_email") }}</p>
                    </div>
                  </div>
                </div>

                <!-- Invoice Items -->
                <div>
                  <table class="w-full border-collapse border border-gray-300">
                    <thead>
                      <tr class="bg-gray-50">
                        <th
                          class="border border-gray-300 px-4 py-2 text-left text-sm font-semibold text-gray-900"
                        >
                          {{
                            invoice?.language === "fa" ? "شرح" : "Description"
                          }}
                        </th>
                        <th
                          class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-gray-900"
                        >
                          {{ invoice?.language === "fa" ? "تعداد" : "Qty" }}
                        </th>
                        <th
                          class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-gray-900"
                        >
                          {{
                            invoice?.language === "fa"
                              ? "قیمت واحد"
                              : "Unit Price"
                          }}
                        </th>
                        <th
                          class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-gray-900"
                        >
                          {{ invoice?.language === "fa" ? "مجموع" : "Total" }}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in invoice?.items" :key="item.id">
                        <td
                          class="border border-gray-300 px-4 py-2 text-sm text-gray-900"
                        >
                          {{ item.description }}
                        </td>
                        <td
                          class="border border-gray-300 px-4 py-2 text-sm text-gray-900 text-right"
                        >
                          {{ formatNumber(item.quantity) }}
                        </td>
                        <td
                          class="border border-gray-300 px-4 py-2 text-sm text-gray-900 text-right"
                        >
                          {{ formatCurrency(item.unit_price) }}
                        </td>
                        <td
                          class="border border-gray-300 px-4 py-2 text-sm font-medium text-gray-900 text-right"
                        >
                          {{ formatCurrency(item.total_price) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Totals -->
                <div class="flex justify-end">
                  <div class="w-64">
                    <div class="space-y-2">
                      <div class="flex justify-between text-sm">
                        <span class="text-gray-600">
                          {{
                            invoice?.language === "fa" ? "جمع کل:" : "Subtotal:"
                          }}
                        </span>
                        <span class="font-medium text-gray-900">
                          {{ formatCurrency(invoice?.subtotal || 0) }}
                        </span>
                      </div>
                      <div class="flex justify-between text-sm">
                        <span class="text-gray-600">
                          {{ invoice?.language === "fa" ? "مالیات:" : "Tax:" }}
                        </span>
                        <span class="font-medium text-gray-900">
                          {{ formatCurrency(invoice?.tax_amount || 0) }}
                        </span>
                      </div>
                      <div class="border-t border-gray-300 pt-2">
                        <div class="flex justify-between text-base font-bold">
                          <span class="text-gray-900">
                            {{
                              invoice?.language === "fa"
                                ? "مجموع نهایی:"
                                : "Total:"
                            }}
                          </span>
                          <span class="text-gray-900">
                            {{ formatCurrency(invoice?.total_amount || 0) }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Notes -->
                <div
                  v-if="invoice?.notes"
                  class="border-t border-gray-300 pt-4"
                >
                  <h3 class="text-sm font-semibold text-gray-900 mb-2">
                    {{ invoice?.language === "fa" ? "یادداشت:" : "Notes:" }}
                  </h3>
                  <p class="text-sm text-gray-700">{{ invoice.notes }}</p>
                </div>

                <!-- Footer -->
                <div
                  class="flex justify-between items-end border-t border-gray-300 pt-4"
                >
                  <div class="text-xs text-gray-500">
                    <p>{{ $t("invoices.thank_you_message") }}</p>
                  </div>
                  <div
                    class="w-16 h-16 bg-gray-200 border border-gray-300 flex items-center justify-center"
                  >
                    <span class="text-xs text-gray-500">QR</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { XMarkIcon, DocumentArrowDownIcon } from "@heroicons/vue/24/outline";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { useCalendarConversion } from "@/composables/useCalendarConversion";
import type { Invoice } from "@/types";

// Props
interface Props {
  invoice: Invoice | null;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
  close: [];
  download: [invoice: Invoice];
}>();

const { formatCurrency, formatNumber } = useNumberFormatter();
const { formatDate } = useCalendarConversion();

// State
const loading = ref(true);

// Simulate loading
onMounted(() => {
  setTimeout(() => {
    loading.value = false;
  }, 1000);
});
</script>
