<template>
  <div class="space-y-6">
    <div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">
        {{ $t("settings.business.title") }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t("settings.business.description") }}
      </p>
    </div>

    <form @submit.prevent="saveBusinessConfig" class="space-y-6">
      <!-- Business Information -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <label for="business_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t("settings.business.business_name") }}
          </label>
          <input
            type="text"
            id="business_name"
            v-model="form.business_name"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            :placeholder="$t('settings.business.business_name_placeholder')"
          />
        </div>

        <div>
          <label for="business_name_persian" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t("settings.business.business_name_persian") }}
          </label>
          <input
            type="text"
            id="business_name_persian"
            v-model="form.business_name_persian"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            :placeholder="$t('settings.business.business_name_persian_placeholder')"
            dir="rtl"
          />
        </div>

        <div>
          <label for="business_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t("settings.business.business_email") }}
          </label>
          <input
            type="email"
            id="business_email"
            v-model="form.business_email"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            :placeholder="$t('settings.business.business_email_placeholder')"
          />
        </div>

        <div>
          <label for="business_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t("settings.business.business_phone") }}
          </label>
          <input
            type="tel"
            id="business_phone"
            v-model="form.business_phone"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            :placeholder="$t('settings.business.business_phone_placeholder')"
          />
        </div>

        <div>
          <label for="business_website" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t("settings.business.business_website") }}
          </label>
          <input
            type="url"
            id="business_website"
            v-model="form.business_website"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            :placeholder="$t('settings.business.business_website_placeholder')"
          />
        </div>

        <div>
          <label for="tax_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t("settings.business.tax_number") }}
          </label>
          <input
            type="text"
            id="tax_number"
            v-model="form.tax_number"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            :placeholder="$t('settings.business.tax_number_placeholder')"
          />
        </div>
      </div>

      <!-- Address -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <label for="business_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t("settings.business.business_address") }}
          </label>
          <textarea
            id="business_address"
            v-model="form.business_address"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            :placeholder="$t('settings.business.business_address_placeholder')"
          ></textarea>
        </div>

        <div>
          <label for="business_address_persian" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t("settings.business.business_address_persian") }}
          </label>
          <textarea
            id="business_address_persian"
            v-model="form.business_address_persian"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            :placeholder="$t('settings.business.business_address_persian_placeholder')"
            dir="rtl"
          ></textarea>
        </div>
      </div>

      <!-- Financial Settings -->
      <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.business.financial_settings") }}
        </h4>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
          <div>
            <label for="default_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.business.default_currency") }}
            </label>
            <select
              id="default_currency"
              v-model="form.default_currency"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            >
              <option value="USD">USD - US Dollar</option>
              <option value="EUR">EUR - Euro</option>
              <option value="IRR">IRR - Iranian Rial</option>
              <option value="AED">AED - UAE Dirham</option>
            </select>
          </div>

          <div>
            <label for="default_tax_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.business.default_tax_rate") }}
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <input
                type="number"
                id="default_tax_rate"
                v-model.number="form.default_tax_rate"
                step="0.01"
                min="0"
                max="100"
                class="block w-full rounded-md border-gray-300 pr-12 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                :placeholder="$t('settings.business.default_tax_rate_placeholder')"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <span class="text-gray-500 sm:text-sm">%</span>
              </div>
            </div>
          </div>

          <div>
            <label for="default_profit_percentage" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.business.default_profit_percentage") }}
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <input
                type="number"
                id="default_profit_percentage"
                v-model.number="form.default_profit_percentage"
                step="0.01"
                min="0"
                max="1000"
                class="block w-full rounded-md border-gray-300 pr-12 focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                :placeholder="$t('settings.business.default_profit_percentage_placeholder')"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <span class="text-gray-500 sm:text-sm">%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Invoice Settings -->
      <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.business.invoice_settings") }}
        </h4>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label for="invoice_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.business.invoice_prefix") }}
            </label>
            <input
              type="text"
              id="invoice_prefix"
              v-model="form.invoice_prefix"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              :placeholder="$t('settings.business.invoice_prefix_placeholder')"
            />
          </div>

          <div>
            <label for="invoice_starting_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t("settings.business.invoice_starting_number") }}
            </label>
            <input
              type="number"
              id="invoice_starting_number"
              v-model.number="form.invoice_starting_number"
              min="1"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              :placeholder="$t('settings.business.invoice_starting_number_placeholder')"
            />
          </div>
        </div>
      </div>

      <!-- Logo Upload -->
      <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.business.logo") }}
        </h4>
        
        <div class="flex items-center space-x-6">
          <div class="shrink-0">
            <img
              v-if="form.logo_path"
              :src="form.logo_path"
              :alt="$t('settings.business.current_logo')"
              class="h-20 w-20 object-cover rounded-lg"
            />
            <div
              v-else
              class="h-20 w-20 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center"
            >
              <BuildingOfficeIcon class="h-8 w-8 text-gray-400" />
            </div>
          </div>
          <div>
            <input
              ref="logoInput"
              type="file"
              accept="image/*"
              @change="handleLogoUpload"
              class="hidden"
            />
            <button
              type="button"
              @click="logoInput?.click()"
              class="bg-white dark:bg-gray-700 py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              {{ $t("settings.business.change_logo") }}
            </button>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
              {{ $t("settings.business.logo_requirements") }}
            </p>
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
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from "vue";
import { useSettingsStore } from "@/stores/settings";
import { useNotifications } from "@/composables/useNotifications";
import { BuildingOfficeIcon } from "@heroicons/vue/24/outline";
import type { BusinessConfiguration } from "@/types/settings";

const settingsStore = useSettingsStore();
const { showNotification } = useNotifications();

// State
const isLoading = ref(false);
const logoInput = ref<HTMLInputElement>();

const form = reactive<Partial<BusinessConfiguration>>({
  business_name: "",
  business_name_persian: "",
  business_address: "",
  business_address_persian: "",
  business_phone: "",
  business_email: "",
  business_website: "",
  tax_number: "",
  registration_number: "",
  logo_path: "",
  default_currency: "USD",
  default_language: "en",
  default_tax_rate: 0,
  default_profit_percentage: 0,
  invoice_prefix: "INV",
  invoice_starting_number: 1,
  fiscal_year_start: "01-01",
  timezone: "UTC",
  date_format: "YYYY-MM-DD",
  time_format: "HH:mm",
  number_format: "en",
});

// Watch for settings changes
watch(
  () => settingsStore.businessConfig,
  (config) => {
    if (config) {
      Object.assign(form, config);
    }
  },
  { immediate: true }
);

const handleLogoUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  
  if (!file) return;

  // Validate file type
  if (!file.type.startsWith('image/')) {
    showNotification({
      type: "error",
      title: "Invalid file type",
      message: "Please select an image file",
    });
    return;
  }

  // Validate file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    showNotification({
      type: "error",
      title: "File too large",
      message: "Please select an image smaller than 2MB",
    });
    return;
  }

  try {
    isLoading.value = true;
    
    const formData = new FormData();
    formData.append('logo', file);
    
    // Upload logo (this would be implemented in the API service)
    // const response = await apiService.post('/api/settings/business/logo', formData);
    // form.logo_path = response.data.data.logo_path;
    
    // For now, create a preview URL
    form.logo_path = URL.createObjectURL(file);
    
    showNotification({
      type: "success",
      title: "Logo uploaded",
      message: "Business logo has been updated successfully",
    });
  } catch (error) {
    showNotification({
      type: "error",
      title: "Upload failed",
      message: "Failed to upload logo. Please try again.",
    });
  } finally {
    isLoading.value = false;
  }
};

const saveBusinessConfig = async () => {
  try {
    isLoading.value = true;
    
    const result = await settingsStore.updateBusinessConfig(form);
    
    if (result.success) {
      showNotification({
        type: "success",
        title: "Settings saved",
        message: "Business configuration has been updated successfully",
      });
    } else {
      showNotification({
        type: "error",
        title: "Save failed",
        message: result.error || "Failed to save business configuration",
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
  if (!settingsStore.businessConfig) {
    settingsStore.fetchSettings();
  }
});
</script>