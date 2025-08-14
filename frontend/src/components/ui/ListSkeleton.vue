<template>
  <div class="list-skeleton bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <!-- Header -->
    <div v-if="showHeader" class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <SkeletonLoader width="150px" height="20px" />
        <div class="flex space-x-2">
          <SkeletonLoader width="80px" height="32px" />
          <SkeletonLoader width="100px" height="32px" />
        </div>
      </div>
    </div>

    <!-- Search/Filter Bar -->
    <div v-if="showFilters" class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center space-x-4">
        <SkeletonLoader width="200px" height="36px" />
        <SkeletonLoader width="120px" height="36px" />
        <SkeletonLoader width="100px" height="36px" />
      </div>
    </div>

    <!-- List Items -->
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
      <div
        v-for="i in items"
        :key="`item-${i}`"
        class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700"
      >
        <div class="flex items-center space-x-4">
          <!-- Icon/Avatar -->
          <div v-if="showIcons">
            <SkeletonLoader variant="circular" class="w-10 h-10" />
          </div>

          <!-- Main Content -->
          <div class="flex-1 space-y-2">
            <div class="flex items-center justify-between">
              <SkeletonLoader :width="getTitleWidth(i)" height="16px" />
              <SkeletonLoader width="60px" height="14px" />
            </div>
            <SkeletonLoader :width="getSubtitleWidth(i)" height="14px" />
            <div v-if="showMetadata" class="flex space-x-4">
              <SkeletonLoader width="80px" height="12px" />
              <SkeletonLoader width="100px" height="12px" />
              <SkeletonLoader width="70px" height="12px" />
            </div>
          </div>

          <!-- Actions -->
          <div v-if="showActions" class="flex space-x-2">
            <SkeletonLoader width="24px" height="24px" />
            <SkeletonLoader width="24px" height="24px" />
            <SkeletonLoader width="24px" height="24px" />
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="showPagination" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <SkeletonLoader width="150px" height="16px" />
        <div class="flex space-x-2">
          <SkeletonLoader width="32px" height="32px" />
          <SkeletonLoader width="32px" height="32px" />
          <SkeletonLoader width="32px" height="32px" />
          <SkeletonLoader width="32px" height="32px" />
          <SkeletonLoader width="32px" height="32px" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import SkeletonLoader from './SkeletonLoader.vue';

interface Props {
  items?: number;
  showHeader?: boolean;
  showFilters?: boolean;
  showIcons?: boolean;
  showActions?: boolean;
  showMetadata?: boolean;
  showPagination?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  items: 6,
  showHeader: true,
  showFilters: true,
  showIcons: true,
  showActions: true,
  showMetadata: true,
  showPagination: true
});

const getTitleWidth = (index: number): string => {
  const widths = ['180px', '220px', '160px', '200px', '190px', '210px'];
  return widths[(index - 1) % widths.length];
};

const getSubtitleWidth = (index: number): string => {
  const widths = ['140px', '160px', '120px', '150px', '130px', '170px'];
  return widths[(index - 1) % widths.length];
};
</script>

<style scoped>
.list-skeleton {
  @apply animate-pulse;
}
</style>