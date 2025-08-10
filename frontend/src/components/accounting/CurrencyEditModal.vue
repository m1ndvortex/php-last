<template>
  <div
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
  >
    <div
      class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white dark:bg-gray-800"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("accounting.edit_currency") }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Currency Code -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("accounting.currency_code") }}
          </label>
          <input
            :value="currency?.code || ''"
            type="text"
            disabled
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50 text-gray-500 dark:bg-gray-600 dark:border-gray-500 dark:text-gray-400 sm:text-sm"
          />
        </div>

        <!-- Currency Name -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("accounting.currency_name") }}
          </label>
          <input
            v-model="form.name"
            type="text"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>

        <!-- Exchange Rate -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("accounting.exchange_rate") }}
          </label>
          <input
            v-model.number="form.rate"
            type="number"
            step="0.000001"
            min="0"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $t("accounting.exchange_rate_help") }}
          </p>
        </div>

        <!-- Active Status -->
        <div class="flex items-center">
          <input
            v-model="form.is_active"
            type="checkbox"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <label class="ml-2 block text-sm text-gray-900 dark:text-white">
            {{ $t("accounting.currency_is_active") }}
          </label>
        </div>

        <!-- Form Actions -->
        <div
          class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700"
        >
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
          >
            {{ $t("common.cancel") }}
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
          >
            <span v-if="loading" class="inline-flex items-center">
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
              {{ $t("common.save") }}
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";

interface Currency {
  code: string;
  name: string;
  rate: number;
  change: number;
  is_active: boolean;
}

interface Props {
  currency: Currency | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  close: [];
  saved: [currency: Currency];
}>();

const loading = ref(false);

const form = reactive({
  name: "",
  rate: 0,
  is_active: true,
});

const handleSubmit = async () => {
  loading.value = true;

  try {
    // In real implementation, this would call an API
    await new Promise((resolve) => setTimeout(resolve, 500));

    const updatedCurrency: Currency = {
      ...(props.currency || {
        code: "",
        name: "",
        rate: 1,
        change: 0,
        is_active: true,
      }),
      name: form.name,
      rate: form.rate,
      is_active: form.is_active,
    };

    emit("saved", updatedCurrency);
  } catch (error) {
    console.error("Failed to save currency:", error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (props.currency) {
    form.name = props.currency.name;
    form.rate = props.currency.rate;
    form.is_active = props.currency.is_active;
  }
});
</script>
