<template>
  <div
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
  >
    <div
      class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800"
    >
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("accounting.asset_details") }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div v-if="asset" class="space-y-6">
        <!-- Asset Header -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("accounting.asset_number") }}
              </label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                {{ asset.asset_number }}
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("common.name") }}
              </label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ getLocalizedName(asset) }}
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("accounting.category") }}
              </label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white capitalize">
                {{ $t(`accounting.${asset.category}`) }}
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("common.status") }}
              </label>
              <span
                :class="[
                  'inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1',
                  asset.status === 'active'
                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                    : asset.status === 'disposed'
                      ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                      : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                ]"
              >
                {{ $t(`accounting.${asset.status}`) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Financial Information -->
        <div>
          <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ $t("accounting.financial_information") }}
          </h4>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("accounting.purchase_cost") }}
                </label>
                <p
                  class="mt-1 text-sm text-gray-900 dark:text-white font-semibold"
                >
                  {{ formatCurrency(asset.purchase_cost) }}
                </p>
              </div>
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("accounting.current_value") }}
                </label>
                <p
                  class="mt-1 text-sm text-gray-900 dark:text-white font-semibold"
                >
                  {{ formatCurrency(asset.current_value) }}
                </p>
              </div>
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("accounting.accumulated_depreciation") }}
                </label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                  {{ formatCurrency(asset.accumulated_depreciation) }}
                </p>
              </div>
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("accounting.salvage_value") }}
                </label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                  {{ formatCurrency(asset.salvage_value) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Depreciation Information -->
        <div>
          <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ $t("accounting.depreciation_information") }}
          </h4>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("accounting.depreciation_method") }}
                </label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                  {{ $t(`accounting.${asset.depreciation_method}`) }}
                </p>
              </div>
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("accounting.useful_life_years") }}
                </label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                  {{ asset.useful_life_years }} {{ $t("common.years") }}
                </p>
              </div>
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("accounting.purchase_date") }}
                </label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                  {{ formatDate(asset.purchase_date) }}
                </p>
              </div>
              <div v-if="asset.disposal_date">
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("accounting.disposal_date") }}
                </label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                  {{ formatDate(asset.disposal_date) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div v-if="asset.description">
          <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ $t("common.description") }}
          </h4>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-sm text-gray-900 dark:text-white">
              {{ asset.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div
        class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700 mt-6"
      >
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
        >
          {{ $t("common.close") }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { XMarkIcon } from "@heroicons/vue/24/outline";
import { useLocale } from "@/composables/useLocale";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { Asset } from "@/stores/accounting";

interface Props {
  asset: Asset | null;
}

defineProps<Props>();
defineEmits<{
  close: [];
}>();

const { formatDate } = useLocale();
const { formatCurrency } = useNumberFormatter();

const getLocalizedName = (asset: Asset) => {
  const locale = document.documentElement.lang || "en";
  return locale === "fa" && asset.name_persian
    ? asset.name_persian
    : asset.name;
};
</script>
