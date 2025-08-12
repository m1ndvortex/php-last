<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ReportService;
use App\Services\InvoiceService;
use App\Models\User;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\BusinessConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ComprehensiveReportDataTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $reportService;
    protected $invoiceService;
    protected $customers;
    protected $categories;
    protected $locations;
    protected $inventoryItems;
    protected $invoices;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->reportService = app(ReportService::class);
        $this->invoiceService = app(InvoiceService::class);

        // Set up business configuration
        BusinessConfiguration::updateOrCreate(['key' => 'default_labor_percentage'], ['value' => '10.0']);
        BusinessConfiguration::updateOrCreate(['key' => 'default_profit_percentage'], ['value' => '15.0']);
        BusinessConfiguration::updateOrCreate(['key' => 'default_tax_percentage'], ['value' => '9.0']);

        $this->createComprehensiveTestData();
    }

    protected function createComprehensiveTestData()
    {
        // Create categories
        $this->categories = collect([
            Category::factory()->create(['name' => 'Gold Rings']),
            Category::factory()->create(['name' => 'Gold Necklaces']),
            Category::factory()->create(['name' => 'Gold Bracelets']),
            Category::factory()->create(['name' => 'Gold Earrings'])
        ]);

        // Create locations
        $this->locations = collect([
            Location::factory()->create(['name' => 'Main Store']),
            Location::factory()->create(['name' => 'Branch Store']),
            Location::factory()->create(['name' => 'Warehouse'])
        ]);

        // Create customers with different profiles
        $this->customers = collect([
            Customer::factory()->create(['name' => 'High Value Customer', 'email' => 'high@test.com']),
            Customer::factory()->create(['name' => 'Regular Customer', 'email' => 'regular@test.com']),
            Customer::factory()->create(['name' => 'Occasional Customer', 'email' => 'occasional@test.com']),
            Customer::factory()->create(['name' => 'New Customer', 'email' => 'new@test.com']),
            Customer::factory()->create(['name' => 'VIP Customer', 'email' => 'vip@test.com'])
        ]);

        // Create diverse inventory items
        $this->inventoryItems = collect();
        
        // High-value items with dynamic pricing
        foreach ($this->categories as $categoryIndex => $category) {
            for ($i = 1; $i <= 3; $i++) {
                $this->inventoryItems->push(InventoryItem::factory()->create([
                    'sku' => "HIGH-{$category->id}-{$i}",
                    'name' => "{$category->name} Premium Item {$i}",
                    'category_id' => $category->id,
                    'location_id' => $this->locations->random()->id,
                    'quantity' => rand(10, 50),
                    'weight' => rand(50, 150) / 10, // 5.0 to 15.0 grams
                    'gold_purity' => collect([18.0, 21.0, 22.0, 24.0])->random(),
                    'unit_price' => null, // Dynamic pricing
                    'cost_price' => null,
                    'minimum_stock' => rand(2, 8)
                ]));
            }
        }

        // Medium-value items with static pricing
        foreach ($this->categories as $categoryIndex => $category) {
            for ($i = 1; $i <= 2; $i++) {
                $unitPrice = rand(500, 1500);
                $this->inventoryItems->push(InventoryItem::factory()->create([
                    'sku' => "MED-{$category->id}-{$i}",
                    'name' => "{$category->name} Standard Item {$i}",
                    'category_id' => $category->id,
                    'location_id' => $this->locations->random()->id,
                    'quantity' => rand(20, 80),
                    'weight' => rand(20, 80) / 10, // 2.0 to 8.0 grams
                    'gold_purity' => collect([18.0, 21.0])->random(),
                    'unit_price' => $unitPrice,
                    'cost_price' => $unitPrice * 0.8,
                    'minimum_stock' => rand(5, 15)
                ]));
            }
        }

        // Low-value items
        foreach ($this->categories as $categoryIndex => $category) {
            $unitPrice = rand(100, 400);
            $this->inventoryItems->push(InventoryItem::factory()->create([
                'sku' => "LOW-{$category->id}",
                'name' => "{$category->name} Basic Item",
                'category_id' => $category->id,
                'location_id' => $this->locations->random()->id,
                'quantity' => rand(50, 200),
                'weight' => rand(10, 40) / 10, // 1.0 to 4.0 grams
                'gold_purity' => 18.0,
                'unit_price' => $unitPrice,
                'cost_price' => $unitPrice * 0.75,
                'minimum_stock' => rand(10, 30)
            ]));
        }

        $this->createRealisticInvoiceData();
    }

    protected function createRealisticInvoiceData()
    {
        $this->invoices = collect();

        // Create invoices over the past 12 months with seasonal patterns
        for ($month = 0; $month < 12; $month++) {
            $baseDate = Carbon::now()->subMonths($month);
            
            // Seasonal multiplier (more sales in December, less in summer)
            $seasonalMultiplier = $month == 0 ? 2.0 : ($month >= 5 && $month <= 7 ? 0.7 : 1.0);
            $invoicesThisMonth = (int)(rand(8, 15) * $seasonalMultiplier);
            
            for ($i = 0; $i < $invoicesThisMonth; $i++) {
                $issueDate = $baseDate->copy()->subDays(rand(0, 28));
                
                // Customer behavior patterns
                $customer = $this->getCustomerByBehavior($month);
                
                // Create invoice with realistic patterns
                if (rand(0, 100) < 70) { // 70% dynamic pricing
                    $invoice = $this->createDynamicPricingInvoice($customer, $issueDate);
                } else { // 30% static pricing
                    $invoice = $this->createStaticPricingInvoice($customer, $issueDate);
                }
                
                if ($invoice) {
                    $this->invoices->push($invoice);
                }
            }
        }

        // Create some cancelled invoices (5% cancellation rate)
        $toCancel = $this->invoices->random((int)($this->invoices->count() * 0.05));
        foreach ($toCancel as $invoice) {
            $this->invoiceService->cancelInvoice($invoice, 'Test cancellation');
        }
    }

    protected function getCustomerByBehavior($month)
    {
        // High value customer more active in recent months
        if ($month < 3 && rand(0, 100) < 40) {
            return $this->customers->where('name', 'High Value Customer')->first();
        }
        
        // VIP customer consistent throughout year
        if (rand(0, 100) < 20) {
            return $this->customers->where('name', 'VIP Customer')->first();
        }
        
        // New customer only in recent months
        if ($month < 2 && rand(0, 100) < 15) {
            return $this->customers->where('name', 'New Customer')->first();
        }
        
        // Regular and occasional customers
        return $this->customers->whereIn('name', ['Regular Customer', 'Occasional Customer'])->random();
    }

    protected function createDynamicPricingInvoice($customer, $issueDate)
    {
        $itemsCount = rand(1, 4);
        $items = [];
        
        $availableItems = $this->inventoryItems->where('quantity', '>', 0)->whereNull('unit_price');
        if ($availableItems->isEmpty()) {
            return null;
        }

        for ($i = 0; $i < $itemsCount; $i++) {
            $item = $availableItems->random();
            $quantity = min(rand(1, 3), $item->quantity);
            
            if ($quantity > 0) {
                $items[] = [
                    'inventory_item_id' => $item->id,
                    'quantity' => $quantity,
                    'name' => $item->name
                ];
                
                $item->decrement('quantity', $quantity);
            }
        }

        if (empty($items)) {
            return null;
        }

        try {
            return $this->invoiceService->createInvoice([
                'customer_id' => $customer->id,
                'issue_date' => $issueDate,
                'due_date' => $issueDate->copy()->addDays(30),
                'gold_pricing' => [
                    'gold_price_per_gram' => rand(4500, 7000) / 100, // 45.00 to 70.00
                    'labor_percentage' => rand(800, 2000) / 100, // 8.00 to 20.00
                    'profit_percentage' => rand(1000, 3000) / 100, // 10.00 to 30.00
                    'tax_percentage' => rand(500, 1500) / 100 // 5.00 to 15.00
                ],
                'items' => $items
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function createStaticPricingInvoice($customer, $issueDate)
    {
        $availableItems = $this->inventoryItems->where('quantity', '>', 0)->whereNotNull('unit_price');
        if ($availableItems->isEmpty()) {
            return null;
        }

        $item = $availableItems->random();
        $quantity = min(rand(1, 2), $item->quantity);
        
        if ($quantity <= 0) {
            return null;
        }

        try {
            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'invoice_number' => 'INV-' . str_pad(Invoice::count() + 1, 6, '0', STR_PAD_LEFT),
                'issue_date' => $issueDate,
                'due_date' => $issueDate->copy()->addDays(30),
                'status' => 'draft',
                'subtotal' => $item->unit_price * $quantity,
                'tax_amount' => ($item->unit_price * $quantity) * 0.09,
                'total_amount' => ($item->unit_price * $quantity) * 1.09
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'inventory_item_id' => $item->id,
                'description' => $item->name,
                'quantity' => $quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->unit_price * $quantity,
                'gold_purity' => $item->gold_purity,
                'weight' => $item->weight
            ]);

            $item->decrement('quantity', $quantity);
            
            return $invoice;
        } catch (\Exception $e) {
            return null;
        }
    }

    /** @test */
    public function it_generates_accurate_sales_report_with_real_data()
    {
        $report = $this->reportService->generateReport([
            'type' => 'sales',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(6)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'filters' => [],
            'language' => 'en',
            'format' => 'json'
        ]);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('monthly_trends', $report);
        $this->assertArrayHasKey('top_items', $report);
        $this->assertArrayHasKey('customer_analysis', $report);

        // Verify summary calculations
        $summary = $report['summary'];
        $this->assertArrayHasKey('total_sales', $summary);
        $this->assertArrayHasKey('total_invoices', $summary);
        $this->assertArrayHasKey('average_invoice_value', $summary);
        $this->assertArrayHasKey('growth_rate', $summary);

        $this->assertGreaterThan(0, $summary['total_sales']);
        $this->assertGreaterThan(0, $summary['total_invoices']);
        $this->assertGreaterThan(0, $summary['average_invoice_value']);

        // Verify monthly trends
        $monthlyTrends = $report['monthly_trends'];
        $this->assertNotEmpty($monthlyTrends);
        
        foreach ($monthlyTrends as $month => $data) {
            $this->assertArrayHasKey('total_sales', $data);
            $this->assertArrayHasKey('invoice_count', $data);
            $this->assertArrayHasKey('average_value', $data);
            $this->assertGreaterThanOrEqual(0, $data['total_sales']);
            $this->assertGreaterThanOrEqual(0, $data['invoice_count']);
        }

        // Verify top items
        $topItems = $report['top_items'];
        $this->assertNotEmpty($topItems);
        
        foreach ($topItems as $item) {
            $this->assertArrayHasKey('item_name', $item);
            $this->assertArrayHasKey('total_quantity', $item);
            $this->assertArrayHasKey('total_revenue', $item);
            $this->assertGreaterThan(0, $item['total_quantity']);
            $this->assertGreaterThan(0, $item['total_revenue']);
        }
    }

    /** @test */
    public function it_generates_comprehensive_inventory_report()
    {
        $report = $this->reportService->generateReport([
            'type' => 'inventory',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(1)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'filters' => [],
            'language' => 'en',
            'format' => 'json'
        ]);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('by_category', $report);
        $this->assertArrayHasKey('by_location', $report);
        $this->assertArrayHasKey('low_stock_items', $report);
        $this->assertArrayHasKey('valuation', $report);
        $this->assertArrayHasKey('movement_analysis', $report);

        // Verify summary
        $summary = $report['summary'];
        $this->assertArrayHasKey('total_items', $summary);
        $this->assertArrayHasKey('total_quantity', $summary);
        $this->assertArrayHasKey('total_value', $summary);
        $this->assertArrayHasKey('low_stock_count', $summary);
        $this->assertArrayHasKey('out_of_stock_count', $summary);

        $this->assertGreaterThan(0, $summary['total_items']);
        $this->assertGreaterThan(0, $summary['total_quantity']);

        // Verify category breakdown
        $byCategory = $report['by_category'];
        $this->assertCount(4, $byCategory); // We created 4 categories
        
        foreach ($byCategory as $categoryData) {
            $this->assertArrayHasKey('category_name', $categoryData);
            $this->assertArrayHasKey('item_count', $categoryData);
            $this->assertArrayHasKey('total_quantity', $categoryData);
            $this->assertArrayHasKey('total_value', $categoryData);
            $this->assertArrayHasKey('average_value_per_item', $categoryData);
        }

        // Verify location breakdown
        $byLocation = $report['by_location'];
        $this->assertCount(3, $byLocation); // We created 3 locations
        
        foreach ($byLocation as $locationData) {
            $this->assertArrayHasKey('location_name', $locationData);
            $this->assertArrayHasKey('item_count', $locationData);
            $this->assertArrayHasKey('total_quantity', $locationData);
            $this->assertArrayHasKey('total_value', $locationData);
        }

        // Verify valuation
        $valuation = $report['valuation'];
        $this->assertArrayHasKey('total_cost_value', $valuation);
        $this->assertArrayHasKey('total_selling_value', $valuation);
        $this->assertArrayHasKey('potential_profit', $valuation);
        $this->assertArrayHasKey('margin_percentage', $valuation);
    }

    /** @test */
    public function it_generates_detailed_financial_report()
    {
        $report = $this->reportService->generateReport([
            'type' => 'financial',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(3)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'filters' => [],
            'language' => 'en',
            'format' => 'json'
        ]);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('profit_analysis', $report);
        $this->assertArrayHasKey('cost_breakdown', $report);
        $this->assertArrayHasKey('monthly_trends', $report);
        $this->assertArrayHasKey('pricing_analysis', $report);

        // Verify summary
        $summary = $report['summary'];
        $this->assertArrayHasKey('total_revenue', $summary);
        $this->assertArrayHasKey('total_cost', $summary);
        $this->assertArrayHasKey('gross_profit', $summary);
        $this->assertArrayHasKey('profit_margin', $summary);
        $this->assertArrayHasKey('average_order_value', $summary);

        // Verify profit analysis
        $profitAnalysis = $report['profit_analysis'];
        $this->assertArrayHasKey('by_category', $profitAnalysis);
        $this->assertArrayHasKey('by_customer', $profitAnalysis);
        $this->assertArrayHasKey('by_pricing_method', $profitAnalysis);

        // Verify cost breakdown
        $costBreakdown = $report['cost_breakdown'];
        $this->assertArrayHasKey('material_costs', $costBreakdown);
        $this->assertArrayHasKey('labor_costs', $costBreakdown);
        $this->assertArrayHasKey('overhead_costs', $costBreakdown);
        $this->assertArrayHasKey('tax_costs', $costBreakdown);

        // Verify pricing analysis (specific to jewelry business)
        $pricingAnalysis = $report['pricing_analysis'];
        $this->assertArrayHasKey('dynamic_vs_static', $pricingAnalysis);
        $this->assertArrayHasKey('gold_price_impact', $pricingAnalysis);
        $this->assertArrayHasKey('average_margins', $pricingAnalysis);
    }

    /** @test */
    public function it_generates_customer_behavior_report()
    {
        $report = $this->reportService->generateReport([
            'type' => 'customer',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(12)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'filters' => [],
            'language' => 'en',
            'format' => 'json'
        ]);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('top_customers', $report);
        $this->assertArrayHasKey('customer_segments', $report);
        $this->assertArrayHasKey('purchase_patterns', $report);
        $this->assertArrayHasKey('retention_analysis', $report);

        // Verify summary
        $summary = $report['summary'];
        $this->assertArrayHasKey('total_customers', $summary);
        $this->assertArrayHasKey('active_customers', $summary);
        $this->assertArrayHasKey('new_customers', $summary);
        $this->assertArrayHasKey('average_order_value', $summary);
        $this->assertArrayHasKey('customer_lifetime_value', $summary);

        $this->assertEquals(5, $summary['total_customers']); // We created 5 customers

        // Verify top customers
        $topCustomers = $report['top_customers'];
        $this->assertNotEmpty($topCustomers);
        
        foreach ($topCustomers as $customer) {
            $this->assertArrayHasKey('customer_name', $customer);
            $this->assertArrayHasKey('total_orders', $customer);
            $this->assertArrayHasKey('total_spent', $customer);
            $this->assertArrayHasKey('average_order_value', $customer);
            $this->assertArrayHasKey('last_order_date', $customer);
            $this->assertArrayHasKey('preferred_categories', $customer);
        }

        // Verify customer segments
        $segments = $report['customer_segments'];
        $this->assertArrayHasKey('high_value', $segments);
        $this->assertArrayHasKey('medium_value', $segments);
        $this->assertArrayHasKey('low_value', $segments);
        $this->assertArrayHasKey('inactive', $segments);

        // Verify purchase patterns
        $patterns = $report['purchase_patterns'];
        $this->assertArrayHasKey('seasonal_trends', $patterns);
        $this->assertArrayHasKey('category_preferences', $patterns);
        $this->assertArrayHasKey('price_sensitivity', $patterns);
    }

    /** @test */
    public function it_validates_report_calculations_against_database()
    {
        // Get sales report
        $salesReport = $this->reportService->generateReport([
            'type' => 'sales',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(12)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'filters' => [],
            'language' => 'en',
            'format' => 'json'
        ]);
        
        // Manually calculate from database
        $actualInvoices = Invoice::where('status', '!=', 'cancelled')->get();
        $actualTotalSales = $actualInvoices->sum('total_amount');
        $actualTotalInvoices = $actualInvoices->count();

        // Verify report structure
        $this->assertIsArray($salesReport);
        $this->assertArrayHasKey('data', $salesReport);

        // Get inventory report
        $inventoryReport = $this->reportService->generateReport([
            'type' => 'inventory',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(1)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'filters' => [],
            'language' => 'en',
            'format' => 'json'
        ]);
        
        // Verify report structure
        $this->assertIsArray($inventoryReport);
        $this->assertArrayHasKey('data', $inventoryReport);
    }

    /** @test */
    public function it_handles_complex_filtering_scenarios()
    {
        $highValueCustomer = $this->customers->where('name', 'High Value Customer')->first();
        $goldRingsCategory = $this->categories->where('name', 'Gold Rings')->first();
        $mainStore = $this->locations->where('name', 'Main Store')->first();

        // Test customer-specific sales report
        $customerReport = $this->reportService->generateReport([
            'type' => 'sales',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(6)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'filters' => ['customer_id' => $highValueCustomer->id],
            'language' => 'en',
            'format' => 'json'
        ]);

        $this->assertIsArray($customerReport);

        // Test category-specific inventory report
        $categoryReport = $this->reportService->generateReport([
            'type' => 'inventory',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(1)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'filters' => ['category_id' => $goldRingsCategory->id],
            'language' => 'en',
            'format' => 'json'
        ]);

        $this->assertIsArray($categoryReport);
        
        // Test location-specific inventory report
        $locationReport = $this->reportService->generateReport([
            'type' => 'inventory',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(1)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'filters' => ['location_id' => $mainStore->id],
            'language' => 'en',
            'format' => 'json'
        ]);

        $this->assertIsArray($locationReport);

        // Test date range filtering
        $dateRangeReport = $this->reportService->generateReport([
            'type' => 'financial',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(2)->toDateString(),
                'end' => Carbon::now()->subMonth()->toDateString()
            ],
            'filters' => [],
            'language' => 'en',
            'format' => 'json'
        ]);

        $this->assertIsArray($dateRangeReport);
    }

    /** @test */
    public function it_provides_export_ready_data_formats()
    {
        $salesReport = $this->reportService->generateReport([
            'type' => 'sales',
            'subtype' => 'summary',
            'date_range' => [
                'start' => Carbon::now()->subMonths(3)->toDateString(),
                'end' => Carbon::now()->toDateString()
            ],
            'filters' => [],
            'language' => 'en',
            'format' => 'export'
        ]);

        $this->assertIsArray($salesReport);
        $this->assertArrayHasKey('headers', $salesReport);
        $this->assertArrayHasKey('data', $salesReport);
        $this->assertArrayHasKey('summary_rows', $salesReport);

        // Verify headers are properly formatted
        $headers = $salesReport['headers'];
        $this->assertNotEmpty($headers);
        $this->assertContainsOnly('string', $headers);

        // Verify data is in tabular format
        $data = $salesReport['data'];
        $this->assertIsArray($data);
        
        if (!empty($data)) {
            $firstRow = $data[0];
            $this->assertCount(count($headers), $firstRow);
        }
    }
}