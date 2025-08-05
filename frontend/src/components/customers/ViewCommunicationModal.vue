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
        <div v-if="communication">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">
              <div
                :class="[
                  'h-10 w-10 rounded-full flex items-center justify-center',
                  getCommunicationTypeColor(communication.type),
                ]"
              >
                <component
                  :is="getCommunicationIcon(communication.type)"
                  class="h-5 w-5"
                />
              </div>
              <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ $t(`communication.${communication.type}`) }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ communication.customer?.name || $t("common.unknown") }}
                </p>
              </div>
            </div>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>

          <!-- Communication Details -->
          <div class="space-y-4">
            <!-- Status -->
            <div class="flex items-center justify-between">
              <span
                class="text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("common.status") }}:
              </span>
              <span
                :class="[
                  'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                  getCommunicationStatusColor(communication.status),
                ]"
              >
                {{ $t(`communication.statuses.${communication.status}`) }}
              </span>
            </div>

            <!-- Subject (if applicable) -->
            <div v-if="communication.subject">
              <span
                class="text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("communication.subject") }}:
              </span>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ communication.subject }}
              </p>
            </div>

            <!-- Message -->
            <div>
              <span
                class="text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("communication.message") }}:
              </span>
              <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p
                  class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap"
                >
                  {{ communication.message }}
                </p>
              </div>
            </div>

            <!-- Timestamps -->
            <div
              class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700"
            >
              <div>
                <span
                  class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("communication.created") }}:
                </span>
                <p class="text-sm text-gray-900 dark:text-white">
                  {{ formatDateTime(communication.created_at) }}
                </p>
              </div>

              <div v-if="communication.sent_at">
                <span
                  class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("communication.sent") }}:
                </span>
                <p class="text-sm text-gray-900 dark:text-white">
                  {{ formatDateTime(communication.sent_at) }}
                </p>
              </div>

              <div v-if="communication.delivered_at">
                <span
                  class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("communication.delivered") }}:
                </span>
                <p class="text-sm text-gray-900 dark:text-white">
                  {{ formatDateTime(communication.delivered_at) }}
                </p>
              </div>

              <div v-if="communication.read_at">
                <span
                  class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("communication.read") }}:
                </span>
                <p class="text-sm text-gray-900 dark:text-white">
                  {{ formatDateTime(communication.read_at) }}
                </p>
              </div>
            </div>

            <!-- Metadata (if any) -->
            <div
              v-if="
                communication.metadata &&
                Object.keys(communication.metadata).length > 0
              "
              class="pt-4 border-t border-gray-200 dark:border-gray-700"
            >
              <span
                class="text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("communication.metadata") }}:
              </span>
              <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <pre class="text-xs text-gray-900 dark:text-white">{{
                  JSON.stringify(communication.metadata, null, 2)
                }}</pre>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  XMarkIcon,
  EnvelopeIcon,
  DevicePhoneMobileIcon,
  PhoneIcon,
  CalendarIcon,
  DocumentTextIcon,
} from "@heroicons/vue/24/outline";
import type { Communication } from "@/types";

// Props & Emits
interface Props {
  communication: Communication | null;
}

defineProps<Props>();

defineEmits<{
  close: [];
}>();

// Methods
const getCommunicationIcon = (type: string) => {
  const icons = {
    email: EnvelopeIcon,
    sms: DevicePhoneMobileIcon,
    whatsapp: DevicePhoneMobileIcon,
    phone: PhoneIcon,
    meeting: CalendarIcon,
    note: DocumentTextIcon,
  };
  return icons[type as keyof typeof icons] || DocumentTextIcon;
};

const getCommunicationTypeColor = (type: string) => {
  const colors = {
    email: "bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300",
    sms: "bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300",
    whatsapp:
      "bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300",
    phone:
      "bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-300",
    meeting:
      "bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-300",
    note: "bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-300",
  };
  return colors[type as keyof typeof colors] || colors.note;
};

const getCommunicationStatusColor = (status: string) => {
  const colors = {
    draft: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200",
    sent: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    delivered:
      "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    read: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    failed: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
  };
  return colors[status as keyof typeof colors] || colors.draft;
};

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString();
};
</script>
