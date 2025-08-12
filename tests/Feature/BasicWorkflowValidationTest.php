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
use Illuminate\Foundation\Testing\RefreshDatabase;

class BasicWorkflowValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $category;
    protected $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->customer = Customer::factory()->create();
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
        
        // Create business configuration
        BusinessConfiguration::setValue('default_labor_percentage', 10.0, 'float', 'pricing');
        BusinessConfiguration::setValue('default_profit_percentage', 15.0, 'float', 'pricing');
        BusinessConfiguration::setValue('default_tax_percentage', 9.0, 'float', 'pricing');
    }

    /** @test */
    public function test_inventory_item_creation_with_optional_prices()
    {
        $itemData = [
            'sku' => 'TEST-ITEM-001',
            'name' => 'Test Gold Ring',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'gold_purity' => 18.0,
            'weight' => 5.5,
            // No unit_price or cost_price - should be optional
        ];

        $response = $this->postJson('/api/inventory', $itemData);
        $response->assertStatus(201);
        
        $item = InventoryItem::where('sku', 'TEST-ITEM-001')->first();
        $this->assertNotNull($item);
        $this->assertNull($item->unit_price);
        $this->assertNull($item->cost_price);
        $this->assertEquals(10, $item->quantity);
    }

    /** @test */
    public function test_inventory_form_endpoints_work()
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
    }

    /** @test */
    public function test_invoice_creation_reduces_inventory()
    {
        // Create inventory item
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'weight' => 5.0,
            'gold_purity' => 18.0
        ]);

        $initialQuantity = $item->quantity;

        // Create invoice
        $invoiceData = [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'language' => 'en',
            'gold_pricing' => [
                'price_per_gram' => 65.50,
                'labor_percentage' => 12,
                'profit_percentage' => 18,
                'tax_percentage' => 9
            ],
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 3,
                    'name' => $item->name,
                    'unit_price' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/invoices', $invoiceData);
        $response->assertStatus(201);

        // Verify inventory was reduced
        $item->refresh();
        $this->assertEquals($initialQuantity - 3, $item->quantity);
    }

    /** @test */
    public function test_reports_return_data()
    {
        // Create some test data
        InventoryItem::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        // Test report endpoints
        $reportData = [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ];

        $response = $this->postJson('/api/reports/sales', $reportData);
        $response->assertStatus(200);

        $response = $this->postJson('/api/reports/inventory', $reportData);
        $response->assertStatus(200);

        $response = $this->postJson('/api/reports/financial', $reportData);
        $response->assertStatus(200);

        $response = $this->postJson('/api/reports/customer', $reportData);
        $response->assertStatus(200);
    }

    /** @test */
    public function test_insufficient_inventory_error_handling()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 2 // Only 2 in stock
        ]);

        $invoiceData = [
            'customer_id' => $this->customer->id,
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
                    'quantity' => 5, // Requesting more than available
                    'name' => $item->name,
                    'unit_price' => 100.00
                ]
            ]
        ];

        $response = $this->postJson('/api/invoices', $invoiceData);
        $response->assertStatus(422); // Should return validation error
    }

    /** @test */
    public function test_console_error_endpoints_accessibility()
    {
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
            
            // Should not return 404 (endpoint not found)
            $this->assertNotEquals(404, $response->status(), 
                "Endpoint {$method} {$endpoint} returned 404");
            
            // Should return valid response codes
            $this->assertTrue(
                in_array($response->status(), [200, 201, 401, 422]),
                "Endpoint {$method} {$endpoint} returned unexpected status: {$response->status()}"
            );
        }
    }
}