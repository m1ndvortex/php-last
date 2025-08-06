<template>
  <div class="space-y-6">
    <!-- Category Actions Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div class="sm:flex-auto">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.categories.title") }}
        </h2>
        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
          {{ $t("inventory.categories.description") }}
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:flex-none space-x-3 flex items-center">
        <!-- Language indicator -->
        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ currentLanguage === 'fa' ? 'فارسی' : 'English' }}
        </div>
        <button
          @click="showCreateModal = true"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          {{ $t("inventory.categories.add_category") }}
        </button>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search -->
          <div>
            <label for="search" class="sr-only">{{
              $t("inventory.categories.search_placeholder")
            }}</label>
            <div class="relative">
              <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
              >
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input
                id="search"
                v-model="searchQuery"
                type="text"
                :placeholder="$t('inventory.categories.search_placeholder')"
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 text-sm"
              />
            </div>
          </div>

          <!-- Filter by Parent -->
          <div>
            <label for="parent-filter" class="sr-only">{{
              $t("inventory.categories.filter_by_parent")
            }}</label>
            <select
              id="parent-filter"
              v-model="parentFilter"
              class="block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 text-sm"
            >
              <option value="">
                {{ $t("inventory.categories.show_all") }}
              </option>
              <option value="main">
                {{ $t("inventory.categories.show_main_only") }}
              </option>
              <option value="with_subcategories">
                {{ $t("inventory.categories.show_subcategories") }}
              </option>
            </select>
          </div>

          <!-- Tree Actions -->
          <div class="flex space-x-2">
            <button
              @click="expandAll"
              type="button"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              {{ $t("inventory.categories.expand_all") }}
            </button>
            <button
              @click="collapseAll"
              type="button"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              {{ $t("inventory.categories.collapse_all") }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Tree View -->
    <CategoryTree
      :categories="filteredCategories"
      :expanded-nodes="expandedNodes"
      :loading="inventoryStore.loading.categories"
      @edit="editCategory"
      @delete="deleteCategory"
      @reorder="reorderCategories"
      @toggle-expand="toggleExpand"
      @create-subcategory="createSubcategory"
    />

    <!-- Category Form Modal -->
    <CategoryFormModal
      v-if="showCreateModal || showEditModal"
      :category="selectedCategory"
      :is-edit="showEditModal"
      :parent-id="parentCategoryId"
      @close="closeModals"
      @saved="handleCategorySaved"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      :title="$t('inventory.categories.delete_category')"
      :message="
        $t('inventory.categories.delete_confirmation', {
          name: selectedCategory?.name,
        })
      "
      :loading="inventoryStore.loading.deleting"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { PlusIcon, MagnifyingGlassIcon } from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";
import { useLocale } from "@/composables/useLocale";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { Category } from "@/types";

// Components
import CategoryTree from "./CategoryTree.vue";
import CategoryFormModal from "./CategoryFormModal.vue";
import ConfirmationModal from "@/components/ui/ConfirmationModal.vue";

const inventoryStore = useInventoryStore();
const { currentLanguage, isRTL, getLocalizedCategoryName } = useLocale();
const { formatCategoryData } = useNumberFormatter();

// State
const searchQuery = ref("");
const parentFilter = ref("");
const expandedNodes = ref<Set<number>>(new Set());
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedCategory = ref<Category | null>(null);
const parentCategoryId = ref<number | null>(null);

// Computed
const filteredCategories = computed(() => {
  let categories = inventoryStore.categories || [];

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    categories = categories.filter(
      (category) =>
        category.name?.toLowerCase().includes(query) ||
        category.name_persian?.toLowerCase().includes(query) ||
        category.code?.toLowerCase().includes(query),
    );
  }

  // Apply parent filter
  if (parentFilter.value === "main") {
    categories = categories.filter((category) => !category.parent_id);
  } else if (parentFilter.value === "with_subcategories") {
    categories = categories.filter((category) => category.has_children);
  }

  return categories;
});

// Methods
const editCategory = (category: Category) => {
  selectedCategory.value = category;
  showEditModal.value = true;
};

const deleteCategory = (category: Category) => {
  selectedCategory.value = category;
  showDeleteModal.value = true;
};

const createSubcategory = (parentCategory: Category) => {
  parentCategoryId.value = parentCategory.id;
  showCreateModal.value = true;
};

const confirmDelete = async () => {
  if (selectedCategory.value) {
    try {
      await inventoryStore.deleteCategory(selectedCategory.value.id);
      showDeleteModal.value = false;
      selectedCategory.value = null;
    } catch (error) {
      console.error("Failed to delete category:", error);
    }
  }
};

const reorderCategories = async (orderData: any) => {
  try {
    await inventoryStore.reorderCategories(orderData);
  } catch (error) {
    console.error("Failed to reorder categories:", error);
  }
};

const toggleExpand = (categoryId: number) => {
  if (expandedNodes.value.has(categoryId)) {
    expandedNodes.value.delete(categoryId);
  } else {
    expandedNodes.value.add(categoryId);
  }
};

const expandAll = () => {
  const allCategoryIds = inventoryStore.categories?.map((cat) => cat.id) || [];
  expandedNodes.value = new Set(allCategoryIds);
};

const collapseAll = () => {
  expandedNodes.value.clear();
};

const handleCategorySaved = () => {
  closeModals();
  inventoryStore.fetchCategories();
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedCategory.value = null;
  parentCategoryId.value = null;
};

// Lifecycle
onMounted(async () => {
  await inventoryStore.fetchCategories();
});
</script>
