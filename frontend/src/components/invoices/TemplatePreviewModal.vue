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
        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
      >
        <!-- Header -->
        <div
          class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 border-b border-gray-200 dark:border-gray-700"
        >
          <div class="flex items-center justify-between">
            <h3
              class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
            >
              {{ $t("invoices.template_preview") }} - {{ template?.name }}
            </h3>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>
        </div>

        <!-- Preview Content -->
        <div
          class="bg-gray-100 dark:bg-gray-900 p-6 max-h-[80vh] overflow-y-auto"
        >
          <div class="max-w-3xl mx-auto">
            <div class="bg-white shadow-lg rounded-lg p-8">
              <!-- Template preview content would go here -->
              <div class="text-center py-12">
                <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">
                  {{ $t("invoices.template_preview_placeholder") }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  {{ template?.name }} - {{ template?.template_data?.layout }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { XMarkIcon, DocumentTextIcon } from "@heroicons/vue/24/outline";
import type { InvoiceTemplate } from "@/types";

// Props
interface Props {
  template: InvoiceTemplate | null;
}

defineProps<Props>();

// Emits
defineEmits<{
  close: [];
}>();
</script>
