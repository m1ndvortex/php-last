<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ $t("pages.accounting") }}
        </h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
          {{ $t("accounting.description") }}
        </p>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          <component :is="tab.icon" class="w-5 h-5 inline-block mr-2" />
          {{ $t(tab.label) }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
      <!-- General Ledger -->
      <GeneralLedgerView v-if="activeTab === 'ledger'" />
      
      <!-- Transactions -->
      <TransactionManagement v-else-if="activeTab === 'transactions'" />
      
      <!-- Financial Reports -->
      <FinancialReports v-else-if="activeTab === 'reports'" />
      
      <!-- Multi-Currency -->
      <CurrencyManagement v-else-if="activeTab === 'currency'" />
      
      <!-- Cost Centers & Assets -->
      <CostCenterAssetManagement v-else-if="activeTab === 'assets'" />
      
      <!-- Tax Reports -->
      <TaxReports v-else-if="activeTab === 'tax'" />
      
      <!-- Audit Logs -->
      <AuditLogViewer v-else-if="activeTab === 'audit'" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { 
  BookOpenIcon, 
  CurrencyDollarIcon, 
  DocumentChartBarIcon,
  GlobeAltIcon,
  BuildingOfficeIcon,
  DocumentTextIcon,
  ShieldCheckIcon
} from '@heroicons/vue/24/outline'
import GeneralLedgerView from '@/components/accounting/GeneralLedgerView.vue'
import TransactionManagement from '@/components/accounting/TransactionManagement.vue'
import FinancialReports from '@/components/accounting/FinancialReports.vue'
import CurrencyManagement from '@/components/accounting/CurrencyManagement.vue'
import CostCenterAssetManagement from '@/components/accounting/CostCenterAssetManagement.vue'
import TaxReports from '@/components/accounting/TaxReports.vue'
import AuditLogViewer from '@/components/accounting/AuditLogViewer.vue'

const activeTab = ref('ledger')

const tabs = [
  {
    id: 'ledger',
    label: 'accounting.general_ledger',
    icon: BookOpenIcon
  },
  {
    id: 'transactions',
    label: 'accounting.transactions',
    icon: CurrencyDollarIcon
  },
  {
    id: 'reports',
    label: 'accounting.financial_reports',
    icon: DocumentChartBarIcon
  },
  {
    id: 'currency',
    label: 'accounting.multi_currency',
    icon: GlobeAltIcon
  },
  {
    id: 'assets',
    label: 'accounting.cost_centers_assets',
    icon: BuildingOfficeIcon
  },
  {
    id: 'tax',
    label: 'accounting.tax_reports',
    icon: DocumentTextIcon
  },
  {
    id: 'audit',
    label: 'accounting.audit_logs',
    icon: ShieldCheckIcon
  }
]
</script>
