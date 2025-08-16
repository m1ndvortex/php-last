<template>
  <div v-if="hasError" class="error-boundary">
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <ExclamationTriangleIcon class="h-6 w-6 text-red-400" />
        </div>
        <div class="ml-3 flex-1">
          <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
            {{ title || $t('errors.component_error') }}
          </h3>
          <div class="mt-2 text-sm text-red-700 dark:text-red-300">
            <p>{{ message || $t('errors.component_error_message') }}</p>
          </div>
          <div v-if="showDetails && errorDetails" class="mt-3">
            <details class="text-xs text-red-600 dark:text-red-400">
              <summary class="cursor-pointer hover:text-red-800 dark:hover:text-red-200">
                {{ $t('errors.show_details') }}
              </summary>
              <pre class="mt-2 whitespace-pre-wrap bg-red-100 dark:bg-red-900/40 p-2 rounded">{{ errorDetails }}</pre>
            </details>
          </div>
          <div class="mt-4 flex space-x-3">
            <button
              @click="retry"
              class="bg-red-100 dark:bg-red-900/40 px-3 py-2 rounded-md text-sm font-medium text-red-800 dark:text-red-200 hover:bg-red-200 dark:hover:bg-red-900/60 transition-colors"
            >
              {{ $t('common.retry') }}
            </button>
            <button
              v-if="showReload"
              @click="reload"
              class="bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
            >
              {{ $t('common.reload_page') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <slot v-else />
</template>

<script setup lang="ts">
import { ref, onErrorCaptured, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

interface Props {
  title?: string;
  message?: string;
  showDetails?: boolean;
  showReload?: boolean;
  fallbackComponent?: any;
  onError?: (error: Error, instance: any, info: string) => void;
}

const props = withDefaults(defineProps<Props>(), {
  showDetails: false,
  showReload: false,
});

const emit = defineEmits<{
  error: [error: Error, instance: any, info: string];
  retry: [];
}>();

const { t } = useI18n();

const hasError = ref(false);
const errorDetails = ref<string>('');
const retryKey = ref(0);

// Vue 3 error boundary using onErrorCaptured
onErrorCaptured((error: Error, instance: any, info: string) => {
  hasError.value = true;
  errorDetails.value = `${error.message}\n\nStack trace:\n${error.stack}\n\nComponent info: ${info}`;
  
  // Call custom error handler if provided
  if (props.onError) {
    props.onError(error, instance, info);
  }
  
  // Emit error event
  emit('error', error, instance, info);
  
  // Log error to console in development
  if (import.meta.env.DEV) {
    console.error('Error caught by ErrorBoundary:', error);
    console.error('Component info:', info);
    console.error('Instance:', instance);
  }
  
  // Prevent the error from propagating further
  return false;
});

const retry = async () => {
  hasError.value = false;
  errorDetails.value = '';
  retryKey.value++;
  
  // Wait for next tick to ensure component re-renders
  await nextTick();
  
  emit('retry');
};

const reload = () => {
  window.location.reload();
};
</script>

<style scoped>
.error-boundary {
  margin: 1rem 0;
}

pre {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 0.75rem;
  line-height: 1.4;
  max-height: 200px;
  overflow-y: auto;
}

details summary {
  outline: none;
}

details[open] summary {
  margin-bottom: 0.5rem;
}
</style>