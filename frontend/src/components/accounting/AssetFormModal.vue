<template>
  <div
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
  >
    <div
      class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-screen overflow-y-auto"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{
            asset ? $t("accounting.edit_asset") : $t("accounting.create_asset")
          }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("accounting.asset_number") }}
            </label>
            <input
              v-model="form.asset_number"
              type="text"
              :placeholder="$t('accounting.auto_generated')"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("accounting.category") }} *
            </label>
            <select
              v-model="form.category"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            >
              <option value="">{{ $t("common.select") }}</option>
              <option value="equipment">
                {{ $t("accounting.equipment") }}
              </option>
              <option value="furniture">
                {{ $t("accounting.furniture") }}
              </option>
              <option value="vehicle">{{ $t("accounting.vehicle") }}</option>
              <option value="building">{{ $t("accounting.building") }}</option>
              <option value="software">{{ $t("accounting.software") }}</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("common.name") }} (English) *
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("common.name") }} (Persian)
            </label>
            <input
              v-model="form.name_persian"
              type="text"
              dir="rtl"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
        </div>

        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("common.description") }}
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          ></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("accounting.purchase_cost") }} *
            </label>
            <input
              v-model.number="form.purchase_cost"
              type="number"
              step="0.01"
              min="0"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("accounting.purchase_date") }} *
            </label>
            <input
              v-model="form.purchase_date"
              type="date"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("accounting.salvage_value") }}
            </label>
            <input
              v-model.number="form.salvage_value"
              type="number"
              step="0.01"
              min="0"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("accounting.useful_life_years") }} *
            </label>
            <input
              v-model.number="form.useful_life_years"
              type="number"
              min="1"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("accounting.depreciation_method") }}
            </label>
            <select
              v-model="form.depreciation_method"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            >
              <option value="straight_line">
                {{ $t("accounting.straight_line") }}
              </option>
              <option value="declining_balance">
                {{ $t("accounting.declining_balance") }}
              </option>
              <option value="units_of_production">
                {{ $t("accounting.units_of_production") }}
              </option>
            </select>
          </div>
        </div>

        <div
          class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700"
        >
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
          >
            {{ $t("common.cancel") }}
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
          >
            {{
              loading
                ? $t("common.saving")
                : asset
                  ? $t("common.update")
                  : $t("common.create")
            }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";
import type { Asset } from "@/stores/accounting";

interface Props {
  asset?: Asset | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  close: [];
  saved: [asset: Asset];
}>();

const loading = ref(false);

const form = reactive({
  asset_number: "",
  name: "",
  name_persian: "",
  description: "",
  category: "",
  purchase_cost: 0,
  purchase_date: new Date().toISOString().split("T")[0],
  salvage_value: 0,
  useful_life_years: 5,
  depreciation_method: "straight_line" as
    | "straight_line"
    | "declining_balance"
    | "units_of_production",
});

const handleSubmit = async () => {
  loading.value = true;

  try {
    // Mock save operation
    await new Promise((resolve) => setTimeout(resolve, 500));

    const savedAsset: Asset = {
      id: props.asset?.id || Date.now(),
      asset_number: form.asset_number || `AST-${Date.now()}`,
      name: form.name,
      name_persian: form.name_persian,
      description: form.description,
      category: form.category,
      purchase_cost: form.purchase_cost,
      purchase_date: form.purchase_date,
      salvage_value: form.salvage_value,
      useful_life_years: form.useful_life_years,
      depreciation_method: form.depreciation_method,
      accumulated_depreciation: 0,
      current_value: form.purchase_cost,
      status: "active",
      disposal_date: undefined,
      disposal_value: undefined,
      cost_center_id: undefined,
      metadata: undefined,
      created_at: props.asset?.created_at || new Date().toISOString(),
      updated_at: new Date().toISOString(),
    };

    emit("saved", savedAsset);
  } catch (error) {
    console.error("Failed to save asset:", error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (props.asset) {
    form.asset_number = props.asset.asset_number;
    form.name = props.asset.name;
    form.name_persian = props.asset.name_persian || "";
    form.description = props.asset.description || "";
    form.category = props.asset.category;
    form.purchase_cost = props.asset.purchase_cost;
    form.purchase_date = props.asset.purchase_date;
    form.salvage_value = props.asset.salvage_value;
    form.useful_life_years = props.asset.useful_life_years;
    form.depreciation_method = props.asset.depreciation_method;
  }
});
</script>
