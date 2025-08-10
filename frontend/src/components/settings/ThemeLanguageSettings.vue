<template>
  <div class="space-y-8">
    <div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">
        {{ $t("settings.appearance.title") }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $t("settings.appearance.description") }}
      </p>
    </div>

    <form @submit.prevent="saveSettings" class="space-y-8">
      <!-- Theme Settings -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.appearance.theme_settings") }}
        </h4>

        <div class="space-y-6">
          <!-- Theme Mode -->
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"
            >
              {{ $t("settings.appearance.theme_mode") }}
            </label>
            <div class="grid grid-cols-3 gap-3">
              <label
                v-for="mode in themeModes"
                :key="mode.value"
                :class="[
                  themeForm.mode === mode.value
                    ? 'border-blue-500 ring-2 ring-blue-500'
                    : 'border-gray-300 dark:border-gray-600',
                  'relative border rounded-lg p-4 flex cursor-pointer focus:outline-none',
                ]"
              >
                <input
                  type="radio"
                  :value="mode.value"
                  v-model="themeForm.mode"
                  class="sr-only"
                />
                <div class="flex-1">
                  <div class="flex items-center">
                    <component
                      :is="mode.icon"
                      class="h-5 w-5 text-gray-400 mr-3"
                    />
                    <div class="text-sm">
                      <div class="font-medium text-gray-900 dark:text-white">
                        {{ $t(`settings.appearance.modes.${mode.value}`) }}
                      </div>
                      <div class="text-gray-500 dark:text-gray-400">
                        {{ $t(`settings.appearance.modes.${mode.value}_desc`) }}
                      </div>
                    </div>
                  </div>
                </div>
              </label>
            </div>
          </div>

          <!-- Color Scheme -->
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"
            >
              {{ $t("settings.appearance.color_scheme") }}
            </label>
            <div class="grid grid-cols-4 gap-3">
              <label
                v-for="color in colorSchemes"
                :key="color.value"
                :class="[
                  themeForm.primary_color === color.value
                    ? 'ring-2 ring-offset-2 ring-blue-500'
                    : '',
                  'relative rounded-lg p-3 cursor-pointer focus:outline-none',
                ]"
                :style="{ backgroundColor: color.value }"
              >
                <input
                  type="radio"
                  :value="color.value"
                  v-model="themeForm.primary_color"
                  class="sr-only"
                />
                <div class="flex items-center justify-center h-8">
                  <CheckIcon
                    v-if="themeForm.primary_color === color.value"
                    class="h-5 w-5 text-white"
                  />
                </div>
                <div class="text-center mt-2">
                  <div class="text-xs font-medium text-white">
                    {{ color.name }}
                  </div>
                </div>
              </label>
            </div>
          </div>

          <!-- Layout Options -->
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label
                for="sidebar_style"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("settings.appearance.sidebar_style") }}
              </label>
              <select
                id="sidebar_style"
                v-model="themeForm.sidebar_style"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              >
                <option value="expanded">
                  {{ $t("settings.appearance.sidebar_expanded") }}
                </option>
                <option value="collapsed">
                  {{ $t("settings.appearance.sidebar_collapsed") }}
                </option>
                <option value="overlay">
                  {{ $t("settings.appearance.sidebar_overlay") }}
                </option>
              </select>
            </div>

            <div>
              <label
                for="font_size"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("settings.appearance.font_size") }}
              </label>
              <select
                id="font_size"
                v-model="themeForm.font_size"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              >
                <option value="small">
                  {{ $t("settings.appearance.font_small") }}
                </option>
                <option value="medium">
                  {{ $t("settings.appearance.font_medium") }}
                </option>
                <option value="large">
                  {{ $t("settings.appearance.font_large") }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Language Settings -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.appearance.language_settings") }}
        </h4>

        <div class="space-y-6">
          <!-- Default Language -->
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"
            >
              {{ $t("settings.appearance.default_language") }}
            </label>
            <div class="grid grid-cols-2 gap-3">
              <label
                v-for="lang in languages"
                :key="lang.code"
                :class="[
                  languageForm.default_language === lang.code
                    ? 'border-blue-500 ring-2 ring-blue-500'
                    : 'border-gray-300 dark:border-gray-600',
                  'relative border rounded-lg p-4 flex cursor-pointer focus:outline-none',
                ]"
              >
                <input
                  type="radio"
                  :value="lang.code"
                  v-model="languageForm.default_language"
                  class="sr-only"
                />
                <div class="flex-1">
                  <div class="flex items-center">
                    <span class="text-2xl mr-3">{{ lang.flag }}</span>
                    <div class="text-sm">
                      <div class="font-medium text-gray-900 dark:text-white">
                        {{ lang.name }}
                      </div>
                      <div class="text-gray-500 dark:text-gray-400">
                        {{
                          lang.dir === "rtl" ? "Right-to-Left" : "Left-to-Right"
                        }}
                      </div>
                    </div>
                  </div>
                </div>
              </label>
            </div>
          </div>

          <!-- RTL Support -->
          <div class="flex items-center">
            <input
              id="rtl_enabled"
              type="checkbox"
              v-model="languageForm.rtl_enabled"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label
              for="rtl_enabled"
              class="ml-2 block text-sm text-gray-900 dark:text-white"
            >
              {{ $t("settings.appearance.enable_rtl") }}
            </label>
          </div>

          <!-- Date and Number Formats -->
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label
                for="calendar_type"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("settings.appearance.calendar_type") }}
              </label>
              <select
                id="calendar_type"
                v-model="languageForm.calendar_type"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              >
                <option value="gregorian">
                  {{ $t("settings.appearance.gregorian") }}
                </option>
                <option value="jalali">
                  {{ $t("settings.appearance.jalali") }}
                </option>
              </select>
            </div>

            <div>
              <label
                for="number_format"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("settings.appearance.number_format") }}
              </label>
              <select
                id="number_format"
                v-model="languageForm.number_format"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              >
                <option value="en">
                  {{ $t("settings.appearance.english_numerals") }}
                </option>
                <option value="fa">
                  {{ $t("settings.appearance.persian_numerals") }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label
                for="date_format"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("settings.appearance.date_format") }}
              </label>
              <select
                id="date_format"
                v-model="languageForm.date_format"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              >
                <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                <option value="DD-MM-YYYY">DD-MM-YYYY</option>
              </select>
            </div>

            <div>
              <label
                for="time_format"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("settings.appearance.time_format") }}
              </label>
              <select
                id="time_format"
                v-model="languageForm.time_format"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              >
                <option value="HH:mm">24 Hour (HH:mm)</option>
                <option value="hh:mm A">12 Hour (hh:mm AM/PM)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Preview Section -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
          {{ $t("settings.appearance.preview") }}
        </h4>

        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
          <div class="flex items-center justify-between mb-4">
            <h5 class="text-sm font-medium text-gray-900 dark:text-white">
              {{ $t("settings.appearance.preview_sample") }}
            </h5>
            <div class="flex items-center space-x-2">
              <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ formatPreviewDate() }}
              </span>
              <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ formatPreviewNumber(1234.56) }}
              </span>
            </div>
          </div>

          <div
            :class="[
              'p-4 rounded-lg',
              themeForm.primary_color
                ? `bg-opacity-10`
                : 'bg-blue-50 dark:bg-blue-900',
            ]"
            :style="{ backgroundColor: themeForm.primary_color + '20' }"
          >
            <div class="flex items-center">
              <div
                class="w-3 h-3 rounded-full mr-3"
                :style="{ backgroundColor: themeForm.primary_color }"
              ></div>
              <span class="text-sm font-medium text-gray-900 dark:text-white">
                {{ $t("settings.appearance.sample_content") }}
              </span>
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
            <svg
              class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
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
import { useCalendarConversion } from "@/composables/useCalendarConversion";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import {
  SunIcon,
  MoonIcon,
  ComputerDesktopIcon,
  CheckIcon,
} from "@heroicons/vue/24/outline";
import type { ThemeSettings, LanguageSettings } from "@/types/settings";

const settingsStore = useSettingsStore();
const { showNotification } = useNotifications();
const { formatJalaliDate, formatGregorianDate } = useCalendarConversion();
const { formatNumber } = useNumberFormatter();

// State
const isLoading = ref(false);

const themeForm = reactive<Partial<ThemeSettings>>({
  mode: "light",
  primary_color: "#3B82F6",
  secondary_color: "#6B7280",
  accent_color: "#10B981",
  sidebar_style: "expanded",
  header_style: "fixed",
  font_size: "medium",
  border_radius: "medium",
});

const languageForm = reactive<Partial<LanguageSettings>>({
  default_language: "en",
  rtl_enabled: true,
  date_format: "YYYY-MM-DD",
  time_format: "HH:mm",
  number_format: "en",
  currency_format: "USD",
  calendar_type: "gregorian",
});

const themeModes = [
  { value: "light", icon: SunIcon },
  { value: "dark", icon: MoonIcon },
  { value: "system", icon: ComputerDesktopIcon },
];

const colorSchemes = [
  { name: "Blue", value: "#3B82F6" },
  { name: "Green", value: "#10B981" },
  { name: "Purple", value: "#8B5CF6" },
  { name: "Pink", value: "#EC4899" },
  { name: "Indigo", value: "#6366F1" },
  { name: "Red", value: "#EF4444" },
  { name: "Orange", value: "#F59E0B" },
  { name: "Teal", value: "#14B8A6" },
];

const languages = [
  { code: "en", name: "English", flag: "🇺🇸", dir: "ltr" },
  { code: "fa", name: "فارسی", flag: "🇮🇷", dir: "rtl" },
];

// Watch for settings changes
watch(
  () => settingsStore.themeSettings,
  (settings) => {
    if (settings) {
      Object.assign(themeForm, settings);
    }
  },
  { immediate: true },
);

watch(
  () => settingsStore.languageSettings,
  (settings) => {
    if (settings) {
      Object.assign(languageForm, settings);
    }
  },
  { immediate: true },
);

// Methods
const formatPreviewDate = () => {
  const now = new Date();
  if (languageForm.calendar_type === "jalali") {
    return formatJalaliDate(now, languageForm.date_format || "YYYY-MM-DD");
  } else {
    return formatGregorianDate(now, languageForm.date_format || "YYYY-MM-DD");
  }
};

const formatPreviewNumber = (number: number) => {
  return formatNumber(number, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
    useGrouping: true,
  });
};

const saveSettings = async () => {
  try {
    isLoading.value = true;

    // Save theme settings
    const themeResult = await settingsStore.updateThemeSettings(themeForm);
    if (!themeResult.success) {
      throw new Error(themeResult.error || "Theme update failed");
    }

    // Save language settings
    const languageResult =
      await settingsStore.updateLanguageSettings(languageForm);
    if (!languageResult.success) {
      throw new Error(languageResult.error || "Language update failed");
    }

    showNotification({
      type: "success",
      title: "Settings saved",
      message: "Theme and language settings have been updated successfully",
    });

    // Apply theme changes immediately
    applyThemeChanges();
  } catch (error: any) {
    showNotification({
      type: "error",
      title: "Save failed",
      message: error.message || "Failed to save settings",
    });
  } finally {
    isLoading.value = false;
  }
};

const applyThemeChanges = () => {
  // Apply theme mode
  const html = document.documentElement;
  if (themeForm.mode === "dark") {
    html.classList.add("dark");
  } else if (themeForm.mode === "light") {
    html.classList.remove("dark");
  } else {
    // System preference
    const prefersDark = window.matchMedia(
      "(prefers-color-scheme: dark)",
    ).matches;
    if (prefersDark) {
      html.classList.add("dark");
    } else {
      html.classList.remove("dark");
    }
  }

  // Apply RTL/LTR
  if (languageForm.default_language === "fa" && languageForm.rtl_enabled) {
    html.setAttribute("dir", "rtl");
  } else {
    html.setAttribute("dir", "ltr");
  }

  // Apply primary color as CSS custom property
  if (themeForm.primary_color) {
    html.style.setProperty("--primary-color", themeForm.primary_color);
  }

  // Apply font size
  if (themeForm.font_size) {
    html.classList.remove("text-sm", "text-base", "text-lg");
    switch (themeForm.font_size) {
      case "small":
        html.classList.add("text-sm");
        break;
      case "large":
        html.classList.add("text-lg");
        break;
      default:
        html.classList.add("text-base");
    }
  }
};

onMounted(() => {
  if (!settingsStore.themeSettings || !settingsStore.languageSettings) {
    settingsStore.fetchSettings();
  }
});
</script>
