<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DashboardService;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = new DashboardService();
    }

    public function test_get_kpis_returns_correct_structure()
    {
        $kpis = $this->dashboardService->getKPIs();

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('gold_sold', $kpis);
        $this->assertArrayHasKey('total_profits', $kpis);
        $this->assertArrayHasKey('average_price', $kpis);
        $this->assertArrayHasKey('returns', $kpis);
        $this->assertArrayHasKey('gross_margin', $kpis);
        $this->assertArrayHasKey('net_margin', $kpis);
        $this->assertArrayHasKey('total_sales', $kpis);
        $this->assertArrayHasKey('active_customers', $kpis);
        $this->assertArrayHasKey('inventory_value', $kpis);
        $this->assertArrayHasKey('pending_invoices', $kpis);
    }

    public function test_get_kpis_with_date_range()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $kpis = $this->dashboardService->getKPIs([
            'start' => $startDate,
            'end' => $endDate
        ]);

        $this->assertIsArray($kpis);
        $this->assertIsNumeric($kpis['total_sales']);
        $this->assertIsNumeric($kpis['total_profits']);
    }

    public function test_calculate_gold_sold_with_invoices()
    {
        // Create test data
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();
        $location = \App\Models\Location::factory()->create();
        
        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'weight' => 10.5,
            'cost_price' => 100
        ]);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => Carbon::now(),
            'total_amount' => 1000
        ]);

        \App\Models\InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity' => 2,
            'unit_price' => 500
        ]);

        $kpis = $this->dashboardService->getKPIs();

        $this->assertEquals(21.0, $kpis['gold_sold']); // 2 * 10.5
    }

    public function test_calculate_total_sales()
    {
        $customer = Customer::factory()->create();
        
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => Carbon::now(),
            'total_amount' => 1000
        ]);

        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => Carbon::now(),
            'total_amount' => 500
        ]);

        // Draft invoice should not be counted
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'draft',
            'issue_date' => Carbon::now(),
            'total_amount' => 300
        ]);

        $kpis = $this->dashboardService->getKPIs();

        $this->assertEquals(1500, $kpis['total_sales']);
    }

    public function test_calculate_returns()
    {
        $customer = Customer::factory()->create();
        
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'cancelled',
            'issue_date' => Carbon::now(),
            'total_amount' => 200
        ]);

        $kpis = $this->dashboardService->getKPIs();

        $this->assertEquals(200, $kpis['returns']);
    }

    public function test_get_sales_chart_data_weekly()
    {
        $chartData = $this->dashboardService->getSalesChartData('week');

        $this->assertIsArray($chartData);
        $this->assertCount(7, $chartData); // 7 days in a week

        foreach ($chartData as $data) {
            $this->assertArrayHasKey('date', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('sales', $data);
            $this->assertIsNumeric($data['sales']);
        }
    }

    public function test_get_sales_chart_data_monthly()
    {
        $chartData = $this->dashboardService->getSalesChartData('month');

        $this->assertIsArray($chartData);
        $this->assertCount(12, $chartData); // 12 months in a year

        foreach ($chartData as $data) {
            $this->assertArrayHasKey('date', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('sales', $data);
            $this->assertIsNumeric($data['sales']);
        }
    }

    public function test_get_sales_chart_data_yearly()
    {
        $chartData = $this->dashboardService->getSalesChartData('year');

        $this->assertIsArray($chartData);
        $this->assertCount(5, $chartData); // 5 years (current year - 4 to current year)

        foreach ($chartData as $data) {
            $this->assertArrayHasKey('date', $data);
            $this->assertArrayHasKey('label', $data);
            $this->assertArrayHasKey('sales', $data);
            $this->assertIsNumeric($data['sales']);
        }
    }

    public function test_get_category_performance()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Gold Rings']);
        $location = \App\Models\Location::factory()->create();
        $customer = Customer::factory()->create();
        
        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'cost_price' => 100
        ]);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => Carbon::now(),
            'total_amount' => 500
        ]);

        \App\Models\InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity' => 1,
            'unit_price' => 500
        ]);

        $performance = $this->dashboardService->getCategoryPerformance();

        $this->assertIsArray($performance);
        $this->assertNotEmpty($performance);

        $categoryData = collect($performance)->firstWhere('name', 'Gold Rings');
        $this->assertNotNull($categoryData);
        $this->assertEquals(500, $categoryData->total_revenue);
        $this->assertEquals(400, $categoryData->profit); // 500 - 100
    }

    public function test_get_inventory_value()
    {
        $category = Category::factory()->create();
        $location = \App\Models\Location::factory()->create();
        
        InventoryItem::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 5,
            'unit_price' => 100
        ]);

        InventoryItem::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 3,
            'unit_price' => 200
        ]);

        $kpis = $this->dashboardService->getKPIs();

        $this->assertEquals(1100, $kpis['inventory_value']); // (5 * 100) + (3 * 200)
    }

    public function test_get_pending_invoices_count()
    {
        $customer = Customer::factory()->create();
        
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'sent'
        ]);

        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'overdue'
        ]);

        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid'
        ]);

        $kpis = $this->dashboardService->getKPIs();

        $this->assertEquals(2, $kpis['pending_invoices']);
    }

    public function test_get_active_customers()
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $customer3 = Customer::factory()->create();
        
        // Customer 1 has invoice in current month
        Invoice::factory()->create([
            'customer_id' => $customer1->id,
            'issue_date' => Carbon::now()
        ]);

        // Customer 2 has invoice in current month
        Invoice::factory()->create([
            'customer_id' => $customer2->id,
            'issue_date' => Carbon::now()
        ]);

        // Customer 3 has no invoices in current month
        Invoice::factory()->create([
            'customer_id' => $customer3->id,
            'issue_date' => Carbon::now()->subMonths(2)
        ]);

        $kpis = $this->dashboardService->getKPIs();

        $this->assertEquals(2, $kpis['active_customers']);
    }

    public function test_cache_is_used_for_kpis()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'gold_sold' => 100,
                'total_profits' => 500,
                'average_price' => 250,
                'returns' => 50,
                'gross_margin' => 20,
                'net_margin' => 15,
                'total_sales' => 1000,
                'active_customers' => 5,
                'inventory_value' => 2000,
                'pending_invoices' => 3
            ]);

        $kpis = $this->dashboardService->getKPIs();

        $this->assertEquals(100, $kpis['gold_sold']);
        $this->assertEquals(500, $kpis['total_profits']);
    }

    public function test_clear_cache()
    {
        Cache::shouldReceive('forget')->times(3);

        $this->dashboardService->clearCache();

        // Test passes if no exceptions are thrown
        $this->assertTrue(true);
    }
}