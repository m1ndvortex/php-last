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
        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
      >
        <div class="mb-4">
          <h3
            class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
          >
            {{ $t("communication.add_communication") }}
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{
              $t("communication.add_description", { customer: customer.name })
            }}
          </p>
        </div>

        <form @submit.prevent="handleSubmit">
          <!-- Communication Type -->
          <div class="mb-4">
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
            >
              {{ $t("communication.type") }} *
            </label>
            <select
              v-model="form.type"
              required
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            >
              <option value="">{{ $t("common.select") }}</option>
              <option value="email">{{ $t("communication.email") }}</option>
              <option value="sms">{{ $t("communication.sms") }}</option>
              <option value="whatsapp">
                {{ $t("communication.whatsapp") }}
              </option>
              <option value="phone">{{ $t("communication.phone") }}</option>
              <option value="meeting">{{ $t("communication.meeting") }}</option>
              <option value="note">{{ $t("communication.note") }}</option>
            </select>
          </div>

          <!-- Subject (for email) -->
          <div v-if="form.type === 'email'" class="mb-4">
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
            >
              {{ $t("communication.subject") }}
            </label>
            <input
              v-model="form.subject"
              type="text"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            />
          </div>

          <!-- Message -->
          <div class="mb-4">
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
            >
              {{ $t("communication.message") }} *
            </label>
            <textarea
              v-model="form.message"
              rows="4"
              required
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            ></textarea>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              {{ $t("common.cancel") }}
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
            >
              <div
                v-if="loading"
                class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"
              ></div>
              {{ $t("common.add") }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from "vue";
import { useCustomersStore } from "@/stores/customers";
import type { Customer } from "@/types";

// Props & Emits
interface Props {
  customer: Customer;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
  added: [];
}>();

const customersStore = useCustomersStore();

// State
const loading = ref(false);

const form = reactive({
  type: "",
  subject: "",
  message: "",
});

// Methods
const handleSubmit = async () => {
  loading.value = true;
  try {
    await customersStore.sendCommunication(props.customer.id, {
      type: form.type,
      subject: form.subject,
      message: form.message,
    });

    emit("added");
  } catch (error) {
    console.error("Failed to add communication:", error);
  } finally {
    loading.value = false;
  }
};
</script>
