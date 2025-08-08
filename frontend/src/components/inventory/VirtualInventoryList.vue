<template>
  <div class="space-y-6">
    <!-- Filters and Search (same as original) -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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

    <!-- Virtual Scrolling Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.items_list") }}
        </h3>
      </div>

      <!-- Loading State -->
      <TableSkeleton v-if="inventoryStore.loading.items" :rows="10" :columns="9" />

      <!-- Empty State -->
      <div v-else-if="inventoryStore.items.length === 0" class="p-6 text-center">
        <div class="text-gray-500 dark:text-gray-400">
          {{ $t("inventory.no_items_found") }}
        </div>
      </div>

      <!-- Virtual Scrolling Container -->
      <div v-else class="relative">
        <!-- Table Header (Fixed) -->
        <div class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
          <div class="grid grid-cols-9 gap-4 px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            <div>{{ $t("inventory.item") }}</div>
            <div>{{ $t("inventory.sku") }}</div>
            <div>{{ $t("inventory.category") }}</div>
            <div>{{ $t("inventory.location") }}</div>
            <div>{{ $t("inventory.quantity") }}</div>
            <div>{{ $t("inventory.unit_price") }}</div>
            <div>{{ $t("inventory.gold_purity") }}</div>
            <div>{{ $t("common.status") }}</div>
            <div class="text-right">{{ $t("common.actions") }}</div>
          </div>
        </div>

        <!-- Virtual Scroll Container -->
        <div
          ref="containerRef"
          class="overflow-auto"
          :style="{ height: `${containerHeight}px` }"
          @scroll="handleScroll"
        >
          <!-- Virtual Scroll Content -->
          <div :style="{ height: `${totalHeight}px`, position: 'relative' }">
            <!-- Visible Items -->
            <div
              :style="{ transform: `translateY(${offsetY}px)` }"
              class="absolute top-0 left-0 right-0"
            >
              <div
                v-for="{ item, index } in visibleItems"
                :key="item.id"
                class="grid grid-cols-9 gap-4 px-6 py-4 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                :style="{ height: `${itemHeight}px` }"
              >
                <!-- Item Info -->
                <div class="flex items-center min-w-0">
                  <div class="flex-shrink-0 h-8 w-8 mr-3">
                    <div class="h-8 w-8 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                      <CubeIcon class="h-5 w-5 text-gray-400" />
                    </div>
                  </div>
                  <div class="min-w-0">
                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                      {{ item.localized_name || item.name }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                      {{ item.localized_description || item.description }}
                    </div>
                  </div>
                </div>

                <!-- SKU -->
                <div class="flex items-center text-sm text-gray-900 dark:text-white">
                  {{ item.sku }}
                </div>

                <!-- Category -->
                <div class="flex items-center text-sm text-gray-900 dark:text-white">
                  {{ item.category?.name || "-" }}
                </div>

                <!-- Location -->
                <div class="flex items-center text-sm text-gray-900 dark:text-white">
                  {{ item.location?.name || "-" }}
                </div>

                <!-- Quantity -->
                <div class="flex items-center">
                  <div class="text-sm text-gray-900 dark:text-white">
                    {{ formatNumber(item.quantity) }}
                  </div>
                  <div v-if="item.is_low_stock" class="ml-2 text-xs text-red-600 dark:text-red-400">
                    {{ $t("inventory.low_stock") }}
                  </div>
                </div>

                <!-- Unit Price -->
                <div class="flex items-center text-sm text-gray-900 dark:text-white">
                  {{ formatCurrency(item.unit_price) }}
                </div>

                <!-- Gold Purity -->
                <div class="flex items-center text-sm text-gray-900 dark:text-white">
                  <span v-if="item.gold_purity">
                    {{ formatGoldPurity(item.gold_purity) }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </div>

                <!-- Status -->
                <div class="flex items-center">
                  <div class="flex flex-col space-y-1">
                    <span
                      :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        item.is_active
                          ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                          : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                      ]"
                    >
                      {{ item.is_active ? $t("common.active") : $t("common.inactive") }}
                    </span>
                    <span
                      v-if="item.is_expiring"
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400"
                    >
                      {{ $t("inventory.expiring") }}
                    </span>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-2">
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
              </div>
            </div>
          </div>
        </div>

        <!-- Scroll to Top Button -->
        <Transition name="fade">
          <button
            v-if="scrollTop > 200"
            @click="scrollToTop"
            class="fixed bottom-4 right-4 bg-primary-600 text-white p-3 rounded-full shadow-lg hover:bg-primary-700 transition-colors z-10"
          >
            <ArrowUpIcon class="h-5 w-5" />
          </button>
        </Transition>
      </div>

      <!-- Load More Button (for infinite scroll fallback) -->
      <div v-if="hasMoreItems && !inventoryStore.loading.items" class="p-4 text-center border-t border-gray-200 dark:border-gray-700">
        <button
          @click="loadMoreItems"
          :disabled="loadingMore"
          class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="loadingMore">{{ $t("common.loading") }}...</span>
          <span v-else>{{ $t("common.load_more") }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import {
  CubeIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  ArrowUpIcon,
} from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { useVirtualScrolling } from "@/composables/useVirtualScrolling";
import { usePerformanceMonitoring } from "@/composables/usePerformanceMonitoring";
import TableSkeleton from "@/components/ui/TableSkeleton.vue";
import type { InventoryItem } from "@/types";

// Simple debounce implementation
const debounce = (func: Function, wait: number) => {
  let timeout: NodeJS.Timeout;
  return function executedFunction(...args: any[]) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

// Emits
defineEmits<{
  "view-item": [item: InventoryItem];
  "edit-item": [item: InventoryItem];
  "delete-item": [item: InventoryItem];
}>();

const { locale } = useI18n();
const inventoryStore = useInventoryStore();
const { formatNumber, formatCurrency } = useNumberFormatter();
const { mark, measure } = usePerformanceMonitoring('VirtualInventoryList');

// Virtual scrolling configuration
const itemHeight = 80; // Height of each row in pixels
const containerHeight = 600; // Height of the scrollable container

// Virtual scrolling setup
const {
  containerRef,
  visibleItems,
  totalHeight,
  offsetY,
  scrollTop,
  scrollToTop,
  handleScroll,
} = useVirtualScrolling(inventoryStore.items, {
  itemHeight,
  containerHeight,
  buffer: 5,
});

// State
const searchQuery = ref("");
const filters = ref({
  category_id: "",
  location_id: "",
  status: "",
});
const loadingMore = ref(false);

// Computed
const hasMoreItems = computed(() => {
  return inventoryStore.pagination.current_page < inventoryStore.pagination.last_page;
});

// Methods
const debouncedSearch = debounce(() => {
  mark('search-start');
  applyFilters();
  measure('search-duration', 'search-start');
}, 300);

const formatGoldPurity = (purity: number): string => {
  if (!purity) return "-";
  
  if (locale.value === "fa") {
    const persianDigits = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
    const formattedPurity = purity.toFixed(1).replace(/\d/g, (digit) => persianDigits[parseInt(digit)]);
    return `${formattedPurity} عیار`;
  }
  
  return `${purity.toFixed(1)}K`;
};

const applyFilters = async () => {
  mark('filter-start');
  
  const params: Record<string, any> = {
    search: searchQuery.value,
    ...filters.value,
    page: 1,
    per_page: 100, // Load more items for virtual scrolling
  };

  // Remove empty filters
  Object.keys(params).forEach((key) => {
    if (params[key] === "") {
      delete params[key];
    }
  });

  await inventoryStore.fetchItems(params);
  measure('filter-duration', 'filter-start');
};

const loadMoreItems = async () => {
  if (loadingMore.value || !hasMoreItems.value) return;
  
  loadingMore.value = true;
  mark('load-more-start');
  
  try {
    const params: Record<string, any> = {
      search: searchQuery.value,
      ...filters.value,
      page: inventoryStore.pagination.current_page + 1,
      per_page: 100,
    };

    // Remove empty filters
    Object.keys(params).forEach((key) => {
      if (params[key] === "") {
        delete params[key];
      }
    });

    await inventoryStore.fetchItems(params, true); // Append to existing items
    measure('load-more-duration', 'load-more-start');
  } finally {
    loadingMore.value = false;
  }
};

// Lifecycle
onMounted(() => {
  mark('component-mount');
  applyFilters();
  measure('initial-load', 'component-mount');
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom scrollbar */
.overflow-auto::-webkit-scrollbar {
  width: 8px;
}

.overflow-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Dark mode scrollbar */
.dark .overflow-auto::-webkit-scrollbar-track {
  background: #374151;
}

.dark .overflow-auto::-webkit-scrollbar-thumb {
  background: #6b7280;
}

.dark .overflow-auto::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}
</style>