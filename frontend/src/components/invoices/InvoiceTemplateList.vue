<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("invoices.invoice_templates") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("invoices.templates_description") }}
        </p>
      </div>
      <button
        @click="$emit('create-template')"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
      >
        <PlusIcon class="h-4 w-4 mr-2" />
        {{ $t("invoices.create_template") }}
      </button>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="template in invoicesStore.templates"
        :key="template.id"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow"
      >
        <!-- Template Preview -->
        <div class="aspect-[3/4] bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 p-4">
          <div class="h-full bg-white dark:bg-gray-800 rounded shadow-sm p-3 text-xs">
            <!-- Mock template preview -->
            <div class="space-y-2">
              <div class="flex justify-between items-start">
                <div class="w-8 h-4 bg-gray-200 dark:bg-gray-600 rounded"></div>
                <div class="text-right space-y-1">
                  <div class="w-16 h-2 bg-gray-200 dark:bg-gray-600 rounded"></div>
                  <div class="w-12 h-2 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </div>
              </div>
              <div class="w-12 h-3 bg-gray-300 dark:bg-gray-500 rounded font-bold"></div>
              <div class="space-y-1">
                <div class="w-20 h-2 bg-gray-200 dark:bg-gray-600 rounded"></div>
                <div class="w-16 h-2 bg-gray-200 dark:bg-gray-600 rounded"></div>
                <div class="w-18 h-2 bg-gray-200 dark:bg-gray-600 rounded"></div>
              </div>
              <div class="space-y-1 mt-3">
                <div class="flex justify-between">
                  <div class="w-12 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                  <div class="w-8 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                  <div class="w-6 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                  <div class="w-8 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </div>
                <div class="flex justify-between">
                  <div class="w-10 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                  <div class="w-6 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                  <div class="w-8 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                  <div class="w-10 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                </div>
              </div>
              <div class="flex justify-end mt-2">
                <div class="space-y-1">
                  <div class="w-12 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                  <div class="w-10 h-1 bg-gray-200 dark:bg-gray-600 rounded"></div>
                  <div class="w-14 h-1 bg-gray-300 dark:bg-gray-500 rounded"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Template Info -->
        <div class="p-4">
          <div class="flex items-center justify-between mb-2">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
              {{ template.name }}
            </h4>
            <span
              :class="template.language === 'fa' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'"
              class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
            >
              {{ template.language === 'fa' ? $t("common.persian") : $t("common.english") }}
            </span>
          </div>
          
          <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-3">
            <span>{{ $t(`invoices.layout_${template.layout}`) }}</span>
            <span>{{ formatDate(template.created_at) }}</span>
          </div>

          <!-- Template Features -->
          <div class="flex flex-wrap gap-1 mb-3">
            <span
              v-if="template.fields.logo"
              class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded dark:bg-gray-700 dark:text-gray-300"
            >
              {{ $t("invoices.logo") }}
            </span>
            <span
              v-if="template.fields.qr_code"
              class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded dark:bg-gray-700 dark:text-gray-300"
            >
              {{ $t("invoices.qr_code") }}
            </span>
            <span
              v-if="template.fields.custom_fields?.length"
              class="inline-flex px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded dark:bg-gray-700 dark:text-gray-300"
            >
              {{ $t("invoices.custom_fields") }} ({{ template.fields.custom_fields.length }})
            </span>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <button
                @click="previewTemplate(template)"
                class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 text-sm"
              >
                {{ $t("invoices.preview") }}
              </button>
              <button
                @click="$emit('edit-template', template)"
                class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 text-sm"
              >
                {{ $t("common.edit") }}
              </button>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="duplicateTemplate(template)"
                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
              >
                <DocumentDuplicateIcon class="h-4 w-4" />
              </button>
              <button
                @click="$emit('delete-template', template)"
                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
              >
                <TrashIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="invoicesStore.templates.length === 0" class="text-center py-12">
      <PaintBrushIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
        {{ $t("invoices.no_templates") }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t("invoices.no_templates_description") }}
      </p>
      <div class="mt-6">
        <button
          @click="$emit('create-template')"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          {{ $t("invoices.create_first_template") }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="invoicesStore.loading.templates" class="text-center py-12">
      <div class="inline-flex items-center">
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ $t("common.loading") }}
      </div>
    </div>

    <!-- Template Preview Modal -->
    <TemplatePreviewModal
      v-if="showPreviewModal"
      :template="selectedTemplate"
      @close="showPreviewModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import {
  PlusIcon,
  PaintBrushIcon,
  DocumentDuplicateIcon,
  TrashIcon,
} from "@heroicons/vue/24/outline";
import { useInvoicesStore } from "@/stores/invoices";
import { useCalendarConversion } from "@/composables/useCalendarConversion";
import type { InvoiceTemplate } from "@/types";

// Components
import TemplatePreviewModal from "./TemplatePreviewModal.vue";

// Emits
const emit = defineEmits<{
  "edit-template": [template: InvoiceTemplate];
  "delete-template": [template: InvoiceTemplate];
  "create-template": [];
}>();

const invoicesStore = useInvoicesStore();
const { formatDate } = useCalendarConversion();

// State
const showPreviewModal = ref(false);
const selectedTemplate = ref<InvoiceTemplate | null>(null);

// Methods
const previewTemplate = (template: InvoiceTemplate) => {
  selectedTemplate.value = template;
  showPreviewModal.value = true;
};

const duplicateTemplate = async (template: InvoiceTemplate) => {
  try {
    const duplicatedTemplate = {
      ...template,
      name: `${template.name} (Copy)`,
      id: undefined,
    };
    await invoicesStore.createTemplate(duplicatedTemplate);
  } catch (error) {
    console.error("Failed to duplicate template:", error);
  }
};
</script>