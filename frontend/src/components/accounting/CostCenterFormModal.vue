<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ costCenter ? $t('accounting.edit_cost_center') : $t('accounting.create_cost_center') }}
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
            {{ $t('accounting.code') }} *
          </label>
          <input
            v-model="form.code"
            type="text"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('common.name') }} (English) *
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('common.name') }} (Persian)
            </label>
            <input
              v-model="form.name_persian"
              type="text"
              dir="rtl"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('common.description') }}
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          ></textarea>
        </div>

        <div class="flex items-center">
          <input
            v-model="form.is_active"
            type="checkbox"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <label class="ml-2 block text-sm text-gray-900 dark:text-white">
            {{ $t('common.active') }}
          </label>
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
            {{ loading ? $t('common.saving') : (costCenter ? $t('common.update') : $t('common.create')) }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import type { CostCenter } from '@/stores/accounting'

interface Props {
  costCenter?: CostCenter | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  saved: [costCenter: CostCenter]
}>()

const loading = ref(false)

const form = reactive({
  code: '',
  name: '',
  name_persian: '',
  description: '',
  is_active: true
})

const handleSubmit = async () => {
  loading.value = true
  
  try {
    // Mock save operation
    await new Promise(resolve => setTimeout(resolve, 500))
    
    const savedCostCenter: CostCenter = {
      id: props.costCenter?.id || Date.now(),
      code: form.code,
      name: form.name,
      name_persian: form.name_persian,
      description: form.description,
      is_active: form.is_active,
      created_at: props.costCenter?.created_at || new Date().toISOString(),
      updated_at: new Date().toISOString()
    }
    
    emit('saved', savedCostCenter)
  } catch (error) {
    console.error('Failed to save cost center:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (props.costCenter) {
    form.code = props.costCenter.code
    form.name = props.costCenter.name
    form.name_persian = props.costCenter.name_persian || ''
    form.description = props.costCenter.description || ''
    form.is_active = props.costCenter.is_active
  }
})
</script>