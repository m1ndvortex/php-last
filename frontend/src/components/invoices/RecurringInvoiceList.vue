<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("invoices.recurring_invoices") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("invoices.recurring_description") }}
        </p>
      </div>
      <button
        @click="$emit('create-recurring')"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
      >
        <PlusIcon class="h-4 w-4 mr-2" />
        {{ $t("invoices.create_recurring") }}
      </button>
    </div>

    <!-- Recurring Invoices Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t("invoices.template_name") }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t("invoices.customer") }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t("invoices.frequency") }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t("invoices.next_generation") }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t("invoices.amount") }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t("common.status") }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ $t("common.actions") }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="recurring in invoicesStore.recurringInvoices"
              :key="recurring.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ recurring.name }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ $t("invoices.created") }} {{ formatDate(recurring.created_at) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-white">
                  {{ recurring.customer?.name || $t("common.no_customer") }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-white">
                  {{ $t(`invoices.frequency_${recurring.frequency}`) }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ $t("invoices.every") }} {{ recurring.interval }} {{ $t(`invoices.${recurring.frequency}`) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-white">
                  {{ formatDate(recurring.next_generation_date) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ formatCurrency(recurring.amount) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusClass(recurring.status)"
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                >
                  {{ $t(`invoices.recurring_status_${recurring.status}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <button
                    @click="viewRecurring(recurring)"
                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="$emit('edit-recurring', recurring)"
                    class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300"
                  >
                    <PencilIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="toggleRecurring(recurring)"
                    :class="recurring.status === 'active' ? 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300' : 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300'"
                  >
                    <component :is="recurring.status === 'active' ? PauseIcon : PlayIcon" class="h-4 w-4" />
                  </button>
                  <button
                    @click="$emit('delete-recurring', recurring)"
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

      <!-- Empty State -->
      <div v-if="invoicesStore.recurringInvoices.length === 0" class="p-8 text-center">
        <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("invoices.no_recurring_invoices") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("invoices.no_recurring_description") }}
        </p>
        <div class="mt-6">
          <button
            @click="$emit('create-recurring')"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            {{ $t("invoices.create_first_recurring") }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="invoicesStore.loading.recurring" class="p-8 text-center">
        <div class="inline-flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ $t("common.loading") }}
        </div>
      </div>
    </div>

    <!-- Recurring Invoice Details Modal -->
    <RecurringInvoiceDetailsModal
      v-if="showDetailsModal"
      :recurring-invoice="selectedRecurring"
      @close="showDetailsModal = false"
      @edit="$emit('edit-recurring', selectedRecurring)"
      @delete="$emit('delete-recurring', selectedRecurring)"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import {
  PlusIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  ClockIcon,
  PlayIcon,
  PauseIcon,
} from "@heroicons/vue/24/outline";
import { useInvoicesStore } from "@/stores/invoices";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { useCalendarConversion } from "@/composables/useCalendarConversion";

// Components
import RecurringInvoiceDetailsModal from "./RecurringInvoiceDetailsModal.vue";

// Emits
const emit = defineEmits<{
  "edit-recurring": [recurring: any];
  "delete-recurring": [recurring: any];
  "create-recurring": [];
}>();

const invoicesStore = useInvoicesStore();
const { formatCurrency } = useNumberFormatter();
const { formatDate } = useCalendarConversion();

// State
const showDetailsModal = ref(false);
const selectedRecurring = ref<any>(null);

// Methods
const getStatusClass = (status: string) => {
  const classes = {
    active: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    paused: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
    completed: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300",
    cancelled: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
  };
  return classes[status as keyof typeof classes] || classes.active;
};

const viewRecurring = (recurring: any) => {
  selectedRecurring.value = recurring;
  showDetailsModal.value = true;
};

const toggleRecurring = async (recurring: any) => {
  try {
    const newStatus = recurring.status === 'active' ? 'paused' : 'active';
    // In a real app, this would call the API to update the status
    console.log(`Toggle recurring invoice ${recurring.id} to ${newStatus}`);
  } catch (error) {
    console.error("Failed to toggle recurring invoice:", error);
  }
};
</script>