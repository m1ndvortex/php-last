<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class BudgetPlanningService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Create a new budget
     */
    public function createBudget(array $data): Budget
    {
        return DB::transaction(function () use ($data) {
            $budget = Budget::create([
                'name' => $data['name'],
                'name_persian' => $data['name_persian'] ?? null,
                'description' => $data['description'] ?? null,
                'budget_year' => $data['budget_year'],
                'start_date' => Carbon::parse($data['start_date']),
                'end_date' => Carbon::parse($data['end_date']),
                'status' => $data['status'] ?? 'draft',
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => auth()->id(),
                'approved_by' => null,
                'approved_at' => null,
            ]);

            // Create budget lines
            if (isset($data['budget_lines'])) {
                foreach ($data['budget_lines'] as $lineData) {
                    $this->createBudgetLine($budget, $lineData);
                }
            }

            AuditLog::logActivity($budget, 'budget_created');

            return $budget;
        });
    }

    /**
     * Create budget line item
     */
    public function createBudgetLine(Budget $budget, array $data): BudgetLine
    {
        $account = Account::findOrFail($data['account_id']);

        return BudgetLine::create([
            'budget_id' => $budget->id,
            'account_id' => $data['account_id'],
            'account_code' => $account->code,
            'account_name' => $account->name,
            'category' => $data['category'] ?? $account->type,
            'subcategory' => $data['subcategory'] ?? null,
            'cost_center_id' => $data['cost_center_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            
            // Monthly budget amounts
            'january' => $data['january'] ?? 0,
            'february' => $data['february'] ?? 0,
            'march' => $data['march'] ?? 0,
            'april' => $data['april'] ?? 0,
            'may' => $data['may'] ?? 0,
            'june' => $data['june'] ?? 0,
            'july' => $data['july'] ?? 0,
            'august' => $data['august'] ?? 0,
            'september' => $data['september'] ?? 0,
            'october' => $data['october'] ?? 0,
            'november' => $data['november'] ?? 0,
            'december' => $data['december'] ?? 0,
            
            'total_budget' => $data['total_budget'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Generate budget from historical data
     */
    public function generateBudgetFromHistory(int $baseYear, int $targetYear, float $growthRate = 0): Budget
    {
        $budget = $this->createBudget([
            'name' => "Budget {$targetYear} (Generated from {$baseYear})",
            'name_persian' => "بودجه {$targetYear} (تولید شده از {$baseYear})",
            'description' => "Auto-generated budget based on {$baseYear} actuals with {$growthRate}% growth",
            'budget_year' => $targetYear,
            'start_date' => "{$targetYear}-01-01",
            'end_date' => "{$targetYear}-12-31",
            'status' => 'draft',
        ]);

        // Get historical data for base year
        $historicalData = $this->getHistoricalData($baseYear);

        foreach ($historicalData as $accountId => $monthlyData) {
            $account = Account::find($accountId);
            if (!$account) continue;

            $budgetLineData = [
                'account_id' => $accountId,
                'category' => $account->type,
                'january' => $monthlyData[1] * (1 + $growthRate / 100),
                'february' => $monthlyData[2] * (1 + $growthRate / 100),
                'march' => $monthlyData[3] * (1 + $growthRate / 100),
                'april' => $monthlyData[4] * (1 + $growthRate / 100),
                'may' => $monthlyData[5] * (1 + $growthRate / 100),
                'june' => $monthlyData[6] * (1 + $growthRate / 100),
                'july' => $monthlyData[7] * (1 + $growthRate / 100),
                'august' => $monthlyData[8] * (1 + $growthRate / 100),
                'september' => $monthlyData[9] * (1 + $growthRate / 100),
                'october' => $monthlyData[10] * (1 + $growthRate / 100),
                'november' => $monthlyData[11] * (1 + $growthRate / 100),
                'december' => $monthlyData[12] * (1 + $growthRate / 100),
            ];

            $budgetLineData['total_budget'] = array_sum(array_slice($budgetLineData, 2, 12));
            $this->createBudgetLine($budget, $budgetLineData);
        }

        return $budget;
    }

    /**
     * Perform budget variance analysis
     */
    public function performVarianceAnalysis(Budget $budget, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?? now();
        $currentMonth = $asOfDate->month;
        
        $analysis = [
            'budget_id' => $budget->id,
            'budget_name' => $budget->name,
            'analysis_date' => $asOfDate->toDateString(),
            'period_covered' => "January - " . $asOfDate->format('F Y'),
            'summary' => [
                'total_budget_ytd' => 0,
                'total_actual_ytd' => 0,
                'total_variance_ytd' => 0,
                'variance_percentage' => 0,
            ],
            'categories' => [],
            'accounts' => [],
        ];

        $budgetLines = $budget->budgetLines()->with('account')->get();
        $categoryTotals = [];

        foreach ($budgetLines as $line) {
            // Calculate YTD budget amount
            $ytdBudget = $this->calculateYTDBudget($line, $currentMonth);
            
            // Get actual amounts from transactions
            $ytdActual = $this->getActualAmount($line->account_id, $budget->start_date, $asOfDate);
            
            // Calculate variance
            $variance = $ytdActual - $ytdBudget;
            $variancePercentage = $ytdBudget != 0 ? ($variance / $ytdBudget) * 100 : 0;

            // Account level analysis
            $accountAnalysis = [
                'account_id' => $line->account_id,
                'account_code' => $line->account_code,
                'account_name' => $line->account_name,
                'category' => $line->category,
                'ytd_budget' => $ytdBudget,
                'ytd_actual' => $ytdActual,
                'variance' => $variance,
                'variance_percentage' => $variancePercentage,
                'status' => $this->getVarianceStatus($variancePercentage, $line->category),
                'monthly_breakdown' => $this->getMonthlyBreakdown($line, $asOfDate),
            ];

            $analysis['accounts'][] = $accountAnalysis;

            // Aggregate by category
            if (!isset($categoryTotals[$line->category])) {
                $categoryTotals[$line->category] = [
                    'category' => $line->category,
                    'ytd_budget' => 0,
                    'ytd_actual' => 0,
                    'variance' => 0,
                    'account_count' => 0,
                ];
            }

            $categoryTotals[$line->category]['ytd_budget'] += $ytdBudget;
            $categoryTotals[$line->category]['ytd_actual'] += $ytdActual;
            $categoryTotals[$line->category]['variance'] += $variance;
            $categoryTotals[$line->category]['account_count']++;

            // Update summary totals
            $analysis['summary']['total_budget_ytd'] += $ytdBudget;
            $analysis['summary']['total_actual_ytd'] += $ytdActual;
            $analysis['summary']['total_variance_ytd'] += $variance;
        }

        // Calculate category variance percentages
        foreach ($categoryTotals as &$category) {
            $category['variance_percentage'] = $category['ytd_budget'] != 0 
                ? ($category['variance'] / $category['ytd_budget']) * 100 
                : 0;
            $category['status'] = $this->getVarianceStatus($category['variance_percentage'], $category['category']);
        }

        $analysis['categories'] = array_values($categoryTotals);

        // Calculate overall variance percentage
        $analysis['summary']['variance_percentage'] = $analysis['summary']['total_budget_ytd'] != 0 
            ? ($analysis['summary']['total_variance_ytd'] / $analysis['summary']['total_budget_ytd']) * 100 
            : 0;

        return $analysis;
    }

    /**
     * Create budget revision
     */
    public function createBudgetRevision(Budget $originalBudget, array $revisions, string $reason): Budget
    {
        return DB::transaction(function () use ($originalBudget, $revisions, $reason) {
            // Create new budget version
            $revisedBudget = $this->createBudget([
                'name' => $originalBudget->name . ' (Revision ' . ($originalBudget->revision_number + 1) . ')',
                'name_persian' => $originalBudget->name_persian . ' (تجدید نظر ' . ($originalBudget->revision_number + 1) . ')',
                'description' => $originalBudget->description . "\n\nRevision Reason: " . $reason,
                'budget_year' => $originalBudget->budget_year,
                'start_date' => $originalBudget->start_date,
                'end_date' => $originalBudget->end_date,
                'status' => 'draft',
                'currency' => $originalBudget->currency,
                'parent_budget_id' => $originalBudget->id,
                'revision_number' => $originalBudget->revision_number + 1,
            ]);

            // Copy budget lines with revisions
            foreach ($originalBudget->budgetLines as $originalLine) {
                $lineData = $originalLine->toArray();
                unset($lineData['id'], $lineData['budget_id'], $lineData['created_at'], $lineData['updated_at']);

                // Apply revisions if specified for this account
                if (isset($revisions[$originalLine->account_id])) {
                    $revision = $revisions[$originalLine->account_id];
                    foreach ($revision as $field => $value) {
                        if (in_array($field, ['january', 'february', 'march', 'april', 'may', 'june', 
                                            'july', 'august', 'september', 'october', 'november', 'december'])) {
                            $lineData[$field] = $value;
                        }
                    }
                    // Recalculate total
                    $lineData['total_budget'] = array_sum([
                        $lineData['january'], $lineData['february'], $lineData['march'], $lineData['april'],
                        $lineData['may'], $lineData['june'], $lineData['july'], $lineData['august'],
                        $lineData['september'], $lineData['october'], $lineData['november'], $lineData['december']
                    ]);
                }

                $this->createBudgetLine($revisedBudget, $lineData);
            }

            // Mark original budget as superseded
            $originalBudget->update(['status' => 'superseded']);

            AuditLog::logActivity($revisedBudget, 'budget_revised', null, [
                'original_budget_id' => $originalBudget->id,
                'reason' => $reason,
                'revisions_count' => count($revisions),
            ]);

            return $revisedBudget;
        });
    }

    /**
     * Generate budget forecast
     */
    public function generateBudgetForecast(Budget $budget, ?Carbon $forecastDate = null): array
    {
        $forecastDate = $forecastDate ?? now();
        $remainingMonths = 12 - $forecastDate->month;
        
        $forecast = [
            'budget_id' => $budget->id,
            'forecast_date' => $forecastDate->toDateString(),
            'remaining_months' => $remainingMonths,
            'accounts' => [],
            'summary' => [
                'total_annual_budget' => 0,
                'total_ytd_actual' => 0,
                'total_forecast_remaining' => 0,
                'total_forecast_annual' => 0,
                'total_variance_forecast' => 0,
            ],
        ];

        foreach ($budget->budgetLines as $line) {
            $ytdActual = $this->getActualAmount($line->account_id, $budget->start_date, $forecastDate);
            $ytdBudget = $this->calculateYTDBudget($line, $forecastDate->month);
            
            // Calculate trend-based forecast
            $monthsElapsed = $forecastDate->month;
            $averageMonthlyActual = $monthsElapsed > 0 ? $ytdActual / $monthsElapsed : 0;
            $forecastRemaining = $averageMonthlyActual * $remainingMonths;
            $forecastAnnual = $ytdActual + $forecastRemaining;
            
            $accountForecast = [
                'account_id' => $line->account_id,
                'account_name' => $line->account_name,
                'annual_budget' => $line->total_budget,
                'ytd_budget' => $ytdBudget,
                'ytd_actual' => $ytdActual,
                'average_monthly_actual' => $averageMonthlyActual,
                'forecast_remaining' => $forecastRemaining,
                'forecast_annual' => $forecastAnnual,
                'variance_forecast' => $forecastAnnual - $line->total_budget,
                'variance_percentage' => $line->total_budget != 0 ? (($forecastAnnual - $line->total_budget) / $line->total_budget) * 100 : 0,
            ];

            $forecast['accounts'][] = $accountForecast;

            // Update summary
            $forecast['summary']['total_annual_budget'] += $line->total_budget;
            $forecast['summary']['total_ytd_actual'] += $ytdActual;
            $forecast['summary']['total_forecast_remaining'] += $forecastRemaining;
            $forecast['summary']['total_forecast_annual'] += $forecastAnnual;
            $forecast['summary']['total_variance_forecast'] += $accountForecast['variance_forecast'];
        }

        return $forecast;
    }

    protected function getHistoricalData(int $year): array
    {
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        $transactions = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->with('entries.account')
            ->get();

        $data = [];

        foreach ($transactions as $transaction) {
            $month = $transaction->transaction_date->month;
            
            foreach ($transaction->entries as $entry) {
                $accountId = $entry->account_id;
                
                if (!isset($data[$accountId])) {
                    $data[$accountId] = array_fill(1, 12, 0);
                }

                // For revenue and liability accounts, use credit amounts
                // For asset and expense accounts, use debit amounts
                $account = $entry->account;
                if (in_array($account->type, ['revenue', 'liability', 'equity'])) {
                    $data[$accountId][$month] += $entry->credit_amount;
                } else {
                    $data[$accountId][$month] += $entry->debit_amount;
                }
            }
        }

        return $data;
    }

    protected function calculateYTDBudget(BudgetLine $line, int $currentMonth): float
    {
        $months = ['january', 'february', 'march', 'april', 'may', 'june',
                  'july', 'august', 'september', 'october', 'november', 'december'];
        
        $ytdBudget = 0;
        for ($i = 0; $i < $currentMonth; $i++) {
            $ytdBudget += $line->{$months[$i]};
        }

        return $ytdBudget;
    }

    protected function getActualAmount(int $accountId, Carbon $startDate, Carbon $endDate): float
    {
        $account = Account::find($accountId);
        if (!$account) return 0;

        $entries = $account->transactionEntries()
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transaction_date', [$startDate, $endDate]);
            })
            ->get();

        $total = 0;
        foreach ($entries as $entry) {
            if (in_array($account->type, ['revenue', 'liability', 'equity'])) {
                $total += $entry->credit_amount - $entry->debit_amount;
            } else {
                $total += $entry->debit_amount - $entry->credit_amount;
            }
        }

        return $total;
    }

    protected function getVarianceStatus(float $variancePercentage, string $accountType): string
    {
        $threshold = 10; // 10% variance threshold

        if (abs($variancePercentage) <= $threshold) {
            return 'on_track';
        }

        // For revenue accounts, positive variance is good
        // For expense accounts, negative variance is good
        if ($accountType === 'revenue') {
            return $variancePercentage > 0 ? 'favorable' : 'unfavorable';
        } else {
            return $variancePercentage < 0 ? 'favorable' : 'unfavorable';
        }
    }

    protected function getMonthlyBreakdown(BudgetLine $line, Carbon $asOfDate): array
    {
        $months = ['january', 'february', 'march', 'april', 'may', 'june',
                  'july', 'august', 'september', 'october', 'november', 'december'];
        
        $breakdown = [];
        
        for ($i = 0; $i < 12; $i++) {
            $monthDate = Carbon::create($line->budget->budget_year, $i + 1, 1);
            $isCompleted = $monthDate->lte($asOfDate);
            
            $actual = 0;
            if ($isCompleted) {
                $monthStart = $monthDate->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();
                $actual = $this->getActualAmount($line->account_id, $monthStart, $monthEnd);
            }

            $breakdown[] = [
                'month' => $months[$i],
                'month_name' => $monthDate->format('F'),
                'budget' => $line->{$months[$i]},
                'actual' => $actual,
                'variance' => $actual - $line->{$months[$i]},
                'is_completed' => $isCompleted,
            ];
        }

        return $breakdown;
    }
}