<template>
  <div class="relative">
    <select
      :id="id"
      :value="modelValue"
      @input="handleInput"
      class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
    >
      <option value="">
        {{ placeholder || $t("inventory.categories.select_parent") }}
      </option>
      <option
        v-for="category in availableCategories"
        :key="category.id"
        :value="category.id"
        :disabled="category.id === excludeId"
      >
        {{ getCategoryDisplayName(category) }}
      </option>
    </select>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { useInventoryStore } from "@/stores/inventory";
import type { Category } from "@/types";

interface Props {
  modelValue?: number | null;
  id?: string;
  placeholder?: string;
  excludeId?: number | null;
  initialParentId?: number | null;
}

interface Emits {
  (e: "update:modelValue", value: number | null): void;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  excludeId: null,
  initialParentId: null,
});

const emit = defineEmits<Emits>();

const inventoryStore = useInventoryStore();

// Computed
const availableCategories = computed(() => {
  const categories = inventoryStore.categories || [];

  // Filter out the excluded category and its descendants
  if (props.excludeId) {
    return categories
      .filter((category) => {
        if (category.id === props.excludeId) {
          return false;
        }

        // Check if this category is a descendant of the excluded category
        return !isDescendantOf(category, props.excludeId!, categories);
      })
      .sort((a, b) => {
        // Sort by hierarchy level first, then by sort_order, then by name
        const aLevel = getCategoryLevel(a);
        const bLevel = getCategoryLevel(b);

        if (aLevel !== bLevel) {
          return aLevel - bLevel;
        }

        if (a.sort_order !== b.sort_order) {
          return (a.sort_order || 0) - (b.sort_order || 0);
        }

        return (a.name || "").localeCompare(b.name || "");
      });
  }

  return categories.sort((a, b) => {
    // Sort by hierarchy level first, then by sort_order, then by name
    const aLevel = getCategoryLevel(a);
    const bLevel = getCategoryLevel(b);

    if (aLevel !== bLevel) {
      return aLevel - bLevel;
    }

    if (a.sort_order !== b.sort_order) {
      return (a.sort_order || 0) - (b.sort_order || 0);
    }

    return (a.name || "").localeCompare(b.name || "");
  });
});

// Methods
const handleInput = (event: Event) => {
  const target = event.target as HTMLSelectElement;
  const value = target.value ? parseInt(target.value) : null;
  emit("update:modelValue", value);
};

const getCategoryDisplayName = (category: Category): string => {
  const name = category.localized_name || category.name;
  const level = getCategoryLevel(category);
  const indent = "  ".repeat(level);

  return `${indent}${name}${category.code ? ` (${category.code})` : ""}`;
};

const getCategoryLevel = (category: Category): number => {
  let level = 0;
  let currentCategory = category;
  const categories = inventoryStore.categories || [];

  while (currentCategory.parent_id) {
    level++;
    const parent = categories.find(
      (cat) => cat.id === currentCategory.parent_id,
    );
    if (!parent) break;
    currentCategory = parent;
  }

  return level;
};

const isDescendantOf = (
  category: Category,
  ancestorId: number,
  categories: Category[],
): boolean => {
  let currentCategory = category;

  while (currentCategory.parent_id) {
    if (currentCategory.parent_id === ancestorId) {
      return true;
    }

    const parent = categories.find(
      (cat) => cat.id === currentCategory.parent_id,
    );
    if (!parent) break;
    currentCategory = parent;
  }

  return false;
};

// Lifecycle
onMounted(async () => {
  if (!inventoryStore.categories || inventoryStore.categories.length === 0) {
    await inventoryStore.fetchCategories();
  }
});
</script>
