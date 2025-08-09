<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryOptionalPricesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        
        // Create test category and location
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
    }

    /** @test */
    public function it_can_create_inventory_item_with_null_prices()
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Test Jewelry Item',
            'sku' => 'TEST-001',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'unit_price' => null,  // Testing null price
            'cost_price' => null,  // Testing null price
            'gold_purity' => 18.0,
            'weight' => 5.5,
            'is_active' => true,
            'track_serial' => false,
            'track_batch' => false,
        ];

        $response = $this->postJson('/api/inventory', $data);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Inventory item created successfully'
                ]);

        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Test Jewelry Item',
            'sku' => 'TEST-001',
            'unit_price' => null,
            'cost_price' => null,
        ]);
    }

    /** @test */
    public function it_can_create_inventory_item_with_prices()
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Test Jewelry Item with Prices',
            'sku' => 'TEST-002',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 5,
            'unit_price' => 100.50,
            'cost_price' => 80.25,
            'gold_purity' => 21.0,
            'weight' => 3.2,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/inventory', $data);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Inventory item created successfully'
                ]);

        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Test Jewelry Item with Prices',
            'sku' => 'TEST-002',
            'unit_price' => 100.50,
            'cost_price' => 80.25,
        ]);
    }

    /** @test */
    public function it_validates_negative_prices()
    {
        $this->actingAs($this->user);

        $data = [
            'name' => 'Test Item',
            'sku' => 'TEST-003',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 1,
            'unit_price' => -10.00,  // Invalid negative price
            'cost_price' => -5.00,   // Invalid negative price
        ];

        $response = $this->postJson('/api/inventory', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['unit_price', 'cost_price']);
    }
}