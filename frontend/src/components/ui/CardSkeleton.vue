<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
  >
    <!-- Header -->
    <div v-if="showHeader" class="flex items-center justify-between mb-4">
      <SkeletonLoader width="150px" height="20px" />
      <SkeletonLoader width="60px" height="16px" />
    </div>

    <!-- Content -->
    <div class="space-y-4">
      <!-- Main content area -->
      <div
        v-if="variant === 'chart'"
        class="h-64 flex items-center justify-center"
      >
        <SkeletonLoader width="100%" height="100%" variant="card" />
      </div>

      <div
        v-else-if="variant === 'stats'"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
      >
        <div v-for="i in statsCount" :key="`stat-${i}`" class="text-center">
          <SkeletonLoader width="60px" height="32px" class="mx-auto mb-2" />
          <SkeletonLoader width="80px" height="16px" class="mx-auto" />
        </div>
      </div>

      <div v-else-if="variant === 'list'" class="space-y-3">
        <div
          v-for="i in listItems"
          :key="`item-${i}`"
          class="flex items-center space-x-3"
        >
          <SkeletonLoader variant="circular" />
          <div class="flex-1 space-y-2">
            <SkeletonLoader width="70%" height="16px" />
            <SkeletonLoader width="50%" height="12px" />
          </div>
          <SkeletonLoader width="60px" height="12px" />
        </div>
      </div>

      <div v-else class="space-y-3">
        <SkeletonLoader
          v-for="i in lines"
          :key="`line-${i}`"
          :width="getLineWidth(i)"
          height="16px"
        />
      </div>
    </div>

    <!-- Footer -->
    <div
      v-if="showFooter"
      class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700"
    >
      <div class="flex justify-between items-center">
        <SkeletonLoader width="100px" height="16px" />
        <SkeletonLoader width="80px" height="32px" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import SkeletonLoader from "./SkeletonLoader.vue";

interface Props {
  variant?: "default" | "chart" | "stats" | "list";
  lines?: number;
  statsCount?: number;
  listItems?: number;
  showHeader?: boolean;
  showFooter?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  variant: "default",
  lines: 4,
  statsCount: 3,
  listItems: 4,
  showHeader: true,
  showFooter: false,
});

const getLineWidth = (lineIndex: number): string => {
  // Vary line widths for more realistic skeleton
  const widths = ["100%", "85%", "92%", "78%", "95%", "88%"];
  return widths[(lineIndex - 1) % widths.length];
};
</script>
