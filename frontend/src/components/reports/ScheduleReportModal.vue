<template>
  <div
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
  >
    <div
      class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800"
    >
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t("reports.schedule_report") }}
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <form @submit.prevent="scheduleReport" class="space-y-4">
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
            >
              {{ $t("reports.report_name") }}
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="form-input w-full"
              :placeholder="$t('reports.enter_report_name')"
            />
          </div>

          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
            >
              {{ $t("reports.frequency") }}
            </label>
            <select
              v-model="form.frequency"
              required
              class="form-select w-full"
            >
              <option value="daily">{{ $t("reports.daily") }}</option>
              <option value="weekly">{{ $t("reports.weekly") }}</option>
              <option value="monthly">{{ $t("reports.monthly") }}</option>
              <option value="quarterly">{{ $t("reports.quarterly") }}</option>
            </select>
          </div>

          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
            >
              {{ $t("reports.delivery_time") }}
            </label>
            <input
              v-model="form.time"
              type="time"
              required
              class="form-input w-full"
            />
          </div>

          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
            >
              {{ $t("reports.delivery_method") }}
            </label>
            <select
              v-model="form.deliveryMethod"
              required
              class="form-select w-full"
            >
              <option value="email">{{ $t("reports.email") }}</option>
              <option value="download">
                {{ $t("reports.download_link") }}
              </option>
            </select>
          </div>

          <div v-if="form.deliveryMethod === 'email'">
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
            >
              {{ $t("reports.email_recipients") }}
            </label>
            <textarea
              v-model="form.recipients"
              rows="3"
              class="form-input w-full"
              :placeholder="$t('reports.enter_email_addresses')"
            ></textarea>
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="btn btn-secondary"
            >
              {{ $t("common.cancel") }}
            </button>
            <button type="submit" :disabled="loading" class="btn btn-primary">
              <i v-if="loading" class="fas fa-spinner animate-spin mr-2"></i>
              {{ $t("reports.schedule") }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import api from "@/services/api";

const { t } = useI18n();

defineProps<{
  reportTypes: Array<any>;
}>();

const emit = defineEmits<{
  close: [];
  scheduled: [];
}>();

const loading = ref(false);
const form = ref({
  name: "",
  frequency: "monthly",
  time: "09:00",
  deliveryMethod: "email",
  recipients: "",
});

const scheduleReport = async () => {
  loading.value = true;
  try {
    await api.post("/reports/schedule", {
      name: form.value.name,
      type: "sales", // Default type
      subtype: "summary",
      parameters: {},
      schedule: {
        frequency: form.value.frequency,
        time: form.value.time,
      },
      delivery: {
        method: form.value.deliveryMethod,
        recipients:
          form.value.deliveryMethod === "email"
            ? form.value.recipients.split(",").map((email) => email.trim())
            : [],
      },
    });

    emit("scheduled");
  } catch (error) {
    console.error("Failed to schedule report:", error);
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.btn {
  @apply px-4 py-2 rounded-lg font-medium transition-colors;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300;
}

.form-input,
.form-select {
  @apply block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white;
}
</style>
