<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\InventoryItem;
use App\Models\Customer;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\PricingException;
use App\Services\GoldPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ErrorHandlingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->inventoryItem = InventoryItem::factory()->create([
            'quantity' => 5,
            'weight' => 10.5,
            'gold_purity' => 18.0
        ]);
    }

    /** @test */
    public function it_handles_pricing_exception_properly()
    {
        $goldPricingService = new GoldPricingService();

        $this->expectException(PricingException::class);

        // Try to calculate price with invalid parameters
        $goldPricingService->calculateItemPrice([
            'weight' => 0, // Invalid weight
            'gold_price_per_gram' => 50,
            'labor_percentage' => 10,
            'profit_percentage' => 15,
            'tax_percentage' => 9,
            'quantity' => 1
        ]);
    }

    /** @test */
    public function it_handles_validation_errors_properly()
    {
        $this->actingAs($this->user);

        // Try to create an invoice without required fields
        $response = $this->postJson('/api/invoices', [
            // Missing required fields
        ]);

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
                    'details' => [
                        'type',
                        'code',
                        'timestamp'
                    ]
                ]);
    }

    /** @test */
    public function it_handles_not_found_errors_properly()
    {
        $this->actingAs($this->user);

        // Try to access non-existent invoice
        $response = $this->getJson('/api/invoices/99999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'error' => 'resource_not_found'
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
    }

    /** @test */
    public function it_handles_authentication_errors_properly()
    {
        // Try to access protected endpoint without authentication
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
    }

    /** @test */
    public function it_provides_consistent_error_response_structure()
    {
        $this->actingAs($this->user);

        // Test validation error
        $response = $this->postJson('/api/invoices', []);
        
        $response->assertStatus(422)
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

        $responseData = $response->json();
        $this->assertFalse($responseData['success']);
        $this->assertIsString($responseData['error']);
        $this->assertIsString($responseData['message']);
        $this->assertIsArray($responseData['details']);
        $this->assertEquals(422, $responseData['details']['code']);
    }

    /** @test */
    public function it_handles_pricing_validation_errors()
    {
        $goldPricingService = new GoldPricingService();

        // Test with negative weight
        try {
            $goldPricingService->calculateItemPrice([
                'weight' => -1,
                'gold_price_per_gram' => 50,
                'labor_percentage' => 10,
                'profit_percentage' => 15,
                'tax_percentage' => 9,
                'quantity' => 1
            ]);
            $this->fail('Expected PricingException was not thrown');
        } catch (PricingException $e) {
            $this->assertStringContainsString('parameters', strtolower($e->getMessage()));
        }

        // Test with negative gold price
        try {
            $goldPricingService->calculateItemPrice([
                'weight' => 10,
                'gold_price_per_gram' => -50,
                'labor_percentage' => 10,
                'profit_percentage' => 15,
                'tax_percentage' => 9,
                'quantity' => 1
            ]);
            $this->fail('Expected PricingException was not thrown');
        } catch (PricingException $e) {
            $this->assertStringContainsString('parameters', strtolower($e->getMessage()));
        }
    }

    /** @test */
    public function it_validates_pricing_parameters_correctly()
    {
        $goldPricingService = new GoldPricingService();

        // Test valid parameters
        $validParams = [
            'weight' => 10.5,
            'gold_price_per_gram' => 50,
            'labor_percentage' => 10,
            'profit_percentage' => 15,
            'tax_percentage' => 9,
            'quantity' => 1
        ];

        $errors = $goldPricingService->validatePricingParams($validParams);
        $this->assertEmpty($errors);

        // Test invalid parameters
        $invalidParams = [
            'weight' => 0,
            'gold_price_per_gram' => -50,
            'labor_percentage' => -10,
            'quantity' => 0
        ];

        $errors = $goldPricingService->validatePricingParams($invalidParams);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('weight', $errors);
        $this->assertArrayHasKey('gold_price_per_gram', $errors);
        $this->assertArrayHasKey('labor_percentage', $errors);
    }

    /** @test */
    public function it_handles_insufficient_inventory_exception_structure()
    {
        $unavailableItems = [
            [
                'item_id' => 1,
                'item_name' => 'Test Item',
                'item_sku' => 'TEST-001',
                'requested_quantity' => 10,
                'available_quantity' => 5,
                'error' => 'Insufficient inventory'
            ]
        ];

        $exception = new InsufficientInventoryException(
            'Insufficient inventory for requested items',
            $unavailableItems
        );

        $response = $exception->render();

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('insufficient_inventory', $data['error']);
        $this->assertEquals($unavailableItems, $data['unavailable_items']);
        $this->assertArrayHasKey('details', $data);
        $this->assertEquals('inventory_error', $data['details']['type']);
    }

    /** @test */
    public function it_handles_pricing_exception_structure()
    {
        $pricingData = [
            'weight' => -1,
            'gold_price_per_gram' => 50
        ];

        $exception = new PricingException(
            'Invalid pricing parameters',
            $pricingData
        );

        $response = $exception->render();

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('pricing_error', $data['error']);
        $this->assertEquals($pricingData, $data['pricing_data']);
        $this->assertArrayHasKey('details', $data);
        $this->assertEquals('pricing_error', $data['details']['type']);
    }

    /** @test */
    public function it_provides_error_messages_in_correct_language()
    {
        // Test English error messages
        app()->setLocale('en');
        
        $goldPricingService = new GoldPricingService();
        
        try {
            $goldPricingService->calculateItemPrice([
                'weight' => 0,
                'gold_price_per_gram' => 50,
                'quantity' => 1
            ]);
        } catch (PricingException $e) {
            $this->assertIsString($e->getMessage());
        }

        // Test Persian error messages
        app()->setLocale('fa');
        
        try {
            $goldPricingService->calculateItemPrice([
                'weight' => 0,
                'gold_price_per_gram' => 50,
                'quantity' => 1
            ]);
        } catch (PricingException $e) {
            $this->assertIsString($e->getMessage());
        }
    }

    /** @test */
    public function it_handles_api_endpoint_not_found()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/non-existent-endpoint');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'error' => 'endpoint_not_found'
                ])
                ->assertJsonStructure([
                    'success',
                    'error',
                    'message',
                    'details'
                ]);
    }

    /** @test */
    public function error_responses_include_timestamp()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/invoices/99999');

        $data = $response->json();
        $this->assertArrayHasKey('details', $data);
        $this->assertArrayHasKey('timestamp', $data['details']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $data['details']['timestamp']);
    }
}