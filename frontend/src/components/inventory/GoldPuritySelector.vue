<template>
  <div class="relative gold-purity-selector" :dir="isRTL ? 'rtl' : 'ltr'">
    <div class="flex">
      <select
        :id="id"
        :value="selectedStandard"
        @input="handleStandardChange"
        class="flex-1 border-gray-300 dark:border-gray-600 rounded-l-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
        :class="{ 'text-right': isRTL }"
      >
        <option value="">
          {{ $t("inventory.categories.gold_purity_placeholder") }}
        </option>
        <option
          v-for="option in standardOptions"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
        <option value="custom">{{ $t("common.custom") }}</option>
      </select>

      <input
        v-if="showCustomInput"
        v-model.number="customValue"
        @input="handleCustomInput"
        type="number"
        step="0.001"
        min="0"
        max="24"
        :placeholder="$t('inventory.categories.gold_purity_placeholder')"
        class="flex-1 border-l-0 border-gray-300 dark:border-gray-600 rounded-r-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
        :class="{ 'text-right': isRTL }"
        :dir="isRTL ? 'rtl' : 'ltr'"
      />
    </div>

    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
      {{ $t("inventory.gold_purity_help") }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useApi } from "@/composables/useApi";
import { useLocale } from "@/composables/useLocale";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { apiService } from "@/services/api";

interface Props {
  modelValue?: number | null;
  id?: string;
  placeholder?: string;
}

interface Emits {
  (e: "update:modelValue", value: number | null): void;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
});

const emit = defineEmits<Emits>();

const { t, locale } = useI18n();
const { execute } = useApi();
const { isRTL, formatGoldPurity } = useLocale();
const { getGoldPurityOptions, toPersianNumerals, formatNumber } = useNumberFormatter();

// State
const selectedStandard = ref("");
const customValue = ref<number | null>(null);
const backendOptions = ref([]);

// Computed
const standardOptions = computed(() => {
  if (backendOptions.value.length > 0) {
    return backendOptions.value.map((option: any) => ({
      value: option.purity,
      label: option.label,
    }));
  }

  // Use the composable for consistent gold purity options
  return getGoldPurityOptions();
});

// Helper function to format numbers in Persian
const formatPersianNumber = (num: number): string => {
  const persianDigits = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
  return num
    .toString()
    .replace(/\d/g, (digit) => persianDigits[parseInt(digit)]);
};

const showCustomInput = computed(() => {
  return selectedStandard.value === "custom";
});

// Methods
const handleStandardChange = (event: Event) => {
  const target = event.target as HTMLSelectElement;
  const value = target.value;

  selectedStandard.value = value;

  if (value === "custom") {
    customValue.value = props.modelValue;
  } else if (value === "") {
    emit("update:modelValue", null);
  } else {
    const numericValue = parseFloat(value);
    emit("update:modelValue", numericValue);
  }
};

const handleCustomInput = () => {
  if (customValue.value !== null && customValue.value !== undefined) {
    emit("update:modelValue", customValue.value);
  } else {
    emit("update:modelValue", null);
  }
};

// Initialize component based on modelValue
const initializeComponent = () => {
  if (props.modelValue !== null && props.modelValue !== undefined) {
    const standardOption = standardOptions.value.find(
      (option) => option.value === props.modelValue,
    );

    if (standardOption) {
      selectedStandard.value = String(standardOption.value);
    } else {
      selectedStandard.value = "custom";
      customValue.value = props.modelValue;
    }
  } else {
    selectedStandard.value = "";
    customValue.value = null;
  }
};

// Watchers
watch(() => props.modelValue, initializeComponent);

// Methods
const fetchGoldPurityOptions = async () => {
  try {
    const result = await execute(() => 
      apiService.get("/api/inventory/gold-purity-options")
    );
    if (result) {
      backendOptions.value = result.standard_purities || [];
    }
  } catch (error) {
    console.error("Failed to fetch gold purity options:", error);
  }
};

// Lifecycle
onMounted(() => {
  fetchGoldPurityOptions();
  initializeComponent();
});
</script>
