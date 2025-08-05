<template>
  <div class="space-y-4">
    <!-- Add Communication Button -->
    <div class="flex justify-end">
      <button
        @click="showAddModal = true"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700"
      >
        <PlusIcon class="h-4 w-4 mr-2" />
        {{ $t("communication.add_communication") }}
      </button>
    </div>

    <!-- Communications List -->
    <div class="space-y-3">
      <div
        v-for="communication in communications"
        :key="communication.id"
        class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4"
      >
        <div class="flex items-start space-x-3">
          <!-- Communication Type Icon -->
          <div class="flex-shrink-0">
            <div
              :class="[
                'h-8 w-8 rounded-full flex items-center justify-center',
                getCommunicationTypeColor(communication.type),
              ]"
            >
              <component
                :is="getCommunicationIcon(communication.type)"
                class="h-4 w-4"
              />
            </div>
          </div>

          <!-- Communication Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ $t(`communication.${communication.type}`) }}
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
              <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ formatDate(communication.created_at) }}
              </span>
            </div>

            <!-- Subject -->
            <div v-if="communication.subject" class="mt-1">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ communication.subject }}
              </p>
            </div>

            <!-- Message -->
            <div class="mt-2">
              <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                {{ communication.message }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="communications.length === 0" class="text-center py-8">
        <ChatBubbleLeftRightIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("communication.no_communications") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("communication.no_communications_customer") }}
        </p>
      </div>
    </div>

    <!-- Add Communication Modal -->
    <AddCommunicationModal
      v-if="showAddModal"
      :customer="customer"
      @close="showAddModal = false"
      @added="handleCommunicationAdded"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import {
  PlusIcon,
  ChatBubbleLeftRightIcon,
  EnvelopeIcon,
  DevicePhoneMobileIcon,
  PhoneIcon,
  CalendarIcon,
  DocumentTextIcon,
} from "@heroicons/vue/24/outline";
import { useCustomersStore } from "@/stores/customers";
import type { Customer } from "@/types";

// Components
import AddCommunicationModal from "./AddCommunicationModal.vue";

// Props
interface Props {
  customer: Customer;
}

const props = defineProps<Props>();

const customersStore = useCustomersStore();

// State
const showAddModal = ref(false);

// Computed
const communications = computed(() => customersStore.communications);

// Methods
const handleCommunicationAdded = () => {
  showAddModal.value = false;
  // Refresh communications
  customersStore.fetchCommunications(props.customer.id);
};

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

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};
</script>
