<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t('reports.export_report') }}
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ $t('reports.export_format') }}
            </label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  v-model="selectedFormat"
                  type="radio"
                  value="pdf"
                  class="form-radio"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                  <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                  PDF Document
                </span>
              </label>
              <label class="flex items-center">
                <input
                  v-model="selectedFormat"
                  type="radio"
                  value="excel"
                  class="form-radio"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                  <i class="fas fa-file-excel text-green-500 mr-2"></i>
                  Excel Spreadsheet
                </span>
              </label>
              <label class="flex items-center">
                <input
                  v-model="selectedFormat"
                  type="radio"
                  value="csv"
                  class="form-radio"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                  <i class="fas fa-file-csv text-blue-500 mr-2"></i>
                  CSV File
                </span>
              </label>
            </div>
          </div>

          <div v-if="report">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              {{ $t('reports.report_details') }}
            </label>
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-md text-sm">
              <p><strong>{{ $t('reports.type') }}:</strong> {{ report.type }}</p>
              <p><strong>{{ $t('reports.period') }}:</strong> {{ report.period }}</p>
              <p><strong>{{ $t('reports.generated') }}:</strong> {{ formatDate(report.generated_at) }}</p>
            </div>
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="btn btn-secondary"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              @click="exportReport"
              :disabled="loading || !selectedFormat"
              class="btn btn-primary"
            >
              <i v-if="loading" class="fas fa-spinner animate-spin mr-2"></i>
              <i v-else class="fas fa-download mr-2"></i>
              {{ $t('reports.export') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

const { t } = useI18n()

const props = defineProps<{
  report: any
}>()

const emit = defineEmits<{
  close: []
  exported: []
}>()

const loading = ref(false)
const selectedFormat = ref('pdf')

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const exportReport = async () => {
  loading.value = true
  try {
    const response = await api.post('/reports/export', {
      report_id: props.report.id,
      format: selectedFormat.value
    })

    // Create download link
    const downloadUrl = response.data.data.download_url
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = response.data.data.filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    emit('exported')
  } catch (error) {
    console.error('Failed to export report:', error)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.btn {
  @apply px-4 py-2 rounded-lg font-medium transition-colors;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50;
}

.btn-secondary {
  @apply bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300;
}

.form-radio {
  @apply text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600;
}
</style>