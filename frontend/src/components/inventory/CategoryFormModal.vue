<template>
  <div
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
  >
    <div
      class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
    >
      <!-- Background overlay -->
      <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="$emit('close')"
      ></div>

      <!-- Modal panel -->
      <div
        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6"
      >
        <form @submit.prevent="handleSubmit">
          <!-- Header -->
          <div class="mb-6">
            <h3
              class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
              id="modal-title"
            >
              {{
                isEdit
                  ? $t("inventory.categories.edit_category")
                  : $t("inventory.categories.add_category")
              }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{
                isEdit
                  ? $t("inventory.categories.edit_category")
                  : $t("inventory.categories.description")
              }}
            </p>
          </div>

          <!-- General Error Message -->
          <div
            v-if="errors.general"
            class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md"
          >
            <p class="text-sm text-red-600 dark:text-red-400">
              {{ errors.general[0] }}
            </p>
          </div>

          <!-- Form Fields -->
          <div class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Category Name -->
              <div>
                <label
                  for="name"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("inventory.categories.name") }} *
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  required
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  :placeholder="$t('inventory.categories.name_placeholder')"
                />
                <p v-if="errors.name" class="mt-1 text-sm text-red-600">
                  {{ errors.name[0] }}
                </p>
              </div>

              <!-- Category Name Persian -->
              <div>
                <label
                  for="name_persian"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("inventory.categories.name_persian") }}
                </label>
                <input
                  id="name_persian"
                  v-model="form.name_persian"
                  type="text"
                  dir="rtl"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  :placeholder="
                    $t('inventory.categories.name_persian_placeholder')
                  "
                />
                <p v-if="errors.name_persian" class="mt-1 text-sm text-red-600">
                  {{ errors.name_persian[0] }}
                </p>
              </div>

              <!-- Category Code -->
              <div>
                <label
                  for="code"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("inventory.categories.code") }} *
                </label>
                <input
                  id="code"
                  v-model="form.code"
                  type="text"
                  required
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  :placeholder="$t('inventory.categories.code_placeholder')"
                />
                <p v-if="errors.code" class="mt-1 text-sm text-red-600">
                  {{ errors.code[0] }}
                </p>
              </div>

              <!-- Parent Category -->
              <div>
                <label
                  for="parent_id"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("inventory.categories.parent_category") }}
                </label>
                <CategorySelector
                  id="parent_id"
                  v-model="form.parent_id"
                  :exclude-id="category?.id"
                  :placeholder="$t('inventory.categories.select_parent')"
                  :initial-parent-id="parentId"
                />
                <p v-if="errors.parent_id" class="mt-1 text-sm text-red-600">
                  {{ errors.parent_id[0] }}
                </p>
              </div>

              <!-- Default Gold Purity -->
              <div>
                <label
                  for="default_gold_purity"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("inventory.categories.default_gold_purity") }}
                </label>
                <GoldPuritySelector
                  id="default_gold_purity"
                  v-model="form.default_gold_purity"
                  :placeholder="
                    $t('inventory.categories.gold_purity_placeholder')
                  "
                />
                <p
                  v-if="errors.default_gold_purity"
                  class="mt-1 text-sm text-red-600"
                >
                  {{ errors.default_gold_purity[0] }}
                </p>
              </div>

              <!-- Sort Order -->
              <div>
                <label
                  for="sort_order"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("inventory.categories.sort_order") }}
                </label>
                <input
                  id="sort_order"
                  v-model.number="form.sort_order"
                  type="number"
                  min="0"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                />
                <p v-if="errors.sort_order" class="mt-1 text-sm text-red-600">
                  {{ errors.sort_order[0] }}
                </p>
              </div>
            </div>

            <!-- Category Image -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("inventory.categories.image") }}
              </label>
              <CategoryImageUpload
                v-model="form.image"
                :current-image="category?.image_path"
                @uploaded="handleImageUploaded"
                @removed="handleImageRemoved"
              />
              <p v-if="errors.image" class="mt-1 text-sm text-red-600">
                {{ errors.image[0] }}
              </p>
            </div>

            <!-- Description -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label
                  for="description"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("inventory.categories.description") }}
                </label>
                <textarea
                  id="description"
                  v-model="form.description"
                  rows="3"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  :placeholder="
                    $t('inventory.categories.description_placeholder')
                  "
                ></textarea>
                <p v-if="errors.description" class="mt-1 text-sm text-red-600">
                  {{ errors.description[0] }}
                </p>
              </div>

              <div>
                <label
                  for="description_persian"
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("inventory.categories.description_persian") }}
                </label>
                <textarea
                  id="description_persian"
                  v-model="form.description_persian"
                  rows="3"
                  dir="rtl"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  :placeholder="
                    $t('inventory.categories.description_persian_placeholder')
                  "
                ></textarea>
                <p
                  v-if="errors.description_persian"
                  class="mt-1 text-sm text-red-600"
                >
                  {{ errors.description_persian[0] }}
                </p>
              </div>
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
              <input
                id="is_active"
                v-model="form.is_active"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label
                for="is_active"
                class="ml-2 block text-sm text-gray-900 dark:text-white"
              >
                {{ $t("inventory.categories.is_active") }}
              </label>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="mt-8 flex justify-end space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              {{ $t("common.cancel") }}
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span
                v-if="loading"
                class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
              >
                <svg
                  class="animate-spin h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  ></circle>
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
              </span>
              {{ isEdit ? $t("common.update") : $t("common.create") }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from "vue";
import { useInventoryStore } from "@/stores/inventory";
import { useI18n } from "vue-i18n";
import type { Category } from "@/types";

// Components
import CategorySelector from "./CategorySelector.vue";
import GoldPuritySelector from "./GoldPuritySelector.vue";
import CategoryImageUpload from "./CategoryImageUpload.vue";

interface Props {
  category?: Category | null;
  isEdit?: boolean;
  parentId?: number | null;
}

interface Emits {
  (e: "close"): void;
  (e: "saved", category: Category): void;
}

const props = withDefaults(defineProps<Props>(), {
  category: null,
  isEdit: false,
  parentId: null,
});

const emit = defineEmits<Emits>();

const inventoryStore = useInventoryStore();
const { t } = useI18n();

// State
const loading = ref(false);
const errors = ref<Record<string, string[]>>({});

const form = reactive({
  name: "",
  name_persian: "",
  code: "",
  description: "",
  description_persian: "",
  parent_id: null as number | null,
  default_gold_purity: null as number | null,
  sort_order: 0,
  is_active: true,
  image: null as File | null,
});

// Methods
const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};

  try {
    // Client-side validation for circular reference
    if (form.parent_id && props.category?.id) {
      if (form.parent_id === props.category.id) {
        errors.value = {
          parent_id: [t("inventory.categories.circular_reference_error")],
        };
        return;
      }

      // Check if the selected parent is a descendant of current category
      if (isDescendantOf(form.parent_id, props.category.id)) {
        errors.value = {
          parent_id: [t("inventory.categories.circular_reference_error")],
        };
        return;
      }
    }

    const formData = new FormData();

    // Add form fields
    Object.entries(form).forEach(([key, value]) => {
      if (value !== null && value !== undefined) {
        if (key === "image" && value instanceof File) {
          formData.append(key, value);
        } else if (typeof value === "boolean") {
          formData.append(key, value ? "1" : "0");
        } else {
          formData.append(key, String(value));
        }
      }
    });

    let savedCategory: Category;

    if (props.isEdit && props.category) {
      savedCategory = await inventoryStore.updateCategory(
        props.category.id,
        formData,
      );
    } else {
      savedCategory = await inventoryStore.createCategory(formData);
    }

    emit("saved", savedCategory);
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else if (error.response?.data?.message) {
      // Handle circular reference error from backend
      if (error.response.data.message.includes("circular")) {
        errors.value = {
          parent_id: [t("inventory.categories.circular_reference_error")],
        };
      } else {
        errors.value = { general: [error.response.data.message] };
      }
    } else {
      console.error("Failed to save category:", error);
      errors.value = { general: [t("common.error")] };
    }
  } finally {
    loading.value = false;
  }
};

const handleImageUploaded = (file: File) => {
  form.image = file;
};

const handleImageRemoved = () => {
  form.image = null;
};

// Helper function to check if a category is a descendant of another
const isDescendantOf = (categoryId: number, ancestorId: number): boolean => {
  const categories = inventoryStore.categories || [];
  let currentCategory = categories.find((cat) => cat.id === categoryId);

  while (currentCategory?.parent_id) {
    if (currentCategory.parent_id === ancestorId) {
      return true;
    }
    currentCategory = categories.find(
      (cat) => cat.id === currentCategory!.parent_id,
    );
  }

  return false;
};

// Initialize form
const initializeForm = () => {
  if (props.isEdit && props.category) {
    form.name = props.category.name || "";
    form.name_persian = props.category.name_persian || "";
    form.code = props.category.code || "";
    form.description = props.category.description || "";
    form.description_persian = props.category.description_persian || "";
    form.parent_id = props.category.parent_id || null;
    form.default_gold_purity = props.category.default_gold_purity || null;
    form.sort_order = props.category.sort_order || 0;
    form.is_active = props.category.is_active ?? true;
  } else {
    // Reset form for new category
    form.name = "";
    form.name_persian = "";
    form.code = "";
    form.description = "";
    form.description_persian = "";
    form.parent_id = props.parentId || null;
    form.default_gold_purity = null;
    form.sort_order = 0;
    form.is_active = true;
    form.image = null;
  }
};

// Watchers
watch(() => props.category, initializeForm, { immediate: true });
watch(
  () => props.parentId,
  (newParentId) => {
    if (!props.isEdit) {
      form.parent_id = newParentId;
    }
  },
);

// Lifecycle
onMounted(() => {
  initializeForm();
});
</script>
