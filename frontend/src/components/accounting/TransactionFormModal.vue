<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-5/6 lg:w-4/5 xl:w-3/4 shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-screen overflow-y-auto">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ transaction ? $t('accounting.edit_transaction') : $t('accounting.create_transaction') }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Transaction Header -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.reference_number') }}
            </label>
            <input
              v-model="form.reference_number"
              type="text"
              :placeholder="$t('accounting.auto_generated')"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('common.date') }} *
            </label>
            <input
              v-model="form.transaction_date"
              type="date"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.transaction_type') }} *
            </label>
            <select
              v-model="form.type"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            >
              <option value="">{{ $t('common.select') }}</option>
              <option value="journal">{{ $t('accounting.journal_entry') }}</option>
              <option value="invoice">{{ $t('accounting.invoice_entry') }}</option>
              <option value="payment">{{ $t('accounting.payment_entry') }}</option>
              <option value="adjustment">{{ $t('accounting.adjustment_entry') }}</option>
            </select>
          </div>
        </div>

        <!-- Description -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.description_english') }} *
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              required
              :placeholder="$t('accounting.transaction_description_placeholder')"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            ></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.description_persian') }}
            </label>
            <textarea
              v-model="form.description_persian"
              rows="3"
              :placeholder="$t('accounting.transaction_description_persian_placeholder')"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
              dir="rtl"
            ></textarea>
          </div>
        </div>

        <!-- Currency and Exchange Rate -->
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
              {{ $t('accounting.exchange_rate') }}
            </label>
            <input
              v-model.number="form.exchange_rate"
              type="number"
              step="0.000001"
              min="0"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
        </div>

        <!-- Transaction Entries -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white">
              {{ $t('accounting.transaction_entries') }}
            </h4>
            <button
              type="button"
              @click="addEntry"
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-900 dark:text-blue-200"
            >
              <PlusIcon class="w-4 h-4 mr-1" />
              {{ $t('accounting.add_entry') }}
            </button>
          </div>

          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div v-if="form.entries.length === 0" class="text-center py-8">
              <p class="text-gray-500 dark:text-gray-400">
                {{ $t('accounting.no_entries_added') }}
              </p>
              <button
                type="button"
                @click="addEntry"
                class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-900 dark:text-blue-200"
              >
                <PlusIcon class="w-4 h-4 mr-2" />
                {{ $t('accounting.add_first_entry') }}
              </button>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(entry, index) in form.entries"
                :key="index"
                class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
              >
                <div class="flex items-center justify-between mb-4">
                  <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $t('accounting.entry') }} {{ index + 1 }}
                  </h5>
                  <button
                    type="button"
                    @click="removeEntry(index)"
                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('accounting.account') }} *
                    </label>
                    <select
                      v-model="entry.account_id"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                    >
                      <option value="">{{ $t('common.select') }}</option>
                      <optgroup
                        v-for="(accounts, type) in accountingStore.accountsByType"
                        :key="type"
                        :label="$t(`accounting.${type}`)"
                      >
                        <option
                          v-for="account in accounts"
                          :key="account.id"
                          :value="account.id"
                        >
                          {{ account.code }} - {{ getLocalizedName(account) }}
                        </option>
                      </optgroup>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('accounting.debit_amount') }}
                    </label>
                    <input
                      v-model.number="entry.debit_amount"
                      @input="updateTotalAmount"
                      type="number"
                      step="0.01"
                      min="0"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('accounting.credit_amount') }}
                    </label>
                    <input
                      v-model.number="entry.credit_amount"
                      @input="updateTotalAmount"
                      type="number"
                      step="0.01"
                      min="0"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                      {{ $t('common.description') }}
                    </label>
                    <input
                      v-model="entry.description"
                      type="text"
                      :placeholder="$t('accounting.entry_description_placeholder')"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Balance Check -->
            <div v-if="form.entries.length > 0" class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
              <div class="flex items-center justify-between text-sm">
                <div class="flex space-x-6">
                  <span class="text-gray-600 dark:text-gray-400">
                    {{ $t('accounting.total_debits') }}: {{ formatCurrency(totalDebits) }}
                  </span>
                  <span class="text-gray-600 dark:text-gray-400">
                    {{ $t('accounting.total_credits') }}: {{ formatCurrency(totalCredits) }}
                  </span>
                </div>
                <span
                  :class="[
                    'font-medium',
                    isBalanced
                      ? 'text-green-600 dark:text-green-400'
                      : 'text-red-600 dark:text-red-400'
                  ]"
                >
                  {{ isBalanced ? $t('accounting.balanced') : $t('accounting.unbalanced') }}
                  ({{ $t('accounting.difference') }}: {{ formatCurrency(Math.abs(totalDebits - totalCredits)) }})
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $t('common.notes') }}
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            :placeholder="$t('accounting.transaction_notes_placeholder')"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          ></textarea>
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
            :disabled="loading || !isBalanced || form.entries.length === 0"
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
              {{ transaction ? $t('common.update') : $t('common.create') }}
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { XMarkIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useAccountingStore, type TransactionForm, type TransactionEntry } from '@/stores/accounting'
import { useNumberFormatter } from '@/composables/useNumberFormatter'
import type { Transaction, Account } from '@/types/business'

interface Props {
  transaction?: Transaction | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  saved: [transaction: Transaction]
}>()

const accountingStore = useAccountingStore()
const { formatCurrency } = useNumberFormatter()
const loading = ref(false)

const form = reactive<TransactionForm>({
  reference_number: '',
  description: '',
  description_persian: '',
  transaction_date: new Date().toISOString().split('T')[0],
  type: '',
  total_amount: 0,
  currency: 'USD',
  exchange_rate: 1,
  cost_center_id: undefined,
  tags: [],
  notes: '',
  entries: []
})

const totalDebits = computed(() => {
  return form.entries.reduce((sum, entry) => sum + (entry.debit_amount || 0), 0)
})

const totalCredits = computed(() => {
  return form.entries.reduce((sum, entry) => sum + (entry.credit_amount || 0), 0)
})

const isBalanced = computed(() => {
  return Math.abs(totalDebits.value - totalCredits.value) < 0.01
})

const getLocalizedName = (account: Account) => {
  const locale = document.documentElement.lang || 'en'
  return locale === 'fa' && account.name_persian ? account.name_persian : account.name
}

const addEntry = () => {
  form.entries.push({
    account_id: 0,
    debit_amount: 0,
    credit_amount: 0,
    description: '',
    description_persian: ''
  })
}

const removeEntry = (index: number) => {
  form.entries.splice(index, 1)
  updateTotalAmount()
}

const updateTotalAmount = () => {
  form.total_amount = Math.max(totalDebits.value, totalCredits.value)
}

const handleSubmit = async () => {
  if (!isBalanced.value || form.entries.length === 0) return
  
  loading.value = true
  
  try {
    let savedTransaction: Transaction
    
    if (props.transaction) {
      savedTransaction = await accountingStore.updateTransaction(props.transaction.id, form)
    } else {
      savedTransaction = await accountingStore.createTransaction(form)
    }
    
    emit('saved', savedTransaction)
  } catch (error) {
    console.error('Failed to save transaction:', error)
  } finally {
    loading.value = false
  }
}

// Initialize form with transaction data if editing
onMounted(() => {
  if (props.transaction) {
    Object.assign(form, {
      reference_number: props.transaction.reference_number,
      description: props.transaction.description,
      description_persian: props.transaction.description_persian || '',
      transaction_date: props.transaction.transaction_date,
      type: props.transaction.type,
      total_amount: props.transaction.total_amount,
      currency: props.transaction.currency || 'USD',
      exchange_rate: props.transaction.exchange_rate || 1,
      cost_center_id: props.transaction.cost_center_id,
      tags: props.transaction.tags || [],
      notes: props.transaction.notes || '',
      entries: [] // Would need to fetch entries from API
    })
  } else {
    // Add two empty entries for new transactions
    addEntry()
    addEntry()
  }
})
</script>