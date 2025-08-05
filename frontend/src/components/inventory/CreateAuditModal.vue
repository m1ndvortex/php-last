<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="fixed inset-0 bg-black bg-opacity-25"
        @click="$emit('close')"
      ></div>

      <div
        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full"
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              {{ $t("inventory.create_audit") }}
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
          <div class="space-y-4">
            <!-- Audit Number -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.audit_number") }} *
              </label>
              <input
                v-model="form.audit_number"
                type="text"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.audit_number
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
                :placeholder="$t('inventory.audit_number_placeholder')"
              />
              <p
                v-if="errors.audit_number"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.audit_number[0] }}
              </p>
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
                <option value="">{{ $t("inventory.all_locations") }}</option>
                <option
                  v-for="location in inventoryStore.locations"
                  :key="location.id"
                  :value="location.id"
                >
                  {{ location.name }}
                </option>
              </select>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t("inventory.location_audit_help") }}
              </p>
            </div>

            <!-- Audit Date -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.audit_date") }} *
              </label>
              <input
                v-model="form.audit_date"
                type="date"
                required
                :class="[
                  'block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm',
                  errors.audit_date
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : '',
                ]"
              />
              <p
                v-if="errors.audit_date"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.audit_date[0] }}
              </p>
            </div>

            <!-- Notes -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("inventory.notes") }}
              </label>
              <textarea
                v-model="form.notes"
                rows="3"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                :placeholder="$t('inventory.audit_notes_placeholder')"
              ></textarea>
            </div>

            <!-- Auto-start option -->
            <div class="flex items-center">
              <input
                v-model="form.auto_start"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label class="ml-2 block text-sm text-gray-900 dark:text-white">
                {{ $t("inventory.auto_start_audit") }}
              </label>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ $t("inventory.auto_start_help") }}
            </p>
          </div>

          <!-- Form Actions -->
          <div class="mt-6 flex justify-end space-x-3">
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
                {{ $t("common.creating") }}
              </span>
              <span v-else>
                {{ $t("inventory.create_audit") }}
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
// import { useI18n } from "vue-i18n";
import { XMarkIcon } from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";

// Emits
const emit = defineEmits<{
  close: [];
  created: [audit: any];
}>();

// const {} = useI18n();
const inventoryStore = useInventoryStore();

// State
const loading = ref(false);
const errors = ref<Record<string, string[]>>({});

// Form data
const form = reactive({
  audit_number: "",
  location_id: "",
  audit_date: new Date().toISOString().split("T")[0],
  notes: "",
  auto_start: false,
});

// Methods
const generateAuditNumber = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  const time =
    String(now.getHours()).padStart(2, "0") +
    String(now.getMinutes()).padStart(2, "0");

  form.audit_number = `AUD-${year}${month}${day}-${time}`;
};

const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};

  try {
    const auditData = {
      ...form,
      location_id: form.location_id || null,
    };

    const result = await inventoryStore.createAudit(auditData);

    if (result && form.auto_start) {
      await inventoryStore.startAudit(result.id);
    }

    emit("created", result);
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      console.error("Failed to create audit:", error);
    }
  } finally {
    loading.value = false;
  }
};

// Lifecycle
onMounted(() => {
  generateAuditNumber();
});
</script>
