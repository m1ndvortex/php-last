<template>
  <div
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
  >
    <div
      class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800"
    >
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("accounting.audit_log_details") }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div v-if="auditLog" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("common.date_time") }}
            </label>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">
              {{ formatDateTime(auditLog.created_at) }}
            </p>
          </div>
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("common.user") }}
            </label>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">
              {{ auditLog.user?.name || "System" }}
            </p>
          </div>
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("accounting.action") }}
            </label>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">
              {{ $t(`accounting.${auditLog.action}`) }}
            </p>
          </div>
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
              {{ $t("accounting.entity") }}
            </label>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">
              {{ auditLog.auditable_type }} (ID: {{ auditLog.auditable_id }})
            </p>
          </div>
        </div>

        <div v-if="auditLog.old_values">
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("accounting.old_values") }}
          </label>
          <pre
            class="bg-gray-50 dark:bg-gray-700 p-3 rounded text-xs overflow-x-auto"
            >{{ JSON.stringify(auditLog.old_values, null, 2) }}</pre
          >
        </div>

        <div v-if="auditLog.new_values">
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("accounting.new_values") }}
          </label>
          <pre
            class="bg-gray-50 dark:bg-gray-700 p-3 rounded text-xs overflow-x-auto"
            >{{ JSON.stringify(auditLog.new_values, null, 2) }}</pre
          >
        </div>
      </div>

      <div
        class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700 mt-6"
      >
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
        >
          {{ $t("common.close") }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { XMarkIcon } from "@heroicons/vue/24/outline";
import { useLocale } from "@/composables/useLocale";
import type { AuditLogEntry } from "@/stores/accounting";

interface Props {
  auditLog: AuditLogEntry | null;
}

defineProps<Props>();
defineEmits<{
  close: [];
}>();

const { formatDate } = useLocale();

const formatDateTime = (dateString: string) => {
  const date = new Date(dateString);
  return `${formatDate(dateString)} ${date.toLocaleTimeString()}`;
};
</script>
