<template>
  <div class="route-skeleton">
    <!-- Header Skeleton -->
    <div v-if="showHeader" class="header-skeleton mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <SkeletonLoader variant="circular" class="w-8 h-8" />
          <SkeletonLoader width="200px" height="24px" />
        </div>
        <div class="flex space-x-2">
          <SkeletonLoader width="80px" height="32px" />
          <SkeletonLoader width="100px" height="32px" />
        </div>
      </div>
    </div>

    <!-- Navigation Skeleton -->
    <div v-if="showNavigation" class="navigation-skeleton mb-6">
      <div class="flex space-x-6">
        <SkeletonLoader
          v-for="i in navigationItems"
          :key="`nav-${i}`"
          width="80px"
          height="20px"
        />
      </div>
    </div>

    <!-- Main Content Skeleton -->
    <div class="main-content-skeleton">
      <component
        :is="contentSkeletonComponent"
        v-bind="contentSkeletonProps"
      />
    </div>

    <!-- Loading Progress -->
    <div v-if="showProgress" class="loading-progress mt-6">
      <div class="flex items-center justify-between mb-2">
        <SkeletonLoader width="120px" height="16px" />
        <SkeletonLoader width="40px" height="16px" />
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="bg-blue-600 h-2 rounded-full transition-all duration-300"
          :style="{ width: `${progress}%` }"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SkeletonLoader from './SkeletonLoader.vue';
import CardSkeleton from './CardSkeleton.vue';
import TableSkeleton from './TableSkeleton.vue';
import ListSkeleton from './ListSkeleton.vue';
import ChartSkeleton from './ChartSkeleton.vue';

interface Props {
  routeType?: 'dashboard' | 'list' | 'form' | 'report' | 'settings';
  showHeader?: boolean;
  showNavigation?: boolean;
  showProgress?: boolean;
  navigationItems?: number;
  progress?: number;
  customSkeleton?: string;
}

const props = withDefaults(defineProps<Props>(), {
  routeType: 'dashboard',
  showHeader: true,
  showNavigation: true,
  showProgress: false,
  navigationItems: 5,
  progress: 0
});

const contentSkeletonComponent = computed(() => {
  if (props.customSkeleton) {
    return props.customSkeleton;
  }

  switch (props.routeType) {
    case 'dashboard':
      return CardSkeleton;
    case 'list':
      return TableSkeleton;
    case 'form':
      return CardSkeleton;
    case 'report':
      return ChartSkeleton;
    case 'settings':
      return ListSkeleton;
    default:
      return CardSkeleton;
  }
});

const contentSkeletonProps = computed(() => {
  switch (props.routeType) {
    case 'dashboard':
      return {
        variant: 'stats',
        statsCount: 4,
        showHeader: true,
        showFooter: false
      };
    case 'list':
      return {
        rows: 8,
        columns: 6
      };
    case 'form':
      return {
        variant: 'default',
        lines: 6,
        showHeader: true,
        showFooter: true
      };
    case 'report':
      return {
        showHeader: true,
        showControls: true
      };
    case 'settings':
      return {
        items: 8,
        showIcons: true
      };
    default:
      return {};
  }
});
</script>

<style scoped>
.route-skeleton {
  @apply animate-pulse;
}

.header-skeleton {
  @apply border-b border-gray-200 dark:border-gray-700 pb-4;
}

.navigation-skeleton {
  @apply border-b border-gray-200 dark:border-gray-700 pb-4;
}

.loading-progress {
  @apply bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700;
}
</style>