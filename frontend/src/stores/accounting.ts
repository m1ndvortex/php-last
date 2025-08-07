import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'
import type { Account, Transaction } from '@/types/business'

// Additional accounting types
export interface TransactionEntry {
  id?: number
  account_id: number
  debit_amount: number
  credit_amount: number
  description?: string
  description_persian?: string
  metadata?: Record<string, any>
  account?: Account
}

export interface TransactionForm {
  reference_number?: string
  description: string
  description_persian?: string
  transaction_date: string
  type: string
  total_amount: number
  currency: string
  exchange_rate: number
  cost_center_id?: number
  tags?: string[]
  notes?: string
  entries: TransactionEntry[]
}

export interface FinancialReport {
  type: 'trial_balance' | 'balance_sheet' | 'income_statement' | 'cash_flow'
  data: any
  as_of_date?: string
  period?: {
    start_date: string
    end_date: string
  }
  generated_at: string
}

export interface CostCenter {
  id: number
  code: string
  name: string
  name_persian?: string
  description?: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Asset {
  id: number
  asset_number: string
  name: string
  name_persian?: string
  description?: string
  category: string
  purchase_cost: number
  purchase_date: string
  salvage_value: number
  useful_life_years: number
  depreciation_method: 'straight_line' | 'declining_balance' | 'units_of_production'
  accumulated_depreciation: number
  current_value: number
  status: 'active' | 'disposed' | 'under_maintenance'
  disposal_date?: string
  disposal_value?: number
  cost_center_id?: number
  metadata?: Record<string, any>
  created_at: string
  updated_at: string
  cost_center?: CostCenter
}

export interface TaxReport {
  id: number
  report_type: string
  period_start: string
  period_end: string
  total_sales: number
  total_tax: number
  status: 'draft' | 'submitted' | 'approved'
  generated_at: string
  data: any
}

export interface AuditLogEntry {
  id: number
  user_id?: number
  action: string
  auditable_type: string
  auditable_id: number
  old_values?: Record<string, any>
  new_values?: Record<string, any>
  url?: string
  ip_address?: string
  user_agent?: string
  created_at: string
  user?: any
}

export const useAccountingStore = defineStore('accounting', () => {
  // State
  const accounts = ref<Account[]>([])
  const transactions = ref<Transaction[]>([])
  const costCenters = ref<CostCenter[]>([])
  const assets = ref<Asset[]>([])
  const auditLogs = ref<AuditLogEntry[]>([])
  const currentReport = ref<FinancialReport | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Filters
  const accountFilter = ref({
    type: '',
    is_active: true,
    search: ''
  })

  const transactionFilter = ref({
    date_from: '',
    date_to: '',
    account_id: null as number | null,
    type: '',
    is_locked: null as boolean | null,
    search: ''
  })

  const auditLogFilter = ref({
    date_from: '',
    date_to: '',
    action: '',
    auditable_type: '',
    user_id: null as number | null,
    search: ''
  })

  // Computed
  const filteredAccounts = computed(() => {
    return accounts.value.filter(account => {
      const matchesType = !accountFilter.value.type || account.type === accountFilter.value.type
      const matchesActive = account.is_active === accountFilter.value.is_active
      const matchesSearch = !accountFilter.value.search || 
        account.name.toLowerCase().includes(accountFilter.value.search.toLowerCase()) ||
        account.code.toLowerCase().includes(accountFilter.value.search.toLowerCase())
      
      return matchesType && matchesActive && matchesSearch
    })
  })

  const filteredTransactions = computed(() => {
    return transactions.value.filter(transaction => {
      const matchesDateFrom = !transactionFilter.value.date_from || 
        transaction.transaction_date >= transactionFilter.value.date_from
      const matchesDateTo = !transactionFilter.value.date_to || 
        transaction.transaction_date <= transactionFilter.value.date_to
      const matchesType = !transactionFilter.value.type || transaction.type === transactionFilter.value.type
      const matchesLocked = transactionFilter.value.is_locked === null || 
        transaction.is_locked === transactionFilter.value.is_locked
      const matchesSearch = !transactionFilter.value.search || 
        transaction.description.toLowerCase().includes(transactionFilter.value.search.toLowerCase()) ||
        transaction.reference_number.toLowerCase().includes(transactionFilter.value.search.toLowerCase())
      
      return matchesDateFrom && matchesDateTo && matchesType && matchesLocked && matchesSearch
    })
  })

  const accountsByType = computed(() => {
    const grouped: Record<string, Account[]> = {}
    accounts.value.forEach(account => {
      if (!grouped[account.type]) {
        grouped[account.type] = []
      }
      grouped[account.type].push(account)
    })
    return grouped
  })

  // Actions
  const fetchAccounts = async () => {
    try {
      loading.value = true
      const response = await api.get('/accounting/accounts')
      accounts.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch accounts'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createAccount = async (accountData: Partial<Account>) => {
    try {
      loading.value = true
      const response = await api.post('/accounting/accounts', accountData)
      accounts.value.push(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create account'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateAccount = async (id: number, accountData: Partial<Account>) => {
    try {
      loading.value = true
      const response = await api.put(`/accounting/accounts/${id}`, accountData)
      const index = accounts.value.findIndex(a => a.id === id)
      if (index !== -1) {
        accounts.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update account'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteAccount = async (id: number) => {
    try {
      loading.value = true
      await api.delete(`/accounting/accounts/${id}`)
      accounts.value = accounts.value.filter(a => a.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete account'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchTransactions = async () => {
    try {
      loading.value = true
      const params = new URLSearchParams()
      if (transactionFilter.value.date_from) params.append('date_from', transactionFilter.value.date_from)
      if (transactionFilter.value.date_to) params.append('date_to', transactionFilter.value.date_to)
      if (transactionFilter.value.account_id) params.append('account_id', transactionFilter.value.account_id.toString())
      if (transactionFilter.value.type) params.append('type', transactionFilter.value.type)
      if (transactionFilter.value.is_locked !== null) params.append('is_locked', transactionFilter.value.is_locked.toString())
      if (transactionFilter.value.search) params.append('search', transactionFilter.value.search)

      const response = await api.get(`/accounting/transactions?${params.toString()}`)
      transactions.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch transactions'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createTransaction = async (transactionData: TransactionForm) => {
    try {
      loading.value = true
      const response = await api.post('/accounting/transactions', transactionData)
      transactions.value.unshift(response.data.data)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create transaction'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateTransaction = async (id: number, transactionData: TransactionForm) => {
    try {
      loading.value = true
      const response = await api.put(`/accounting/transactions/${id}`, transactionData)
      const index = transactions.value.findIndex(t => t.id === id)
      if (index !== -1) {
        transactions.value[index] = response.data.data
      }
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update transaction'
      throw err
    } finally {
      loading.value = false
    }
  }

  const lockTransaction = async (id: number) => {
    try {
      loading.value = true
      await api.post(`/accounting/transactions/${id}/lock`)
      const transaction = transactions.value.find(t => t.id === id)
      if (transaction) {
        transaction.is_locked = true
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to lock transaction'
      throw err
    } finally {
      loading.value = false
    }
  }

  const unlockTransaction = async (id: number) => {
    try {
      loading.value = true
      await api.post(`/accounting/transactions/${id}/unlock`)
      const transaction = transactions.value.find(t => t.id === id)
      if (transaction) {
        transaction.is_locked = false
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to unlock transaction'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteTransaction = async (id: number) => {
    try {
      loading.value = true
      await api.delete(`/accounting/transactions/${id}`)
      transactions.value = transactions.value.filter(t => t.id !== id)
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete transaction'
      throw err
    } finally {
      loading.value = false
    }
  }

  const generateReport = async (reportType: string, params: any = {}) => {
    try {
      loading.value = true
      const response = await api.post(`/accounting/reports/${reportType}`, params)
      currentReport.value = response.data.data
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to generate report'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchCostCenters = async () => {
    try {
      loading.value = true
      const response = await api.get('/accounting/cost-centers')
      costCenters.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch cost centers'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchAssets = async () => {
    try {
      loading.value = true
      const response = await api.get('/accounting/assets')
      assets.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch assets'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchAuditLogs = async () => {
    try {
      loading.value = true
      const params = new URLSearchParams()
      if (auditLogFilter.value.date_from) params.append('date_from', auditLogFilter.value.date_from)
      if (auditLogFilter.value.date_to) params.append('date_to', auditLogFilter.value.date_to)
      if (auditLogFilter.value.action) params.append('action', auditLogFilter.value.action)
      if (auditLogFilter.value.auditable_type) params.append('auditable_type', auditLogFilter.value.auditable_type)
      if (auditLogFilter.value.user_id) params.append('user_id', auditLogFilter.value.user_id.toString())
      if (auditLogFilter.value.search) params.append('search', auditLogFilter.value.search)

      const response = await api.get(`/accounting/audit-logs?${params.toString()}`)
      auditLogs.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch audit logs'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getGeneralLedger = async (accountId: number, startDate?: string, endDate?: string) => {
    try {
      loading.value = true
      const params = new URLSearchParams()
      if (startDate) params.append('start_date', startDate)
      if (endDate) params.append('end_date', endDate)

      const response = await api.get(`/accounting/accounts/${accountId}/ledger?${params.toString()}`)
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch general ledger'
      throw err
    } finally {
      loading.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    // State
    accounts,
    transactions,
    costCenters,
    assets,
    auditLogs,
    currentReport,
    loading,
    error,
    
    // Filters
    accountFilter,
    transactionFilter,
    auditLogFilter,
    
    // Computed
    filteredAccounts,
    filteredTransactions,
    accountsByType,
    
    // Actions
    fetchAccounts,
    createAccount,
    updateAccount,
    deleteAccount,
    fetchTransactions,
    createTransaction,
    updateTransaction,
    lockTransaction,
    unlockTransaction,
    deleteTransaction,
    generateReport,
    fetchCostCenters,
    fetchAssets,
    fetchAuditLogs,
    getGeneralLedger,
    clearError
  }
})