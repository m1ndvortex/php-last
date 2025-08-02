<?php

namespace Tests\Unit;

use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class InventoryItemModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test category and location
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
    }

    public function test_inventory_item_can_be_created()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $this->assertInstanceOf(InventoryItem::class, $item);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);
    }

    public function test_inventory_item_belongs_to_category()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $this->assertInstanceOf(Category::class, $item->category);
        $this->assertEquals($this->category->id, $item->category->id);
    }

    public function test_inventory_item_belongs_to_location()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $this->assertInstanceOf(Location::class, $item->location);
        $this->assertEquals($this->location->id, $item->location->id);
    }

    public function test_total_value_attribute()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'unit_price' => 25.50,
        ]);

        $this->assertEquals(255.0, $item->total_value);
    }

    public function test_total_cost_attribute()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 10,
            'cost_price' => 20.00,
        ]);

        $this->assertEquals(200.0, $item->total_cost);
    }

    public function test_is_low_stock_attribute()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 5,
            'minimum_stock' => 10,
        ]);

        $this->assertTrue($item->is_low_stock);

        $item->update(['quantity' => 15]);
        $this->assertFalse($item->fresh()->is_low_stock);
    }

    public function test_is_expiring_attribute()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'expiry_date' => Carbon::now()->addDays(15),
        ]);

        $this->assertTrue($item->is_expiring);

        $item->update(['expiry_date' => Carbon::now()->addDays(45)]);
        $this->assertFalse($item->fresh()->is_expiring);
    }

    public function test_is_expired_attribute()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'expiry_date' => Carbon::now()->subDays(5),
        ]);

        $this->assertTrue($item->is_expired);

        $item->update(['expiry_date' => Carbon::now()->addDays(5)]);
        $this->assertFalse($item->fresh()->is_expired);
    }

    public function test_localized_name_attribute()
    {
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'name' => 'Gold Ring',
            'name_persian' => 'حلقه طلا',
        ]);

        // Test English locale
        app()->setLocale('en');
        $this->assertEquals('Gold Ring', $item->localized_name);

        // Test Persian locale
        app()->setLocale('fa');
        $this->assertEquals('حلقه طلا', $item->localized_name);
    }

    public function test_active_scope()
    {
        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'is_active' => false,
        ]);

        $activeItems = InventoryItem::active()->get();
        $this->assertCount(1, $activeItems);
        $this->assertTrue($activeItems->first()->is_active);
    }

    public function test_low_stock_scope()
    {
        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 5,
            'minimum_stock' => 10,
        ]);

        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'quantity' => 15,
            'minimum_stock' => 10,
        ]);

        $lowStockItems = InventoryItem::lowStock()->get();
        $this->assertCount(1, $lowStockItems);
    }

    public function test_expiring_scope()
    {
        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'expiry_date' => Carbon::now()->addDays(15),
        ]);

        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'expiry_date' => Carbon::now()->addDays(45),
        ]);

        $expiringItems = InventoryItem::expiring(30)->get();
        $this->assertCount(1, $expiringItems);
    }

    public function test_expired_scope()
    {
        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'expiry_date' => Carbon::now()->subDays(5),
        ]);

        InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'expiry_date' => Carbon::now()->addDays(5),
        ]);

        $expiredItems = InventoryItem::expired()->get();
        $this->assertCount(1, $expiredItems);
    }
}
