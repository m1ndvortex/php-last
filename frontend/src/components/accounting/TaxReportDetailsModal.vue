<template>
  <div
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
  >
    <div
      class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800"
    >
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("accounting.tax_report_details") }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div v-if="report" class="space-y-6">
        <!-- Report Header -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("accounting.report_type") }}
              </label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ $t(`accounting.${report.report_type}`) }}
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("accounting.period") }}
              </label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ formatDate(report.period_start) }} -
                {{ formatDate(report.period_end) }}
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("accounting.total_sales") }}
              </label>
              <p
                class="mt-1 text-sm text-gray-900 dark:text-white font-semibold"
              >
                {{ formatCurrency(report.total_sales) }}
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("accounting.total_tax") }}
              </label>
              <p
                class="mt-1 text-sm text-gray-900 dark:text-white font-semibold"
              >
                {{ formatCurrency(report.total_tax) }}
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("common.status") }}
              </label>
              <span
                :class="[
                  'inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1',
                  report.status === 'approved'
                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                    : report.status === 'submitted'
                      ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                      : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                ]"
              >
                {{ $t(`accounting.${report.status}`) }}
              </span>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("accounting.generated_at") }}
              </label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ formatDateTime(report.generated_at) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Report Data -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("accounting.report_data") }}
          </label>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <pre class="text-xs overflow-x-auto">{{
              JSON.stringify(report.data, null, 2)
            }}</pre>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div
        class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700 mt-6"
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
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { TaxReport } from "@/stores/accounting";

interface Props {
  report: TaxReport | null;
}

defineProps<Props>();
defineEmits<{
  close: [];
}>();

const { formatDate } = useLocale();
const { formatCurrency } = useNumberFormatter();

const formatDateTime = (dateString: string) => {
  const date = new Date(dateString);
  return `${formatDate(dateString)} ${date.toLocaleTimeString()}`;
};
</script>
