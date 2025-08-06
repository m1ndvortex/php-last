<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="fixed inset-0 bg-black bg-opacity-25"
        @click="$emit('close')"
      ></div>

      <div
        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              {{
                isEdit ? $t("inventory.edit_item") : $t("inventory.add_item")
              }}
            </h3>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="md:col-span-2">
              <h4
                class="text-md font-medium text-gray-900 dark:text-white mb-4"
              >
                {{ $t("inventory.basic_information") }}
              </h4>
            </div>

            <!-- Name -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.name") }} *
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.name
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
                :placeholder="$t('inventory.name_placeholder')"
              />
              <p
                v-if="errors.name"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.name[0] }}
              </p>
            </div>

            <!-- Name Persian -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.name_persian") }}
              </label>
              <input
                v-model="form.name_persian"
                type="text"
                dir="rtl"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                :placeholder="$t('inventory.name_persian_placeholder')"
              />
            </div>

            <!-- SKU -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.sku") }} *
              </label>
              <input
                v-model="form.sku"
                type="text"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.sku
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
                :placeholder="$t('inventory.sku_placeholder')"
              />
              <p
                v-if="errors.sku"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.sku[0] }}
              </p>
            </div>

            <!-- Main Category -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.categories.main_category") }}
              </label>
              <select
                v-model="form.main_category_id"
                @change="onMainCategoryChange"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              >
                <option value="">{{ $t("inventory.categories.select_main_category") }}</option>
                <option
                  v-for="category in mainCategories"
                  :key="category.id"
                  :value="category.id"
                >
                  <span v-if="category.image_path">🖼️ </span>{{ category.localized_name || category.name }}
                </option>
              </select>
            </div>

            <!-- Subcategory -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.categories.subcategory") }}
              </label>
              <select
                v-model="form.category_id"
                @change="onSubcategoryChange"
                :disabled="!form.main_category_id || availableSubcategories.length === 0"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <option value="">{{ $t("inventory.categories.select_subcategory") }}</option>
                <option
                  v-for="subcategory in availableSubcategories"
                  :key="subcategory.id"
                  :value="subcategory.id"
                >
                  <span v-if="subcategory.image_path">🖼️ </span>{{ subcategory.localized_name || subcategory.name }}
                </option>
              </select>
              <p v-if="!form.main_category_id" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.categories.select_main_first") }}
              </p>
              <p v-else-if="availableSubcategories.length === 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.categories.no_subcategories") }}
              </p>
            </div>

            <!-- Description -->
            <div class="md:col-span-2">
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.description") }}
              </label>
              <textarea
                v-model="form.description"
                rows="3"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                :placeholder="$t('inventory.description_placeholder')"
              ></textarea>
            </div>

            <!-- Description Persian -->
            <div class="md:col-span-2">
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.description_persian") }}
              </label>
              <textarea
                v-model="form.description_persian"
                rows="3"
                dir="rtl"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                :placeholder="$t('inventory.description_persian_placeholder')"
              ></textarea>
            </div>

            <!-- Category Path Display -->
            <div v-if="categoryPath" class="md:col-span-2">
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.categories.category_path") }}
              </label>
              <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-md border border-gray-300 dark:border-gray-600">
                <span class="text-sm text-gray-900 dark:text-white">{{ categoryPath }}</span>
              </div>
            </div>

            <!-- Location and Stock Information -->
            <div class="md:col-span-2">
              <h4
                class="text-md font-medium text-gray-900 dark:text-white mb-4 mt-6"
              >
                {{ $t("inventory.location_stock") }}
              </h4>
            </div>

            <!-- Location -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.location") }}
              </label>
              <select
                v-model="form.location_id"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              >
                <option value="">{{ $t("inventory.select_location") }}</option>
                <option
                  v-for="location in inventoryStore.locations"
                  :key="location.id"
                  :value="location.id"
                >
                  {{ location.name }}
                </option>
              </select>
            </div>

            <!-- Quantity -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.quantity") }} *
              </label>
              <input
                v-model.number="form.quantity"
                type="number"
                step="0.001"
                min="0"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.quantity
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              />
              <p
                v-if="errors.quantity"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.quantity[0] }}
              </p>
            </div>

            <!-- Minimum Stock -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.minimum_stock") }}
              </label>
              <input
                v-model.number="form.minimum_stock"
                type="number"
                step="0.001"
                min="0"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>

            <!-- Maximum Stock -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.maximum_stock") }}
              </label>
              <input
                v-model.number="form.maximum_stock"
                type="number"
                step="0.001"
                min="0"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>

            <!-- Pricing Information -->
            <div class="md:col-span-2">
              <h4
                class="text-md font-medium text-gray-900 dark:text-white mb-4 mt-6"
              >
                {{ $t("inventory.pricing") }}
              </h4>
            </div>

            <!-- Unit Price -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.unit_price") }} *
              </label>
              <input
                v-model.number="form.unit_price"
                type="number"
                step="0.01"
                min="0"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.unit_price
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              />
              <p
                v-if="errors.unit_price"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.unit_price[0] }}
              </p>
            </div>

            <!-- Cost Price -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.cost_price") }} *
              </label>
              <input
                v-model.number="form.cost_price"
                type="number"
                step="0.01"
                min="0"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.cost_price
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              />
              <p
                v-if="errors.cost_price"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.cost_price[0] }}
              </p>
            </div>

            <!-- Jewelry Specific Information -->
            <div class="md:col-span-2">
              <h4
                class="text-md font-medium text-gray-900 dark:text-white mb-4 mt-6"
              >
                {{ $t("inventory.jewelry_info") }}
              </h4>
            </div>

            <!-- Gold Purity -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.gold_purity") }}
              </label>
              <input
                v-model.number="form.gold_purity"
                type="number"
                step="0.001"
                min="0"
                max="24"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                :placeholder="selectedCategory?.default_gold_purity ? selectedCategory.default_gold_purity.toString() : '18.000'"
              />
              <p v-if="selectedCategory?.default_gold_purity" class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                {{ $t("inventory.categories.default_from_category", { 
                  purity: formatGoldPurity(selectedCategory.default_gold_purity),
                  category: selectedCategory.localized_name || selectedCategory.name 
                }) }}
              </p>
              <p v-else class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.gold_purity_help") }}
              </p>
            </div>

            <!-- Weight -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.weight") }}
              </label>
              <input
                v-model.number="form.weight"
                type="number"
                step="0.001"
                min="0"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                placeholder="0.000"
              />
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.weight_help") }}
              </p>
            </div>

            <!-- Tracking Information -->
            <div class="md:col-span-2">
              <h4
                class="text-md font-medium text-gray-900 dark:text-white mb-4 mt-6"
              >
                {{ $t("inventory.tracking") }}
              </h4>
            </div>

            <!-- Serial Number -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.serial_number") }}
              </label>
              <input
                v-model="form.serial_number"
                type="text"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                :placeholder="$t('inventory.serial_number_placeholder')"
              />
            </div>

            <!-- Batch Number -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.batch_number") }}
              </label>
              <input
                v-model="form.batch_number"
                type="text"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                :placeholder="$t('inventory.batch_number_placeholder')"
              />
            </div>

            <!-- Expiry Date -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.expiry_date") }}
              </label>
              <input
                v-model="form.expiry_date"
                type="date"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>

            <!-- Track Serial -->
            <div class="flex items-center">
              <input
                v-model="form.track_serial"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label class="ml-2 block text-sm text-gray-900 dark:text-white">
                {{ $t("inventory.track_serial") }}
              </label>
            </div>

            <!-- Track Batch -->
            <div class="flex items-center">
              <input
                v-model="form.track_batch"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label class="ml-2 block text-sm text-gray-900 dark:text-white">
                {{ $t("inventory.track_batch") }}
              </label>
            </div>

            <!-- Is Active -->
            <div class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label class="ml-2 block text-sm text-gray-900 dark:text-white">
                {{ $t("inventory.is_active") }}
              </label>
            </div>

            <!-- Image Upload -->
            <div class="md:col-span-2">
              <h4
                class="text-md font-medium text-gray-900 dark:text-white mb-4 mt-6"
              >
                {{ $t("inventory.images") }}
              </h4>
              <div class="mt-2">
                <div
                  class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md dark:border-gray-600"
                >
                  <div class="space-y-1 text-center">
                    <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <div class="flex text-sm text-gray-600 dark:text-gray-400">
                      <label
                        class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500"
                      >
                        <span>{{ $t("inventory.upload_image") }}</span>
                        <input
                          ref="imageInput"
                          type="file"
                          accept="image/*"
                          multiple
                          class="sr-only"
                          @change="handleImageUpload"
                        />
                      </label>
                      <p class="pl-1">{{ $t("inventory.or_drag_drop") }}</p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ $t("inventory.image_formats") }}
                    </p>
                  </div>
                </div>

                <!-- Image Preview -->
                <div
                  v-if="imagePreview.length > 0"
                  class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4"
                >
                  <div
                    v-for="(image, index) in imagePreview"
                    :key="index"
                    class="relative"
                  >
                    <img
                      :src="image"
                      :alt="`Preview ${index + 1}`"
                      class="h-24 w-24 object-cover rounded-lg"
                    />
                    <button
                      @click="removeImage(index)"
                      type="button"
                      class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                    >
                      <XMarkIcon class="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="mt-8 flex justify-end space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              {{ $t("common.cancel") }}
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="flex items-center">
                <svg
                  class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                {{ $t("common.saving") }}
              </span>
              <span v-else>
                {{ isEdit ? $t("common.update") : $t("common.create") }}
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from "vue";
// import { useI18n } from "vue-i18n";
import { XMarkIcon, PhotoIcon } from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";
import type { InventoryItem } from "@/types";

// Props
interface Props {
  item?: InventoryItem | null;
  isEdit?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  item: null,
  isEdit: false,
});

// Emits
const emit = defineEmits<{
  close: [];
  saved: [item: InventoryItem];
}>();

// const {} = useI18n();
const inventoryStore = useInventoryStore();

// State
const loading = ref(false);
const errors = ref<Record<string, string[]>>({});
const imageInput = ref<HTMLInputElement>();
const imageFiles = ref<File[]>([]);
const imagePreview = ref<string[]>([]);

// Form data
const form = reactive({
  name: "",
  name_persian: "",
  description: "",
  description_persian: "",
  sku: "",
  category_id: "",
  main_category_id: "",
  location_id: "",
  quantity: 0,
  unit_price: 0,
  cost_price: 0,
  gold_purity: null as number | null,
  weight: null as number | null,
  serial_number: "",
  batch_number: "",
  expiry_date: "",
  minimum_stock: null as number | null,
  maximum_stock: null as number | null,
  is_active: true,
  track_serial: false,
  track_batch: false,
});

// Computed
const isEdit = computed(() => props.isEdit && props.item);

// Get main categories (categories without parent)
const mainCategories = computed(() => 
  inventoryStore.categories.filter(category => !category.parent_id)
);

// Get subcategories for selected main category
const availableSubcategories = computed(() => {
  if (!form.main_category_id) return [];
  return inventoryStore.categories.filter(
    category => category.parent_id === parseInt(form.main_category_id)
  );
});

// Get selected category for gold purity auto-population
const selectedCategory = computed(() => {
  if (form.category_id) {
    return inventoryStore.categories.find(cat => cat.id === parseInt(form.category_id));
  }
  if (form.main_category_id) {
    return inventoryStore.categories.find(cat => cat.id === parseInt(form.main_category_id));
  }
  return null;
});

// Get category path for display
const categoryPath = computed(() => {
  const path = [];
  if (form.main_category_id) {
    const mainCat = inventoryStore.categories.find(cat => cat.id === parseInt(form.main_category_id));
    if (mainCat) path.push(mainCat.localized_name || mainCat.name);
  }
  if (form.category_id && form.category_id !== form.main_category_id) {
    const subCat = inventoryStore.categories.find(cat => cat.id === parseInt(form.category_id));
    if (subCat) path.push(subCat.localized_name || subCat.name);
  }
  return path.join(' > ');
});

// Methods
const handleImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const files = target.files;

  if (files) {
    Array.from(files).forEach((file) => {
      if (file.type.startsWith("image/")) {
        imageFiles.value.push(file);

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
          if (e.target?.result) {
            imagePreview.value.push(e.target.result as string);
          }
        };
        reader.readAsDataURL(file);
      }
    });
  }
};

const removeImage = (index: number) => {
  imageFiles.value.splice(index, 1);
  imagePreview.value.splice(index, 1);
};

// Category handling methods
const onMainCategoryChange = () => {
  // Clear subcategory when main category changes
  form.category_id = "";
  
  // Auto-populate gold purity from main category if available
  const mainCategory = inventoryStore.categories.find(cat => cat.id === parseInt(form.main_category_id));
  if (mainCategory?.default_gold_purity && !form.gold_purity) {
    form.gold_purity = mainCategory.default_gold_purity;
  }
};

const onSubcategoryChange = () => {
  // Auto-populate gold purity from subcategory if available
  const subcategory = inventoryStore.categories.find(cat => cat.id === parseInt(form.category_id));
  if (subcategory?.default_gold_purity) {
    form.gold_purity = subcategory.default_gold_purity;
  }
};

// Format gold purity for display
const formatGoldPurity = (purity: number | string): string => {
  // For now, always use English format. Can be enhanced later with i18n
  const numericPurity = typeof purity === 'string' ? parseFloat(purity) : purity;
  return `${numericPurity.toFixed(1)}K`;
};

const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};

  try {
    const formData = new FormData();

    // Add form fields
    Object.entries(form).forEach(([key, value]) => {
      if (value !== null && value !== "") {
        formData.append(key, value.toString());
      }
    });

    // Add images
    imageFiles.value.forEach((file, index) => {
      formData.append(`images[${index}]`, file);
    });

    let result;
    if (isEdit.value && props.item) {
      result = await inventoryStore.updateItem(props.item.id, formData);
    } else {
      result = await inventoryStore.createItem(formData);
    }

    if (result) {
      emit("saved", result);
    }
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      console.error("Failed to save item:", error);
    }
  } finally {
    loading.value = false;
  }
};

// Initialize form with item data if editing
const initializeForm = () => {
  if (props.item) {
    Object.assign(form, {
      name: props.item.name || "",
      name_persian: props.item.name_persian || "",
      description: props.item.description || "",
      description_persian: props.item.description_persian || "",
      sku: props.item.sku || "",
      category_id: props.item.category_id || "",
      main_category_id: props.item.main_category_id || "",
      location_id: props.item.location_id || "",
      quantity: props.item.quantity || 0,
      unit_price: props.item.unit_price || 0,
      cost_price: props.item.cost_price || 0,
      gold_purity: props.item.gold_purity || null,
      weight: props.item.weight || null,
      serial_number: props.item.serial_number || "",
      batch_number: props.item.batch_number || "",
      expiry_date: props.item.expiry_date || "",
      minimum_stock: props.item.minimum_stock || null,
      maximum_stock: props.item.maximum_stock || null,
      is_active: props.item.is_active ?? true,
      track_serial: props.item.track_serial || false,
      track_batch: props.item.track_batch || false,
    });
  }
};

// Watch for item changes
watch(() => props.item, initializeForm, { immediate: true });

// Lifecycle
onMounted(async () => {
  // Ensure categories are loaded
  if (inventoryStore.categories.length === 0) {
    await inventoryStore.fetchCategories();
  }
  initializeForm();
});
</script>
