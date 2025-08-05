<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ $t("inventory.title") }}
        </h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
          {{ $t("inventory.description") }}
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          @click="showCreateModal = true"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          {{ $t("inventory.add_item") }}
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === tab.key
              ? 'border-primary-500 text-primary-600 dark:text-primary-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
          ]"
        >
          {{ $t(tab.label) }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
      <!-- Items List Tab -->
      <div v-if="activeTab === 'items'">
        <InventoryList
          @edit-item="editItem"
          @view-item="viewItem"
          @delete-item="deleteItem"
        />
      </div>

      <!-- Stock Audit Tab -->
      <div v-if="activeTab === 'audit'">
        <StockAuditInterface />
      </div>

      <!-- Movement History Tab -->
      <div v-if="activeTab === 'movements'">
        <MovementHistory />
      </div>

      <!-- BOM Management Tab -->
      <div v-if="activeTab === 'bom'">
        <BOMManagement />
      </div>
    </div>

    <!-- Create/Edit Item Modal -->
    <ItemFormModal
      v-if="showCreateModal || showEditModal"
      :item="selectedItem"
      :is-edit="showEditModal"
      @close="closeModals"
      @saved="handleItemSaved"
    />

    <!-- Item Details Modal -->
    <ItemDetailsModal
      v-if="showDetailsModal"
      :item="selectedItem"
      @close="closeModals"
      @edit="editItem"
      @delete="deleteItem"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      :title="$t('inventory.delete_item')"
      :message="
        $t('inventory.delete_confirmation', { name: selectedItem?.name })
      "
      :loading="inventoryStore.loading.deleting"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
// import { useI18n } from "vue-i18n";
import { PlusIcon } from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";
import type { InventoryItem } from "@/types";

// Components
import InventoryList from "@/components/inventory/InventoryList.vue";
import StockAuditInterface from "@/components/inventory/StockAuditInterface.vue";
import MovementHistory from "@/components/inventory/MovementHistory.vue";
import BOMManagement from "@/components/inventory/BOMManagement.vue";
import ItemFormModal from "@/components/inventory/ItemFormModal.vue";
import ItemDetailsModal from "@/components/inventory/ItemDetailsModal.vue";
import ConfirmationModal from "@/components/ui/ConfirmationModal.vue";

// const {} = useI18n();
const inventoryStore = useInventoryStore();

// State
const activeTab = ref("items");
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDetailsModal = ref(false);
const showDeleteModal = ref(false);
const selectedItem = ref<InventoryItem | null>(null);

// Tabs configuration
const tabs = [
  { key: "items", label: "inventory.tabs.items" },
  { key: "audit", label: "inventory.tabs.stock_audit" },
  { key: "movements", label: "inventory.tabs.movements" },
  { key: "bom", label: "inventory.tabs.bom" },
];

// Methods
const editItem = (item: InventoryItem) => {
  selectedItem.value = item;
  showEditModal.value = true;
  showDetailsModal.value = false;
};

const viewItem = (item: InventoryItem) => {
  selectedItem.value = item;
  showDetailsModal.value = true;
};

const deleteItem = (item: InventoryItem) => {
  selectedItem.value = item;
  showDeleteModal.value = true;
  showDetailsModal.value = false;
};

const confirmDelete = async () => {
  if (selectedItem.value) {
    try {
      await inventoryStore.deleteItem(selectedItem.value.id);
      showDeleteModal.value = false;
      selectedItem.value = null;
    } catch (error) {
      console.error("Failed to delete item:", error);
    }
  }
};

const handleItemSaved = () => {
  closeModals();
  // Refresh the items list
  inventoryStore.fetchItems();
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  showDetailsModal.value = false;
  selectedItem.value = null;
};

// Lifecycle
onMounted(async () => {
  await Promise.all([
    inventoryStore.fetchItems(),
    inventoryStore.fetchCategories(),
    inventoryStore.fetchLocations(),
  ]);
});
</script>
