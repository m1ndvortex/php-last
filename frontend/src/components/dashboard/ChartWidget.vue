<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ title }}
      </h3>
      <div class="flex items-center space-x-2">
        <select 
          v-if="showPeriodSelector"
          v-model="selectedPeriod"
          class="text-sm border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500"
          @change="onPeriodChange"
        >
          <option value="daily">{{ $t('dashboard.periods.daily') }}</option>
          <option value="weekly">{{ $t('dashboard.periods.weekly') }}</option>
          <option value="monthly">{{ $t('dashboard.periods.monthly') }}</option>
          <option value="yearly">{{ $t('dashboard.periods.yearly') }}</option>
        </select>
        
        <button
          @click="refreshChart"
          class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
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
      <div v-if="isLoading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      </div>
      
      <div class="h-64" ref="chartContainer">
        <!-- Chart will be rendered here -->
        <canvas ref="chartCanvas"></canvas>
      </div>
    </div>
    
    <!-- Chart Legend -->
    <div v-if="showLegend && chartData" class="mt-4 flex flex-wrap gap-4">
      <div 
        v-for="(dataset, index) in chartData.datasets" 
        :key="index"
        class="flex items-center space-x-2"
      >
        <div 
          class="w-3 h-3 rounded-full"
          :style="{ backgroundColor: (Array.isArray(dataset.backgroundColor) ? dataset.backgroundColor[0] : dataset.backgroundColor) || (Array.isArray(dataset.borderColor) ? dataset.borderColor[0] : dataset.borderColor) || '#3b82f6' }"
        ></div>
        <span class="text-sm text-gray-600">{{ dataset.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';
import type { ChartData } from '@/types/dashboard';

// Chart.js imports (you'll need to install chart.js)
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
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
);

interface Props {
  title: string;
  chartType: 'line' | 'bar' | 'doughnut' | 'pie';
  chartData?: ChartData;
  showPeriodSelector?: boolean;
  showLegend?: boolean;
  height?: number;
}

const props = withDefaults(defineProps<Props>(), {
  showPeriodSelector: false,
  showLegend: true,
  height: 256
});

const emit = defineEmits<{
  periodChange: [period: string];
  refresh: [];
}>();

const { locale } = useI18n();

const chartContainer = ref<HTMLDivElement>();
const chartCanvas = ref<HTMLCanvasElement>();
const selectedPeriod = ref('monthly');
const isLoading = ref(false);

let chartInstance: ChartJS | null = null;

const refreshChart = () => {
  isLoading.value = true;
  emit('refresh');
  
  // Simulate loading delay
  setTimeout(() => {
    isLoading.value = false;
  }, 1000);
};

const onPeriodChange = () => {
  emit('periodChange', selectedPeriod.value);
};

const initChart = async () => {
  if (!chartCanvas.value || !props.chartData) return;
  
  await nextTick();
  
  const ctx = chartCanvas.value.getContext('2d');
  if (!ctx) return;
  
  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy();
  }
  
  const isRTL = locale.value === 'fa';
  
  chartInstance = new ChartJS(ctx, {
    type: props.chartType,
    data: props.chartData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: props.showLegend,
          position: 'top',
          rtl: isRTL,
          labels: {
            usePointStyle: true,
            padding: 20,
            font: {
              family: isRTL ? 'Vazirmatn' : 'Inter',
            }
          }
        },
        tooltip: {
          rtl: isRTL,
          titleFont: {
            family: isRTL ? 'Vazirmatn' : 'Inter',
          },
          bodyFont: {
            family: isRTL ? 'Vazirmatn' : 'Inter',
          }
        }
      },
      scales: props.chartType !== 'doughnut' && props.chartType !== 'pie' ? {
        x: {
          ticks: {
            font: {
              family: isRTL ? 'Vazirmatn' : 'Inter',
            }
          }
        },
        y: {
          ticks: {
            font: {
              family: isRTL ? 'Vazirmatn' : 'Inter',
            }
          }
        }
      } : undefined,
      animation: {
        duration: 750,
        easing: 'easeInOutQuart'
      }
    }
  });
};

const updateChart = () => {
  if (chartInstance && props.chartData) {
    chartInstance.data = props.chartData;
    chartInstance.update('active');
  }
};

// Watch for data changes
watch(() => props.chartData, () => {
  if (chartInstance) {
    updateChart();
  } else {
    initChart();
  }
}, { deep: true });

// Watch for locale changes
watch(() => locale.value, () => {
  initChart();
});

onMounted(() => {
  initChart();
});

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy();
  }
});
</script>