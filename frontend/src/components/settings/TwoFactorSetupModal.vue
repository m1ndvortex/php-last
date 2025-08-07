<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="$emit('close')">
    <div
      class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white dark:bg-gray-800"
      @click.stop
    >
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.security.setup_2fa") }}
        </h3>

        <!-- Step 1: Method Selection (if both) -->
        <div v-if="method === 'both' && currentStep === 1" class="space-y-4">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t("settings.security.choose_2fa_method") }}
          </p>
          
          <div class="space-y-2">
            <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
              <input
                type="radio"
                value="sms"
                v-model="selectedMethod"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
              />
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ $t("settings.security.sms_method") }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ $t("settings.security.sms_method_desc") }}
                </div>
              </div>
            </label>

            <label class="flex items-center p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
              <input
                type="radio"
                value="totp"
                v-model="selectedMethod"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
              />
              <div class="ml-3">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ $t("settings.security.totp_method") }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ $t("settings.security.totp_method_desc") }}
                </div>
              </div>
            </label>
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              {{ $t("common.cancel") }}
            </button>
            <button
              type="button"
              @click="proceedToSetup"
              :disabled="!selectedMethod"
              class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ $t("common.continue") }}
            </button>
          </div>
        </div>

        <!-- Step 2: TOTP Setup -->
        <div v-else-if="(method === 'totp' || selectedMethod === 'totp') && currentStep <= 2" class="space-y-4">
          <div v-if="!twoFactorSetup" class="text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
              {{ $t("settings.security.generating_qr") }}
            </p>
          </div>

          <div v-else class="space-y-4">
            <div class="text-center">
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                {{ $t("settings.security.scan_qr_code") }}
              </p>
              
              <!-- QR Code would be displayed here -->
              <div class="bg-white p-4 rounded-lg inline-block">
                <div class="w-48 h-48 bg-gray-200 flex items-center justify-center">
                  <span class="text-gray-500">QR Code</span>
                </div>
              </div>

              <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                {{ $t("settings.security.manual_entry") }}: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ twoFactorSetup.secret }}</code>
              </p>
            </div>

            <div>
              <label for="totp_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.security.enter_verification_code") }}
              </label>
              <input
                type="text"
                id="totp_code"
                v-model="verificationCode"
                maxlength="6"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm text-center text-lg tracking-widest"
                :placeholder="$t('settings.security.six_digit_code')"
              />
            </div>

            <!-- Backup Codes -->
            <div v-if="twoFactorSetup.backup_codes" class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
              <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">
                {{ $t("settings.security.backup_codes") }}
              </h4>
              <p class="text-xs text-yellow-700 dark:text-yellow-300 mb-3">
                {{ $t("settings.security.backup_codes_desc") }}
              </p>
              <div class="grid grid-cols-2 gap-2">
                <code
                  v-for="code in twoFactorSetup.backup_codes"
                  :key="code"
                  class="bg-yellow-100 dark:bg-yellow-800 px-2 py-1 rounded text-xs text-center"
                >
                  {{ code }}
                </code>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              {{ $t("common.cancel") }}
            </button>
            <button
              type="button"
              @click="verifyTwoFactor"
              :disabled="!verificationCode || verificationCode.length !== 6 || isLoading"
              class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isLoading">{{ $t("common.verifying") }}</span>
              <span v-else>{{ $t("common.verify") }}</span>
            </button>
          </div>
        </div>

        <!-- Step 2: SMS Setup -->
        <div v-else-if="(method === 'sms' || selectedMethod === 'sms') && currentStep <= 2" class="space-y-4">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $t("settings.security.sms_setup_desc") }}
          </p>

          <div>
            <label for="phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.security.phone_number") }}
            </label>
            <input
              type="tel"
              id="phone_number"
              v-model="phoneNumber"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              :placeholder="$t('settings.security.phone_placeholder')"
            />
          </div>

          <button
            type="button"
            @click="sendSmsCode"
            :disabled="!phoneNumber || isLoading"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isLoading">{{ $t("settings.security.sending_code") }}</span>
            <span v-else>{{ $t("settings.security.send_verification_code") }}</span>
          </button>

          <div v-if="smsSent">
            <label for="sms_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.security.enter_sms_code") }}
            </label>
            <input
              type="text"
              id="sms_code"
              v-model="verificationCode"
              maxlength="6"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm text-center text-lg tracking-widest"
              :placeholder="$t('settings.security.six_digit_code')"
            />
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              {{ $t("common.cancel") }}
            </button>
            <button
              v-if="smsSent"
              type="button"
              @click="verifyTwoFactor"
              :disabled="!verificationCode || verificationCode.length !== 6 || isLoading"
              class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isLoading">{{ $t("common.verifying") }}</span>
              <span v-else>{{ $t("common.verify") }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useSettingsStore } from "@/stores/settings";
import { useNotifications } from "@/composables/useNotifications";
import type { TwoFactorSetup } from "@/types/settings";

interface Props {
  method: "sms" | "totp" | "both";
}

interface Emits {
  (e: "close"): void;
  (e: "success"): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const settingsStore = useSettingsStore();
const { showNotification } = useNotifications();

// State
const isLoading = ref(false);
const currentStep = ref(1);
const selectedMethod = ref<"sms" | "totp">(props.method === "both" ? "sms" : props.method);
const twoFactorSetup = ref<TwoFactorSetup | null>(null);
const verificationCode = ref("");
const phoneNumber = ref("");
const smsSent = ref(false);

// Methods
const proceedToSetup = async () => {
  currentStep.value = 2;
  
  if (selectedMethod.value === "totp") {
    await setupTOTP();
  }
};

const setupTOTP = async () => {
  try {
    isLoading.value = true;
    
    const result = await settingsStore.setupTwoFactor("totp");
    
    if (result.success) {
      twoFactorSetup.value = result.data as TwoFactorSetup;
    } else {
      showNotification({
        type: "error",
        title: "Setup failed",
        message: result.error || "Failed to setup two-factor authentication",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Setup failed",
      message: "An unexpected error occurred during setup",
    });
  } finally {
    isLoading.value = false;
  }
};

const sendSmsCode = async () => {
  try {
    isLoading.value = true;
    
    const result = await settingsStore.setupTwoFactor("sms");
    
    if (result.success) {
      smsSent.value = true;
      showNotification({
        type: "success",
        title: "Code sent",
        message: "Verification code has been sent to your phone",
      });
    } else {
      showNotification({
        type: "error",
        title: "Send failed",
        message: result.error || "Failed to send verification code",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Send failed",
      message: "An unexpected error occurred while sending the code",
    });
  } finally {
    isLoading.value = false;
  }
};

const verifyTwoFactor = async () => {
  try {
    isLoading.value = true;
    
    const result = await settingsStore.verifyTwoFactor(verificationCode.value);
    
    if (result.success) {
      showNotification({
        type: "success",
        title: "Verification successful",
        message: "Two-factor authentication has been enabled",
      });
      emit("success");
    } else {
      showNotification({
        type: "error",
        title: "Verification failed",
        message: result.error || "Invalid verification code",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Verification failed",
      message: "An unexpected error occurred during verification",
    });
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  if (props.method !== "both") {
    currentStep.value = 2;
    if (props.method === "totp") {
      setupTOTP();
    }
  }
});
</script>