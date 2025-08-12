<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Currency;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AdvancedJournalEntryService
{
    protected AccountingService $accountingService;
    protected TaxService $taxService;

    public function __construct(AccountingService $accountingService, TaxService $taxService)
    {
        $this->accountingService = $accountingService;
        $this->taxService = $taxService;
    }

    /**
     * Create advanced journal entry with multi-currency support
     */
    public function createAdvancedJournalEntry(array $data): Transaction
    {
        $this->validateJournalEntry($data);

        return DB::transaction(function () use ($data) {
            // Handle currency conversion if needed
            $baseCurrency = config('app.base_currency', 'USD');
            $exchangeRates = $this->getExchangeRates($data['entries'], $baseCurrency);

            $totalAmount = $this->calculateTotalAmount($data['entries'], $exchangeRates, $baseCurrency);

            $transaction = Transaction::create([
                'reference_number' => $data['reference_number'] ?? Transaction::generateReferenceNumber(),
                'description' => $data['description'],
                'description_persian' => $data['description_persian'] ?? null,
                'transaction_date' => Carbon::parse($data['transaction_date']),
                'type' => 'journal_entry',
                'source_type' => $data['source_type'] ?? 'manual',
                'source_id' => $data['source_id'] ?? null,
                'total_amount' => $totalAmount,
                'currency' => $baseCurrency,
                'exchange_rate' => 1,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'tags' => $data['tags'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'approval_status' => $data['requires_approval'] ?? false ? 'pending' : 'approved',
                'approved_by' => $data['requires_approval'] ?? false ? null : auth()->id(),
                'approved_at' => $data['requires_approval'] ?? false ? null : now(),
            ]);

            // Create transaction entries with currency conversion
            foreach ($data['entries'] as $entryData) {
                $currency = $entryData['currency'] ?? $baseCurrency;
                $exchangeRate = $exchangeRates[$currency] ?? 1;

                // Convert amounts to base currency
                $debitAmount = ($entryData['debit_amount'] ?? 0) * $exchangeRate;
                $creditAmount = ($entryData['credit_amount'] ?? 0) * $exchangeRate;

                $transaction->entries()->create([
                    'account_id' => $entryData['account_id'],
                    'debit_amount' => $debitAmount,
                    'credit_amount' => $creditAmount,
                    'original_debit_amount' => $entryData['debit_amount'] ?? 0,
                    'original_credit_amount' => $entryData['credit_amount'] ?? 0,
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                    'description' => $entryData['description'] ?? null,
                    'description_persian' => $entryData['description_persian'] ?? null,
                    'metadata' => json_encode([
                        'cost_center_id' => $entryData['cost_center_id'] ?? null,
                        'project_id' => $entryData['project_id'] ?? null,
                        'department_id' => $entryData['department_id'] ?? null,
                        'tax_code' => $entryData['tax_code'] ?? null,
                        'custom_fields' => $entryData['custom_fields'] ?? null,
                    ]),
                ]);
            }

            // Validate transaction is balanced
            if (!$transaction->isBalanced()) {
                throw new \Exception('Journal entry is not balanced');
            }

            // Handle tax calculations if applicable
            if (isset($data['calculate_taxes']) && $data['calculate_taxes']) {
                $this->calculateAndCreateTaxEntries($transaction, $data);
            }

            // Update account balances
            $this->updateAccountBalances($transaction);

            // Create audit trail
            AuditLog::logActivity($transaction, 'journal_entry_created', null, [
                'entries_count' => count($data['entries']),
                'currencies_used' => array_unique(array_column($data['entries'], 'currency')),
                'total_amount' => $totalAmount,
            ]);

            return $transaction;
        });
    }

    /**
     * Create recurring journal entry
     */
    public function createRecurringJournalEntry(array $data): Collection
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $frequency = $data['frequency']; // daily, weekly, monthly, quarterly, yearly
        
        $transactions = collect();
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $entryData = $data;
            $entryData['transaction_date'] = $currentDate->format('Y-m-d');
            $entryData['reference_number'] = $this->generateRecurringReference($data['reference_prefix'] ?? 'REC', $currentDate);
            
            $transaction = $this->createAdvancedJournalEntry($entryData);
            $transactions->push($transaction);

            // Move to next occurrence
            $currentDate = $this->getNextOccurrence($currentDate, $frequency);
        }

        return $transactions;
    }

    /**
     * Create reversing journal entry
     */
    public function createReversingEntry(Transaction $originalTransaction, Carbon $reversingDate = null): Transaction
    {
        $reversingDate = $reversingDate ?? now();

        $reversingData = [
            'reference_number' => 'REV-' . $originalTransaction->reference_number,
            'description' => 'Reversing entry for: ' . $originalTransaction->description,
            'description_persian' => $originalTransaction->description_persian ? 'برگشت: ' . $originalTransaction->description_persian : null,
            'transaction_date' => $reversingDate,
            'source_type' => 'reversal',
            'source_id' => $originalTransaction->id,
            'cost_center_id' => $originalTransaction->cost_center_id,
            'tags' => $originalTransaction->tags,
            'notes' => 'Automatic reversal of transaction #' . $originalTransaction->reference_number,
            'entries' => []
        ];

        // Reverse all entries (swap debits and credits)
        foreach ($originalTransaction->entries as $entry) {
            $reversingData['entries'][] = [
                'account_id' => $entry->account_id,
                'debit_amount' => $entry->original_credit_amount ?? $entry->credit_amount,
                'credit_amount' => $entry->original_debit_amount ?? $entry->debit_amount,
                'currency' => $entry->currency ?? 'USD',
                'description' => 'Reversal: ' . $entry->description,
                'description_persian' => $entry->description_persian ? 'برگشت: ' . $entry->description_persian : null,
            ];
        }

        return $this->createAdvancedJournalEntry($reversingData);
    }

    /**
     * Create adjusting journal entry
     */
    public function createAdjustingEntry(array $data): Transaction
    {
        $data['type'] = 'adjusting_entry';
        $data['source_type'] = 'adjustment';
        $data['requires_approval'] = true; // Adjusting entries typically require approval

        return $this->createAdvancedJournalEntry($data);
    }

    /**
     * Create closing journal entry
     */
    public function createClosingEntry(Carbon $periodEnd): Collection
    {
        $transactions = collect();

        // Close revenue accounts
        $revenueAccounts = Account::where('type', 'revenue')->where('is_active', true)->get();
        if ($revenueAccounts->isNotEmpty()) {
            $revenueClosingData = $this->prepareClosingEntry($revenueAccounts, $periodEnd, 'revenue');
            if (!empty($revenueClosingData['entries'])) {
                $transactions->push($this->createAdvancedJournalEntry($revenueClosingData));
            }
        }

        // Close expense accounts
        $expenseAccounts = Account::where('type', 'expense')->where('is_active', true)->get();
        if ($expenseAccounts->isNotEmpty()) {
            $expenseClosingData = $this->prepareClosingEntry($expenseAccounts, $periodEnd, 'expense');
            if (!empty($expenseClosingData['entries'])) {
                $transactions->push($this->createAdvancedJournalEntry($expenseClosingData));
            }
        }

        return $transactions;
    }

    protected function validateJournalEntry(array $data): void
    {
        if (empty($data['entries']) || count($data['entries']) < 2) {
            throw new \Exception('Journal entry must have at least 2 entries');
        }

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($data['entries'] as $entry) {
            if (!isset($entry['account_id'])) {
                throw new \Exception('Each entry must have an account_id');
            }

            $account = Account::find($entry['account_id']);
            if (!$account || !$account->is_active) {
                throw new \Exception('Invalid or inactive account: ' . $entry['account_id']);
            }

            $debit = $entry['debit_amount'] ?? 0;
            $credit = $entry['credit_amount'] ?? 0;

            if ($debit < 0 || $credit < 0) {
                throw new \Exception('Debit and credit amounts must be positive');
            }

            if ($debit > 0 && $credit > 0) {
                throw new \Exception('Entry cannot have both debit and credit amounts');
            }

            if ($debit == 0 && $credit == 0) {
                throw new \Exception('Entry must have either debit or credit amount');
            }

            $totalDebits += $debit;
            $totalCredits += $credit;
        }

        if (abs($totalDebits - $totalCredits) > 0.01) {
            throw new \Exception('Journal entry is not balanced. Debits: ' . $totalDebits . ', Credits: ' . $totalCredits);
        }
    }

    protected function getExchangeRates(array $entries, string $baseCurrency): array
    {
        $currencies = array_unique(array_column($entries, 'currency'));
        $rates = [$baseCurrency => 1];

        foreach ($currencies as $currency) {
            if ($currency && $currency !== $baseCurrency) {
                // In a real implementation, you would fetch from an exchange rate service
                $rates[$currency] = $this->getExchangeRate($currency, $baseCurrency);
            }
        }

        return $rates;
    }

    protected function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        // This is a simplified implementation
        // In production, you would integrate with a real exchange rate service
        $currency = Currency::where('code', $fromCurrency)->first();
        return $currency ? $currency->exchange_rate : 1;
    }

    protected function calculateTotalAmount(array $entries, array $exchangeRates, string $baseCurrency): float
    {
        $total = 0;

        foreach ($entries as $entry) {
            $currency = $entry['currency'] ?? $baseCurrency;
            $rate = $exchangeRates[$currency] ?? 1;
            $amount = max($entry['debit_amount'] ?? 0, $entry['credit_amount'] ?? 0);
            $total += $amount * $rate;
        }

        return $total / 2; // Divide by 2 because we're counting both debits and credits
    }

    protected function calculateAndCreateTaxEntries(Transaction $transaction, array $data): void
    {
        if (!isset($data['tax_entries'])) {
            return;
        }

        foreach ($data['tax_entries'] as $taxEntry) {
            $taxAmount = $this->taxService->calculateTax(
                $taxEntry['taxable_amount'],
                $taxEntry['tax_code']
            );

            if ($taxAmount > 0) {
                $transaction->entries()->create([
                    'account_id' => $taxEntry['tax_account_id'],
                    'debit_amount' => $taxEntry['is_tax_debit'] ? $taxAmount : 0,
                    'credit_amount' => $taxEntry['is_tax_debit'] ? 0 : $taxAmount,
                    'description' => 'Tax calculation for ' . $taxEntry['tax_code'],
                    'metadata' => json_encode([
                        'tax_code' => $taxEntry['tax_code'],
                        'taxable_amount' => $taxEntry['taxable_amount'],
                        'tax_rate' => $this->taxService->getTaxRate($taxEntry['tax_code']),
                    ]),
                ]);
            }
        }
    }

    protected function updateAccountBalances(Transaction $transaction): void
    {
        $accountIds = $transaction->entries->pluck('account_id')->unique();
        
        foreach ($accountIds as $accountId) {
            $account = Account::find($accountId);
            if ($account) {
                $account->updateBalance();
            }
        }
    }

    protected function generateRecurringReference(string $prefix, Carbon $date): string
    {
        return $prefix . '-' . $date->format('Y-m-d') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }

    protected function getNextOccurrence(Carbon $currentDate, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $currentDate->addDay(),
            'weekly' => $currentDate->addWeek(),
            'monthly' => $currentDate->addMonth(),
            'quarterly' => $currentDate->addMonths(3),
            'yearly' => $currentDate->addYear(),
            default => $currentDate->addMonth(),
        };
    }

    protected function prepareClosingEntry(Collection $accounts, Carbon $periodEnd, string $accountType): array
    {
        $entries = [];
        $totalAmount = 0;

        foreach ($accounts as $account) {
            $balance = $this->accountingService->getAccountBalance($account, $periodEnd);
            
            if (abs($balance) > 0.01) {
                if ($accountType === 'revenue') {
                    // Close revenue accounts (debit revenue, credit income summary)
                    $entries[] = [
                        'account_id' => $account->id,
                        'debit_amount' => $balance,
                        'credit_amount' => 0,
                        'description' => 'Closing entry for ' . $account->name,
                    ];
                    $totalAmount += $balance;
                } else {
                    // Close expense accounts (credit expense, debit income summary)
                    $entries[] = [
                        'account_id' => $account->id,
                        'debit_amount' => 0,
                        'credit_amount' => $balance,
                        'description' => 'Closing entry for ' . $account->name,
                    ];
                    $totalAmount += $balance;
                }
            }
        }

        if (!empty($entries) && abs($totalAmount) > 0.01) {
            // Add income summary entry
            $incomeSummaryAccount = Account::where('code', '3300')->first(); // Current Year Earnings
            if ($incomeSummaryAccount) {
                $entries[] = [
                    'account_id' => $incomeSummaryAccount->id,
                    'debit_amount' => $accountType === 'expense' ? $totalAmount : 0,
                    'credit_amount' => $accountType === 'revenue' ? $totalAmount : 0,
                    'description' => 'Income summary for ' . $accountType . ' accounts',
                ];
            }
        }

        return [
            'reference_number' => 'CLOSE-' . strtoupper($accountType) . '-' . $periodEnd->format('Y-m-d'),
            'description' => 'Closing entry for ' . $accountType . ' accounts - ' . $periodEnd->format('Y-m-d'),
            'transaction_date' => $periodEnd,
            'source_type' => 'closing_entry',
            'entries' => $entries,
        ];
    }
}