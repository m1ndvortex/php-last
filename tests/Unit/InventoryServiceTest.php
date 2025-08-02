<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;
    protected User $user;
    protected Category $category;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = new InventoryService();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
        
        $this->actingAs($this->user);
    }

    public function test_create_item_with_initial_stock()
    {
        $itemData = [
            'name' => 'Gold Ring',
            'sku' => 'GR001',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'unit_price' => 100.00,
            'cost_price' => 80.00,
        ];

        $item = $this->inventoryService->createItem($itemData);

        $this->assertInstanceOf(InventoryItem::class, $item);
        $this->assertEquals(10, $item->quantity);
        
        // Check initial stock movement was created
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'type' => InventoryMovement::TYPE_IN,
            'quantity' => 10,
            'reference_type' => 'initial_stock',
        ]);
    }

    public function test_update_item_quantity_creates_adjustment_movement()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
        ]);

        $this->inventoryService->updateItem($item, ['quantity' => 15]);

        $this->assertEquals(15, $item->fresh()->quantity);
        
        // Check adjustment movement was created
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'type' => InventoryMovement::TYPE_ADJUSTMENT,
            'quantity' => 5,
            'reference_type' => 'manual_adjustment',
        ]);
    }

    public function test_transfer_item_between_locations()
    {
        $fromLocation = Location::factory()->create();
        $toLocation = Location::factory()->create();
        
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $fromLocation->id,
            'quantity' => 10,
        ]);

        $movement = $this->inventoryService->transferItem(
            $item,
            $fromLocation->id,
            $toLocation->id,
            5,
            'Test transfer'
        );

        $this->assertInstanceOf(InventoryMovement::class, $movement);
        $this->assertEquals(InventoryMovement::TYPE_TRANSFER, $movement->type);
        $this->assertEquals(5, $movement->quantity);
        $this->assertEquals($fromLocation->id, $movement->from_location_id);
        $this->assertEquals($toLocation->id, $movement->to_location_id);
    }

    public function test_transfer_insufficient_stock_throws_exception()
    {
        $fromLocation = Location::factory()->create();
        $toLocation = Location::factory()->create();
        
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $fromLocation->id,
            'quantity' => 5,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock for transfer');

        $this->inventoryService->transferItem(
            $item,
            $fromLocation->id,
            $toLocation->id,
            10
        );
    }

    public function test_get_low_stock_items()
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

        $lowStockItems = $this->inventoryService->getLowStockItems();
        
        $this->assertCount(1, $lowStockItems);
    }

    public function test_get_expiring_items()
    {
        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'expiry_date' => Carbon::now()->addDays(15),
            'is_active' => true,
        ]);

        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'expiry_date' => Carbon::now()->addDays(45),
            'is_active' => true,
        ]);

        $expiringItems = $this->inventoryService->getExpiringItems(30);
        
        $this->assertCount(1, $expiringItems);
    }

    public function test_generate_sku()
    {
        $category = Category::factory()->create(['code' => 'GLD']);
        
        $sku = $this->inventoryService->generateSKU($category);
        $this->assertEquals('GLD001', $sku);

        // Create an item with the generated SKU
        InventoryItem::factory()->create([
            'sku' => $sku,
            'category_id' => $category->id,
            'location_id' => $this->location->id,
        ]);

        // Generate next SKU
        $nextSku = $this->inventoryService->generateSKU($category);
        $this->assertEquals('GLD002', $nextSku);
    }

    public function test_search_items_with_filters()
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        $location1 = Location::factory()->create();
        
        InventoryItem::factory()->create([
            'name' => 'Gold Ring',
            'category_id' => $category1->id,
            'location_id' => $location1->id,
            'is_active' => true,
        ]);

        InventoryItem::factory()->create([
            'name' => 'Silver Necklace',
            'category_id' => $category2->id,
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        // Test search by name
        $results = $this->inventoryService->searchItems(['search' => 'Gold']);
        $this->assertCount(1, $results);
        $this->assertEquals('Gold Ring', $results->first()->name);

        // Test filter by category
        $results = $this->inventoryService->searchItems(['category_id' => $category1->id]);
        $this->assertCount(1, $results);
        $this->assertEquals($category1->id, $results->first()->category_id);

        // Test filter by location
        $results = $this->inventoryService->searchItems(['location_id' => $location1->id]);
        $this->assertCount(1, $results);
        $this->assertEquals($location1->id, $results->first()->location_id);
    }

    public function test_calculate_current_stock()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 0,
        ]);

        // Create some movements
        InventoryMovement::factory()->create([
            'inventory_item_id' => $item->id,
            'type' => InventoryMovement::TYPE_IN,
            'quantity' => 20,
        ]);

        InventoryMovement::factory()->create([
            'inventory_item_id' => $item->id,
            'type' => InventoryMovement::TYPE_OUT,
            'quantity' => 5,
        ]);

        InventoryMovement::factory()->create([
            'inventory_item_id' => $item->id,
            'type' => InventoryMovement::TYPE_PRODUCTION,
            'quantity' => 10,
        ]);

        $currentStock = $this->inventoryService->calculateCurrentStock($item);
        $this->assertEquals(25, $currentStock); // 20 + 10 - 5
    }

    public function test_get_movement_history()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        // Create multiple movements
        InventoryMovement::factory()->count(3)->create([
            'inventory_item_id' => $item->id,
        ]);

        $movements = $this->inventoryService->getMovementHistory($item);
        
        $this->assertCount(3, $movements);
        $this->assertTrue($movements->first()->movement_date >= $movements->last()->movement_date);
    }
}
