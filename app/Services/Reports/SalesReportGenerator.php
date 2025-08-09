<?php

namespace App\Services\Reports;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InventoryItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportGenerator extends BaseReportGenerator
{
    /**
     * Generate sales report
     */
    public function generate(): array
    {
        switch ($this->subtype) {
            case 'summary':
                return $this->generateSummaryReport();
            case 'detailed':
                return $this->generateDetailedReport();
            case 'by_period':
                return $this->generateByPeriodReport();
            case 'by_customer':
                return $this->generateByCustomerReport();
            case 'by_product':
                return $this->generateByProductReport();
            default:
                throw new \InvalidArgumentException("Unknown sales report subtype: {$this->subtype}");
        }
    }

    /**
     * Generate sales summary report
     */
    protected function generateSummaryReport(): array
    {
        $invoices = $this->getBaseInvoiceQuery()->get();
        
        $totalSales = $invoices->sum('total_amount');
        $totalInvoices = $invoices->count();
        $averageOrderValue = $totalInvoices > 0 ? $totalSales / $totalInvoices : 0;
        
        // Previous period comparison
        $previousPeriod = $this->getPreviousPeriodData();
        
        // Daily sales trend
        $dailySales = $this->getDailySalesData($invoices);
        
        // Top customers
        $topCustomers = $this->getTopCustomers($invoices);
        
        // Top products
        $topProducts = $this->getTopProducts($invoices);
        
        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.sales_summary'),
            'type' => 'sales',
            'subtype' => 'summary',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_sales' => [
                    'value' => $totalSales,
                    'formatted' => $this->formatCurrency($totalSales),
                    'change' => $this->calculatePercentageChange($totalSales, $previousPeriod['total_sales']),
                    'label' => $this->trans('reports.total_sales')
                ],
                'total_invoices' => [
                    'value' => $totalInvoices,
                    'formatted' => number_format($totalInvoices),
                    'change' => $this->calculatePercentageChange($totalInvoices, $previousPeriod['total_invoices']),
                    'label' => $this->trans('reports.total_invoices')
                ],
                'average_order_value' => [
                    'value' => $averageOrderValue,
                    'formatted' => $this->formatCurrency($averageOrderValue),
                    'change' => $this->calculatePercentageChange($averageOrderValue, $previousPeriod['average_order_value']),
                    'label' => $this->trans('reports.average_order_value')
                ]
            ],
            'charts' => [
                'daily_sales' => $this->generateChartData($dailySales, 'line', [
                    'title' => $this->trans('reports.daily_sales_trend'),
                    'label_field' => 'date',
                    'value_field' => 'total',
                    'dataset_label' => $this->trans('reports.sales')
                ]),
                'top_customers' => $this->generateChartData($topCustomers, 'bar', [
                    'title' => $this->trans('reports.top_customers'),
                    'label_field' => 'name',
                    'value_field' => 'total',
                    'dataset_label' => $this->trans('reports.sales_amount')
                ]),
                'top_products' => $this->generateChartData($topProducts, 'pie', [
                    'title' => $this->trans('reports.top_products'),
                    'label_field' => 'name',
                    'value_field' => 'quantity'
                ])
            ],
            'data' => [
                'top_customers' => $topCustomers->toArray(),
                'top_products' => $topProducts->toArray(),
                'daily_sales' => $dailySales->toArray()
            ]
        ];
    }

    /**
     * Generate detailed sales report
     */
    protected function generateDetailedReport(): array
    {
        $invoices = $this->getBaseInvoiceQuery()
            ->with(['customer', 'items.inventoryItem'])
            ->get();

        $detailedData = $invoices->map(function ($invoice) {
            return [
                'invoice_number' => $invoice->invoice_number,
                'date' => $this->formatDate($invoice->issue_date),
                'customer' => $invoice->customer->name,
                'customer_phone' => $invoice->customer->phone,
                'items_count' => $invoice->items->count(),
                'subtotal' => $invoice->subtotal,
                'tax_amount' => $invoice->tax_amount,
                'total_amount' => $invoice->total_amount,
                'status' => $invoice->status,
                'payment_status' => $invoice->payment_status ?? 'pending',
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'name' => $item->inventoryItem->name ?? $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'gold_purity' => $item->gold_purity,
                        'weight' => $item->weight
                    ];
                })
            ];
        });

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.detailed_sales_report'),
            'type' => 'sales',
            'subtype' => 'detailed',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => $this->generateSummary($invoices, [
                'total_amount' => ['label' => $this->trans('reports.total_sales'), 'format' => 'currency'],
                'subtotal' => ['label' => $this->trans('reports.subtotal'), 'format' => 'currency'],
                'tax_amount' => ['label' => $this->trans('reports.tax_amount'), 'format' => 'currency']
            ]),
            'data' => $detailedData->toArray(),
            'totals' => [
                'count' => $invoices->count(),
                'subtotal' => $invoices->sum('subtotal'),
                'tax_amount' => $invoices->sum('tax_amount'),
                'total_amount' => $invoices->sum('total_amount')
            ]
        ];
    }

    /**
     * Generate sales by period report
     */
    protected function generateByPeriodReport(): array
    {
        $periodType = $this->filters['period_type'] ?? 'daily';
        
        $salesByPeriod = $this->getSalesByPeriod($periodType);
        
        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.sales_by_period'),
            'type' => 'sales',
            'subtype' => 'by_period',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'period_type' => $periodType,
            'charts' => [
                'sales_trend' => $this->generateChartData($salesByPeriod, 'line', [
                    'title' => $this->trans('reports.sales_trend'),
                    'label_field' => 'period',
                    'value_field' => 'total_sales',
                    'dataset_label' => $this->trans('reports.sales')
                ]),
                'invoice_count' => $this->generateChartData($salesByPeriod, 'bar', [
                    'title' => $this->trans('reports.invoice_count_by_period'),
                    'label_field' => 'period',
                    'value_field' => 'invoice_count',
                    'dataset_label' => $this->trans('reports.invoices')
                ])
            ],
            'data' => $salesByPeriod->toArray(),
            'summary' => $this->generateSummary($salesByPeriod, [
                'total_sales' => ['label' => $this->trans('reports.total_sales'), 'format' => 'currency'],
                'invoice_count' => ['label' => $this->trans('reports.invoice_count'), 'format' => 'number']
            ])
        ];
    }

    /**
     * Generate sales by customer report
     */
    protected function generateByCustomerReport(): array
    {
        $customerSales = $this->getBaseInvoiceQuery()
            ->select([
                'customer_id',
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('AVG(total_amount) as average_order_value'),
                DB::raw('MAX(issue_date) as last_purchase_date')
            ])
            ->with('customer')
            ->groupBy('customer_id')
            ->orderBy('total_sales', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'customer_id' => $item->customer_id,
                    'customer_name' => $item->customer->name,
                    'customer_phone' => $item->customer->phone,
                    'customer_email' => $item->customer->email,
                    'invoice_count' => $item->invoice_count,
                    'total_sales' => $item->total_sales,
                    'average_order_value' => $item->average_order_value,
                    'last_purchase_date' => $this->formatDate(\Carbon\Carbon::parse($item->last_purchase_date))
                ];
            });

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.sales_by_customer'),
            'type' => 'sales',
            'subtype' => 'by_customer',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'charts' => [
                'top_customers' => $this->generateChartData($customerSales->take(10), 'bar', [
                    'title' => $this->trans('reports.top_10_customers'),
                    'label_field' => 'customer_name',
                    'value_field' => 'total_sales',
                    'dataset_label' => $this->trans('reports.sales_amount')
                ])
            ],
            'data' => $customerSales->toArray(),
            'summary' => [
                'total_customers' => $customerSales->count(),
                'total_sales' => $customerSales->sum('total_sales'),
                'average_customer_value' => $customerSales->avg('total_sales')
            ]
        ];
    }

    /**
     * Generate sales by product report
     */
    protected function generateByProductReport(): array
    {
        $productSales = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('inventory_items', 'invoice_items.inventory_item_id', '=', 'inventory_items.id')
            ->whereBetween('invoices.issue_date', [$this->startDate, $this->endDate])
            ->select([
                'inventory_items.id',
                'inventory_items.name',
                'inventory_items.sku',
                'inventory_items.category_id',
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(invoice_items.total_price) as total_sales'),
                DB::raw('AVG(invoice_items.unit_price) as average_price'),
                DB::raw('COUNT(DISTINCT invoices.id) as invoice_count')
            ])
            ->groupBy('inventory_items.id', 'inventory_items.name', 'inventory_items.sku', 'inventory_items.category_id')
            ->orderBy('total_sales', 'desc')
            ->get();

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.sales_by_product'),
            'type' => 'sales',
            'subtype' => 'by_product',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'charts' => [
                'top_products' => $this->generateChartData($productSales->take(10), 'bar', [
                    'title' => $this->trans('reports.top_10_products'),
                    'label_field' => 'name',
                    'value_field' => 'total_sales',
                    'dataset_label' => $this->trans('reports.sales_amount')
                ]),
                'quantity_sold' => $this->generateChartData($productSales->take(10), 'pie', [
                    'title' => $this->trans('reports.quantity_distribution'),
                    'label_field' => 'name',
                    'value_field' => 'total_quantity'
                ])
            ],
            'data' => $productSales->toArray(),
            'summary' => [
                'total_products' => $productSales->count(),
                'total_quantity' => $productSales->sum('total_quantity'),
                'total_sales' => $productSales->sum('total_sales')
            ]
        ];
    }

    /**
     * Get base invoice query with filters
     */
    protected function getBaseInvoiceQuery()
    {
        $query = Invoice::whereBetween('issue_date', [$this->startDate, $this->endDate]);

        if (isset($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['payment_status'])) {
            $query->where('payment_status', $this->filters['payment_status']);
        }

        return $query;
    }

    /**
     * Get previous period data for comparison
     */
    protected function getPreviousPeriodData(): array
    {
        $daysDiff = $this->startDate->diffInDays($this->endDate);
        $previousStart = $this->startDate->copy()->subDays($daysDiff + 1);
        $previousEnd = $this->startDate->copy()->subDay();

        $previousInvoices = Invoice::whereBetween('issue_date', [$previousStart, $previousEnd])->get();

        return [
            'total_sales' => $previousInvoices->sum('total_amount'),
            'total_invoices' => $previousInvoices->count(),
            'average_order_value' => $previousInvoices->count() > 0 ? $previousInvoices->sum('total_amount') / $previousInvoices->count() : 0
        ];
    }

    /**
     * Get daily sales data
     */
    protected function getDailySalesData(Collection $invoices): Collection
    {
        return $invoices->groupBy(function ($invoice) {
            return $invoice->issue_date->format('Y-m-d');
        })->map(function ($dayInvoices, $date) {
            return [
                'date' => $date,
                'total' => $dayInvoices->sum('total_amount'),
                'count' => $dayInvoices->count()
            ];
        })->values();
    }

    /**
     * Get top customers
     */
    protected function getTopCustomers(Collection $invoices): Collection
    {
        return $invoices->groupBy('customer_id')
            ->map(function ($customerInvoices) {
                $customer = $customerInvoices->first()->customer;
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'total' => $customerInvoices->sum('total_amount'),
                    'count' => $customerInvoices->count()
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();
    }

    /**
     * Get top products
     */
    protected function getTopProducts(Collection $invoices): Collection
    {
        $items = $invoices->flatMap->items;
        
        return $items->groupBy('inventory_item_id')
            ->map(function ($productItems) {
                $item = $productItems->first();
                return [
                    'id' => $item->inventory_item_id,
                    'name' => $item->inventoryItem->name ?? $item->description,
                    'quantity' => $productItems->sum('quantity'),
                    'total' => $productItems->sum('total_price')
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();
    }

    /**
     * Get sales by period
     */
    protected function getSalesByPeriod(string $periodType): Collection
    {
        $format = match ($periodType) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            'quarterly' => '%Y-Q%q',
            default => '%Y-%m-%d'
        };

        return collect(DB::select("
            SELECT 
                DATE_FORMAT(issue_date, ?) as period,
                COUNT(*) as invoice_count,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as average_order_value
            FROM invoices 
            WHERE issue_date BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(issue_date, ?)
            ORDER BY period
        ", [$format, $this->startDate, $this->endDate, $format]));
    }
}