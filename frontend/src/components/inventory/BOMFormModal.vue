<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="fixed inset-0 bg-black bg-opacity-25"
        @click="$emit('close')"
      ></div>

      <div
        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full"
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              {{
                isEdit ? $t("inventory.edit_bom") : $t("inventory.create_bom")
              }}
            </h3>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="p-6">
          <div class="space-y-4">
            <!-- Finished Item -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.finished_item") }} *
              </label>
              <select
                v-model="form.finished_item_id"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.finished_item_id
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              >
                <option value="">
                  {{ $t("inventory.select_finished_item") }}
                </option>
                <option
                  v-for="item in inventoryStore.items"
                  :key="item.id"
                  :value="item.id"
                >
                  {{ item.localized_name || item.name }} ({{ item.sku }})
                </option>
              </select>
              <p
                v-if="errors.finished_item_id"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.finished_item_id[0] }}
              </p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.finished_item_help") }}
              </p>
            </div>

            <!-- Component Item -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.component_item") }} *
              </label>
              <select
                v-model="form.component_item_id"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.component_item_id
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              >
                <option value="">
                  {{ $t("inventory.select_component_item") }}
                </option>
                <option
                  v-for="item in availableComponentItems"
                  :key="item.id"
                  :value="item.id"
                >
                  {{ item.localized_name || item.name }} ({{ item.sku }})
                </option>
              </select>
              <p
                v-if="errors.component_item_id"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.component_item_id[0] }}
              </p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.component_item_help") }}
              </p>
            </div>

            <!-- Quantity Required -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.quantity_required") }} *
              </label>
              <input
                v-model.number="form.quantity_required"
                type="number"
                step="0.001"
                min="0"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.quantity_required
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              />
              <p
                v-if="errors.quantity_required"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.quantity_required[0] }}
              </p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.quantity_required_help") }}
              </p>
            </div>

            <!-- Wastage Percentage -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.wastage_percentage") }}
              </label>
              <input
                v-model.number="form.wastage_percentage"
                type="number"
                step="0.01"
                min="0"
                max="100"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.wastage_percentage_help") }}
              </p>
            </div>

            <!-- Calculated Total -->
            <div
              v-if="calculatedTotal > 0"
              class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg"
            >
              <div
                class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-2"
              >
                {{ $t("inventory.calculated_totals") }}
              </div>
              <div class="space-y-1 text-sm text-blue-900 dark:text-blue-100">
                <div class="flex justify-between">
                  <span>{{ $t("inventory.base_quantity") }}:</span>
                  <span>{{ formatNumber(form.quantity_required) }}</span>
                </div>
                <div class="flex justify-between">
                  <span
                    >{{ $t("inventory.wastage") }} ({{
                      formatNumber(form.wastage_percentage)
                    }}%):</span
                  >
                  <span>{{ formatNumber(wastageQuantity) }}</span>
                </div>
                <div
                  class="flex justify-between font-medium border-t border-blue-200 dark:border-blue-700 pt-1"
                >
                  <span>{{ $t("inventory.total_required") }}:</span>
                  <span>{{ formatNumber(calculatedTotal) }}</span>
                </div>
                <div v-if="estimatedCost > 0" class="flex justify-between">
                  <span>{{ $t("inventory.estimated_cost") }}:</span>
                  <span>{{ formatCurrency(estimatedCost) }}</span>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.notes") }}
              </label>
              <textarea
                v-model="form.notes"
                rows="3"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                :placeholder="$t('inventory.bom_notes_placeholder')"
              ></textarea>
            </div>

            <!-- Is Active -->
            <div class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label class="ml-2 block text-sm text-gray-900 dark:text-white">
                {{ $t("inventory.is_active") }}
              </label>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="mt-6 flex justify-end space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              {{ $t("common.cancel") }}
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="flex items-center">
                <svg
                  class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                {{ $t("common.saving") }}
              </span>
              <span v-else>
                {{ isEdit ? $t("common.update") : $t("common.create") }}
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from "vue";
// import { useI18n } from "vue-i18n";
import { XMarkIcon } from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { BillOfMaterial } from "@/types";

// Props
interface Props {
  bom?: BillOfMaterial | null;
  isEdit?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  bom: null,
  isEdit: false,
});

// Emits
const emit = defineEmits<{
  close: [];
  saved: [bom: BillOfMaterial];
}>();

// const {} = useI18n();
const inventoryStore = useInventoryStore();
const { formatNumber, formatCurrency } = useNumberFormatter();

// State
const loading = ref(false);
const errors = ref<Record<string, string[]>>({});

// Form data
const form = reactive({
  finished_item_id: "",
  component_item_id: "",
  quantity_required: 0,
  wastage_percentage: 0,
  is_active: true,
  notes: "",
});

// Computed
const isEdit = computed(() => props.isEdit && props.bom);

const availableComponentItems = computed(() => {
  // Exclude the selected finished item from component options
  return inventoryStore.items.filter(
    (item) => item.id !== Number(form.finished_item_id),
  );
});

const wastageQuantity = computed(() => {
  return (form.quantity_required * form.wastage_percentage) / 100;
});

const calculatedTotal = computed(() => {
  return form.quantity_required + wastageQuantity.value;
});

const selectedComponentItem = computed(() => {
  return inventoryStore.items.find(
    (item) => item.id === Number(form.component_item_id),
  );
});

const estimatedCost = computed(() => {
  if (!selectedComponentItem.value) return 0;
  return calculatedTotal.value * (selectedComponentItem.value.cost_price || 0);
});

// Methods
const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};

  try {
    const bomData = {
      ...form,
      finished_item_id: Number(form.finished_item_id),
      component_item_id: Number(form.component_item_id),
    };

    let result;
    if (isEdit.value && props.bom) {
      // Note: Update method not implemented in store yet
      // result = await inventoryStore.updateBOM(props.bom.id, bomData);
      console.log("Update BOM:", bomData);
    } else {
      result = await inventoryStore.createBOM(bomData);
    }

    if (result) {
      emit("saved", result);
    }
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      console.error("Failed to save BOM:", error);
    }
  } finally {
    loading.value = false;
  }
};

// Initialize form with BOM data if editing
const initializeForm = () => {
  if (props.bom) {
    Object.assign(form, {
      finished_item_id: props.bom.finished_item_id?.toString() || "",
      component_item_id: props.bom.component_item_id?.toString() || "",
      quantity_required: props.bom.quantity_required || 0,
      wastage_percentage: props.bom.wastage_percentage || 0,
      is_active: props.bom.is_active ?? true,
      notes: props.bom.notes || "",
    });
  }
};

// Watch for BOM changes
watch(() => props.bom, initializeForm, { immediate: true });

// Watch for finished item changes to reset component selection
watch(
  () => form.finished_item_id,
  () => {
    if (form.component_item_id === form.finished_item_id) {
      form.component_item_id = "";
    }
  },
);

// Lifecycle
onMounted(async () => {
  // Ensure we have items loaded
  if (inventoryStore.items.length === 0) {
    await inventoryStore.fetchItems();
  }
  initializeForm();
});
</script>
