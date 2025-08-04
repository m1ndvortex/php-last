<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ $t("pages.settings") }}
        </h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
          {{ $t("settings.description") }}
        </p>
      </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <p class="text-gray-500 dark:text-gray-400">
        {{ $t("common.coming_soon") }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
// Placeholder for settings view
</script>
