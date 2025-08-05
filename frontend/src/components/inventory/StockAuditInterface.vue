<template>
  <div class="space-y-6">
    <!-- Header with Actions -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div class="sm:flex-auto">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.stock_audits") }}
        </h2>
        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
          {{ $t("inventory.stock_audits_description") }}
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          @click="showCreateAuditModal = true"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          {{ $t("inventory.create_audit") }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("common.status") }}
          </label>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all_statuses") }}</option>
            <option value="pending">
              {{ $t("inventory.audit_status.pending") }}
            </option>
            <option value="in_progress">
              {{ $t("inventory.audit_status.in_progress") }}
            </option>
            <option value="completed">
              {{ $t("inventory.audit_status.completed") }}
            </option>
            <option value="cancelled">
              {{ $t("inventory.audit_status.cancelled") }}
            </option>
          </select>
        </div>
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("inventory.location") }}
          </label>
          <select
            v-model="filters.location_id"
            @change="applyFilters"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          >
            <option value="">{{ $t("common.all_locations") }}</option>
            <option
              v-for="location in inventoryStore.locations"
              :key="location.id"
              :value="location.id"
            >
              {{ location.name }}
            </option>
          </select>
        </div>
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
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

    <!-- Audits List -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <!-- Loading State -->
      <div v-if="inventoryStore.loading.audits" class="p-6">
        <div class="animate-pulse space-y-4">
          <div
            v-for="i in 3"
            :key="i"
            class="h-20 bg-gray-200 dark:bg-gray-700 rounded"
          ></div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="inventoryStore.audits.length === 0"
        class="p-6 text-center"
      >
        <ClipboardDocumentListIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("inventory.no_audits") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("inventory.no_audits_description") }}
        </p>
        <div class="mt-6">
          <button
            @click="showCreateAuditModal = true"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            {{ $t("inventory.create_first_audit") }}
          </button>
        </div>
      </div>

      <!-- Audits Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.audit_number") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.location") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.audit_date") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("common.status") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.progress") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("inventory.variance") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
              >
                {{ $t("common.actions") }}
              </th>
            </tr>
          </thead>
          <tbody
            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
          >
            <tr
              v-for="audit in inventoryStore.audits"
              :key="audit.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ audit.audit_number }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ audit.auditor?.name || "-" }}
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ audit.location?.name || $t("common.all_locations") }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ formatDate(audit.audit_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    getStatusColor(audit.status),
                  ]"
                >
                  {{ $t(`inventory.audit_status.${audit.status}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div
                    class="w-16 bg-gray-200 rounded-full h-2 dark:bg-gray-700"
                  >
                    <div
                      class="bg-primary-600 h-2 rounded-full"
                      :style="{ width: `${audit.completion_percentage || 0}%` }"
                    ></div>
                  </div>
                  <span class="ml-2 text-sm text-gray-900 dark:text-white">
                    {{ Math.round(audit.completion_percentage || 0) }}%
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900 dark:text-white">
                  {{ audit.items_with_variance_count || 0 }}
                  {{ $t("inventory.items") }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ formatCurrency(audit.total_variance_value || 0) }}
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
              >
                <div class="flex justify-end space-x-2">
                  <button
                    @click="viewAudit(audit)"
                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  <button
                    v-if="audit.status === 'pending'"
                    @click="startAudit(audit)"
                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                  >
                    <PlayIcon class="h-4 w-4" />
                  </button>
                  <button
                    v-if="audit.status === 'in_progress'"
                    @click="completeAudit(audit)"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                  >
                    <CheckIcon class="h-4 w-4" />
                  </button>
                  <button
                    v-if="['pending', 'in_progress'].includes(audit.status)"
                    @click="deleteAudit(audit)"
                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Audit Modal -->
    <CreateAuditModal
      v-if="showCreateAuditModal"
      @close="showCreateAuditModal = false"
      @created="handleAuditCreated"
    />

    <!-- Audit Details Modal -->
    <AuditDetailsModal
      v-if="showAuditDetailsModal"
      :audit="selectedAudit"
      @close="showAuditDetailsModal = false"
      @updated="handleAuditUpdated"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      :title="$t('inventory.delete_audit')"
      :message="
        $t('inventory.delete_audit_confirmation', {
          number: selectedAudit?.audit_number,
        })
      "
      :loading="inventoryStore.loading.deleting"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
// import { useI18n } from "vue-i18n";
import {
  PlusIcon,
  EyeIcon,
  PlayIcon,
  CheckIcon,
  TrashIcon,
  ClipboardDocumentListIcon,
} from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { StockAudit } from "@/types";

// Components
import CreateAuditModal from "./CreateAuditModal.vue";
import AuditDetailsModal from "./AuditDetailsModal.vue";
import ConfirmationModal from "@/components/ui/ConfirmationModal.vue";

// const {} = useI18n();
const inventoryStore = useInventoryStore();
const { formatCurrency } = useNumberFormatter();

// State
const showCreateAuditModal = ref(false);
const showAuditDetailsModal = ref(false);
const showDeleteModal = ref(false);
const selectedAudit = ref<StockAudit | null>(null);

const filters = ref({
  status: "",
  location_id: "",
  date_from: "",
});

// Methods
const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString();
};

const getStatusColor = (status: string) => {
  const colors = {
    pending:
      "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400",
    in_progress:
      "bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400",
    completed:
      "bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400",
    cancelled: "bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400",
  };
  return colors[status as keyof typeof colors] || colors.pending;
};

const applyFilters = () => {
  const params = { ...filters.value };

  // Remove empty filters
  Object.keys(params).forEach((key) => {
    if (params[key as keyof typeof params] === "") {
      delete params[key as keyof typeof params];
    }
  });

  inventoryStore.fetchAudits(params);
};

const viewAudit = (audit: StockAudit) => {
  selectedAudit.value = audit;
  showAuditDetailsModal.value = true;
};

const startAudit = async (audit: StockAudit) => {
  try {
    await inventoryStore.startAudit(audit.id);
    // Refresh audits list
    applyFilters();
  } catch (error) {
    console.error("Failed to start audit:", error);
  }
};

const completeAudit = async (audit: StockAudit) => {
  try {
    await inventoryStore.completeAudit(audit.id);
    // Refresh audits list
    applyFilters();
  } catch (error) {
    console.error("Failed to complete audit:", error);
  }
};

const deleteAudit = (audit: StockAudit) => {
  selectedAudit.value = audit;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  if (selectedAudit.value) {
    try {
      // Note: Delete method not implemented in store yet
      // await inventoryStore.deleteAudit(selectedAudit.value.id);
      showDeleteModal.value = false;
      selectedAudit.value = null;
      applyFilters();
    } catch (error) {
      console.error("Failed to delete audit:", error);
    }
  }
};

const handleAuditCreated = () => {
  showCreateAuditModal.value = false;
  applyFilters();
};

const handleAuditUpdated = () => {
  showAuditDetailsModal.value = false;
  applyFilters();
};

// Lifecycle
onMounted(() => {
  applyFilters();
});
</script>
