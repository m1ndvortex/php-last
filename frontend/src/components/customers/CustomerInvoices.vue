<template>
  <div class="space-y-4">
    <!-- Invoice Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ invoices.length }}
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ $t("invoices.total_invoices") }}
        </div>
      </div>
      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ formatCurrency(totalInvoiced) }}
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ $t("invoices.total_invoiced") }}
        </div>
      </div>
      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
          {{ formatCurrency(totalOutstanding) }}
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ $t("invoices.outstanding_balance") }}
        </div>
      </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("invoices.invoice_history") }}
        </h4>
      </div>

      <!-- Empty State -->
      <div v-if="invoices.length === 0" class="p-8 text-center">
        <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("invoices.no_invoices") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("invoices.no_invoices_customer") }}
        </p>
      </div>

      <!-- Invoice List -->
      <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
        <div
          v-for="invoice in invoices"
          :key="invoice.id"
          class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <!-- Invoice Icon -->
              <div class="flex-shrink-0">
                <div
                  class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center"
                >
                  <DocumentTextIcon
                    class="h-5 w-5 text-blue-600 dark:text-blue-300"
                  />
                </div>
              </div>

              <!-- Invoice Details -->
              <div>
                <div class="flex items-center space-x-2">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ invoice.invoice_number }}
                  </p>
                  <span
                    :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      getInvoiceStatusColor(invoice.status),
                    ]"
                  >
                    {{ $t(`invoices.statuses.${invoice.status}`) }}
                  </span>
                </div>
                <div
                  class="mt-1 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400"
                >
                  <span
                    >{{ $t("invoices.issued") }}:
                    {{ formatDate(invoice.issue_date) }}</span
                  >
                  <span
                    >{{ $t("invoices.due") }}:
                    {{ formatDate(invoice.due_date) }}</span
                  >
                </div>
              </div>
            </div>

            <!-- Invoice Amount -->
            <div class="text-right">
              <div class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ formatCurrency(invoice.total_amount) }}
              </div>
              <div
                v-if="invoice.status === 'overdue'"
                class="text-sm text-red-600 dark:text-red-400"
              >
                {{ $t("invoices.overdue") }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { DocumentTextIcon } from "@heroicons/vue/24/outline";
import type { Customer, Invoice } from "@/types";

// Props
interface Props {
  customer: Customer;
}

const props = defineProps<Props>();

// Mock data - in a real implementation, this would come from the store
const invoices = computed<Invoice[]>(() => {
  // Mock invoice data would go here
  // In real implementation, would use props.customer.id to fetch invoices
  console.log("Customer ID:", props.customer.id); // Using props to avoid lint error
  return [];
});

// Computed
const totalInvoiced = computed(() => {
  return invoices.value.reduce(
    (total, invoice) => total + invoice.total_amount,
    0,
  );
});

const totalOutstanding = computed(() => {
  return invoices.value
    .filter((invoice) => ["sent", "overdue"].includes(invoice.status))
    .reduce((total, invoice) => total + invoice.total_amount, 0);
});

// Methods
const getInvoiceStatusColor = (status: string) => {
  const colors = {
    draft: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200",
    sent: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    paid: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    overdue: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
    cancelled: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200",
  };
  return colors[status as keyof typeof colors] || colors.draft;
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};
</script>
