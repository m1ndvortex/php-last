<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="fixed inset-0 bg-black bg-opacity-25"
        @click="$emit('close')"
      ></div>

      <div
        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full"
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.bom_details") }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{
                  bom?.finished_item?.localized_name || bom?.finished_item?.name
                }}
              </p>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="bom && $emit('edit', bom)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <PencilIcon class="h-4 w-4 mr-2" />
                {{ $t("common.edit") }}
              </button>
              <button
                @click="bom && $emit('delete', bom)"
                class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
              >
                <TrashIcon class="h-4 w-4 mr-2" />
                {{ $t("common.delete") }}
              </button>
              <button
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Finished Item Information -->
            <div class="space-y-4">
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.finished_item") }}
              </h4>

              <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-12 w-12">
                    <div
                      class="h-12 w-12 rounded-lg bg-blue-200 dark:bg-blue-800 flex items-center justify-center"
                    >
                      <CubeIcon
                        class="h-8 w-8 text-blue-600 dark:text-blue-400"
                      />
                    </div>
                  </div>
                  <div class="ml-4 flex-1">
                    <div
                      class="text-sm font-medium text-blue-900 dark:text-blue-100"
                    >
                      {{
                        bom?.finished_item?.localized_name ||
                        bom?.finished_item?.name
                      }}
                    </div>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                      {{ $t("inventory.sku") }}: {{ bom?.finished_item?.sku }}
                    </div>
                    <div
                      v-if="bom?.finished_item?.category"
                      class="text-sm text-blue-700 dark:text-blue-300"
                    >
                      {{ $t("inventory.category") }}:
                      {{ bom.finished_item.category.name }}
                    </div>
                  </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                  <div>
                    <div
                      class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase"
                    >
                      {{ $t("inventory.unit_price") }}
                    </div>
                    <div
                      class="text-sm font-semibold text-blue-900 dark:text-blue-100"
                    >
                      {{ formatCurrency(bom?.finished_item?.unit_price || 0) }}
                    </div>
                  </div>
                  <div>
                    <div
                      class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase"
                    >
                      {{ $t("inventory.current_stock") }}
                    </div>
                    <div
                      class="text-sm font-semibold text-blue-900 dark:text-blue-100"
                    >
                      {{ formatNumber(bom?.finished_item?.quantity || 0) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Component Item Information -->
            <div class="space-y-4">
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.component_item") }}
              </h4>

              <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-12 w-12">
                    <div
                      class="h-12 w-12 rounded-lg bg-green-200 dark:bg-green-800 flex items-center justify-center"
                    >
                      <CubeIcon
                        class="h-8 w-8 text-green-600 dark:text-green-400"
                      />
                    </div>
                  </div>
                  <div class="ml-4 flex-1">
                    <div
                      class="text-sm font-medium text-green-900 dark:text-green-100"
                    >
                      {{
                        bom?.component_item?.localized_name ||
                        bom?.component_item?.name
                      }}
                    </div>
                    <div class="text-sm text-green-700 dark:text-green-300">
                      {{ $t("inventory.sku") }}: {{ bom?.component_item?.sku }}
                    </div>
                    <div
                      v-if="bom?.component_item?.category"
                      class="text-sm text-green-700 dark:text-green-300"
                    >
                      {{ $t("inventory.category") }}:
                      {{ bom.component_item.category.name }}
                    </div>
                  </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                  <div>
                    <div
                      class="text-xs font-medium text-green-600 dark:text-green-400 uppercase"
                    >
                      {{ $t("inventory.cost_price") }}
                    </div>
                    <div
                      class="text-sm font-semibold text-green-900 dark:text-green-100"
                    >
                      {{ formatCurrency(bom?.component_item?.cost_price || 0) }}
                    </div>
                  </div>
                  <div>
                    <div
                      class="text-xs font-medium text-green-600 dark:text-green-400 uppercase"
                    >
                      {{ $t("inventory.available_stock") }}
                    </div>
                    <div
                      class="text-sm font-semibold text-green-900 dark:text-green-100"
                    >
                      {{ formatNumber(bom?.component_item?.quantity || 0) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- BOM Calculations -->
            <div class="lg:col-span-2 space-y-4">
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.bom_calculations") }}
              </h4>

              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                  <div class="text-center">
                    <div
                      class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                      {{ formatNumber(bom?.quantity_required || 0) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("inventory.base_quantity") }}
                    </div>
                  </div>

                  <div class="text-center">
                    <div
                      class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"
                    >
                      {{ formatNumber(bom?.wastage_percentage || 0) }}%
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("inventory.wastage_percentage") }}
                    </div>
                  </div>

                  <div class="text-center">
                    <div
                      class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                    >
                      {{ formatNumber(bom?.total_quantity_required || 0) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("inventory.total_required") }}
                    </div>
                  </div>

                  <div class="text-center">
                    <div
                      class="text-2xl font-bold text-green-600 dark:text-green-400"
                    >
                      {{ formatCurrency(bom?.total_cost || 0) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("inventory.total_cost") }}
                    </div>
                  </div>
                </div>

                <!-- Calculation Breakdown -->
                <div
                  class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700"
                >
                  <h5
                    class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                  >
                    {{ $t("inventory.calculation_breakdown") }}
                  </h5>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">
                        {{ $t("inventory.base_quantity") }}:
                      </span>
                      <span class="text-gray-900 dark:text-white">
                        {{ formatNumber(bom?.quantity_required || 0) }}
                      </span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">
                        {{ $t("inventory.wastage") }} ({{
                          formatNumber(bom?.wastage_percentage || 0)
                        }}%):
                      </span>
                      <span class="text-gray-900 dark:text-white">
                        {{ formatNumber(wastageQuantity) }}
                      </span>
                    </div>
                    <div
                      class="flex justify-between font-medium border-t border-gray-200 dark:border-gray-700 pt-2"
                    >
                      <span class="text-gray-900 dark:text-white">
                        {{ $t("inventory.total_required") }}:
                      </span>
                      <span class="text-gray-900 dark:text-white">
                        {{ formatNumber(bom?.total_quantity_required || 0) }}
                      </span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">
                        {{ $t("inventory.unit_cost") }}:
                      </span>
                      <span class="text-gray-900 dark:text-white">
                        {{
                          formatCurrency(bom?.component_item?.cost_price || 0)
                        }}
                      </span>
                    </div>
                    <div
                      class="flex justify-between font-medium border-t border-gray-200 dark:border-gray-700 pt-2"
                    >
                      <span class="text-gray-900 dark:text-white">
                        {{ $t("inventory.total_cost") }}:
                      </span>
                      <span
                        class="text-green-600 dark:text-green-400 font-semibold"
                      >
                        {{ formatCurrency(bom?.total_cost || 0) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Production Analysis -->
            <div class="lg:col-span-2 space-y-4">
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.production_analysis") }}
              </h4>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Possible Production Quantity -->
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                  <div
                    class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-2"
                  >
                    {{ $t("inventory.possible_production") }}
                  </div>
                  <div
                    class="text-2xl font-bold text-blue-900 dark:text-blue-100"
                  >
                    {{ possibleProductionQuantity }}
                  </div>
                  <div class="text-sm text-blue-700 dark:text-blue-300">
                    {{ $t("inventory.units_can_produce") }}
                  </div>
                </div>

                <!-- Stock Status -->
                <div
                  :class="[
                    'p-4 rounded-lg',
                    stockStatus === 'sufficient'
                      ? 'bg-green-50 dark:bg-green-900/20'
                      : stockStatus === 'low'
                        ? 'bg-yellow-50 dark:bg-yellow-900/20'
                        : 'bg-red-50 dark:bg-red-900/20',
                  ]"
                >
                  <div
                    :class="[
                      'text-sm font-medium mb-2',
                      stockStatus === 'sufficient'
                        ? 'text-green-600 dark:text-green-400'
                        : stockStatus === 'low'
                          ? 'text-yellow-600 dark:text-yellow-400'
                          : 'text-red-600 dark:text-red-400',
                    ]"
                  >
                    {{ $t("inventory.stock_status") }}
                  </div>
                  <div
                    :class="[
                      'text-lg font-bold',
                      stockStatus === 'sufficient'
                        ? 'text-green-900 dark:text-green-100'
                        : stockStatus === 'low'
                          ? 'text-yellow-900 dark:text-yellow-100'
                          : 'text-red-900 dark:text-red-100',
                    ]"
                  >
                    {{ $t(`inventory.stock_status_${stockStatus}`) }}
                  </div>
                  <div
                    :class="[
                      'text-sm',
                      stockStatus === 'sufficient'
                        ? 'text-green-700 dark:text-green-300'
                        : stockStatus === 'low'
                          ? 'text-yellow-700 dark:text-yellow-300'
                          : 'text-red-700 dark:text-red-300',
                    ]"
                  >
                    {{ stockStatusMessage }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Information -->
            <div
              v-if="bom?.notes || bom?.created_at"
              class="lg:col-span-2 space-y-4"
            >
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.additional_information") }}
              </h4>

              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <dl class="space-y-3">
                  <div v-if="bom?.notes">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.notes") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ bom.notes }}
                    </dd>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <dt
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("common.status") }}
                      </dt>
                      <dd class="mt-1">
                        <span
                          :class="[
                            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                            bom?.is_active
                              ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                              : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                          ]"
                        >
                          {{
                            bom?.is_active
                              ? $t("common.active")
                              : $t("common.inactive")
                          }}
                        </span>
                      </dd>
                    </div>

                    <div v-if="bom?.created_at">
                      <dt
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("common.created_at") }}
                      </dt>
                      <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ formatDateTime(bom.created_at) }}
                      </dd>
                    </div>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import {
  XMarkIcon,
  PencilIcon,
  TrashIcon,
  CubeIcon,
} from "@heroicons/vue/24/outline";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { BillOfMaterial } from "@/types";

// Props
interface Props {
  bom: BillOfMaterial | null;
}

const props = defineProps<Props>();

// Emits
defineEmits<{
  close: [];
  edit: [bom: BillOfMaterial];
  delete: [bom: BillOfMaterial];
}>();

const { t } = useI18n();
const { formatNumber, formatCurrency } = useNumberFormatter();

// Computed
const wastageQuantity = computed(() => {
  if (!props.bom) return 0;
  return (props.bom.quantity_required * props.bom.wastage_percentage) / 100;
});

const possibleProductionQuantity = computed(() => {
  if (
    !props.bom?.component_item?.quantity ||
    !props.bom?.total_quantity_required
  )
    return 0;
  return Math.floor(
    props.bom.component_item.quantity / props.bom.total_quantity_required,
  );
});

const stockStatus = computed(() => {
  const available = props.bom?.component_item?.quantity || 0;
  const required = props.bom?.total_quantity_required || 0;

  if (available >= required * 10) return "sufficient";
  if (available >= required) return "low";
  return "insufficient";
});

const stockStatusMessage = computed(() => {
  const available = props.bom?.component_item?.quantity || 0;
  const required = props.bom?.total_quantity_required || 0;

  if (stockStatus.value === "sufficient") {
    return t("inventory.sufficient_stock_message", {
      quantity: formatNumber(available),
    });
  } else if (stockStatus.value === "low") {
    return t("inventory.low_stock_message", {
      quantity: formatNumber(available),
    });
  } else {
    const shortage = required - available;
    return t("inventory.insufficient_stock_message", {
      shortage: formatNumber(shortage),
    });
  }
});

// Methods
const formatDateTime = (date: string) => {
  return new Date(date).toLocaleString();
};
</script>
