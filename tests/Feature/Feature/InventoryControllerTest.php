<?php

namespace Tests\Feature\Feature;

use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
        
        $this->actingAs($this->user);
    }

    public function test_can_list_inventory_items()
    {
        InventoryItem::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/inventory');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'sku',
                        'quantity',
                        'unit_price',
                        'category',
                        'location',
                    ]
                ]
            ]);
    }

    public function test_can_create_inventory_item()
    {
        $itemData = [
            'name' => 'Gold Ring',
            'name_persian' => 'حلقه طلا',
            'description' => 'Beautiful gold ring',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'unit_price' => 100.00,
            'cost_price' => 80.00,
            'gold_purity' => 18.000,
            'weight' => 5.5,
            'minimum_stock' => 5,
        ];

        $response = $this->postJson('/api/inventory', $itemData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'sku',
                    'quantity',
                    'category',
                    'location',
                ]
            ]);

        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Gold Ring',
            'quantity' => 10,
            'unit_price' => 100.00,
        ]);
    }

    public function test_can_show_inventory_item()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $response = $this->getJson("/api/inventory/{$item->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'sku',
                    'category',
                    'location',
                    'movements',
                ]
            ]);
    }

    public function test_can_update_inventory_item()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'name' => 'Old Name',
            'quantity' => 10,
        ]);

        $updateData = [
            'name' => 'New Name',
            'quantity' => 15,
        ];

        $response = $this->putJson("/api/inventory/{$item->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Inventory item updated successfully',
            ]);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'name' => 'New Name',
            'quantity' => 15,
        ]);
    }

    public function test_can_delete_inventory_item()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $response = $this->deleteJson("/api/inventory/{$item->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Inventory item deleted successfully',
            ]);

        $this->assertDatabaseMissing('inventory_items', [
            'id' => $item->id,
        ]);
    }

    public function test_can_transfer_item_between_locations()
    {
        $fromLocation = Location::factory()->create();
        $toLocation = Location::factory()->create();
        
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $fromLocation->id,
            'quantity' => 10,
        ]);

        $transferData = [
            'from_location_id' => $fromLocation->id,
            'to_location_id' => $toLocation->id,
            'quantity' => 5,
            'notes' => 'Transfer for testing',
        ];

        $response = $this->postJson("/api/inventory/{$item->id}/transfer", $transferData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Item transferred successfully',
            ]);

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'from_location_id' => $fromLocation->id,
            'to_location_id' => $toLocation->id,
            'quantity' => 5,
            'type' => 'transfer',
        ]);
    }

    public function test_can_get_low_stock_items()
    {
        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 5,
            'minimum_stock' => 10,
            'is_active' => true,
        ]);

        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 15,
            'minimum_stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/inventory/low-stock');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_get_expiring_items()
    {
        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'expiry_date' => now()->addDays(15),
            'is_active' => true,
        ]);

        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'expiry_date' => now()->addDays(45),
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/inventory/expiring?days=30');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_validation_errors_on_create()
    {
        $response = $this->postJson('/api/inventory', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'category_id',
                'location_id',
                'quantity',
                'unit_price',
                'cost_price',
            ]);
    }

    public function test_cannot_delete_item_with_movements()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        // Create a movement for the item
        $item->movements()->create([
            'type' => 'in',
            'quantity' => 5,
            'unit_cost' => 10.00,
            'user_id' => $this->user->id,
            'movement_date' => now(),
        ]);

        $response = $this->deleteJson("/api/inventory/{$item->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete item with existing movements',
            ]);
    }

    public function test_can_search_items_by_name()
    {
        InventoryItem::factory()->create([
            'name' => 'Gold Ring',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        InventoryItem::factory()->create([
            'name' => 'Silver Necklace',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/inventory?search=Gold');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gold Ring');
    }

    public function test_can_filter_items_by_category()
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        InventoryItem::factory()->create([
            'category_id' => $category1->id,
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        InventoryItem::factory()->create([
            'category_id' => $category2->id,
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/inventory?category_id={$category1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category_id', $category1->id);
    }
}
