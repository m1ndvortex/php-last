<template>
  <div class="space-y-6">
    <!-- Communication Center Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t("customers.communication_center") }}
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $t("customers.communication_description") }}
          </p>
        </div>
        <button
          @click="showSendModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <PaperAirplaneIcon class="h-4 w-4 mr-2" />
          {{ $t("communication.send_message") }}
        </button>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ communicationStats.total }}
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t("communication.total_sent") }}
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">
            {{ communicationStats.delivered }}
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t("communication.delivered") }}
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="text-2xl font-bold text-red-600 dark:text-red-400">
            {{ communicationStats.failed }}
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t("communication.failed") }}
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
            {{ communicationStats.pending }}
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t("communication.pending") }}
          </div>
        </div>
      </div>
    </div>

    <!-- Communication Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Customer Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("customers.customer") }}
          </label>
          <select
            v-model="filters.customer_id"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option
              v-for="customer in customersStore.customers"
              :key="customer.id"
              :value="customer.id"
            >
              {{ customer.name }}
            </option>
          </select>
        </div>

        <!-- Type Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("communication.type") }}
          </label>
          <select
            v-model="filters.type"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option value="email">{{ $t("communication.email") }}</option>
            <option value="sms">{{ $t("communication.sms") }}</option>
            <option value="whatsapp">{{ $t("communication.whatsapp") }}</option>
            <option value="phone">{{ $t("communication.phone") }}</option>
            <option value="meeting">{{ $t("communication.meeting") }}</option>
            <option value="note">{{ $t("communication.note") }}</option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("common.status") }}
          </label>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option value="draft">
              {{ $t("communication.statuses.draft") }}
            </option>
            <option value="sent">
              {{ $t("communication.statuses.sent") }}
            </option>
            <option value="delivered">
              {{ $t("communication.statuses.delivered") }}
            </option>
            <option value="read">
              {{ $t("communication.statuses.read") }}
            </option>
            <option value="failed">
              {{ $t("communication.statuses.failed") }}
            </option>
          </select>
        </div>

        <!-- Date Range -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {{ $t("common.date_range") }}
          </label>
          <input
            v-model="filters.date_from"
            type="date"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Communication History -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"
        ></div>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("common.loading") }}
        </p>
      </div>

      <!-- Empty State -->
      <div v-else-if="communications.length === 0" class="p-8 text-center">
        <ChatBubbleLeftRightIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("communication.no_communications") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("communication.no_communications_description") }}
        </p>
      </div>

      <!-- Communication List -->
      <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
        <div
          v-for="communication in communications"
          :key="communication.id"
          class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          <div class="flex items-start space-x-4">
            <!-- Communication Type Icon -->
            <div class="flex-shrink-0">
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
            </div>

            <!-- Communication Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ communication.customer?.name || $t("common.unknown") }}
                  </p>
                  <span
                    :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      getCommunicationStatusColor(communication.status),
                    ]"
                  >
                    {{ $t(`communication.statuses.${communication.status}`) }}
                  </span>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ formatDate(communication.created_at) }}
                </div>
              </div>

              <!-- Subject -->
              <div v-if="communication.subject" class="mt-1">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ communication.subject }}
                </p>
              </div>

              <!-- Message -->
              <div class="mt-2">
                <p
                  class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3"
                >
                  {{ communication.message }}
                </p>
              </div>

              <!-- Communication Type and Timestamps -->
              <div
                class="mt-3 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400"
              >
                <span class="flex items-center">
                  <component
                    :is="getCommunicationIcon(communication.type)"
                    class="h-3 w-3 mr-1"
                  />
                  {{ $t(`communication.${communication.type}`) }}
                </span>
                <span v-if="communication.sent_at">
                  {{ $t("communication.sent") }}:
                  {{ formatDate(communication.sent_at) }}
                </span>
                <span v-if="communication.delivered_at">
                  {{ $t("communication.delivered") }}:
                  {{ formatDate(communication.delivered_at) }}
                </span>
                <span v-if="communication.read_at">
                  {{ $t("communication.read") }}:
                  {{ formatDate(communication.read_at) }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex-shrink-0">
              <button
                @click="viewCommunication(communication)"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              >
                <EyeIcon class="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Send Communication Modal -->
    <SendCommunicationModal
      v-if="showSendModal"
      @close="showSendModal = false"
      @sent="handleCommunicationSent"
    />

    <!-- View Communication Modal -->
    <ViewCommunicationModal
      v-if="showViewModal"
      :communication="selectedCommunication"
      @close="showViewModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import {
  PaperAirplaneIcon,
  ChatBubbleLeftRightIcon,
  EyeIcon,
  EnvelopeIcon,
  DevicePhoneMobileIcon,
  PhoneIcon,
  CalendarIcon,
  DocumentTextIcon,
} from "@heroicons/vue/24/outline";
import { useCustomersStore } from "@/stores/customers";
import type { Communication } from "@/types";

// Components (these would need to be created)
import SendCommunicationModal from "./SendCommunicationModal.vue";
import ViewCommunicationModal from "./ViewCommunicationModal.vue";

const customersStore = useCustomersStore();

// State
const loading = ref(false);
const showSendModal = ref(false);
const showViewModal = ref(false);
const selectedCommunication = ref<Communication | null>(null);
const communications = ref<Communication[]>([]);

const filters = reactive({
  customer_id: "",
  type: "",
  status: "",
  date_from: "",
  date_to: "",
});

// Computed
const communicationStats = computed(() => {
  const stats = {
    total: communications.value.length,
    delivered: 0,
    failed: 0,
    pending: 0,
  };

  communications.value.forEach((comm) => {
    switch (comm.status) {
      case "delivered":
      case "read":
        stats.delivered++;
        break;
      case "failed":
        stats.failed++;
        break;
      case "draft":
      case "sent":
        stats.pending++;
        break;
    }
  });

  return stats;
});

// Methods
const applyFilters = () => {
  // In a real implementation, this would filter the communications
  // For now, we'll just log the filters
  console.log("Applying filters:", filters);
};

const viewCommunication = (communication: Communication) => {
  selectedCommunication.value = communication;
  showViewModal.value = true;
};

const handleCommunicationSent = () => {
  showSendModal.value = false;
  // Refresh communications list
  loadCommunications();
};

const loadCommunications = async () => {
  loading.value = true;
  try {
    // In a real implementation, this would fetch communications from the API
    // For now, we'll use mock data
    communications.value = [];
  } catch (error) {
    console.error("Failed to load communications:", error);
  } finally {
    loading.value = false;
  }
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

// Lifecycle
onMounted(() => {
  // Load customers for the filter dropdown
  if (customersStore.customers.length === 0) {
    customersStore.fetchCustomers();
  }

  // Load communications
  loadCommunications();
});
</script>
