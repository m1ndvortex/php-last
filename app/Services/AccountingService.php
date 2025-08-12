<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AccountingService
{
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                'reference_number' => $data['reference_number'] ?? Transaction::generateReferenceNumber(),
                'description' => $data['description'],
                'description_persian' => $data['description_persian'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'type' => $data['type'],
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'total_amount' => $data['total_amount'],
                'currency' => $data['currency'] ?? 'USD',
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'tags' => $data['tags'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create transaction entries
            foreach ($data['entries'] as $entryData) {
                $transaction->entries()->create([
                    'account_id' => $entryData['account_id'],
                    'debit_amount' => $entryData['debit_amount'] ?? 0,
                    'credit_amount' => $entryData['credit_amount'] ?? 0,
                    'description' => $entryData['description'] ?? null,
                    'description_persian' => $entryData['description_persian'] ?? null,
                    'metadata' => $entryData['metadata'] ?? null,
                ]);
            }

            // Validate transaction is balanced
            if (!$transaction->isBalanced()) {
                throw new \Exception('Transaction is not balanced');
            }

            // Update account balances
            $this->updateAccountBalances($transaction);

            // Log audit trail
            AuditLog::logActivity($transaction, 'created');

            return $transaction;
        });
    }

    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        if ($transaction->is_locked) {
            throw new \Exception('Cannot update locked transaction');
        }

        return DB::transaction(function () use ($transaction, $data) {
            $oldValues = $transaction->toArray();

            // Update transaction
            $transaction->update([
                'description' => $data['description'],
                'description_persian' => $data['description_persian'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'total_amount' => $data['total_amount'],
                'currency' => $data['currency'] ?? 'USD',
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'tags' => $data['tags'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Delete existing entries
            $transaction->entries()->delete();

            // Create new entries
            foreach ($data['entries'] as $entryData) {
                $transaction->entries()->create([
                    'account_id' => $entryData['account_id'],
                    'debit_amount' => $entryData['debit_amount'] ?? 0,
                    'credit_amount' => $entryData['credit_amount'] ?? 0,
                    'description' => $entryData['description'] ?? null,
                    'description_persian' => $entryData['description_persian'] ?? null,
                    'metadata' => $entryData['metadata'] ?? null,
                ]);
            }

            // Validate transaction is balanced
            if (!$transaction->isBalanced()) {
                throw new \Exception('Transaction is not balanced');
            }

            // Update account balances
            $this->updateAccountBalances($transaction);

            // Log audit trail
            AuditLog::logActivity($transaction, 'updated', $oldValues, $transaction->toArray());

            return $transaction->fresh();
        });
    }

    public function lockTransaction(Transaction $transaction): bool
    {
        if ($transaction->is_locked) {
            return false;
        }

        $transaction->lock();
        AuditLog::logActivity($transaction, 'locked');

        return true;
    }

    public function unlockTransaction(Transaction $transaction): bool
    {
        if (!$transaction->is_locked) {
            return false;
        }

        $transaction->unlock();
        AuditLog::logActivity($transaction, 'unlocked');

        return true;
    }

    public function deleteTransaction(Transaction $transaction): bool
    {
        if ($transaction->is_locked) {
            throw new \Exception('Cannot delete locked transaction');
        }

        return DB::transaction(function () use ($transaction) {
            AuditLog::logActivity($transaction, 'deleted', $transaction->toArray());
            
            $transaction->entries()->delete();
            $transaction->delete();

            return true;
        });
    }

    private function updateAccountBalances(Transaction $transaction): void
    {
        $accountIds = $transaction->entries->pluck('account_id')->unique();
        
        foreach ($accountIds as $accountId) {
            $account = Account::find($accountId);
            $account->updateBalance();
        }
    }

    public function getAccountBalance(Account $account, ?Carbon $asOfDate = null): float
    {
        $query = $account->transactionEntries()
            ->whereHas('transaction', function ($q) use ($asOfDate) {
                if ($asOfDate) {
                    $q->where('transaction_date', '<=', $asOfDate);
                }
            });

        $totalDebits = $query->sum('debit_amount');
        $totalCredits = $query->sum('credit_amount');

        return match ($account->type) {
            'asset', 'expense' => $account->opening_balance + $totalDebits - $totalCredits,
            'liability', 'equity', 'revenue' => $account->opening_balance + $totalCredits - $totalDebits,
            default => $account->opening_balance
        };
    }

    public function getTrialBalance(?Carbon $asOfDate = null): Collection
    {
        $asOfDate = $asOfDate ?? now();
        
        return Account::active()
            ->with(['transactionEntries' => function ($query) use ($asOfDate) {
                $query->whereHas('transaction', function ($q) use ($asOfDate) {
                    $q->where('transaction_date', '<=', $asOfDate);
                });
            }])
            ->get()
            ->map(function ($account) {
                $balance = $this->getAccountBalance($account);
                
                return [
                    'account_code' => $account->code,
                    'account_name' => $account->localized_name,
                    'account_type' => $account->type,
                    'debit_balance' => $account->isDebitAccount() && $balance > 0 ? $balance : 0,
                    'credit_balance' => $account->isCreditAccount() && $balance > 0 ? $balance : 0,
                    'balance' => $balance,
                ];
            })
            ->filter(function ($item) {
                return $item['balance'] != 0;
            });
    }

    public function getGeneralLedger(Account $account, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? now()->startOfYear();
        $endDate = $endDate ?? now();

        $entries = $account->transactionEntries()
            ->with(['transaction'])
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transaction_date', [$startDate, $endDate])
                    ->orderBy('transaction_date')
                    ->orderBy('id');
            })
            ->get();

        $runningBalance = $this->getAccountBalance($account, $startDate->copy()->subDay());
        
        return $entries->map(function ($entry) use (&$runningBalance, $account) {
            if ($account->isDebitAccount()) {
                $runningBalance += $entry->debit_amount - $entry->credit_amount;
            } else {
                $runningBalance += $entry->credit_amount - $entry->debit_amount;
            }

            return [
                'date' => $entry->transaction->transaction_date,
                'reference' => $entry->transaction->reference_number,
                'description' => $entry->transaction->localized_description,
                'debit' => $entry->debit_amount,
                'credit' => $entry->credit_amount,
                'balance' => $runningBalance,
            ];
        });
    }

    /**
     * Create comprehensive chart of accounts with sub-accounts
     */
    public function createChartOfAccounts(): void
    {
        $accounts = [
            // Assets
            ['code' => '1000', 'name' => 'Current Assets', 'name_persian' => 'دارایی‌های جاری', 'type' => 'asset', 'parent_code' => null],
            ['code' => '1100', 'name' => 'Cash and Cash Equivalents', 'name_persian' => 'نقد و معادل نقد', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '1110', 'name' => 'Petty Cash', 'name_persian' => 'تنخواه', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1120', 'name' => 'Bank Account - Main', 'name_persian' => 'حساب بانکی - اصلی', 'type' => 'asset', 'parent_code' => '1100'],
            ['code' => '1130', 'name' => 'Bank Account - Savings', 'name_persian' => 'حساب بانکی - پس‌انداز', 'type' => 'asset', 'parent_code' => '1100'],
            
            ['code' => '1200', 'name' => 'Accounts Receivable', 'name_persian' => 'حساب‌های دریافتنی', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '1210', 'name' => 'Trade Receivables', 'name_persian' => 'دریافتنی تجاری', 'type' => 'asset', 'parent_code' => '1200'],
            ['code' => '1220', 'name' => 'Other Receivables', 'name_persian' => 'سایر دریافتنی‌ها', 'type' => 'asset', 'parent_code' => '1200'],
            ['code' => '1230', 'name' => 'Allowance for Doubtful Accounts', 'name_persian' => 'ذخیره مطالبات مشکوک‌الوصول', 'type' => 'asset', 'parent_code' => '1200'],
            
            ['code' => '1300', 'name' => 'Inventory', 'name_persian' => 'موجودی کالا', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '1310', 'name' => 'Raw Materials - Gold', 'name_persian' => 'مواد اولیه - طلا', 'type' => 'asset', 'parent_code' => '1300'],
            ['code' => '1320', 'name' => 'Raw Materials - Silver', 'name_persian' => 'مواد اولیه - نقره', 'type' => 'asset', 'parent_code' => '1300'],
            ['code' => '1330', 'name' => 'Raw Materials - Gems', 'name_persian' => 'مواد اولیه - سنگ‌های قیمتی', 'type' => 'asset', 'parent_code' => '1300'],
            ['code' => '1340', 'name' => 'Work in Progress', 'name_persian' => 'کالای در جریان ساخت', 'type' => 'asset', 'parent_code' => '1300'],
            ['code' => '1350', 'name' => 'Finished Goods', 'name_persian' => 'کالای ساخته شده', 'type' => 'asset', 'parent_code' => '1300'],
            
            ['code' => '1400', 'name' => 'Prepaid Expenses', 'name_persian' => 'هزینه‌های پیش‌پرداخت', 'type' => 'asset', 'parent_code' => '1000'],
            ['code' => '1410', 'name' => 'Prepaid Insurance', 'name_persian' => 'بیمه پیش‌پرداخت', 'type' => 'asset', 'parent_code' => '1400'],
            ['code' => '1420', 'name' => 'Prepaid Rent', 'name_persian' => 'اجاره پیش‌پرداخت', 'type' => 'asset', 'parent_code' => '1400'],
            
            // Fixed Assets
            ['code' => '1500', 'name' => 'Fixed Assets', 'name_persian' => 'دارایی‌های ثابت', 'type' => 'asset', 'parent_code' => null],
            ['code' => '1510', 'name' => 'Equipment', 'name_persian' => 'تجهیزات', 'type' => 'asset', 'parent_code' => '1500'],
            ['code' => '1511', 'name' => 'Jewelry Making Equipment', 'name_persian' => 'تجهیزات جواهرسازی', 'type' => 'asset', 'parent_code' => '1510'],
            ['code' => '1512', 'name' => 'Office Equipment', 'name_persian' => 'تجهیزات اداری', 'type' => 'asset', 'parent_code' => '1510'],
            ['code' => '1520', 'name' => 'Accumulated Depreciation - Equipment', 'name_persian' => 'استهلاک انباشته - تجهیزات', 'type' => 'asset', 'parent_code' => '1500'],
            ['code' => '1530', 'name' => 'Furniture and Fixtures', 'name_persian' => 'اثاثه و تجهیزات', 'type' => 'asset', 'parent_code' => '1500'],
            ['code' => '1540', 'name' => 'Accumulated Depreciation - Furniture', 'name_persian' => 'استهلاک انباشته - اثاثه', 'type' => 'asset', 'parent_code' => '1500'],
            ['code' => '1550', 'name' => 'Building', 'name_persian' => 'ساختمان', 'type' => 'asset', 'parent_code' => '1500'],
            ['code' => '1560', 'name' => 'Accumulated Depreciation - Building', 'name_persian' => 'استهلاک انباشته - ساختمان', 'type' => 'asset', 'parent_code' => '1500'],
            
            // Liabilities
            ['code' => '2000', 'name' => 'Current Liabilities', 'name_persian' => 'بدهی‌های جاری', 'type' => 'liability', 'parent_code' => null],
            ['code' => '2100', 'name' => 'Accounts Payable', 'name_persian' => 'حساب‌های پرداختنی', 'type' => 'liability', 'parent_code' => '2000'],
            ['code' => '2110', 'name' => 'Trade Payables', 'name_persian' => 'پرداختنی تجاری', 'type' => 'liability', 'parent_code' => '2100'],
            ['code' => '2120', 'name' => 'Other Payables', 'name_persian' => 'سایر پرداختنی‌ها', 'type' => 'liability', 'parent_code' => '2100'],
            
            ['code' => '2200', 'name' => 'Accrued Expenses', 'name_persian' => 'هزینه‌های تعهدی', 'type' => 'liability', 'parent_code' => '2000'],
            ['code' => '2210', 'name' => 'Accrued Wages', 'name_persian' => 'دستمزد تعهدی', 'type' => 'liability', 'parent_code' => '2200'],
            ['code' => '2220', 'name' => 'Accrued Interest', 'name_persian' => 'سود تعهدی', 'type' => 'liability', 'parent_code' => '2200'],
            
            ['code' => '2300', 'name' => 'Tax Liabilities', 'name_persian' => 'بدهی‌های مالیاتی', 'type' => 'liability', 'parent_code' => '2000'],
            ['code' => '2310', 'name' => 'Sales Tax Payable', 'name_persian' => 'مالیات فروش پرداختنی', 'type' => 'liability', 'parent_code' => '2300'],
            ['code' => '2320', 'name' => 'Income Tax Payable', 'name_persian' => 'مالیات درآمد پرداختنی', 'type' => 'liability', 'parent_code' => '2300'],
            ['code' => '2330', 'name' => 'VAT Payable', 'name_persian' => 'مالیات بر ارزش افزوده پرداختنی', 'type' => 'liability', 'parent_code' => '2300'],
            
            // Long-term Liabilities
            ['code' => '2500', 'name' => 'Long-term Liabilities', 'name_persian' => 'بدهی‌های بلندمدت', 'type' => 'liability', 'parent_code' => null],
            ['code' => '2510', 'name' => 'Long-term Loans', 'name_persian' => 'وام‌های بلندمدت', 'type' => 'liability', 'parent_code' => '2500'],
            ['code' => '2520', 'name' => 'Mortgage Payable', 'name_persian' => 'رهن پرداختنی', 'type' => 'liability', 'parent_code' => '2500'],
            
            // Equity
            ['code' => '3000', 'name' => 'Owner\'s Equity', 'name_persian' => 'حقوق صاحبان سهام', 'type' => 'equity', 'parent_code' => null],
            ['code' => '3100', 'name' => 'Capital', 'name_persian' => 'سرمایه', 'type' => 'equity', 'parent_code' => '3000'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'name_persian' => 'سود انباشته', 'type' => 'equity', 'parent_code' => '3000'],
            ['code' => '3300', 'name' => 'Current Year Earnings', 'name_persian' => 'سود سال جاری', 'type' => 'equity', 'parent_code' => '3000'],
            ['code' => '3400', 'name' => 'Owner Drawings', 'name_persian' => 'برداشت مالک', 'type' => 'equity', 'parent_code' => '3000'],
            
            // Revenue
            ['code' => '4000', 'name' => 'Revenue', 'name_persian' => 'درآمد', 'type' => 'revenue', 'parent_code' => null],
            ['code' => '4100', 'name' => 'Sales Revenue', 'name_persian' => 'درآمد فروش', 'type' => 'revenue', 'parent_code' => '4000'],
            ['code' => '4110', 'name' => 'Gold Jewelry Sales', 'name_persian' => 'فروش جواهرات طلا', 'type' => 'revenue', 'parent_code' => '4100'],
            ['code' => '4120', 'name' => 'Silver Jewelry Sales', 'name_persian' => 'فروش جواهرات نقره', 'type' => 'revenue', 'parent_code' => '4100'],
            ['code' => '4130', 'name' => 'Custom Design Sales', 'name_persian' => 'فروش طراحی سفارشی', 'type' => 'revenue', 'parent_code' => '4100'],
            ['code' => '4140', 'name' => 'Repair Services', 'name_persian' => 'خدمات تعمیر', 'type' => 'revenue', 'parent_code' => '4100'],
            
            ['code' => '4200', 'name' => 'Other Revenue', 'name_persian' => 'سایر درآمدها', 'type' => 'revenue', 'parent_code' => '4000'],
            ['code' => '4210', 'name' => 'Interest Income', 'name_persian' => 'درآمد سود', 'type' => 'revenue', 'parent_code' => '4200'],
            ['code' => '4220', 'name' => 'Rental Income', 'name_persian' => 'درآمد اجاره', 'type' => 'revenue', 'parent_code' => '4200'],
            
            // Cost of Goods Sold
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'name_persian' => 'بهای تمام شده کالای فروخته شده', 'type' => 'expense', 'parent_code' => null],
            ['code' => '5100', 'name' => 'Material Costs', 'name_persian' => 'هزینه مواد', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5110', 'name' => 'Gold Costs', 'name_persian' => 'هزینه طلا', 'type' => 'expense', 'parent_code' => '5100'],
            ['code' => '5120', 'name' => 'Silver Costs', 'name_persian' => 'هزینه نقره', 'type' => 'expense', 'parent_code' => '5100'],
            ['code' => '5130', 'name' => 'Gem Costs', 'name_persian' => 'هزینه سنگ‌های قیمتی', 'type' => 'expense', 'parent_code' => '5100'],
            ['code' => '5200', 'name' => 'Direct Labor', 'name_persian' => 'دستمزد مستقیم', 'type' => 'expense', 'parent_code' => '5000'],
            ['code' => '5300', 'name' => 'Manufacturing Overhead', 'name_persian' => 'سربار تولید', 'type' => 'expense', 'parent_code' => '5000'],
            
            // Operating Expenses
            ['code' => '6000', 'name' => 'Operating Expenses', 'name_persian' => 'هزینه‌های عملیاتی', 'type' => 'expense', 'parent_code' => null],
            ['code' => '6100', 'name' => 'Selling Expenses', 'name_persian' => 'هزینه‌های فروش', 'type' => 'expense', 'parent_code' => '6000'],
            ['code' => '6110', 'name' => 'Advertising', 'name_persian' => 'تبلیغات', 'type' => 'expense', 'parent_code' => '6100'],
            ['code' => '6120', 'name' => 'Sales Commissions', 'name_persian' => 'کمیسیون فروش', 'type' => 'expense', 'parent_code' => '6100'],
            
            ['code' => '6200', 'name' => 'Administrative Expenses', 'name_persian' => 'هزینه‌های اداری', 'type' => 'expense', 'parent_code' => '6000'],
            ['code' => '6210', 'name' => 'Office Supplies', 'name_persian' => 'لوازم اداری', 'type' => 'expense', 'parent_code' => '6200'],
            ['code' => '6220', 'name' => 'Utilities', 'name_persian' => 'آب و برق و گاز', 'type' => 'expense', 'parent_code' => '6200'],
            ['code' => '6230', 'name' => 'Insurance', 'name_persian' => 'بیمه', 'type' => 'expense', 'parent_code' => '6200'],
            ['code' => '6240', 'name' => 'Professional Fees', 'name_persian' => 'حق‌الزحمه حرفه‌ای', 'type' => 'expense', 'parent_code' => '6200'],
            ['code' => '6250', 'name' => 'Depreciation Expense', 'name_persian' => 'هزینه استهلاک', 'type' => 'expense', 'parent_code' => '6200'],
            
            ['code' => '6300', 'name' => 'Financial Expenses', 'name_persian' => 'هزینه‌های مالی', 'type' => 'expense', 'parent_code' => '6000'],
            ['code' => '6310', 'name' => 'Interest Expense', 'name_persian' => 'هزینه سود', 'type' => 'expense', 'parent_code' => '6300'],
            ['code' => '6320', 'name' => 'Bank Charges', 'name_persian' => 'کارمزد بانک', 'type' => 'expense', 'parent_code' => '6300'],
        ];

        // Create accounts with proper parent relationships
        $createdAccounts = [];
        
        foreach ($accounts as $accountData) {
            $parentId = null;
            
            if (isset($accountData['parent_code']) && $accountData['parent_code']) {
                $parentAccount = collect($createdAccounts)->firstWhere('code', $accountData['parent_code']);
                $parentId = $parentAccount ? $parentAccount->id : null;
            }
            
            $account = Account::create([
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'name_persian' => $accountData['name_persian'],
                'type' => $accountData['type'],
                'parent_id' => $parentId,
                'is_active' => true,
                'description' => "Auto-generated account for {$accountData['name']}",
                'currency' => 'USD',
                'opening_balance' => 0
            ]);
            
            $createdAccounts[] = $account;
        }
    }

    /**
     * Create accounting entries for invoice sales
     */
    public function createSaleEntries($invoice): Transaction
    {
        // Get required accounts
        $salesRevenueAccount = Account::where('code', '4110')->first(); // Gold Jewelry Sales
        $accountsReceivableAccount = Account::where('code', '1210')->first(); // Trade Receivables
        $salesTaxAccount = Account::where('code', '2310')->first(); // Sales Tax Payable
        $inventoryAccount = Account::where('code', '1350')->first(); // Finished Goods
        $cogsAccount = Account::where('code', '5000')->first(); // Cost of Goods Sold

        if (!$salesRevenueAccount || !$accountsReceivableAccount) {
            throw new \Exception('Required accounts not found for sale entries');
        }

        $entries = [];

        // Debit Accounts Receivable for total amount
        $entries[] = [
            'account_id' => $accountsReceivableAccount->id,
            'debit_amount' => $invoice->total_amount,
            'credit_amount' => 0,
            'description' => "Sale to {$invoice->customer->name} - Invoice #{$invoice->invoice_number}",
            'description_persian' => "فروش به {$invoice->customer->name} - فاکتور #{$invoice->invoice_number}",
        ];

        // Credit Sales Revenue for subtotal
        $entries[] = [
            'account_id' => $salesRevenueAccount->id,
            'debit_amount' => 0,
            'credit_amount' => $invoice->subtotal,
            'description' => "Sales revenue - Invoice #{$invoice->invoice_number}",
            'description_persian' => "درآمد فروش - فاکتور #{$invoice->invoice_number}",
        ];

        // Credit Sales Tax if applicable
        if ($invoice->tax_amount > 0 && $salesTaxAccount) {
            $entries[] = [
                'account_id' => $salesTaxAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $invoice->tax_amount,
                'description' => "Sales tax - Invoice #{$invoice->invoice_number}",
                'description_persian' => "مالیات فروش - فاکتور #{$invoice->invoice_number}",
            ];
        }

        // Create COGS entries if inventory and COGS accounts exist
        if ($inventoryAccount && $cogsAccount) {
            $totalCost = $invoice->items->sum(function ($item) {
                return $item->inventoryItem ? $item->inventoryItem->cost_price * $item->quantity : 0;
            });

            if ($totalCost > 0) {
                // Debit COGS
                $entries[] = [
                    'account_id' => $cogsAccount->id,
                    'debit_amount' => $totalCost,
                    'credit_amount' => 0,
                    'description' => "Cost of goods sold - Invoice #{$invoice->invoice_number}",
                    'description_persian' => "بهای تمام شده - فاکتور #{$invoice->invoice_number}",
                ];

                // Credit Inventory
                $entries[] = [
                    'account_id' => $inventoryAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $totalCost,
                    'description' => "Inventory reduction - Invoice #{$invoice->invoice_number}",
                    'description_persian' => "کاهش موجودی - فاکتور #{$invoice->invoice_number}",
                ];
            }
        }

        return $this->createTransaction([
            'description' => "Sale - Invoice #{$invoice->invoice_number}",
            'description_persian' => "فروش - فاکتور #{$invoice->invoice_number}",
            'transaction_date' => $invoice->issue_date,
            'type' => 'invoice',
            'source_type' => 'invoice',
            'source_id' => $invoice->id,
            'total_amount' => $invoice->total_amount,
            'entries' => $entries,
        ]);
    }

    /**
     * Create accounting entries for inventory adjustments
     */
    public function createInventoryAdjustmentEntry($movement): Transaction
    {
        $inventoryAccount = Account::where('code', '1350')->first(); // Finished Goods
        $adjustmentAccount = Account::where('code', '6200')->first(); // Administrative Expenses (for adjustments)

        if (!$inventoryAccount || !$adjustmentAccount) {
            throw new \Exception('Required accounts not found for inventory adjustment');
        }

        $adjustmentValue = abs($movement->quantity * $movement->unit_cost);
        $entries = [];

        if ($movement->quantity > 0) {
            // Inventory increase
            $entries[] = [
                'account_id' => $inventoryAccount->id,
                'debit_amount' => $adjustmentValue,
                'credit_amount' => 0,
                'description' => "Inventory adjustment - {$movement->notes}",
                'description_persian' => "تعدیل موجودی - {$movement->notes}",
            ];

            $entries[] = [
                'account_id' => $adjustmentAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $adjustmentValue,
                'description' => "Inventory adjustment - {$movement->notes}",
                'description_persian' => "تعدیل موجودی - {$movement->notes}",
            ];
        } else {
            // Inventory decrease
            $entries[] = [
                'account_id' => $adjustmentAccount->id,
                'debit_amount' => $adjustmentValue,
                'credit_amount' => 0,
                'description' => "Inventory adjustment - {$movement->notes}",
                'description_persian' => "تعدیل موجودی - {$movement->notes}",
            ];

            $entries[] = [
                'account_id' => $inventoryAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $adjustmentValue,
                'description' => "Inventory adjustment - {$movement->notes}",
                'description_persian' => "تعدیل موجودی - {$movement->notes}",
            ];
        }

        return $this->createTransaction([
            'description' => "Inventory Adjustment - {$movement->inventoryItem->name}",
            'description_persian' => "تعدیل موجودی - {$movement->inventoryItem->name}",
            'transaction_date' => $movement->movement_date,
            'type' => 'adjustment',
            'source_type' => 'inventory_movement',
            'source_id' => $movement->id,
            'total_amount' => $adjustmentValue,
            'entries' => $entries,
        ]);
    }

    /**
     * Create accounting entries for returns
     */
    public function createReturnEntries($originalInvoice, array $returnItems): Transaction
    {
        $salesRevenueAccount = Account::where('code', '4110')->first();
        $accountsReceivableAccount = Account::where('code', '1210')->first();
        $salesTaxAccount = Account::where('code', '2310')->first();
        $inventoryAccount = Account::where('code', '1350')->first();
        $cogsAccount = Account::where('code', '5000')->first();

        if (!$salesRevenueAccount || !$accountsReceivableAccount) {
            throw new \Exception('Required accounts not found for return entries');
        }

        $returnValue = collect($returnItems)->sum('total_price');
        $returnTax = $returnValue * ($originalInvoice->tax_percentage / 100);
        $returnTotal = $returnValue + $returnTax;

        $entries = [];

        // Credit Accounts Receivable (reduce receivable)
        $entries[] = [
            'account_id' => $accountsReceivableAccount->id,
            'debit_amount' => 0,
            'credit_amount' => $returnTotal,
            'description' => "Return from {$originalInvoice->customer->name} - Invoice #{$originalInvoice->invoice_number}",
            'description_persian' => "برگشت از {$originalInvoice->customer->name} - فاکتور #{$originalInvoice->invoice_number}",
        ];

        // Debit Sales Revenue (reduce revenue)
        $entries[] = [
            'account_id' => $salesRevenueAccount->id,
            'debit_amount' => $returnValue,
            'credit_amount' => 0,
            'description' => "Sales return - Invoice #{$originalInvoice->invoice_number}",
            'description_persian' => "برگشت فروش - فاکتور #{$originalInvoice->invoice_number}",
        ];

        // Debit Sales Tax if applicable
        if ($returnTax > 0 && $salesTaxAccount) {
            $entries[] = [
                'account_id' => $salesTaxAccount->id,
                'debit_amount' => $returnTax,
                'credit_amount' => 0,
                'description' => "Sales tax return - Invoice #{$originalInvoice->invoice_number}",
                'description_persian' => "برگشت مالیات فروش - فاکتور #{$originalInvoice->invoice_number}",
            ];
        }

        return $this->createTransaction([
            'description' => "Return - Invoice #{$originalInvoice->invoice_number}",
            'description_persian' => "برگشت - فاکتور #{$originalInvoice->invoice_number}",
            'transaction_date' => now(),
            'type' => 'adjustment',
            'source_type' => 'invoice',
            'source_id' => $originalInvoice->id,
            'total_amount' => $returnTotal,
            'entries' => $entries,
        ]);
    }

    /**
     * Update customer account information
     */
    public function updateCustomerAccount($customer): void
    {
        // This method can be used to update customer-specific accounting records
        // For now, we'll just log the update
        \Log::info('Customer account updated', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
        ]);
    }
}