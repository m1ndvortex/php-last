<template>
  <div class="category-node">
    <!-- Category Row -->
    <div
      :class="[
        'flex items-center justify-between p-3 rounded-lg border transition-colors',
        'hover:bg-gray-50 dark:hover:bg-gray-700',
        'border-gray-200 dark:border-gray-600',
        dragOver
          ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
          : '',
      ]"
      :style="{ marginLeft: `${level * 24}px` }"
      draggable="true"
      @dragstart="handleDragStart"
      @dragover="handleDragOver"
      @dragleave="handleDragLeave"
      @drop="handleDrop"
    >
      <div class="flex items-center space-x-3 flex-1">
        <!-- Expand/Collapse Button -->
        <button
          v-if="hasChildren"
          @click="$emit('toggle-expand', category.id)"
          class="flex-shrink-0 p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
        >
          <ChevronRightIcon
            :class="[
              'h-4 w-4 text-gray-500 transition-transform',
              expanded ? 'transform rotate-90' : '',
            ]"
          />
        </button>
        <div v-else class="w-6"></div>

        <!-- Category Image -->
        <div class="flex-shrink-0">
          <img
            v-if="category.image_path"
            :src="category.image_path"
            :alt="category.localized_name"
            class="h-8 w-8 rounded object-cover"
          />
          <div
            v-else
            class="h-8 w-8 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center"
          >
            <FolderIcon class="h-4 w-4 text-gray-500" />
          </div>
        </div>

        <!-- Category Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center space-x-2">
            <h4
              class="text-sm font-medium text-gray-900 dark:text-white truncate"
            >
              {{ category.localized_name }}
            </h4>
            <span
              v-if="category.code"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
            >
              {{ category.code }}
            </span>
            <span
              v-if="!category.is_active"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300"
            >
              {{ $t("common.inactive") }}
            </span>
          </div>
          <div
            class="flex items-center space-x-4 mt-1 text-xs text-gray-500 dark:text-gray-400"
          >
            <span v-if="category.item_count">
              {{ $t("inventory.categories.item_count") }}:
              {{ category.item_count }}
            </span>
            <span v-if="category.subcategory_count">
              {{ $t("inventory.categories.subcategory_count") }}:
              {{ category.subcategory_count }}
            </span>
            <span v-if="category.default_gold_purity">
              {{ $t("inventory.gold_purity") }}:
              {{ formatGoldPurity(category.default_gold_purity) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center space-x-2">
        <!-- Create Subcategory -->
        <button
          @click="$emit('create-subcategory', category)"
          class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
          :title="$t('inventory.categories.create_subcategory')"
        >
          <PlusIcon class="h-4 w-4" />
        </button>

        <!-- Edit -->
        <button
          @click="$emit('edit', category)"
          class="p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600"
          :title="$t('inventory.categories.edit_category')"
        >
          <PencilIcon class="h-4 w-4" />
        </button>

        <!-- Delete -->
        <button
          @click="$emit('delete', category)"
          class="p-1 rounded text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/20"
          :title="$t('inventory.categories.delete_category')"
          :disabled="
            (category.item_count || 0) > 0 ||
            (category.subcategory_count || 0) > 0
          "
        >
          <TrashIcon class="h-4 w-4" />
        </button>

        <!-- Drag Handle -->
        <div
          class="drag-handle p-1 cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <Bars3Icon class="h-4 w-4" />
        </div>
      </div>
    </div>

    <!-- Children -->
    <div v-if="expanded && hasChildren" class="mt-2 space-y-2">
      <CategoryTreeNode
        v-for="child in children"
        :key="child.id"
        :category="child"
        :level="level + 1"
        :expanded="expandedNodes.has(child.id)"
        :all-categories="allCategories"
        :expanded-nodes="expandedNodes"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
        @toggle-expand="$emit('toggle-expand', $event)"
        @create-subcategory="$emit('create-subcategory', $event)"
        @reorder="$emit('reorder', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import {
  ChevronRightIcon,
  FolderIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  Bars3Icon,
} from "@heroicons/vue/24/outline";
import type { Category } from "@/types";

interface Props {
  category: Category;
  level: number;
  expanded: boolean;
  allCategories: Category[];
  expandedNodes?: Set<number>;
}

interface Emits {
  (e: "edit", category: Category): void;
  (e: "delete", category: Category): void;
  (e: "toggle-expand", categoryId: number): void;
  (e: "create-subcategory", category: Category): void;
  (e: "reorder", orderData: any): void;
}

const props = withDefaults(defineProps<Props>(), {
  expandedNodes: () => new Set<number>(),
});

const emit = defineEmits<Emits>();

// State
const dragOver = ref(false);

// Computed
const children = computed(() => {
  return props.allCategories.filter(
    (cat) => cat.parent_id === props.category.id,
  );
});

const hasChildren = computed(() => {
  return children.value.length > 0;
});

// Methods
const formatGoldPurity = (purity: number): string => {
  return `${purity}K`;
};

const handleDragStart = (event: DragEvent) => {
  if (event.dataTransfer) {
    event.dataTransfer.setData(
      "text/plain",
      JSON.stringify({
        id: props.category.id,
        type: "category",
      }),
    );
    event.dataTransfer.effectAllowed = "move";
  }
};

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();

  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = "move";
  }

  dragOver.value = true;
};

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();
  dragOver.value = false;
};

const handleDrop = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();
  dragOver.value = false;

  if (event.dataTransfer) {
    try {
      const data = JSON.parse(event.dataTransfer.getData("text/plain"));
      if (data.type === "category" && data.id !== props.category.id) {
        // Emit reorder event
        const orderData = {
          categoryId: data.id,
          newParentId: props.category.id,
          newPosition: 0, // Could be calculated based on drop position
        };

        // Prevent circular references
        if (!isCircularReference(data.id, props.category.id)) {
          emit("reorder", orderData);
        }
      }
    } catch (error) {
      console.error("Error parsing drag data:", error);
    }
  }
};

const isCircularReference = (
  draggedId: number,
  targetParentId: number,
): boolean => {
  // Check if the target is a descendant of the dragged category
  const checkDescendant = (categoryId: number): boolean => {
    const descendants = props.allCategories.filter(
      (cat) => cat.parent_id === categoryId,
    );
    return descendants.some(
      (desc) => desc.id === targetParentId || checkDescendant(desc.id),
    );
  };

  return checkDescendant(draggedId);
};
</script>

<style scoped>
.category-node {
  position: relative;
}

.drag-handle {
  cursor: grab;
}

.drag-handle:active {
  cursor: grabbing;
}
</style>
