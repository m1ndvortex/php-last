<template>
  <div
    v-if="showWarning"
    class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border border-orange-200"
  >
    <div class="p-4">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <svg
            class="h-6 w-6 text-orange-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
            />
          </svg>
        </div>
        <div class="ml-3 w-0 flex-1">
          <p class="text-sm font-medium text-gray-900">
            Session Expiring Soon
          </p>
          <p class="mt-1 text-sm text-gray-500">
            Your session will expire in {{ timeRemaining }} minute{{ timeRemaining !== 1 ? 's' : '' }}.
          </p>
          <div class="mt-3 flex space-x-2">
            <button
              @click="extendSession"
              :disabled="isExtending"
              class="bg-orange-600 hover:bg-orange-700 text-white text-xs font-medium py-1 px-3 rounded-md disabled:opacity-50"
            >
              <span v-if="isExtending">Extending...</span>
              <span v-else>Extend Session</span>
            </button>
            <button
              @click="dismissWarning"
              class="bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-medium py-1 px-3 rounded-md"
            >
              Dismiss
            </button>
          </div>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
          <button
            @click="dismissWarning"
            class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
          >
            <span class="sr-only">Close</span>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Progress bar -->
    <div class="bg-gray-200 h-1">
      <div
        class="bg-orange-500 h-1 transition-all duration-1000 ease-linear"
        :style="{ width: `${progressPercentage}%` }"
      ></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useNotifications } from '@/composables/useNotifications';

const authStore = useAuthStore();
const { showSuccess, showError } = useNotifications();

const showWarning = ref(false);
const isExtending = ref(false);
const warningThreshold = 5; // Show warning when 5 minutes or less remain
const updateInterval = ref<number | null>(null);

const timeRemaining = computed(() => {
  return authStore.sessionTimeRemaining;
});

const progressPercentage = computed(() => {
  if (timeRemaining.value <= 0) return 0;
  return Math.max(0, (timeRemaining.value / warningThreshold) * 100);
});

const shouldShowWarning = computed(() => {
  return authStore.isAuthenticated && 
         timeRemaining.value > 0 && 
         timeRemaining.value <= warningThreshold;
});

// Watch for session expiry conditions
watch(shouldShowWarning, (newValue) => {
  if (newValue && !showWarning.value) {
    showWarning.value = true;
  }
});

// Watch for session expiry
watch(timeRemaining, (newValue) => {
  if (newValue <= 0 && authStore.isAuthenticated) {
    showWarning.value = false;
    handleSessionExpired();
  }
});

const extendSession = async (): Promise<void> => {
  if (isExtending.value) return;

  try {
    isExtending.value = true;
    const extended = await authStore.extendSession();
    
    if (extended) {
      showSuccess('Session Extended', 'Your session has been extended successfully.');
      showWarning.value = false;
    } else {
      showError('Extension Failed', 'Unable to extend your session. Please save your work and log in again.');
    }
  } catch (error) {
    console.error('Session extension failed:', error);
    showError('Extension Failed', 'An error occurred while extending your session.');
  } finally {
    isExtending.value = false;
  }
};

const dismissWarning = (): void => {
  showWarning.value = false;
};

const handleSessionExpired = (): void => {
  showError(
    'Session Expired',
    'Your session has expired. You will be redirected to the login page.',
    { duration: 5000 }
  );

  // Logout after a short delay
  setTimeout(() => {
    authStore.logout();
  }, 2000);
};

// Set up periodic updates
onMounted(() => {
  updateInterval.value = window.setInterval(() => {
    // This will trigger reactivity updates
  }, 1000);
});

onUnmounted(() => {
  if (updateInterval.value) {
    clearInterval(updateInterval.value);
  }
});
</script>