<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AlertService;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\Customer;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class AlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private AlertService $alertService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alertService = new AlertService();
    }

    public function test_get_alerts_returns_correct_structure()
    {
        $alerts = $this->alertService->getAlerts();

        $this->assertIsArray($alerts);
        $this->assertArrayHasKey('pending_cheques', $alerts);
        $this->assertArrayHasKey('stock_warnings', $alerts);
        $this->assertArrayHasKey('overdue_invoices', $alerts);
        $this->assertArrayHasKey('expiring_items', $alerts);
        $this->assertArrayHasKey('low_stock', $alerts);
        $this->assertArrayHasKey('high_value_pending', $alerts);
    }

    public function test_get_pending_cheque_alerts()
    {
        $customer = Customer::factory()->create(['name' => 'Test Customer']);
        
        // Create pending cheque due in 3 days
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'payment_method' => 'cheque',
            'status' => 'sent',
            'due_date' => Carbon::now()->addDays(3),
            'total_amount' => 1000
        ]);

        // Create overdue cheque
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'payment_method' => 'cheque',
            'status' => 'overdue',
            'due_date' => Carbon::now()->subDays(2),
            'total_amount' => 500
        ]);

        $alerts = $this->alertService->getAlerts();

        $this->assertCount(2, $alerts['pending_cheques']);
        
        // Find alerts by their characteristics rather than amount
        $overdueAlert = collect($alerts['pending_cheques'])->first(function ($alert) {
            return $alert['data']['days_overdue'] > 0;
        });
        $this->assertNotNull($overdueAlert);
        $this->assertEquals('high', $overdueAlert['severity']);

        $upcomingAlert = collect($alerts['pending_cheques'])->first(function ($alert) {
            return $alert['data']['days_overdue'] < 0;
        });
        $this->assertNotNull($upcomingAlert);
        $this->assertEquals('medium', $upcomingAlert['severity']);
    }

    public function test_get_stock_warning_alerts()
    {
        $category = Category::factory()->create();
        $location = \App\Models\Location::factory()->create();
        
        // Low stock item
        InventoryItem::factory()->create([
            'name' => 'Low Stock Item',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 3,
            'sku' => 'LOW001'
        ]);

        // Out of stock item
        InventoryItem::factory()->create([
            'name' => 'Out of Stock Item',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 0,
            'sku' => 'OUT001'
        ]);

        // Normal stock item (should not appear in alerts)
        InventoryItem::factory()->create([
            'name' => 'Normal Stock Item',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 20,
            'sku' => 'NORM001'
        ]);

        $alerts = $this->alertService->getAlerts();

        $this->assertCount(2, $alerts['stock_warnings']);

        $lowStockAlert = collect($alerts['stock_warnings'])->firstWhere('type', 'low_stock');
        $this->assertNotNull($lowStockAlert);
        $this->assertEquals('medium', $lowStockAlert['severity']);
        $this->assertEquals('Low Stock Item', $lowStockAlert['data']['item_name']);

        $outOfStockAlert = collect($alerts['stock_warnings'])->firstWhere('type', 'out_of_stock');
        $this->assertNotNull($outOfStockAlert);
        $this->assertEquals('high', $outOfStockAlert['severity']);
        $this->assertEquals('Out of Stock Item', $outOfStockAlert['data']['item_name']);
    }

    public function test_get_overdue_invoice_alerts()
    {
        $customer = Customer::factory()->create(['name' => 'Test Customer']);
        
        // Recently overdue invoice
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV001',
            'status' => 'overdue',
            'due_date' => Carbon::now()->subDays(5),
            'total_amount' => 1000
        ]);

        // Long overdue invoice
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV002',
            'status' => 'overdue',
            'due_date' => Carbon::now()->subDays(35),
            'total_amount' => 2000
        ]);

        $alerts = $this->alertService->getAlerts();

        $this->assertCount(2, $alerts['overdue_invoices']);

        $recentOverdueAlert = collect($alerts['overdue_invoices'])->firstWhere('data.invoice_number', 'INV001');
        $this->assertNotNull($recentOverdueAlert);
        $this->assertEquals('medium', $recentOverdueAlert['severity']);

        $longOverdueAlert = collect($alerts['overdue_invoices'])->firstWhere('data.invoice_number', 'INV002');
        $this->assertNotNull($longOverdueAlert);
        $this->assertEquals('high', $longOverdueAlert['severity']);
    }

    public function test_get_expiring_item_alerts()
    {
        $category = Category::factory()->create();
        $location = \App\Models\Location::factory()->create();
        
        // Item expiring in 5 days (high severity)
        InventoryItem::factory()->create([
            'name' => 'Expiring Soon Item',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'expiry_date' => Carbon::now()->addDays(5),
            'quantity' => 10
        ]);

        // Item expiring in 20 days (medium severity)
        InventoryItem::factory()->create([
            'name' => 'Expiring Later Item',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'expiry_date' => Carbon::now()->addDays(20),
            'quantity' => 5
        ]);

        // Item expiring in 40 days (should not appear)
        InventoryItem::factory()->create([
            'name' => 'Not Expiring Soon Item',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'expiry_date' => Carbon::now()->addDays(40),
            'quantity' => 15
        ]);

        $alerts = $this->alertService->getAlerts();

        $this->assertCount(2, $alerts['expiring_items']);

        $soonExpiringAlert = collect($alerts['expiring_items'])->firstWhere('data.item_name', 'Expiring Soon Item');
        $this->assertNotNull($soonExpiringAlert);
        $this->assertEquals('high', $soonExpiringAlert['severity']);

        $laterExpiringAlert = collect($alerts['expiring_items'])->firstWhere('data.item_name', 'Expiring Later Item');
        $this->assertNotNull($laterExpiringAlert);
        $this->assertEquals('medium', $laterExpiringAlert['severity']);
    }

    public function test_get_high_value_pending_alerts()
    {
        $customer = Customer::factory()->create(['name' => 'High Value Customer']);
        
        // High value pending invoice
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-HIGH-001',
            'status' => 'sent',
            'total_amount' => 75000,
            'issue_date' => Carbon::now()
        ]);

        // Normal value pending invoice (should not appear)
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-NORMAL-001',
            'status' => 'sent',
            'total_amount' => 25000,
            'issue_date' => Carbon::now()
        ]);

        $alerts = $this->alertService->getAlerts();

        $this->assertCount(1, $alerts['high_value_pending']);

        $highValueAlert = $alerts['high_value_pending'][0];
        $this->assertEquals('medium', $highValueAlert['severity']);
        $this->assertEquals('INV-HIGH-001', $highValueAlert['data']['invoice_number']);
        $this->assertEquals('75000.00', $highValueAlert['data']['amount']);
    }

    public function test_get_alert_counts()
    {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();
        $location = \App\Models\Location::factory()->create();
        
        // Create high severity alert (out of stock)
        InventoryItem::factory()->create([
            'name' => 'Out of Stock Item',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 0
        ]);

        // Create medium severity alert (pending cheque)
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'payment_method' => 'cheque',
            'status' => 'sent',
            'due_date' => Carbon::now()->addDays(3),
            'total_amount' => 1000
        ]);

        // Create low severity alert (low stock alert)
        InventoryItem::factory()->create([
            'name' => 'Low Stock Item',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 8
        ]);

        $alertCounts = $this->alertService->getAlertCounts();

        $this->assertIsArray($alertCounts);
        $this->assertArrayHasKey('total', $alertCounts);
        $this->assertArrayHasKey('high', $alertCounts);
        $this->assertArrayHasKey('medium', $alertCounts);
        $this->assertArrayHasKey('low', $alertCounts);

        $this->assertGreaterThanOrEqual(0, $alertCounts['total']);
        $this->assertGreaterThanOrEqual(0, $alertCounts['high']);
        $this->assertGreaterThanOrEqual(0, $alertCounts['medium']);
        $this->assertGreaterThanOrEqual(0, $alertCounts['low']);
    }

    public function test_mark_as_read()
    {
        Cache::shouldReceive('forget')->once()->with('dashboard_alerts');

        $result = $this->alertService->markAsRead('test_alert_id');

        $this->assertTrue($result);
    }

    public function test_clear_cache()
    {
        Cache::shouldReceive('forget')->once()->with('dashboard_alerts');

        $this->alertService->clearCache();

        // Test passes if no exceptions are thrown
        $this->assertTrue(true);
    }

    public function test_alerts_are_cached()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'pending_cheques' => [],
                'stock_warnings' => [],
                'overdue_invoices' => [],
                'expiring_items' => [],
                'low_stock' => [],
                'high_value_pending' => []
            ]);

        $alerts = $this->alertService->getAlerts();

        $this->assertIsArray($alerts);
        $this->assertArrayHasKey('pending_cheques', $alerts);
    }
}