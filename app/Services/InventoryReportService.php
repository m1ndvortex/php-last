<?php

namespace App\Services;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class InventoryReportService
{
    private GoldPurityService $goldPurityService;

    public function __construct(GoldPurityService $goldPurityService)
    {
        $this->goldPurityService = $goldPurityService;
    }

    /**
     * Generate inventory report grouped by category hierarchy
     */
    public function generateCategoryHierarchyReport(array $filters): array
    {
        $query = InventoryItem::with(['mainCategory', 'subcategory']);

        // Apply filters
        if (isset($filters['main_category_id']) && $filters['main_category_id']) {
            $query->where('main_category_id', $filters['main_category_id']);
        }

        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        $items = $query->get();

        // Group by main category and subcategory
        $hierarchyData = [];
        
        foreach ($items as $item) {
            $mainCategoryId = $item->main_category_id ?? 'uncategorized';
            $subcategoryId = $item->category_id ?? 'uncategorized';
            
            $mainCategoryName = $item->mainCategory ? $item->mainCategory->localized_name : 'Uncategorized';
            $subcategoryName = $item->subcategory ? $item->subcategory->localized_name : 'Uncategorized';

            if (!isset($hierarchyData[$mainCategoryId])) {
                $hierarchyData[$mainCategoryId] = [
                    'id' => $mainCategoryId,
                    'name' => $mainCategoryName,
                    'total_items' => 0,
                    'total_quantity' => 0,
                    'total_value' => 0,
                    'total_cost' => 0,
                    'subcategories' => [],
                ];
            }

            if (!isset($hierarchyData[$mainCategoryId]['subcategories'][$subcategoryId])) {
                $hierarchyData[$mainCategoryId]['subcategories'][$subcategoryId] = [
                    'id' => $subcategoryId,
                    'name' => $subcategoryName,
                    'items' => [],
                    'total_items' => 0,
                    'total_quantity' => 0,
                    'total_value' => 0,
                    'total_cost' => 0,
                ];
            }

            // Add item data
            $itemValue = $item->quantity * $item->unit_price;
            $itemCost = $item->quantity * $item->cost_price;

            $hierarchyData[$mainCategoryId]['subcategories'][$subcategoryId]['items'][] = [
                'id' => $item->id,
                'name' => $item->localized_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'cost_price' => $item->cost_price,
                'total_value' => $itemValue,
                'total_cost' => $itemCost,
                'gold_purity' => $item->gold_purity,
                'weight' => $item->weight,
            ];

            // Update subcategory totals
            $hierarchyData[$mainCategoryId]['subcategories'][$subcategoryId]['total_items']++;
            $hierarchyData[$mainCategoryId]['subcategories'][$subcategoryId]['total_quantity'] += $item->quantity;
            $hierarchyData[$mainCategoryId]['subcategories'][$subcategoryId]['total_value'] += $itemValue;
            $hierarchyData[$mainCategoryId]['subcategories'][$subcategoryId]['total_cost'] += $itemCost;

            // Update main category totals
            $hierarchyData[$mainCategoryId]['total_items']++;
            $hierarchyData[$mainCategoryId]['total_quantity'] += $item->quantity;
            $hierarchyData[$mainCategoryId]['total_value'] += $itemValue;
            $hierarchyData[$mainCategoryId]['total_cost'] += $itemCost;
        }

        // Convert subcategories to arrays
        foreach ($hierarchyData as &$mainCategory) {
            $mainCategory['subcategories'] = array_values($mainCategory['subcategories']);
        }

        return [
            'filters' => $filters,
            'summary' => [
                'total_main_categories' => count($hierarchyData),
                'total_items' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'total_value' => $items->sum(function ($item) {
                    return $item->quantity * $item->unit_price;
                }),
                'total_cost' => $items->sum(function ($item) {
                    return $item->quantity * $item->cost_price;
                }),
            ],
            'categories' => array_values($hierarchyData),
        ];
    }

    /**
     * Generate category-based sales performance metrics
     */
    public function generateCategorySalesPerformance(array $filters): array
    {
        $query = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('inventory_items', 'invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->leftJoin('categories as main_categories', 'inventory_items.main_category_id', '=', 'main_categories.id')
            ->leftJoin('categories as subcategories', 'inventory_items.category_id', '=', 'subcategories.id')
            ->where('invoices.status', 'paid');

        // Apply date filters
        if (isset($filters['start_date']) && $filters['start_date']) {
            $query->where('invoices.issue_date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date']) && $filters['end_date']) {
            $query->where('invoices.issue_date', '<=', $filters['end_date']);
        }

        // Apply category filters
        if (isset($filters['main_category_id']) && $filters['main_category_id']) {
            $query->where('inventory_items.main_category_id', $filters['main_category_id']);
        }
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('inventory_items.category_id', $filters['category_id']);
        }

        // Group by categories based on group_by parameter
        $groupByFields = [];
        $selectFields = [
            DB::raw('SUM(invoice_items.quantity) as total_quantity_sold'),
            DB::raw('SUM(invoice_items.quantity * invoice_items.unit_price) as total_revenue'),
            DB::raw('SUM(invoice_items.quantity * inventory_items.cost_price) as total_cost'),
            DB::raw('COUNT(DISTINCT invoices.id) as total_orders'),
            DB::raw('COUNT(DISTINCT invoice_items.inventory_item_id) as unique_items_sold'),
            DB::raw('AVG(invoice_items.unit_price) as average_unit_price'),
        ];

        $groupBy = $filters['group_by'] ?? 'both';
        switch ($groupBy) {
            case 'main_category':
                $groupByFields = ['main_categories.id', 'main_categories.name', 'main_categories.name_persian'];
                $selectFields = array_merge($selectFields, [
                    'main_categories.id as category_id',
                    'main_categories.name as category_name',
                    'main_categories.name_persian as category_name_persian',
                    DB::raw("'main' as category_type"),
                ]);
                break;
            case 'subcategory':
                $groupByFields = ['subcategories.id', 'subcategories.name', 'subcategories.name_persian'];
                $selectFields = array_merge($selectFields, [
                    'subcategories.id as category_id',
                    'subcategories.name as category_name',
                    'subcategories.name_persian as category_name_persian',
                    DB::raw("'sub' as category_type"),
                ]);
                break;
            case 'both':
            default:
                $groupByFields = [
                    'main_categories.id', 'main_categories.name', 'main_categories.name_persian',
                    'subcategories.id', 'subcategories.name', 'subcategories.name_persian'
                ];
                $selectFields = array_merge($selectFields, [
                    'main_categories.id as main_category_id',
                    'main_categories.name as main_category_name',
                    'main_categories.name_persian as main_category_name_persian',
                    'subcategories.id as subcategory_id',
                    'subcategories.name as subcategory_name',
                    'subcategories.name_persian as subcategory_name_persian',
                ]);
                break;
        }

        $results = $query->select($selectFields)
            ->groupBy($groupByFields)
            ->get();

        // Calculate additional metrics
        $performanceData = $results->map(function ($item) use ($filters, $groupBy) {
            $profit = $item->total_revenue - $item->total_cost;
            $marginPercentage = $item->total_revenue > 0 ? ($profit / $item->total_revenue) * 100 : 0;
            
            $data = [
                'total_quantity_sold' => (float) $item->total_quantity_sold,
                'total_revenue' => (float) $item->total_revenue,
                'total_cost' => (float) $item->total_cost,
                'total_profit' => $profit,
                'margin_percentage' => round($marginPercentage, 2),
                'total_orders' => (int) $item->total_orders,
                'unique_items_sold' => (int) $item->unique_items_sold,
                'average_unit_price' => (float) $item->average_unit_price,
                'average_order_value' => $item->total_orders > 0 ? $item->total_revenue / $item->total_orders : 0,
            ];

            if ($groupBy === 'both') {
                $data['main_category'] = [
                    'id' => $item->main_category_id,
                    'name' => $item->main_category_name,
                    'name_persian' => $item->main_category_name_persian,
                ];
                $data['subcategory'] = [
                    'id' => $item->subcategory_id,
                    'name' => $item->subcategory_name,
                    'name_persian' => $item->subcategory_name_persian,
                ];
            } else {
                $data['category'] = [
                    'id' => $item->category_id,
                    'name' => $item->category_name,
                    'name_persian' => $item->category_name_persian,
                    'type' => $item->category_type ?? 'unknown',
                ];
            }

            return $data;
        });

        // Calculate summary
        $summary = [
            'total_revenue' => $performanceData->sum('total_revenue'),
            'total_cost' => $performanceData->sum('total_cost'),
            'total_profit' => $performanceData->sum('total_profit'),
            'total_quantity_sold' => $performanceData->sum('total_quantity_sold'),
            'total_orders' => $performanceData->sum('total_orders'),
            'average_margin_percentage' => $performanceData->avg('margin_percentage'),
        ];

        return [
            'filters' => $filters,
            'summary' => $summary,
            'performance_data' => $performanceData->values()->toArray(),
        ];
    }

    /**
     * Generate category stock level reporting
     */
    public function generateCategoryStockLevels(array $filters): array
    {
        $query = InventoryItem::with(['mainCategory', 'subcategory']);

        // Apply category filters
        if (isset($filters['main_category_id']) && $filters['main_category_id']) {
            $query->where('main_category_id', $filters['main_category_id']);
        }
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply stock level filters
        if (!$filters['include_zero_stock']) {
            $query->where('quantity', '>', 0);
        }

        $items = $query->get();

        // Categorize items by stock levels
        $lowStockThreshold = $filters['low_stock_threshold'];
        $stockLevels = [
            'out_of_stock' => [],
            'low_stock' => [],
            'adequate_stock' => [],
        ];

        $categoryStats = [];

        foreach ($items as $item) {
            $mainCategoryId = $item->main_category_id ?? 'uncategorized';
            $subcategoryId = $item->category_id ?? 'uncategorized';
            
            $mainCategoryName = $item->mainCategory ? $item->mainCategory->localized_name : 'Uncategorized';
            $subcategoryName = $item->subcategory ? $item->subcategory->localized_name : 'Uncategorized';

            // Initialize category stats if not exists
            if (!isset($categoryStats[$mainCategoryId])) {
                $categoryStats[$mainCategoryId] = [
                    'id' => $mainCategoryId,
                    'name' => $mainCategoryName,
                    'out_of_stock_count' => 0,
                    'low_stock_count' => 0,
                    'adequate_stock_count' => 0,
                    'total_items' => 0,
                    'total_value' => 0,
                    'subcategories' => [],
                ];
            }

            if (!isset($categoryStats[$mainCategoryId]['subcategories'][$subcategoryId])) {
                $categoryStats[$mainCategoryId]['subcategories'][$subcategoryId] = [
                    'id' => $subcategoryId,
                    'name' => $subcategoryName,
                    'out_of_stock_count' => 0,
                    'low_stock_count' => 0,
                    'adequate_stock_count' => 0,
                    'total_items' => 0,
                    'total_value' => 0,
                ];
            }

            $itemData = [
                'id' => $item->id,
                'name' => $item->localized_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_value' => $item->quantity * $item->unit_price,
                'main_category' => $mainCategoryName,
                'subcategory' => $subcategoryName,
                'gold_purity' => $item->gold_purity,
                'weight' => $item->weight,
            ];

            // Categorize by stock level
            if ($item->quantity == 0) {
                $stockLevels['out_of_stock'][] = $itemData;
                $categoryStats[$mainCategoryId]['out_of_stock_count']++;
                $categoryStats[$mainCategoryId]['subcategories'][$subcategoryId]['out_of_stock_count']++;
            } elseif ($item->quantity <= $lowStockThreshold) {
                $stockLevels['low_stock'][] = $itemData;
                $categoryStats[$mainCategoryId]['low_stock_count']++;
                $categoryStats[$mainCategoryId]['subcategories'][$subcategoryId]['low_stock_count']++;
            } else {
                $stockLevels['adequate_stock'][] = $itemData;
                $categoryStats[$mainCategoryId]['adequate_stock_count']++;
                $categoryStats[$mainCategoryId]['subcategories'][$subcategoryId]['adequate_stock_count']++;
            }

            // Update totals
            $categoryStats[$mainCategoryId]['total_items']++;
            $categoryStats[$mainCategoryId]['total_value'] += $itemData['total_value'];
            $categoryStats[$mainCategoryId]['subcategories'][$subcategoryId]['total_items']++;
            $categoryStats[$mainCategoryId]['subcategories'][$subcategoryId]['total_value'] += $itemData['total_value'];
        }

        // Convert subcategories to arrays
        foreach ($categoryStats as &$category) {
            $category['subcategories'] = array_values($category['subcategories']);
        }

        return [
            'filters' => $filters,
            'summary' => [
                'total_items' => $items->count(),
                'out_of_stock_count' => count($stockLevels['out_of_stock']),
                'low_stock_count' => count($stockLevels['low_stock']),
                'adequate_stock_count' => count($stockLevels['adequate_stock']),
                'total_value' => $items->sum(function ($item) {
                    return $item->quantity * $item->unit_price;
                }),
            ],
            'stock_levels' => $stockLevels,
            'category_stats' => array_values($categoryStats),
        ];
    }

    /**
     * Generate gold purity analysis reports
     */
    public function generateGoldPurityAnalysis(array $filters): array
    {
        $query = InventoryItem::with(['mainCategory', 'subcategory'])
            ->whereNotNull('gold_purity');

        // Apply category filters
        if (isset($filters['main_category_id']) && $filters['main_category_id']) {
            $query->where('main_category_id', $filters['main_category_id']);
        }
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply purity range filters
        if (isset($filters['purity_range_min']) && $filters['purity_range_min']) {
            $query->where('gold_purity', '>=', $filters['purity_range_min']);
        }
        if (isset($filters['purity_range_max']) && $filters['purity_range_max']) {
            $query->where('gold_purity', '<=', $filters['purity_range_max']);
        }

        $items = $query->get();

        // Get sales data for the same items
        $itemIds = $items->pluck('id');
        $salesQuery = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('inventory_items', 'invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->whereIn('invoice_items.inventory_item_id', $itemIds)
            ->where('invoices.status', 'paid');

        if (isset($filters['start_date']) && $filters['start_date']) {
            $salesQuery->where('invoices.issue_date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date']) && $filters['end_date']) {
            $salesQuery->where('invoices.issue_date', '<=', $filters['end_date']);
        }

        $salesData = $salesQuery->select([
            'inventory_items.id',
            'inventory_items.gold_purity',
            DB::raw('SUM(invoice_items.quantity) as quantity_sold'),
            DB::raw('SUM(invoice_items.quantity * invoice_items.unit_price) as revenue'),
            DB::raw('SUM(invoice_items.quantity * inventory_items.cost_price) as cost'),
        ])->groupBy('inventory_items.id', 'inventory_items.gold_purity')->get();

        // Group by gold purity
        $purityGroups = [];
        $standardPurities = $this->goldPurityService->getStandardPurities();

        // Initialize standard purity groups
        foreach ($standardPurities as $purity) {
            $purityGroups[$purity['purity']] = [
                'purity' => $purity['purity'],
                'karat' => $purity['karat'],
                'display_name' => $purity['display_name'],
                'inventory_count' => 0,
                'total_quantity' => 0,
                'total_weight' => 0,
                'total_inventory_value' => 0,
                'quantity_sold' => 0,
                'sales_revenue' => 0,
                'sales_cost' => 0,
                'items' => [],
            ];
        }

        // Process inventory items
        foreach ($items as $item) {
            $purity = $item->gold_purity;
            
            // Find closest standard purity or create custom group
            $closestPurity = $this->findClosestStandardPurity($purity, $standardPurities);
            
            if (!isset($purityGroups[$closestPurity])) {
                $purityGroups[$closestPurity] = [
                    'purity' => $closestPurity,
                    'karat' => $this->goldPurityService->convertPurityToKarat($closestPurity),
                    'display_name' => $this->goldPurityService->formatPurityDisplay($closestPurity, app()->getLocale()),
                    'inventory_count' => 0,
                    'total_quantity' => 0,
                    'total_weight' => 0,
                    'total_inventory_value' => 0,
                    'quantity_sold' => 0,
                    'sales_revenue' => 0,
                    'sales_cost' => 0,
                    'items' => [],
                ];
            }

            $purityGroups[$closestPurity]['inventory_count']++;
            $purityGroups[$closestPurity]['total_quantity'] += $item->quantity;
            $purityGroups[$closestPurity]['total_weight'] += $item->weight ?? 0;
            $purityGroups[$closestPurity]['total_inventory_value'] += $item->quantity * $item->unit_price;
            
            $purityGroups[$closestPurity]['items'][] = [
                'id' => $item->id,
                'name' => $item->localized_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'weight' => $item->weight,
                'gold_purity' => $item->gold_purity,
                'unit_price' => $item->unit_price,
                'main_category' => $item->mainCategory ? $item->mainCategory->localized_name : null,
                'subcategory' => $item->subcategory ? $item->subcategory->localized_name : null,
            ];
        }

        // Process sales data
        foreach ($salesData as $sale) {
            $closestPurity = $this->findClosestStandardPurity($sale->gold_purity, $standardPurities);
            
            if (isset($purityGroups[$closestPurity])) {
                $purityGroups[$closestPurity]['quantity_sold'] += $sale->quantity_sold;
                $purityGroups[$closestPurity]['sales_revenue'] += $sale->revenue;
                $purityGroups[$closestPurity]['sales_cost'] += $sale->cost;
            }
        }

        // Calculate additional metrics for each group
        foreach ($purityGroups as &$group) {
            $group['sales_profit'] = $group['sales_revenue'] - $group['sales_cost'];
            $group['margin_percentage'] = $group['sales_revenue'] > 0 ? 
                ($group['sales_profit'] / $group['sales_revenue']) * 100 : 0;
            $group['turnover_rate'] = $group['total_quantity'] > 0 ? 
                $group['quantity_sold'] / $group['total_quantity'] : 0;
        }

        // Remove empty groups and sort by purity
        $purityGroups = array_filter($purityGroups, function ($group) {
            return $group['inventory_count'] > 0 || $group['quantity_sold'] > 0;
        });
        
        ksort($purityGroups);

        return [
            'filters' => $filters,
            'summary' => [
                'total_purity_groups' => count($purityGroups),
                'total_inventory_items' => $items->count(),
                'total_inventory_value' => array_sum(array_column($purityGroups, 'total_inventory_value')),
                'total_weight' => array_sum(array_column($purityGroups, 'total_weight')),
                'total_sales_revenue' => array_sum(array_column($purityGroups, 'sales_revenue')),
                'total_sales_profit' => array_sum(array_column($purityGroups, 'sales_profit')),
            ],
            'purity_groups' => array_values($purityGroups),
        ];
    }

    /**
     * Generate comprehensive inventory analytics
     */
    public function generateInventoryAnalytics(string $period, array $filters): array
    {
        $dateRanges = $this->getDateRangesForPeriod($period);
        
        $analytics = [
            'period' => $period,
            'filters' => $filters,
            'trends' => [],
            'category_performance' => [],
            'gold_purity_trends' => [],
            'stock_alerts' => [],
        ];

        foreach ($dateRanges as $range) {
            $rangeFilters = array_merge($filters, [
                'start_date' => $range['start'],
                'end_date' => $range['end'],
            ]);

            // Get sales performance for this period
            $salesPerformance = $this->generateCategorySalesPerformance($rangeFilters);
            
            $analytics['trends'][] = [
                'period_label' => $range['label'],
                'start_date' => $range['start']->toDateString(),
                'end_date' => $range['end']->toDateString(),
                'total_revenue' => $salesPerformance['summary']['total_revenue'],
                'total_profit' => $salesPerformance['summary']['total_profit'],
                'total_quantity_sold' => $salesPerformance['summary']['total_quantity_sold'],
                'average_margin' => $salesPerformance['summary']['average_margin_percentage'],
            ];
        }

        // Get current category performance
        $currentFilters = array_merge($filters, [
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->endOfMonth(),
            'group_by' => 'both',
        ]);
        
        $categoryPerformance = $this->generateCategorySalesPerformance($currentFilters);
        $analytics['category_performance'] = $categoryPerformance['performance_data'];

        // Get gold purity trends
        $purityAnalysis = $this->generateGoldPurityAnalysis($currentFilters);
        $analytics['gold_purity_trends'] = $purityAnalysis['purity_groups'];

        // Get stock alerts
        $stockLevels = $this->generateCategoryStockLevels(array_merge($filters, [
            'low_stock_threshold' => 10,
            'include_zero_stock' => true,
        ]));
        
        $analytics['stock_alerts'] = [
            'out_of_stock' => array_slice($stockLevels['stock_levels']['out_of_stock'], 0, 10),
            'low_stock' => array_slice($stockLevels['stock_levels']['low_stock'], 0, 10),
        ];

        return $analytics;
    }

    /**
     * Find the closest standard purity for grouping
     */
    private function findClosestStandardPurity(float $purity, array $standardPurities): float
    {
        $closest = null;
        $minDifference = PHP_FLOAT_MAX;

        foreach ($standardPurities as $standard) {
            $difference = abs($purity - $standard['purity']);
            if ($difference < $minDifference) {
                $minDifference = $difference;
                $closest = $standard['purity'];
            }
        }

        // If difference is too large (>0.5), use the actual purity
        return $minDifference > 0.5 ? $purity : $closest;
    }

    /**
     * Get date ranges for the specified period
     */
    private function getDateRangesForPeriod(string $period): array
    {
        $ranges = [];
        $now = Carbon::now();

        switch ($period) {
            case 'week':
                for ($i = 6; $i >= 0; $i--) {
                    $date = $now->copy()->subWeeks($i);
                    $ranges[] = [
                        'start' => $date->copy()->startOfWeek(),
                        'end' => $date->copy()->endOfWeek(),
                        'label' => $date->format('M d'),
                    ];
                }
                break;
            case 'month':
                for ($i = 11; $i >= 0; $i--) {
                    $date = $now->copy()->subMonths($i);
                    $ranges[] = [
                        'start' => $date->copy()->startOfMonth(),
                        'end' => $date->copy()->endOfMonth(),
                        'label' => $date->format('M Y'),
                    ];
                }
                break;
            case 'quarter':
                for ($i = 3; $i >= 0; $i--) {
                    $date = $now->copy()->subQuarters($i);
                    $ranges[] = [
                        'start' => $date->copy()->startOfQuarter(),
                        'end' => $date->copy()->endOfQuarter(),
                        'label' => 'Q' . $date->quarter . ' ' . $date->year,
                    ];
                }
                break;
            case 'year':
                for ($i = 4; $i >= 0; $i--) {
                    $date = $now->copy()->subYears($i);
                    $ranges[] = [
                        'start' => $date->copy()->startOfYear(),
                        'end' => $date->copy()->endOfYear(),
                        'label' => (string) $date->year,
                    ];
                }
                break;
        }

        return $ranges;
    }
}