<template>
  <div
    ref="targetRef"
    :class="[
      'relative overflow-hidden',
      containerClass
    ]"
    :style="containerStyle"
  >
    <!-- Loading Placeholder -->
    <div
      v-if="!isLoaded && !imageError"
      class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800"
    >
      <div v-if="imageLoading" class="flex flex-col items-center space-y-2">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}...</span>
      </div>
      <div v-else class="flex flex-col items-center space-y-2">
        <PhotoIcon class="h-8 w-8 text-gray-400" />
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ placeholder || $t('common.image') }}</span>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="imageError"
      class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800"
    >
      <div class="flex flex-col items-center space-y-2">
        <ExclamationTriangleIcon class="h-8 w-8 text-red-400" />
        <span class="text-xs text-red-500 dark:text-red-400">{{ $t('common.image_error') }}</span>
        <button
          v-if="allowRetry"
          @click="retryLoad"
          class="text-xs text-primary-600 hover:text-primary-700 underline"
        >
          {{ $t('common.retry') }}
        </button>
      </div>
    </div>

    <!-- Actual Image -->
    <Transition name="fade">
      <img
        v-if="imageSrc && !imageError"
        :src="imageSrc"
        :alt="alt"
        :class="[
          'w-full h-full object-cover transition-opacity duration-300',
          imageClass
        ]"
        @load="handleImageLoad"
        @error="handleImageError"
      />
    </Transition>

    <!-- Overlay Content -->
    <div v-if="$slots.overlay" class="absolute inset-0">
      <slot name="overlay" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { PhotoIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useLazyImage } from '@/composables/useLazyLoading'

interface Props {
  src: string
  alt?: string
  placeholder?: string
  width?: string | number
  height?: string | number
  containerClass?: string
  imageClass?: string
  allowRetry?: boolean
  eager?: boolean
  threshold?: number
  rootMargin?: string
}

const props = withDefaults(defineProps<Props>(), {
  alt: '',
  placeholder: '',
  containerClass: '',
  imageClass: '',
  allowRetry: true,
  eager: false,
  threshold: 0.1,
  rootMargin: '50px'
})

const { t } = useI18n()

// Lazy loading setup
const {
  targetRef,
  imageSrc,
  imageError,
  imageLoading,
  isVisible,
  isLoaded,
  loadImage
} = useLazyImage(props.src, {
  threshold: props.threshold,
  rootMargin: props.rootMargin,
  once: true
})

// Computed styles
const containerStyle = computed(() => {
  const style: Record<string, string> = {}
  
  if (props.width) {
    style.width = typeof props.width === 'number' ? `${props.width}px` : props.width
  }
  
  if (props.height) {
    style.height = typeof props.height === 'number' ? `${props.height}px` : props.height
  }
  
  return style
})

// Methods
const handleImageLoad = () => {
  // Image loaded successfully
}

const handleImageError = () => {
  // Image failed to load
}

const retryLoad = () => {
  imageError.value = false
  loadImage()
}

// Watch for visibility changes to trigger loading
watch(isVisible, (visible) => {
  if (visible && !isLoaded.value && !props.eager) {
    loadImage()
  }
})

// Watch for src changes
watch(() => props.src, (newSrc) => {
  if (newSrc && newSrc !== imageSrc.value) {
    imageError.value = false
    isLoaded.value = false
    if (isVisible.value || props.eager) {
      loadImage()
    }
  }
})

// Load immediately if eager
onMounted(() => {
  if (props.eager) {
    loadImage()
  }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Loading animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>