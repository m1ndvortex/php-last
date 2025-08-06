<template>
  <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-8">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"
        ></div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="!categories || categories.length === 0"
        class="text-center py-8"
      >
        <FolderIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.categories.no_categories_found") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("inventory.categories.description") }}
        </p>
      </div>

      <!-- Category Tree -->
      <div v-else class="space-y-2">
        <CategoryTreeNode
          v-for="category in rootCategories"
          :key="category.id"
          :category="category"
          :level="0"
          :expanded="expandedNodes.has(category.id)"
          :all-categories="categories"
          @edit="$emit('edit', $event)"
          @delete="$emit('delete', $event)"
          @toggle-expand="$emit('toggle-expand', $event)"
          @create-subcategory="$emit('create-subcategory', $event)"
          @reorder="$emit('reorder', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { FolderIcon } from "@heroicons/vue/24/outline";
import type { Category } from "@/types";

// Components
import CategoryTreeNode from "./CategoryTreeNode.vue";

interface Props {
  categories: Category[];
  expandedNodes: Set<number>;
  loading?: boolean;
}

interface Emits {
  (e: "edit", category: Category): void;
  (e: "delete", category: Category): void;
  (e: "toggle-expand", categoryId: number): void;
  (e: "create-subcategory", category: Category): void;
  (e: "reorder", orderData: any): void;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
});

defineEmits<Emits>();

// Computed
const rootCategories = computed(() => {
  return props.categories.filter((category) => !category.parent_id);
});
</script>
