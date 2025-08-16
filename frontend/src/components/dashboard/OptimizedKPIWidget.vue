<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow"
    :class="{ 'animate-pulse': isLoading }"
  >
    <!-- Loading Skeleton -->
    <div v-if="isLoading" class="animate-pulse">
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 mb-2"></div>
          <div class="flex items-baseline space-x-2">
            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-12"></div>
          </div>
        </div>
        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
      </div>
      <div v-if="showProgressBar" class="mt-4">
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2"></div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="hasError" class="text-center py-4">
      <ExclamationTriangleIcon class="w-8 h-8 text-red-500 mx-auto mb-2" />
      <p class="text-sm text-red-600 dark:text-red-400">{{ $t('dashboard.kpi_error') }}</p>
      <button
        @click="$emit('retry')"
        class="mt-2 text-xs text-blue-600 hover:text-blue-800 underline"
      >
        {{ $t('common.retry') }}
      </button>
    </div>

    <!-- Content -->
    <div v-else class="flex items-center justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
          {{ kpiLabel }}
        </p>
        <div class="flex items-baseline space-x-2 rtl:space-x-reverse">
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ memoizedFormattedValue }}
          </p>
          <div
            v-if="kpi.change !== undefined"
            class="flex items-center text-sm"
            :class="memoizedChangeColorClass"
          >
            <component
              :is="memoizedChangeIcon"
              class="w-4 h-4 mr-1 rtl:mr-0 rtl:ml-1"
              :class="memoizedChangeColorClass"
            />
            {{ Math.abs(kpi.change) }}%
          </div>
        </div>
      </div>

      <div class="flex-shrink-0">
        <div
          class="w-12 h-12 rounded-full flex items-center justify-center"
          :class="memoizedIconBackgroundClass"
        >
          <component :is="memoizedKpiIcon" class="w-6 h-6" :class="memoizedIconColorClass" />
        </div>
      </div>
    </div>

    <!-- Progress bar for percentage values -->
    <div v-if="!isLoading && !hasError && kpi.format === 'percentage' && showProgressBar" class="mt-4">
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
        <div
          class="h-2 rounded-full transition-all duration-300"
          :class="memoizedProgressBarClass"
          :style="{ width: `${Math.min(Number(kpi.value), 100)}%` }"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, toRefs } from "vue";
import { useI18n } from "vue-i18n";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { DashboardKPI } from "@/types/dashboard";

// Icons
import {
  ArrowTrendingUpIcon as TrendingUpIcon,
  ArrowTrendingDownIcon as TrendingDownIcon,
  MinusIcon,
  CurrencyDollarIcon,
  ScaleIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
} from "@heroicons/vue/24/outline";

interface Props {
  kpi: DashboardKPI;
  showProgressBar?: boolean;
  isLoading?: boolean;
  hasError?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showProgressBar: false,
  isLoading: false,
  hasError: false,
});

const emit = defineEmits<{
  retry: [];
}>();

const { t, locale } = useI18n();
const { formatNumber, formatCurrency, formatPercentage } = useNumberFormatter();

// Use toRefs for better reactivity performance
const { kpi } = toRefs(props);

// Memoized computed properties for better performance
const kpiLabel = computed(() => t(`dashboard.kpis.${kpi.value.key}`));

const memoizedFormattedValue = computed(() => {
  const value = kpi.value.value;

  switch (kpi.value.format) {
    case "currency":
      return formatCurrency(Number(value));
    case "percentage":
      return formatPercentage(Number(value));
    case "weight":
      return `${formatNumber(Number(value))} ${locale.value === "fa" ? "گرم" : "g"}`;
    case "number":
    default:
      return formatNumber(Number(value));
  }
});

const memoizedChangeColorClass = computed(() => {
  if (kpi.value.change === undefined) return "";

  switch (kpi.value.changeType) {
    case "increase":
      return "text-green-600 dark:text-green-400";
    case "decrease":
      return "text-red-600 dark:text-red-400";
    case "neutral":
    default:
      return "text-gray-600 dark:text-gray-400";
  }
});

const memoizedChangeIcon = computed(() => {
  if (kpi.value.change === undefined) return MinusIcon;

  switch (kpi.value.changeType) {
    case "increase":
      return TrendingUpIcon;
    case "decrease":
      return TrendingDownIcon;
    case "neutral":
    default:
      return MinusIcon;
  }
});

const memoizedKpiIcon = computed(() => {
  switch (kpi.value.key) {
    case "gold_sold":
      return ScaleIcon;
    case "total_profit":
    case "average_price":
      return CurrencyDollarIcon;
    case "returns":
      return ExclamationTriangleIcon;
    case "gross_margin":
    case "net_margin":
      return ChartBarIcon;
    default:
      return InformationCircleIcon;
  }
});

const memoizedIconBackgroundClass = computed(() => {
  const color = kpi.value.color || "blue";
  return `bg-${color}-100 dark:bg-${color}-900`;
});

const memoizedIconColorClass = computed(() => {
  const color = kpi.value.color || "blue";
  return `text-${color}-600 dark:text-${color}-400`;
});

const memoizedProgressBarClass = computed(() => {
  const color = kpi.value.color || "blue";
  return `bg-${color}-500 dark:bg-${color}-600`;
});
</script>