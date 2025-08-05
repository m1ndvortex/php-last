<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <form @submit.prevent="handleSubmit">
          <!-- Header -->
          <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                {{ recurringInvoice ? $t("invoices.edit_recurring") : $t("invoices.create_recurring") }}
              </h3>
              <button
                type="button"
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>

            <!-- Form Fields -->
            <div class="space-y-6">
              <!-- Basic Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t("invoices.template_name") }} *
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    :class="{ 'border-red-500': errors.name }"
                  />
                  <p v-if="errors.name" class="mt-1 text-sm text-red-600">
                    {{ errors.name[0] }}
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t("invoices.customer") }} *
                  </label>
                  <select
                    v-model="form.customer_id"
                    required
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    :class="{ 'border-red-500': errors.customer_id }"
                  >
                    <option value="">{{ $t("invoices.select_customer") }}</option>
                    <option
                      v-for="customer in customers"
                      :key="customer.id"
                      :value="customer.id"
                    >
                      {{ customer.name }}
                    </option>
                  </select>
                  <p v-if="errors.customer_id" class="mt-1 text-sm text-red-600">
                    {{ errors.customer_id[0] }}
                  </p>
                </div>
              </div>

              <!-- Frequency Settings -->
              <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                  {{ $t("invoices.frequency_settings") }}
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      {{ $t("invoices.frequency") }} *
                    </label>
                    <select
                      v-model="form.frequency"
                      required
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                      <option value="daily">{{ $t("invoices.frequency_daily") }}</option>
                      <option value="weekly">{{ $t("invoices.frequency_weekly") }}</option>
                      <option value="monthly">{{ $t("invoices.frequency_monthly") }}</option>
                      <option value="quarterly">{{ $t("invoices.frequency_quarterly") }}</option>
                      <option value="yearly">{{ $t("invoices.frequency_yearly") }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      {{ $t("invoices.interval") }} *
                    </label>
                    <input
                      v-model.number="form.interval"
                      type="number"
                      min="1"
                      required
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.interval_description") }}
                    </p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      {{ $t("invoices.start_date") }} *
                    </label>
                    <DatePicker
                      v-model="form.start_date"
                      required
                    />
                  </div>
                </div>
              </div>

              <!-- End Conditions -->
              <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                  {{ $t("invoices.end_conditions") }}
                </h4>
                <div class="space-y-3">
                  <label class="flex items-center">
                    <input
                      v-model="form.end_type"
                      type="radio"
                      value="never"
                      class="text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      {{ $t("invoices.never_end") }}
                    </span>
                  </label>
                  
                  <label class="flex items-center">
                    <input
                      v-model="form.end_type"
                      type="radio"
                      value="date"
                      class="text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      {{ $t("invoices.end_on_date") }}
                    </span>
                  </label>
                  
                  <div v-if="form.end_type === 'date'" class="ml-6">
                    <DatePicker
                      v-model="form.end_date"
                      class="max-w-xs"
                    />
                  </div>
                  
                  <label class="flex items-center">
                    <input
                      v-model="form.end_type"
                      type="radio"
                      value="count"
                      class="text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                      {{ $t("invoices.end_after_count") }}
                    </span>
                  </label>
                  
                  <div v-if="form.end_type === 'count'" class="ml-6">
                    <input
                      v-model.number="form.max_occurrences"
                      type="number"
                      min="1"
                      class="block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.max_occurrences_description") }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Invoice Template -->
              <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                  {{ $t("invoices.invoice_template") }}
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      {{ $t("common.language") }} *
                    </label>
                    <select
                      v-model="form.language"
                      required
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                      <option value="en">{{ $t("common.english") }}</option>
                      <option value="fa">{{ $t("common.persian") }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      {{ $t("invoices.template") }}
                    </label>
                    <select
                      v-model="form.template_id"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                      <option value="">{{ $t("invoices.default_template") }}</option>
                      <option
                        v-for="template in templates"
                        :key="template.id"
                        :value="template.id"
                      >
                        {{ template.name }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Invoice Items -->
              <div>
                <div class="flex items-center justify-between mb-3">
                  <h4 class="text-md font-medium text-gray-900 dark:text-white">
                    {{ $t("invoices.items") }}
                  </h4>
                  <button
                    type="button"
                    @click="addItem"
                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200 dark:bg-primary-900 dark:text-primary-300"
                  >
                    <PlusIcon class="h-4 w-4 mr-1" />
                    {{ $t("invoices.add_item") }}
                  </button>
                </div>

                <!-- Items List -->
                <div class="space-y-3">
                  <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-md"
                  >
                    <div class="flex-1">
                      <input
                        v-model="item.description"
                        type="text"
                        :placeholder="$t('invoices.item_description')"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm"
                      />
                    </div>
                    <div class="w-20">
                      <input
                        v-model.number="item.quantity"
                        type="number"
                        step="0.01"
                        min="0"
                        :placeholder="$t('invoices.quantity')"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm"
                        @input="calculateItemTotal(index)"
                      />
                    </div>
                    <div class="w-24">
                      <input
                        v-model.number="item.unit_price"
                        type="number"
                        step="0.01"
                        min="0"
                        :placeholder="$t('invoices.unit_price')"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white text-sm"
                        @input="calculateItemTotal(index)"
                      />
                    </div>
                    <div class="w-24 text-sm font-medium text-gray-900 dark:text-white">
                      {{ formatCurrency(item.total_price || 0) }}
                    </div>
                    <button
                      type="button"
                      @click="removeItem(index)"
                      class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </div>
                </div>

                <!-- Total -->
                <div class="mt-3 flex justify-end">
                  <div class="text-sm">
                    <span class="text-gray-600 dark:text-gray-400">{{ $t("invoices.total") }}: </span>
                    <span class="font-medium text-gray-900 dark:text-white">
                      {{ formatCurrency(totalAmount) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  {{ $t("invoices.notes") }}
                </label>
                <textarea
                  v-model="form.notes"
                  rows="3"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  :placeholder="$t('invoices.notes_placeholder')"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="loading || form.items.length === 0"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ recurringInvoice ? $t("common.update") : $t("common.create") }}
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
            >
              {{ $t("common.cancel") }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { XMarkIcon, PlusIcon, TrashIcon } from "@heroicons/vue/24/outline";
import { useInvoicesStore } from "@/stores/invoices";
import { useCustomersStore } from "@/stores/customers";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import DatePicker from "@/components/localization/DatePicker.vue";

// Props
interface Props {
  recurringInvoice?: any | null;
}

const props = withDefaults(defineProps<Props>(), {
  recurringInvoice: null,
});

// Emits
const emit = defineEmits<{
  close: [];
  saved: [recurring: any];
}>();

const invoicesStore = useInvoicesStore();
const customersStore = useCustomersStore();
const { formatCurrency } = useNumberFormatter();

// State
const loading = ref(false);
const errors = ref<Record<string, string[]>>({});

const form = ref({
  name: "",
  customer_id: "",
  frequency: "monthly",
  interval: 1,
  start_date: new Date().toISOString().split('T')[0],
  end_type: "never",
  end_date: "",
  max_occurrences: 12,
  language: "en" as "en" | "fa",
  template_id: "",
  notes: "",
  items: [] as any[],
});

// Computed
const customers = computed(() => customersStore.customers);
const templates = computed(() => invoicesStore.templates);

const totalAmount = computed(() => {
  return form.value.items.reduce((sum, item) => sum + (item.total_price || 0), 0);
});

// Methods
const addItem = () => {
  form.value.items.push({
    description: "",
    quantity: 1,
    unit_price: 0,
    total_price: 0,
  });
};

const removeItem = (index: number) => {
  form.value.items.splice(index, 1);
};

const calculateItemTotal = (index: number) => {
  const item = form.value.items[index];
  item.total_price = (item.quantity || 0) * (item.unit_price || 0);
};

const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};

  try {
    const recurringData = {
      ...form.value,
      customer_id: Number(form.value.customer_id),
      template_id: form.value.template_id ? Number(form.value.template_id) : undefined,
      amount: totalAmount.value,
    };

    let savedRecurring;
    if (props.recurringInvoice) {
      // Update existing recurring invoice
      savedRecurring = await invoicesStore.updateRecurringInvoice(props.recurringInvoice.id, recurringData);
    } else {
      savedRecurring = await invoicesStore.createRecurringInvoice(recurringData);
    }

    if (savedRecurring) {
      emit("saved", savedRecurring);
    }
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    console.error("Failed to save recurring invoice:", error);
  } finally {
    loading.value = false;
  }
};

// Initialize form with existing data
const initializeForm = () => {
  if (props.recurringInvoice) {
    form.value = {
      name: props.recurringInvoice.name,
      customer_id: props.recurringInvoice.customer_id?.toString() || "",
      frequency: props.recurringInvoice.frequency,
      interval: props.recurringInvoice.interval,
      start_date: props.recurringInvoice.start_date,
      end_type: props.recurringInvoice.end_type || "never",
      end_date: props.recurringInvoice.end_date || "",
      max_occurrences: props.recurringInvoice.max_occurrences || 12,
      language: props.recurringInvoice.language || "en",
      template_id: props.recurringInvoice.template_id?.toString() || "",
      notes: props.recurringInvoice.notes || "",
      items: props.recurringInvoice.items || [],
    };
  }
};

// Lifecycle
onMounted(async () => {
  await Promise.all([
    customersStore.fetchCustomers(),
    invoicesStore.fetchTemplates(),
  ]);

  initializeForm();
});
</script>