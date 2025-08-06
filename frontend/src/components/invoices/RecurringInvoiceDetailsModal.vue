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
        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full"
      >
        <div v-if="recurringInvoice" class="bg-white dark:bg-gray-800">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <h3
                  class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
                >
                  {{ $t("invoices.recurring_invoice_details") }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  {{ recurringInvoice.name }}
                </p>
              </div>
              <button
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
            <div class="space-y-6">
              <!-- Basic Information -->
              <div>
                <h4
                  class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                >
                  {{ $t("invoices.basic_information") }}
                </h4>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.template_name") }}:
                    </dt>
                    <dd
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{ recurringInvoice.name }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.customer") }}:
                    </dt>
                    <dd
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{
                        recurringInvoice.customer?.name ||
                        $t("common.no_customer")
                      }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.frequency") }}:
                    </dt>
                    <dd
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{
                        $t(`invoices.frequency_${recurringInvoice.frequency}`)
                      }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.amount") }}:
                    </dt>
                    <dd
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{ formatCurrency(recurringInvoice.amount) }}
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Schedule Information -->
              <div>
                <h4
                  class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                >
                  {{ $t("invoices.schedule_information") }}
                </h4>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.start_date") }}:
                    </dt>
                    <dd
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{ formatDate(recurringInvoice.start_date) }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.next_generation") }}:
                    </dt>
                    <dd
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{ formatDate(recurringInvoice.next_generation_date) }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.generated_count") }}:
                    </dt>
                    <dd
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{ recurringInvoice.generated_count || 0 }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("common.status") }}:
                    </dt>
                    <dd>
                      <span
                        :class="getStatusClass(recurringInvoice.status)"
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      >
                        {{
                          $t(
                            `invoices.recurring_status_${recurringInvoice.status}`,
                          )
                        }}
                      </span>
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Generated Invoices -->
              <div v-if="recurringInvoice.generated_invoices?.length">
                <h4
                  class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                >
                  {{ $t("invoices.generated_invoices") }}
                </h4>
                <div class="overflow-x-auto">
                  <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                  >
                    <thead class="bg-gray-50 dark:bg-gray-700">
                      <tr>
                        <th
                          class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                        >
                          {{ $t("invoices.invoice_number") }}
                        </th>
                        <th
                          class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                        >
                          {{ $t("invoices.generated_date") }}
                        </th>
                        <th
                          class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                        >
                          {{ $t("invoices.amount") }}
                        </th>
                        <th
                          class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                        >
                          {{ $t("common.status") }}
                        </th>
                      </tr>
                    </thead>
                    <tbody
                      class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                      <tr
                        v-for="invoice in recurringInvoice.generated_invoices"
                        :key="invoice.id"
                      >
                        <td
                          class="px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                          {{ invoice.invoice_number }}
                        </td>
                        <td
                          class="px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                          {{ formatDate(invoice.created_at) }}
                        </td>
                        <td
                          class="px-3 py-2 text-sm text-gray-900 dark:text-white"
                        >
                          {{ formatCurrency(invoice.total_amount) }}
                        </td>
                        <td class="px-3 py-2 text-sm">
                          <span
                            :class="getInvoiceStatusClass(invoice.status)"
                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                          >
                            {{ $t(`invoices.status_${invoice.status}`) }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
          <div
            class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex items-center justify-between"
          >
            <div class="flex items-center space-x-2">
              <button
                @click="$emit('edit', recurringInvoice)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
              >
                <PencilIcon class="h-4 w-4 mr-2" />
                {{ $t("common.edit") }}
              </button>
            </div>

            <div class="flex items-center space-x-2">
              <button
                @click="$emit('delete', recurringInvoice)"
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
  </div>
</template>

<script setup lang="ts">
import { XMarkIcon, PencilIcon, TrashIcon } from "@heroicons/vue/24/outline";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { useCalendarConversion } from "@/composables/useCalendarConversion";

// Props
interface Props {
  recurringInvoice: any | null;
}

defineProps<Props>();

// Emits
defineEmits<{
  close: [];
  edit: [recurring: any];
  delete: [recurring: any];
}>();

const { formatCurrency } = useNumberFormatter();
const { formatDate } = useCalendarConversion();

// Methods
const getStatusClass = (status: string) => {
  const classes = {
    active: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    paused:
      "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
    completed: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
    cancelled: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
  };
  return classes[status as keyof typeof classes] || classes.active;
};

const getInvoiceStatusClass = (status: string) => {
  const classes = {
    draft: "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300",
    sent: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
    paid: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    overdue: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
    cancelled: "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300",
  };
  return classes[status as keyof typeof classes] || classes.draft;
};
</script>
