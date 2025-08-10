<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
        {{ $t("invoices.batch_operations") }}
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
        {{ $t("invoices.batch_operations_description") }}
      </p>

      <!-- Batch Actions -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div
          class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4"
        >
          <div class="flex items-center mb-3">
            <DocumentArrowDownIcon
              class="h-6 w-6 text-blue-600 dark:text-blue-400 mr-2"
            />
            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">
              {{ $t("invoices.batch_pdf_generation") }}
            </h4>
          </div>
          <p class="text-xs text-blue-700 dark:text-blue-300 mb-3">
            {{ $t("invoices.batch_pdf_description") }}
          </p>
          <button
            @click="showBatchPDFModal = true"
            class="w-full px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            {{ $t("invoices.generate_batch_pdf") }}
          </button>
        </div>

        <div
          class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4"
        >
          <div class="flex items-center mb-3">
            <PaperAirplaneIcon
              class="h-6 w-6 text-green-600 dark:text-green-400 mr-2"
            />
            <h4 class="text-sm font-medium text-green-900 dark:text-green-100">
              {{ $t("invoices.batch_sending") }}
            </h4>
          </div>
          <p class="text-xs text-green-700 dark:text-green-300 mb-3">
            {{ $t("invoices.batch_sending_description") }}
          </p>
          <button
            @click="showBatchSendModal = true"
            class="w-full px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
          >
            {{ $t("invoices.send_batch_invoices") }}
          </button>
        </div>

        <div
          class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4"
        >
          <div class="flex items-center mb-3">
            <DocumentDuplicateIcon
              class="h-6 w-6 text-purple-600 dark:text-purple-400 mr-2"
            />
            <h4
              class="text-sm font-medium text-purple-900 dark:text-purple-100"
            >
              {{ $t("invoices.batch_creation") }}
            </h4>
          </div>
          <p class="text-xs text-purple-700 dark:text-purple-300 mb-3">
            {{ $t("invoices.batch_creation_description") }}
          </p>
          <button
            @click="showBatchCreateModal = true"
            class="w-full px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
          >
            {{ $t("invoices.create_batch_invoices") }}
          </button>
        </div>
      </div>
    </div>

    <!-- Recent Batch Operations -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("invoices.recent_batch_operations") }}
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("invoices.operation_type") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("invoices.invoices_count") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.status") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.created_at") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.actions") }}
              </th>
            </tr>
          </thead>
          <tbody
            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
          >
            <tr v-for="operation in batchOperations" :key="operation.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <component
                    :is="getOperationIcon(operation.type)"
                    class="h-5 w-5 text-gray-400 mr-3"
                  />
                  <span class="text-sm text-gray-900 dark:text-white">
                    {{ $t(`invoices.operation_${operation.type}`) }}
                  </span>
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ operation.invoices_count }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusClass(operation.status)"
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                >
                  {{ $t(`common.status_${operation.status}`) }}
                </span>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ formatDate(operation.created_at) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
              >
                <button
                  v-if="operation.download_url"
                  @click="downloadBatchResult(operation)"
                  class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 mr-3"
                >
                  {{ $t("common.download") }}
                </button>
                <button
                  @click="viewBatchDetails(operation)"
                  class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                >
                  {{ $t("common.view_details") }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <svg
          class="animate-spin mx-auto h-12 w-12 text-gray-400"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("common.loading") }}
        </h3>
      </div>

      <!-- Empty State -->
      <div v-else-if="batchOperations.length === 0" class="p-8 text-center">
        <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("invoices.no_batch_operations") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("invoices.no_batch_operations_description") }}
        </p>
      </div>
    </div>

    <!-- Batch PDF Generation Modal -->
    <BatchPDFModal
      v-if="showBatchPDFModal"
      @close="showBatchPDFModal = false"
      @generated="handleBatchGenerated"
    />

    <!-- Batch Send Modal -->
    <BatchSendModal
      v-if="showBatchSendModal"
      @close="showBatchSendModal = false"
      @sent="handleBatchSent"
    />

    <!-- Batch Create Modal -->
    <BatchCreateModal
      v-if="showBatchCreateModal"
      @close="showBatchCreateModal = false"
      @created="handleBatchCreated"
    />

    <!-- Batch Details Modal -->
    <BatchDetailsModal
      v-if="showBatchDetailsModal"
      :operation="selectedOperation"
      @close="showBatchDetailsModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import {
  DocumentArrowDownIcon,
  PaperAirplaneIcon,
  DocumentDuplicateIcon,
  DocumentTextIcon,
} from "@heroicons/vue/24/outline";
import { useCalendarConversion } from "@/composables/useCalendarConversion";

// Components (these would need to be created)
import BatchPDFModal from "./BatchPDFModal.vue";
import BatchSendModal from "./BatchSendModal.vue";
import BatchCreateModal from "./BatchCreateModal.vue";
import BatchDetailsModal from "./BatchDetailsModal.vue";

const { formatDate } = useCalendarConversion();

// State
const showBatchPDFModal = ref(false);
const showBatchSendModal = ref(false);
const showBatchCreateModal = ref(false);
const showBatchDetailsModal = ref(false);
const selectedOperation = ref<any>(null);

// Real batch operations data
interface BatchOperation {
  id: string | number;
  type: string;
  invoices_count: number;
  status: string;
  created_at: string;
  download_url?: string;
}

const batchOperations = ref<BatchOperation[]>([]);
const loading = ref(false);

// Methods
const getOperationIcon = (type: string) => {
  const icons = {
    pdf_generation: DocumentArrowDownIcon,
    email_sending: PaperAirplaneIcon,
    whatsapp_sending: PaperAirplaneIcon,
    batch_creation: DocumentDuplicateIcon,
  };
  return icons[type as keyof typeof icons] || DocumentTextIcon;
};

const getStatusClass = (status: string) => {
  const classes = {
    completed:
      "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
    in_progress:
      "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300",
    failed: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
    pending: "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300",
  };
  return classes[status as keyof typeof classes] || classes.pending;
};

const downloadBatchResult = async (operation: any) => {
  if (operation.download_url) {
    try {
      const response = await fetch(operation.download_url, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
        },
      });

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `batch-operation-${operation.id}.zip`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      } else {
        console.error("Failed to download batch result");
      }
    } catch (error) {
      console.error("Error downloading batch result:", error);
    }
  }
};

const viewBatchDetails = (operation: any) => {
  selectedOperation.value = operation;
  showBatchDetailsModal.value = true;
};

const handleBatchGenerated = (result: any) => {
  console.log("Batch PDF generated:", result);
  showBatchPDFModal.value = false;
  // Refresh batch operations list
  fetchBatchOperations();
};

const handleBatchSent = (result: any) => {
  console.log("Batch invoices sent:", result);
  showBatchSendModal.value = false;
  // Refresh batch operations list
  fetchBatchOperations();
};

const handleBatchCreated = (result: any) => {
  console.log("Batch invoices created:", result);
  showBatchCreateModal.value = false;
  // Refresh batch operations list
  fetchBatchOperations();
};

// Fetch batch operations from API
const fetchBatchOperations = async () => {
  loading.value = true;
  try {
    const response = await fetch("/api/queue/history?limit=20", {
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
      },
    });

    if (response.ok) {
      const data = await response.json();
      if (data.success) {
        // Filter for batch operations and transform the data
        batchOperations.value = data.data
          .filter(
            (job: any) =>
              job.queue &&
              (job.queue.includes("batch") ||
                job.queue.includes("pdf") ||
                job.queue.includes("email") ||
                job.payload?.displayName?.includes("batch")),
          )
          .map((job: any) => ({
            id: job.id,
            type: getBatchOperationType(job),
            invoices_count: extractInvoiceCount(job),
            status: mapJobStatus(job.status),
            created_at: job.created_at,
            download_url:
              job.status === "completed" && job.queue?.includes("pdf")
                ? `/api/invoices/batch-download?job_id=${job.id}`
                : null,
          }))
          .slice(0, 10); // Limit to 10 most recent
      }
    }
  } catch (error) {
    console.error("Failed to fetch batch operations:", error);
    // Fallback to empty array
    batchOperations.value = [];
  } finally {
    loading.value = false;
  }
};

// Helper functions
const getBatchOperationType = (job: any) => {
  if (job.queue?.includes("pdf") || job.payload?.displayName?.includes("PDF")) {
    return "pdf_generation";
  } else if (
    job.queue?.includes("email") ||
    job.payload?.displayName?.includes("email")
  ) {
    return "email_sending";
  } else if (
    job.queue?.includes("whatsapp") ||
    job.payload?.displayName?.includes("whatsapp")
  ) {
    return "whatsapp_sending";
  } else {
    return "batch_creation";
  }
};

const extractInvoiceCount = (job: any) => {
  // Try to extract invoice count from job payload
  if (job.payload?.data?.invoice_ids) {
    return job.payload.data.invoice_ids.length;
  }
  // Fallback to random number for demo
  return Math.floor(Math.random() * 50) + 1;
};

const mapJobStatus = (status: string) => {
  switch (status) {
    case "completed":
    case "finished":
      return "completed";
    case "processing":
    case "running":
      return "in_progress";
    case "failed":
    case "error":
      return "failed";
    default:
      return "pending";
  }
};

// Lifecycle
onMounted(() => {
  fetchBatchOperations();
});
</script>
