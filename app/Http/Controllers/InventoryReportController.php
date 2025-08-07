<?php

namespace App\Http\Controllers;

use App\Services\InventoryReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class InventoryReportController extends Controller
{
    private InventoryReportService $reportService;

    public function __construct(InventoryReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get inventory report grouped by category hierarchy
     */
    public function categoryHierarchyReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'main_category_id' => 'nullable|exists:categories,id',
            'category_id' => 'nullable|exists:categories,id',
            'include_subcategories' => 'nullable|boolean',
        ]);

        $filters = [
            'start_date' => $request->start_date ? Carbon::parse($request->start_date) : null,
            'end_date' => $request->end_date ? Carbon::parse($request->end_date) : null,
            'main_category_id' => $request->main_category_id,
            'category_id' => $request->category_id,
            'include_subcategories' => $request->boolean('include_subcategories', true),
        ];

        $report = $this->reportService->generateCategoryHierarchyReport($filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get category-based sales performance metrics
     */
    public function categorySalesPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'main_category_id' => 'nullable|exists:categories,id',
            'category_id' => 'nullable|exists:categories,id',
            'group_by' => 'nullable|in:main_category,subcategory,both',
        ]);

        $filters = [
            'start_date' => $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth(),
            'end_date' => $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth(),
            'main_category_id' => $request->main_category_id,
            'category_id' => $request->category_id,
            'group_by' => $request->group_by ?? 'both',
        ];

        $report = $this->reportService->generateCategorySalesPerformance($filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get category stock level reporting
     */
    public function categoryStockLevels(Request $request): JsonResponse
    {
        $request->validate([
            'main_category_id' => 'nullable|exists:categories,id',
            'category_id' => 'nullable|exists:categories,id',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'include_zero_stock' => 'nullable|boolean',
        ]);

        $filters = [
            'main_category_id' => $request->main_category_id,
            'category_id' => $request->category_id,
            'low_stock_threshold' => $request->low_stock_threshold ?? 10,
            'include_zero_stock' => $request->boolean('include_zero_stock', true),
        ];

        $report = $this->reportService->generateCategoryStockLevels($filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get gold purity analysis reports
     */
    public function goldPurityAnalysis(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'main_category_id' => 'nullable|exists:categories,id',
            'category_id' => 'nullable|exists:categories,id',
            'purity_range_min' => 'nullable|numeric|min:0|max:24',
            'purity_range_max' => 'nullable|numeric|min:0|max:24|gte:purity_range_min',
        ]);

        $filters = [
            'start_date' => $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth(),
            'end_date' => $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth(),
            'main_category_id' => $request->main_category_id,
            'category_id' => $request->category_id,
            'purity_range_min' => $request->purity_range_min,
            'purity_range_max' => $request->purity_range_max,
        ];

        $report = $this->reportService->generateGoldPurityAnalysis($filters);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get comprehensive inventory analytics
     */
    public function inventoryAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:week,month,quarter,year',
            'main_category_id' => 'nullable|exists:categories,id',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $period = $request->period ?? 'month';
        $filters = [
            'main_category_id' => $request->main_category_id,
            'category_id' => $request->category_id,
        ];

        $analytics = $this->reportService->generateInventoryAnalytics($period, $filters);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }
}