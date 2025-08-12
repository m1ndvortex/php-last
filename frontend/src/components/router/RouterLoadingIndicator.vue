<template>
  <div
    v-if="isLoading"
    class="fixed inset-0 z-50 flex items-center justify-center bg-white bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"
  >
    <div class="flex flex-col items-center space-y-4">
      <!-- Loading spinner -->
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      
      <!-- Loading message -->
      <div class="text-center">
        <p class="text-lg font-medium text-gray-900 dark:text-white">
          {{ loadingMessage }}
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {{ loadingSubMessage }}
        </p>
      </div>
      
      <!-- Progress bar for timeout -->
      <div v-if="showProgress" class="w-64 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
        <div 
          class="bg-blue-600 h-2 rounded-full transition-all duration-300"
          :style="{ width: `${progressPercentage}%` }"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

// Loading state
const isLoading = computed(() => authStore.isLoading)

// Progress tracking
const showProgress = ref(false)
const progressPercentage = ref(0)
const progressInterval = ref<number | null>(null)
const loadingStartTime = ref<number>(0)

// Loading messages
const loadingMessage = computed(() => {
  if (authStore.isLoading) {
    return 'Authenticating...'
  }
  return 'Loading...'
})

const loadingSubMessage = computed(() => {
  if (authStore.isLoading) {
    return 'Verifying your session and permissions'
  }
  return 'Please wait while we prepare your content'
})

// Start progress tracking when loading begins
watch(isLoading, (newValue) => {
  if (newValue) {
    startProgressTracking()
  } else {
    stopProgressTracking()
  }
})

const startProgressTracking = () => {
  loadingStartTime.value = Date.now()
  showProgress.value = true
  progressPercentage.value = 0
  
  // Show progress after 1 second of loading
  setTimeout(() => {
    if (isLoading.value) {
      progressInterval.value = window.setInterval(() => {
        const elapsed = Date.now() - loadingStartTime.value
        const maxTime = 10000 // 10 seconds max
        const percentage = Math.min((elapsed / maxTime) * 100, 95) // Never reach 100%
        progressPercentage.value = percentage
        
        // If loading takes too long, show error
        if (elapsed > maxTime && isLoading.value) {
          console.warn('Authentication taking longer than expected')
          stopProgressTracking()
        }
      }, 100)
    }
  }, 1000)
}

const stopProgressTracking = () => {
  showProgress.value = false
  progressPercentage.value = 0
  
  if (progressInterval.value) {
    clearInterval(progressInterval.value)
    progressInterval.value = null
  }
}

// Cleanup on unmount
onUnmounted(() => {
  stopProgressTracking()
})

// Handle route changes
router.beforeEach((to, from, next) => {
  // Reset progress when navigating
  if (progressInterval.value) {
    stopProgressTracking()
  }
  next()
})
</script>

<style scoped>
/* Additional loading animations */
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

/* Smooth transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>