<template>
  <div class="chart-container">
    <div v-if="type === 'bar'" class="bar-chart">
      <div class="chart-title">{{ title }}</div>
      <div class="bars">
        <div v-for="(item, index) in data" :key="index" class="bar-item">
          <div
            class="bar"
            :style="{ height: `${(item.value / maxValue) * 100}%` }"
          ></div>
          <div class="bar-label">{{ item.label }}</div>
        </div>
      </div>
    </div>

    <div v-else-if="type === 'line'" class="line-chart">
      <div class="chart-title">{{ title }}</div>
      <div class="line-placeholder">
        <i class="fas fa-chart-line text-4xl text-gray-400"></i>
        <p class="text-sm text-gray-500 mt-2">Line Chart</p>
      </div>
    </div>

    <div v-else-if="type === 'pie'" class="pie-chart">
      <div class="chart-title">{{ title }}</div>
      <div class="pie-placeholder">
        <i class="fas fa-chart-pie text-4xl text-gray-400"></i>
        <p class="text-sm text-gray-500 mt-2">Pie Chart</p>
      </div>
    </div>

    <div v-else class="default-chart">
      <div class="chart-title">{{ title }}</div>
      <div class="chart-placeholder">
        <i class="fas fa-chart-bar text-4xl text-gray-400"></i>
        <p class="text-sm text-gray-500 mt-2">Chart</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

interface ChartData {
  label: string;
  value: number;
}

const props = defineProps<{
  type: "bar" | "line" | "pie" | "area";
  title?: string;
  data: ChartData[];
}>();

const maxValue = computed(() => {
  return Math.max(...props.data.map((item) => item.value));
});
</script>

<style scoped>
.chart-container {
  @apply w-full h-64 p-4 bg-white dark:bg-gray-800 rounded-lg;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white mb-4 text-center;
}

.bar-chart {
  @apply h-full;
}

.bars {
  @apply flex items-end justify-around h-48 border-b border-gray-200 dark:border-gray-700;
}

.bar-item {
  @apply flex flex-col items-center flex-1 mx-1;
}

.bar {
  @apply bg-blue-500 w-full min-h-2 rounded-t;
}

.bar-label {
  @apply text-xs text-gray-600 dark:text-gray-400 mt-2 text-center;
}

.line-chart,
.pie-chart,
.default-chart {
  @apply h-full flex flex-col;
}

.line-placeholder,
.pie-placeholder,
.chart-placeholder {
  @apply flex-1 flex flex-col items-center justify-center;
}
</style>
