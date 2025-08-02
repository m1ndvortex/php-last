<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
    private FinancialReportService $reportService;

    public function __construct(FinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = $request->has('as_of_date') 
            ? Carbon::parse($request->as_of_date)
            : null;

        $report = $this->reportService->generateTrialBalance($asOfDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = $request->has('as_of_date') 
            ? Carbon::parse($request->as_of_date)
            : null;

        $report = $this->reportService->generateBalanceSheet($asOfDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date') 
            ? Carbon::parse($request->start_date)
            : null;
            
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)
            : null;

        $report = $this->reportService->generateIncomeStatement($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function cashFlowStatement(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->has('start_date') 
            ? Carbon::parse($request->start_date)
            : null;
            
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)
            : null;

        $report = $this->reportService->generateCashFlowStatement($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function agedReceivables(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = $request->has('as_of_date') 
            ? Carbon::parse($request->as_of_date)
            : null;

        $report = $this->reportService->generateAgedReceivables($asOfDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function agedPayables(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = $request->has('as_of_date') 
            ? Carbon::parse($request->as_of_date)
            : null;

        $report = $this->reportService->generateAgedPayables($asOfDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function customReport(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => 'required|in:account_summary,transaction_summary,cost_center_summary',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'account_types' => 'nullable|array',
            'account_types.*' => 'in:asset,liability,equity,revenue,expense',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $startDate = $request->has('start_date') 
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfYear();
            
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::now();

        $report = match ($request->report_type) {
            'account_summary' => $this->generateAccountSummary($startDate, $endDate, $request->account_types),
            'transaction_summary' => $this->generateTransactionSummary($startDate, $endDate),
            'cost_center_summary' => $this->generateCostCenterSummary($startDate, $endDate, $request->cost_center_id),
            default => ['error' => 'Invalid report type']
        };

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    private function generateAccountSummary(Carbon $startDate, Carbon $endDate, ?array $accountTypes = null): array
    {
        $query = \App\Models\Account::active();
        
        if ($accountTypes) {
            $query->whereIn('type', $accountTypes);
        }

        $accounts = $query->with(['transactionEntries' => function ($q) use ($startDate, $endDate) {
            $q->whereHas('transaction', function ($tq) use ($startDate, $endDate) {
                $tq->whereBetween('transaction_date', [$startDate, $endDate]);
            });
        }])->get();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'accounts' => $accounts->map(function ($account) {
                $entries = $account->transactionEntries;
                $totalDebits = $entries->sum('debit_amount');
                $totalCredits = $entries->sum('credit_amount');
                
                return [
                    'code' => $account->code,
                    'name' => $account->localized_name,
                    'type' => $account->type,
                    'total_debits' => $totalDebits,
                    'total_credits' => $totalCredits,
                    'net_activity' => $totalDebits - $totalCredits,
                    'current_balance' => $account->current_balance,
                ];
            }),
        ];
    }

    private function generateTransactionSummary(Carbon $startDate, Carbon $endDate): array
    {
        $transactions = \App\Models\Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->with('entries.account')
            ->get();

        $summary = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('total_amount'),
            'by_type' => [],
            'by_month' => [],
        ];

        // Group by type
        $byType = $transactions->groupBy('type');
        foreach ($byType as $type => $typeTransactions) {
            $summary['by_type'][$type] = [
                'count' => $typeTransactions->count(),
                'total_amount' => $typeTransactions->sum('total_amount'),
            ];
        }

        // Group by month
        $byMonth = $transactions->groupBy(function ($transaction) {
            return $transaction->transaction_date->format('Y-m');
        });
        
        foreach ($byMonth as $month => $monthTransactions) {
            $summary['by_month'][$month] = [
                'count' => $monthTransactions->count(),
                'total_amount' => $monthTransactions->sum('total_amount'),
            ];
        }

        return $summary;
    }

    private function generateCostCenterSummary(Carbon $startDate, Carbon $endDate, ?int $costCenterId = null): array
    {
        $query = \App\Models\Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNotNull('cost_center_id');
            
        if ($costCenterId) {
            $query->where('cost_center_id', $costCenterId);
        }

        $transactions = $query->with('costCenter')->get();

        $summary = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'cost_centers' => [],
        ];

        $byCostCenter = $transactions->groupBy('cost_center_id');
        
        foreach ($byCostCenter as $costCenterId => $centerTransactions) {
            $costCenter = $centerTransactions->first()->costCenter;
            
            $summary['cost_centers'][] = [
                'id' => $costCenter->id,
                'name' => $costCenter->localized_name,
                'code' => $costCenter->code,
                'transaction_count' => $centerTransactions->count(),
                'total_amount' => $centerTransactions->sum('total_amount'),
            ];
        }

        return $summary;
    }
}