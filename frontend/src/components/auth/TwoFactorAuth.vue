<template>
  <div>
    <h4 class="text-md font-medium text-gray-900 mb-4">
      {{ $t("auth.two_factor") }}
    </h4>

    <div v-if="!is2FAEnabled" class="space-y-4">
      <p class="text-sm text-gray-600">
        {{ $t("auth.2fa_description") }}
      </p>

      <button @click="enable2FA" :disabled="isLoading" class="btn btn-primary">
        <span v-if="isLoading">{{ $t("common.loading") }}</span>
        <span v-else>{{ $t("auth.enable_2fa") }}</span>
      </button>
    </div>

    <div v-else class="space-y-4">
      <div
        class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-md"
      >
        <div class="flex items-center">
          <svg
            class="h-5 w-5 text-green-400 mr-2"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="text-sm text-green-800">{{
            $t("auth.2fa_enabled_status")
          }}</span>
        </div>

        <button
          @click="disable2FA"
          :disabled="isLoading"
          class="btn btn-sm btn-outline-danger"
        >
          {{ $t("auth.disable_2fa") }}
        </button>
      </div>

      <div class="text-sm text-gray-600">
        <p>{{ $t("auth.2fa_backup_codes_info") }}</p>
        <button
          @click="showBackupCodes"
          class="text-primary-600 hover:text-primary-500 underline"
        >
          {{ $t("auth.view_backup_codes") }}
        </button>
      </div>
    </div>

    <!-- 2FA Setup Modal -->
    <div
      v-if="showSetupModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="closeSetupModal"
    >
      <div
        class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
        @click.stop
      >
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ $t("auth.setup_2fa") }}
          </h3>

          <div v-if="setupStep === 1" class="space-y-4">
            <p class="text-sm text-gray-600">
              {{ $t("auth.scan_qr_code") }}
            </p>

            <div class="flex justify-center">
              <div class="p-4 bg-white border-2 border-gray-300 rounded-lg">
                <!-- QR Code placeholder -->
                <div
                  class="w-48 h-48 bg-gray-100 flex items-center justify-center"
                >
                  <span class="text-gray-500">QR Code</span>
                </div>
              </div>
            </div>

            <div class="text-center">
              <p class="text-xs text-gray-500 mb-2">
                {{ $t("auth.manual_entry") }}
              </p>
              <code class="text-xs bg-gray-100 p-2 rounded">{{
                secretKey
              }}</code>
            </div>

            <button @click="setupStep = 2" class="w-full btn btn-primary">
              {{ $t("common.next") }}
            </button>
          </div>

          <div v-if="setupStep === 2" class="space-y-4">
            <p class="text-sm text-gray-600">
              {{ $t("auth.enter_verification_code") }}
            </p>

            <div>
              <label for="verification_code" class="form-label">
                {{ $t("auth.2fa_code") }}
              </label>
              <input
                id="verification_code"
                v-model="verificationCode"
                type="text"
                maxlength="6"
                class="form-input text-center text-lg tracking-widest"
                placeholder="000000"
              />
            </div>

            <div class="flex space-x-3">
              <button @click="setupStep = 1" class="flex-1 btn btn-outline">
                {{ $t("common.back") }}
              </button>
              <button
                @click="verify2FA"
                :disabled="verificationCode.length !== 6 || isLoading"
                class="flex-1 btn btn-primary"
              >
                <span v-if="isLoading">{{ $t("common.verifying") }}</span>
                <span v-else>{{ $t("auth.verify_2fa") }}</span>
              </button>
            </div>
          </div>

          <div v-if="setupStep === 3" class="space-y-4">
            <div class="text-center">
              <svg
                class="mx-auto h-12 w-12 text-green-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 48 48"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <h3 class="mt-2 text-lg font-medium text-gray-900">
                {{ $t("auth.2fa_setup_complete") }}
              </h3>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
              <p class="text-sm text-yellow-800 mb-2">
                {{ $t("auth.backup_codes_important") }}
              </p>
              <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                <div
                  v-for="code in backupCodes"
                  :key="code"
                  class="bg-white p-1 rounded"
                >
                  {{ code }}
                </div>
              </div>
            </div>

            <button @click="completeSetup" class="w-full btn btn-primary">
              {{ $t("common.done") }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useNotifications } from "@/composables/useNotifications";
import { apiService } from "@/services/api";

const authStore = useAuthStore();
const { showSuccess, showError } = useNotifications();

const isLoading = ref(false);
const showSetupModal = ref(false);
const setupStep = ref(1);
const secretKey = ref("");
const verificationCode = ref("");
const backupCodes = ref<string[]>([]);

const is2FAEnabled = computed(() => {
  // Check if user has 2FA enabled from auth store
  return authStore.user?.two_factor_enabled || false;
});

const enable2FA = async () => {
  try {
    isLoading.value = true;

    // Call API to generate 2FA secret
    const response = await apiService.auth.enable2FA();
    secretKey.value = response.data.secret;

    showSetupModal.value = true;
    setupStep.value = 1;
  } catch (error: any) {
    showError(
      "2FA Setup Failed",
      error.response?.data?.message || "Failed to enable 2FA",
    );
  } finally {
    isLoading.value = false;
  }
};

const disable2FA = async () => {
  const password = prompt("Please enter your password to disable 2FA:");
  if (!password) return;

  try {
    isLoading.value = true;

    // Call API to disable 2FA
    await apiService.auth.disable2FA({ password });

    showSuccess("2FA Disabled", "Two-factor authentication has been disabled");
  } catch (error: any) {
    showError(
      "Disable Failed",
      error.response?.data?.message || "Failed to disable 2FA",
    );
  } finally {
    isLoading.value = false;
  }
};

const verify2FA = async () => {
  try {
    isLoading.value = true;

    // Call API to verify 2FA code
    const response = await apiService.auth.verify2FA({
      code: verificationCode.value,
    });
    backupCodes.value = response.data.backup_codes;

    setupStep.value = 3;
  } catch (error: any) {
    showError(
      "Verification Failed",
      error.response?.data?.message || "Invalid verification code",
    );
  } finally {
    isLoading.value = false;
  }
};

const completeSetup = () => {
  showSetupModal.value = false;
  setupStep.value = 1;
  verificationCode.value = "";
  showSuccess(
    "2FA Enabled",
    "Two-factor authentication has been enabled successfully",
  );
};

const closeSetupModal = () => {
  showSetupModal.value = false;
  setupStep.value = 1;
  verificationCode.value = "";
};

const showBackupCodes = () => {
  // TODO: Implement backup codes modal
  alert("Backup codes functionality coming soon");
};
</script>
