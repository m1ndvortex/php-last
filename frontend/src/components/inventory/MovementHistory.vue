<template>
  <div class="space-y-6">
    <!-- Header with Actions -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div class="sm:flex-auto">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.movement_history") }}
        </h2>
        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
          {{ $t("inventory.movement_history_description") }}
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          @click="showCreateMovementModal = true"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          {{ $t("inventory.add_movement") }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Item Search -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("inventory.item") }}
          </label>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            :placeholder="$t('inventory.search_items')"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>

        <!-- Movement Type -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("inventory.movement_type") }}
          </label>
          <select
            v-model="filters.type"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all_types") }}</option>
            <option value="in">{{ $t("inventory.movement_types.in") }}</option>
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
        </div>

        <!-- Location -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("inventory.location") }}
          </label>
          <select
            v-model="filters.location_id"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all_locations") }}</option>
            <option
              v-for="location in inventoryStore.locations"
              :key="location.id"
              :value="location.id"
            >
              {{ location.name }}
            </option>
          </select>
        </div>

        <!-- Date Range -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("common.date_from") }}
          </label>
          <input
            v-model="filters.date_from"
            @change="applyFilters"
            type="date"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Movement Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
        <div class="text-sm font-medium text-green-600 dark:text-green-400">
          {{ $t("inventory.total_inbound") }}
        </div>
        <div class="text-2xl font-bold text-green-900 dark:text-green-100">
          {{ inboundMovements.length }}
        </div>
        <div class="text-sm text-green-600 dark:text-green-400">
          {{ formatCurrency(inboundValue) }}
        </div>
      </div>
      <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
        <div class="text-sm font-medium text-red-600 dark:text-red-400">
          {{ $t("inventory.total_outbound") }}
        </div>
        <div class="text-2xl font-bold text-red-900 dark:text-red-100">
          {{ outboundMovements.length }}
        </div>
        <div class="text-sm text-red-600 dark:text-red-400">
          {{ formatCurrency(outboundValue) }}
        </div>
      </div>
      <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
        <div class="text-sm font-medium text-blue-600 dark:text-blue-400">
          {{ $t("inventory.total_transfers") }}
        </div>
        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
          {{ transferMovements.length }}
        </div>
      </div>
      <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
        <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
          {{ $t("inventory.total_adjustments") }}
        </div>
        <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
          {{ adjustmentMovements.length }}
        </div>
        <div class="text-sm text-yellow-600 dark:text-yellow-400">
          {{ formatCurrency(adjustmentValue) }}
        </div>
      </div>
    </div>

    <!-- Movements Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.movements_list") }}
        </h3>
      </div>

      <!-- Loading State -->
      <div v-if="inventoryStore.loading.movements" class="p-6">
        <div class="animate-pulse space-y-4">
          <div
            v-for="i in 5"
            :key="i"
            class="h-16 bg-gray-200 dark:bg-gray-700 rounded"
          ></div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="inventoryStore.movements.length === 0"
        class="p-6 text-center"
      >
        <ArrowsRightLeftIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.no_movements") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("inventory.no_movements_description") }}
        </p>
      </div>

      <!-- Movements Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.item") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.movement_type") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.quantity") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.locations") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.unit_cost") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.total_value") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("common.date") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("common.actions") }}
              </th>
            </tr>
          </thead>
          <tbody
            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
          >
            <tr
              v-for="movement in inventoryStore.movements"
              :key="movement.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div
                      class="h-10 w-10 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center"
                    >
                      <CubeIcon class="h-6 w-6 text-gray-400" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{
                        movement.inventory_item?.localized_name ||
                        movement.inventory_item?.name
                      }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ movement.inventory_item?.sku }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    getMovementTypeColor(movement.type),
                  ]"
                >
                  {{ $t(`inventory.movement_types.${movement.type}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div
                  :class="[
                    'text-sm font-medium',
                    movement.is_inbound
                      ? 'text-green-600 dark:text-green-400'
                      : movement.is_outbound
                        ? 'text-red-600 dark:text-red-400'
                        : 'text-gray-900 dark:text-white',
                  ]"
                >
                  {{
                    movement.is_inbound ? "+" : movement.is_outbound ? "-" : ""
                  }}{{ formatNumber(movement.quantity) }}
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                <div v-if="movement.type === 'transfer'">
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $t("inventory.from") }}:
                    {{ movement.from_location?.name || "-" }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $t("inventory.to") }}:
                    {{ movement.to_location?.name || "-" }}
                  </div>
                </div>
                <div v-else>
                  {{
                    movement.to_location?.name ||
                    movement.from_location?.name ||
                    "-"
                  }}
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{
                  movement.unit_cost ? formatCurrency(movement.unit_cost) : "-"
                }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{
                  movement.total_value
                    ? formatCurrency(movement.total_value)
                    : "-"
                }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ formatDateTime(movement.movement_date) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
              >
                <div class="flex justify-end space-x-2">
                  <button
                    @click="viewMovement(movement)"
                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Movement Modal -->
    <CreateMovementModal
      v-if="showCreateMovementModal"
      @close="showCreateMovementModal = false"
      @created="handleMovementCreated"
    />

    <!-- Movement Details Modal -->
    <MovementDetailsModal
      v-if="showMovementDetailsModal"
      :movement="selectedMovement"
      @close="showMovementDetailsModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
// import { useI18n } from "vue-i18n";
import { debounce } from "lodash-es";
import {
  PlusIcon,
  EyeIcon,
  CubeIcon,
  ArrowsRightLeftIcon,
} from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { InventoryMovement } from "@/types";

// Components
import CreateMovementModal from "./CreateMovementModal.vue";
import MovementDetailsModal from "./MovementDetailsModal.vue";

// const {} = useI18n();
const inventoryStore = useInventoryStore();
const { formatNumber, formatCurrency } = useNumberFormatter();

// State
const showCreateMovementModal = ref(false);
const showMovementDetailsModal = ref(false);
const selectedMovement = ref<InventoryMovement | null>(null);

const filters = ref({
  search: "",
  type: "",
  location_id: "",
  date_from: "",
});

// Computed
const inboundMovements = computed(() =>
  inventoryStore.movements.filter((m) => m.is_inbound),
);

const outboundMovements = computed(() =>
  inventoryStore.movements.filter((m) => m.is_outbound),
);

const transferMovements = computed(() =>
  inventoryStore.movements.filter((m) => m.is_transfer),
);

const adjustmentMovements = computed(() =>
  inventoryStore.movements.filter((m) => m.type === "adjustment"),
);

const inboundValue = computed(() =>
  inboundMovements.value.reduce((sum, m) => sum + (m.total_value || 0), 0),
);

const outboundValue = computed(() =>
  outboundMovements.value.reduce((sum, m) => sum + (m.total_value || 0), 0),
);

const adjustmentValue = computed(() =>
  adjustmentMovements.value.reduce((sum, m) => sum + (m.total_value || 0), 0),
);

// Methods
const formatDateTime = (date: string) => {
  return new Date(date).toLocaleString();
};

const getMovementTypeColor = (type: string) => {
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

const debouncedSearch = debounce(() => {
  applyFilters();
}, 300);

const applyFilters = () => {
  const params = { ...filters.value };

  // Remove empty filters
  Object.keys(params).forEach((key) => {
    if (params[key as keyof typeof params] === "") {
      delete params[key as keyof typeof params];
    }
  });

  inventoryStore.fetchMovements(params);
};

const viewMovement = (movement: InventoryMovement) => {
  selectedMovement.value = movement;
  showMovementDetailsModal.value = true;
};

const handleMovementCreated = () => {
  showCreateMovementModal.value = false;
  applyFilters();
};

// Lifecycle
onMounted(() => {
  applyFilters();
});
</script>
