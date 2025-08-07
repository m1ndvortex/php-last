<template>
  <div class="space-y-8">
    <div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">
        {{ $t("settings.backup.title") }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t("settings.backup.description") }}
      </p>
    </div>

    <form @submit.prevent="saveBackupSettings" class="space-y-8">
      <!-- Automatic Backup -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-white">
              {{ $t("settings.backup.automatic_backup") }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("settings.backup.automatic_backup_description") }}
            </p>
          </div>
          <div class="flex items-center">
            <input
              id="auto_backup_enabled"
              type="checkbox"
              v-model="backupForm.auto_backup_enabled"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="auto_backup_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
              {{ $t("settings.backup.enable_auto_backup") }}
            </label>
          </div>
        </div>

        <div v-if="backupForm.auto_backup_enabled" class="space-y-6">
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div>
              <label for="backup_frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.backup.backup_frequency") }}
              </label>
              <select
                id="backup_frequency"
                v-model="backupForm.backup_frequency"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              >
                <option value="daily">{{ $t("settings.backup.daily") }}</option>
                <option value="weekly">{{ $t("settings.backup.weekly") }}</option>
                <option value="monthly">{{ $t("settings.backup.monthly") }}</option>
              </select>
            </div>

            <div>
              <label for="backup_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.backup.backup_time") }}
              </label>
              <input
                type="time"
                id="backup_time"
                v-model="backupForm.backup_time"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              />
            </div>

            <div>
              <label for="backup_retention" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.backup.backup_retention") }}
              </label>
              <div class="mt-1 relative rounded-md shadow-sm">
                <input
                  type="number"
                  id="backup_retention"
                  v-model.number="backupForm.backup_retention"
                  min="1"
                  max="365"
                  class="block w-full rounded-md border-gray-300 pr-16 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                  :placeholder="$t('settings.backup.retention_placeholder')"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                  <span class="text-gray-500 sm:text-sm">{{ $t("common.days") }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Backup Location -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.backup.backup_location") }}
        </h4>
        
        <div class="space-y-4">
          <div class="space-y-2">
            <label class="flex items-center">
              <input
                type="radio"
                value="local"
                v-model="backupForm.backup_location"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
              />
              <span class="ml-2 text-sm text-gray-900 dark:text-white">
                {{ $t("settings.backup.local_storage") }}
              </span>
            </label>
            <p class="ml-6 text-xs text-gray-500 dark:text-gray-400">
              {{ $t("settings.backup.local_storage_desc") }}
            </p>

            <label class="flex items-center">
              <input
                type="radio"
                value="cloud"
                v-model="backupForm.backup_location"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
              />
              <span class="ml-2 text-sm text-gray-900 dark:text-white">
                {{ $t("settings.backup.cloud_storage") }}
              </span>
            </label>
            <p class="ml-6 text-xs text-gray-500 dark:text-gray-400">
              {{ $t("settings.backup.cloud_storage_desc") }}
            </p>
          </div>

          <!-- Cloud Provider Settings -->
          <div v-if="backupForm.backup_location === 'cloud'" class="ml-6 space-y-4 border-l-2 border-gray-200 dark:border-gray-700 pl-4">
            <div>
              <label for="cloud_provider" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.backup.cloud_provider") }}
              </label>
              <select
                id="cloud_provider"
                v-model="backupForm.cloud_provider"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              >
                <option value="aws">Amazon S3</option>
                <option value="google">Google Cloud Storage</option>
                <option value="azure">Azure Blob Storage</option>
              </select>
            </div>

            <!-- AWS S3 Settings -->
            <div v-if="backupForm.cloud_provider === 'aws'" class="space-y-4">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label for="aws_access_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t("settings.backup.aws_access_key") }}
                  </label>
                  <input
                    type="text"
                    id="aws_access_key"
                    v-model="cloudCredentials.aws_access_key_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                    :placeholder="$t('settings.backup.aws_access_key_placeholder')"
                  />
                </div>

                <div>
                  <label for="aws_secret_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t("settings.backup.aws_secret_key") }}
                  </label>
                  <input
                    type="password"
                    id="aws_secret_key"
                    v-model="cloudCredentials.aws_secret_access_key"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                    :placeholder="$t('settings.backup.aws_secret_key_placeholder')"
                  />
                </div>

                <div>
                  <label for="aws_region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t("settings.backup.aws_region") }}
                  </label>
                  <input
                    type="text"
                    id="aws_region"
                    v-model="cloudCredentials.aws_region"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                    :placeholder="$t('settings.backup.aws_region_placeholder')"
                  />
                </div>

                <div>
                  <label for="aws_bucket" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t("settings.backup.aws_bucket") }}
                  </label>
                  <input
                    type="text"
                    id="aws_bucket"
                    v-model="cloudCredentials.aws_bucket"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                    :placeholder="$t('settings.backup.aws_bucket_placeholder')"
                  />
                </div>
              </div>
            </div>

            <button
              type="button"
              @click="testConnection"
              :disabled="isTestingConnection"
              class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isTestingConnection" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t("settings.backup.testing_connection") }}
              </span>
              <span v-else>{{ $t("settings.backup.test_connection") }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Backup Options -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.backup.backup_options") }}
        </h4>
        
        <div class="space-y-4">
          <label class="flex items-center">
            <input
              type="checkbox"
              v-model="backupForm.backup_encryption"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-900 dark:text-white">
              {{ $t("settings.backup.enable_encryption") }}
            </span>
          </label>

          <label class="flex items-center">
            <input
              type="checkbox"
              v-model="backupForm.backup_compression"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-900 dark:text-white">
              {{ $t("settings.backup.enable_compression") }}
            </span>
          </label>

          <label class="flex items-center">
            <input
              type="checkbox"
              v-model="backupForm.include_files"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-900 dark:text-white">
              {{ $t("settings.backup.include_files") }}
            </span>
          </label>

          <!-- Exclude Tables -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ $t("settings.backup.exclude_tables") }}
            </label>
            <div class="space-y-2">
              <label
                v-for="table in availableTables"
                :key="table"
                class="flex items-center"
              >
                <input
                  type="checkbox"
                  :value="table"
                  v-model="backupForm.exclude_tables"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ table }}
                </span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Manual Backup -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-white">
              {{ $t("settings.backup.manual_backup") }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("settings.backup.manual_backup_description") }}
            </p>
          </div>
          <button
            type="button"
            @click="createManualBackup"
            :disabled="isCreatingBackup"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isCreatingBackup" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ $t("settings.backup.creating_backup") }}
            </span>
            <span v-else>{{ $t("settings.backup.create_backup") }}</span>
          </button>
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
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from "vue";
import { useSettingsStore } from "@/stores/settings";
import { useNotifications } from "@/composables/useNotifications";
import type { BackupSettings } from "@/types/settings";

const settingsStore = useSettingsStore();
const { showNotification } = useNotifications();

// State
const isLoading = ref(false);
const isTestingConnection = ref(false);
const isCreatingBackup = ref(false);

const backupForm = reactive<Partial<BackupSettings>>({
  auto_backup_enabled: false,
  backup_frequency: "daily",
  backup_time: "02:00",
  backup_retention: 30,
  backup_location: "local",
  cloud_provider: "aws",
  backup_encryption: true,
  backup_compression: true,
  include_files: true,
  exclude_tables: [],
});

const cloudCredentials = reactive({
  aws_access_key_id: "",
  aws_secret_access_key: "",
  aws_region: "us-east-1",
  aws_bucket: "",
});

const availableTables = [
  "sessions",
  "password_resets",
  "failed_jobs",
  "audit_logs",
  "login_anomalies",
];

// Watch for settings changes
watch(
  () => settingsStore.backupSettings,
  (settings) => {
    if (settings) {
      Object.assign(backupForm, settings);
      if (settings.cloud_credentials) {
        Object.assign(cloudCredentials, settings.cloud_credentials);
      }
    }
  },
  { immediate: true }
);

// Methods
const testConnection = async () => {
  try {
    isTestingConnection.value = true;
    
    const result = await settingsStore.testBackupConnection();
    
    if (result.success) {
      showNotification({
        type: "success",
        title: "Connection successful",
        message: "Successfully connected to the backup storage",
      });
    } else {
      showNotification({
        type: "error",
        title: "Connection failed",
        message: result.error || "Failed to connect to backup storage",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Connection failed",
      message: "An unexpected error occurred while testing the connection",
    });
  } finally {
    isTestingConnection.value = false;
  }
};

const createManualBackup = async () => {
  try {
    isCreatingBackup.value = true;
    
    const result = await settingsStore.createBackup();
    
    if (result.success) {
      showNotification({
        type: "success",
        title: "Backup created",
        message: "Manual backup has been created successfully",
      });
    } else {
      showNotification({
        type: "error",
        title: "Backup failed",
        message: result.error || "Failed to create backup",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Backup failed",
      message: "An unexpected error occurred while creating the backup",
    });
  } finally {
    isCreatingBackup.value = false;
  }
};

const saveBackupSettings = async () => {
  try {
    isLoading.value = true;
    
    const settingsData = {
      ...backupForm,
      cloud_credentials: backupForm.backup_location === "cloud" ? cloudCredentials : undefined,
    };
    
    const result = await settingsStore.updateBackupSettings(settingsData);
    
    if (result.success) {
      showNotification({
        type: "success",
        title: "Backup settings saved",
        message: "Your backup settings have been updated successfully",
      });
    } else {
      showNotification({
        type: "error",
        title: "Save failed",
        message: result.error || "Failed to save backup settings",
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

onMounted(() => {
  if (!settingsStore.backupSettings) {
    settingsStore.fetchSettings();
  }
});
</script>