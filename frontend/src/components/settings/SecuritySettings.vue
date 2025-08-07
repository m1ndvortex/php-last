<template>
  <div class="space-y-8">
    <div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">
        {{ $t("settings.security.title") }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t("settings.security.description") }}
      </p>
    </div>

    <form @submit.prevent="saveSecuritySettings" class="space-y-8">
      <!-- Two-Factor Authentication -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-white">
              {{ $t("settings.security.two_factor_auth") }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("settings.security.two_factor_description") }}
            </p>
          </div>
          <div class="flex items-center">
            <input
              id="two_factor_enabled"
              type="checkbox"
              v-model="securityForm.two_factor_enabled"
              @change="handleTwoFactorToggle"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="two_factor_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
              {{ $t("settings.security.enable_2fa") }}
            </label>
          </div>
        </div>

        <div v-if="securityForm.two_factor_enabled" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
              {{ $t("settings.security.two_factor_method") }}
            </label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  type="radio"
                  value="sms"
                  v-model="securityForm.two_factor_method"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ $t("settings.security.sms_method") }}
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  value="totp"
                  v-model="securityForm.two_factor_method"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ $t("settings.security.totp_method") }}
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="radio"
                  value="both"
                  v-model="securityForm.two_factor_method"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">
                  {{ $t("settings.security.both_methods") }}
                </span>
              </label>
            </div>
          </div>

          <button
            type="button"
            @click="setupTwoFactor"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            {{ $t("settings.security.setup_2fa") }}
          </button>
        </div>
      </div>

      <!-- Session Management -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.security.session_management") }}
        </h4>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label for="session_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.security.session_timeout") }}
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <input
                type="number"
                id="session_timeout"
                v-model.number="securityForm.session_timeout"
                min="5"
                max="1440"
                class="block w-full rounded-md border-gray-300 pr-20 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                :placeholder="$t('settings.security.session_timeout_placeholder')"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <span class="text-gray-500 sm:text-sm">{{ $t("common.minutes") }}</span>
              </div>
            </div>
          </div>

          <div>
            <label for="max_login_attempts" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.security.max_login_attempts") }}
            </label>
            <input
              type="number"
              id="max_login_attempts"
              v-model.number="securityForm.max_login_attempts"
              min="3"
              max="10"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              :placeholder="$t('settings.security.max_login_attempts_placeholder')"
            />
          </div>

          <div>
            <label for="lockout_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.security.lockout_duration") }}
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <input
                type="number"
                id="lockout_duration"
                v-model.number="securityForm.lockout_duration"
                min="5"
                max="1440"
                class="block w-full rounded-md border-gray-300 pr-20 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                :placeholder="$t('settings.security.lockout_duration_placeholder')"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <span class="text-gray-500 sm:text-sm">{{ $t("common.minutes") }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Password Policy -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.security.password_policy") }}
        </h4>
        
        <div class="space-y-4">
          <div>
            <label for="password_min_length" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.security.password_min_length") }}
            </label>
            <input
              type="number"
              id="password_min_length"
              v-model.number="securityForm.password_min_length"
              min="6"
              max="32"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>

          <div class="space-y-2">
            <label class="flex items-center">
              <input
                type="checkbox"
                v-model="securityForm.password_require_uppercase"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-900 dark:text-white">
                {{ $t("settings.security.require_uppercase") }}
              </span>
            </label>

            <label class="flex items-center">
              <input
                type="checkbox"
                v-model="securityForm.password_require_lowercase"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-900 dark:text-white">
                {{ $t("settings.security.require_lowercase") }}
              </span>
            </label>

            <label class="flex items-center">
              <input
                type="checkbox"
                v-model="securityForm.password_require_numbers"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-900 dark:text-white">
                {{ $t("settings.security.require_numbers") }}
              </span>
            </label>

            <label class="flex items-center">
              <input
                type="checkbox"
                v-model="securityForm.password_require_symbols"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-900 dark:text-white">
                {{ $t("settings.security.require_symbols") }}
              </span>
            </label>
          </div>
        </div>
      </div>

      <!-- IP Whitelist -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h4 class="text-md font-medium text-gray-900 dark:text-white">
              {{ $t("settings.security.ip_whitelist") }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("settings.security.ip_whitelist_description") }}
            </p>
          </div>
          <div class="flex items-center">
            <input
              id="ip_whitelist_enabled"
              type="checkbox"
              v-model="securityForm.ip_whitelist_enabled"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="ip_whitelist_enabled" class="ml-2 block text-sm text-gray-900 dark:text-white">
              {{ $t("settings.security.enable_ip_whitelist") }}
            </label>
          </div>
        </div>

        <div v-if="securityForm.ip_whitelist_enabled" class="space-y-4">
          <div>
            <label for="ip_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.security.add_ip_address") }}
            </label>
            <div class="mt-1 flex rounded-md shadow-sm">
              <input
                type="text"
                id="ip_address"
                v-model="newIpAddress"
                class="flex-1 rounded-none rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                :placeholder="$t('settings.security.ip_address_placeholder')"
              />
              <button
                type="button"
                @click="addIpAddress"
                class="relative -ml-px inline-flex items-center space-x-2 rounded-r-md border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:bg-gray-600 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
              >
                {{ $t("common.add") }}
              </button>
            </div>
          </div>

          <div v-if="securityForm.ip_whitelist && securityForm.ip_whitelist.length > 0">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ $t("settings.security.whitelisted_ips") }}
            </label>
            <div class="space-y-2">
              <div
                v-for="(ip, index) in securityForm.ip_whitelist"
                :key="index"
                class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-md px-3 py-2"
              >
                <span class="text-sm text-gray-900 dark:text-white">{{ ip }}</span>
                <button
                  type="button"
                  @click="removeIpAddress(index)"
                  class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                >
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Advanced Security -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.security.advanced_security") }}
        </h4>
        
        <div class="space-y-4">
          <label class="flex items-center">
            <input
              type="checkbox"
              v-model="securityForm.login_anomaly_detection"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-900 dark:text-white">
              {{ $t("settings.security.enable_anomaly_detection") }}
            </span>
          </label>

          <div>
            <label for="audit_log_retention" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.security.audit_log_retention") }}
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <input
                type="number"
                id="audit_log_retention"
                v-model.number="securityForm.audit_log_retention"
                min="30"
                max="3650"
                class="block w-full rounded-md border-gray-300 pr-16 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                :placeholder="$t('settings.security.audit_log_retention_placeholder')"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <span class="text-gray-500 sm:text-sm">{{ $t("common.days") }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Login Anomalies -->
      <div v-if="loginAnomalies.length > 0" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.security.recent_anomalies") }}
        </h4>
        
        <div class="space-y-3">
          <div
            v-for="anomaly in loginAnomalies.slice(0, 5)"
            :key="anomaly.id"
            class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900 rounded-lg"
          >
            <div>
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ anomaly.anomaly_type.replace('_', ' ').toUpperCase() }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ anomaly.ip_address }} • {{ formatDate(anomaly.created_at) }}
              </div>
            </div>
            <div class="flex space-x-2">
              <button
                @click="updateAnomalyStatus(anomaly.id, 'approved')"
                class="text-green-600 hover:text-green-900 dark:text-green-400 text-xs font-medium"
              >
                {{ $t("common.approve") }}
              </button>
              <button
                @click="updateAnomalyStatus(anomaly.id, 'blocked')"
                class="text-red-600 hover:text-red-900 dark:text-red-400 text-xs font-medium"
              >
                {{ $t("common.block") }}
              </button>
            </div>
          </div>
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

    <!-- Two-Factor Setup Modal -->
    <TwoFactorSetupModal
      v-if="showTwoFactorModal"
      :method="securityForm.two_factor_method || 'sms'"
      @close="showTwoFactorModal = false"
      @success="handleTwoFactorSetupSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from "vue";
import { useSettingsStore } from "@/stores/settings";
import { useNotifications } from "@/composables/useNotifications";
import { XMarkIcon } from "@heroicons/vue/24/outline";
import TwoFactorSetupModal from "@/components/settings/TwoFactorSetupModal.vue";
import type { SecuritySettings } from "@/types/settings";

const settingsStore = useSettingsStore();
const { showNotification } = useNotifications();

// State
const isLoading = ref(false);
const showTwoFactorModal = ref(false);
const newIpAddress = ref("");

const securityForm = reactive<Partial<SecuritySettings>>({
  two_factor_enabled: false,
  two_factor_method: "sms",
  session_timeout: 30,
  max_login_attempts: 5,
  lockout_duration: 15,
  password_min_length: 8,
  password_require_uppercase: true,
  password_require_lowercase: true,
  password_require_numbers: true,
  password_require_symbols: false,
  ip_whitelist_enabled: false,
  ip_whitelist: [],
  audit_log_retention: 365,
  login_anomaly_detection: true,
});

// Computed
const loginAnomalies = computed(() => settingsStore.loginAnomalies);

// Watch for settings changes
watch(
  () => settingsStore.securitySettings,
  (settings) => {
    if (settings) {
      Object.assign(securityForm, settings);
    }
  },
  { immediate: true }
);

// Methods
const handleTwoFactorToggle = () => {
  if (securityForm.two_factor_enabled) {
    setupTwoFactor();
  }
};

const setupTwoFactor = () => {
  showTwoFactorModal.value = true;
};

const handleTwoFactorSetupSuccess = () => {
  showTwoFactorModal.value = false;
  showNotification({
    type: "success",
    title: "Two-factor authentication enabled",
    message: "Your account is now more secure with 2FA enabled",
  });
};

const addIpAddress = () => {
  if (!newIpAddress.value.trim()) return;
  
  // Basic IP validation
  const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
  if (!ipRegex.test(newIpAddress.value.trim())) {
    showNotification({
      type: "error",
      title: "Invalid IP address",
      message: "Please enter a valid IP address",
    });
    return;
  }

  if (!securityForm.ip_whitelist) {
    securityForm.ip_whitelist = [];
  }

  if (!securityForm.ip_whitelist.includes(newIpAddress.value.trim())) {
    securityForm.ip_whitelist.push(newIpAddress.value.trim());
    newIpAddress.value = "";
  } else {
    showNotification({
      type: "warning",
      title: "IP already exists",
      message: "This IP address is already in the whitelist",
    });
  }
};

const removeIpAddress = (index: number) => {
  if (securityForm.ip_whitelist) {
    securityForm.ip_whitelist.splice(index, 1);
  }
};

const updateAnomalyStatus = async (id: number, status: "approved" | "blocked") => {
  try {
    const result = await settingsStore.updateAnomalyStatus(id, status);
    
    if (result.success) {
      showNotification({
        type: "success",
        title: "Status updated",
        message: `Login anomaly has been ${status}`,
      });
    } else {
      showNotification({
        type: "error",
        title: "Update failed",
        message: result.error || "Failed to update anomaly status",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Update failed",
      message: "An unexpected error occurred",
    });
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const saveSecuritySettings = async () => {
  try {
    isLoading.value = true;
    
    const result = await settingsStore.updateSecuritySettings(securityForm);
    
    if (result.success) {
      showNotification({
        type: "success",
        title: "Security settings saved",
        message: "Your security settings have been updated successfully",
      });
    } else {
      showNotification({
        type: "error",
        title: "Save failed",
        message: result.error || "Failed to save security settings",
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
  if (!settingsStore.securitySettings) {
    await settingsStore.fetchSettings();
  }
  await settingsStore.fetchLoginAnomalies();
});
</script>