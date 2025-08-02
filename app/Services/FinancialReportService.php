<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class FinancialReportService
{
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function generateTrialBalance(?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        $trialBalance = $this->accountingService->getTrialBalance($asOfDate);
        
        $totalDebits = $trialBalance->sum('debit_balance');
        $totalCredits = $trialBalance->sum('credit_balance');
        
        return [
            'as_of_date' => $asOfDate->toDateString(),
            'accounts' => $trialBalance->toArray(),
            'totals' => [
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'difference' => $totalDebits - $totalCredits,
                'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
            ],
        ];
    }

    public function generateBalanceSheet(?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        
        $assets = $this->getAccountsByType(['asset'], $asOfDate);
        $liabilities = $this->getAccountsByType(['liability'], $asOfDate);
        $equity = $this->getAccountsByType(['equity'], $asOfDate);
        
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');
        
        return [
            'as_of_date' => $asOfDate->toDateString(),
            'assets' => [
                'current_assets' => $assets->where('subtype', 'current_asset')->values(),
                'fixed_assets' => $assets->where('subtype', 'fixed_asset')->values(),
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'current_liabilities' => $liabilities->where('subtype', 'current_liability')->values(),
                'long_term_liabilities' => $liabilities->where('subtype', 'long_term_liability')->values(),
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equity->values(),
                'total' => $totalEquity,
            ],
            'totals' => [
                'total_assets' => $totalAssets,
                'total_liabilities_equity' => $totalLiabilities + $totalEquity,
                'difference' => $totalAssets - ($totalLiabilities + $totalEquity),
                'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
            ],
        ];
    }

    public function generateIncomeStatement(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfYear();
        $endDate = $endDate ?? now();
        
        $revenues = $this->getAccountsByType(['revenue'], $endDate, $startDate);
        $expenses = $this->getAccountsByType(['expense'], $endDate, $startDate);
        
        $totalRevenue = $revenues->sum('balance');
        $totalExpenses = $expenses->sum('balance');
        $netIncome = $totalRevenue - $totalExpenses;
        
        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'revenue' => [
                'operating_revenue' => $revenues->where('subtype', 'operating_revenue')->values(),
                'other_revenue' => $revenues->where('subtype', 'other_revenue')->values(),
                'total' => $totalRevenue,
            ],
            'expenses' => [
                'operating_expenses' => $expenses->where('subtype', 'operating_expense')->values(),
                'other_expenses' => $expenses->where('subtype', 'other_expense')->values(),
                'total' => $totalExpenses,
            ],
            'net_income' => $netIncome,
            'gross_margin' => $totalRevenue > 0 ? (($totalRevenue - $totalExpenses) / $totalRevenue) * 100 : 0,
        ];
    }

    public function generateCashFlowStatement(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfYear();
        $endDate = $endDate ?? now();
        
        // Get cash and cash equivalent accounts
        $cashAccounts = Account::where('type', 'asset')
            ->where('subtype', 'current_asset')
            ->where(function ($query) {
                $query->where('name', 'like', '%cash%')
                    ->orWhere('name', 'like', '%bank%')
                    ->orWhere('code', 'like', '1010%'); // Assuming cash accounts start with 1010
            })
            ->get();
        
        $operatingActivities = $this->calculateOperatingCashFlow($startDate, $endDate);
        $investingActivities = $this->calculateInvestingCashFlow($startDate, $endDate);
        $financingActivities = $this->calculateFinancingCashFlow($startDate, $endDate);
        
        $netCashFlow = $operatingActivities['total'] + $investingActivities['total'] + $financingActivities['total'];
        
        $beginningCash = $cashAccounts->sum(function ($account) use ($startDate) {
            return $this->accountingService->getAccountBalance($account, $startDate->copy()->subDay());
        });
        
        $endingCash = $beginningCash + $netCashFlow;
        
        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'operating_activities' => $operatingActivities,
            'investing_activities' => $investingActivities,
            'financing_activities' => $financingActivities,
            'net_cash_flow' => $netCashFlow,
            'beginning_cash' => $beginningCash,
            'ending_cash' => $endingCash,
        ];
    }

    private function getAccountsByType(array $types, ?Carbon $asOfDate = null, ?Carbon $startDate = null): Collection
    {
        return Account::whereIn('type', $types)
            ->active()
            ->get()
            ->map(function ($account) use ($asOfDate, $startDate) {
                $balance = $startDate 
                    ? $this->getPeriodBalance($account, $startDate, $asOfDate)
                    : $this->accountingService->getAccountBalance($account, $asOfDate);
                
                return [
                    'code' => $account->code,
                    'name' => $account->localized_name,
                    'type' => $account->type,
                    'subtype' => $account->subtype,
                    'balance' => abs($balance), // Use absolute value for reporting
                ];
            })
            ->filter(function ($item) {
                return $item['balance'] > 0;
            });
    }

    private function getPeriodBalance(Account $account, Carbon $startDate, Carbon $endDate): float
    {
        $entries = $account->transactionEntries()
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transaction_date', [$startDate, $endDate]);
            })
            ->get();

        $totalDebits = $entries->sum('debit_amount');
        $totalCredits = $entries->sum('credit_amount');

        return match ($account->type) {
            'revenue' => $totalCredits - $totalDebits,
            'expense' => $totalDebits - $totalCredits,
            default => $totalDebits - $totalCredits
        };
    }

    private function calculateOperatingCashFlow(Carbon $startDate, Carbon $endDate): array
    {
        // Simplified operating cash flow calculation
        // In a real implementation, this would be more sophisticated
        
        $netIncome = $this->generateIncomeStatement($startDate, $endDate)['net_income'];
        
        // Add back non-cash expenses (depreciation, etc.)
        $depreciation = $this->getDepreciationExpense($startDate, $endDate);
        
        // Changes in working capital would be calculated here
        $workingCapitalChanges = 0;
        
        $total = $netIncome + $depreciation + $workingCapitalChanges;
        
        return [
            'net_income' => $netIncome,
            'depreciation' => $depreciation,
            'working_capital_changes' => $workingCapitalChanges,
            'total' => $total,
        ];
    }

    private function calculateInvestingCashFlow(Carbon $startDate, Carbon $endDate): array
    {
        // Calculate cash flows from investing activities
        // This would include asset purchases, sales, etc.
        
        return [
            'asset_purchases' => 0,
            'asset_sales' => 0,
            'total' => 0,
        ];
    }

    private function calculateFinancingCashFlow(Carbon $startDate, Carbon $endDate): array
    {
        // Calculate cash flows from financing activities
        // This would include loans, equity transactions, etc.
        
        return [
            'loan_proceeds' => 0,
            'loan_payments' => 0,
            'equity_transactions' => 0,
            'total' => 0,
        ];
    }

    private function getDepreciationExpense(Carbon $startDate, Carbon $endDate): float
    {
        // Get depreciation expense from expense accounts
        $depreciationAccounts = Account::where('type', 'expense')
            ->where(function ($query) {
                $query->where('name', 'like', '%depreciation%')
                    ->orWhere('code', 'like', '6%'); // Assuming depreciation accounts start with 6
            })
            ->get();

        return $depreciationAccounts->sum(function ($account) use ($startDate, $endDate) {
            return $this->getPeriodBalance($account, $startDate, $endDate);
        });
    }

    public function generateAgedReceivables(?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        
        // This would typically come from customer invoices
        // For now, return a placeholder structure
        
        return [
            'as_of_date' => $asOfDate->toDateString(),
            'aging_buckets' => [
                'current' => ['amount' => 0, 'count' => 0],
                '1_30_days' => ['amount' => 0, 'count' => 0],
                '31_60_days' => ['amount' => 0, 'count' => 0],
                '61_90_days' => ['amount' => 0, 'count' => 0],
                'over_90_days' => ['amount' => 0, 'count' => 0],
            ],
            'total_receivables' => 0,
        ];
    }

    public function generateAgedPayables(?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        
        // This would typically come from vendor invoices
        // For now, return a placeholder structure
        
        return [
            'as_of_date' => $asOfDate->toDateString(),
            'aging_buckets' => [
                'current' => ['amount' => 0, 'count' => 0],
                '1_30_days' => ['amount' => 0, 'count' => 0],
                '31_60_days' => ['amount' => 0, 'count' => 0],
                '61_90_days' => ['amount' => 0, 'count' => 0],
                'over_90_days' => ['amount' => 0, 'count' => 0],
            ],
            'total_payables' => 0,
        ];
    }
}