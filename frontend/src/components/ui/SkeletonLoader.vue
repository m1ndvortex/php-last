<template>
  <div
    :class="[
      'animate-pulse bg-gray-200 dark:bg-gray-700 rounded',
      sizeClasses,
      customClass
    ]"
    :style="customStyle"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  width?: string | number
  height?: string | number
  variant?: 'text' | 'circular' | 'rectangular' | 'card'
  lines?: number
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'rectangular',
  lines: 1,
  class: ''
})

const sizeClasses = computed(() => {
  switch (props.variant) {
    case 'text':
      return 'h-4'
    case 'circular':
      return 'rounded-full w-10 h-10'
    case 'card':
      return 'h-32'
    default:
      return 'h-6'
  }
})

const customStyle = computed(() => {
  const style: Record<string, string> = {}
  
  if (props.width) {
    style.width = typeof props.width === 'number' ? `${props.width}px` : props.width
  }
  
  if (props.height) {
    style.height = typeof props.height === 'number' ? `${props.height}px` : props.height
  }
  
  return style
})

const customClass = computed(() => props.class)
</script>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>