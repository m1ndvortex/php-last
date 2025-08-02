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
}