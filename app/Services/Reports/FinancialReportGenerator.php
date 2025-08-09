<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportGenerator extends BaseReportGenerator
{
    /**
     * Generate financial report
     */
    public function generate(): array
    {
        switch ($this->subtype) {
            case 'profit_loss':
                return $this->generateProfitLossReport();
            case 'balance_sheet':
                return $this->generateBalanceSheetReport();
            case 'cash_flow':
                return $this->generateCashFlowReport();
            case 'trial_balance':
                return $this->generateTrialBalanceReport();
            default:
                throw new \InvalidArgumentException("Unknown financial report subtype: {$this->subtype}");
        }
    }

    /**
     * Generate Profit & Loss report
     */
    protected function generateProfitLossReport(): array
    {
        // Get revenue from invoices
        $invoices = Invoice::whereBetween('issue_date', [$this->startDate, $this->endDate])
            ->where('status', '!=', 'cancelled')
            ->with('items.inventoryItem')
            ->get();

        $totalRevenue = $invoices->sum('total_amount');
        
        // Calculate COGS (Cost of Goods Sold) from invoice items
        $totalCOGS = $invoices->flatMap->items->sum(function ($item) {
            // Use cost price from inventory item, fallback to 60% of selling price
            $costPrice = $item->inventoryItem->cost_price ?? ($item->unit_price * 0.6);
            return $costPrice * $item->quantity;
        });

        // Estimate operating expenses (20% of revenue as default)
        $operatingExpenses = $totalRevenue * 0.20;

        // Calculate gross profit
        $grossProfit = $totalRevenue - $totalCOGS;
        $grossProfitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

        // Calculate net profit
        $netProfit = $grossProfit - $operatingExpenses;
        $netProfitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        // Previous period comparison
        $previousPeriod = $this->getPreviousPeriodPL();

        // Monthly breakdown
        $monthlyBreakdown = $this->getMonthlyPLBreakdown();

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.profit_loss_statement'),
            'type' => 'financial',
            'subtype' => 'profit_loss',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_revenue' => [
                    'value' => $totalRevenue,
                    'formatted' => $this->formatCurrency($totalRevenue),
                    'change' => $this->calculatePercentageChange($totalRevenue, $previousPeriod['revenue']),
                    'label' => $this->trans('reports.total_revenue')
                ],
                'gross_profit' => [
                    'value' => $grossProfit,
                    'formatted' => $this->formatCurrency($grossProfit),
                    'margin' => $grossProfitMargin,
                    'change' => $this->calculatePercentageChange($grossProfit, $previousPeriod['gross_profit']),
                    'label' => $this->trans('reports.gross_profit')
                ],
                'net_profit' => [
                    'value' => $netProfit,
                    'formatted' => $this->formatCurrency($netProfit),
                    'margin' => $netProfitMargin,
                    'change' => $this->calculatePercentageChange($netProfit, $previousPeriod['net_profit']),
                    'label' => $this->trans('reports.net_profit')
                ]
            ],
            'charts' => [
                'monthly_profit' => $this->generateChartData($monthlyBreakdown, 'line', [
                    'title' => $this->trans('reports.monthly_profit_trend'),
                    'label_field' => 'month',
                    'value_field' => 'net_profit',
                    'dataset_label' => $this->trans('reports.net_profit')
                ]),
                'revenue_vs_expenses' => $this->generateChartData(collect([
                    ['category' => 'Revenue', 'amount' => $totalRevenue],
                    ['category' => 'COGS', 'amount' => $totalCOGS],
                    ['category' => 'Operating Expenses', 'amount' => $operatingExpenses]
                ]), 'bar', [
                    'title' => $this->trans('reports.revenue_vs_expenses'),
                    'label_field' => 'category',
                    'value_field' => 'amount',
                    'dataset_label' => $this->trans('reports.amount')
                ])
            ],
            'data' => [
                'revenue' => [
                    'invoices' => $invoices->map(function ($invoice) {
                        return [
                            'invoice_number' => $invoice->invoice_number,
                            'date' => $invoice->issue_date->format('Y-m-d'),
                            'customer' => $invoice->customer->name,
                            'amount' => $invoice->total_amount
                        ];
                    })->toArray(),
                    'total' => $totalRevenue
                ],
                'cost_of_goods_sold' => [
                    'total' => $totalCOGS,
                    'calculation_method' => 'Estimated from inventory cost prices (60% fallback)'
                ],
                'operating_expenses' => [
                    'total' => $operatingExpenses,
                    'calculation_method' => 'Estimated as 20% of revenue'
                ],
                'calculations' => [
                    'gross_profit' => $grossProfit,
                    'gross_profit_margin' => $grossProfitMargin,
                    'operating_expenses' => $operatingExpenses,
                    'net_profit' => $netProfit,
                    'net_profit_margin' => $netProfitMargin
                ],
                'monthly_breakdown' => $monthlyBreakdown->toArray()
            ]
        ];
    }

    /**
     * Generate Balance Sheet report
     */
    protected function generateBalanceSheetReport(): array
    {
        // Assets
        $currentAssets = Account::where('type', 'asset')
            ->where(function ($query) {
                $query->where('code', 'like', '1%')
                      ->where('code', 'not like', '15%'); // Exclude fixed assets
            })->get();
        $currentAssetsData = $this->getAccountBalances($currentAssets, 'debit');
        $totalCurrentAssets = $currentAssetsData->sum('balance');

        $fixedAssets = Account::where('type', 'asset')
            ->where('code', 'like', '15%')
            ->get();
        $fixedAssetsData = $this->getAccountBalances($fixedAssets, 'debit');
        $totalFixedAssets = $fixedAssetsData->sum('balance');

        $totalAssets = $totalCurrentAssets + $totalFixedAssets;

        // Liabilities
        $currentLiabilities = Account::where('type', 'liability')
            ->where('code', 'like', '2%')
            ->get();
        $currentLiabilitiesData = $this->getAccountBalances($currentLiabilities, 'credit');
        $totalCurrentLiabilities = $currentLiabilitiesData->sum('balance');

        $longTermLiabilities = Account::where('type', 'liability')
            ->where('code', 'like', '25%')
            ->get();
        $longTermLiabilitiesData = $this->getAccountBalances($longTermLiabilities, 'credit');
        $totalLongTermLiabilities = $longTermLiabilitiesData->sum('balance');

        $totalLiabilities = $totalCurrentLiabilities + $totalLongTermLiabilities;

        // Equity
        $equityAccounts = Account::where('type', 'equity')->get();
        $equityData = $this->getAccountBalances($equityAccounts, 'credit');
        $totalEquity = $equityData->sum('balance');

        // Add current period profit to equity
        $currentPeriodProfit = $this->getCurrentPeriodProfit();
        $totalEquity += $currentPeriodProfit;

        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        // Financial ratios
        $currentRatio = $totalCurrentLiabilities > 0 ? $totalCurrentAssets / $totalCurrentLiabilities : 0;
        $debtToEquityRatio = $totalEquity > 0 ? $totalLiabilities / $totalEquity : 0;

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.balance_sheet'),
            'type' => 'financial',
            'subtype' => 'balance_sheet',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_assets' => [
                    'value' => $totalAssets,
                    'formatted' => $this->formatCurrency($totalAssets),
                    'label' => $this->trans('reports.total_assets')
                ],
                'total_liabilities' => [
                    'value' => $totalLiabilities,
                    'formatted' => $this->formatCurrency($totalLiabilities),
                    'label' => $this->trans('reports.total_liabilities')
                ],
                'total_equity' => [
                    'value' => $totalEquity,
                    'formatted' => $this->formatCurrency($totalEquity),
                    'label' => $this->trans('reports.total_equity')
                ],
                'balance_check' => [
                    'balanced' => abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01,
                    'difference' => $totalAssets - $totalLiabilitiesAndEquity
                ]
            ],
            'ratios' => [
                'current_ratio' => [
                    'value' => $currentRatio,
                    'formatted' => number_format($currentRatio, 2),
                    'label' => $this->trans('reports.current_ratio')
                ],
                'debt_to_equity' => [
                    'value' => $debtToEquityRatio,
                    'formatted' => number_format($debtToEquityRatio, 2),
                    'label' => $this->trans('reports.debt_to_equity_ratio')
                ]
            ],
            'charts' => [
                'assets_breakdown' => $this->generateChartData(collect([
                    ['category' => 'Current Assets', 'amount' => $totalCurrentAssets],
                    ['category' => 'Fixed Assets', 'amount' => $totalFixedAssets]
                ]), 'pie', [
                    'title' => $this->trans('reports.assets_breakdown'),
                    'label_field' => 'category',
                    'value_field' => 'amount'
                ]),
                'liabilities_equity' => $this->generateChartData(collect([
                    ['category' => 'Current Liabilities', 'amount' => $totalCurrentLiabilities],
                    ['category' => 'Long-term Liabilities', 'amount' => $totalLongTermLiabilities],
                    ['category' => 'Equity', 'amount' => $totalEquity]
                ]), 'pie', [
                    'title' => $this->trans('reports.liabilities_equity_breakdown'),
                    'label_field' => 'category',
                    'value_field' => 'amount'
                ])
            ],
            'data' => [
                'assets' => [
                    'current_assets' => [
                        'accounts' => $currentAssetsData->toArray(),
                        'total' => $totalCurrentAssets
                    ],
                    'fixed_assets' => [
                        'accounts' => $fixedAssetsData->toArray(),
                        'total' => $totalFixedAssets
                    ],
                    'total' => $totalAssets
                ],
                'liabilities' => [
                    'current_liabilities' => [
                        'accounts' => $currentLiabilitiesData->toArray(),
                        'total' => $totalCurrentLiabilities
                    ],
                    'long_term_liabilities' => [
                        'accounts' => $longTermLiabilitiesData->toArray(),
                        'total' => $totalLongTermLiabilities
                    ],
                    'total' => $totalLiabilities
                ],
                'equity' => [
                    'accounts' => $equityData->toArray(),
                    'current_period_profit' => $currentPeriodProfit,
                    'total' => $totalEquity
                ]
            ]
        ];
    }

    /**
     * Generate Cash Flow report
     */
    protected function generateCashFlowReport(): array
    {
        // Operating Activities
        $netIncome = $this->getCurrentPeriodProfit();
        
        // Get cash accounts
        $cashAccounts = Account::where('type', 'asset')
            ->where(function ($query) {
                $query->where('name', 'like', '%cash%')
                      ->orWhere('name', 'like', '%bank%');
            })->get();

        $operatingCashFlow = $this->getOperatingCashFlow();
        $investingCashFlow = $this->getInvestingCashFlow();
        $financingCashFlow = $this->getFinancingCashFlow();

        $netCashFlow = $operatingCashFlow + $investingCashFlow + $financingCashFlow;

        // Monthly cash flow
        $monthlyCashFlow = $this->getMonthlyCashFlow();

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.cash_flow_statement'),
            'type' => 'financial',
            'subtype' => 'cash_flow',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'operating_cash_flow' => [
                    'value' => $operatingCashFlow,
                    'formatted' => $this->formatCurrency($operatingCashFlow),
                    'label' => $this->trans('reports.operating_cash_flow')
                ],
                'investing_cash_flow' => [
                    'value' => $investingCashFlow,
                    'formatted' => $this->formatCurrency($investingCashFlow),
                    'label' => $this->trans('reports.investing_cash_flow')
                ],
                'financing_cash_flow' => [
                    'value' => $financingCashFlow,
                    'formatted' => $this->formatCurrency($financingCashFlow),
                    'label' => $this->trans('reports.financing_cash_flow')
                ],
                'net_cash_flow' => [
                    'value' => $netCashFlow,
                    'formatted' => $this->formatCurrency($netCashFlow),
                    'label' => $this->trans('reports.net_cash_flow')
                ]
            ],
            'charts' => [
                'monthly_cash_flow' => $this->generateChartData($monthlyCashFlow, 'line', [
                    'title' => $this->trans('reports.monthly_cash_flow'),
                    'label_field' => 'month',
                    'value_field' => 'net_cash_flow',
                    'dataset_label' => $this->trans('reports.net_cash_flow')
                ]),
                'cash_flow_breakdown' => $this->generateChartData(collect([
                    ['category' => 'Operating', 'amount' => $operatingCashFlow],
                    ['category' => 'Investing', 'amount' => $investingCashFlow],
                    ['category' => 'Financing', 'amount' => $financingCashFlow]
                ]), 'bar', [
                    'title' => $this->trans('reports.cash_flow_by_activity'),
                    'label_field' => 'category',
                    'value_field' => 'amount',
                    'dataset_label' => $this->trans('reports.cash_flow')
                ])
            ],
            'data' => [
                'operating_activities' => [
                    'net_income' => $netIncome,
                    'adjustments' => [],
                    'working_capital_changes' => [],
                    'total' => $operatingCashFlow
                ],
                'investing_activities' => [
                    'total' => $investingCashFlow
                ],
                'financing_activities' => [
                    'total' => $financingCashFlow
                ],
                'monthly_breakdown' => $monthlyCashFlow->toArray()
            ]
        ];
    }

    /**
     * Generate Trial Balance report
     */
    protected function generateTrialBalanceReport(): array
    {
        $accounts = Account::with(['transactions' => function ($query) {
            $query->whereBetween('transaction_date', [$this->startDate, $this->endDate]);
        }])->get();

        $trialBalanceData = $accounts->map(function ($account) {
            $debitTotal = $account->transactions->sum('debit_amount');
            $creditTotal = $account->transactions->sum('credit_amount');
            
            $balance = 0;
            $balanceType = '';
            
            if (in_array($account->type, ['asset', 'expense'])) {
                $balance = $debitTotal - $creditTotal;
                $balanceType = $balance >= 0 ? 'debit' : 'credit';
            } else {
                $balance = $creditTotal - $debitTotal;
                $balanceType = $balance >= 0 ? 'credit' : 'debit';
            }

            return [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'account_type' => $account->type,
                'debit_total' => $debitTotal,
                'credit_total' => $creditTotal,
                'balance' => abs($balance),
                'balance_type' => $balanceType
            ];
        })->filter(function ($account) {
            return $account['debit_total'] > 0 || $account['credit_total'] > 0 || $account['balance'] > 0;
        });

        $totalDebits = $trialBalanceData->sum('debit_total');
        $totalCredits = $trialBalanceData->sum('credit_total');
        $totalDebitBalances = $trialBalanceData->where('balance_type', 'debit')->sum('balance');
        $totalCreditBalances = $trialBalanceData->where('balance_type', 'credit')->sum('balance');

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.trial_balance'),
            'type' => 'financial',
            'subtype' => 'trial_balance',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_debits' => [
                    'value' => $totalDebits,
                    'formatted' => $this->formatCurrency($totalDebits),
                    'label' => $this->trans('reports.total_debits')
                ],
                'total_credits' => [
                    'value' => $totalCredits,
                    'formatted' => $this->formatCurrency($totalCredits),
                    'label' => $this->trans('reports.total_credits')
                ],
                'balance_check' => [
                    'balanced' => abs($totalDebits - $totalCredits) < 0.01,
                    'difference' => $totalDebits - $totalCredits
                ]
            ],
            'data' => [
                'accounts' => $trialBalanceData->toArray(),
                'totals' => [
                    'debit_total' => $totalDebits,
                    'credit_total' => $totalCredits,
                    'debit_balance_total' => $totalDebitBalances,
                    'credit_balance_total' => $totalCreditBalances
                ]
            ]
        ];
    }

    /**
     * Get account balances for a collection of accounts
     */
    protected function getAccountBalances(Collection $accounts, string $normalBalance): Collection
    {
        return $accounts->map(function ($account) use ($normalBalance) {
            $transactions = Transaction::join('transaction_entries', 'transactions.id', '=', 'transaction_entries.transaction_id')
                ->where('transaction_entries.account_id', $account->id)
                ->whereBetween('transactions.transaction_date', [$this->startDate, $this->endDate])
                ->select('transaction_entries.debit_amount', 'transaction_entries.credit_amount')
                ->get();

            $debitTotal = $transactions->sum('debit_amount');
            $creditTotal = $transactions->sum('credit_amount');

            $balance = $normalBalance === 'debit' 
                ? $debitTotal - $creditTotal 
                : $creditTotal - $debitTotal;

            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'balance' => max(0, $balance), // Only show positive balances
                'debit_total' => $debitTotal,
                'credit_total' => $creditTotal
            ];
        })->filter(function ($account) {
            return $account['balance'] > 0;
        });
    }

    /**
     * Get previous period P&L data
     */
    protected function getPreviousPeriodPL(): array
    {
        $daysDiff = $this->startDate->diffInDays($this->endDate);
        $previousStart = $this->startDate->copy()->subDays($daysDiff + 1);
        $previousEnd = $this->startDate->copy()->subDay();

        $previousInvoices = Invoice::whereBetween('issue_date', [$previousStart, $previousEnd])
            ->where('status', '!=', 'cancelled')
            ->with('items.inventoryItem')
            ->get();

        $previousRevenue = $previousInvoices->sum('total_amount');
        
        $previousCOGS = $previousInvoices->flatMap->items->sum(function ($item) {
            $costPrice = $item->inventoryItem->cost_price ?? ($item->unit_price * 0.6);
            return $costPrice * $item->quantity;
        });

        $previousOperatingExpenses = $previousRevenue * 0.20;
        $previousGrossProfit = $previousRevenue - $previousCOGS;
        $previousNetProfit = $previousGrossProfit - $previousOperatingExpenses;

        return [
            'revenue' => $previousRevenue,
            'gross_profit' => $previousGrossProfit,
            'net_profit' => $previousNetProfit
        ];
    }

    /**
     * Get monthly P&L breakdown
     */
    protected function getMonthlyPLBreakdown(): Collection
    {
        $monthlyData = collect();
        
        $start = $this->startDate->copy()->startOfMonth();
        $end = $this->endDate->copy()->endOfMonth();
        
        while ($start <= $end) {
            $monthStart = $start->copy()->startOfMonth();
            $monthEnd = $start->copy()->endOfMonth();
            
            $monthlyInvoices = Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])
                ->where('status', '!=', 'cancelled')
                ->with('items.inventoryItem')
                ->get();
            
            $monthlyRevenue = $monthlyInvoices->sum('total_amount');
            $monthlyCOGS = $monthlyInvoices->flatMap->items->sum(function ($item) {
                $costPrice = $item->inventoryItem->cost_price ?? ($item->unit_price * 0.6);
                return $costPrice * $item->quantity;
            });
            
            $monthlyOperatingExpenses = $monthlyRevenue * 0.20;
            $monthlyGrossProfit = $monthlyRevenue - $monthlyCOGS;
            $monthlyNetProfit = $monthlyGrossProfit - $monthlyOperatingExpenses;
            
            $monthlyData->push([
                'month' => $start->format('Y-m'),
                'month_name' => $start->format('M Y'),
                'revenue' => $monthlyRevenue,
                'cogs' => $monthlyCOGS,
                'gross_profit' => $monthlyGrossProfit,
                'operating_expenses' => $monthlyOperatingExpenses,
                'net_profit' => $monthlyNetProfit
            ]);
            
            $start->addMonth();
        }
        
        return $monthlyData;
    }

    /**
     * Get current period profit
     */
    protected function getCurrentPeriodProfit(): float
    {
        $revenue = Account::where('type', 'revenue')->get();
        $revenueTotal = $this->getAccountBalances($revenue, 'credit')->sum('balance');

        $expenses = Account::where('type', 'expense')->get();
        $expenseTotal = $this->getAccountBalances($expenses, 'debit')->sum('balance');

        return $revenueTotal - $expenseTotal;
    }

    /**
     * Get operating cash flow
     */
    protected function getOperatingCashFlow(): float
    {
        // This would calculate operating cash flow
        // For now, return current period profit as approximation
        return $this->getCurrentPeriodProfit();
    }

    /**
     * Get investing cash flow
     */
    protected function getInvestingCashFlow(): float
    {
        // This would calculate investing cash flow
        return 0;
    }

    /**
     * Get financing cash flow
     */
    protected function getFinancingCashFlow(): float
    {
        // This would calculate financing cash flow
        return 0;
    }

    /**
     * Get monthly cash flow
     */
    protected function getMonthlyCashFlow(): Collection
    {
        // This would calculate monthly cash flow
        return collect([]);
    }

    /**
     * Calculate percentage change between current and previous values
     */
    protected function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return (($current - $previous) / $previous) * 100;
    }
}