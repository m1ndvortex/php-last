<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get available report types
     */
    public function getReportTypes(): JsonResponse
    {
        $reportTypes = [
            'sales' => [
                'name' => 'Sales Reports',
                'description' => 'Sales analytics, trends, and performance metrics',
                'types' => ['summary', 'detailed', 'by_period', 'by_customer', 'by_product']
            ],
            'inventory' => [
                'name' => 'Inventory Reports',
                'description' => 'Stock analysis, movements, and valuation',
                'types' => ['stock_levels', 'movements', 'valuation', 'aging', 'reorder']
            ],
            'financial' => [
                'name' => 'Financial Reports',
                'description' => 'P&L, balance sheet, cash flow, and financial analysis',
                'types' => ['profit_loss', 'balance_sheet', 'cash_flow', 'trial_balance']
            ],
            'customer' => [
                'name' => 'Customer Reports',
                'description' => 'Customer aging, purchase history, and analytics',
                'types' => ['aging', 'purchase_history', 'communication_log', 'analytics']
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $reportTypes
        ]);
    }

    /**
     * Generate a report
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:sales,inventory,financial,customer',
            'subtype' => 'required|string',
            'date_range' => 'required|array',
            'date_range.start' => 'required|date',
            'date_range.end' => 'required|date|after_or_equal:date_range.start',
            'filters' => 'sometimes|array',
            'language' => 'sometimes|string|in:en,fa',
            'format' => 'sometimes|string|in:json,pdf,excel,csv'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $parameters = [
                'type' => $request->input('type'),
                'subtype' => $request->input('subtype'),
                'date_range' => $request->input('date_range'),
                'filters' => $request->input('filters', []),
                'language' => $request->input('language', 'en'),
                'format' => $request->input('format', 'json')
            ];

            $report = $this->reportService->generateReport($parameters);

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report to file
     */
    public function exportReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report_id' => 'required|string',
            'format' => 'required|string|in:pdf,excel,csv'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $exportPath = $this->reportService->exportReport(
                $request->input('report_id'),
                $request->input('format')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => url('storage/reports/' . basename($exportPath)),
                    'filename' => basename($exportPath)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule a report
     */
    public function scheduleReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:sales,inventory,financial,customer',
            'subtype' => 'required|string',
            'parameters' => 'required|array',
            'schedule' => 'required|array',
            'schedule.frequency' => 'required|string|in:daily,weekly,monthly,quarterly',
            'schedule.time' => 'required|string',
            'delivery' => 'required|array',
            'delivery.method' => 'required|string|in:email,download',
            'delivery.recipients' => 'required_if:delivery.method,email|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = $this->reportService->scheduleReport($request->all());

            return response()->json([
                'success' => true,
                'data' => $schedule
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scheduled reports
     */
    public function getScheduledReports(): JsonResponse
    {
        try {
            $schedules = $this->reportService->getScheduledReports();

            return response()->json([
                'success' => true,
                'data' => $schedules
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get scheduled reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete scheduled report
     */
    public function deleteScheduledReport(string $id): JsonResponse
    {
        try {
            $this->reportService->deleteScheduledReport($id);

            return response()->json([
                'success' => true,
                'message' => 'Scheduled report deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete scheduled report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}