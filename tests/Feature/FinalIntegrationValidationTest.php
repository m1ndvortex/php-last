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
use App\Services\InventoryManagementService;
use App\Services\InvoiceService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class FinalIntegrationValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $customer;
    protected $category;
    protected $location;
    protected $goldPricingService;
    protected $inventoryService;
    protected $invoiceService;
    protected $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        // Create test data
        $this->customer = Customer::factory()->create();
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
        
        // Initialize services
        $this->goldPricingService = app(GoldPricingService::class);
        $this->inventoryService = app(InventoryManagementService::class);
        $this->invoiceService = app(InvoiceService::class);
        $this->reportService = app(ReportService::class);
        
        // Create business configuration with default percentages
        BusinessConfiguration::setValue('default_labor_percentage', 10.0, 'float', 'pricing');
        BusinessConfiguration::setValue('default_profit_percentage', 15.0, 'float', 'pricing');
        BusinessConfiguration::setValue('default_tax_percentage', 9.0, 'float', 'pricing');
        BusinessConfiguration::setValue('business_name', 'Test Jewelry Store', 'string', 'business');
        BusinessConfiguration::setValue('business_phone', '+1234567890', 'string', 'business');
        BusinessConfiguration::setValue('business_email', 'test@jewelry.com', 'string', 'business');
    }

    /** @test */
    public function test_complete_workflow_create_item_to_invoice_to_inventory_reduction()
    {
        // Step 1: Create inventory item with optional pricing
        $itemData = [
            'sku' => 'GOLD-RING-001',
            'name' => 'Gold Ring 18K',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'gold_purity' => 18.0,
            'weight' => 5.5,
            'unit_price' => null, // Optional pricing
            'cost_price' => null, // Optional pricing
            'is_active' => true
        ];

        $response = $this->postJson('/api/inventory', $itemData);
        $response->assertStatus(201);
        
        $item = InventoryItem::where('sku', 'GOLD-RING-001')->first();
        $this->assertNotNull($item);
        $this->assertNull($item->unit_price);
        $this->assertNull($item->cost_price);
        $this->assertEquals(10, $item->quantity);

        // Step 2: Create invoice with dynamic gold pricing
        $invoiceData = [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'language' => 'en',
            'gold_pricing' => [
                'gold_price_per_gram' => 65.50, // Current gold price
                'labor_percentage' => 12,
                'profit_percentage' => 18,
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
        
        $invoice = Invoice::latest()->first();
        $this->assertNotNull($invoice);

        // Step 3: Verify inventory reduction
        $item->refresh();
        $this->assertEquals(7, $item->quantity); // 10 - 3 = 7

        // Step 4: Verify Persian gold pricing formula
        $expectedPrice = $this->calculateExpectedPrice(5.5, 65.50, 12, 18, 9, 3);
        $invoiceItem = $invoice->items->first();
        $this->assertEquals($expectedPrice['total_price'], $invoiceItem->total_price);

        // Step 5: Test invoice cancellation restores inventory
        $response = $this->deleteJson("/api/invoices/{$invoice->id}");
        $response->assertStatus(200);
        
        $item->refresh();
        $this->assertEquals(10, $item->quantity); // Restored to original
    }

    /** @test */
    public function test_all_four_report_types_work_with_actual_data()
    {
        // Create test data for reports
        $items = InventoryItem::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 20,
            'unit_price' => 100,
            'cost_price' => 80
        ]);

        $invoices = Invoice::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'status' => 'paid'
        ]);

        // Test Sales Report
        $response = $this->postJson('/api/reports/sales', [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);
        $response->assertStatus(200);

        // Test Inventory Report
        $response = $this->postJson('/api/reports/inventory', [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);
        $response->assertStatus(200);

        // Test Financial Report
        $response = $this->postJson('/api/reports/financial', [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);
        $response->assertStatus(200);

        // Test Customer Report
        $response = $this->postJson('/api/reports/customer', [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function test_persian_gold_pricing_formula_accuracy()
    {
        $testCases = [
            [
                'weight' => 5.0,
                'gold_price_per_gram' => 60.0,
                'labor_percentage' => 10,
                'profit_percentage' => 15,
                'tax_percentage' => 9,
                'quantity' => 1
            ],
            [
                'weight' => 10.5,
                'gold_price_per_gram' => 65.50,
                'labor_percentage' => 12,
                'profit_percentage' => 18,
                'tax_percentage' => 9,
                'quantity' => 2
            ]
        ];

        foreach ($testCases as $case) {
            $result = $this->goldPricingService->calculateItemPrice($case);
            $expected = $this->calculateExpectedPrice(
                $case['weight'],
                $case['gold_price_per_gram'],
                $case['labor_percentage'],
                $case['profit_percentage'],
                $case['tax_percentage'],
                $case['quantity']
            );

            $this->assertEquals($expected['base_gold_cost'], $result['base_gold_cost']);
            $this->assertEquals($expected['labor_cost'], $result['labor_cost']);
            $this->assertEquals($expected['profit'], $result['profit']);
            $this->assertEquals($expected['tax'], $result['tax']);
            $this->assertEquals($expected['total_price'], $result['total_price']);
        }
    }

    /** @test */
    public function test_inventory_form_api_endpoints_work_without_errors()
    {
        // Test categories endpoint
        $response = $this->getJson('/api/categories');
        $response->assertStatus(200);

        // Test locations endpoint
        $response = $this->getJson('/api/locations');
        $response->assertStatus(200);

        // Test gold purity options endpoint
        $response = $this->getJson('/api/inventory/gold-purity-options');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'standard_purities' => [
                    '*' => ['karat', 'purity', 'percentage', 'display', 'display_name', 'label']
                ],
                'purity_ranges'
            ]
        ]);
    }

    /** @test */
    public function test_realistic_data_volume_scenarios()
    {
        // Create realistic data volumes
        $categories = Category::factory()->count(10)->create();
        $locations = Location::factory()->count(5)->create();
        $customers = Customer::factory()->count(50)->create();
        
        // Create 100 inventory items
        $items = collect();
        for ($i = 0; $i < 100; $i++) {
            $items->push(InventoryItem::factory()->create([
                'category_id' => $categories->random()->id,
                'location_id' => $locations->random()->id,
                'quantity' => rand(1, 50),
                'weight' => rand(10, 200) / 10, // 1.0 to 20.0 grams
                'gold_purity' => collect([18.0, 21.0, 22.0, 24.0])->random()
            ]));
        }

        // Create 50 invoices with multiple items each
        for ($i = 0; $i < 50; $i++) {
            $invoiceItems = $items->random(rand(1, 5))->map(function ($item) {
                return [
                    'inventory_item_id' => $item->id,
                    'quantity' => rand(1, 3)
                ];
            })->toArray();

            $invoiceData = [
                'customer_id' => $customers->random()->id,
                'issue_date' => now()->subDays(rand(0, 90))->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
                'language' => collect(['en', 'fa'])->random(),
                'gold_pricing' => [
                    'price_per_gram' => rand(6000, 7000) / 100, // 60.00 to 70.00
                    'labor_percentage' => rand(8, 15),
                    'profit_percentage' => rand(10, 20),
                    'tax_percentage' => 9
                ],
                'items' => $invoiceItems
            ];

            try {
                $response = $this->postJson('/api/invoices', $invoiceData);
                // Some may fail due to insufficient inventory, which is expected
            } catch (\Exception $e) {
                // Continue with other invoices
            }
        }

        // Test that reports still work with large data volumes
        $response = $this->postJson('/api/reports/sales', [
            'date_from' => now()->subDays(90)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);
        $response->assertStatus(200);

        $response = $this->postJson('/api/reports/inventory', [
            'date_from' => now()->subDays(90)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);
        $response->assertStatus(200);

        // Verify performance is acceptable (response time < 5 seconds)
        $start = microtime(true);
        $this->postJson('/api/reports/financial', [
            'date_from' => now()->subDays(90)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);
        $end = microtime(true);
        $this->assertLessThan(5, $end - $start);
    }

    /** @test */
    public function test_error_handling_scenarios()
    {
        $item = InventoryItem::factory()->create([
            'quantity' => 2,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        // Test insufficient inventory error
        $invoiceData = [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'gold_pricing' => [
                'gold_price_per_gram' => 65.50,
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
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'errors'
        ]);

        // Test invalid item creation
        $invalidItemData = [
            'sku' => '', // Required field empty
            'name' => 'Test Item',
            'category_id' => 999, // Non-existent category
            'location_id' => $this->location->id,
            'quantity' => -5 // Invalid quantity
        ];

        $response = $this->postJson('/api/inventory', $invalidItemData);
        $response->assertStatus(422);
    }

    private function calculateExpectedPrice($weight, $goldPrice, $laborPercentage, $profitPercentage, $taxPercentage, $quantity)
    {
        // Persian jewelry pricing formula
        $baseGoldCost = $weight * $goldPrice;
        $laborCost = $baseGoldCost * ($laborPercentage / 100);
        $subtotal = $baseGoldCost + $laborCost;
        $profit = $subtotal * ($profitPercentage / 100);
        $subtotalWithProfit = $subtotal + $profit;
        $tax = $subtotalWithProfit * ($taxPercentage / 100);
        $unitPrice = $subtotalWithProfit + $tax;
        $totalPrice = $unitPrice * $quantity;

        return [
            'base_gold_cost' => round($baseGoldCost * $quantity, 2),
            'labor_cost' => round($laborCost * $quantity, 2),
            'profit' => round($profit * $quantity, 2),
            'tax' => round($tax * $quantity, 2),
            'unit_price' => round($unitPrice, 2),
            'total_price' => round($totalPrice, 2)
        ];
    }
}