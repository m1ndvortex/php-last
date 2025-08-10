<template>
  <div class="space-y-6">
    <!-- Header with Actions -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div class="sm:flex-auto">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.bom_management") }}
        </h2>
        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
          {{ $t("inventory.bom_description") }}
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          @click="showCreateBOMModal = true"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          {{ $t("inventory.create_bom") }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Finished Item Search -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("inventory.finished_item") }}
          </label>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            :placeholder="$t('inventory.search_finished_items')"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("common.status") }}
          </label>
          <select
            v-model="filters.is_active"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all_statuses") }}</option>
            <option value="1">{{ $t("common.active") }}</option>
            <option value="0">{{ $t("common.inactive") }}</option>
          </select>
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
      </div>
    </div>

    <!-- BOM Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
        <div class="text-sm font-medium text-blue-600 dark:text-blue-400">
          {{ $t("inventory.total_boms") }}
        </div>
        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
          {{ inventoryStore.boms.length }}
        </div>
      </div>
      <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
        <div class="text-sm font-medium text-green-600 dark:text-green-400">
          {{ $t("inventory.active_boms") }}
        </div>
        <div class="text-2xl font-bold text-green-900 dark:text-green-100">
          {{ activeBOMs.length }}
        </div>
      </div>
      <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
        <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
          {{ $t("inventory.finished_items") }}
        </div>
        <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
          {{ uniqueFinishedItems.length }}
        </div>
      </div>
      <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
        <div class="text-sm font-medium text-purple-600 dark:text-purple-400">
          {{ $t("inventory.component_items") }}
        </div>
        <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
          {{ uniqueComponentItems.length }}
        </div>
      </div>
    </div>

    <!-- BOMs Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.bom_list") }}
        </h3>
      </div>

      <!-- Loading State -->
      <div v-if="inventoryStore.loading.boms" class="p-6">
        <div class="animate-pulse space-y-4">
          <div
            v-for="i in 5"
            :key="i"
            class="h-16 bg-gray-200 dark:bg-gray-700 rounded"
          ></div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="inventoryStore.boms.length === 0" class="p-6 text-center">
        <RectangleStackIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.no_boms") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("inventory.no_boms_description") }}
        </p>
        <div class="mt-6">
          <button
            @click="showCreateBOMModal = true"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            {{ $t("inventory.create_first_bom") }}
          </button>
        </div>
      </div>

      <!-- BOMs Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.finished_item") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.component_item") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.quantity_required") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.wastage_percentage") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.total_required") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.total_cost") }}
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
              v-for="bom in inventoryStore.boms"
              :key="bom.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div
                      class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center"
                    >
                      <CubeIcon
                        class="h-6 w-6 text-blue-600 dark:text-blue-400"
                      />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{
                        bom.finished_item?.localized_name ||
                        bom.finished_item?.name
                      }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ bom.finished_item?.sku }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8">
                    <div
                      class="h-8 w-8 rounded-lg bg-green-100 dark:bg-green-900/20 flex items-center justify-center"
                    >
                      <CubeIcon
                        class="h-4 w-4 text-green-600 dark:text-green-400"
                      />
                    </div>
                  </div>
                  <div class="ml-3">
                    <div
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{
                        bom.component_item?.localized_name ||
                        bom.component_item?.name
                      }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ bom.component_item?.sku }}
                    </div>
                  </div>
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ formatNumber(bom.quantity_required) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ formatNumber(bom.wastage_percentage) }}%
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"
              >
                {{ formatNumber(bom.total_quantity_required || 0) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ formatCurrency(bom.total_cost || 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    bom.is_active
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                      : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                  ]"
                >
                  {{
                    bom.is_active ? $t("common.active") : $t("common.inactive")
                  }}
                </span>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
              >
                <div class="flex justify-end space-x-2">
                  <button
                    @click="viewBOM(bom)"
                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="editBOM(bom)"
                    class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                  >
                    <PencilIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="deleteBOM(bom)"
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
    </div>

    <!-- Create/Edit BOM Modal -->
    <BOMFormModal
      v-if="showCreateBOMModal || showEditBOMModal"
      :bom="selectedBOM"
      :is-edit="showEditBOMModal"
      @close="closeBOMModals"
      @saved="handleBOMSaved"
    />

    <!-- BOM Details Modal -->
    <BOMDetailsModal
      v-if="showBOMDetailsModal"
      :bom="selectedBOM"
      @close="showBOMDetailsModal = false"
      @edit="editBOM"
      @delete="deleteBOM"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      :title="$t('inventory.delete_bom')"
      :message="$t('inventory.delete_bom_confirmation')"
      :loading="inventoryStore.loading.deleting"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
// import { useI18n } from "vue-i18n";
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
import {
  PlusIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  CubeIcon,
  RectangleStackIcon,
} from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { BillOfMaterial } from "@/types";

// Components
import BOMFormModal from "./BOMFormModal.vue";
import BOMDetailsModal from "./BOMDetailsModal.vue";
import ConfirmationModal from "@/components/ui/ConfirmationModal.vue";

// const {} = useI18n();
const inventoryStore = useInventoryStore();
const { formatNumber, formatCurrency } = useNumberFormatter();

// State
const showCreateBOMModal = ref(false);
const showEditBOMModal = ref(false);
const showBOMDetailsModal = ref(false);
const showDeleteModal = ref(false);
const selectedBOM = ref<BillOfMaterial | null>(null);

const filters = ref({
  search: "",
  is_active: "",
  category_id: "",
});

// Computed
const activeBOMs = computed(() =>
  inventoryStore.boms.filter((bom) => bom.is_active),
);

const uniqueFinishedItems = computed(() => {
  const items = new Set();
  inventoryStore.boms.forEach((bom) => {
    if (bom.finished_item_id) {
      items.add(bom.finished_item_id);
    }
  });
  return Array.from(items);
});

const uniqueComponentItems = computed(() => {
  const items = new Set();
  inventoryStore.boms.forEach((bom) => {
    if (bom.component_item_id) {
      items.add(bom.component_item_id);
    }
  });
  return Array.from(items);
});

// Methods
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

  inventoryStore.fetchBOMs(params);
};

const viewBOM = (bom: BillOfMaterial) => {
  selectedBOM.value = bom;
  showBOMDetailsModal.value = true;
};

const editBOM = (bom: BillOfMaterial) => {
  selectedBOM.value = bom;
  showEditBOMModal.value = true;
  showBOMDetailsModal.value = false;
};

const deleteBOM = (bom: BillOfMaterial) => {
  selectedBOM.value = bom;
  showDeleteModal.value = true;
  showBOMDetailsModal.value = false;
};

const confirmDelete = async () => {
  if (selectedBOM.value) {
    try {
      // Note: Delete method not implemented in store yet
      // await inventoryStore.deleteBOM(selectedBOM.value.id);
      showDeleteModal.value = false;
      selectedBOM.value = null;
      applyFilters();
    } catch (error) {
      console.error("Failed to delete BOM:", error);
    }
  }
};

const closeBOMModals = () => {
  showCreateBOMModal.value = false;
  showEditBOMModal.value = false;
  selectedBOM.value = null;
};

const handleBOMSaved = () => {
  closeBOMModals();
  applyFilters();
};

// Lifecycle
onMounted(() => {
  applyFilters();
});
</script>
