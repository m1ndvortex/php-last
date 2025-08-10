<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0"
      >
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $t("accounting.multi_currency") }}
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $t("accounting.multi_currency_description") }}
          </p>
        </div>
        <div class="flex space-x-3">
          <button
            @click="refreshExchangeRates"
            :disabled="loading"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50"
          >
            <ArrowPathIcon
              class="w-4 h-4 mr-2"
              :class="{ 'animate-spin': loading }"
            />
            {{ $t("accounting.refresh_rates") }}
          </button>
        </div>
      </div>
    </div>

    <!-- Exchange Rates -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("accounting.exchange_rates") }}
        </h4>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("accounting.last_updated") }}: {{ formatDate(lastUpdated) }}
        </p>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.currency") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.currency_name") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.exchange_rate") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.change_24h") }}
              </th>
              <th
                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("common.actions") }}
              </th>
            </tr>
          </thead>
          <tbody
            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
          >
            <tr v-for="currency in currencies" :key="currency.code">
              <td
                class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"
              >
                <div class="flex items-center">
                  <span class="font-mono">{{ currency.code }}</span>
                  <span
                    v-if="currency.code === baseCurrency"
                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                  >
                    {{ $t("accounting.base") }}
                  </span>
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                {{ currency.name }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white font-mono"
              >
                {{
                  currency.code === baseCurrency
                    ? "1.000000"
                    : currency.rate.toFixed(6)
                }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                <span
                  :class="[
                    'font-mono',
                    currency.change >= 0
                      ? 'text-green-600 dark:text-green-400'
                      : 'text-red-600 dark:text-red-400',
                  ]"
                >
                  {{ currency.change >= 0 ? "+" : ""
                  }}{{ currency.change.toFixed(4) }}%
                </span>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium"
              >
                <button
                  @click="editCurrency(currency)"
                  class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                  {{ $t("common.edit") }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Currency Conversion Calculator -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
        {{ $t("accounting.currency_converter") }}
      </h4>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("accounting.amount") }}
          </label>
          <input
            v-model.number="converter.amount"
            @input="convertCurrency"
            type="number"
            step="0.01"
            min="0"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          />
        </div>
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("accounting.from_currency") }}
          </label>
          <select
            v-model="converter.fromCurrency"
            @change="convertCurrency"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          >
            <option
              v-for="currency in currencies"
              :key="currency.code"
              :value="currency.code"
            >
              {{ currency.code }} - {{ currency.name }}
            </option>
          </select>
        </div>
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("accounting.to_currency") }}
          </label>
          <select
            v-model="converter.toCurrency"
            @change="convertCurrency"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
          >
            <option
              v-for="currency in currencies"
              :key="currency.code"
              :value="currency.code"
            >
              {{ currency.code }} - {{ currency.name }}
            </option>
          </select>
        </div>
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ $t("accounting.converted_amount") }}
          </label>
          <div
            class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-sm text-gray-900 dark:bg-gray-600 dark:border-gray-500 dark:text-white font-mono"
          >
            {{ formatCurrency(converter.result, converter.toCurrency) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Multi-Currency Account Balances -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("accounting.multi_currency_balances") }}
        </h4>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("accounting.account_balances_by_currency") }}
        </p>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.account") }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.currency") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.balance") }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              >
                {{ $t("accounting.base_currency_equivalent") }}
              </th>
            </tr>
          </thead>
          <tbody
            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
          >
            <tr
              v-for="balance in multiCurrencyBalances"
              :key="`${balance.account_code}-${balance.currency}`"
            >
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
              >
                <div>
                  <p class="font-medium">{{ balance.account_code }}</p>
                  <p class="text-gray-500 dark:text-gray-400">
                    {{ balance.account_name }}
                  </p>
                </div>
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono"
              >
                {{ balance.currency }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white font-mono"
              >
                {{ formatCurrency(balance.balance, balance.currency) }}
              </td>
              <td
                class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white font-mono"
              >
                {{ formatCurrency(balance.base_equivalent, baseCurrency) }}
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <td
                colspan="3"
                class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white"
              >
                {{ $t("accounting.total_in_base_currency") }}
              </td>
              <td
                class="px-6 py-3 text-sm font-bold text-right text-gray-900 dark:text-white font-mono"
              >
                {{ formatCurrency(totalBaseEquivalent, baseCurrency) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Currency Edit Modal -->
    <CurrencyEditModal
      v-if="showCurrencyModal"
      :currency="editingCurrency"
      @close="closeCurrencyModal"
      @saved="handleCurrencySaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { ArrowPathIcon } from "@heroicons/vue/24/outline";
import { useLocale } from "@/composables/useLocale";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import CurrencyEditModal from "./CurrencyEditModal.vue";

interface Currency {
  code: string;
  name: string;
  rate: number;
  change: number;
  is_active: boolean;
}

interface MultiCurrencyBalance {
  account_code: string;
  account_name: string;
  currency: string;
  balance: number;
  base_equivalent: number;
}

const { formatDate } = useLocale();
const { formatCurrency } = useNumberFormatter();

const loading = ref(false);
const showCurrencyModal = ref(false);
const editingCurrency = ref<Currency | null>(null);
const lastUpdated = ref(new Date().toISOString());
const baseCurrency = ref("USD");

// Mock data - in real implementation, this would come from the API
const currencies = ref<Currency[]>([
  { code: "USD", name: "US Dollar", rate: 1.0, change: 0.0, is_active: true },
  { code: "EUR", name: "Euro", rate: 0.85, change: -0.0125, is_active: true },
  {
    code: "IRR",
    name: "Iranian Rial",
    rate: 42000.0,
    change: 0.005,
    is_active: true,
  },
  {
    code: "AED",
    name: "UAE Dirham",
    rate: 3.673,
    change: 0.0,
    is_active: true,
  },
]);

const multiCurrencyBalances = ref<MultiCurrencyBalance[]>([
  {
    account_code: "1001",
    account_name: "Cash - USD",
    currency: "USD",
    balance: 10000,
    base_equivalent: 10000,
  },
  {
    account_code: "1002",
    account_name: "Cash - EUR",
    currency: "EUR",
    balance: 5000,
    base_equivalent: 5882.35,
  },
  {
    account_code: "1003",
    account_name: "Cash - IRR",
    currency: "IRR",
    balance: 50000000,
    base_equivalent: 1190.48,
  },
  {
    account_code: "1004",
    account_name: "Cash - AED",
    currency: "AED",
    balance: 15000,
    base_equivalent: 4084.97,
  },
]);

const converter = ref({
  amount: 1000,
  fromCurrency: "USD",
  toCurrency: "EUR",
  result: 0,
});

const totalBaseEquivalent = computed(() => {
  return multiCurrencyBalances.value.reduce(
    (sum, balance) => sum + balance.base_equivalent,
    0,
  );
});

const refreshExchangeRates = async () => {
  loading.value = true;
  try {
    // In real implementation, this would call an API to get latest rates
    await new Promise((resolve) => setTimeout(resolve, 1000));
    lastUpdated.value = new Date().toISOString();

    // Mock rate updates
    currencies.value.forEach((currency) => {
      if (currency.code !== baseCurrency.value) {
        const changePercent = (Math.random() - 0.5) * 0.02; // ±1% change
        currency.rate *= 1 + changePercent;
        currency.change = changePercent * 100;
      }
    });

    // Recalculate multi-currency balances
    updateMultiCurrencyBalances();
  } catch (error) {
    console.error("Failed to refresh exchange rates:", error);
  } finally {
    loading.value = false;
  }
};

const convertCurrency = () => {
  const fromRate =
    currencies.value.find((c) => c.code === converter.value.fromCurrency)
      ?.rate || 1;
  const toRate =
    currencies.value.find((c) => c.code === converter.value.toCurrency)?.rate ||
    1;

  // Convert to base currency first, then to target currency
  const baseAmount = converter.value.amount / fromRate;
  converter.value.result = baseAmount * toRate;
};

const updateMultiCurrencyBalances = () => {
  multiCurrencyBalances.value.forEach((balance) => {
    const rate =
      currencies.value.find((c) => c.code === balance.currency)?.rate || 1;
    balance.base_equivalent = balance.balance / rate;
  });
};

const editCurrency = (currency: Currency) => {
  editingCurrency.value = currency;
  showCurrencyModal.value = true;
};

const closeCurrencyModal = () => {
  showCurrencyModal.value = false;
  editingCurrency.value = null;
};

const handleCurrencySaved = () => {
  closeCurrencyModal();
  updateMultiCurrencyBalances();
};

onMounted(() => {
  convertCurrency();
});
</script>
