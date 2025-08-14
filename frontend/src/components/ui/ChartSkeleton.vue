<template>
  <div class="chart-skeleton bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <!-- Header -->
    <div v-if="showHeader" class="flex items-center justify-between mb-6">
      <div class="space-y-2">
        <SkeletonLoader width="200px" height="24px" />
        <SkeletonLoader width="150px" height="16px" />
      </div>
      <div class="flex space-x-2">
        <SkeletonLoader width="80px" height="32px" />
        <SkeletonLoader width="100px" height="32px" />
      </div>
    </div>

    <!-- Controls -->
    <div v-if="showControls" class="flex items-center justify-between mb-6">
      <div class="flex space-x-4">
        <SkeletonLoader width="120px" height="36px" />
        <SkeletonLoader width="100px" height="36px" />
        <SkeletonLoader width="80px" height="36px" />
      </div>
      <div class="flex space-x-2">
        <SkeletonLoader width="32px" height="32px" />
        <SkeletonLoader width="32px" height="32px" />
      </div>
    </div>

    <!-- Chart Area -->
    <div class="chart-area mb-6" :style="{ height: chartHeight }">
      <div v-if="chartType === 'line'" class="relative h-full">
        <!-- Y-axis labels -->
        <div class="absolute left-0 top-0 h-full flex flex-col justify-between py-4">
          <SkeletonLoader
            v-for="i in 6"
            :key="`y-label-${i}`"
            width="40px"
            height="12px"
          />
        </div>
        
        <!-- Chart content -->
        <div class="ml-12 h-full relative">
          <!-- Grid lines -->
          <div class="absolute inset-0 flex flex-col justify-between">
            <div
              v-for="i in 6"
              :key="`grid-${i}`"
              class="h-px bg-gray-200 dark:bg-gray-700"
            />
          </div>
          
          <!-- Line chart simulation -->
          <div class="absolute inset-0 flex items-end justify-between px-4">
            <div
              v-for="i in 12"
              :key="`bar-${i}`"
              class="bg-gray-300 dark:bg-gray-600 w-2 rounded-t"
              :style="{ height: `${getRandomHeight()}%` }"
            />
          </div>
        </div>
        
        <!-- X-axis labels -->
        <div class="ml-12 mt-2 flex justify-between">
          <SkeletonLoader
            v-for="i in 6"
            :key="`x-label-${i}`"
            width="30px"
            height="12px"
          />
        </div>
      </div>

      <div v-else-if="chartType === 'bar'" class="relative h-full">
        <!-- Y-axis -->
        <div class="absolute left-0 top-0 h-full flex flex-col justify-between py-4">
          <SkeletonLoader
            v-for="i in 5"
            :key="`y-label-${i}`"
            width="40px"
            height="12px"
          />
        </div>
        
        <!-- Bars -->
        <div class="ml-12 h-full flex items-end justify-between px-4">
          <div
            v-for="i in 8"
            :key="`bar-${i}`"
            class="bg-gray-300 dark:bg-gray-600 rounded-t flex-1 mx-1"
            :style="{ height: `${getRandomHeight()}%` }"
          />
        </div>
        
        <!-- X-axis labels -->
        <div class="ml-12 mt-2 flex justify-between px-4">
          <SkeletonLoader
            v-for="i in 8"
            :key="`x-label-${i}`"
            width="40px"
            height="12px"
          />
        </div>
      </div>

      <div v-else-if="chartType === 'pie'" class="flex items-center justify-center h-full">
        <!-- Pie chart simulation -->
        <div class="relative">
          <div class="w-48 h-48 rounded-full bg-gray-300 dark:bg-gray-600 relative overflow-hidden">
            <!-- Pie segments -->
            <div class="absolute inset-0 bg-gray-400 dark:bg-gray-500" style="clip-path: polygon(50% 50%, 50% 0%, 100% 0%, 100% 50%);" />
            <div class="absolute inset-0 bg-gray-500 dark:bg-gray-400" style="clip-path: polygon(50% 50%, 100% 50%, 100% 100%, 50% 100%);" />
            <div class="absolute inset-0 bg-gray-600 dark:bg-gray-300" style="clip-path: polygon(50% 50%, 50% 100%, 0% 100%, 0% 0%, 50% 0%);" />
          </div>
          
          <!-- Legend -->
          <div class="absolute -right-32 top-0 space-y-3">
            <div
              v-for="i in 4"
              :key="`legend-${i}`"
              class="flex items-center space-x-2"
            >
              <div class="w-3 h-3 bg-gray-400 dark:bg-gray-500 rounded" />
              <SkeletonLoader width="80px" height="14px" />
            </div>
          </div>
        </div>
      </div>

      <div v-else class="flex items-center justify-center h-full">
        <SkeletonLoader width="100%" height="100%" variant="card" />
      </div>
    </div>

    <!-- Stats/Summary -->
    <div v-if="showStats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div
        v-for="i in 4"
        :key="`stat-${i}`"
        class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
      >
        <SkeletonLoader width="60px" height="24px" class="mx-auto mb-2" />
        <SkeletonLoader width="80px" height="14px" class="mx-auto" />
      </div>
    </div>

    <!-- Footer -->
    <div v-if="showFooter" class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
      <SkeletonLoader width="120px" height="14px" />
      <div class="flex space-x-2">
        <SkeletonLoader width="80px" height="32px" />
        <SkeletonLoader width="100px" height="32px" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SkeletonLoader from './SkeletonLoader.vue';

interface Props {
  chartType?: 'line' | 'bar' | 'pie' | 'area' | 'custom';
  chartHeight?: string;
  showHeader?: boolean;
  showControls?: boolean;
  showStats?: boolean;
  showFooter?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  chartType: 'line',
  chartHeight: '300px',
  showHeader: true,
  showControls: true,
  showStats: false,
  showFooter: false
});

const getRandomHeight = (): number => {
  return Math.floor(Math.random() * 80) + 20; // 20-100%
};
</script>

<style scoped>
.chart-skeleton {
  @apply animate-pulse;
}

.chart-area {
  @apply bg-gray-50 dark:bg-gray-900 rounded-lg p-4;
}
</style>