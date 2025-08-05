<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="fixed inset-0 bg-black bg-opacity-25"
        @click="$emit('close')"
      ></div>

      <div
        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-y-auto"
      >
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ audit?.audit_number }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ audit?.location?.name || $t("inventory.all_locations") }} -
                {{ formatDate(audit?.audit_date) }}
              </p>
            </div>
            <div class="flex items-center space-x-2">
              <span
                :class="[
                  'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                  getStatusColor(audit?.status),
                ]"
              >
                {{ $t(`inventory.audit_status.${audit?.status}`) }}
              </span>
              <button
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Audit Summary -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
              <div class="text-sm font-medium text-blue-600 dark:text-blue-400">
                {{ $t("inventory.total_items") }}
              </div>
              <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {{ audit?.audit_items?.length || 0 }}
              </div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
              <div
                class="text-sm font-medium text-green-600 dark:text-green-400"
              >
                {{ $t("inventory.counted_items") }}
              </div>
              <div
                class="text-2xl font-bold text-green-900 dark:text-green-100"
              >
                {{ countedItems }}
              </div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
              <div
                class="text-sm font-medium text-yellow-600 dark:text-yellow-400"
              >
                {{ $t("inventory.items_with_variance") }}
              </div>
              <div
                class="text-2xl font-bold text-yellow-900 dark:text-yellow-100"
              >
                {{ audit?.items_with_variance_count || 0 }}
              </div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
              <div class="text-sm font-medium text-red-600 dark:text-red-400">
                {{ $t("inventory.total_variance_value") }}
              </div>
              <div class="text-2xl font-bold text-red-900 dark:text-red-100">
                {{ formatCurrency(audit?.total_variance_value || 0) }}
              </div>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mb-6">
            <div
              class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2"
            >
              <span>{{ $t("inventory.audit_progress") }}</span>
              <span>{{ Math.round(audit?.completion_percentage || 0) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
              <div
                class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${audit?.completion_percentage || 0}%` }"
              ></div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div v-if="audit?.status !== 'completed'" class="mb-6 flex space-x-3">
            <button
              v-if="audit?.status === 'pending'"
              @click="startAudit"
              :disabled="loading.starting"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
            >
              <PlayIcon class="h-4 w-4 mr-2" />
              {{ $t("inventory.start_audit") }}
            </button>
            <button
              v-if="
                audit?.status === 'in_progress' &&
                (audit?.completion_percentage || 0) === 100
              "
              @click="completeAudit"
              :disabled="loading.completing"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
            >
              <CheckIcon class="h-4 w-4 mr-2" />
              {{ $t("inventory.complete_audit") }}
            </button>
          </div>

          <!-- Audit Items Table -->
          <div
            class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden"
          >
            <div
              class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"
            >
              <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ $t("inventory.audit_items") }}
              </h4>
            </div>

            <!-- Loading State -->
            <div v-if="inventoryStore.loading.audit" class="p-6">
              <div class="animate-pulse space-y-4">
                <div
                  v-for="i in 5"
                  :key="i"
                  class="h-16 bg-gray-200 dark:bg-gray-700 rounded"
                ></div>
              </div>
            </div>

            <!-- Items Table -->
            <div v-else class="overflow-x-auto">
              <table
                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
              >
                <thead class="bg-gray-50 dark:bg-gray-900">
                  <tr>
                    <th
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                    >
                      {{ $t("inventory.item") }}
                    </th>
                    <th
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                    >
                      {{ $t("inventory.expected_quantity") }}
                    </th>
                    <th
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                    >
                      {{ $t("inventory.actual_quantity") }}
                    </th>
                    <th
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                    >
                      {{ $t("inventory.variance") }}
                    </th>
                    <th
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                    >
                      {{ $t("inventory.variance_value") }}
                    </th>
                    <th
                      class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                    >
                      {{ $t("common.status") }}
                    </th>
                    <th
                      v-if="audit?.status === 'in_progress'"
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
                    v-for="auditItem in audit?.audit_items"
                    :key="auditItem.id"
                    :class="[
                      'hover:bg-gray-50 dark:hover:bg-gray-700',
                      auditItem.variance !== 0
                        ? 'bg-yellow-50 dark:bg-yellow-900/10'
                        : '',
                    ]"
                  >
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                          <div
                            class="h-10 w-10 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center"
                          >
                            <CubeIcon class="h-6 w-6 text-gray-400" />
                          </div>
                        </div>
                        <div class="ml-4">
                          <div
                            class="text-sm font-medium text-gray-900 dark:text-white"
                          >
                            {{
                              auditItem.inventory_item?.localized_name ||
                              auditItem.inventory_item?.name
                            }}
                          </div>
                          <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ auditItem.inventory_item?.sku }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                    >
                      {{ formatNumber(auditItem.expected_quantity) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div
                        v-if="
                          audit?.status === 'in_progress' &&
                          !auditItem.is_counted
                        "
                      >
                        <input
                          v-model.number="auditItem.actual_quantity"
                          @blur="updateAuditItem(auditItem)"
                          type="number"
                          step="0.001"
                          min="0"
                          class="block w-20 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                        />
                      </div>
                      <div v-else class="text-sm text-gray-900 dark:text-white">
                        {{
                          auditItem.actual_quantity !== null &&
                          auditItem.actual_quantity !== undefined
                            ? formatNumber(auditItem.actual_quantity)
                            : "-"
                        }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        v-if="auditItem.variance !== null"
                        :class="[
                          'text-sm font-medium',
                          (auditItem.variance || 0) > 0
                            ? 'text-green-600 dark:text-green-400'
                            : (auditItem.variance || 0) < 0
                              ? 'text-red-600 dark:text-red-400'
                              : 'text-gray-900 dark:text-white',
                        ]"
                      >
                        {{ (auditItem.variance || 0) > 0 ? "+" : ""
                        }}{{ formatNumber(auditItem.variance || 0) }}
                      </span>
                      <span
                        v-else
                        class="text-sm text-gray-500 dark:text-gray-400"
                        >-</span
                      >
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        v-if="auditItem.variance_value !== null"
                        :class="[
                          'text-sm font-medium',
                          (auditItem.variance_value || 0) > 0
                            ? 'text-green-600 dark:text-green-400'
                            : (auditItem.variance_value || 0) < 0
                              ? 'text-red-600 dark:text-red-400'
                              : 'text-gray-900 dark:text-white',
                        ]"
                      >
                        {{ (auditItem.variance_value || 0) > 0 ? "+" : ""
                        }}{{ formatCurrency(auditItem.variance_value || 0) }}
                      </span>
                      <span
                        v-else
                        class="text-sm text-gray-500 dark:text-gray-400"
                        >-</span
                      >
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        :class="[
                          'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                          auditItem.is_counted
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                        ]"
                      >
                        {{
                          auditItem.is_counted
                            ? $t("inventory.counted")
                            : $t("inventory.pending")
                        }}
                      </span>
                    </td>
                    <td
                      v-if="audit?.status === 'in_progress'"
                      class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                    >
                      <button
                        v-if="!auditItem.is_counted"
                        @click="markAsCounted(auditItem)"
                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                      >
                        <CheckIcon class="h-4 w-4" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
// import { useI18n } from "vue-i18n";
import {
  XMarkIcon,
  PlayIcon,
  CheckIcon,
  CubeIcon,
} from "@heroicons/vue/24/outline";
import { useInventoryStore } from "@/stores/inventory";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { StockAudit, StockAuditItem } from "@/types";

// Props
interface Props {
  audit: StockAudit | null;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
  close: [];
  updated: [];
}>();

// const {} = useI18n();
const inventoryStore = useInventoryStore();
const { formatNumber, formatCurrency } = useNumberFormatter();

// State
const loading = ref({
  starting: false,
  completing: false,
  updating: false,
});

// Computed
const countedItems = computed(() => {
  return (
    props.audit?.audit_items?.filter((item) => item.is_counted).length || 0
  );
});

// Methods
const formatDate = (date: string | undefined) => {
  if (!date) return "-";
  return new Date(date).toLocaleDateString();
};

const getStatusColor = (status: string | undefined) => {
  if (!status) return "";

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

const startAudit = async () => {
  if (!props.audit) return;

  loading.value.starting = true;
  try {
    await inventoryStore.startAudit(props.audit.id);
    emit("updated");
  } catch (error) {
    console.error("Failed to start audit:", error);
  } finally {
    loading.value.starting = false;
  }
};

const completeAudit = async () => {
  if (!props.audit) return;

  loading.value.completing = true;
  try {
    await inventoryStore.completeAudit(props.audit.id);
    emit("updated");
  } catch (error) {
    console.error("Failed to complete audit:", error);
  } finally {
    loading.value.completing = false;
  }
};

const updateAuditItem = async (auditItem: StockAuditItem) => {
  if (!props.audit || loading.value.updating) return;

  loading.value.updating = true;
  try {
    await inventoryStore.updateAuditItem(
      props.audit.id,
      auditItem.inventory_item_id,
      {
        actual_quantity: auditItem.actual_quantity,
      },
    );

    // Refresh audit data
    if (props.audit) {
      await inventoryStore.fetchAudit(props.audit.id);
    }
  } catch (error) {
    console.error("Failed to update audit item:", error);
  } finally {
    loading.value.updating = false;
  }
};

const markAsCounted = async (auditItem: StockAuditItem) => {
  if (!props.audit) return;

  try {
    await inventoryStore.updateAuditItem(
      props.audit.id,
      auditItem.inventory_item_id,
      {
        actual_quantity:
          auditItem.actual_quantity || auditItem.expected_quantity,
        is_counted: true,
      },
    );

    // Refresh audit data
    if (props.audit) {
      await inventoryStore.fetchAudit(props.audit.id);
    }
  } catch (error) {
    console.error("Failed to mark item as counted:", error);
  }
};

// Lifecycle
onMounted(async () => {
  if (props.audit) {
    await inventoryStore.fetchAudit(props.audit.id);
  }
});
</script>
