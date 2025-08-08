<template>
  <div
    ref="targetRef"
    :class="[
      'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700',
      containerClass
    ]"
  >
    <!-- Loading State -->
    <div v-if="!isLoaded && !componentError" class="p-6">
      <CardSkeleton
        :variant="skeletonVariant"
        :show-header="showHeader"
        :show-footer="showFooter"
        :lines="skeletonLines"
      />
    </div>

    <!-- Error State -->
    <div v-else-if="componentError" class="p-6 text-center">
      <div class="flex flex-col items-center space-y-3">
        <ExclamationTriangleIcon class="h-12 w-12 text-red-400" />
        <div class="text-sm text-red-600 dark:text-red-400">
          {{ $t('dashboard.widget_error') }}
        </div>
        <button
          @click="retryLoad"
          class="text-sm text-primary-600 hover:text-primary-700 underline"
        >
          {{ $t('common.retry') }}
        </button>
      </div>
    </div>

    <!-- Actual Component -->
    <Suspense v-else-if="component">
      <component
        :is="component"
        v-bind="componentProps"
        @error="handleComponentError"
      />
      
      <template #fallback>
        <div class="p-6">
          <CardSkeleton
            :variant="skeletonVariant"
            :show-header="showHeader"
            :show-footer="showFooter"
            :lines="skeletonLines"
          />
        </div>
      </template>
    </Suspense>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useLazyComponent } from '@/composables/useLazyLoading'
import { usePerformanceMonitoring } from '@/composables/usePerformanceMonitoring'
import CardSkeleton from '@/components/ui/CardSkeleton.vue'

interface Props {
  componentImport: () => Promise<any>
  componentProps?: Record<string, any>
  containerClass?: string
  skeletonVariant?: 'default' | 'chart' | 'stats' | 'list'
  skeletonLines?: number
  showHeader?: boolean
  showFooter?: boolean
  eager?: boolean
  threshold?: number
  rootMargin?: string
}

const props = withDefaults(defineProps<Props>(), {
  componentProps: () => ({}),
  containerClass: '',
  skeletonVariant: 'default',
  skeletonLines: 4,
  showHeader: true,
  showFooter: false,
  eager: false,
  threshold: 0.1,
  rootMargin: '100px'
})

const { t } = useI18n()
const { mark, measure } = usePerformanceMonitoring('LazyDashboardWidget')

// Lazy loading setup
const {
  targetRef,
  component,
  componentError,
  componentLoading,
  isVisible,
  isLoaded,
  loadComponent
} = useLazyComponent(props.componentImport, {
  threshold: props.threshold,
  rootMargin: props.rootMargin,
  once: true
})

// Methods
const handleComponentError = (error: Error) => {
  console.error('Dashboard widget error:', error)
  componentError.value = true
}

const retryLoad = () => {
  mark('retry-start')
  componentError.value = false
  isLoaded.value = false
  loadComponent()
  measure('retry-duration', 'retry-start')
}

// Watch for visibility changes to trigger loading
watch(isVisible, (visible) => {
  if (visible && !isLoaded.value && !props.eager) {
    mark('lazy-load-start')
    loadComponent()
    measure('lazy-load-duration', 'lazy-load-start')
  }
})

// Load immediately if eager
onMounted(() => {
  if (props.eager) {
    mark('eager-load-start')
    loadComponent()
    measure('eager-load-duration', 'eager-load-start')
  }
})
</script>