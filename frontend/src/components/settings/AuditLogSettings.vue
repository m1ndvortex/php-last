<template>
  <div class="space-y-8">
    <div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">
        {{ $t("settings.audit.title") }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t("settings.audit.description") }}
      </p>
    </div>

    <form @submit.prevent="saveAuditSettings" class="space-y-8">
      <!-- Audit Log Configuration -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-white">
              {{ $t("settings.audit.audit_logging") }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("settings.audit.audit_logging_description") }}
            </p>
          </div>
          <div class="flex items-center">
            <input
              id="audit_enabled"
              type="checkbox"
              v-model="auditForm.enabled"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="audit_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
              {{ $t("settings.audit.enable_audit_logging") }}
            </label>
          </div>
        </div>

        <div v-if="auditForm.enabled" class="space-y-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label for="log_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.audit.log_level") }}
              </label>
              <select
                id="log_level"
                v-model="auditForm.log_level"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              >
                <option value="basic">{{ $t("settings.audit.basic") }}</option>
                <option value="detailed">{{ $t("settings.audit.detailed") }}</option>
                <option value="verbose">{{ $t("settings.audit.verbose") }}</option>
              </select>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $t(`settings.audit.${auditForm.log_level}_description`) }}
              </p>
            </div>

            <div>
              <label for="retention_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.audit.retention_days") }}
              </label>
              <div class="mt-1 relative rounded-md shadow-sm">
                <input
                  type="number"
                  id="retention_days"
                  v-model.number="auditForm.retention_days"
                  min="30"
                  max="3650"
                  class="block w-full rounded-md border-gray-300 pr-16 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                  :placeholder="$t('settings.audit.retention_placeholder')"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                  <span class="text-gray-500 sm:text-sm">{{ $t("common.days") }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Log Types -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
              {{ $t("settings.audit.log_types") }}
            </label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="auditForm.log_user_actions"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ $t("settings.audit.log_user_actions") }}
                </span>
              </label>

              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="auditForm.log_system_events"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ $t("settings.audit.log_system_events") }}
                </span>
              </label>

              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="auditForm.log_api_requests"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ $t("settings.audit.log_api_requests") }}
                </span>
              </label>

              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="auditForm.log_database_changes"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ $t("settings.audit.log_database_changes") }}
                </span>
              </label>

              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="auditForm.log_file_operations"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ $t("settings.audit.log_file_operations") }}
                </span>
              </label>
            </div>
          </div>

          <!-- Alert Settings -->
          <div>
            <label class="flex items-center">
              <input
                type="checkbox"
                v-model="auditForm.alert_on_suspicious_activity"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-900 dark:text-white">
                {{ $t("settings.audit.alert_on_suspicious_activity") }}
              </span>
            </label>
            <p class="ml-6 text-xs text-gray-500 dark:text-gray-400">
              {{ $t("settings.audit.alert_description") }}
            </p>
          </div>

          <!-- Export Format -->
          <div>
            <label for="export_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.audit.export_format") }}
            </label>
            <select
              id="export_format"
              v-model="auditForm.export_format"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            >
              <option value="json">JSON</option>
              <option value="csv">CSV</option>
              <option value="xml">XML</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Audit Log Viewer -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-md font-medium text-gray-900 dark:text-white">
            {{ $t("settings.audit.recent_logs") }}
          </h4>
          <div class="flex space-x-2">
            <button
              type="button"
              @click="refreshAuditLogs"
              :disabled="isLoadingLogs"
              class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-3 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ArrowPathIcon class="h-4 w-4" />
            </button>
            <button
              type="button"
              @click="exportAuditLogs"
              :disabled="isExportingLogs"
              class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-3 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ArrowDownTrayIcon class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
          <div>
            <label for="filter_action" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.audit.filter_action") }}
            </label>
            <input
              type="text"
              id="filter_action"
              v-model="logFilters.action"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              :placeholder="$t('settings.audit.filter_action_placeholder')"
            />
          </div>

          <div>
            <label for="filter_user" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.audit.filter_user") }}
            </label>
            <input
              type="text"
              id="filter_user"
              v-model="logFilters.user"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              :placeholder="$t('settings.audit.filter_user_placeholder')"
            />
          </div>

          <div>
            <label for="filter_date_from" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.audit.filter_date_from") }}
            </label>
            <input
              type="date"
              id="filter_date_from"
              v-model="logFilters.date_from"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>

          <div>
            <label for="filter_date_to" class="block text-xs font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.audit.filter_date_to") }}
            </label>
            <input
              type="date"
              id="filter_date_to"
              v-model="logFilters.date_to"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
        </div>

        <!-- Audit Logs Table -->
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
          <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t("settings.audit.timestamp") }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t("settings.audit.user") }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t("settings.audit.action") }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t("settings.audit.ip_address") }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t("common.actions") }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-if="isLoadingLogs">
                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  <div class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 text-gray-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ $t("common.loading") }}
                  </div>
                </td>
              </tr>
              <tr v-else-if="filteredAuditLogs.length === 0">
                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  {{ $t("settings.audit.no_logs") }}
                </td>
              </tr>
              <tr v-else v-for="log in filteredAuditLogs.slice(0, 10)" :key="log.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ formatDate(log.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ log.user?.name || $t("settings.audit.system") }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ log.action }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ log.ip_address }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <button
                    @click="viewLogDetails(log)"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                  >
                    {{ $t("common.view") }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="isLoading"
          class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="isLoading" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ $t("common.saving") }}
          </span>
          <span v-else>{{ $t("common.save") }}</span>
        </button>
      </div>
    </form>

    <!-- Log Details Modal -->
    <div
      v-if="selectedLog"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="selectedLog = null"
    >
      <div
        class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800"
        @click.stop
      >
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ $t("settings.audit.log_details") }}
          </h3>
          
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t("settings.audit.timestamp") }}
                </label>
                <p class="text-sm text-gray-900 dark:text-white">
                  {{ formatDate(selectedLog.created_at) }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t("settings.audit.user") }}
                </label>
                <p class="text-sm text-gray-900 dark:text-white">
                  {{ selectedLog.user?.name || $t("settings.audit.system") }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t("settings.audit.action") }}
                </label>
                <p class="text-sm text-gray-900 dark:text-white">
                  {{ selectedLog.action }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t("settings.audit.ip_address") }}
                </label>
                <p class="text-sm text-gray-900 dark:text-white">
                  {{ selectedLog.ip_address }}
                </p>
              </div>
            </div>

            <div v-if="selectedLog.old_values || selectedLog.new_values">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ $t("settings.audit.changes") }}
              </label>
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <pre class="text-xs text-gray-900 dark:text-white overflow-auto">{{ formatChanges(selectedLog) }}</pre>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-4">
            <button
              @click="selectedLog = null"
              class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
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
import { ref, reactive, computed, watch, onMounted } from "vue";
import { useSettingsStore } from "@/stores/settings";
import { useNotifications } from "@/composables/useNotifications";
import { ArrowPathIcon, ArrowDownTrayIcon } from "@heroicons/vue/24/outline";
import type { AuditLogSettings, AuditLogEntry } from "@/types/settings";

const settingsStore = useSettingsStore();
const { showNotification } = useNotifications();

// State
const isLoading = ref(false);
const isLoadingLogs = ref(false);
const isExportingLogs = ref(false);
const selectedLog = ref<AuditLogEntry | null>(null);

const auditForm = reactive<Partial<AuditLogSettings>>({
  enabled: true,
  log_level: "detailed",
  retention_days: 365,
  log_user_actions: true,
  log_system_events: true,
  log_api_requests: false,
  log_database_changes: true,
  log_file_operations: false,
  alert_on_suspicious_activity: true,
  export_format: "json",
});

const logFilters = reactive({
  action: "",
  user: "",
  date_from: "",
  date_to: "",
});

// Computed
const filteredAuditLogs = computed(() => {
  let logs = settingsStore.auditLogs;

  if (logFilters.action) {
    logs = logs.filter(log => 
      log.action.toLowerCase().includes(logFilters.action.toLowerCase())
    );
  }

  if (logFilters.user) {
    logs = logs.filter(log => 
      log.user?.name?.toLowerCase().includes(logFilters.user.toLowerCase())
    );
  }

  if (logFilters.date_from) {
    logs = logs.filter(log => 
      new Date(log.created_at) >= new Date(logFilters.date_from)
    );
  }

  if (logFilters.date_to) {
    logs = logs.filter(log => 
      new Date(log.created_at) <= new Date(logFilters.date_to)
    );
  }

  return logs;
});

// Watch for settings changes
watch(
  () => settingsStore.auditSettings,
  (settings) => {
    if (settings) {
      Object.assign(auditForm, settings);
    }
  },
  { immediate: true }
);

// Watch for filter changes
watch(
  logFilters,
  () => {
    // Debounce the filter application
    // In a real implementation, you might want to add debouncing here
  },
  { deep: true }
);

// Methods
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString();
};

const formatChanges = (log: AuditLogEntry) => {
  const changes: any = {};
  
  if (log.old_values) {
    changes.old = log.old_values;
  }
  
  if (log.new_values) {
    changes.new = log.new_values;
  }
  
  return JSON.stringify(changes, null, 2);
};

const viewLogDetails = (log: AuditLogEntry) => {
  selectedLog.value = log;
};

const refreshAuditLogs = async () => {
  try {
    isLoadingLogs.value = true;
    await settingsStore.fetchAuditLogs(logFilters);
  } catch (error) {
    showNotification({
      type: "error",
      title: "Refresh failed",
      message: "Failed to refresh audit logs",
    });
  } finally {
    isLoadingLogs.value = false;
  }
};

const exportAuditLogs = async () => {
  try {
    isExportingLogs.value = true;
    
    const result = await settingsStore.exportAuditLogs(auditForm.export_format || "json", logFilters);
    
    if (result.success) {
      // Create download link
      const blob = new Blob([result.data], { 
        type: auditForm.export_format === "csv" ? "text/csv" : "application/json" 
      });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `audit-logs-${new Date().toISOString().split('T')[0]}.${auditForm.export_format}`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      showNotification({
        type: "success",
        title: "Export successful",
        message: "Audit logs have been exported successfully",
      });
    } else {
      showNotification({
        type: "error",
        title: "Export failed",
        message: result.error || "Failed to export audit logs",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Export failed",
      message: "An unexpected error occurred during export",
    });
  } finally {
    isExportingLogs.value = false;
  }
};

const saveAuditSettings = async () => {
  try {
    isLoading.value = true;
    
    const result = await settingsStore.updateAuditSettings(auditForm);
    
    if (result.success) {
      showNotification({
        type: "success",
        title: "Audit settings saved",
        message: "Your audit log settings have been updated successfully",
      });
    } else {
      showNotification({
        type: "error",
        title: "Save failed",
        message: result.error || "Failed to save audit settings",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Save failed",
      message: "An unexpected error occurred while saving",
    });
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  if (!settingsStore.auditSettings) {
    await settingsStore.fetchSettings();
  }
  await refreshAuditLogs();
});
</script>