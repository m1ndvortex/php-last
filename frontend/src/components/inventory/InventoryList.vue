<template>
  <div class="space-y-6">
    <!-- Filters and Search -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Search -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("common.search") }}
          </label>
          <input
            v-model="searchQuery"
            @input="debouncedSearch"
            type="text"
            :placeholder="$t('inventory.search_placeholder')"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>

        <!-- Category Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("inventory.category") }}
          </label>
          <select
            v-model="filters.category_id"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all_categories") }}</option>
            <option
              v-for="category in inventoryStore.categories"
              :key="category.id"
              :value="category.id"
            >
              {{ category.name }}
            </option>
          </select>
        </div>

        <!-- Location Filter -->
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

        <!-- Status Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("common.status") }}
          </label>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all_statuses") }}</option>
            <option value="active">{{ $t("common.active") }}</option>
            <option value="low_stock">{{ $t("inventory.low_stock") }}</option>
            <option value="expiring">{{ $t("inventory.expiring") }}</option>
            <option value="expired">{{ $t("inventory.expired") }}</option>
          </select>
        </div>
      </div>

      <!-- Gold Purity Filters Row -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <!-- Gold Purity Range Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("inventory.gold_purity_range") }}
          </label>
          <select
            v-model="filters.gold_purity_range"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option
              v-for="range in goldPurityRanges"
              :key="range.label"
              :value="range.label"
            >
              {{ range.label }}
            </option>
          </select>
        </div>

        <!-- Min Gold Purity -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("inventory.gold_purity_min") }}
          </label>
          <input
            v-model.number="filters.gold_purity_min"
            @input="debouncedApplyFilters"
            type="number"
            step="0.001"
            min="0"
            max="24"
            :placeholder="$t('inventory.gold_purity_min')"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>

        <!-- Max Gold Purity -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("inventory.gold_purity_max") }}
          </label>
          <input
            v-model.number="filters.gold_purity_max"
            @input="debouncedApplyFilters"
            type="number"
            step="0.001"
            min="0"
            max="24"
            :placeholder="$t('inventory.gold_purity_max')"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
          <div class="text-sm font-medium text-blue-600 dark:text-blue-400">
            {{ $t("inventory.total_items") }}
          </div>
          <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
            {{ inventoryStore.pagination.total }}
          </div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
          <div class="text-sm font-medium text-green-600 dark:text-green-400">
            {{ $t("inventory.total_value") }}
          </div>
          <div class="text-2xl font-bold text-green-900 dark:text-green-100">
            {{ formatCurrency(inventoryStore.totalInventoryValue) }}
          </div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
          <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
            {{ $t("inventory.low_stock_items") }}
          </div>
          <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
            {{ inventoryStore.lowStockItems.length }}
          </div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
          <div class="text-sm font-medium text-red-600 dark:text-red-400">
            {{ $t("inventory.expiring_items") }}
          </div>
          <div class="text-2xl font-bold text-red-900 dark:text-red-100">
            {{ inventoryStore.expiringItems.length }}
          </div>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.items_list") }}
        </h3>
      </div>

      <!-- Loading State -->
      <div v-if="inventoryStore.loading.items" class="p-6">
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
        v-else-if="inventoryStore.items.length === 0"
        class="p-6 text-center"
      >
        <div class="text-gray-500 dark:text-gray-400">
          {{ $t("inventory.no_items_found") }}
        </div>
      </div>

      <!-- Items Table -->
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
                {{ $t("inventory.sku") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.category") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.location") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.quantity") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.unit_price") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.gold_purity") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("common.status") }}
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
              v-for="item in inventoryStore.items"
              :key="item.id"
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
                      {{ item.localized_name || item.name }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ item.localized_description || item.description }}
                    </div>
                  </div>
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ item.sku }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ item.category?.name || "-" }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ item.location?.name || "-" }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-white">
                  {{ formatNumber(item.quantity) }}
                </div>
                <div
                  v-if="item.is_low_stock"
                  class="text-xs text-red-600 dark:text-red-400"
                >
                  {{ $t("inventory.low_stock") }}
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ formatCurrency(item.unit_price) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                <span v-if="item.gold_purity">
                  {{ formatGoldPurity(item.gold_purity) }}
                </span>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex flex-col space-y-1">
                  <span
                    :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      item.is_active
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                    ]"
                  >
                    {{
                      item.is_active
                        ? $t("common.active")
                        : $t("common.inactive")
                    }}
                  </span>
                  <span
                    v-if="item.is_expiring"
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400"
                  >
                    {{ $t("inventory.expiring") }}
                  </span>
                  <span
                    v-if="item.is_expired"
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400"
                  >
                    {{ $t("inventory.expired") }}
                  </span>
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
              >
                <div class="flex justify-end space-x-2">
                  <button
                    @click="$emit('view-item', item)"
                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="$emit('edit-item', item)"
                    class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                  >
                    <PencilIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="$emit('delete-item', item)"
                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="inventoryStore.pagination.last_page > 1"
        class="px-6 py-4 border-t border-gray-200 dark:border-gray-700"
      >
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700 dark:text-gray-300">
            {{ $t("common.showing") }}
            {{
              (inventoryStore.pagination.current_page - 1) *
                inventoryStore.pagination.per_page +
              1
            }}
            {{ $t("common.to") }}
            {{
              Math.min(
                inventoryStore.pagination.current_page *
                  inventoryStore.pagination.per_page,
                inventoryStore.pagination.total,
              )
            }}
            {{ $t("common.of") }}
            {{ inventoryStore.pagination.total }}
            {{ $t("common.results") }}
          </div>
          <div class="flex space-x-2">
            <button
              @click="changePage(inventoryStore.pagination.current_page - 1)"
              :disabled="inventoryStore.pagination.current_page === 1"
              class="px-3 py-1 text-sm border rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ $t("common.previous") }}
            </button>
            <button
              @click="changePage(inventoryStore.pagination.current_page + 1)"
              :disabled="
                inventoryStore.pagination.current_page ===
                inventoryStore.pagination.last_page
              "
              class="px-3 py-1 text-sm border rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ $t("common.next") }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { debounce } from "lodash-es";
import {
  CubeIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
} from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { useApi } from "@/composables/useApi";
import { apiService } from "@/services/api";
import type { InventoryItem } from "@/types";

// Emits
defineEmits<{
  "view-item": [item: InventoryItem];
  "edit-item": [item: InventoryItem];
  "delete-item": [item: InventoryItem];
}>();

const { locale } = useI18n();
const inventoryStore = useInventoryStore();
const { formatNumber, formatCurrency } = useNumberFormatter();
const { execute } = useApi();

// State
const searchQuery = ref("");
const filters = ref({
  category_id: "",
  location_id: "",
  status: "",
  gold_purity_range: "",
  gold_purity_min: null as number | null,
  gold_purity_max: null as number | null,
});
const goldPurityRanges = ref<Array<{ label: string; min: number; max: number }>>([]);

// Methods
const debouncedSearch = debounce(() => {
  applyFilters();
}, 300);

const debouncedApplyFilters = debounce(() => {
  applyFilters();
}, 500);

const formatGoldPurity = (purity: number): string => {
  if (!purity) return "-";
  
  if (locale.value === "fa") {
    // Convert to Persian numerals
    const persianDigits = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
    const formattedPurity = purity.toFixed(1).replace(/\d/g, (digit) => persianDigits[parseInt(digit)]);
    return `${formattedPurity} عیار`;
  }
  
  return `${purity.toFixed(1)}K`;
};

const fetchGoldPurityOptions = async () => {
  try {
    const result = await execute(() => 
      apiService.get("/inventory/gold-purity-options")
    );
    if (result) {
      goldPurityRanges.value = result.purity_ranges || [];
    }
  } catch (error) {
    console.error("Failed to fetch gold purity options:", error);
  }
};

const applyFilters = () => {
  const params: Record<string, any> = {
    search: searchQuery.value,
    ...filters.value,
    page: 1,
  };

  // Remove empty filters
  Object.keys(params).forEach((key) => {
    if (params[key] === "") {
      delete params[key];
    }
  });

  inventoryStore.fetchItems(params);
};

const changePage = (page: number) => {
  if (page >= 1 && page <= inventoryStore.pagination.last_page) {
    const params: Record<string, any> = {
      search: searchQuery.value,
      ...filters.value,
      page,
    };

    // Remove empty filters
    Object.keys(params).forEach((key) => {
      if (params[key] === "") {
        delete params[key];
      }
    });

    inventoryStore.fetchItems(params);
  }
};

// Lifecycle
onMounted(() => {
  fetchGoldPurityOptions();
  applyFilters();
});
</script>
