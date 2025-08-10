<template>
  <div
    class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
  >
    <div
      class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800"
    >
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("accounting.transaction_details") }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div v-if="transaction" class="space-y-6">
        <!-- Transaction Header -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("accounting.reference_number") }}
              </label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                {{ transaction.reference_number }}
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("common.date") }}
              </label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ formatDate(transaction.transaction_date) }}
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("common.type") }}
              </label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white capitalize">
                {{ $t(`accounting.${transaction.type}`) }}
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("accounting.total_amount") }}
              </label>
              <p
                class="mt-1 text-sm text-gray-900 dark:text-white font-semibold"
              >
                {{ formatCurrency(transaction.total_amount || 0) }}
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                  {{ transaction.currency || "USD" }}
                </span>
              </p>
            </div>
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("common.status") }}
              </label>
              <span
                :class="[
                  'inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1',
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
            </div>
            <div
              v-if="
                transaction.exchange_rate && transaction.exchange_rate !== 1
              "
            >
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                {{ $t("accounting.exchange_rate") }}
              </label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ transaction.exchange_rate }}
              </p>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("common.description") }}
          </label>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-sm text-gray-900 dark:text-white">
              {{ getLocalizedDescription(transaction) }}
            </p>
            <p
              v-if="
                transaction.description_persian &&
                transaction.description_persian !== transaction.description
              "
              class="text-sm text-gray-600 dark:text-gray-400 mt-2 italic"
              dir="rtl"
            >
              {{ transaction.description_persian }}
            </p>
          </div>
        </div>

        <!-- Transaction Entries -->
        <div>
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4"
          >
            {{ $t("accounting.transaction_entries") }}
          </label>

          <div v-if="transactionEntries.length > 0" class="overflow-x-auto">
            <table
              class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
            >
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
                </tr>
              </thead>
              <tbody
                class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
              >
                <tr v-for="entry in transactionEntries" :key="entry.id">
                  <td
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                  >
                    <div>
                      <p class="font-medium">{{ entry.account?.code }}</p>
                      <p class="text-gray-500 dark:text-gray-400">
                        {{ getLocalizedAccountName(entry.account) }}
                      </p>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                    {{ entry.description || "-" }}
                  </td>
                  <td
                    class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                  >
                    {{
                      entry.debit_amount > 0
                        ? formatCurrency(entry.debit_amount)
                        : "-"
                    }}
                  </td>
                  <td
                    class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white"
                  >
                    {{
                      entry.credit_amount > 0
                        ? formatCurrency(entry.credit_amount)
                        : "-"
                    }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <td
                    colspan="2"
                    class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white"
                  >
                    {{ $t("common.total") }}
                  </td>
                  <td
                    class="px-6 py-3 text-sm font-medium text-right text-gray-900 dark:text-white"
                  >
                    {{ formatCurrency(totalDebits) }}
                  </td>
                  <td
                    class="px-6 py-3 text-sm font-medium text-right text-gray-900 dark:text-white"
                  >
                    {{ formatCurrency(totalCredits) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <div
            v-else
            class="text-center py-8 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <p class="text-gray-500 dark:text-gray-400">
              {{ $t("accounting.no_entries_found") }}
            </p>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="transaction.notes">
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("common.notes") }}
          </label>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p
              class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap"
            >
              {{ transaction.notes }}
            </p>
          </div>
        </div>

        <!-- Metadata -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            {{ $t("accounting.transaction_metadata") }}
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-gray-500 dark:text-gray-400"
                >{{ $t("common.created_at") }}:</span
              >
              <span class="ml-2 text-gray-900 dark:text-white">{{
                formatDate(transaction.created_at)
              }}</span>
            </div>
            <div>
              <span class="text-gray-500 dark:text-gray-400"
                >{{ $t("common.updated_at") }}:</span
              >
              <span class="ml-2 text-gray-900 dark:text-white">{{
                formatDate(transaction.updated_at)
              }}</span>
            </div>
            <div v-if="transaction.created_by">
              <span class="text-gray-500 dark:text-gray-400"
                >{{ $t("accounting.created_by") }}:</span
              >
              <span class="ml-2 text-gray-900 dark:text-white">{{
                transaction.creator?.name || "System"
              }}</span>
            </div>
            <div v-if="transaction.approved_by">
              <span class="text-gray-500 dark:text-gray-400"
                >{{ $t("accounting.approved_by") }}:</span
              >
              <span class="ml-2 text-gray-900 dark:text-white">{{
                transaction.approver?.name
              }}</span>
            </div>
          </div>
        </div>

        <!-- Tags -->
        <div v-if="transaction.tags && transaction.tags.length > 0">
          <label
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
          >
            {{ $t("common.tags") }}
          </label>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="tag in transaction.tags"
              :key="tag"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
            >
              {{ tag }}
            </span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div
        class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700 mt-6"
      >
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
        >
          {{ $t("common.close") }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { XMarkIcon } from "@heroicons/vue/24/outline";
import { useLocale } from "@/composables/useLocale";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import type { Transaction, Account } from "@/types/business";
import type { TransactionEntry } from "@/stores/accounting";

interface Props {
  transaction: Transaction | null;
}

const props = defineProps<Props>();
defineEmits<{
  close: [];
}>();

const { formatDate } = useLocale();
const { formatCurrency } = useNumberFormatter();

const transactionEntries = ref<TransactionEntry[]>([]);

const totalDebits = computed(() => {
  return transactionEntries.value.reduce(
    (sum, entry) => sum + (entry.debit_amount || 0),
    0,
  );
});

const totalCredits = computed(() => {
  return transactionEntries.value.reduce(
    (sum, entry) => sum + (entry.credit_amount || 0),
    0,
  );
});

const getLocalizedDescription = (transaction: Transaction | null) => {
  if (!transaction) return "";
  const locale = document.documentElement.lang || "en";
  return locale === "fa" && transaction.description_persian
    ? transaction.description_persian
    : transaction.description;
};

const getLocalizedAccountName = (account?: Account) => {
  if (!account) return "";
  const locale = document.documentElement.lang || "en";
  return locale === "fa" && account.name_persian
    ? account.name_persian
    : account.name;
};

// In a real implementation, this would fetch the transaction entries from the API
onMounted(() => {
  if (!props.transaction) return;

  // Mock transaction entries for display
  transactionEntries.value = [
    {
      id: 1,
      account_id: 1,
      debit_amount: (props.transaction.total_amount || 0) / 2,
      credit_amount: 0,
      description: "Sample debit entry",
      account: {
        id: 1,
        code: "1001",
        name: "Cash",
        name_persian: "نقد",
        type: "asset" as const,
        balance: 0,
        is_active: true,
        created_at: "",
        updated_at: "",
      },
    },
    {
      id: 2,
      account_id: 2,
      debit_amount: 0,
      credit_amount: (props.transaction.total_amount || 0) / 2,
      description: "Sample credit entry",
      account: {
        id: 2,
        code: "4001",
        name: "Sales Revenue",
        name_persian: "درآمد فروش",
        type: "revenue" as const,
        balance: 0,
        is_active: true,
        created_at: "",
        updated_at: "",
      },
    },
  ];
});
</script>
