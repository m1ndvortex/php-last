<template>
  <div :class="layoutClasses" :dir="direction">
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed, watch, onMounted } from "vue";
import { useI18n } from "vue-i18n";

interface Props {
  forceDirection?: "ltr" | "rtl";
}

const props = withDefaults(defineProps<Props>(), {
  forceDirection: undefined,
});

const { locale } = useI18n();

// Determine text direction based on locale or forced direction
const direction = computed(() => {
  if (props.forceDirection) {
    return props.forceDirection;
  }
  return locale.value === "fa" ? "rtl" : "ltr";
});

const isRTL = computed(() => direction.value === "rtl");

// Layout classes for RTL/LTR support
const layoutClasses = computed(() => ({
  "rtl-layout": isRTL.value,
  "ltr-layout": !isRTL.value,
  "text-right": isRTL.value,
  "text-left": !isRTL.value,
}));

// Update document direction when locale changes
const updateDocumentDirection = () => {
  document.documentElement.dir = direction.value;
  document.documentElement.lang = locale.value;

  // Update body classes for global RTL/LTR styling
  document.body.classList.toggle("rtl", isRTL.value);
  document.body.classList.toggle("ltr", !isRTL.value);
};

// Watch for locale changes
watch(locale, updateDocumentDirection, { immediate: true });

onMounted(() => {
  updateDocumentDirection();
});

// Expose direction state for child components
defineExpose({
  direction,
  isRTL,
});
</script>

<style scoped>
.rtl-layout {
  direction: rtl;
}

.ltr-layout {
  direction: ltr;
}

/* RTL-specific adjustments */
.rtl-layout :deep(.space-x-2 > * + *) {
  margin-left: 0;
  margin-right: 0.5rem;
}

.rtl-layout :deep(.space-x-3 > * + *) {
  margin-left: 0;
  margin-right: 0.75rem;
}

.rtl-layout :deep(.space-x-4 > * + *) {
  margin-left: 0;
  margin-right: 1rem;
}

/* LTR-specific adjustments */
.ltr-layout :deep(.space-x-reverse > * + *) {
  margin-right: 0;
  margin-left: 0.5rem;
}
</style>
