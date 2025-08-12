<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Budget;
use App\Models\RecurringTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CashFlowForecastingService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Generate comprehensive cash flow forecast
     */
    public function generateCashFlowForecast(Carbon $startDate, Carbon $endDate, array $options = []): array
    {
        $forecast = [
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'generated_at' => now()->toISOString(),
            'forecast_type' => $options['type'] ?? 'comprehensive',
            'opening_balance' => $this->getOpeningCashBalance($startDate),
            'closing_balance_forecast' => 0,
            'monthly_breakdown' => [],
            'cash_inflows' => [],
            'cash_outflows' => [],
            'summary' => [
                'total_inflows' => 0,
                'total_outflows' => 0,
                'net_cash_flow' => 0,
                'minimum_balance' => 0,
                'maximum_balance' => 0,
            ],
            'scenarios' => [],
            'recommendations' => [],
        ];

        $currentDate = $startDate->copy();
        $runningBalance = $forecast['opening_balance'];
        $minBalance = $runningBalance;
        $maxBalance = $runningBalance;

        while ($currentDate->lte($endDate)) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();

            // Calculate monthly cash flows
            $monthlyForecast = $this->calculateMonthlyCashFlow($monthStart, $monthEnd, $options);
            
            $runningBalance += $monthlyForecast['net_cash_flow'];
            $minBalance = min($minBalance, $runningBalance);
            $maxBalance = max($maxBalance, $runningBalance);

            $forecast['monthly_breakdown'][] = [
                'month' => $currentDate->format('Y-m'),
                'month_name' => $currentDate->format('F Y'),
                'opening_balance' => $runningBalance - $monthlyForecast['net_cash_flow'],
                'inflows' => $monthlyForecast['inflows'],
                'outflows' => $monthlyForecast['outflows'],
                'net_cash_flow' => $monthlyForecast['net_cash_flow'],
                'closing_balance' => $runningBalance,
                'details' => $monthlyForecast['details'],
            ];

            $forecast['summary']['total_inflows'] += $monthlyForecast['inflows'];
            $forecast['summary']['total_outflows'] += $monthlyForecast['outflows'];

            $currentDate->addMonth();
        }

        $forecast['closing_balance_forecast'] = $runningBalance;
        $forecast['summary']['net_cash_flow'] = $forecast['summary']['total_inflows'] - $forecast['summary']['total_outflows'];
        $forecast['summary']['minimum_balance'] = $minBalance;
        $forecast['summary']['maximum_balance'] = $maxBalance;

        // Generate scenarios
        $forecast['scenarios'] = $this->generateCashFlowScenarios($forecast, $options);

        // Generate recommendations
        $forecast['recommendations'] = $this->generateCashFlowRecommendations($forecast);

        return $forecast;
    }

    /**
     * Calculate monthly cash flow
     */
    protected function calculateMonthlyCashFlow(Carbon $monthStart, Carbon $monthEnd, array $options): array
    {
        $result = [
            'inflows' => 0,
            'outflows' => 0,
            'net_cash_flow' => 0,
            'details' => [
                'receivables_collection' => 0,
                'sales_revenue' => 0,
                'other_income' => 0,
                'payables_payment' => 0,
                'operating_expenses' => 0,
                'capital_expenditure' => 0,
                'loan_payments' => 0,
                'tax_payments' => 0,
            ],
        ];

        // Forecast receivables collection
        $receivablesCollection = $this->forecastReceivablesCollection($monthStart, $monthEnd);
        $result['details']['receivables_collection'] = $receivablesCollection;
        $result['inflows'] += $receivablesCollection;

        // Forecast sales revenue (new sales)
        $salesRevenue = $this->forecastSalesRevenue($monthStart, $monthEnd, $options);
        $result['details']['sales_revenue'] = $salesRevenue;
        $result['inflows'] += $salesRevenue;

        // Forecast other income
        $otherIncome = $this->forecastOtherIncome($monthStart, $monthEnd);
        $result['details']['other_income'] = $otherIncome;
        $result['inflows'] += $otherIncome;

        // Forecast payables payment
        $payablesPayment = $this->forecastPayablesPayment($monthStart, $monthEnd);
        $result['details']['payables_payment'] = $payablesPayment;
        $result['outflows'] += $payablesPayment;

        // Forecast operating expenses
        $operatingExpenses = $this->forecastOperatingExpenses($monthStart, $monthEnd, $options);
        $result['details']['operating_expenses'] = $operatingExpenses;
        $result['outflows'] += $operatingExpenses;

        // Forecast capital expenditure
        $capitalExpenditure = $this->forecastCapitalExpenditure($monthStart, $monthEnd);
        $result['details']['capital_expenditure'] = $capitalExpenditure;
        $result['outflows'] += $capitalExpenditure;

        // Forecast loan payments
        $loanPayments = $this->forecastLoanPayments($monthStart, $monthEnd);
        $result['details']['loan_payments'] = $loanPayments;
        $result['outflows'] += $loanPayments;

        // Forecast tax payments
        $taxPayments = $this->forecastTaxPayments($monthStart, $monthEnd);
        $result['details']['tax_payments'] = $taxPayments;
        $result['outflows'] += $taxPayments;

        $result['net_cash_flow'] = $result['inflows'] - $result['outflows'];

        return $result;
    }

    /**
     * Bank reconciliation service
     */
    public function performBankReconciliation(int $accountId, Carbon $statementDate, array $bankStatementData): array
    {
        $account = Account::findOrFail($accountId);
        
        $reconciliation = [
            'account_id' => $accountId,
            'account_name' => $account->name,
            'statement_date' => $statementDate->toDateString(),
            'reconciliation_date' => now()->toISOString(),
            'book_balance' => $this->accountingService->getAccountBalance($account, $statementDate),
            'bank_balance' => $bankStatementData['ending_balance'],
            'reconciled_balance' => 0,
            'outstanding_deposits' => [],
            'outstanding_checks' => [],
            'bank_errors' => [],
            'book_errors' => [],
            'adjustments_needed' => [],
            'is_reconciled' => false,
            'variance' => 0,
        ];

        // Get book transactions for the period
        $bookTransactions = $this->getBookTransactions($accountId, $statementDate);
        
        // Get bank statement transactions
        $bankTransactions = collect($bankStatementData['transactions']);

        // Match transactions
        $matchedTransactions = $this->matchTransactions($bookTransactions, $bankTransactions);

        // Identify outstanding items
        $reconciliation['outstanding_deposits'] = $this->identifyOutstandingDeposits($matchedTransactions['unmatched_book']);
        $reconciliation['outstanding_checks'] = $this->identifyOutstandingChecks($matchedTransactions['unmatched_book']);

        // Calculate reconciled balance
        $reconciliation['reconciled_balance'] = $reconciliation['book_balance'] 
            + array_sum(array_column($reconciliation['outstanding_deposits'], 'amount'))
            - array_sum(array_column($reconciliation['outstanding_checks'], 'amount'));

        // Calculate variance
        $reconciliation['variance'] = abs($reconciliation['reconciled_balance'] - $reconciliation['bank_balance']);

        // Check if reconciled (within tolerance)
        $tolerance = 0.01; // $0.01 tolerance
        $reconciliation['is_reconciled'] = $reconciliation['variance'] <= $tolerance;

        // Identify potential errors
        if (!$reconciliation['is_reconciled']) {
            $reconciliation['bank_errors'] = $this->identifyBankErrors($matchedTransactions['unmatched_bank']);
            $reconciliation['book_errors'] = $this->identifyBookErrors($matchedTransactions['unmatched_book']);
            $reconciliation['adjustments_needed'] = $this->suggestAdjustments($reconciliation);
        }

        return $reconciliation;
    }

    /**
     * Create bank reconciliation adjustments
     */
    public function createReconciliationAdjustments(array $reconciliationData, array $adjustments): Collection
    {
        $transactions = collect();

        foreach ($adjustments as $adjustment) {
            $transactionData = [
                'reference_number' => 'BANK-ADJ-' . now()->format('Y-m-d-His'),
                'description' => $adjustment['description'],
                'description_persian' => $adjustment['description_persian'] ?? null,
                'transaction_date' => Carbon::parse($adjustment['date']),
                'type' => 'bank_adjustment',
                'source_type' => 'bank_reconciliation',
                'total_amount' => abs($adjustment['amount']),
                'entries' => []
            ];

            // Bank account entry
            $transactionData['entries'][] = [
                'account_id' => $reconciliationData['account_id'],
                'debit_amount' => $adjustment['amount'] > 0 ? $adjustment['amount'] : 0,
                'credit_amount' => $adjustment['amount'] < 0 ? abs($adjustment['amount']) : 0,
                'description' => $adjustment['description'],
            ];

            // Offsetting entry
            $offsetAccountId = $this->getOffsetAccount($adjustment['type']);
            $transactionData['entries'][] = [
                'account_id' => $offsetAccountId,
                'debit_amount' => $adjustment['amount'] < 0 ? abs($adjustment['amount']) : 0,
                'credit_amount' => $adjustment['amount'] > 0 ? $adjustment['amount'] : 0,
                'description' => $adjustment['description'],
            ];

            $transaction = $this->accountingService->createTransaction($transactionData);
            $transactions->push($transaction);
        }

        return $transactions;
    }

    protected function getOpeningCashBalance(Carbon $date): float
    {
        $cashAccounts = Account::where('type', 'asset')
            ->where(function ($query) {
                $query->where('name', 'like', '%cash%')
                      ->orWhere('name', 'like', '%bank%')
                      ->orWhere('code', 'like', '11%'); // Cash and bank accounts
            })
            ->get();

        $totalBalance = 0;
        foreach ($cashAccounts as $account) {
            $totalBalance += $this->accountingService->getAccountBalance($account, $date->copy()->subDay());
        }

        return $totalBalance;
    }

    protected function forecastReceivablesCollection(Carbon $monthStart, Carbon $monthEnd): float
    {
        // Get outstanding invoices and estimate collection based on aging
        $outstandingInvoices = Invoice::where('status', '!=', 'paid')
            ->where('due_date', '<=', $monthEnd)
            ->get();

        $expectedCollection = 0;

        foreach ($outstandingInvoices as $invoice) {
            $daysOverdue = $monthEnd->diffInDays($invoice->due_date);
            
            // Apply collection probability based on aging
            $collectionProbability = match (true) {
                $daysOverdue <= 0 => 0.95,      // Current: 95%
                $daysOverdue <= 30 => 0.85,     // 1-30 days: 85%
                $daysOverdue <= 60 => 0.70,     // 31-60 days: 70%
                $daysOverdue <= 90 => 0.50,     // 61-90 days: 50%
                default => 0.25,                // Over 90 days: 25%
            };

            $expectedCollection += $invoice->total_amount * $collectionProbability;
        }

        return $expectedCollection;
    }

    protected function forecastSalesRevenue(Carbon $monthStart, Carbon $monthEnd, array $options): float
    {
        // Use historical data and growth trends
        $historicalSales = $this->getHistoricalSales($monthStart->copy()->subYear(), $monthEnd->copy()->subYear());
        $growthRate = $options['growth_rate'] ?? 0.05; // 5% default growth
        
        return $historicalSales * (1 + $growthRate);
    }

    protected function forecastOtherIncome(Carbon $monthStart, Carbon $monthEnd): float
    {
        // Forecast other income sources (interest, rental, etc.)
        $otherIncomeAccounts = Account::where('type', 'revenue')
            ->where('name', 'not like', '%sales%')
            ->get();

        $totalOtherIncome = 0;
        foreach ($otherIncomeAccounts as $account) {
            // Use average of last 3 months
            $historicalAverage = $this->getAccountAverageForPeriod($account, $monthStart->copy()->subMonths(3), $monthStart->copy()->subDay());
            $totalOtherIncome += $historicalAverage;
        }

        return $totalOtherIncome;
    }

    protected function forecastPayablesPayment(Carbon $monthStart, Carbon $monthEnd): float
    {
        // Estimate payables payment based on payment terms
        $payableAccounts = Account::where('type', 'liability')
            ->where('name', 'like', '%payable%')
            ->get();

        $totalPayments = 0;
        foreach ($payableAccounts as $account) {
            $currentBalance = $this->accountingService->getAccountBalance($account);
            // Assume 80% of payables are paid within the month
            $totalPayments += $currentBalance * 0.8;
        }

        return $totalPayments;
    }

    protected function forecastOperatingExpenses(Carbon $monthStart, Carbon $monthEnd, array $options): float
    {
        // Use budget data if available, otherwise use historical averages
        $budget = Budget::where('budget_year', $monthStart->year)
            ->where('status', 'approved')
            ->first();

        if ($budget) {
            return $this->getBudgetedExpensesForMonth($budget, $monthStart->month);
        }

        // Fallback to historical average
        $expenseAccounts = Account::where('type', 'expense')->get();
        $totalExpenses = 0;

        foreach ($expenseAccounts as $account) {
            $historicalAverage = $this->getAccountAverageForPeriod($account, $monthStart->copy()->subMonths(3), $monthStart->copy()->subDay());
            $totalExpenses += $historicalAverage;
        }

        return $totalExpenses;
    }

    protected function forecastCapitalExpenditure(Carbon $monthStart, Carbon $monthEnd): float
    {
        // Check for planned capital expenditures
        // This would typically come from a capital budget or planned purchases
        return 0; // Placeholder
    }

    protected function forecastLoanPayments(Carbon $monthStart, Carbon $monthEnd): float
    {
        // Calculate scheduled loan payments
        $loanAccounts = Account::where('type', 'liability')
            ->where(function ($query) {
                $query->where('name', 'like', '%loan%')
                      ->orWhere('name', 'like', '%mortgage%');
            })
            ->get();

        // This would need to be enhanced with actual loan payment schedules
        return 0; // Placeholder
    }

    protected function forecastTaxPayments(Carbon $monthStart, Carbon $monthEnd): float
    {
        // Estimate tax payments based on tax liability accounts
        $taxAccounts = Account::where('type', 'liability')
            ->where('name', 'like', '%tax%')
            ->get();

        $totalTaxPayments = 0;
        foreach ($taxAccounts as $account) {
            $balance = $this->accountingService->getAccountBalance($account);
            // Assume quarterly tax payments
            if ($monthStart->month % 3 === 0) {
                $totalTaxPayments += $balance;
            }
        }

        return $totalTaxPayments;
    }

    protected function generateCashFlowScenarios(array $baseForecast, array $options): array
    {
        $scenarios = [];

        // Optimistic scenario (20% better)
        $scenarios['optimistic'] = $this->adjustForecastScenario($baseForecast, 1.2, 0.8);

        // Pessimistic scenario (20% worse)
        $scenarios['pessimistic'] = $this->adjustForecastScenario($baseForecast, 0.8, 1.2);

        // Conservative scenario (10% worse inflows, 10% higher outflows)
        $scenarios['conservative'] = $this->adjustForecastScenario($baseForecast, 0.9, 1.1);

        return $scenarios;
    }

    protected function adjustForecastScenario(array $baseForecast, float $inflowMultiplier, float $outflowMultiplier): array
    {
        $scenario = $baseForecast;
        $scenario['summary']['total_inflows'] *= $inflowMultiplier;
        $scenario['summary']['total_outflows'] *= $outflowMultiplier;
        $scenario['summary']['net_cash_flow'] = $scenario['summary']['total_inflows'] - $scenario['summary']['total_outflows'];
        $scenario['closing_balance_forecast'] = $baseForecast['opening_balance'] + $scenario['summary']['net_cash_flow'];

        return $scenario;
    }

    protected function generateCashFlowRecommendations(array $forecast): array
    {
        $recommendations = [];

        // Check for negative cash flow periods
        foreach ($forecast['monthly_breakdown'] as $month) {
            if ($month['closing_balance'] < 0) {
                $recommendations[] = [
                    'type' => 'cash_shortage',
                    'priority' => 'high',
                    'month' => $month['month_name'],
                    'message' => "Projected cash shortage in {$month['month_name']}",
                    'suggestion' => 'Consider arranging additional financing or accelerating receivables collection',
                ];
            }
        }

        // Check for excess cash
        if ($forecast['summary']['minimum_balance'] > 100000) { // $100k threshold
            $recommendations[] = [
                'type' => 'excess_cash',
                'priority' => 'medium',
                'message' => 'Significant excess cash projected',
                'suggestion' => 'Consider investment opportunities or debt reduction',
            ];
        }

        return $recommendations;
    }

    protected function getHistoricalSales(Carbon $startDate, Carbon $endDate): float
    {
        return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'invoice')
            ->sum('total_amount');
    }

    protected function getAccountAverageForPeriod(Account $account, Carbon $startDate, Carbon $endDate): float
    {
        $entries = $account->transactionEntries()
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transaction_date', [$startDate, $endDate]);
            })
            ->get();

        $total = 0;
        $months = $startDate->diffInMonths($endDate) ?: 1;

        foreach ($entries as $entry) {
            if ($account->type === 'revenue') {
                $total += $entry->credit_amount;
            } else {
                $total += $entry->debit_amount;
            }
        }

        return $total / $months;
    }

    protected function getBudgetedExpensesForMonth(Budget $budget, int $month): float
    {
        $monthNames = ['january', 'february', 'march', 'april', 'may', 'june',
                      'july', 'august', 'september', 'october', 'november', 'december'];
        
        $monthName = $monthNames[$month - 1];
        
        return $budget->budgetLines()
            ->whereHas('account', function ($query) {
                $query->where('type', 'expense');
            })
            ->sum($monthName);
    }

    protected function getBookTransactions(int $accountId, Carbon $statementDate): Collection
    {
        return Transaction::whereHas('entries', function ($query) use ($accountId) {
                $query->where('account_id', $accountId);
            })
            ->where('transaction_date', '<=', $statementDate)
            ->with(['entries' => function ($query) use ($accountId) {
                $query->where('account_id', $accountId);
            }])
            ->get();
    }

    protected function matchTransactions(Collection $bookTransactions, Collection $bankTransactions): array
    {
        $matched = [];
        $unmatchedBook = $bookTransactions->toArray();
        $unmatchedBank = $bankTransactions->toArray();

        // Simple matching logic - would need to be enhanced for production
        foreach ($bookTransactions as $bookIndex => $bookTxn) {
            foreach ($bankTransactions as $bankIndex => $bankTxn) {
                if (abs($bookTxn->total_amount - $bankTxn['amount']) < 0.01 &&
                    abs($bookTxn->transaction_date->diffInDays(Carbon::parse($bankTxn['date']))) <= 2) {
                    
                    $matched[] = [
                        'book_transaction' => $bookTxn,
                        'bank_transaction' => $bankTxn,
                    ];
                    
                    unset($unmatchedBook[$bookIndex]);
                    unset($unmatchedBank[$bankIndex]);
                    break;
                }
            }
        }

        return [
            'matched' => $matched,
            'unmatched_book' => array_values($unmatchedBook),
            'unmatched_bank' => array_values($unmatchedBank),
        ];
    }

    protected function identifyOutstandingDeposits(array $unmatchedTransactions): array
    {
        $deposits = [];
        
        foreach ($unmatchedTransactions as $txn) {
            if ($txn->total_amount > 0) { // Positive amount indicates deposit
                $deposits[] = [
                    'transaction_id' => $txn->id,
                    'date' => $txn->transaction_date->toDateString(),
                    'amount' => $txn->total_amount,
                    'description' => $txn->description,
                ];
            }
        }

        return $deposits;
    }

    protected function identifyOutstandingChecks(array $unmatchedTransactions): array
    {
        $checks = [];
        
        foreach ($unmatchedTransactions as $txn) {
            if ($txn->total_amount < 0) { // Negative amount indicates check/payment
                $checks[] = [
                    'transaction_id' => $txn->id,
                    'date' => $txn->transaction_date->toDateString(),
                    'amount' => abs($txn->total_amount),
                    'description' => $txn->description,
                ];
            }
        }

        return $checks;
    }

    protected function identifyBankErrors(array $unmatchedBankTransactions): array
    {
        // Logic to identify potential bank errors
        return [];
    }

    protected function identifyBookErrors(array $unmatchedBookTransactions): array
    {
        // Logic to identify potential book errors
        return [];
    }

    protected function suggestAdjustments(array $reconciliation): array
    {
        $adjustments = [];

        if ($reconciliation['variance'] > 0.01) {
            $adjustments[] = [
                'type' => 'variance_adjustment',
                'description' => 'Bank reconciliation variance adjustment',
                'amount' => $reconciliation['bank_balance'] - $reconciliation['reconciled_balance'],
                'date' => $reconciliation['statement_date'],
            ];
        }

        return $adjustments;
    }

    protected function getOffsetAccount(string $adjustmentType): int
    {
        // Return appropriate offset account based on adjustment type
        // For now, return a miscellaneous expense account
        $account = Account::firstOrCreate(
            ['code' => '6999'],
            [
                'name' => 'Miscellaneous Expense',
                'name_persian' => 'هزینه متفرقه',
                'type' => 'expense',
                'is_active' => true,
                'currency' => 'USD',
                'opening_balance' => 0
            ]
        );

        return $account->id;
    }
}