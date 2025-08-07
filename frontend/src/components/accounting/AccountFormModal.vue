<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ account ? $t('accounting.edit_account') : $t('accounting.create_account') }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Account Code -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('accounting.account_code') }} *
          </label>
          <input
            v-model="form.code"
            type="text"
            required
            :placeholder="$t('accounting.account_code_placeholder')"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
          <p v-if="errors.code" class="mt-1 text-sm text-red-600">{{ errors.code }}</p>
        </div>

        <!-- Account Name -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.account_name_english') }} *
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              :placeholder="$t('accounting.account_name_placeholder')"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.account_name_persian') }}
            </label>
            <input
              v-model="form.name_persian"
              type="text"
              :placeholder="$t('accounting.account_name_persian_placeholder')"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              dir="rtl"
            />
          </div>
        </div>

        <!-- Account Type and Subtype -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.account_type') }} *
            </label>
            <select
              v-model="form.type"
              @change="updateSubtypeOptions"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            >
              <option value="">{{ $t('common.select') }}</option>
              <option value="asset">{{ $t('accounting.asset') }}</option>
              <option value="liability">{{ $t('accounting.liability') }}</option>
              <option value="equity">{{ $t('accounting.equity') }}</option>
              <option value="revenue">{{ $t('accounting.revenue') }}</option>
              <option value="expense">{{ $t('accounting.expense') }}</option>
            </select>
            <p v-if="errors.type" class="mt-1 text-sm text-red-600">{{ errors.type }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.account_subtype') }}
            </label>
            <select
              v-model="form.subtype"
              :disabled="!form.type"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm disabled:opacity-50"
            >
              <option value="">{{ $t('common.select') }}</option>
              <option v-for="subtype in subtypeOptions" :key="subtype.value" :value="subtype.value">
                {{ $t(subtype.label) }}
              </option>
            </select>
          </div>
        </div>

        <!-- Parent Account -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('accounting.parent_account') }}
          </label>
          <select
            v-model="form.parent_id"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          >
            <option value="">{{ $t('accounting.no_parent') }}</option>
            <option
              v-for="parentAccount in availableParentAccounts"
              :key="parentAccount.id"
              :value="parentAccount.id"
            >
              {{ parentAccount.code }} - {{ getLocalizedName(parentAccount) }}
            </option>
          </select>
        </div>

        <!-- Currency and Opening Balance -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.currency') }}
            </label>
            <select
              v-model="form.currency"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            >
              <option value="USD">USD - US Dollar</option>
              <option value="EUR">EUR - Euro</option>
              <option value="IRR">IRR - Iranian Rial</option>
              <option value="AED">AED - UAE Dirham</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.opening_balance') }}
            </label>
            <input
              v-model.number="form.opening_balance"
              type="number"
              step="0.01"
              :placeholder="$t('accounting.opening_balance_placeholder')"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('common.description') }}
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            :placeholder="$t('accounting.account_description_placeholder')"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          ></textarea>
        </div>

        <!-- Active Status -->
        <div class="flex items-center">
          <input
            v-model="form.is_active"
            type="checkbox"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <label class="ml-2 block text-sm text-gray-900 dark:text-white">
            {{ $t('accounting.account_is_active') }}
          </label>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
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
            <span v-if="loading" class="inline-flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ $t('common.saving') }}
            </span>
            <span v-else>
              {{ account ? $t('common.update') : $t('common.create') }}
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useAccountingStore } from '@/stores/accounting'
import type { Account } from '@/types/business'

interface Props {
  account?: Account | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  saved: [account: Account]
}>()

const accountingStore = useAccountingStore()
const loading = ref(false)
const errors = ref<Record<string, string>>({})

const form = reactive({
  code: '',
  name: '',
  name_persian: '',
  type: '' as 'asset' | 'liability' | 'equity' | 'revenue' | 'expense' | '',
  subtype: '',
  parent_id: null as number | null,
  currency: 'USD',
  opening_balance: 0,
  description: '',
  is_active: true
})

const subtypeOptions = ref<Array<{value: string, label: string}>>([])

const subtypeMap = {
  asset: [
    { value: 'current_asset', label: 'accounting.current_asset' },
    { value: 'fixed_asset', label: 'accounting.fixed_asset' },
    { value: 'intangible_asset', label: 'accounting.intangible_asset' }
  ],
  liability: [
    { value: 'current_liability', label: 'accounting.current_liability' },
    { value: 'long_term_liability', label: 'accounting.long_term_liability' }
  ],
  equity: [
    { value: 'owner_equity', label: 'accounting.owner_equity' },
    { value: 'retained_earnings', label: 'accounting.retained_earnings' }
  ],
  revenue: [
    { value: 'operating_revenue', label: 'accounting.operating_revenue' },
    { value: 'other_revenue', label: 'accounting.other_revenue' }
  ],
  expense: [
    { value: 'operating_expense', label: 'accounting.operating_expense' },
    { value: 'other_expense', label: 'accounting.other_expense' }
  ]
}

const availableParentAccounts = computed(() => {
  return accountingStore.accounts.filter(account => {
    // Don't include the current account as a parent option
    if (props.account && account.id === props.account.id) return false
    // Only show accounts of the same type
    return account.type === form.type
  })
})

const getLocalizedName = (account: Account) => {
  const locale = document.documentElement.lang || 'en'
  return locale === 'fa' && account.name_persian ? account.name_persian : account.name
}

const updateSubtypeOptions = () => {
  subtypeOptions.value = subtypeMap[form.type as keyof typeof subtypeMap] || []
  form.subtype = '' // Reset subtype when type changes
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.code.trim()) {
    errors.value.code = 'Account code is required'
  }
  
  if (!form.name.trim()) {
    errors.value.name = 'Account name is required'
  }
  
  if (!form.type) {
    errors.value.type = 'Account type is required'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  loading.value = true
  
  try {
    let savedAccount: Account
    
    const formData = {
      ...form,
      type: form.type as 'asset' | 'liability' | 'equity' | 'revenue' | 'expense',
      parent_id: form.parent_id || undefined
    }
    
    if (props.account) {
      savedAccount = await accountingStore.updateAccount(props.account.id, formData)
    } else {
      savedAccount = await accountingStore.createAccount(formData)
    }
    
    emit('saved', savedAccount)
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
  } finally {
    loading.value = false
  }
}

// Initialize form with account data if editing
onMounted(() => {
  if (props.account) {
    Object.assign(form, {
      code: props.account.code,
      name: props.account.name,
      name_persian: props.account.name_persian || '',
      type: props.account.type,
      subtype: props.account.subtype || '',
      parent_id: props.account.parent_id,
      currency: props.account.currency || 'USD',
      opening_balance: props.account.opening_balance || 0,
      description: props.account.description || '',
      is_active: props.account.is_active
    })
    updateSubtypeOptions()
  }
})

watch(() => form.type, updateSubtypeOptions)
</script>