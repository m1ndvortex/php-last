<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-gray-600 mb-1">
          {{ $t(`dashboard.kpis.${kpi.key}`) }}
        </p>
        <div class="flex items-baseline space-x-2">
          <p class="text-2xl font-bold text-gray-900">
            {{ formattedValue }}
          </p>
          <div 
            v-if="kpi.change !== undefined" 
            class="flex items-center text-sm"
            :class="changeColorClass"
          >
            <component 
              :is="changeIcon" 
              class="w-4 h-4 mr-1" 
              :class="changeColorClass"
            />
            {{ Math.abs(kpi.change) }}%
          </div>
        </div>
      </div>
      
      <div class="flex-shrink-0">
        <div 
          class="w-12 h-12 rounded-full flex items-center justify-center"
          :class="iconBackgroundClass"
        >
          <component 
            :is="kpiIcon" 
            class="w-6 h-6"
            :class="iconColorClass"
          />
        </div>
      </div>
    </div>
    
    <!-- Progress bar for percentage values -->
    <div v-if="kpi.format === 'percentage' && showProgressBar" class="mt-4">
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div 
          class="h-2 rounded-full transition-all duration-300"
          :class="progressBarClass"
          :style="{ width: `${Math.min(Number(kpi.value), 100)}%` }"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useNumberFormatter } from '@/composables/useNumberFormatter';
import type { DashboardKPI } from '@/types/dashboard';

// Icons (you can replace these with your preferred icon library)
import { 
  ArrowTrendingUpIcon as TrendingUpIcon, 
  ArrowTrendingDownIcon as TrendingDownIcon, 
  MinusIcon,
  CurrencyDollarIcon,
  ScaleIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline';

interface Props {
  kpi: DashboardKPI;
  showProgressBar?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showProgressBar: false
});

const { locale } = useI18n();
const { formatNumber, formatCurrency, formatPercentage } = useNumberFormatter();

const formattedValue = computed(() => {
  const value = props.kpi.value;
  
  switch (props.kpi.format) {
    case 'currency':
      return formatCurrency(Number(value));
    case 'percentage':
      return formatPercentage(Number(value));
    case 'weight':
      return `${formatNumber(Number(value))} ${locale.value === 'fa' ? 'گرم' : 'g'}`;
    case 'number':
    default:
      return formatNumber(Number(value));
  }
});

const changeColorClass = computed(() => {
  if (props.kpi.change === undefined) return '';
  
  switch (props.kpi.changeType) {
    case 'increase':
      return 'text-green-600';
    case 'decrease':
      return 'text-red-600';
    case 'neutral':
    default:
      return 'text-gray-600';
  }
});

const changeIcon = computed(() => {
  if (props.kpi.change === undefined) return MinusIcon;
  
  switch (props.kpi.changeType) {
    case 'increase':
      return TrendingUpIcon;
    case 'decrease':
      return TrendingDownIcon;
    case 'neutral':
    default:
      return MinusIcon;
  }
});

const kpiIcon = computed(() => {
  switch (props.kpi.key) {
    case 'gold_sold':
      return ScaleIcon;
    case 'total_profit':
    case 'average_price':
      return CurrencyDollarIcon;
    case 'returns':
      return ExclamationTriangleIcon;
    case 'gross_margin':
    case 'net_margin':
      return ChartBarIcon;
    default:
      return InformationCircleIcon;
  }
});

const iconBackgroundClass = computed(() => {
  const color = props.kpi.color || 'blue';
  return `bg-${color}-100`;
});

const iconColorClass = computed(() => {
  const color = props.kpi.color || 'blue';
  return `text-${color}-600`;
});

const progressBarClass = computed(() => {
  const color = props.kpi.color || 'blue';
  return `bg-${color}-500`;
});
</script>