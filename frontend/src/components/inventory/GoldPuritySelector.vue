<template>
  <div class="relative">
    <div class="flex">
      <select
        :id="id"
        :value="selectedStandard"
        @input="handleStandardChange"
        class="flex-1 border-gray-300 dark:border-gray-600 rounded-l-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
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

const { t } = useI18n();

// State
const selectedStandard = ref("");
const customValue = ref<number | null>(null);

// Computed
const standardOptions = computed(() => {
  const locale = useI18n().locale.value;
  const isRTL = locale === "fa";

  const options = [
    { value: 24, karat: 24, purity: 99.9 },
    { value: 22, karat: 22, purity: 91.7 },
    { value: 21, karat: 21, purity: 87.5 },
    { value: 18, karat: 18, purity: 75.0 },
    { value: 14, karat: 14, purity: 58.3 },
    { value: 10, karat: 10, purity: 41.7 },
    { value: 9, karat: 9, purity: 37.5 },
  ];

  return options.map((option) => ({
    value: option.value,
    label: isRTL
      ? `${formatPersianNumber(option.karat)} عیار (${formatPersianNumber(option.purity)}%)`
      : `${option.karat}${t("inventory.karat")} (${option.purity}%)`,
  }));
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

// Lifecycle
onMounted(() => {
  initializeComponent();
});
</script>
