// Localization Components
export { default as RTLProvider } from "./RTLProvider.vue";
export { default as DatePicker } from "./DatePicker.vue";
export { default as NumberInput } from "./NumberInput.vue";

// Composables
export { useCalendarConversion } from "@/composables/useCalendarConversion";
export { useNumberFormatter } from "@/composables/useNumberFormatter";
export { useLocaleValidation } from "@/composables/useLocaleValidation";

// Types
export interface LocaleInfo {
  code: string;
  name: string;
  flag: string;
  dir: "ltr" | "rtl";
}

export interface CalendarDate {
  year: number;
  month: number;
  day: number;
}

export interface NumberFormatOptions {
  minimumFractionDigits?: number;
  maximumFractionDigits?: number;
  useGrouping?: boolean;
  style?: "decimal" | "currency" | "percent";
  currency?: string;
}
