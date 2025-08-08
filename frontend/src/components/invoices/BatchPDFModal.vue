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
        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
      >
        <!-- Header -->
        <div
          class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 border-b border-gray-200 dark:border-gray-700"
        >
          <div class="flex items-center justify-between">
            <h3
              class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
            >
              {{ $t("invoices.batch_pdf_generation") }}
            </h3>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="px-6 py-4">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ $t("invoices.batch_pdf_description") }}
          </p>

          <!-- Placeholder content -->
          <div class="text-center py-8">
            <DocumentArrowDownIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
              {{ $t("invoices.batch_pdf_placeholder") }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ $t("invoices.batch_pdf_placeholder_description") }}
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div
          class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"
        >
          <button
            @click="handleGenerate"
            :disabled="loading"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ loading ? $t("common.loading") : $t("invoices.generate_pdfs") }}
          </button>
          <button
            @click="$emit('close')"
            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
          >
            {{ $t("common.cancel") }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { XMarkIcon, DocumentArrowDownIcon } from "@heroicons/vue/24/outline";

// Emits
const emit = defineEmits<{
  close: [];
  generated: [result: any];
}>();

import { ref } from "vue";
import { useInvoicesStore } from "@/stores/invoices";

const invoicesStore = useInvoicesStore();
const loading = ref(false);
const selectedInvoices = ref<number[]>([]);

// Methods
const handleGenerate = async () => {
  if (selectedInvoices.value.length === 0) {
    // For now, use all available invoices if none selected
    // In a real implementation, you'd have a selection interface
    selectedInvoices.value = invoicesStore.invoices.map(inv => inv.id);
  }

  loading.value = true;
  try {
    const result = await invoicesStore.generateBatchInvoices(selectedInvoices.value);
    emit("generated", result);
  } catch (error) {
    console.error("Failed to generate batch PDFs:", error);
    // You might want to show an error message to the user
  } finally {
    loading.value = false;
  }
};
</script>
