<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
        <div v-if="operation" class="bg-white dark:bg-gray-800">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                  {{ $t("invoices.batch_operation_details") }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  {{ $t(`invoices.operation_${operation.type}`) }}
                </p>
              </div>
              <button
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="px-6 py-4">
            <div class="space-y-6">
              <!-- Operation Information -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                  {{ $t("invoices.operation_information") }}
                </h4>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.operation_type") }}:
                    </dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ $t(`invoices.operation_${operation.type}`) }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("invoices.invoices_count") }}:
                    </dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ operation.invoices_count }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("common.status") }}:
                    </dt>
                    <dd>
                      <span
                        :class="getStatusClass(operation.status)"
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      >
                        {{ $t(`common.status_${operation.status}`) }}
                      </span>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">
                      {{ $t("common.created_at") }}:
                    </dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ formatDate(operation.created_at) }}
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Placeholder for additional details -->
              <div class="text-center py-8">
                <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                  {{ $t("invoices.batch_details_placeholder") }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  {{ $t("invoices.batch_details_placeholder_description") }}
                </p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex items-center justify-end">
            <button
              @click="$emit('close')"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
            >
              {{ $t("common.close") }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { XMarkIcon, DocumentTextIcon } from "@heroicons/vue/24/outline";
import { useCalendarConversion } from "@/composables/useCalendarConversion";

// Props
interface Props {
  operation: any | null;
}

defineProps<Props>();

// Emits
defineEmits<{
  close: [];
}>();

const { formatDate } = useCalendarConversion();

// Methods
const getStatusClass = (status: string) => {
  const classes = {
    completed: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    in_progress: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
    failed: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
    pending: "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300",
  };
  return classes[status as keyof typeof classes] || classes.pending;
};
</script>