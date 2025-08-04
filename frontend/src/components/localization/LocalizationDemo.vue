<template>
  <div class="max-w-4xl mx-auto p-6 space-y-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
      <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">
        {{ $t("localization.demo.title") }}
      </h2>

      <!-- Language Switcher Demo -->
      <div class="mb-8">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
          {{ $t("localization.demo.language_switcher") }}
        </h3>
        <div class="flex items-center space-x-4 rtl:space-x-reverse">
          <LanguageSwitcher />
          <span class="text-sm text-gray-600 dark:text-gray-400">
            {{ $t("localization.demo.current_locale") }}: {{ locale }}
          </span>
        </div>
      </div>

      <!-- Number Formatting Demo -->
      <div class="mb-8">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
          {{ $t("localization.demo.number_formatting") }}
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <NumberInput
              v-model="demoNumber"
              :label="$t('localization.demo.decimal_number')"
              format="decimal"
              :precision="2"
              :show-controls="true"
            />
          </div>
          <div>
            <NumberInput
              v-model="demoCurrency"
              :label="$t('localization.demo.currency_amount')"
              format="currency"
              :currency="locale === 'fa' ? 'IRR' : 'USD'"
              :precision="0"
            />
          </div>
          <div>
            <NumberInput
              v-model="demoPercentage"
              :label="$t('localization.demo.percentage')"
              format="percentage"
              :min="0"
              :max="100"
              :precision="1"
              suffix="%"
            />
          </div>
          <div>
            <NumberInput
              v-model="demoWeight"
              :label="$t('localization.demo.weight')"
              format="decimal"
              :precision="3"
              :min="0"
              suffix="g"
              :show-controls="true"
              :step="0.1"
            />
          </div>
        </div>

        <!-- Display formatted values -->
        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
          <h4 class="font-medium mb-2 text-gray-800 dark:text-gray-200">
            {{ $t("localization.demo.formatted_values") }}:
          </h4>
          <div class="space-y-1 text-sm">
            <div>
              {{ $t("localization.demo.decimal") }}:
              {{ formatNumber(demoNumber) }}
            </div>
            <div>
              {{ $t("localization.demo.currency") }}:
              {{
                formatCurrency(demoCurrency, locale === "fa" ? "IRR" : "USD")
              }}
            </div>
            <div>
              {{ $t("localization.demo.percentage") }}:
              {{ formatPercentage(demoPercentage) }}
            </div>
            <div>
              {{ $t("localization.demo.file_size") }}:
              {{ formatFileSize(1024 * 1024 * 2.5) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Date Picker Demo -->
      <div class="mb-8">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
          {{ $t("localization.demo.date_picker") }}
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <DatePicker
              v-model="demoDate"
              :label="$t('localization.demo.auto_calendar')"
              calendar-type="auto"
            />
          </div>
          <div>
            <DatePicker
              v-model="demoDateJalali"
              :label="$t('localization.demo.jalali_calendar')"
              calendar-type="jalali"
            />
          </div>
          <div>
            <DatePicker
              v-model="demoDateGregorian"
              :label="$t('localization.demo.gregorian_calendar')"
              calendar-type="gregorian"
            />
          </div>
        </div>

        <!-- Display formatted dates -->
        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
          <h4 class="font-medium mb-2 text-gray-800 dark:text-gray-200">
            {{ $t("localization.demo.formatted_dates") }}:
          </h4>
          <div class="space-y-1 text-sm">
            <div v-if="demoDate">
              {{ $t("localization.demo.selected_date") }}:
              {{ formatDate(demoDate) }}
            </div>
            <div v-if="demoDate">
              {{ $t("localization.demo.jalali_format") }}:
              {{ formatJalaliDate(demoDate) }}
            </div>
            <div v-if="demoDate">
              {{ $t("localization.demo.gregorian_format") }}:
              {{ formatGregorianDate(demoDate) }}
            </div>
          </div>
        </div>
      </div>

      <!-- RTL/LTR Layout Demo -->
      <div class="mb-8">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
          {{ $t("localization.demo.layout_direction") }}
        </h3>
        <div class="space-y-4">
          <div class="flex items-center space-x-4 rtl:space-x-reverse">
            <span class="text-sm"
              >{{ $t("localization.demo.current_direction") }}:</span
            >
            <span
              class="px-2 py-1 bg-primary-100 text-primary-800 rounded text-sm"
            >
              {{ locale === "fa" ? "RTL" : "LTR" }}
            </span>
          </div>

          <div
            class="p-4 border border-gray-200 dark:border-gray-600 rounded-lg"
          >
            <div class="flex items-center justify-between mb-2">
              <span class="font-medium">{{
                $t("localization.demo.sample_form")
              }}</span>
              <button
                class="px-3 py-1 bg-primary-600 text-white rounded text-sm"
              >
                {{ $t("common.save") }}
              </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <input
                type="text"
                :placeholder="$t('common.name')"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              />
              <input
                type="email"
                :placeholder="$t('auth.email')"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Validation Demo -->
      <div class="mb-8">
        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
          {{ $t("localization.demo.validation") }}
        </h3>
        <form @submit.prevent="validateForm" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label
                class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300"
              >
                {{ $t("auth.email") }} *
              </label>
              <input
                v-model="validationForm.email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                :class="{ 'border-red-500': validationErrors.email }"
              />
              <p
                v-if="validationErrors.email"
                class="mt-1 text-sm text-red-600"
              >
                {{ validationErrors.email }}
              </p>
            </div>

            <div>
              <label
                class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300"
              >
                {{ $t("validation.phone") }} *
              </label>
              <input
                v-model="validationForm.phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                :class="{ 'border-red-500': validationErrors.phone }"
              />
              <p
                v-if="validationErrors.phone"
                class="mt-1 text-sm text-red-600"
              >
                {{ validationErrors.phone }}
              </p>
            </div>
          </div>

          <button
            type="submit"
            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition-colors"
          >
            {{ $t("localization.demo.validate") }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";
import LanguageSwitcher from "../ui/LanguageSwitcher.vue";
import NumberInput from "./NumberInput.vue";
import DatePicker from "./DatePicker.vue";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { useCalendarConversion } from "@/composables/useCalendarConversion";
import { useLocaleValidation } from "@/composables/useLocaleValidation";

const { locale } = useI18n();
const { formatNumber, formatCurrency, formatPercentage, formatFileSize } =
  useNumberFormatter();
const { formatDate, formatJalaliDate, formatGregorianDate } =
  useCalendarConversion();
const { email, phone } = useLocaleValidation();

// Demo data
const demoNumber = ref(1234.56);
const demoCurrency = ref(50000);
const demoPercentage = ref(75.5);
const demoWeight = ref(12.345);
const demoDate = ref(new Date());
const demoDateJalali = ref(new Date());
const demoDateGregorian = ref(new Date());

// Validation demo
const validationForm = reactive({
  email: "",
  phone: "",
});

const validationErrors = reactive({
  email: "",
  phone: "",
});

const validateForm = async () => {
  // Clear previous errors
  validationErrors.email = "";
  validationErrors.phone = "";

  try {
    // Validate email
    await email().required().validate(validationForm.email);
  } catch (error: unknown) {
    validationErrors.email =
      error instanceof Error ? error.message : "Validation error";
  }

  try {
    // Validate phone
    await phone().required().validate(validationForm.phone);
  } catch (error: unknown) {
    validationErrors.phone =
      error instanceof Error ? error.message : "Validation error";
  }
};
</script>
