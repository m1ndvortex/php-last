<template>
  <div class="relative">
    <label v-if="label" :class="labelClasses">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1 rtl:mr-1">*</span>
    </label>

    <div class="relative">
      <input
        ref="inputRef"
        :value="displayValue"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        :placeholder="placeholder"
        :class="inputClasses"
        :disabled="disabled"
        :readonly="readonly"
        type="text"
        inputmode="numeric"
      />

      <!-- Currency/Unit suffix -->
      <div v-if="suffix" :class="suffixClasses">
        {{ suffix }}
      </div>

      <!-- Increment/Decrement buttons -->
      <div
        v-if="showControls && !disabled && !readonly"
        :class="controlsClasses"
      >
        <button
          type="button"
          @click="increment"
          :class="controlButtonClasses"
          :disabled="isMaxReached"
        >
          <ChevronUpIcon class="h-3 w-3" />
        </button>
        <button
          type="button"
          @click="decrement"
          :class="controlButtonClasses"
          :disabled="isMinReached"
        >
          <ChevronDownIcon class="h-3 w-3" />
        </button>
      </div>
    </div>

    <!-- Error message -->
    <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
      {{ error }}
    </p>

    <!-- Help text -->
    <p
      v-if="help && !error"
      class="mt-1 text-sm text-gray-500 dark:text-gray-400"
    >
      {{ help }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { ChevronUpIcon, ChevronDownIcon } from "@heroicons/vue/24/outline";
import { useNumberFormatter } from "@/composables/useNumberFormatter";

interface Props {
  modelValue?: number | null;
  label?: string;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  readonly?: boolean;
  min?: number;
  max?: number;
  step?: number;
  precision?: number;
  suffix?: string;
  showControls?: boolean;
  size?: "sm" | "md" | "lg";
  error?: string;
  help?: string;
  format?: "decimal" | "currency" | "percentage";
  currency?: string;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  placeholder: "",
  required: false,
  disabled: false,
  readonly: false,
  step: 1,
  precision: 2,
  showControls: false,
  size: "md",
  format: "decimal",
  currency: "IRR",
});

const emit = defineEmits<{
  "update:modelValue": [value: number | null];
  change: [value: number | null];
  focus: [event: FocusEvent];
  blur: [event: FocusEvent];
}>();

const { locale } = useI18n();
const {
  formatNumber,
  formatCurrency,
  formatPercentage,
  parseNumber,
  formatForInput,
} = useNumberFormatter();

const inputRef = ref<HTMLInputElement>();
const isFocused = ref(false);
const internalValue = ref<string>("");

// Computed values
const numericValue = computed(() => props.modelValue);

const isMinReached = computed(() => {
  return (
    props.min !== undefined &&
    numericValue.value !== null &&
    numericValue.value <= props.min
  );
});

const isMaxReached = computed(() => {
  return (
    props.max !== undefined &&
    numericValue.value !== null &&
    numericValue.value >= props.max
  );
});

// Display value based on focus state and format
const displayValue = computed(() => {
  if (isFocused.value) {
    // When focused, show raw editable value
    return internalValue.value;
  }

  if (numericValue.value === null || numericValue.value === undefined) {
    return "";
  }

  // When not focused, show formatted value
  switch (props.format) {
    case "currency":
      return formatCurrency(numericValue.value, props.currency, {
        minimumFractionDigits: props.precision,
        maximumFractionDigits: props.precision,
      });
    case "percentage":
      return formatPercentage(numericValue.value, {
        minimumFractionDigits: props.precision,
        maximumFractionDigits: props.precision,
      });
    default:
      return formatNumber(numericValue.value, {
        minimumFractionDigits: props.precision,
        maximumFractionDigits: props.precision,
      });
  }
});

// Computed classes
const labelClasses = computed(() => [
  "block text-sm font-medium mb-2",
  "text-gray-700 dark:text-gray-300",
]);

const inputClasses = computed(() => [
  "block w-full rounded-md border-gray-300 dark:border-gray-600",
  "bg-white dark:bg-gray-700",
  "text-gray-900 dark:text-gray-100",
  "shadow-sm focus:border-primary-500 focus:ring-primary-500",
  "disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed",
  "readonly:bg-gray-50 readonly:text-gray-500",
  {
    "text-sm py-2 px-3": props.size === "sm",
    "py-2.5 px-3": props.size === "md",
    "text-lg py-3 px-4": props.size === "lg",
  },
  {
    "pr-16": (props.suffix || props.showControls) && locale.value === "en",
    "pl-16": (props.suffix || props.showControls) && locale.value === "fa",
    "pr-8": props.suffix && !props.showControls && locale.value === "en",
    "pl-8": props.suffix && !props.showControls && locale.value === "fa",
  },
  {
    "border-red-300 focus:border-red-500 focus:ring-red-500": props.error,
  },
]);

const suffixClasses = computed(() => [
  "absolute inset-y-0 flex items-center px-3 pointer-events-none",
  "text-gray-500 dark:text-gray-400 text-sm",
  {
    "right-0": locale.value === "en",
    "left-0": locale.value === "fa",
  },
  {
    "right-8": props.showControls && locale.value === "en",
    "left-8": props.showControls && locale.value === "fa",
  },
]);

const controlsClasses = computed(() => [
  "absolute inset-y-0 flex flex-col",
  {
    "right-0": locale.value === "en",
    "left-0": locale.value === "fa",
  },
]);

const controlButtonClasses = computed(() => [
  "flex-1 px-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300",
  "disabled:opacity-50 disabled:cursor-not-allowed",
  "focus:outline-none focus:text-primary-600",
  "border-l border-gray-300 dark:border-gray-600",
  {
    "border-r-0": locale.value === "en",
    "border-l-0 border-r border-gray-300 dark:border-gray-600":
      locale.value === "fa",
  },
]);

// Event handlers
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  let value = target.value;

  // Store the raw input value for editing
  internalValue.value = value;

  // Parse and validate the number
  const parsed = parseNumber(value);

  if (parsed !== null) {
    // Apply min/max constraints
    let constrainedValue = parsed;

    if (props.min !== undefined && constrainedValue < props.min) {
      constrainedValue = props.min;
    }

    if (props.max !== undefined && constrainedValue > props.max) {
      constrainedValue = props.max;
    }

    emit("update:modelValue", constrainedValue);
    emit("change", constrainedValue);
  } else if (value === "") {
    emit("update:modelValue", null);
    emit("change", null);
  }
};

const handleFocus = (event: FocusEvent) => {
  isFocused.value = true;

  // Set internal value to editable format
  if (numericValue.value !== null && numericValue.value !== undefined) {
    internalValue.value = formatForInput(numericValue.value);
  } else {
    internalValue.value = "";
  }

  nextTick(() => {
    inputRef.value?.select();
  });

  emit("focus", event);
};

const handleBlur = (event: FocusEvent) => {
  isFocused.value = false;

  // Validate and format the final value
  const parsed = parseNumber(internalValue.value);

  if (parsed !== null) {
    let constrainedValue = parsed;

    if (props.min !== undefined && constrainedValue < props.min) {
      constrainedValue = props.min;
    }

    if (props.max !== undefined && constrainedValue > props.max) {
      constrainedValue = props.max;
    }

    if (constrainedValue !== numericValue.value) {
      emit("update:modelValue", constrainedValue);
      emit("change", constrainedValue);
    }
  } else if (internalValue.value === "") {
    emit("update:modelValue", null);
    emit("change", null);
  }

  emit("blur", event);
};

const increment = () => {
  if (props.disabled || props.readonly) return;

  const currentValue = numericValue.value || 0;
  const newValue = currentValue + props.step;

  if (props.max === undefined || newValue <= props.max) {
    emit("update:modelValue", newValue);
    emit("change", newValue);
  }
};

const decrement = () => {
  if (props.disabled || props.readonly) return;

  const currentValue = numericValue.value || 0;
  const newValue = currentValue - props.step;

  if (props.min === undefined || newValue >= props.min) {
    emit("update:modelValue", newValue);
    emit("change", newValue);
  }
};

// Watch for external value changes
watch(
  () => props.modelValue,
  (newValue) => {
    if (isFocused.value) {
      // Don't update internal value while user is editing
      return;
    }

    if (newValue !== null && newValue !== undefined) {
      internalValue.value = formatForInput(newValue);
    } else {
      internalValue.value = "";
    }
  },
  { immediate: true },
);

// Expose methods for parent components
defineExpose({
  focus: () => inputRef.value?.focus(),
  blur: () => inputRef.value?.blur(),
  select: () => inputRef.value?.select(),
});
</script>
