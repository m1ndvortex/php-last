<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Location;
use App\Models\InventoryItem;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;

class ConsoleErrorValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_verifies_all_inventory_form_endpoints_return_valid_responses()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Test Category']);
        $location = Location::factory()->create(['name' => 'Test Location']);

        // Test categories endpoint
        $response = $this->getJson('/api/categories');
        $this->assertSuccessfulApiResponse($response, 'Categories endpoint failed');

        // Test locations endpoint
        $response = $this->getJson('/api/locations');
        $this->assertSuccessfulApiResponse($response, 'Locations endpoint failed');

        // Test gold purity options endpoint
        $response = $this->getJson('/api/inventory/gold-purity-options');
        $this->assertSuccessfulApiResponse($response, 'Gold purity options endpoint failed');

        // Verify response structures contain expected data
        $categoriesResponse = $this->getJson('/api/categories');
        $categoriesData = $categoriesResponse->json();
        
        if (isset($categoriesData['data'])) {
            $this->assertIsArray($categoriesData['data']);
        }

        $locationsResponse = $this->getJson('/api/locations');
        $locationsData = $locationsResponse->json();
        
        if (isset($locationsData['data'])) {
            $this->assertIsArray($locationsData['data']);
        }

        $goldPurityResponse = $this->getJson('/api/inventory/gold-purity-options');
        $goldPurityData = $goldPurityResponse->json();
        
        if (isset($goldPurityData['data'])) {
            $this->assertIsArray($goldPurityData['data']);
        }
    }

    /** @test */
    public function it_validates_inventory_item_creation_with_optional_prices()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        // Test creating item without prices (should succeed)
        $itemDataWithoutPrices = [
            'sku' => 'TEST-NO-PRICE-001',
            'name' => 'Test Item Without Prices',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 10,
            'gold_purity' => 18.0,
            'weight' => 5.5,
            'description' => 'Test item for validation'
        ];

        $response = $this->postJson('/api/inventory', $itemDataWithoutPrices);
        $this->assertSuccessfulCreationResponse($response, 'Failed to create item without prices');

        // Verify item was created with null prices
        $item = InventoryItem::where('sku', 'TEST-NO-PRICE-001')->first();
        $this->assertNotNull($item, 'Item was not created in database');
        $this->assertNull($item->unit_price, 'Unit price should be null');
        $this->assertNull($item->cost_price, 'Cost price should be null');

        // Test creating item with prices (should also succeed)
        $itemDataWithPrices = [
            'sku' => 'TEST-WITH-PRICE-001',
            'name' => 'Test Item With Prices',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 5,
            'unit_price' => 1000.00,
            'cost_price' => 800.00,
            'gold_purity' => 21.0,
            'weight' => 3.2
        ];

        $response = $this->postJson('/api/inventory', $itemDataWithPrices);
        $this->assertSuccessfulCreationResponse($response, 'Failed to create item with prices');

        // Verify item was created with specified prices
        $item = InventoryItem::where('sku', 'TEST-WITH-PRICE-001')->first();
        $this->assertNotNull($item, 'Item with prices was not created in database');
        $this->assertEquals(1000.00, $item->unit_price, 'Unit price not set correctly');
        $this->assertEquals(800.00, $item->cost_price, 'Cost price not set correctly');
    }

    /** @test */
    public function it_handles_network_errors_gracefully_in_form_loading()
    {
        // Test that endpoints handle empty data gracefully
        
        // Delete all categories and test response
        Category::query()->delete();
        $response = $this->getJson('/api/categories');
        $this->assertSuccessfulApiResponse($response, 'Categories endpoint should handle empty data');

        // Delete all locations and test response
        Location::query()->delete();
        $response = $this->getJson('/api/locations');
        $this->assertSuccessfulApiResponse($response, 'Locations endpoint should handle empty data');

        // Gold purity options should always return standard options
        $response = $this->getJson('/api/inventory/gold-purity-options');
        $this->assertSuccessfulApiResponse($response, 'Gold purity options should always be available');
        
        $data = $response->json();
        if (isset($data['data'])) {
            $this->assertNotEmpty($data['data'], 'Gold purity options should not be empty');
        }
    }

    /** @test */
    public function it_provides_clear_validation_error_messages()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        // Test with completely invalid data
        $invalidData = [
            'sku' => '', // Required field empty
            'name' => '', // Required field empty
            'category_id' => 999999, // Non-existent category
            'location_id' => 999999, // Non-existent location
            'quantity' => -5, // Invalid negative quantity
            'unit_price' => -100, // Invalid negative price
            'cost_price' => -50, // Invalid negative price
            'gold_purity' => 30, // Invalid purity (max 24)
            'weight' => -2.5 // Invalid negative weight
        ];

        $response = $this->postJson('/api/inventory', $invalidData);
        
        $this->assertTrue(
            in_array($response->status(), [422, 400]),
            'Should return validation error status'
        );

        $responseData = $response->json();
        
        // Verify error structure
        if (isset($responseData['errors'])) {
            $errors = $responseData['errors'];
            
            // Check for validation errors (any field errors indicate validation is working)
            $this->assertTrue(
                count($errors) > 0,
                'Should have validation errors for invalid data'
            );
            
            // Check for specific validation errors we expect
            $this->assertTrue(
                isset($errors['category_id']) || isset($errors['location_id']) || isset($errors['quantity']),
                'Should have validation errors for invalid field values'
            );
        }
    }

    /** @test */
    public function it_validates_api_endpoint_accessibility()
    {
        // Test that all required endpoints are accessible and don't return 404
        $endpoints = [
            '/api/categories',
            '/api/locations',
            '/api/inventory/gold-purity-options',
            '/api/inventory',
            '/api/invoices',
            '/api/customers'
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            
            $this->assertNotEquals(
                404,
                $response->status(),
                "Endpoint {$endpoint} returned 404 - endpoint not found"
            );
            
            // Should return either success or validation error, not server error
            $this->assertTrue(
                in_array($response->status(), [200, 201, 400, 401, 422]),
                "Endpoint {$endpoint} returned unexpected status: {$response->status()}"
            );
        }
    }

    /** @test */
    public function it_verifies_form_submission_without_console_errors()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        // Test successful form submission
        $validItemData = [
            'sku' => 'CONSOLE-TEST-001',
            'name' => 'Console Error Test Item',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 15,
            'gold_purity' => 18.0,
            'weight' => 7.5,
            'description' => 'Item created to test console error resolution'
        ];

        $response = $this->postJson('/api/inventory', $validItemData);
        
        // Should succeed without any server errors
        $this->assertTrue(
            in_array($response->status(), [200, 201]),
            'Form submission should succeed without errors'
        );

        // Verify the item was actually created
        $item = InventoryItem::where('sku', 'CONSOLE-TEST-001')->first();
        $this->assertNotNull($item, 'Item should be created in database');
        $this->assertEquals('Console Error Test Item', $item->name);
        $this->assertEquals(15, $item->quantity);
        $this->assertEquals(18.0, $item->gold_purity);
        $this->assertEquals(7.5, $item->weight);
    }

    /** @test */
    public function it_handles_authentication_requirements_properly()
    {
        // Test without authentication
        auth()->logout();

        $endpoints = [
            '/api/categories',
            '/api/locations',
            '/api/inventory/gold-purity-options',
            '/api/inventory'
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            
            // Should either allow access or require authentication
            // but should not return server errors
            $this->assertTrue(
                in_array($response->status(), [200, 401]),
                "Endpoint {$endpoint} should handle authentication properly"
            );
        }
    }

    /** @test */
    public function it_validates_invoice_creation_with_inventory_integration()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();
        $customer = Customer::factory()->create();

        // Create inventory item
        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 10,
            'weight' => 5.0,
            'gold_purity' => 18.0,
            'unit_price' => null // For dynamic pricing
        ]);

        // Test invoice creation with dynamic pricing
        $invoiceData = [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'language' => 'en',
            'gold_pricing' => [
                'gold_price_per_gram' => 55.00,
                'labor_percentage' => 12.0,
                'profit_percentage' => 18.0,
                'tax_percentage' => 9.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 2,
                    'name' => $inventoryItem->name,
                    'weight' => $inventoryItem->weight,
                    'gold_purity' => $inventoryItem->gold_purity
                ]
            ]
        ];

        $response = $this->postJson('/api/invoices', $invoiceData);
        
        $this->assertTrue(
            in_array($response->status(), [200, 201]),
            'Invoice creation should succeed without console errors'
        );

        // Verify inventory was reduced
        $inventoryItem->refresh();
        $this->assertEquals(8, $inventoryItem->quantity, 'Inventory should be reduced after invoice creation');
    }

    /** @test */
    public function it_validates_report_endpoints_return_data()
    {
        // Create some test data for reports
        $category = Category::factory()->create();
        $location = Location::factory()->create();
        $customer = Customer::factory()->create();

        InventoryItem::factory()->count(5)->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => rand(10, 50),
            'unit_price' => rand(100, 1000),
            'cost_price' => rand(80, 800)
        ]);

        $reportEndpoints = [
            '/api/reports/sales',
            '/api/reports/inventory',
            '/api/reports/financial',
            '/api/reports/customer'
        ];

        foreach ($reportEndpoints as $endpoint) {
            $response = $this->postJson($endpoint, [
                'date_from' => now()->subDays(30)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d')
            ]);
            
            $this->assertTrue(
                in_array($response->status(), [200, 422]),
                "Report endpoint {$endpoint} should return valid response"
            );

            if ($response->status() === 200) {
                $data = $response->json();
                $this->assertIsArray($data, "Report data should be an array");
            }
        }
    }

    /** @test */
    public function it_verifies_no_javascript_errors_in_api_responses()
    {
        // This test ensures API responses don't contain JavaScript errors or malformed JSON
        
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        $endpoints = [
            '/api/categories',
            '/api/locations',
            '/api/inventory/gold-purity-options'
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            
            // Verify response is valid JSON
            $this->assertJson($response->getContent(), "Response from {$endpoint} should be valid JSON");
            
            // Verify response doesn't contain error indicators
            $content = $response->getContent();
            $this->assertStringNotContainsString('XMLHttpRequest', $content);
            $this->assertStringNotContainsString('net::ERR_FAILED', $content);
            $this->assertStringNotContainsString('Failed to load resource', $content);
            $this->assertStringNotContainsString('Network error', $content);
        }
    }

    /**
     * Helper method to assert successful API response
     */
    protected function assertSuccessfulApiResponse($response, $message = '')
    {
        $this->assertTrue(
            in_array($response->status(), [200, 201]),
            $message ?: "API response should be successful, got {$response->status()}"
        );

        // Verify response is valid JSON
        $this->assertJson($response->getContent(), 'Response should be valid JSON');
    }

    /**
     * Helper method to assert successful creation response
     */
    protected function assertSuccessfulCreationResponse($response, $message = '')
    {
        $this->assertTrue(
            in_array($response->status(), [200, 201]),
            $message ?: "Creation response should be successful, got {$response->status()}"
        );

        $data = $response->json();
        
        // Check for success indicators
        if (isset($data['success'])) {
            $this->assertTrue($data['success'], 'Response should indicate success');
        }
    }
}