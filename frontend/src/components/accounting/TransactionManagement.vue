<template>
  <div class="space-y-6">
    <!-- Header with Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0"
      >
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t("accounting.transactions") }}
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $t("accounting.transactions_description") }}
          </p>
        </div>
        <div class="flex space-x-3">
          <button
            @click="showTransactionModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            {{ $t("accounting.create_transaction") }}
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("common.date_from") }}
          </label>
          <input
            v-model="accountingStore.transactionFilter.date_from"
            @change="accountingStore.fetchTransactions()"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("common.date_to") }}
          </label>
          <input
            v-model="accountingStore.transactionFilter.date_to"
            @change="accountingStore.fetchTransactions()"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("accounting.transaction_type") }}
          </label>
          <select
            v-model="accountingStore.transactionFilter.type"
            @change="accountingStore.fetchTransactions()"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          >
            <option value="">{{ $t("common.all") }}</option>
            <option value="journal">
              {{ $t("accounting.journal_entry") }}
            </option>
            <option value="invoice">
              {{ $t("accounting.invoice_entry") }}
            </option>
            <option value="payment">
              {{ $t("accounting.payment_entry") }}
            </option>
            <option value="adjustment">
              {{ $t("accounting.adjustment_entry") }}
            </option>
          </select>
        </div>
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("common.search") }}
          </label>
          <input
            v-model="accountingStore.transactionFilter.search"
            @input="accountingStore.fetchTransactions()"
            type="text"
            :placeholder="$t('accounting.search_transactions')"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h4 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t("accounting.transaction_list") }}
          </h4>
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("common.total") }}:
              {{ accountingStore.filteredTransactions.length }}
            </span>
          </div>
        </div>
      </div>

      <div v-if="accountingStore.loading" class="p-6 text-center">
        <div class="inline-flex items-center">
          <svg
            class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          {{ $t("common.loading") }}
        </div>
      </div>

      <div
        v-else-if="accountingStore.filteredTransactions.length === 0"
        class="p-12 text-center"
      >
        <CurrencyDollarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("accounting.no_transactions") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("accounting.no_transactions_description") }}
        </p>
        <div class="mt-6">
          <button
            @click="showTransactionModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            {{ $t("accounting.create_first_transaction") }}
          </button>
        </div>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.date") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.reference") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.description") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.type") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.amount") }}
              </th>
              <th
                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.status") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.actions") }}
              </th>
            </tr>
          </thead>
          <tbody
            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
          >
            <tr
              v-for="transaction in accountingStore.filteredTransactions"
              :key="transaction.id"
            >
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ formatDate(transaction.transaction_date) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"
              >
                {{ transaction.reference_number }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                {{ getLocalizedDescription(transaction) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white capitalize"
              >
                {{ $t(`accounting.${transaction.type}`) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
              >
                {{ formatCurrency(transaction.total_amount || 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                    transaction.is_locked
                      ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                      : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                  ]"
                >
                  {{
                    transaction.is_locked
                      ? $t("accounting.locked")
                      : $t("accounting.unlocked")
                  }}
                </span>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
              >
                <div class="flex items-center justify-end space-x-2">
                  <button
                    @click="viewTransaction(transaction)"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                  >
                    {{ $t("common.view") }}
                  </button>
                  <button
                    v-if="!transaction.is_locked"
                    @click="editTransaction(transaction)"
                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                  >
                    {{ $t("common.edit") }}
                  </button>
                  <button
                    @click="toggleTransactionLock(transaction)"
                    :class="[
                      transaction.is_locked
                        ? 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300'
                        : 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300',
                    ]"
                  >
                    {{
                      transaction.is_locked
                        ? $t("accounting.unlock")
                        : $t("accounting.lock")
                    }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Transaction Modal -->
    <TransactionFormModal
      v-if="showTransactionModal"
      :transaction="editingTransaction"
      @close="closeTransactionModal"
      @saved="handleTransactionSaved"
    />

    <!-- Transaction Details Modal -->
    <TransactionDetailsModal
      v-if="showDetailsModal"
      :transaction="viewingTransaction"
      @close="showDetailsModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { PlusIcon, CurrencyDollarIcon } from "@heroicons/vue/24/outline";
import { useAccountingStore } from "@/stores/accounting";
import { useLocale } from "@/composables/useLocale";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { Transaction } from "@/types/business";
import TransactionFormModal from "./TransactionFormModal.vue";
import TransactionDetailsModal from "./TransactionDetailsModal.vue";

const accountingStore = useAccountingStore();
const { formatDate } = useLocale();
const { formatCurrency } = useNumberFormatter();

const showTransactionModal = ref(false);
const showDetailsModal = ref(false);
const editingTransaction = ref<Transaction | null>(null);
const viewingTransaction = ref<Transaction | null>(null);

const getLocalizedDescription = (transaction: Transaction) => {
  const locale = document.documentElement.lang || "en";
  return locale === "fa" && transaction.description_persian
    ? transaction.description_persian
    : transaction.description;
};

const viewTransaction = (transaction: Transaction) => {
  viewingTransaction.value = transaction;
  showDetailsModal.value = true;
};

const editTransaction = (transaction: Transaction) => {
  editingTransaction.value = transaction;
  showTransactionModal.value = true;
};

const toggleTransactionLock = async (transaction: Transaction) => {
  try {
    if (transaction.is_locked) {
      await accountingStore.unlockTransaction(transaction.id);
    } else {
      await accountingStore.lockTransaction(transaction.id);
    }
  } catch (error) {
    console.error("Failed to toggle transaction lock:", error);
  }
};

const closeTransactionModal = () => {
  showTransactionModal.value = false;
  editingTransaction.value = null;
};

const handleTransactionSaved = () => {
  closeTransactionModal();
  accountingStore.fetchTransactions();
};

onMounted(() => {
  accountingStore.fetchAccounts();
  accountingStore.fetchTransactions();
});
</script>
