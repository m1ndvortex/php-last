<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InventoryItem;
use Carbon\Carbon;

class QueryOptimizationService
{
    /**
     * Cache duration in seconds (5 minutes for frequently accessed data)
     */
    const CACHE_DURATION = 300;
    
    /**
     * Cache duration for dashboard KPIs (2 minutes)
     */
    const DASHBOARD_CACHE_DURATION = 120;
    
    /**
     * Cache duration for reports (10 minutes)
     */
    const REPORT_CACHE_DURATION = 600;

    /**
     * Get dashboard KPIs with caching
     */
    public function getDashboardKPIs(): array
    {
        return Cache::remember('dashboard_kpis', self::DASHBOARD_CACHE_DURATION, function () {
            return [
                'total_customers' => $this->getTotalCustomers(),
                'active_customers' => $this->getActiveCustomers(),
                'total_inventory_value' => $this->getTotalInventoryValue(),
                'low_stock_items' => $this->getLowStockItemsCount(),
                'monthly_sales' => $this->getMonthlySales(),
                'pending_invoices' => $this->getPendingInvoicesCount(),
                'overdue_invoices' => $this->getOverdueInvoicesCount(),
                'total_revenue' => $this->getTotalRevenue(),
            ];
        });
    }

    /**
     * Get optimized customer list with pagination
     */
    public function getOptimizedCustomerList(array $filters = [], int $perPage = 50)
    {
        $cacheKey = 'customers_list_' . md5(serialize($filters)) . '_page_' . request('page', 1);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters, $perPage) {
            $query = Customer::query()
                ->select(['id', 'name', 'email', 'phone', 'customer_type', 'is_active', 'crm_stage', 'created_at'])
                ->with(['invoices' => function ($query) {
                    $query->select(['id', 'customer_id', 'total_amount', 'status'])
                          ->latest()
                          ->limit(5);
                }]);

            // Apply filters
            if (!empty($filters['search'])) {
                $query->search($filters['search']);
            }

            if (!empty($filters['type'])) {
                $query->ofType($filters['type']);
            }

            if (!empty($filters['stage'])) {
                $query->inStage($filters['stage']);
            }

            if (isset($filters['active'])) {
                $query->where('is_active', $filters['active']);
            }

            return $query->orderBy('name')->paginate($perPage);
        });
    }

    /**
     * Get optimized inventory list with eager loading
     */
    public function getOptimizedInventoryList(array $filters = [], int $perPage = 50)
    {
        $cacheKey = 'inventory_list_' . md5(serialize($filters)) . '_page_' . request('page', 1);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters, $perPage) {
            $query = InventoryItem::query()
                ->select([
                    'id', 'name', 'name_persian', 'sku', 'category_id', 'main_category_id',
                    'location_id', 'quantity', 'unit_price', 'cost_price', 'gold_purity',
                    'weight', 'minimum_stock', 'is_active', 'created_at'
                ])
                ->with([
                    'category:id,name,name_persian',
                    'mainCategory:id,name,name_persian',
                    'location:id,name'
                ]);

            // Apply filters with optimized queries
            if (!empty($filters['category_id'])) {
                $query->inCategory($filters['category_id']);
            }

            if (!empty($filters['main_category_id'])) {
                $query->inMainCategory($filters['main_category_id']);
            }

            if (!empty($filters['location_id'])) {
                $query->inLocation($filters['location_id']);
            }

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['search']}%")
                      ->orWhere('name_persian', 'like', "%{$filters['search']}%")
                      ->orWhere('sku', 'like', "%{$filters['search']}%");
                });
            }

            if (isset($filters['low_stock']) && $filters['low_stock']) {
                $query->lowStock();
            }

            if (isset($filters['active'])) {
                $query->where('is_active', $filters['active']);
            }

            return $query->orderBy('name')->paginate($perPage);
        });
    }

    /**
     * Get optimized invoice list with relationships
     */
    public function getOptimizedInvoiceList(array $filters = [], int $perPage = 50)
    {
        $cacheKey = 'invoices_list_' . md5(serialize($filters)) . '_page_' . request('page', 1);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters, $perPage) {
            $query = Invoice::query()
                ->select([
                    'id', 'customer_id', 'invoice_number', 'issue_date', 'due_date',
                    'total_amount', 'status', 'language', 'created_at'
                ])
                ->with([
                    'customer:id,name,email,phone',
                    'items' => function ($query) {
                        $query->select(['id', 'invoice_id', 'inventory_item_id', 'quantity', 'unit_price', 'total_price'])
                              ->with('inventoryItem:id,name,name_persian,sku');
                    }
                ]);

            // Apply filters
            if (!empty($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }

            if (!empty($filters['status'])) {
                $query->byStatus($filters['status']);
            }

            if (!empty($filters['language'])) {
                $query->byLanguage($filters['language']);
            }

            if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                $query->byDateRange($filters['date_from'], $filters['date_to']);
            }

            return $query->orderBy('issue_date', 'desc')->paginate($perPage);
        });
    }

    /**
     * Get category hierarchy with caching
     */
    public function getCategoryHierarchy()
    {
        return Cache::remember('category_hierarchy', 3600, function () {
            return DB::table('categories')
                ->select(['id', 'name', 'name_persian', 'parent_id', 'is_active', 'sort_order'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->groupBy('parent_id');
        });
    }

    /**
     * Get inventory statistics with caching
     */
    public function getInventoryStatistics()
    {
        return Cache::remember('inventory_statistics', self::CACHE_DURATION, function () {
            return [
                'total_items' => InventoryItem::active()->count(),
                'total_value' => InventoryItem::active()->sum(DB::raw('quantity * unit_price')),
                'low_stock_count' => InventoryItem::active()->lowStock()->count(),
                'categories_count' => DB::table('categories')->where('is_active', true)->count(),
                'locations_count' => DB::table('locations')->where('is_active', true)->count(),
                'average_item_value' => InventoryItem::active()->avg('unit_price'),
            ];
        });
    }

    /**
     * Clear specific cache keys
     */
    public function clearCache(string $type = 'all'): void
    {
        switch ($type) {
            case 'dashboard':
                Cache::forget('dashboard_kpis');
                break;
            case 'customers':
                Cache::flush(); // For now, flush all customer-related cache
                break;
            case 'inventory':
                Cache::forget('inventory_statistics');
                Cache::forget('category_hierarchy');
                break;
            case 'invoices':
                // Clear invoice-related cache
                break;
            case 'all':
            default:
                Cache::flush();
                break;
        }
        
        Log::info("Cache cleared for type: {$type}");
    }

    /**
     * Private helper methods for KPI calculations
     */
    private function getTotalCustomers(): int
    {
        return Customer::count();
    }

    private function getActiveCustomers(): int
    {
        return Customer::active()->count();
    }

    private function getTotalInventoryValue(): float
    {
        return (float) InventoryItem::active()
            ->sum(DB::raw('quantity * unit_price'));
    }

    private function getLowStockItemsCount(): int
    {
        return InventoryItem::active()->lowStock()->count();
    }

    private function getMonthlySales(): float
    {
        return (float) Invoice::whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
    }

    private function getPendingInvoicesCount(): int
    {
        return Invoice::byStatus('pending')->count();
    }

    private function getOverdueInvoicesCount(): int
    {
        return Invoice::byStatus('overdue')->count();
    }

    private function getTotalRevenue(): float
    {
        return (float) Invoice::where('status', 'paid')->sum('total_amount');
    }

    /**
     * Optimize database queries by adding proper indexes
     */
    public function optimizeQueries(): void
    {
        // This method can be called to ensure indexes are properly set
        // The actual indexes are created via migrations
        Log::info('Query optimization service initialized');
    }

    /**
     * Get slow query analysis
     */
    public function getSlowQueryAnalysis(): array
    {
        // This would typically analyze slow query logs
        // For now, return basic query performance metrics
        return [
            'total_queries' => 0, // Would be populated from slow query log
            'slow_queries' => 0,
            'average_query_time' => 0,
            'recommendations' => [
                'Consider adding indexes for frequently queried columns',
                'Use eager loading for relationships',
                'Implement query result caching',
                'Optimize WHERE clauses with proper indexing'
            ]
        ];
    }
}