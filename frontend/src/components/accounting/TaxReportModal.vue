<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t('accounting.generate_tax_report') }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('accounting.report_type') }} *
          </label>
          <select
            v-model="form.report_type"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          >
            <option value="">{{ $t('common.select') }}</option>
            <option value="vat_return">{{ $t('accounting.vat_return') }}</option>
            <option value="sales_tax">{{ $t('accounting.sales_tax_report') }}</option>
            <option value="income_tax">{{ $t('accounting.income_tax_report') }}</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('common.date_from') }} *
            </label>
            <input
              v-model="form.period_start"
              type="date"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('common.date_to') }} *
            </label>
            <input
              v-model="form.period_end"
              type="date"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
        </div>

        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
          >
            {{ loading ? $t('common.generating') : $t('accounting.generate_report') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import type { TaxReport } from '@/stores/accounting'

const emit = defineEmits<{
  close: []
  generated: [report: TaxReport]
}>()

const loading = ref(false)

const form = reactive({
  report_type: '',
  period_start: new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
  period_end: new Date().toISOString().split('T')[0]
})

const handleSubmit = async () => {
  loading.value = true
  
  try {
    // Mock report generation
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    const report: TaxReport = {
      id: Date.now(),
      report_type: form.report_type,
      period_start: form.period_start,
      period_end: form.period_end,
      total_sales: Math.random() * 100000,
      total_tax: Math.random() * 10000,
      status: 'draft',
      generated_at: new Date().toISOString(),
      data: {}
    }
    
    emit('generated', report)
  } catch (error) {
    console.error('Failed to generate tax report:', error)
  } finally {
    loading.value = false
  }
}
</script>