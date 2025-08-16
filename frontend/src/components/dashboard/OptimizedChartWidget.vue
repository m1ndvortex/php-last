<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
        {{ title }}
      </h3>
      <div class="flex items-center space-x-2 rtl:space-x-reverse">
        <select
          v-if="showPeriodSelector && !isLoading"
          v-model="selectedPeriod"
          class="text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-md focus:ring-primary-500 focus:border-primary-500"
          @change="onPeriodChange"
        >
          <option value="daily">{{ $t("dashboard.periods.daily") }}</option>
          <option value="weekly">{{ $t("dashboard.periods.weekly") }}</option>
          <option value="monthly">{{ $t("dashboard.periods.monthly") }}</option>
          <option value="yearly">{{ $t("dashboard.periods.yearly") }}</option>
        </select>

        <button
          @click="refreshChart"
          class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          :disabled="isLoading"
        >
          <ArrowPathIcon
            class="w-4 h-4"
            :class="{ 'animate-spin': isLoading }"
          />
        </button>
      </div>
    </div>

    <div class="relative">
      <!-- Loading Skeleton -->
      <ChartSkeleton
        v-if="isLoading"
        :chart-type="chartType"
        :chart-height="height + 'px'"
        :show-header="false"
        :show-controls="false"
      />

      <!-- Error State -->
      <div v-else-if="hasError" class="flex flex-col items-center justify-center py-12">
        <ExclamationTriangleIcon class="w-12 h-12 text-red-500 mb-4" />
        <p class="text-sm text-red-600 dark:text-red-400 mb-4">{{ $t('dashboard.chart_error') }}</p>
        <button
          @click="handleRetry"
          class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          {{ $t('common.retry') }}
        </button>
      </div>

      <!-- Chart Content -->
      <div v-else :style="{ height: height + 'px' }" ref="chartContainer">
        <canvas ref="chartCanvas"></canvas>
      </div>
    </div>

    <!-- Chart Legend -->
    <div v-if="showLegend && chartData && !isLoading && !hasError" class="mt-4 flex flex-wrap gap-4">
      <div
        v-for="(dataset, index) in chartData.datasets"
        :key="index"
        class="flex items-center space-x-2 rtl:space-x-reverse"
      >
        <div
          class="w-3 h-3 rounded-full"
          :style="{
            backgroundColor: getDatasetColor(dataset)
          }"
        ></div>
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ dataset.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick, computed, shallowRef } from "vue";
import { useI18n } from "vue-i18n";
import { ArrowPathIcon, ExclamationTriangleIcon } from "@heroicons/vue/24/outline";
import ChartSkeleton from "@/components/ui/ChartSkeleton.vue";
import type { ChartData } from "@/types/dashboard";

// Chart.js imports
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
} from "chart.js";

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
);

interface Props {
  title: string;
  chartType: "line" | "bar" | "doughnut" | "pie";
  chartData?: ChartData;
  showPeriodSelector?: boolean;
  showLegend?: boolean;
  height?: number;
  isLoading?: boolean;
  hasError?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showPeriodSelector: false,
  showLegend: true,
  height: 256,
  isLoading: false,
  hasError: false,
});

const emit = defineEmits<{
  periodChange: [period: string];
  refresh: [];
  retry: [];
}>();

const { locale } = useI18n();

const chartContainer = ref<HTMLDivElement>();
const chartCanvas = ref<HTMLCanvasElement>();
const selectedPeriod = ref("monthly");

// Use shallowRef for better performance with Chart.js instance
const chartInstance = shallowRef<ChartJS | null>(null);

// Memoized chart options for better performance
const memoizedChartOptions = computed(() => {
  const isRTL = locale.value === "fa";
  
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: props.showLegend,
        position: "top" as const,
        rtl: isRTL,
        labels: {
          usePointStyle: true,
          padding: 20,
          font: {
            family: isRTL ? "Vazirmatn" : "Inter",
          },
        },
      },
      tooltip: {
        rtl: isRTL,
        titleFont: {
          family: isRTL ? "Vazirmatn" : "Inter",
        },
        bodyFont: {
          family: isRTL ? "Vazirmatn" : "Inter",
        },
      },
    },
    scales:
      props.chartType !== "doughnut" && props.chartType !== "pie"
        ? {
            x: {
              ticks: {
                font: {
                  family: isRTL ? "Vazirmatn" : "Inter",
                },
              },
            },
            y: {
              ticks: {
                font: {
                  family: isRTL ? "Vazirmatn" : "Inter",
                },
              },
            },
          }
        : undefined,
    animation: {
      duration: 750,
      easing: "easeInOutQuart" as const,
    },
    interaction: {
      intersect: false,
      mode: "index" as const,
    },
  };
});

const refreshChart = () => {
  emit("refresh");
};

const onPeriodChange = () => {
  emit("periodChange", selectedPeriod.value);
};

const handleRetry = () => {
  emit("retry");
};

const getDatasetColor = (dataset: any): string => {
  return (Array.isArray(dataset.backgroundColor)
    ? dataset.backgroundColor[0]
    : dataset.backgroundColor) ||
  (Array.isArray(dataset.borderColor)
    ? dataset.borderColor[0]
    : dataset.borderColor) ||
  '#3b82f6';
};

const initChart = async () => {
  if (!chartCanvas.value || !props.chartData || props.isLoading || props.hasError) return;

  await nextTick();

  const ctx = chartCanvas.value.getContext("2d");
  if (!ctx) return;

  // Destroy existing chart
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }

  try {
    chartInstance.value = new ChartJS(ctx, {
      type: props.chartType,
      data: props.chartData,
      options: memoizedChartOptions.value,
    });
  } catch (error) {
    console.error('Failed to initialize chart:', error);
    emit('retry');
  }
};

const updateChart = () => {
  if (chartInstance.value && props.chartData && !props.isLoading && !props.hasError) {
    try {
      chartInstance.value.data = props.chartData;
      chartInstance.value.update("active");
    } catch (error) {
      console.error('Failed to update chart:', error);
      // Reinitialize chart on update error
      initChart();
    }
  }
};

// Watch for data changes with debouncing for better performance
let updateTimeout: NodeJS.Timeout;
watch(
  () => props.chartData,
  () => {
    if (updateTimeout) clearTimeout(updateTimeout);
    updateTimeout = setTimeout(() => {
      if (chartInstance.value) {
        updateChart();
      } else {
        initChart();
      }
    }, 100);
  },
  { deep: true }
);

// Watch for loading state changes
watch(
  () => [props.isLoading, props.hasError],
  ([isLoading, hasError]) => {
    if (!isLoading && !hasError && props.chartData) {
      // Delay chart initialization to ensure DOM is ready
      nextTick(() => {
        setTimeout(() => initChart(), 100);
      });
    }
  }
);

// Watch for locale changes
watch(
  () => locale.value,
  () => {
    if (!props.isLoading && !props.hasError) {
      initChart();
    }
  }
);

onMounted(() => {
  if (!props.isLoading && !props.hasError && props.chartData) {
    initChart();
  }
});

onUnmounted(() => {
  if (updateTimeout) clearTimeout(updateTimeout);
  if (chartInstance.value) {
    chartInstance.value.destroy();
    chartInstance.value = null;
  }
});
</script>