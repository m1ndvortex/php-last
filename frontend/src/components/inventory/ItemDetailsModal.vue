<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="fixed inset-0 bg-black bg-opacity-25"
        @click="$emit('close')"
      ></div>

      <div
        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ item?.localized_name || item?.name }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $t("inventory.sku") }}: {{ item?.sku }}
              </p>
            </div>
            <div class="flex items-center space-x-2">
              <button
                @click="item && $emit('edit', item)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <PencilIcon class="h-4 w-4 mr-2" />
                {{ $t("common.edit") }}
              </button>
              <button
                @click="item && $emit('delete', item)"
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
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information -->
            <div class="lg:col-span-2 space-y-6">
              <!-- Basic Information -->
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h4
                  class="text-md font-medium text-gray-900 dark:text-white mb-4"
                >
                  {{ $t("inventory.basic_information") }}
                </h4>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.name") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ item?.name }}
                    </dd>
                  </div>
                  <div v-if="item?.name_persian">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.name_persian") }}
                    </dt>
                    <dd
                      class="mt-1 text-sm text-gray-900 dark:text-white"
                      dir="rtl"
                    >
                      {{ item.name_persian }}
                    </dd>
                  </div>
                  <div>
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.category") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ item?.category?.name || "-" }}
                    </dd>
                  </div>
                  <div>
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.location") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ item?.location?.name || "-" }}
                    </dd>
                  </div>
                  <div v-if="item?.description" class="md:col-span-2">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.description") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ item.description }}
                    </dd>
                  </div>
                  <div v-if="item?.description_persian" class="md:col-span-2">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.description_persian") }}
                    </dt>
                    <dd
                      class="mt-1 text-sm text-gray-900 dark:text-white"
                      dir="rtl"
                    >
                      {{ item.description_persian }}
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Stock Information -->
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h4
                  class="text-md font-medium text-gray-900 dark:text-white mb-4"
                >
                  {{ $t("inventory.stock_information") }}
                </h4>
                <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.current_quantity") }}
                    </dt>
                    <dd
                      class="mt-1 text-lg font-semibold text-gray-900 dark:text-white"
                    >
                      {{ formatNumber(item?.quantity || 0) }}
                    </dd>
                  </div>
                  <div v-if="item?.minimum_stock">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.minimum_stock") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatNumber(item.minimum_stock) }}
                    </dd>
                  </div>
                  <div v-if="item?.maximum_stock">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.maximum_stock") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatNumber(item.maximum_stock) }}
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Pricing Information -->
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h4
                  class="text-md font-medium text-gray-900 dark:text-white mb-4"
                >
                  {{ $t("inventory.pricing") }}
                </h4>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.unit_price") }}
                    </dt>
                    <dd
                      class="mt-1 text-lg font-semibold text-green-600 dark:text-green-400"
                    >
                      <span
                        v-if="
                          item?.unit_price !== null &&
                          item?.unit_price !== undefined
                        "
                      >
                        {{ formatCurrency(item.unit_price) }}
                      </span>
                      <span
                        v-else
                        class="text-gray-500 dark:text-gray-400 italic text-sm"
                      >
                        {{ $t("inventory.price_on_request") }}
                      </span>
                    </dd>
                  </div>
                  <div>
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.cost_price") }}
                    </dt>
                    <dd
                      class="mt-1 text-lg font-semibold text-gray-900 dark:text-white"
                    >
                      <span
                        v-if="
                          item?.cost_price !== null &&
                          item?.cost_price !== undefined
                        "
                      >
                        {{ formatCurrency(item.cost_price) }}
                      </span>
                      <span
                        v-else
                        class="text-gray-500 dark:text-gray-400 italic text-sm"
                      >
                        {{ $t("inventory.price_on_request") }}
                      </span>
                    </dd>
                  </div>
                  <div>
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.total_value") }}
                    </dt>
                    <dd
                      class="mt-1 text-lg font-semibold text-blue-600 dark:text-blue-400"
                    >
                      {{ formatCurrency(item?.total_value || 0) }}
                    </dd>
                  </div>
                  <div>
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.total_cost") }}
                    </dt>
                    <dd
                      class="mt-1 text-lg font-semibold text-gray-900 dark:text-white"
                    >
                      {{ formatCurrency(item?.total_cost || 0) }}
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Jewelry Specific Information -->
              <div
                v-if="item?.gold_purity || item?.weight"
                class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4"
              >
                <h4
                  class="text-md font-medium text-gray-900 dark:text-white mb-4"
                >
                  {{ $t("inventory.jewelry_info") }}
                </h4>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div v-if="item?.gold_purity">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.gold_purity") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatNumber(item.gold_purity) }}
                      {{ $t("inventory.karat") }}
                    </dd>
                  </div>
                  <div v-if="item?.weight">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.weight") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatNumber(item.weight) }}
                      {{ $t("inventory.grams") }}
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Tracking Information -->
              <div
                v-if="
                  item?.serial_number || item?.batch_number || item?.expiry_date
                "
                class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4"
              >
                <h4
                  class="text-md font-medium text-gray-900 dark:text-white mb-4"
                >
                  {{ $t("inventory.tracking") }}
                </h4>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div v-if="item?.serial_number">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.serial_number") }}
                    </dt>
                    <dd
                      class="mt-1 text-sm text-gray-900 dark:text-white font-mono"
                    >
                      {{ item.serial_number }}
                    </dd>
                  </div>
                  <div v-if="item?.batch_number">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.batch_number") }}
                    </dt>
                    <dd
                      class="mt-1 text-sm text-gray-900 dark:text-white font-mono"
                    >
                      {{ item.batch_number }}
                    </dd>
                  </div>
                  <div v-if="item?.expiry_date">
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.expiry_date") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatDate(item.expiry_date) }}
                    </dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
              <!-- Status -->
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h4
                  class="text-md font-medium text-gray-900 dark:text-white mb-4"
                >
                  {{ $t("common.status") }}
                </h4>
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("inventory.active") }}
                    </span>
                    <span
                      :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        item?.is_active
                          ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                          : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                      ]"
                    >
                      {{ item?.is_active ? $t("common.yes") : $t("common.no") }}
                    </span>
                  </div>
                  <div
                    v-if="item?.is_low_stock"
                    class="flex items-center justify-between"
                  >
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("inventory.stock_status") }}
                    </span>
                    <span
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400"
                    >
                      {{ $t("inventory.low_stock") }}
                    </span>
                  </div>
                  <div
                    v-if="item?.is_expiring"
                    class="flex items-center justify-between"
                  >
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("inventory.expiry_status") }}
                    </span>
                    <span
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400"
                    >
                      {{ $t("inventory.expiring") }}
                    </span>
                  </div>
                  <div
                    v-if="item?.is_expired"
                    class="flex items-center justify-between"
                  >
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("inventory.expiry_status") }}
                    </span>
                    <span
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400"
                    >
                      {{ $t("inventory.expired") }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Tracking Options -->
              <div
                v-if="item?.track_serial || item?.track_batch"
                class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4"
              >
                <h4
                  class="text-md font-medium text-gray-900 dark:text-white mb-4"
                >
                  {{ $t("inventory.tracking_options") }}
                </h4>
                <div class="space-y-3">
                  <div v-if="item?.track_serial" class="flex items-center">
                    <CheckIcon class="h-4 w-4 text-green-500 mr-2" />
                    <span class="text-sm text-gray-900 dark:text-white">
                      {{ $t("inventory.track_serial") }}
                    </span>
                  </div>
                  <div v-if="item?.track_batch" class="flex items-center">
                    <CheckIcon class="h-4 w-4 text-green-500 mr-2" />
                    <span class="text-sm text-gray-900 dark:text-white">
                      {{ $t("inventory.track_batch") }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Timestamps -->
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h4
                  class="text-md font-medium text-gray-900 dark:text-white mb-4"
                >
                  {{ $t("inventory.timestamps") }}
                </h4>
                <dl class="space-y-3">
                  <div>
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("common.created_at") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatDateTime(item?.created_at) }}
                    </dd>
                  </div>
                  <div>
                    <dt
                      class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("common.updated_at") }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ formatDateTime(item?.updated_at) }}
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Quick Actions -->
              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h4
                  class="text-md font-medium text-gray-900 dark:text-white mb-4"
                >
                  {{ $t("inventory.quick_actions") }}
                </h4>
                <div class="space-y-2">
                  <button
                    @click="viewMovements"
                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md"
                  >
                    {{ $t("inventory.view_movements") }}
                  </button>
                  <button
                    @click="adjustStock"
                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md"
                  >
                    {{ $t("inventory.adjust_stock") }}
                  </button>
                  <button
                    v-if="item?.track_serial"
                    @click="viewSerialNumbers"
                    class="w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md"
                  >
                    {{ $t("inventory.view_serial_numbers") }}
                  </button>
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
// import { useI18n } from "vue-i18n";
import {
  XMarkIcon,
  PencilIcon,
  TrashIcon,
  CheckIcon,
} from "@heroicons/vue/24/outline";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { InventoryItem } from "@/types";

// Props
interface Props {
  item: InventoryItem | null;
}

defineProps<Props>();

// Emits
defineEmits<{
  close: [];
  edit: [item: InventoryItem];
  delete: [item: InventoryItem];
}>();

// const {} = useI18n();
const { formatNumber, formatCurrency } = useNumberFormatter();

// Methods
const formatDate = (date: string | undefined) => {
  if (!date) return "-";
  return new Date(date).toLocaleDateString();
};

const formatDateTime = (date: string | undefined) => {
  if (!date) return "-";
  return new Date(date).toLocaleString();
};

const viewMovements = () => {
  // TODO: Implement view movements functionality
  console.log("View movements");
};

const adjustStock = () => {
  // TODO: Implement adjust stock functionality
  console.log("Adjust stock");
};

const viewSerialNumbers = () => {
  // TODO: Implement view serial numbers functionality
  console.log("View serial numbers");
};
</script>
