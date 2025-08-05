<template>
  <button
    @click="toggleTheme"
    class="p-2 rounded-md text-gray-400 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 transition-colors"
    :title="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
  >
    <!-- Sun icon for light mode -->
    <SunIcon v-if="isDarkMode" class="h-5 w-5" />
    <!-- Moon icon for dark mode -->
    <MoonIcon v-else class="h-5 w-5" />
  </button>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { SunIcon, MoonIcon } from "@heroicons/vue/24/outline";
import { useAppStore } from "../../stores/app";

const appStore = useAppStore();

const isDarkMode = computed(() => appStore.isDarkMode);

const toggleTheme = () => {
  appStore.toggleTheme();
};
</script>
