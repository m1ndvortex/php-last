<template>
  <div class="space-y-6">
    <!-- Header with Account Selection -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0"
      >
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t("accounting.general_ledger") }}
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $t("accounting.general_ledger_description") }}
          </p>
        </div>
        <div class="flex space-x-3">
          <button
            @click="showAccountModal = true"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            {{ $t("accounting.create_account") }}
          </button>
        </div>
      </div>
    </div>

    <!-- Account Tree and Ledger -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Account Tree -->
      <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white">
              {{ $t("accounting.chart_of_accounts") }}
            </h4>
            <!-- Account Filters -->
            <div class="mt-4 space-y-3">
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  {{ $t("accounting.account_type") }}
                </label>
                <select
                  v-model="accountingStore.accountFilter.type"
                  @change="accountingStore.fetchAccounts()"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                >
                  <option value="">{{ $t("common.all") }}</option>
                  <option value="asset">{{ $t("accounting.asset") }}</option>
                  <option value="liability">
                    {{ $t("accounting.liability") }}
                  </option>
                  <option value="equity">{{ $t("accounting.equity") }}</option>
                  <option value="revenue">
                    {{ $t("accounting.revenue") }}
                  </option>
                  <option value="expense">
                    {{ $t("accounting.expense") }}
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
                  v-model="accountingStore.accountFilter.search"
                  @input="accountingStore.fetchAccounts()"
                  type="text"
                  :placeholder="$t('accounting.search_accounts')"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                />
              </div>
            </div>
          </div>
          <div class="p-6 max-h-96 overflow-y-auto">
            <div class="space-y-2">
              <div
                v-for="account in accountingStore.filteredAccounts"
                :key="account.id"
                @click="selectAccount(account)"
                :class="[
                  'p-3 rounded-lg cursor-pointer transition-colors',
                  selectedAccount?.id === account.id
                    ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700'
                    : 'hover:bg-gray-50 dark:hover:bg-gray-700',
                ]"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <p
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{ account.code }} - {{ getLocalizedName(account) }}
                    </p>
                    <p
                      class="text-xs text-gray-500 dark:text-gray-400 capitalize"
                    >
                      {{ $t(`accounting.${account.type}`) }}
                    </p>
                  </div>
                  <div class="text-right">
                    <p
                      class="text-sm font-medium text-gray-900 dark:text-white"
                    >
                      {{ formatCurrency(account.current_balance || 0) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ledger Details -->
      <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                  {{
                    selectedAccount
                      ? `${selectedAccount.code} - ${getLocalizedName(selectedAccount)}`
                      : $t("accounting.select_account")
                  }}
                </h4>
                <p
                  v-if="selectedAccount"
                  class="text-sm text-gray-500 dark:text-gray-400"
                >
                  {{ $t("accounting.current_balance") }}:
                  {{ formatCurrency(selectedAccount.current_balance || 0) }}
                </p>
              </div>
              <div v-if="selectedAccount" class="flex space-x-3">
                <!-- Date Range Filter -->
                <div class="flex space-x-2">
                  <input
                    v-model="dateRange.start"
                    @change="loadLedgerEntries"
                    type="date"
                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                  />
                  <input
                    v-model="dateRange.end"
                    @change="loadLedgerEntries"
                    type="date"
                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                  />
                </div>
              </div>
            </div>
          </div>

          <div v-if="selectedAccount" class="p-6">
            <!-- Ledger Entries -->
            <div v-if="ledgerEntries.length > 0" class="overflow-x-auto">
              <table
                class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
              >
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
                      class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      {{ $t("accounting.debit") }}
                    </th>
                    <th
                      class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      {{ $t("accounting.credit") }}
                    </th>
                    <th
                      class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
                    >
                      {{ $t("accounting.balance") }}
                    </th>
                  </tr>
                </thead>
                <tbody
                  class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                >
                  <tr
                    v-for="entry in ledgerEntries"
                    :key="`${entry.date}-${entry.reference}`"
                  >
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                    >
                      {{ formatDate(entry.date) }}
                    </td>
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                    >
                      {{ entry.reference }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                      {{ entry.description }}
                    </td>
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                    >
                      {{ entry.debit > 0 ? formatCurrency(entry.debit) : "-" }}
                    </td>
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                    >
                      {{
                        entry.credit > 0 ? formatCurrency(entry.credit) : "-"
                      }}
                    </td>
                    <td
                      class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white"
                    >
                      {{ formatCurrency(entry.balance) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
              <BookOpenIcon class="mx-auto h-12 w-12 text-gray-400" />
              <h3
                class="mt-2 text-sm font-medium text-gray-900 dark:text-white"
              >
                {{ $t("accounting.no_ledger_entries") }}
              </h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $t("accounting.no_ledger_entries_description") }}
              </p>
            </div>
          </div>

          <!-- No Account Selected -->
          <div v-else class="p-12 text-center">
            <BookOpenIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
              {{ $t("accounting.select_account_to_view") }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ $t("accounting.select_account_description") }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Account Modal -->
    <AccountFormModal
      v-if="showAccountModal"
      :account="editingAccount"
      @close="closeAccountModal"
      @saved="handleAccountSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { PlusIcon, BookOpenIcon } from "@heroicons/vue/24/outline";
import { useAccountingStore } from "@/stores/accounting";
import { useLocale } from "@/composables/useLocale";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { Account } from "@/types/business";
import AccountFormModal from "./AccountFormModal.vue";

const accountingStore = useAccountingStore();
const { formatDate } = useLocale();
const { formatCurrency } = useNumberFormatter();

const selectedAccount = ref<Account | null>(null);
const ledgerEntries = ref<any[]>([]);
const showAccountModal = ref(false);
const editingAccount = ref<Account | null>(null);

const dateRange = ref({
  start: new Date(new Date().getFullYear(), 0, 1).toISOString().split("T")[0], // Start of year
  end: new Date().toISOString().split("T")[0], // Today
});

const getLocalizedName = (account: Account) => {
  const locale = document.documentElement.lang || "en";
  return locale === "fa" && account.name_persian
    ? account.name_persian
    : account.name;
};

const selectAccount = async (account: Account) => {
  selectedAccount.value = account;
  await loadLedgerEntries();
};

const loadLedgerEntries = async () => {
  if (!selectedAccount.value) return;

  try {
    ledgerEntries.value = await accountingStore.getGeneralLedger(
      selectedAccount.value.id,
      dateRange.value.start,
      dateRange.value.end,
    );
  } catch (error) {
    console.error("Failed to load ledger entries:", error);
  }
};

const closeAccountModal = () => {
  showAccountModal.value = false;
  editingAccount.value = null;
};

const handleAccountSaved = () => {
  closeAccountModal();
  accountingStore.fetchAccounts();
};

onMounted(() => {
  accountingStore.fetchAccounts();
});
</script>
