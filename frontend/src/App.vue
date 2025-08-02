<template>
  <div id="app" :class="{ 'rtl': isRTL }" :dir="isRTL ? 'rtl' : 'ltr'">
    <RouterView />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterView } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()

const isRTL = computed(() => locale.value === 'fa')
</script>

<style>
/* RTL Support */
.rtl {
  direction: rtl;
  font-family: 'Vazir', 'Tahoma', 'Arial', sans-serif;
}

.rtl * {
  font-family: 'Vazir', 'Tahoma', 'Arial', sans-serif;
}

/* LTR Support */
html:not(.rtl) {
  font-family: 'Inter', system-ui, sans-serif;
}

html:not(.rtl) * {
  font-family: 'Inter', system-ui, sans-serif;
}
</style>