<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900"
  >
    <div class="max-w-md w-full space-y-8 text-center">
      <div>
        <h1 class="text-9xl font-bold text-gray-300 dark:text-gray-600">404</h1>
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
          {{ $t("errors.page_not_found") }}
        </h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          {{ $t("errors.page_not_found_description") }}
        </p>
      </div>
      <div>
        <router-link
          to="/dashboard"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          {{ $t("common.go_to_dashboard") }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// 404 Not Found page
</script>
