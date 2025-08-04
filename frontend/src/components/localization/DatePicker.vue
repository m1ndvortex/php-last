<template>
  <div class="relative">
    <label v-if="label" :class="labelClasses">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>

    <div class="relative">
      <input
        ref="inputRef"
        :value="displayValue"
        @input="handleInput"
        @focus="showCalendar = true"
        @blur="handleBlur"
        :placeholder="placeholder"
        :class="inputClasses"
        :readonly="readonly"
        type="text"
      />

      <button type="button" @click="toggleCalendar" :class="buttonClasses">
        <CalendarIcon class="h-5 w-5" />
      </button>
    </div>

    <!-- Calendar Dropdown -->
    <div v-if="showCalendar" :class="calendarClasses" @click.stop>
      <div class="p-4">
        <!-- Calendar Header -->
        <div class="flex items-center justify-between mb-4">
          <button
            type="button"
            @click="previousMonth"
            class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
          >
            <ChevronLeftIcon class="h-5 w-5" />
          </button>

          <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <select
              v-model="currentMonth"
              @change="updateCalendar"
              class="text-sm border-0 bg-transparent font-medium focus:ring-0"
            >
              <option
                v-for="(month, index) in monthNames"
                :key="index"
                :value="index"
              >
                {{ month }}
              </option>
            </select>

            <select
              v-model="currentYear"
              @change="updateCalendar"
              class="text-sm border-0 bg-transparent font-medium focus:ring-0"
            >
              <option v-for="year in yearRange" :key="year" :value="year">
                {{ formatNumber(year) }}
              </option>
            </select>
          </div>

          <button
            type="button"
            @click="nextMonth"
            class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
          >
            <ChevronRightIcon class="h-5 w-5" />
          </button>
        </div>

        <!-- Calendar Type Toggle -->
        <div class="flex justify-center mb-4">
          <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
            <button
              type="button"
              @click="currentCalendarType = 'gregorian'"
              :class="[
                'px-3 py-1 text-sm rounded-md transition-colors',
                currentCalendarType === 'gregorian'
                  ? 'bg-white dark:bg-gray-600 shadow-sm'
                  : 'hover:bg-gray-200 dark:hover:bg-gray-600',
              ]"
            >
              {{ $t("calendar.gregorian") }}
            </button>
            <button
              type="button"
              @click="currentCalendarType = 'jalali'"
              :class="[
                'px-3 py-1 text-sm rounded-md transition-colors',
                currentCalendarType === 'jalali'
                  ? 'bg-white dark:bg-gray-600 shadow-sm'
                  : 'hover:bg-gray-200 dark:hover:bg-gray-600',
              ]"
            >
              {{ $t("calendar.jalali") }}
            </button>
          </div>
        </div>

        <!-- Weekday Headers -->
        <div class="grid grid-cols-7 gap-1 mb-2">
          <div
            v-for="day in weekDays"
            :key="day"
            class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2"
          >
            {{ day }}
          </div>
        </div>

        <!-- Calendar Days -->
        <div class="grid grid-cols-7 gap-1">
          <button
            v-for="day in calendarDays"
            :key="`${day.year}-${day.month}-${day.day}`"
            type="button"
            @click="selectDate(day)"
            :class="getDayClasses(day)"
            :disabled="day.disabled"
          >
            {{ formatNumber(day.day) }}
          </button>
        </div>

        <!-- Today Button -->
        <div class="mt-4 flex justify-center">
          <button
            type="button"
            @click="selectToday"
            class="px-4 py-2 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700 transition-colors"
          >
            {{ $t("calendar.today") }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import {
  CalendarIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from "@heroicons/vue/24/outline";
import { useCalendarConversion } from "@/composables/useCalendarConversion";
import { useNumberFormatter } from "@/composables/useNumberFormatter";

interface Props {
  modelValue?: Date | string | null;
  label?: string;
  placeholder?: string;
  required?: boolean;
  readonly?: boolean;
  disabled?: boolean;
  minDate?: Date;
  maxDate?: Date;
  calendarType?: "gregorian" | "jalali" | "auto";
  format?: string;
  size?: "sm" | "md" | "lg";
}

interface CalendarDay {
  day: number;
  month: number;
  year: number;
  date: Date;
  isCurrentMonth: boolean;
  isToday: boolean;
  isSelected: boolean;
  disabled: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  placeholder: "",
  required: false,
  readonly: false,
  disabled: false,
  calendarType: "auto",
  format: "YYYY/MM/DD",
  size: "md",
});

const emit = defineEmits<{
  "update:modelValue": [value: Date | null];
  change: [value: Date | null];
}>();

const { locale, t } = useI18n();
const {
  convertToJalali,
  convertToGregorian,
  formatJalaliDate,
  formatGregorianDate,
} = useCalendarConversion();
const { formatNumber } = useNumberFormatter();

const inputRef = ref<HTMLInputElement>();
const showCalendar = ref(false);
const currentMonth = ref(new Date().getMonth());
const currentYear = ref(new Date().getFullYear());

// Determine active calendar type
const activeCalendarType = computed(() => {
  if (props.calendarType === "auto") {
    return locale.value === "fa" ? "jalali" : "gregorian";
  }
  return props.calendarType;
});

const currentCalendarType = ref(activeCalendarType.value);

// Watch for locale changes to update calendar type
watch(locale, () => {
  if (props.calendarType === "auto") {
    currentCalendarType.value = locale.value === "fa" ? "jalali" : "gregorian";
    updateCalendar();
  }
});

// Month and weekday names
const monthNames = computed(() => {
  if (currentCalendarType.value === "jalali") {
    return [
      t("calendar.months.jalali.farvardin"),
      t("calendar.months.jalali.ordibehesht"),
      t("calendar.months.jalali.khordad"),
      t("calendar.months.jalali.tir"),
      t("calendar.months.jalali.mordad"),
      t("calendar.months.jalali.shahrivar"),
      t("calendar.months.jalali.mehr"),
      t("calendar.months.jalali.aban"),
      t("calendar.months.jalali.azar"),
      t("calendar.months.jalali.dey"),
      t("calendar.months.jalali.bahman"),
      t("calendar.months.jalali.esfand"),
    ];
  } else {
    return [
      t("calendar.months.gregorian.january"),
      t("calendar.months.gregorian.february"),
      t("calendar.months.gregorian.march"),
      t("calendar.months.gregorian.april"),
      t("calendar.months.gregorian.may"),
      t("calendar.months.gregorian.june"),
      t("calendar.months.gregorian.july"),
      t("calendar.months.gregorian.august"),
      t("calendar.months.gregorian.september"),
      t("calendar.months.gregorian.october"),
      t("calendar.months.gregorian.november"),
      t("calendar.months.gregorian.december"),
    ];
  }
});

const weekDays = computed(() => {
  if (currentCalendarType.value === "jalali") {
    return [
      t("calendar.weekdays.jalali.saturday"),
      t("calendar.weekdays.jalali.sunday"),
      t("calendar.weekdays.jalali.monday"),
      t("calendar.weekdays.jalali.tuesday"),
      t("calendar.weekdays.jalali.wednesday"),
      t("calendar.weekdays.jalali.thursday"),
      t("calendar.weekdays.jalali.friday"),
    ];
  } else {
    return [
      t("calendar.weekdays.gregorian.sunday"),
      t("calendar.weekdays.gregorian.monday"),
      t("calendar.weekdays.gregorian.tuesday"),
      t("calendar.weekdays.gregorian.wednesday"),
      t("calendar.weekdays.gregorian.thursday"),
      t("calendar.weekdays.gregorian.friday"),
      t("calendar.weekdays.gregorian.saturday"),
    ];
  }
});

// Year range for dropdown
const yearRange = computed(() => {
  const currentYearValue = new Date().getFullYear();
  const start = currentYearValue - 50;
  const end = currentYearValue + 10;
  const years = [];

  for (let year = start; year <= end; year++) {
    if (currentCalendarType.value === "jalali") {
      const jalaliYear = convertToJalali(new Date(year, 0, 1)).year;
      years.push(jalaliYear);
    } else {
      years.push(year);
    }
  }

  return years.sort((a, b) => b - a);
});

// Current selected date
const selectedDate = computed(() => {
  if (!props.modelValue) return null;
  return props.modelValue instanceof Date
    ? props.modelValue
    : new Date(props.modelValue);
});

// Display value in input
const displayValue = computed(() => {
  if (!selectedDate.value) return "";

  if (currentCalendarType.value === "jalali") {
    return formatJalaliDate(selectedDate.value, props.format);
  } else {
    return formatGregorianDate(selectedDate.value, props.format);
  }
});

// Calendar days grid
const calendarDays = computed(() => {
  const days: CalendarDay[] = [];
  const today = new Date();

  if (currentCalendarType.value === "jalali") {
    // Generate Jalali calendar days
    const jalaliDate = convertToJalali(
      new Date(currentYear.value, currentMonth.value, 1),
    );
    const firstDayOfMonth = convertToGregorian(
      jalaliDate.year,
      jalaliDate.month,
      1,
    );
    const lastDayOfMonth = convertToGregorian(
      jalaliDate.year,
      jalaliDate.month,
      getDaysInJalaliMonth(jalaliDate.year, jalaliDate.month),
    );

    // Get first day of week (Saturday = 0 for Jalali)
    const firstWeekDay = (firstDayOfMonth.getDay() + 1) % 7;

    // Add previous month days
    for (let i = firstWeekDay - 1; i >= 0; i--) {
      const date = new Date(firstDayOfMonth);
      date.setDate(date.getDate() - i - 1);
      const jalali = convertToJalali(date);

      days.push({
        day: jalali.day,
        month: jalali.month,
        year: jalali.year,
        date,
        isCurrentMonth: false,
        isToday: isSameDay(date, today),
        isSelected: selectedDate.value
          ? isSameDay(date, selectedDate.value)
          : false,
        disabled: isDateDisabled(date),
      });
    }

    // Add current month days
    const daysInMonth = getDaysInJalaliMonth(jalaliDate.year, jalaliDate.month);
    for (let day = 1; day <= daysInMonth; day++) {
      const date = convertToGregorian(jalaliDate.year, jalaliDate.month, day);

      days.push({
        day,
        month: jalaliDate.month,
        year: jalaliDate.year,
        date,
        isCurrentMonth: true,
        isToday: isSameDay(date, today),
        isSelected: selectedDate.value
          ? isSameDay(date, selectedDate.value)
          : false,
        disabled: isDateDisabled(date),
      });
    }

    // Add next month days to fill grid
    const remainingDays = 42 - days.length;
    for (let day = 1; day <= remainingDays; day++) {
      const date = new Date(lastDayOfMonth);
      date.setDate(date.getDate() + day);
      const jalali = convertToJalali(date);

      days.push({
        day: jalali.day,
        month: jalali.month,
        year: jalali.year,
        date,
        isCurrentMonth: false,
        isToday: isSameDay(date, today),
        isSelected: selectedDate.value
          ? isSameDay(date, selectedDate.value)
          : false,
        disabled: isDateDisabled(date),
      });
    }
  } else {
    // Generate Gregorian calendar days
    const firstDayOfMonth = new Date(currentYear.value, currentMonth.value, 1);
    const lastDayOfMonth = new Date(
      currentYear.value,
      currentMonth.value + 1,
      0,
    );
    const firstWeekDay = firstDayOfMonth.getDay();

    // Add previous month days
    for (let i = firstWeekDay - 1; i >= 0; i--) {
      const date = new Date(firstDayOfMonth);
      date.setDate(date.getDate() - i - 1);

      days.push({
        day: date.getDate(),
        month: date.getMonth(),
        year: date.getFullYear(),
        date,
        isCurrentMonth: false,
        isToday: isSameDay(date, today),
        isSelected: selectedDate.value
          ? isSameDay(date, selectedDate.value)
          : false,
        disabled: isDateDisabled(date),
      });
    }

    // Add current month days
    const daysInMonth = lastDayOfMonth.getDate();
    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(currentYear.value, currentMonth.value, day);

      days.push({
        day,
        month: currentMonth.value,
        year: currentYear.value,
        date,
        isCurrentMonth: true,
        isToday: isSameDay(date, today),
        isSelected: selectedDate.value
          ? isSameDay(date, selectedDate.value)
          : false,
        disabled: isDateDisabled(date),
      });
    }

    // Add next month days to fill grid
    const remainingDays = 42 - days.length;
    for (let day = 1; day <= remainingDays; day++) {
      const date = new Date(currentYear.value, currentMonth.value + 1, day);

      days.push({
        day,
        month: currentMonth.value + 1,
        year: currentYear.value,
        date,
        isCurrentMonth: false,
        isToday: isSameDay(date, today),
        isSelected: selectedDate.value
          ? isSameDay(date, selectedDate.value)
          : false,
        disabled: isDateDisabled(date),
      });
    }
  }

  return days;
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
  "disabled:bg-gray-50 disabled:text-gray-500",
  {
    "text-sm py-2 px-3": props.size === "sm",
    "py-2.5 px-3": props.size === "md",
    "text-lg py-3 px-4": props.size === "lg",
  },
  {
    "pr-10": locale.value === "en",
    "pl-10": locale.value === "fa",
  },
]);

const buttonClasses = computed(() => [
  "absolute inset-y-0 flex items-center px-3",
  "text-gray-400 hover:text-gray-600 dark:hover:text-gray-300",
  {
    "right-0": locale.value === "en",
    "left-0": locale.value === "fa",
  },
]);

const calendarClasses = computed(() => [
  "absolute z-50 mt-1 bg-white dark:bg-gray-800",
  "border border-gray-200 dark:border-gray-700",
  "rounded-lg shadow-lg min-w-80",
  {
    "right-0": locale.value === "en",
    "left-0": locale.value === "fa",
  },
]);

// Helper functions
const isSameDay = (date1: Date, date2: Date): boolean => {
  return (
    date1.getFullYear() === date2.getFullYear() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getDate() === date2.getDate()
  );
};

const isDateDisabled = (date: Date): boolean => {
  if (props.disabled) return true;
  if (props.minDate && date < props.minDate) return true;
  if (props.maxDate && date > props.maxDate) return true;
  return false;
};

const getDaysInJalaliMonth = (year: number, month: number): number => {
  if (month <= 6) return 31;
  if (month <= 11) return 30;
  // Check for leap year
  return isJalaliLeapYear(year) ? 30 : 29;
};

const isJalaliLeapYear = (year: number): boolean => {
  const breaks = [
    -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097,
    2192, 2262, 2324, 2394, 2456, 3178,
  ];

  let jp = breaks[0];
  let jump = 0;
  for (let j = 1; j <= breaks.length; j++) {
    const jm = breaks[j];
    jump = jm - jp;
    if (year < jm) break;
    jp = jm;
  }

  let n = year - jp;
  if (n < jump) {
    n = n - Math.floor(n / 33) * 33;
    return n % 4 === 1;
  }

  return false;
};

const getDayClasses = (day: CalendarDay) => [
  "w-8 h-8 text-sm rounded-full transition-colors",
  "hover:bg-gray-100 dark:hover:bg-gray-700",
  "focus:outline-none focus:ring-2 focus:ring-primary-500",
  {
    "text-gray-900 dark:text-gray-100": day.isCurrentMonth,
    "text-gray-400 dark:text-gray-600": !day.isCurrentMonth,
    "bg-primary-600 text-white hover:bg-primary-700": day.isSelected,
    "bg-primary-100 text-primary-900 dark:bg-primary-900 dark:text-primary-100":
      day.isToday && !day.isSelected,
    "opacity-50 cursor-not-allowed": day.disabled,
    "cursor-pointer": !day.disabled,
  },
];

// Event handlers
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const value = target.value;

  // Try to parse the input value
  try {
    let date: Date | null = null;

    if (currentCalendarType.value === "jalali") {
      // Parse Jalali date format
      const parts = value.split("/");
      if (parts.length === 3) {
        const year = parseInt(parts[0]);
        const month = parseInt(parts[1]);
        const day = parseInt(parts[2]);
        date = convertToGregorian(year, month, day);
      }
    } else {
      // Parse Gregorian date
      date = new Date(value);
      if (isNaN(date.getTime())) {
        date = null;
      }
    }

    if (date && !isDateDisabled(date)) {
      emit("update:modelValue", date);
      emit("change", date);
    }
  } catch (error) {
    // Invalid date format, ignore
  }
};

const handleBlur = () => {
  setTimeout(() => {
    showCalendar.value = false;
  }, 200);
};

const toggleCalendar = () => {
  if (props.disabled || props.readonly) return;
  showCalendar.value = !showCalendar.value;
};

const selectDate = (day: CalendarDay) => {
  if (day.disabled) return;

  emit("update:modelValue", day.date);
  emit("change", day.date);
  showCalendar.value = false;
};

const selectToday = () => {
  const today = new Date();
  if (!isDateDisabled(today)) {
    emit("update:modelValue", today);
    emit("change", today);
    showCalendar.value = false;
  }
};

const previousMonth = () => {
  if (currentCalendarType.value === "jalali") {
    if (currentMonth.value === 1) {
      currentMonth.value = 12;
      currentYear.value--;
    } else {
      currentMonth.value--;
    }
  } else {
    if (currentMonth.value === 0) {
      currentMonth.value = 11;
      currentYear.value--;
    } else {
      currentMonth.value--;
    }
  }
};

const nextMonth = () => {
  if (currentCalendarType.value === "jalali") {
    if (currentMonth.value === 12) {
      currentMonth.value = 1;
      currentYear.value++;
    } else {
      currentMonth.value++;
    }
  } else {
    if (currentMonth.value === 11) {
      currentMonth.value = 0;
      currentYear.value++;
    } else {
      currentMonth.value++;
    }
  }
};

const updateCalendar = () => {
  // Update calendar view when month/year changes
  if (selectedDate.value) {
    if (currentCalendarType.value === "jalali") {
      const jalali = convertToJalali(selectedDate.value);
      currentMonth.value = jalali.month;
      currentYear.value = jalali.year;
    } else {
      currentMonth.value = selectedDate.value.getMonth();
      currentYear.value = selectedDate.value.getFullYear();
    }
  }
};

// Click outside handler
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement;
  if (!inputRef.value?.contains(target)) {
    showCalendar.value = false;
  }
};

onMounted(() => {
  document.addEventListener("click", handleClickOutside);

  // Initialize calendar with selected date
  if (selectedDate.value) {
    updateCalendar();
  }
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});

// Watch for calendar type changes
watch(currentCalendarType, () => {
  updateCalendar();
});
</script>
