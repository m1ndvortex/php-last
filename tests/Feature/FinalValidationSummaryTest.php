<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Location;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\BusinessConfiguration;
use App\Services\GoldPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FinalValidationSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        // Set up business configuration
        BusinessConfiguration::setValue('default_labor_percentage', 10.0, 'float', 'pricing');
        BusinessConfiguration::setValue('default_profit_percentage', 15.0, 'float', 'pricing');
        BusinessConfiguration::setValue('default_tax_percentage', 9.0, 'float', 'pricing');
    }

    /** @test */
    public function test_requirement_1_inventory_item_creation_console_errors_fixed()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        // Test all form data endpoints work without errors
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200);

        $response = $this->getJson('/api/locations');
        $response->assertStatus(200);

        $response = $this->getJson('/api/inventory/gold-purity-options');
        $response->assertStatus(200);

        // Test item creation works without console errors
        $itemData = [
            'sku' => 'REQ1-TEST-001',
            'name' => 'Test Item for Requirement 1',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 10,
            'gold_purity' => 18.0,
            'weight' => 5.5
        ];

        $response = $this->postJson('/api/inventory', $itemData);
        $response->assertStatus(201);

        $this->assertTrue(true, 'Requirement 1: Console errors fixed - PASSED');
    }

    /** @test */
    public function test_requirement_2_optional_pricing_fields()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        // Test creating item without prices
        $itemData = [
            'sku' => 'REQ2-TEST-001',
            'name' => 'Test Item Without Prices',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 10,
            // No unit_price or cost_price
        ];

        $response = $this->postJson('/api/inventory', $itemData);
        $response->assertStatus(201);

        $item = InventoryItem::where('sku', 'REQ2-TEST-001')->first();
        $this->assertNull($item->unit_price);
        $this->assertNull($item->cost_price);

        $this->assertTrue(true, 'Requirement 2: Optional pricing fields - PASSED');
    }

    /** @test */
    public function test_requirement_3_dynamic_gold_pricing_persian_formula()
    {
        $goldPricingService = app(GoldPricingService::class);

        // Test Persian formula: Weight × (Gold Price + Labor Cost + Profit + Tax) = Final Price
        $result = $goldPricingService->calculateItemPrice([
            'weight' => 5.0,
            'gold_price_per_gram' => 60.0,
            'labor_percentage' => 10,
            'profit_percentage' => 15,
            'tax_percentage' => 9,
            'quantity' => 1
        ]);

        // Manual calculation:
        // Base gold cost: 5.0 * 60.0 = 300.00
        // Labor cost: 300.00 * 0.10 = 30.00
        // Subtotal: 300.00 + 30.00 = 330.00
        // Profit: 330.00 * 0.15 = 49.50
        // Subtotal with profit: 330.00 + 49.50 = 379.50
        // Tax: 379.50 * 0.09 = 34.16 (rounded)
        // Final price: 379.50 + 34.16 = 413.66

        $this->assertEquals(300.00, $result['base_gold_cost']);
        $this->assertEquals(30.00, $result['labor_cost']);
        $this->assertEquals(49.50, $result['profit']);
        $this->assertEquals(34.16, $result['tax']);
        $this->assertEquals(413.66, $result['total_price']);

        $this->assertTrue(true, 'Requirement 3: Persian gold pricing formula - PASSED');
    }

    /** @test */
    public function test_requirement_4_invoice_inventory_relationship()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();
        $customer = Customer::factory()->create();

        // Create inventory item
        $item = InventoryItem::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 10,
            'weight' => 5.0,
            'gold_purity' => 18.0
        ]);

        $initialQuantity = $item->quantity;

        // Create invoice
        $invoiceData = [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'language' => 'en',
            'gold_pricing' => [
                'gold_price_per_gram' => 65.50,
                'labor_percentage' => 10,
                'profit_percentage' => 15,
                'tax_percentage' => 9
            ],
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 3,
                    'name' => $item->name,
                    'weight' => $item->weight,
                    'gold_purity' => $item->gold_purity
                    // No unit_price - will be calculated dynamically
                ]
            ]
        ];

        $response = $this->postJson('/api/invoices', $invoiceData);
        if ($response->status() !== 201) {
            dump('Invoice creation failed:', $response->json());
        }
        $response->assertStatus(201);

        // Verify inventory was reduced
        $item->refresh();
        $this->assertEquals($initialQuantity - 3, $item->quantity);

        $this->assertTrue(true, 'Requirement 4: Invoice-inventory relationship - PASSED');
    }

    /** @test */
    public function test_requirement_6_reports_with_real_data()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        // Create test data
        InventoryItem::factory()->count(5)->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'unit_price' => 100,
            'cost_price' => 80
        ]);

        $reportData = [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ];

        // Test all four report types
        $response = $this->postJson('/api/reports/sales', $reportData);
        $response->assertStatus(200);

        $response = $this->postJson('/api/reports/inventory', $reportData);
        $response->assertStatus(200);

        $response = $this->postJson('/api/reports/financial', $reportData);
        $response->assertStatus(200);

        $response = $this->postJson('/api/reports/customer', $reportData);
        $response->assertStatus(200);

        $this->assertTrue(true, 'Requirement 6: Reports with real data - PASSED');
    }

    /** @test */
    public function test_requirement_8_console_errors_resolved()
    {
        // Test that all critical endpoints are accessible
        $endpoints = [
            ['GET', '/api/categories'],
            ['GET', '/api/locations'],
            ['GET', '/api/inventory/gold-purity-options'],
            ['GET', '/api/inventory'],
            ['GET', '/api/invoices'],
            ['GET', '/api/customers']
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            
            // Should not return 404 or 500 errors
            $this->assertNotEquals(404, $response->status());
            $this->assertNotEquals(500, $response->status());
            
            // Should return valid response codes
            $this->assertTrue(
                in_array($response->status(), [200, 201, 401, 422]),
                "Endpoint {$method} {$endpoint} returned unexpected status: {$response->status()}"
            );
        }

        $this->assertTrue(true, 'Requirement 8: Console errors resolved - PASSED');
    }

    /** @test */
    public function test_error_handling_scenarios()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();
        $customer = Customer::factory()->create();

        // Test insufficient inventory error
        $item = InventoryItem::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 2
        ]);

        $invoiceData = [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'gold_pricing' => [
                'price_per_gram' => 65.50,
                'labor_percentage' => 10,
                'profit_percentage' => 15,
                'tax_percentage' => 9
            ],
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 5, // More than available
                    'name' => $item->name,
                    'unit_price' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/invoices', $invoiceData);
        $response->assertStatus(422); // Should return validation error

        $this->assertTrue(true, 'Error handling scenarios - PASSED');
    }

    /** @test */
    public function test_realistic_data_volume_performance()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        // Create realistic data volume
        InventoryItem::factory()->count(50)->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'unit_price' => rand(100, 1000),
            'cost_price' => rand(80, 800)
        ]);

        // Test that reports work with larger data sets
        $start = microtime(true);
        
        $response = $this->postJson('/api/reports/inventory', [
            'date_from' => now()->subDays(90)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);
        
        $end = microtime(true);
        $responseTime = $end - $start;

        $response->assertStatus(200);
        $this->assertLessThan(5, $responseTime, 'Report should respond within 5 seconds');

        $this->assertTrue(true, 'Realistic data volume performance - PASSED');
    }
}