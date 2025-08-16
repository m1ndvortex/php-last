<template>
  <div class="dashboard-skeleton" :class="{ rtl: isRTL }">
    <!-- Dashboard Header Skeleton -->
    <div class="dashboard-header mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 rtl:space-x-reverse">
          <SkeletonLoader width="150px" height="16px" />
        </div>
        <div class="flex items-center space-x-4 rtl:space-x-reverse">
          <SkeletonLoader width="32px" height="32px" variant="circular" />
          <SkeletonLoader width="32px" height="32px" variant="circular" />
        </div>
      </div>
    </div>

    <!-- KPI Section Skeleton -->
    <div class="kpi-section mb-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
        <div
          v-for="i in 6"
          :key="`kpi-${i}`"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 animate-pulse"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <SkeletonLoader width="80px" height="16px" class="mb-2" />
              <div class="flex items-baseline space-x-2 rtl:space-x-reverse">
                <SkeletonLoader width="60px" height="24px" />
                <SkeletonLoader width="40px" height="16px" />
              </div>
            </div>
            <SkeletonLoader width="40px" height="40px" variant="circular" />
          </div>
        </div>
      </div>
    </div>

    <!-- Widgets Section Skeleton -->
    <div class="widgets-section mb-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sales Chart Skeleton -->
        <div class="lg:col-span-2">
          <ChartSkeleton
            chart-type="line"
            chart-height="300px"
            :show-header="true"
            :show-controls="true"
          />
        </div>

        <!-- Alerts Widget Skeleton -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-4">
            <SkeletonLoader width="120px" height="20px" />
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
              <SkeletonLoader width="20px" height="20px" variant="circular" />
              <SkeletonLoader width="80px" height="16px" />
            </div>
          </div>
          
          <div class="space-y-3">
            <div
              v-for="i in 3"
              :key="`alert-${i}`"
              class="flex items-start space-x-3 rtl:space-x-reverse p-3 rounded-lg border border-gray-200 dark:border-gray-700"
            >
              <SkeletonLoader width="20px" height="20px" variant="circular" class="mt-0.5" />
              <div class="flex-1 space-y-2">
                <SkeletonLoader width="90%" height="16px" />
                <SkeletonLoader width="100%" height="14px" />
                <SkeletonLoader width="60%" height="12px" />
              </div>
              <div class="flex space-x-1 rtl:space-x-reverse">
                <SkeletonLoader width="24px" height="24px" />
                <SkeletonLoader width="24px" height="24px" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Activities Section Skeleton -->
    <div class="activities-section mb-8">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <SkeletonLoader width="150px" height="20px" class="mb-4" />
        
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead>
              <tr class="border-b border-gray-200 dark:border-gray-700">
                <th class="px-6 py-3 text-left rtl:text-right">
                  <SkeletonLoader width="80px" height="16px" />
                </th>
                <th class="px-6 py-3 text-left rtl:text-right">
                  <SkeletonLoader width="60px" height="16px" />
                </th>
                <th class="px-6 py-3 text-left rtl:text-right">
                  <SkeletonLoader width="70px" height="16px" />
                </th>
                <th class="px-6 py-3 text-left rtl:text-right">
                  <SkeletonLoader width="50px" height="16px" />
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="i in 5"
                :key="`activity-${i}`"
                class="border-b border-gray-200 dark:border-gray-700"
              >
                <td class="px-6 py-4">
                  <SkeletonLoader width="200px" height="16px" />
                </td>
                <td class="px-6 py-4">
                  <SkeletonLoader width="80px" height="16px" />
                </td>
                <td class="px-6 py-4">
                  <SkeletonLoader width="100px" height="16px" />
                </td>
                <td class="px-6 py-4">
                  <SkeletonLoader width="60px" height="20px" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Quick Actions Section Skeleton -->
    <div class="quick-actions-section">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <SkeletonLoader width="120px" height="20px" class="mb-4" />
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <div
            v-for="i in 6"
            :key="`action-${i}`"
            class="flex flex-col items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg min-h-[100px] justify-center"
          >
            <SkeletonLoader width="32px" height="32px" variant="circular" class="mb-2" />
            <SkeletonLoader width="80px" height="16px" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue';
import ChartSkeleton from '@/components/ui/ChartSkeleton.vue';

const { locale } = useI18n();

const isRTL = computed(() => locale.value === 'fa');
</script>

<style scoped>
.dashboard-skeleton {
  padding: 1rem;
}

/* RTL improvements */
.rtl .grid {
  direction: rtl;
}

.rtl .space-x-4 > * + * {
  margin-left: 0;
  margin-right: 1rem;
}

.rtl .space-x-3 > * + * {
  margin-left: 0;
  margin-right: 0.75rem;
}

.rtl .space-x-2 > * + * {
  margin-left: 0;
  margin-right: 0.5rem;
}

.rtl .space-x-1 > * + * {
  margin-left: 0;
  margin-right: 0.25rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .dashboard-skeleton {
    padding: 0.5rem;
  }

  .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-6 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .grid.grid-cols-2.md\\:grid-cols-3.lg\\:grid-cols-6 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3.xl\\:grid-cols-6 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }

  .grid.grid-cols-2.md\\:grid-cols-3.lg\\:grid-cols-6 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }

  .grid.grid-cols-1.lg\\:grid-cols-3 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>