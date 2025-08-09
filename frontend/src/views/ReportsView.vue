<template>
  <div class="reports-view">
    <ReportsInterface />
  </div>
</template>

<script setup lang="ts">
import ReportsInterface from '@/components/reports/ReportsInterface.vue'
</script>

<style scoped>
.reports-view {
  @apply min-h-screen bg-gray-50 dark:bg-gray-900;
}
</style>
