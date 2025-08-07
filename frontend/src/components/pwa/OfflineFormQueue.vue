<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ t('offline.queueTitle') }}
      </h3>
      <div class="flex items-center space-x-2" :class="{ 'rtl:space-x-reverse': isRTL }">
        <span
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          :class="statusClasses"
        >
          <component :is="statusIcon" class="w-3 h-3 mr-1" :class="{ 'rtl:mr-0 rtl:ml-1': isRTL }" />
          {{ statusText }}
        </span>
        <button
          v-if="!isOnline && queuedForms.length > 0"
          @click="syncWhenOnline"
          class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
        >
          {{ t('offline.syncWhenOnline') }}
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="queuedForms.length === 0" class="text-center py-8">
      <CheckCircleIcon class="mx-auto h-12 w-12 text-green-500" />
      <h4 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
        {{ t('offline.noQueuedForms') }}
      </h4>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ t('offline.allFormsSynced') }}
      </p>
    </div>

    <!-- Queued Forms List -->
    <div v-else class="space-y-4">
      <div
        v-for="form in queuedForms"
        :key="form.id"
        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center space-x-2" :class="{ 'rtl:space-x-reverse': isRTL }">
              <component :is="getFormIcon(form.type)" class="h-5 w-5 text-gray-400" />
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                {{ getFormTitle(form.type) }}
              </h4>
              <span
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                :class="getFormStatusClasses(form)"
              >
                {{ getFormStatus(form) }}
              </span>
            </div>
            
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <span class="font-medium">{{ t('offline.createdAt') }}:</span>
                  {{ formatDate(form.timestamp) }}
                </div>
                <div v-if="form.data.name || form.data.title">
                  <span class="font-medium">{{ t('offline.formData') }}:</span>
                  {{ form.data.name || form.data.title || t('offline.untitled') }}
                </div>
              </div>
            </div>

            <!-- Form Data Preview -->
            <div v-if="expandedForms.includes(form.id)" class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded">
              <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ t('offline.formDataPreview') }}
              </h5>
              <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ JSON.stringify(form.data, null, 2) }}</pre>
            </div>
          </div>

          <div class="flex items-center space-x-2 ml-4" :class="{ 'rtl:space-x-reverse rtl:ml-0 rtl:mr-4': isRTL }">
            <button
              @click="toggleFormExpansion(form.id)"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              :title="expandedForms.includes(form.id) ? t('offline.collapse') : t('offline.expand')"
            >
              <ChevronDownIcon
                class="h-4 w-4 transition-transform"
                :class="{ 'rotate-180': expandedForms.includes(form.id) }"
              />
            </button>
            
            <button
              v-if="isOnline && !form.synced"
              @click="syncSingleForm(form)"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
              :title="t('offline.syncNow')"
            >
              <ArrowPathIcon class="h-4 w-4" />
            </button>
            
            <button
              @click="deleteForm(form.id)"
              class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
              :title="t('offline.deleteForm')"
            >
              <TrashIcon class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="queuedForms.length > 0" class="mt-6 flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
      <div class="text-sm text-gray-500 dark:text-gray-400">
        {{ t('offline.totalQueued', { count: queuedForms.length }) }}
      </div>
      <div class="flex items-center space-x-2" :class="{ 'rtl:space-x-reverse': isRTL }">
        <button
          v-if="isOnline"
          @click="syncAllForms"
          :disabled="syncing"
          class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <ArrowPathIcon class="w-3 h-3 mr-1" :class="{ 'rtl:mr-0 rtl:ml-1 animate-spin': syncing }" />
          {{ syncing ? t('offline.syncing') : t('offline.syncAll') }}
        </button>
        
        <button
          @click="clearAllForms"
          class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          <TrashIcon class="w-3 h-3 mr-1" :class="{ 'rtl:mr-0 rtl:ml-1': isRTL }" />
          {{ t('offline.clearAll') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLocale } from '@/composables/useLocale';
import { usePWA } from '@/composables/usePWA';
import { offlineService } from '@/services/offlineService';
import {
  CheckCircleIcon,
  ChevronDownIcon,
  ArrowPathIcon,
  TrashIcon,
  WifiIcon,
  ExclamationCircleIcon,
  ClockIcon,
  UserIcon,
  DocumentTextIcon,
  CubeIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const { isRTL, formatDate } = useLocale();
const { isOnline } = usePWA();

const queuedForms = ref<any[]>([]);
const expandedForms = ref<string[]>([]);
const syncing = ref(false);

const statusClasses = computed(() => {
  if (!isOnline.value) {
    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
  }
  return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
});

const statusIcon = computed(() => {
  return isOnline.value ? CheckCircleIcon : ExclamationCircleIcon;
});

const statusText = computed(() => {
  return isOnline.value ? t('offline.online') : t('offline.offline');
});

const getFormIcon = (type: string) => {
  const icons: Record<string, any> = {
    customer: UserIcon,
    invoice: DocumentTextIcon,
    inventory: CubeIcon,
    transaction: CurrencyDollarIcon
  };
  return icons[type] || DocumentTextIcon;
};

const getFormTitle = (type: string) => {
  const titles: Record<string, string> = {
    customer: t('forms.customer'),
    invoice: t('forms.invoice'),
    inventory: t('forms.inventory'),
    transaction: t('forms.transaction')
  };
  return titles[type] || t('forms.unknown');
};

const getFormStatus = (form: any) => {
  if (form.synced) return t('offline.synced');
  if (!isOnline.value) return t('offline.pending');
  return t('offline.readyToSync');
};

const getFormStatusClasses = (form: any) => {
  if (form.synced) {
    return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
  }
  if (!isOnline.value) {
    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
  }
  return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
};

const loadQueuedForms = async () => {
  try {
    queuedForms.value = await offlineService.getOfflineForms();
  } catch (error) {
    console.error('Error loading queued forms:', error);
  }
};

const toggleFormExpansion = (formId: string) => {
  const index = expandedForms.value.indexOf(formId);
  if (index > -1) {
    expandedForms.value.splice(index, 1);
  } else {
    expandedForms.value.push(formId);
  }
};

const syncSingleForm = async (form: any) => {
  // Implementation would sync a single form
  console.log('Syncing single form:', form);
};

const syncAllForms = async () => {
  syncing.value = true;
  try {
    // Implementation would sync all forms
    await new Promise(resolve => setTimeout(resolve, 2000)); // Simulate sync
    await loadQueuedForms();
  } catch (error) {
    console.error('Error syncing forms:', error);
  } finally {
    syncing.value = false;
  }
};

const syncWhenOnline = () => {
  // Set flag to sync when connection is restored
  localStorage.setItem('syncWhenOnline', 'true');
};

const deleteForm = async (formId: string) => {
  if (confirm(t('offline.confirmDelete'))) {
    try {
      // Implementation would delete the form
      queuedForms.value = queuedForms.value.filter(f => f.id !== formId);
    } catch (error) {
      console.error('Error deleting form:', error);
    }
  }
};

const clearAllForms = async () => {
  if (confirm(t('offline.confirmClearAll'))) {
    try {
      // Implementation would clear all forms
      queuedForms.value = [];
    } catch (error) {
      console.error('Error clearing forms:', error);
    }
  }
};

onMounted(() => {
  loadQueuedForms();
});
</script>