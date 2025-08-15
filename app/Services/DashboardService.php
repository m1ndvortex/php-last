<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\Customer;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Get real-time KPIs for the dashboard
     */
    public function getKPIs(array $dateRange = null): array
    {
        $cacheKey = 'dashboard_kpis_' . md5(json_encode($dateRange));
        
        return Cache::remember($cacheKey, 300, function () use ($dateRange) {
            $startDate = $dateRange['start'] ?? Carbon::now()->startOfMonth();
            $endDate = $dateRange['end'] ?? Carbon::now()->endOfMonth();

            // Calculate previous period for comparison
            $periodLength = $startDate->diffInDays($endDate);
            $previousStartDate = $startDate->copy()->subDays($periodLength + 1);
            $previousEndDate = $startDate->copy()->subDay();

            $currentData = [
                'gold_sold' => $this->calculateGoldSold($startDate, $endDate),
                'total_profits' => $this->calculateTotalProfits($startDate, $endDate),
                'average_price' => $this->calculateAveragePrice($startDate, $endDate),
                'returns' => $this->calculateReturns($startDate, $endDate),
                'gross_margin' => $this->calculateGrossMargin($startDate, $endDate),
                'net_margin' => $this->calculateNetMargin($startDate, $endDate),
                'total_sales' => $this->calculateTotalSales($startDate, $endDate),
                'active_customers' => $this->getActiveCustomers($startDate, $endDate),
                'inventory_value' => $this->getInventoryValue(),
                'pending_invoices' => $this->getPendingInvoicesCount()
            ];

            $previousData = [
                'gold_sold_previous' => $this->calculateGoldSold($previousStartDate, $previousEndDate),
                'total_profits_previous' => $this->calculateTotalProfits($previousStartDate, $previousEndDate),
                'average_price_previous' => $this->calculateAveragePrice($previousStartDate, $previousEndDate),
                'returns_previous' => $this->calculateReturns($previousStartDate, $previousEndDate),
                'gross_margin_previous' => $this->calculateGrossMargin($previousStartDate, $previousEndDate),
                'net_margin_previous' => $this->calculateNetMargin($previousStartDate, $previousEndDate),
            ];

            return array_merge($currentData, $previousData);
        });
    }

    /**
     * Calculate total gold sold in grams
     */
    private function calculateGoldSold($startDate, $endDate): float
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('inventory_items', 'invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.issue_date', [$startDate, $endDate])
            ->whereNotNull('inventory_items.weight')
            ->sum(DB::raw('invoice_items.quantity * inventory_items.weight')) ?? 0;
    }

    /**
     * Calculate total profits
     */
    private function calculateTotalProfits($startDate, $endDate): float
    {
        $revenue = $this->calculateTotalSales($startDate, $endDate);
        $costs = $this->calculateTotalCosts($startDate, $endDate);
        
        return $revenue - $costs;
    }

    /**
     * Calculate total sales revenue
     */
    private function calculateTotalSales($startDate, $endDate): float
    {
        return Invoice::where('status', 'paid')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->sum('total_amount') ?? 0;
    }

    /**
     * Calculate total costs
     */
    private function calculateTotalCosts($startDate, $endDate): float
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('inventory_items', 'invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.issue_date', [$startDate, $endDate])
            ->sum(DB::raw('invoice_items.quantity * inventory_items.cost_price')) ?? 0;
    }

    /**
     * Calculate average price per sale
     */
    private function calculateAveragePrice($startDate, $endDate): float
    {
        $totalSales = $this->calculateTotalSales($startDate, $endDate);
        $invoiceCount = Invoice::where('status', 'paid')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->count();

        return $invoiceCount > 0 ? $totalSales / $invoiceCount : 0;
    }

    /**
     * Calculate returns amount
     */
    private function calculateReturns($startDate, $endDate): float
    {
        return Invoice::where('status', 'cancelled')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->sum('total_amount') ?? 0;
    }

    /**
     * Calculate gross margin percentage
     */
    private function calculateGrossMargin($startDate, $endDate): float
    {
        $revenue = $this->calculateTotalSales($startDate, $endDate);
        $costs = $this->calculateTotalCosts($startDate, $endDate);
        
        return $revenue > 0 ? (($revenue - $costs) / $revenue) * 100 : 0;
    }

    /**
     * Calculate net margin percentage
     */
    private function calculateNetMargin($startDate, $endDate): float
    {
        $revenue = $this->calculateTotalSales($startDate, $endDate);
        $totalExpenses = $this->calculateTotalExpenses($startDate, $endDate);
        
        return $revenue > 0 ? (($revenue - $totalExpenses) / $revenue) * 100 : 0;
    }

    /**
     * Calculate total expenses including operational costs
     */
    private function calculateTotalExpenses($startDate, $endDate): float
    {
        $costs = $this->calculateTotalCosts($startDate, $endDate);
        
        // Add operational expenses from transaction entries
        $operationalExpenses = DB::table('transaction_entries')
            ->join('transactions', 'transaction_entries.transaction_id', '=', 'transactions.id')
            ->join('accounts', 'transaction_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'expense')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->sum('transaction_entries.debit_amount') ?? 0;

        return $costs + $operationalExpenses;
    }

    /**
     * Get active customers count
     */
    private function getActiveCustomers($startDate, $endDate): int
    {
        return Customer::whereHas('invoices', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('issue_date', [$startDate, $endDate]);
        })->count();
    }

    /**
     * Get total inventory value
     */
    private function getInventoryValue(): float
    {
        return InventoryItem::sum(DB::raw('quantity * unit_price')) ?? 0;
    }

    /**
     * Get pending invoices count
     */
    private function getPendingInvoicesCount(): int
    {
        return Invoice::whereIn('status', ['sent', 'overdue'])->count();
    }

    /**
     * Get sales chart data for specified period
     */
    public function getSalesChartData(string $period = 'month'): array
    {
        $cacheKey = "sales_chart_data_{$period}";
        
        return Cache::remember($cacheKey, 600, function () use ($period) {
            switch ($period) {
                case 'week':
                    return $this->getWeeklySalesData();
                case 'month':
                    return $this->getMonthlySalesData();
                case 'year':
                    return $this->getYearlySalesData();
                default:
                    return $this->getMonthlySalesData();
            }
        });
    }

    /**
     * Get weekly sales data
     */
    private function getWeeklySalesData(): array
    {
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        $data = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dailySales = Invoice::where('status', 'paid')
                ->whereDate('issue_date', $date)
                ->sum('total_amount') ?? 0;

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('D'),
                'sales' => $dailySales
            ];
        }

        return $data;
    }

    /**
     * Get monthly sales data
     */
    private function getMonthlySalesData(): array
    {
        $startDate = Carbon::now()->startOfYear();
        $endDate = Carbon::now()->endOfYear();

        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = Carbon::create(null, $month, 1)->startOfMonth();
            $monthEnd = Carbon::create(null, $month, 1)->endOfMonth();

            $monthlySales = Invoice::where('status', 'paid')
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->sum('total_amount') ?? 0;

            $data[] = [
                'date' => $monthStart->format('Y-m'),
                'label' => $monthStart->format('M'),
                'sales' => $monthlySales
            ];
        }

        return $data;
    }

    /**
     * Get yearly sales data
     */
    private function getYearlySalesData(): array
    {
        $currentYear = Carbon::now()->year;
        $data = [];

        for ($year = $currentYear - 4; $year <= $currentYear; $year++) {
            $yearStart = Carbon::create($year, 1, 1)->startOfYear();
            $yearEnd = Carbon::create($year, 12, 31)->endOfYear();

            $yearlySales = Invoice::where('status', 'paid')
                ->whereBetween('issue_date', [$yearStart, $yearEnd])
                ->sum('total_amount') ?? 0;

            $data[] = [
                'date' => (string) $year,
                'label' => (string) $year,
                'sales' => $yearlySales
            ];
        }

        return $data;
    }

    /**
     * Get category-wise performance analytics
     */
    public function getCategoryPerformance(): array
    {
        $cacheKey = 'category_performance';
        
        return Cache::remember($cacheKey, 600, function () {
            // Get main category performance
            $mainCategoryPerformance = DB::table('categories as main_categories')
                ->leftJoin('inventory_items', 'main_categories.id', '=', 'inventory_items.main_category_id')
                ->leftJoin('invoice_items', 'inventory_items.id', '=', 'invoice_items.inventory_item_id')
                ->leftJoin('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->where('invoices.status', 'paid')
                ->whereNull('main_categories.parent_id') // Only main categories
                ->groupBy('main_categories.id', 'main_categories.name', 'main_categories.name_persian')
                ->select([
                    'main_categories.id',
                    'main_categories.name',
                    'main_categories.name_persian',
                    DB::raw("'main' as category_type"),
                    DB::raw('COUNT(DISTINCT invoices.id) as total_orders'),
                    DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                    DB::raw('SUM(invoice_items.quantity * invoice_items.unit_price) as total_revenue'),
                    DB::raw('SUM(invoice_items.quantity * inventory_items.cost_price) as total_cost'),
                    DB::raw('(SUM(invoice_items.quantity * invoice_items.unit_price) - SUM(invoice_items.quantity * inventory_items.cost_price)) as profit'),
                    DB::raw('CASE WHEN SUM(invoice_items.quantity * invoice_items.unit_price) > 0 THEN ((SUM(invoice_items.quantity * invoice_items.unit_price) - SUM(invoice_items.quantity * inventory_items.cost_price)) / SUM(invoice_items.quantity * invoice_items.unit_price)) * 100 ELSE 0 END as margin_percentage')
                ])
                ->get();

            // Get subcategory performance
            $subcategoryPerformance = DB::table('categories as subcategories')
                ->leftJoin('inventory_items', 'subcategories.id', '=', 'inventory_items.category_id')
                ->leftJoin('invoice_items', 'inventory_items.id', '=', 'invoice_items.inventory_item_id')
                ->leftJoin('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->leftJoin('categories as main_categories', 'subcategories.parent_id', '=', 'main_categories.id')
                ->where('invoices.status', 'paid')
                ->whereNotNull('subcategories.parent_id') // Only subcategories
                ->groupBy('subcategories.id', 'subcategories.name', 'subcategories.name_persian', 'main_categories.name', 'main_categories.name_persian')
                ->select([
                    'subcategories.id',
                    'subcategories.name',
                    'subcategories.name_persian',
                    'main_categories.name as parent_name',
                    'main_categories.name_persian as parent_name_persian',
                    DB::raw("'sub' as category_type"),
                    DB::raw('COUNT(DISTINCT invoices.id) as total_orders'),
                    DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                    DB::raw('SUM(invoice_items.quantity * invoice_items.unit_price) as total_revenue'),
                    DB::raw('SUM(invoice_items.quantity * inventory_items.cost_price) as total_cost'),
                    DB::raw('(SUM(invoice_items.quantity * invoice_items.unit_price) - SUM(invoice_items.quantity * inventory_items.cost_price)) as profit'),
                    DB::raw('CASE WHEN SUM(invoice_items.quantity * invoice_items.unit_price) > 0 THEN ((SUM(invoice_items.quantity * invoice_items.unit_price) - SUM(invoice_items.quantity * inventory_items.cost_price)) / SUM(invoice_items.quantity * invoice_items.unit_price)) * 100 ELSE 0 END as margin_percentage')
                ])
                ->get();

            return [
                'main_categories' => $mainCategoryPerformance->toArray(),
                'subcategories' => $subcategoryPerformance->toArray(),
                'combined' => $mainCategoryPerformance->concat($subcategoryPerformance)->toArray(),
            ];
        });
    }

    /**
     * Get gold purity performance analytics
     */
    public function getGoldPurityPerformance(): array
    {
        $cacheKey = 'gold_purity_performance';
        
        return Cache::remember($cacheKey, 600, function () {
            return DB::table('inventory_items')
                ->leftJoin('invoice_items', 'inventory_items.id', '=', 'invoice_items.inventory_item_id')
                ->leftJoin('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->where('invoices.status', 'paid')
                ->whereNotNull('inventory_items.gold_purity')
                ->groupBy('inventory_items.gold_purity')
                ->select([
                    'inventory_items.gold_purity',
                    DB::raw('COUNT(DISTINCT invoices.id) as total_orders'),
                    DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                    DB::raw('SUM(invoice_items.quantity * invoice_items.unit_price) as total_revenue'),
                    DB::raw('SUM(invoice_items.quantity * inventory_items.cost_price) as total_cost'),
                    DB::raw('(SUM(invoice_items.quantity * invoice_items.unit_price) - SUM(invoice_items.quantity * inventory_items.cost_price)) as profit'),
                    DB::raw('CASE WHEN SUM(invoice_items.quantity * invoice_items.unit_price) > 0 THEN ((SUM(invoice_items.quantity * invoice_items.unit_price) - SUM(invoice_items.quantity * inventory_items.cost_price)) / SUM(invoice_items.quantity * invoice_items.unit_price)) * 100 ELSE 0 END as margin_percentage'),
                    DB::raw('SUM(inventory_items.weight * invoice_items.quantity) as total_weight_sold')
                ])
                ->orderBy('inventory_items.gold_purity', 'desc')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get category stock alerts
     */
    public function getCategoryStockAlerts(): array
    {
        $cacheKey = 'category_stock_alerts';
        
        return Cache::remember($cacheKey, 300, function () {
            $lowStockThreshold = 10;
            
            return DB::table('inventory_items')
                ->leftJoin('categories as main_categories', 'inventory_items.main_category_id', '=', 'main_categories.id')
                ->leftJoin('categories as subcategories', 'inventory_items.category_id', '=', 'subcategories.id')
                ->where('inventory_items.quantity', '<=', $lowStockThreshold)
                ->select([
                    'inventory_items.id',
                    'inventory_items.name',
                    'inventory_items.name_persian',
                    'inventory_items.sku',
                    'inventory_items.quantity',
                    'inventory_items.unit_price',
                    'main_categories.name as main_category_name',
                    'main_categories.name_persian as main_category_name_persian',
                    'subcategories.name as subcategory_name',
                    'subcategories.name_persian as subcategory_name_persian',
                    DB::raw('CASE WHEN inventory_items.quantity = 0 THEN "out_of_stock" ELSE "low_stock" END as alert_type')
                ])
                ->orderBy('inventory_items.quantity', 'asc')
                ->limit(20)
                ->get()
                ->toArray();
        });
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(): void
    {
        Cache::forget('dashboard_kpis_*');
        Cache::forget('sales_chart_data_*');
        Cache::forget('category_performance');
        Cache::forget('gold_purity_performance');
        Cache::forget('category_stock_alerts');
    }
}