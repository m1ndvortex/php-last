<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="fixed inset-0 bg-black bg-opacity-25"
        @click="$emit('close')"
      ></div>

      <div
        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full"
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.movement_details") }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{
                  movement?.inventory_item?.localized_name ||
                  movement?.inventory_item?.name
                }}
              </p>
            </div>
            <div class="flex items-center space-x-2">
              <span
                :class="[
                  'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                  getMovementTypeColor(movement?.type),
                ]"
              >
                {{ $t(`inventory.movement_types.${movement?.type}`) }}
              </span>
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
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Movement Information -->
            <div class="space-y-4">
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.movement_information") }}
              </h4>

              <dl class="space-y-3">
                <div>
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.movement_type") }}
                  </dt>
                  <dd class="mt-1">
                    <span
                      :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        getMovementTypeColor(movement?.type),
                      ]"
                    >
                      {{ $t(`inventory.movement_types.${movement?.type}`) }}
                    </span>
                  </dd>
                </div>

                <div>
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.quantity") }}
                  </dt>
                  <dd
                    class="mt-1 text-lg font-semibold text-gray-900 dark:text-white"
                  >
                    <span
                      :class="[
                        movement?.is_inbound
                          ? 'text-green-600 dark:text-green-400'
                          : movement?.is_outbound
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-gray-900 dark:text-white',
                      ]"
                    >
                      {{
                        movement?.is_inbound
                          ? "+"
                          : movement?.is_outbound
                            ? "-"
                            : ""
                      }}{{ formatNumber(movement?.quantity || 0) }}
                    </span>
                  </dd>
                </div>

                <div v-if="movement?.unit_cost">
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.unit_cost") }}
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ formatCurrency(movement.unit_cost) }}
                  </dd>
                </div>

                <div v-if="movement?.total_value">
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.total_value") }}
                  </dt>
                  <dd
                    class="mt-1 text-lg font-semibold text-blue-600 dark:text-blue-400"
                  >
                    {{ formatCurrency(movement.total_value) }}
                  </dd>
                </div>

                <div>
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.movement_date") }}
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ formatDateTime(movement?.movement_date) }}
                  </dd>
                </div>
              </dl>
            </div>

            <!-- Location Information -->
            <div class="space-y-4">
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.location_information") }}
              </h4>

              <dl class="space-y-3">
                <div v-if="movement?.from_location">
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.from_location") }}
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ movement.from_location.name }}
                  </dd>
                </div>

                <div v-if="movement?.to_location">
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.to_location") }}
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ movement.to_location.name }}
                  </dd>
                </div>

                <div
                  v-if="
                    movement?.type === 'transfer' &&
                    movement?.from_location &&
                    movement?.to_location
                  "
                >
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.transfer_direction") }}
                  </dt>
                  <dd
                    class="mt-1 text-sm text-gray-900 dark:text-white flex items-center"
                  >
                    <span
                      class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs"
                    >
                      {{ movement.from_location.name }}
                    </span>
                    <ArrowRightIcon class="h-4 w-4 mx-2 text-gray-400" />
                    <span
                      class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs"
                    >
                      {{ movement.to_location.name }}
                    </span>
                  </dd>
                </div>
              </dl>
            </div>

            <!-- Item Information -->
            <div class="md:col-span-2 space-y-4">
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.item_information") }}
              </h4>

              <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-12 w-12">
                    <div
                      class="h-12 w-12 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center"
                    >
                      <CubeIcon class="h-8 w-8 text-gray-400" />
                    </div>
                  </div>
                  <div class="ml-4 flex-1">
                    <div
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{
                        movement?.inventory_item?.localized_name ||
                        movement?.inventory_item?.name
                      }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("inventory.sku") }}:
                      {{ movement?.inventory_item?.sku }}
                    </div>
                    <div
                      v-if="movement?.inventory_item?.category"
                      class="text-sm text-gray-500 dark:text-gray-400"
                    >
                      {{ $t("inventory.category") }}:
                      {{ movement.inventory_item.category.name }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Information -->
            <div
              v-if="
                movement?.batch_number ||
                movement?.reference_type ||
                movement?.notes
              "
              class="md:col-span-2 space-y-4"
            >
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.additional_information") }}
              </h4>

              <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="movement?.batch_number">
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.batch_number") }}
                  </dt>
                  <dd
                    class="mt-1 text-sm text-gray-900 dark:text-white font-mono"
                  >
                    {{ movement.batch_number }}
                  </dd>
                </div>

                <div v-if="movement?.reference_type">
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.reference") }}
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ movement.reference_type }}
                    <span
                      v-if="movement.reference_id"
                      class="text-gray-500 dark:text-gray-400"
                    >
                      (#{{ movement.reference_id }})
                    </span>
                  </dd>
                </div>

                <div v-if="movement?.notes" class="md:col-span-2">
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.notes") }}
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ movement.notes }}
                  </dd>
                </div>
              </dl>
            </div>

            <!-- Audit Information -->
            <div class="md:col-span-2 space-y-4">
              <h4 class="text-md font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.audit_information") }}
              </h4>

              <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("common.created_at") }}
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ formatDateTime(movement?.created_at) }}
                  </dd>
                </div>

                <div v-if="movement?.user">
                  <dt
                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                  >
                    {{ $t("inventory.created_by") }}
                  </dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ movement.user.name }}
                  </dd>
                </div>
              </dl>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div
          class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end"
        >
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            {{ $t("common.close") }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// import { useI18n } from "vue-i18n";
import { XMarkIcon, ArrowRightIcon, CubeIcon } from "@heroicons/vue/24/outline";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { InventoryMovement } from "@/types";

// Props
interface Props {
  movement: InventoryMovement | null;
}

defineProps<Props>();

// Emits
defineEmits<{
  close: [];
}>();

// const {} = useI18n();
const { formatNumber, formatCurrency } = useNumberFormatter();

// Methods
const formatDateTime = (date: string | undefined) => {
  if (!date) return "-";
  return new Date(date).toLocaleString();
};

const getMovementTypeColor = (type: string | undefined) => {
  if (!type) return "";

  const colors = {
    in: "bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400",
    out: "bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400",
    transfer:
      "bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400",
    adjustment:
      "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400",
    wastage:
      "bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400",
    production:
      "bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400",
  };
  return colors[type as keyof typeof colors] || colors.adjustment;
};
</script>
