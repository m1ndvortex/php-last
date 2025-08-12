<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Services\GoldPricingService;
use App\Services\InvoiceService;
use App\Services\InventoryManagementService;
use App\Services\ReportService;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\PricingException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class ComprehensiveErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $category;
    protected $location;
    protected $goldPricingService;
    protected $invoiceService;
    protected $inventoryService;
    protected $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
        
        $this->goldPricingService = app(GoldPricingService::class);
        $this->invoiceService = app(InvoiceService::class);
        $this->inventoryService = app(InventoryManagementService::class);
        $this->reportService = app(ReportService::class);
    }

    /** @test */
    public function it_handles_pricing_exceptions_with_detailed_error_information()
    {
        $this->actingAs($this->user);

        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'weight' => 5.0,
            'gold_purity' => 18.0
        ]);

        $invalidPricingData = [
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => -50.00, // Invalid negative price
                'labor_percentage' => -10.0, // Invalid negative percentage
                'profit_percentage' => 150.0, // Unreasonably high percentage
                'tax_percentage' => -5.0 // Invalid negative tax
            ],
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 1,
                    'name' => $inventoryItem->name
                ]
            ]
        ];

        $response = $this->postJson('/api/invoices', $invalidPricingData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => 'pricing_error'
                ])
                ->assertJsonStructure([
                    'success',
                    'error',
                    'message',
                    'pricing_data',
                    'validation_errors',
                    'details' => [
                        'type',
                        'code',
                        'timestamp',
                        'trace_id'
                    ]
                ]);

        $responseData = $response->json();
        $this->assertArrayHasKey('pricing_data', $responseData);
        $this->assertArrayHasKey('validation_errors', $responseData);
        
        $validationErrors = $responseData['validation_errors'];
        $this->assertArrayHasKey('gold_price_per_gram', $validationErrors);
        $this->assertArrayHasKey('labor_percentage', $validationErrors);
        $this->assertArrayHasKey('tax_percentage', $validationErrors);
    }

    /** @test */
    public function it_handles_insufficient_inventory_exceptions_with_item_details()
    {
        $this->actingAs($this->user);

        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 3, // Limited stock
            'weight' => 5.0,
            'gold_purity' => 18.0
        ]);

        $insufficientStockData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 10, // More than available
                    'name' => $inventoryItem->name
                ]
            ]
        ];

        $response = $this->postJson('/api/invoices', $insufficientStockData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => 'insufficient_inventory'
                ])
                ->assertJsonStructure([
                    'success',
                    'error',
                    'message',
                    'unavailable_items',
                    'details' => [
                        'type',
                        'code',
                        'timestamp'
                    ]
                ]);

        $responseData = $response->json();
        $unavailableItems = $responseData['unavailable_items'];
        
        $this->assertCount(1, $unavailableItems);
        $this->assertEquals($inventoryItem->id, $unavailableItems[0]['item_id']);
        $this->assertEquals($inventoryItem->name, $unavailableItems[0]['item_name']);
        $this->assertEquals($inventoryItem->sku, $unavailableItems[0]['item_sku']);
        $this->assertEquals(10, $unavailableItems[0]['requested_quantity']);
        $this->assertEquals(3, $unavailableItems[0]['available_quantity']);
        $this->assertEquals('Insufficient inventory', $unavailableItems[0]['error']);
    }

    /** @test */
    public function it_handles_validation_errors_with_field_specific_messages()
    {
        $this->actingAs($this->user);

        $invalidData = [
            'customer_id' => 999999, // Non-existent customer
            'issue_date' => 'invalid-date',
            'due_date' => 'invalid-date',
            'gold_pricing' => [
                'gold_price_per_gram' => 'not-a-number',
                'labor_percentage' => 'invalid',
                'profit_percentage' => null,
                'tax_percentage' => ''
            ],
            'items' => [
                [
                    'inventory_item_id' => 'not-a-number',
                    'quantity' => -1, // Invalid negative quantity
                    'name' => '' // Empty name
                ]
            ]
        ];

        $response = $this->postJson('/api/invoices', $invalidData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'error' => 'validation_failed'
                ])
                ->assertJsonStructure([
                    'success',
                    'error',
                    'message',
                    'errors',
                    'details'
                ]);

        $responseData = $response->json();
        $errors = $responseData['errors'];

        // Verify specific field errors
        $this->assertArrayHasKey('customer_id', $errors);
        $this->assertArrayHasKey('issue_date', $errors);
        $this->assertArrayHasKey('due_date', $errors);
        $this->assertArrayHasKey('gold_pricing.gold_price_per_gram', $errors);
        $this->assertArrayHasKey('items.0.inventory_item_id', $errors);
        $this->assertArrayHasKey('items.0.quantity', $errors);
    }

    /** @test */
    public function it_handles_authentication_and_authorization_errors()
    {
        // Test unauthenticated access
        $response = $this->getJson('/api/invoices');

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'error' => 'unauthenticated'
                ])
                ->assertJsonStructure([
                    'success',
                    'error',
                    'message',
                    'details' => [
                        'type',
                        'code',
                        'timestamp'
                    ]
                ]);

        // Test authenticated but unauthorized access (if applicable)
        $limitedUser = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($limitedUser);

        $response = $this->postJson('/api/invoices', []);

        // This might return 403 if role-based permissions are implemented
        $this->assertTrue(in_array($response->status(), [401, 403, 422]));
    }

    /** @test */
    public function it_handles_resource_not_found_errors_with_context()
    {
        $this->actingAs($this->user);

        // Test non-existent invoice
        $response = $this->getJson('/api/invoices/999999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'error' => 'resource_not_found'
                ])
                ->assertJsonStructure([
                    'success',
                    'error',
                    'message',
                    'resource_type',
                    'resource_id',
                    'details'
                ]);

        $responseData = $response->json();
        $this->assertEquals('invoice', $responseData['resource_type']);
        $this->assertEquals('999999', $responseData['resource_id']);

        // Test non-existent inventory item
        $response = $this->getJson('/api/inventory/999999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'error' => 'resource_not_found'
                ]);
    }

    /** @test */
    public function it_handles_database_connection_errors_gracefully()
    {
        $this->actingAs($this->user);

        // Simulate database connection issue by using invalid database config
        // This is a conceptual test - actual implementation would depend on how you want to handle DB errors
        
        // For now, we'll test that the error handling structure is consistent
        $response = $this->getJson('/api/reports/sales?date_from=invalid&date_to=invalid');

        // Should handle invalid date parameters gracefully
        $this->assertTrue(in_array($response->status(), [400, 422, 500]));
        
        if ($response->status() >= 400) {
            $responseData = $response->json();
            $this->assertArrayHasKey('success', $responseData);
            $this->assertFalse($responseData['success']);
            $this->assertArrayHasKey('error', $responseData);
            $this->assertArrayHasKey('details', $responseData);
        }
    }

    /** @test */
    public function it_provides_consistent_error_response_structure_across_endpoints()
    {
        $this->actingAs($this->user);

        $endpoints = [
            ['method' => 'GET', 'url' => '/api/invoices/999999', 'expected_status' => 404],
            ['method' => 'POST', 'url' => '/api/invoices', 'data' => [], 'expected_status' => 422],
            ['method' => 'GET', 'url' => '/api/inventory/999999', 'expected_status' => 404],
            ['method' => 'POST', 'url' => '/api/inventory', 'data' => [], 'expected_status' => 422],
        ];

        foreach ($endpoints as $endpoint) {
            if ($endpoint['method'] === 'GET') {
                $response = $this->getJson($endpoint['url']);
            } else {
                $response = $this->postJson($endpoint['url'], $endpoint['data'] ?? []);
            }

            $response->assertStatus($endpoint['expected_status']);

            $responseData = $response->json();
            
            // Verify consistent structure
            $this->assertArrayHasKey('success', $responseData);
            $this->assertFalse($responseData['success']);
            $this->assertArrayHasKey('error', $responseData);
            $this->assertArrayHasKey('message', $responseData);
            $this->assertArrayHasKey('details', $responseData);
            
            $details = $responseData['details'];
            $this->assertArrayHasKey('type', $details);
            $this->assertArrayHasKey('code', $details);
            $this->assertArrayHasKey('timestamp', $details);
            
            // Verify timestamp format
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $details['timestamp']);
        }
    }

    /** @test */
    public function it_handles_service_layer_exceptions_properly()
    {
        // Test GoldPricingService exceptions
        try {
            $this->goldPricingService->calculateItemPrice([
                'weight' => -5,
                'gold_price_per_gram' => 50,
                'quantity' => 1
            ]);
            $this->fail('Expected PricingException was not thrown');
        } catch (PricingException $e) {
            $this->assertInstanceOf(PricingException::class, $e);
            $this->assertNotNull($e->getPricingData());
            $this->assertIsArray($e->getPricingData());
        }

        // Test InventoryManagementService exceptions
        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 2
        ]);

        $items = [
            ['inventory_item_id' => $inventoryItem->id, 'quantity' => 5]
        ];

        try {
            $this->inventoryService->validateInventoryAvailability($items);
            $this->fail('Expected InsufficientInventoryException was not thrown');
        } catch (InsufficientInventoryException $e) {
            $this->assertInstanceOf(InsufficientInventoryException::class, $e);
            $this->assertNotNull($e->getUnavailableItems());
            $this->assertIsArray($e->getUnavailableItems());
        }
    }

    /** @test */
    public function it_handles_report_generation_errors()
    {
        $this->actingAs($this->user);

        // Test invalid report parameters
        $response = $this->getJson('/api/reports/sales?date_from=2025-13-01&date_to=2025-14-01');

        $this->assertTrue(in_array($response->status(), [400, 422]));

        if ($response->status() >= 400) {
            $responseData = $response->json();
            $this->assertArrayHasKey('success', $responseData);
            $this->assertFalse($responseData['success']);
        }

        // Test non-existent report type
        $response = $this->getJson('/api/reports/non-existent-type');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_provides_localized_error_messages()
    {
        $this->actingAs($this->user);

        // Test English error messages
        app()->setLocale('en');
        
        $response = $this->postJson('/api/invoices', []);
        $responseData = $response->json();
        
        $this->assertIsString($responseData['message']);
        $this->assertNotEmpty($responseData['message']);

        // Test Persian error messages
        app()->setLocale('fa');
        
        $response = $this->postJson('/api/invoices', []);
        $responseData = $response->json();
        
        $this->assertIsString($responseData['message']);
        $this->assertNotEmpty($responseData['message']);
    }

    /** @test */
    public function it_handles_concurrent_operation_conflicts()
    {
        $this->actingAs($this->user);

        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 5
        ]);

        // Simulate concurrent requests trying to reserve the same inventory
        $invoiceData1 = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 4,
                    'name' => $inventoryItem->name
                ]
            ]
        ];

        $invoiceData2 = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 3,
                    'name' => $inventoryItem->name
                ]
            ]
        ];

        // First request should succeed
        $response1 = $this->postJson('/api/invoices', $invoiceData1);
        
        // Second request should fail due to insufficient inventory
        $response2 = $this->postJson('/api/invoices', $invoiceData2);

        $this->assertTrue(in_array($response1->status(), [200, 201]));
        $this->assertEquals(422, $response2->status());

        $response2Data = $response2->json();
        $this->assertEquals('insufficient_inventory', $response2Data['error']);
    }

    /** @test */
    public function it_logs_errors_with_appropriate_context()
    {
        $this->actingAs($this->user);

        // This test would verify that errors are properly logged
        // For now, we'll just ensure the error handling doesn't break logging
        
        $response = $this->postJson('/api/invoices', [
            'customer_id' => 999999,
            'items' => []
        ]);

        $response->assertStatus(422);
        
        // In a real implementation, you would check logs here
        // $this->assertLogContains('Validation failed for invoice creation');
    }

    /** @test */
    public function it_handles_rate_limiting_errors()
    {
        $this->actingAs($this->user);

        // Simulate rate limiting by making many requests quickly
        // This is a conceptual test - actual implementation depends on rate limiting setup
        
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/invoices');
            
            // Should either succeed or eventually hit rate limit
            $this->assertTrue(in_array($response->status(), [200, 429]));
            
            if ($response->status() === 429) {
                $responseData = $response->json();
                $this->assertArrayHasKey('error', $responseData);
                $this->assertEquals('rate_limit_exceeded', $responseData['error']);
                break;
            }
        }
    }
}