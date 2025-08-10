<template>
  <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <SkeletonLoader width="200px" height="24px" />
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <!-- Table Header -->
        <thead class="bg-gray-50 dark:bg-gray-900">
          <tr>
            <th
              v-for="i in columns"
              :key="`header-${i}`"
              class="px-6 py-3 text-left"
            >
              <SkeletonLoader width="80px" height="16px" />
            </th>
          </tr>
        </thead>

        <!-- Table Body -->
        <tbody
          class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
        >
          <tr
            v-for="row in rows"
            :key="`row-${row}`"
            class="hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            <td
              v-for="col in columns"
              :key="`cell-${row}-${col}`"
              class="px-6 py-4 whitespace-nowrap"
            >
              <div v-if="col === 1" class="flex items-center">
                <!-- Avatar/Icon -->
                <SkeletonLoader variant="circular" class="mr-4" />
                <div class="space-y-2">
                  <SkeletonLoader width="120px" height="16px" />
                  <SkeletonLoader width="80px" height="12px" />
                </div>
              </div>
              <div
                v-else-if="col === columns"
                class="flex justify-end space-x-2"
              >
                <!-- Action buttons -->
                <SkeletonLoader width="24px" height="24px" />
                <SkeletonLoader width="24px" height="24px" />
                <SkeletonLoader width="24px" height="24px" />
              </div>
              <SkeletonLoader
                v-else
                :width="getColumnWidth(col)"
                height="16px"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <SkeletonLoader width="200px" height="16px" />
        <div class="flex space-x-2">
          <SkeletonLoader width="80px" height="32px" />
          <SkeletonLoader width="80px" height="32px" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import SkeletonLoader from "./SkeletonLoader.vue";

interface Props {
  rows?: number;
  columns?: number;
}

const props = withDefaults(defineProps<Props>(), {
  rows: 5,
  columns: 6,
});

const getColumnWidth = (col: number): string => {
  // Vary column widths for more realistic skeleton
  const widths = ["100px", "80px", "120px", "90px", "110px", "70px"];
  return widths[(col - 1) % widths.length];
};
</script>
