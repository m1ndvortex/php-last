<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
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
            >
              {{
                isEdit
                  ? $t("customers.edit_customer")
                  : $t("customers.add_customer")
              }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{
                isEdit
                  ? $t("customers.edit_description")
                  : $t("customers.add_description")
              }}
            </p>
          </div>

          <!-- Form Fields -->
          <div class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Name -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.name") }} *
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.name
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                />
                <p
                  v-if="errors.name"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.name[0] }}
                </p>
              </div>

              <!-- Email -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.email") }}
                </label>
                <input
                  v-model="form.email"
                  type="email"
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.email
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                />
                <p
                  v-if="errors.email"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.email[0] }}
                </p>
              </div>

              <!-- Phone -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.phone") }}
                </label>
                <input
                  v-model="form.phone"
                  type="tel"
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.phone
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                />
                <p
                  v-if="errors.phone"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.phone[0] }}
                </p>
              </div>

              <!-- Customer Type -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.customer_type") }} *
                </label>
                <select
                  v-model="form.customer_type"
                  required
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.customer_type
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                >
                  <option value="retail">
                    {{ $t("customers.types.retail") }}
                  </option>
                  <option value="wholesale">
                    {{ $t("customers.types.wholesale") }}
                  </option>
                  <option value="vip">{{ $t("customers.types.vip") }}</option>
                </select>
                <p
                  v-if="errors.customer_type"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.customer_type[0] }}
                </p>
              </div>

              <!-- Preferred Language -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.preferred_language") }}
                </label>
                <select
                  v-model="form.preferred_language"
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.preferred_language
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                >
                  <option value="en">{{ $t("languages.english") }}</option>
                  <option value="fa">{{ $t("languages.persian") }}</option>
                </select>
                <p
                  v-if="errors.preferred_language"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.preferred_language[0] }}
                </p>
              </div>

              <!-- CRM Stage -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.crm_stage") }}
                </label>
                <select
                  v-model="form.crm_stage"
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.crm_stage
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                >
                  <option value="lead">
                    {{ $t("customers.stages.lead") }}
                  </option>
                  <option value="prospect">
                    {{ $t("customers.stages.prospect") }}
                  </option>
                  <option value="customer">
                    {{ $t("customers.stages.customer") }}
                  </option>
                  <option value="inactive">
                    {{ $t("customers.stages.inactive") }}
                  </option>
                </select>
                <p
                  v-if="errors.crm_stage"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.crm_stage[0] }}
                </p>
              </div>
            </div>

            <!-- Address -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
              >
                {{ $t("customers.address") }}
              </label>
              <textarea
                v-model="form.address"
                rows="3"
                :class="[
                  'block w-full rounded-md shadow-sm sm:text-sm',
                  errors.address
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                ]"
                class="dark:bg-gray-700 dark:text-white"
              ></textarea>
              <p
                v-if="errors.address"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.address[0] }}
              </p>
            </div>

            <!-- Financial Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Credit Limit -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.credit_limit") }}
                </label>
                <input
                  v-model.number="form.credit_limit"
                  type="number"
                  step="0.01"
                  min="0"
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.credit_limit
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                />
                <p
                  v-if="errors.credit_limit"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.credit_limit[0] }}
                </p>
              </div>

              <!-- Payment Terms -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.payment_terms") }} ({{ $t("common.days") }})
                </label>
                <input
                  v-model.number="form.payment_terms"
                  type="number"
                  min="0"
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.payment_terms
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                />
                <p
                  v-if="errors.payment_terms"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.payment_terms[0] }}
                </p>
              </div>
            </div>

            <!-- Personal Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Birthday -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.birthday") }}
                </label>
                <input
                  v-model="form.birthday"
                  type="date"
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.birthday
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                />
                <p
                  v-if="errors.birthday"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.birthday[0] }}
                </p>
              </div>

              <!-- Anniversary -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.anniversary") }}
                </label>
                <input
                  v-model="form.anniversary"
                  type="date"
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.anniversary
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                />
                <p
                  v-if="errors.anniversary"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.anniversary[0] }}
                </p>
              </div>
            </div>

            <!-- Communication Preferences -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Preferred Communication Method -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.preferred_communication") }}
                </label>
                <select
                  v-model="form.preferred_communication_method"
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.preferred_communication_method
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                >
                  <option value="">{{ $t("common.select") }}</option>
                  <option value="email">{{ $t("communication.email") }}</option>
                  <option value="sms">{{ $t("communication.sms") }}</option>
                  <option value="whatsapp">
                    {{ $t("communication.whatsapp") }}
                  </option>
                  <option value="phone">{{ $t("communication.phone") }}</option>
                </select>
                <p
                  v-if="errors.preferred_communication_method"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.preferred_communication_method[0] }}
                </p>
              </div>

              <!-- Lead Source -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                >
                  {{ $t("customers.lead_source") }}
                </label>
                <select
                  v-model="form.lead_source"
                  :class="[
                    'block w-full rounded-md shadow-sm sm:text-sm',
                    errors.lead_source
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                      : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                  ]"
                  class="dark:bg-gray-700 dark:text-white"
                >
                  <option value="">{{ $t("common.select") }}</option>
                  <option value="referral">
                    {{ $t("customers.lead_sources.referral") }}
                  </option>
                  <option value="website">
                    {{ $t("customers.lead_sources.website") }}
                  </option>
                  <option value="social_media">
                    {{ $t("customers.lead_sources.social_media") }}
                  </option>
                  <option value="walk_in">
                    {{ $t("customers.lead_sources.walk_in") }}
                  </option>
                  <option value="advertisement">
                    {{ $t("customers.lead_sources.advertisement") }}
                  </option>
                  <option value="other">
                    {{ $t("customers.lead_sources.other") }}
                  </option>
                </select>
                <p
                  v-if="errors.lead_source"
                  class="mt-1 text-sm text-red-600 dark:text-red-400"
                >
                  {{ errors.lead_source[0] }}
                </p>
              </div>
            </div>

            <!-- Status -->
            <div class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label class="ml-2 block text-sm text-gray-900 dark:text-white">
                {{ $t("customers.is_active") }}
              </label>
            </div>

            <!-- Notes -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
              >
                {{ $t("customers.notes") }}
              </label>
              <textarea
                v-model="form.notes"
                rows="4"
                :placeholder="$t('customers.notes_placeholder')"
                :class="[
                  'block w-full rounded-md shadow-sm sm:text-sm',
                  errors.notes
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
                ]"
                class="dark:bg-gray-700 dark:text-white"
              ></textarea>
              <p
                v-if="errors.notes"
                class="mt-1 text-sm text-red-600 dark:text-red-400"
              >
                {{ errors.notes[0] }}
              </p>
            </div>
          </div>

          <!-- Actions -->
          <div class="mt-6 flex items-center justify-end space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              {{ $t("common.cancel") }}
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <div
                v-if="loading"
                class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"
              ></div>
              {{ isEdit ? $t("common.update") : $t("common.create") }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useCustomersStore } from "@/stores/customers";
import type { Customer } from "@/types";

// Props & Emits
interface Props {
  customer?: Customer | null;
  isEdit?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  customer: null,
  isEdit: false,
});

const emit = defineEmits<{
  close: [];
  saved: [];
}>();

const customersStore = useCustomersStore();

// State
const loading = computed(() =>
  props.isEdit
    ? customersStore.loading.updating
    : customersStore.loading.creating,
);

const errors = ref<Record<string, string[]>>({});

const form = reactive({
  name: "",
  email: "",
  phone: "",
  address: "",
  preferred_language: "en" as "en" | "fa",
  customer_type: "retail" as "retail" | "wholesale" | "vip",
  credit_limit: 0,
  payment_terms: 30,
  notes: "",
  birthday: "",
  anniversary: "",
  preferred_communication_method: "" as
    | ""
    | "email"
    | "sms"
    | "whatsapp"
    | "phone",
  is_active: true,
  crm_stage: "lead" as "lead" | "prospect" | "customer" | "inactive",
  lead_source: "" as
    | ""
    | "referral"
    | "website"
    | "social_media"
    | "walk_in"
    | "advertisement"
    | "other",
});

// Methods
const handleSubmit = async () => {
  errors.value = {};

  try {
    // Create a properly typed customer data object
    const customerData: Partial<Customer> = {
      name: form.name,
      email: form.email || undefined,
      phone: form.phone || undefined,
      address: form.address || undefined,
      preferred_language: form.preferred_language,
      customer_type: form.customer_type,
      credit_limit: form.credit_limit,
      payment_terms: form.payment_terms,
      notes: form.notes || undefined,
      birthday: form.birthday || undefined,
      anniversary: form.anniversary || undefined,
      preferred_communication_method:
        form.preferred_communication_method === ""
          ? undefined
          : form.preferred_communication_method,
      is_active: form.is_active,
      crm_stage: form.crm_stage,
      lead_source: form.lead_source === "" ? undefined : form.lead_source,
    };

    if (props.isEdit && props.customer) {
      await customersStore.updateCustomer(props.customer.id, customerData);
    } else {
      await customersStore.createCustomer(customerData);
    }

    // Emit saved event to parent
    emit("saved");
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    console.error("Failed to save customer:", error);
  }
};

// Initialize form with customer data if editing
onMounted(() => {
  if (props.isEdit && props.customer) {
    Object.keys(form).forEach((key) => {
      const value = props.customer![key as keyof Customer];
      if (value !== undefined) {
        (form as any)[key] = value;
      }
    });
  }
});
</script>
