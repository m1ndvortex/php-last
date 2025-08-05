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
              {{ $t("inventory.add_movement") }}
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
            <!-- Item Selection -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.item") }} *
              </label>
              <select
                v-model="form.inventory_item_id"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.inventory_item_id
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              >
                <option value="">{{ $t("inventory.select_item") }}</option>
                <option
                  v-for="item in inventoryStore.items"
                  :key="item.id"
                  :value="item.id"
                >
                  {{ item.localized_name || item.name }} ({{ item.sku }})
                </option>
              </select>
              <p
                v-if="errors.inventory_item_id"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.inventory_item_id[0] }}
              </p>
            </div>

            <!-- Movement Type -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.movement_type") }} *
              </label>
              <select
                v-model="form.type"
                @change="onTypeChange"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.type
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              >
                <option value="">{{ $t("inventory.select_type") }}</option>
                <option value="in">
                  {{ $t("inventory.movement_types.in") }}
                </option>
                <option value="out">
                  {{ $t("inventory.movement_types.out") }}
                </option>
                <option value="transfer">
                  {{ $t("inventory.movement_types.transfer") }}
                </option>
                <option value="adjustment">
                  {{ $t("inventory.movement_types.adjustment") }}
                </option>
                <option value="wastage">
                  {{ $t("inventory.movement_types.wastage") }}
                </option>
                <option value="production">
                  {{ $t("inventory.movement_types.production") }}
                </option>
              </select>
              <p
                v-if="errors.type"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.type[0] }}
              </p>
            </div>

            <!-- Quantity -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.quantity") }} *
              </label>
              <input
                v-model.number="form.quantity"
                type="number"
                step="0.001"
                min="0"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.quantity
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              />
              <p
                v-if="errors.quantity"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.quantity[0] }}
              </p>
            </div>

            <!-- From Location (for out/transfer) -->
            <div v-if="['out', 'transfer'].includes(form.type)">
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.from_location") }} *
              </label>
              <select
                v-model="form.from_location_id"
                :required="['out', 'transfer'].includes(form.type)"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              >
                <option value="">{{ $t("inventory.select_location") }}</option>
                <option
                  v-for="location in inventoryStore.locations"
                  :key="location.id"
                  :value="location.id"
                >
                  {{ location.name }}
                </option>
              </select>
            </div>

            <!-- To Location (for in/transfer) -->
            <div v-if="['in', 'transfer'].includes(form.type)">
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.to_location") }} *
              </label>
              <select
                v-model="form.to_location_id"
                :required="['in', 'transfer'].includes(form.type)"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              >
                <option value="">{{ $t("inventory.select_location") }}</option>
                <option
                  v-for="location in inventoryStore.locations"
                  :key="location.id"
                  :value="location.id"
                >
                  {{ location.name }}
                </option>
              </select>
            </div>

            <!-- Unit Cost -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.unit_cost") }}
              </label>
              <input
                v-model.number="form.unit_cost"
                type="number"
                step="0.01"
                min="0"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.unit_cost_help") }}
              </p>
            </div>

            <!-- Batch Number -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.batch_number") }}
              </label>
              <input
                v-model="form.batch_number"
                type="text"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                :placeholder="$t('inventory.batch_number_placeholder')"
              />
            </div>

            <!-- Reference -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.reference") }}
              </label>
              <input
                v-model="form.reference_type"
                type="text"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                :placeholder="$t('inventory.reference_placeholder')"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.reference_help") }}
              </p>
            </div>

            <!-- Movement Date -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.movement_date") }} *
              </label>
              <input
                v-model="form.movement_date"
                type="datetime-local"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.movement_date
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              />
              <p
                v-if="errors.movement_date"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.movement_date[0] }}
              </p>
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
                :placeholder="$t('inventory.movement_notes_placeholder')"
              ></textarea>
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
                {{ $t("common.creating") }}
              </span>
              <span v-else>
                {{ $t("inventory.add_movement") }}
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
// import { useI18n } from "vue-i18n";
import { XMarkIcon } from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";

// Emits
const emit = defineEmits<{
  close: [];
  created: [movement: any];
}>();

// const {} = useI18n();
const inventoryStore = useInventoryStore();

// State
const loading = ref(false);
const errors = ref<Record<string, string[]>>({});

// Form data
const form = reactive({
  inventory_item_id: "",
  type: "",
  quantity: 0,
  from_location_id: "",
  to_location_id: "",
  unit_cost: null as number | null,
  batch_number: "",
  reference_type: "",
  reference_id: null as number | null,
  movement_date: new Date().toISOString().slice(0, 16),
  notes: "",
});

// Methods
const onTypeChange = () => {
  // Reset location fields when type changes
  form.from_location_id = "";
  form.to_location_id = "";
};

const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};

  try {
    const movementData = {
      ...form,
      inventory_item_id: Number(form.inventory_item_id),
      from_location_id: form.from_location_id
        ? Number(form.from_location_id)
        : null,
      to_location_id: form.to_location_id ? Number(form.to_location_id) : null,
      unit_cost: form.unit_cost || null,
      reference_id: form.reference_id || null,
    };

    const result = await inventoryStore.createMovement(movementData);
    emit("created", result);
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      console.error("Failed to create movement:", error);
    }
  } finally {
    loading.value = false;
  }
};

// Lifecycle
onMounted(async () => {
  // Ensure we have items and locations loaded
  if (inventoryStore.items.length === 0) {
    await inventoryStore.fetchItems();
  }
  if (inventoryStore.locations.length === 0) {
    await inventoryStore.fetchLocations();
  }
});
</script>
