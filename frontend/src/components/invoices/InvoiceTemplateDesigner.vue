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
        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full"
      >
        <!-- Header -->
        <div
          class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 border-b border-gray-200 dark:border-gray-700"
        >
          <div class="flex items-center justify-between">
            <h3
              class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
            >
              {{
                template
                  ? $t("invoices.edit_template")
                  : $t("invoices.create_template")
              }}
            </h3>
            <div class="flex items-center space-x-3">
              <button
                @click="previewTemplate"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
              >
                <EyeIcon class="h-4 w-4 mr-2" />
                {{ $t("invoices.preview") }}
              </button>
              <button
                @click="saveTemplate"
                :disabled="loading"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
              >
                <svg
                  v-if="loading"
                  class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                {{ $t("common.save") }}
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

        <div class="flex h-[80vh]">
          <!-- Left Sidebar - Template Settings -->
          <div
            class="w-80 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 overflow-y-auto"
          >
            <div class="p-4 space-y-6">
              <!-- Basic Settings -->
              <div>
                <h4
                  class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                >
                  {{ $t("invoices.template_settings") }}
                </h4>
                <div class="space-y-4">
                  <div>
                    <label
                      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                    >
                      {{ $t("invoices.template_name") }}
                    </label>
                    <input
                      v-model="templateForm.name"
                      type="text"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    />
                  </div>
                  <div>
                    <label
                      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                    >
                      {{ $t("common.language") }}
                    </label>
                    <select
                      v-model="templateForm.language"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                      <option value="en">{{ $t("common.english") }}</option>
                      <option value="fa">{{ $t("common.persian") }}</option>
                    </select>
                  </div>
                  <div>
                    <label
                      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                    >
                      {{ $t("invoices.layout") }}
                    </label>
                    <select
                      v-model="templateForm.layout"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    >
                      <option value="standard">
                        {{ $t("invoices.layout_standard") }}
                      </option>
                      <option value="modern">
                        {{ $t("invoices.layout_modern") }}
                      </option>
                      <option value="classic">
                        {{ $t("invoices.layout_classic") }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Available Fields -->
              <div>
                <h4
                  class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                >
                  {{ $t("invoices.available_fields") }}
                </h4>
                <div class="space-y-2">
                  <div
                    v-for="field in availableFields"
                    :key="field.key"
                    :draggable="true"
                    @dragstart="startDrag(field)"
                    class="p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md cursor-move hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                  >
                    <div class="flex items-center">
                      <component
                        :is="field.icon"
                        class="h-4 w-4 text-gray-500 mr-2"
                      />
                      <span class="text-sm text-gray-900 dark:text-white">{{
                        $t(field.label)
                      }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {{ $t(field.description) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Field Options -->
              <div>
                <h4
                  class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                >
                  {{ $t("invoices.field_options") }}
                </h4>
                <div class="space-y-3">
                  <label class="flex items-center">
                    <input
                      v-model="templateForm.fields.logo"
                      type="checkbox"
                      class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      {{ $t("invoices.include_logo") }}
                    </span>
                  </label>
                  <label class="flex items-center">
                    <input
                      v-model="templateForm.fields.qr_code"
                      type="checkbox"
                      class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      {{ $t("invoices.include_qr_code") }}
                    </span>
                  </label>
                  <label class="flex items-center">
                    <input
                      v-model="templateForm.fields.category_hierarchy"
                      type="checkbox"
                      class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      {{ $t("invoices.include_category_hierarchy") }}
                    </span>
                  </label>
                  <label class="flex items-center">
                    <input
                      v-model="templateForm.fields.category_images"
                      type="checkbox"
                      class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      {{ $t("invoices.include_category_images") }}
                    </span>
                  </label>
                  <label class="flex items-center">
                    <input
                      v-model="templateForm.fields.gold_purity"
                      type="checkbox"
                      class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      {{ $t("invoices.include_gold_purity") }}
                    </span>
                  </label>
                </div>
              </div>

              <!-- Custom Fields -->
              <div>
                <h4
                  class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                >
                  {{ $t("invoices.custom_fields") }}
                </h4>
                <div class="space-y-2">
                  <div
                    v-for="(field, index) in templateForm.fields.custom_fields"
                    :key="index"
                    class="flex items-center space-x-2"
                  >
                    <input
                      v-model="templateForm.fields.custom_fields[index]"
                      type="text"
                      :placeholder="$t('invoices.custom_field_name')"
                      class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    />
                    <button
                      @click="removeCustomField(index)"
                      class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </div>
                  <button
                    @click="addCustomField"
                    class="w-full px-3 py-2 border border-dashed border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-600 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                  >
                    <PlusIcon class="h-4 w-4 inline mr-1" />
                    {{ $t("invoices.add_custom_field") }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Main Canvas Area -->
          <div class="flex-1 bg-white dark:bg-gray-800 overflow-hidden">
            <div class="h-full flex flex-col">
              <!-- Canvas Toolbar -->
              <div class="border-b border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                      {{ $t("invoices.canvas") }}
                    </span>
                    <div class="flex items-center space-x-2">
                      <button
                        @click="zoomOut"
                        class="p-1 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                      >
                        <MinusIcon class="h-4 w-4" />
                      </button>
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ Math.round(zoom * 100) }}%
                      </span>
                      <button
                        @click="zoomIn"
                        class="p-1 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                      >
                        <PlusIcon class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <button
                      @click="resetCanvas"
                      class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                      {{ $t("common.reset") }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Canvas -->
              <div
                class="flex-1 overflow-auto p-8 bg-gray-100 dark:bg-gray-900"
              >
                <div
                  ref="canvasRef"
                  class="mx-auto bg-white shadow-lg"
                  :style="{
                    width: `${canvasWidth * zoom}px`,
                    height: `${canvasHeight * zoom}px`,
                    transform: `scale(${zoom})`,
                    transformOrigin: 'top left',
                  }"
                  @dragover.prevent
                  @drop="handleDrop"
                >
                  <!-- Template Preview -->
                  <div class="h-full p-8 relative">
                    <!-- Header Section -->
                    <div class="mb-8">
                      <div v-if="templateForm.fields.logo" class="mb-4">
                        <div
                          class="w-32 h-16 bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center"
                        >
                          <span class="text-xs text-gray-500">{{
                            $t("invoices.logo_placeholder")
                          }}</span>
                        </div>
                      </div>
                      <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        {{
                          templateForm.language === "fa" ? "فاکتور" : "INVOICE"
                        }}
                      </h1>
                      <div class="text-sm text-gray-600">
                        <p>
                          {{
                            templateForm.language === "fa"
                              ? "شماره فاکتور: INV-2024-001"
                              : "Invoice Number: INV-2024-001"
                          }}
                        </p>
                        <p>
                          {{
                            templateForm.language === "fa"
                              ? "تاریخ: ۱۴۰۳/۰۱/۰۱"
                              : "Date: 2024-01-01"
                          }}
                        </p>
                      </div>
                    </div>

                    <!-- Draggable Fields -->
                    <div
                      v-for="(field, index) in placedFields"
                      :key="field.id"
                      :style="{
                        position: 'absolute',
                        left: `${field.x}px`,
                        top: `${field.y}px`,
                        width: `${field.width}px`,
                        height: `${field.height}px`,
                      }"
                      class="border border-dashed border-primary-300 bg-primary-50 p-2 cursor-move"
                      :draggable="true"
                      @dragstart="startFieldDrag(field, $event)"
                      @click="selectField(field)"
                      :class="{
                        'border-primary-500 bg-primary-100':
                          selectedField?.id === field.id,
                      }"
                    >
                      <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-primary-700">
                          {{ $t(field.label) }}
                        </span>
                        <button
                          @click.stop="removeField(index)"
                          class="text-red-500 hover:text-red-700"
                        >
                          <XMarkIcon class="h-3 w-3" />
                        </button>
                      </div>
                      <div class="text-xs text-gray-600 mt-1">
                        {{ field.sampleValue }}
                      </div>
                    </div>

                    <!-- QR Code -->
                    <div
                      v-if="templateForm.fields.qr_code"
                      class="absolute bottom-8 right-8"
                    >
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

          <!-- Right Sidebar - Field Properties -->
          <div
            v-if="selectedField"
            class="w-64 bg-gray-50 dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 overflow-y-auto"
          >
            <div class="p-4">
              <h4
                class="text-sm font-medium text-gray-900 dark:text-white mb-3"
              >
                {{ $t("invoices.field_properties") }}
              </h4>
              <div class="space-y-4">
                <div>
                  <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                  >
                    {{ $t("invoices.field_label") }}
                  </label>
                  <input
                    v-model="selectedField.label"
                    type="text"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                  />
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label
                      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                    >
                      X
                    </label>
                    <input
                      v-model.number="selectedField.x"
                      type="number"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    />
                  </div>
                  <div>
                    <label
                      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                    >
                      Y
                    </label>
                    <input
                      v-model.number="selectedField.y"
                      type="number"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    />
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label
                      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                    >
                      {{ $t("common.width") }}
                    </label>
                    <input
                      v-model.number="selectedField.width"
                      type="number"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    />
                  </div>
                  <div>
                    <label
                      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                    >
                      {{ $t("common.height") }}
                    </label>
                    <input
                      v-model.number="selectedField.height"
                      type="number"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                    />
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
import { ref, computed, onMounted } from "vue";
import {
  XMarkIcon,
  EyeIcon,
  PlusIcon,
  MinusIcon,
  TrashIcon,
  DocumentTextIcon,
  UserIcon,
  CalendarIcon,
  CurrencyDollarIcon,
  HashtagIcon,
} from "@heroicons/vue/24/outline";
import { useInvoicesStore } from "@/stores/invoices";
import type { InvoiceTemplate } from "@/types";

// Props
interface Props {
  template?: InvoiceTemplate | null;
}

const props = withDefaults(defineProps<Props>(), {
  template: null,
});

// Emits
const emit = defineEmits<{
  close: [];
  saved: [template: InvoiceTemplate];
}>();

const invoicesStore = useInvoicesStore();

// State
const loading = ref(false);
const canvasRef = ref<HTMLElement>();
const zoom = ref(1);
const canvasWidth = 595; // A4 width in pixels at 72 DPI
const canvasHeight = 842; // A4 height in pixels at 72 DPI
const selectedField = ref<any>(null);
const draggedField = ref<any>(null);
const placedFields = ref<any[]>([]);

const templateForm = ref({
  name: "",
  language: "en" as "en" | "fa",
  layout: "standard" as "standard" | "modern" | "classic",
  fields: {
    logo: true,
    qr_code: true,
    category_hierarchy: true,
    category_images: true,
    gold_purity: true,
    custom_fields: [] as string[],
  },
});

// Available fields that can be dragged
const availableFields = [
  {
    key: "customer_name",
    label: "invoices.customer_name",
    description: "invoices.customer_name_desc",
    icon: UserIcon,
    sampleValue: "John Doe",
  },
  {
    key: "customer_address",
    label: "invoices.customer_address",
    description: "invoices.customer_address_desc",
    icon: DocumentTextIcon,
    sampleValue: "123 Main St, City",
  },
  {
    key: "invoice_date",
    label: "invoices.invoice_date",
    description: "invoices.invoice_date_desc",
    icon: CalendarIcon,
    sampleValue: "2024-01-01",
  },
  {
    key: "due_date",
    label: "invoices.due_date",
    description: "invoices.due_date_desc",
    icon: CalendarIcon,
    sampleValue: "2024-01-31",
  },
  {
    key: "subtotal",
    label: "invoices.subtotal",
    description: "invoices.subtotal_desc",
    icon: CurrencyDollarIcon,
    sampleValue: "$1,000.00",
  },
  {
    key: "tax_amount",
    label: "invoices.tax_amount",
    description: "invoices.tax_amount_desc",
    icon: CurrencyDollarIcon,
    sampleValue: "$100.00",
  },
  {
    key: "total_amount",
    label: "invoices.total_amount",
    description: "invoices.total_amount_desc",
    icon: CurrencyDollarIcon,
    sampleValue: "$1,100.00",
  },
  {
    key: "invoice_number",
    label: "invoices.invoice_number",
    description: "invoices.invoice_number_desc",
    icon: HashtagIcon,
    sampleValue: "INV-2024-001",
  },
  {
    key: "category_summary",
    label: "invoices.category_summary",
    description: "invoices.category_summary_desc",
    icon: DocumentTextIcon,
    sampleValue: "Rings: 3 items, Necklaces: 2 items",
  },
  {
    key: "gold_purity_summary",
    label: "invoices.gold_purity_summary",
    description: "invoices.gold_purity_summary_desc",
    icon: DocumentTextIcon,
    sampleValue: "18K: 2 items, 21K: 3 items",
  },
];

// Methods
const startDrag = (field: any) => {
  draggedField.value = field;
};

const handleDrop = (event: DragEvent) => {
  event.preventDefault();
  if (!draggedField.value || !canvasRef.value) return;

  const rect = canvasRef.value.getBoundingClientRect();
  if (!rect) return;

  const x = (event.clientX - rect.left) / zoom.value;
  const y = (event.clientY - rect.top) / zoom.value;

  const newField = {
    id: Date.now(),
    ...draggedField.value,
    x: Math.max(0, x - 50),
    y: Math.max(0, y - 25),
    width: 200,
    height: 50,
  };

  placedFields.value.push(newField);
  draggedField.value = null;
};

const startFieldDrag = (field: any, event: DragEvent) => {
  if (!canvasRef.value || !field) return;

  const rect = canvasRef.value.getBoundingClientRect();
  if (!rect) return;

  const offsetX = event.clientX - rect.left - field.x * zoom.value;
  const offsetY = event.clientY - rect.top - field.y * zoom.value;

  const handleMouseMove = (e: MouseEvent) => {
    if (!canvasRef.value) return;

    const currentRect = canvasRef.value.getBoundingClientRect();
    if (!currentRect) return;

    const newX = (e.clientX - currentRect.left - offsetX) / zoom.value;
    const newY = (e.clientY - currentRect.top - offsetY) / zoom.value;

    field.x = Math.max(0, Math.min(canvasWidth - (field.width || 200), newX));
    field.y = Math.max(0, Math.min(canvasHeight - (field.height || 50), newY));
  };

  const handleMouseUp = () => {
    document.removeEventListener("mousemove", handleMouseMove);
    document.removeEventListener("mouseup", handleMouseUp);
  };

  document.addEventListener("mousemove", handleMouseMove);
  document.addEventListener("mouseup", handleMouseUp);
};

const selectField = (field: any) => {
  selectedField.value = field;
};

const removeField = (index: number) => {
  if (index >= 0 && index < placedFields.value.length) {
    const fieldToRemove = placedFields.value[index];
    placedFields.value.splice(index, 1);
    if (
      selectedField.value &&
      fieldToRemove &&
      selectedField.value.id === fieldToRemove.id
    ) {
      selectedField.value = null;
    }
  }
};

const addCustomField = () => {
  templateForm.value.fields.custom_fields.push("");
};

const removeCustomField = (index: number) => {
  templateForm.value.fields.custom_fields.splice(index, 1);
};

const zoomIn = () => {
  zoom.value = Math.min(2, zoom.value + 0.1);
};

const zoomOut = () => {
  zoom.value = Math.max(0.5, zoom.value - 0.1);
};

const resetCanvas = () => {
  placedFields.value = [];
  selectedField.value = null;
  zoom.value = 1;
};

const previewTemplate = () => {
  // Implementation for template preview
  console.log("Preview template:", templateForm.value);
};

const saveTemplate = async () => {
  loading.value = true;

  try {
    const templateData = {
      name: templateForm.value.name,
      language: templateForm.value.language,
      template_data: {
        layout: templateForm.value.layout,
        fields: {
          ...templateForm.value.fields,
          placed_fields: placedFields.value,
        },
      },
      is_active: true,
    };

    let savedTemplate;
    if (props.template) {
      savedTemplate = await invoicesStore.updateTemplate(
        props.template.id,
        templateData,
      );
    } else {
      savedTemplate = await invoicesStore.createTemplate(templateData);
    }

    if (savedTemplate) {
      emit("saved", savedTemplate);
    }
  } catch (error) {
    console.error("Failed to save template:", error);
  } finally {
    loading.value = false;
  }
};

// Initialize form with existing template data
const initializeForm = () => {
  if (props.template) {
    const templateData = props.template.template_data || {};
    const fields = templateData.fields || {};
    
    templateForm.value = {
      name: props.template.name,
      language: props.template.language,
      layout: templateData.layout || 'standard',
      fields: {
        logo: fields.logo ?? true,
        qr_code: fields.qr_code ?? true,
        category_hierarchy: fields.category_hierarchy ?? true,
        category_images: fields.category_images ?? true,
        gold_purity: fields.gold_purity ?? true,
        custom_fields: fields.custom_fields || [],
      },
    };

    // Load placed fields if they exist
    if (fields.placed_fields) {
      placedFields.value = fields.placed_fields;
    }
  }
};

// Lifecycle
onMounted(() => {
  initializeForm();
});
</script>
