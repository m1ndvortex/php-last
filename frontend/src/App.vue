<template>
  <RTLProvider>
    <div id="app" :class="{ dark: isDarkMode }">
      <router-view />
    </div>
  </RTLProvider>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useAppStore } from "./stores/app";
import RTLProvider from "./components/localization/RTLProvider.vue";

const appStore = useAppStore();
const isDarkMode = computed(() => appStore.isDarkMode);
</script>

<style>
/* Base styles */
* {
  box-sizing: border-box;
}

/* RTL Support */
.rtl {
  direction: rtl;
  font-family: "Vazir", "Tahoma", "Arial", sans-serif;
}

.rtl * {
  font-family: "Vazir", "Tahoma", "Arial", sans-serif;
}

/* LTR Support */
html:not(.rtl) {
  font-family: "Inter", system-ui, sans-serif;
}

html:not(.rtl) * {
  font-family: "Inter", system-ui, sans-serif;
}

/* Dark mode support */
.dark {
  color-scheme: dark;
}

/* Scrollbar styles */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(156, 163, 175, 0.7);
}

.dark ::-webkit-scrollbar-thumb {
  background: rgba(75, 85, 99, 0.5);
}

.dark ::-webkit-scrollbar-thumb:hover {
  background: rgba(75, 85, 99, 0.7);
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}

.slide-enter-from {
  transform: translateX(-100%);
}

.slide-leave-to {
  transform: translateX(-100%);
}

.rtl .slide-enter-from {
  transform: translateX(100%);
}

.rtl .slide-leave-to {
  transform: translateX(100%);
}
</style>
