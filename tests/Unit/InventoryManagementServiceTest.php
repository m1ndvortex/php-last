<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InventoryManagementService;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Location;
use App\Exceptions\InsufficientInventoryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class InventoryManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $inventoryService;
    protected $user;
    protected $category;
    protected $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = new InventoryManagementService();
        
        // Create test user
        $this->user = User::factory()->create();
        Auth::login($this->user);
        
        // Create test category and location
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
    }

    public function test_check_inventory_availability_with_sufficient_stock()
    {
        // Create inventory item with sufficient stock
        $item = InventoryItem::factory()->create([
            'quantity' => 10,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        $items = [
            ['inventory_item_id' => $item->id, 'quantity' => 5]
        ];

        $unavailableItems = $this->inventoryService->checkInventoryAvailability($items);

        $this->assertEmpty($unavailableItems);
    }

    public function test_check_inventory_availability_with_insufficient_stock()
    {
        // Create inventory item with insufficient stock
        $item = InventoryItem::factory()->create([
            'quantity' => 3,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        $items = [
            ['inventory_item_id' => $item->id, 'quantity' => 5]
        ];

        $unavailableItems = $this->inventoryService->checkInventoryAvailability($items);

        $this->assertCount(1, $unavailableItems);
        $this->assertEquals($item->id, $unavailableItems[0]['item_id']);
        $this->assertEquals(5, $unavailableItems[0]['requested_quantity']);
        $this->assertEquals(3, $unavailableItems[0]['available_quantity']);
        $this->assertEquals('Insufficient inventory', $unavailableItems[0]['error']);
    }

    public function test_check_inventory_availability_with_nonexistent_item()
    {
        $items = [
            ['inventory_item_id' => 999, 'quantity' => 5]
        ];

        $unavailableItems = $this->inventoryService->checkInventoryAvailability($items);

        $this->assertCount(1, $unavailableItems);
        $this->assertEquals(999, $unavailableItems[0]['item_id']);
        $this->assertEquals('Item not found', $unavailableItems[0]['error']);
    }

    public function test_reserve_inventory_success()
    {
        // Create inventory item
        $item = InventoryItem::factory()->create([
            'quantity' => 10,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        // Create invoice with items
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-001'
        ]);

        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'inventory_item_id' => $item->id,
            'quantity' => 3
        ]);

        // Reserve inventory
        $this->inventoryService->reserveInventory($invoice);

        // Check that inventory was reduced
        $item->refresh();
        $this->assertEquals(7, $item->quantity);

        // Check that inventory movement was created
        $movement = InventoryMovement::where('inventory_item_id', $item->id)
            ->where('type', 'sale')
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(-3, $movement->quantity);
        $this->assertEquals('invoice', $movement->reference_type);
        $this->assertEquals($invoice->id, $movement->reference_id);
        $this->assertStringContainsString('INV-001', $movement->notes);
    }

    public function test_reserve_inventory_throws_exception_for_insufficient_stock()
    {
        // Create inventory item with insufficient stock
        $item = InventoryItem::factory()->create([
            'quantity' => 2,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        // Create invoice with items
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-002'
        ]);

        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'inventory_item_id' => $item->id,
            'quantity' => 5
        ]);

        // Expect exception
        $this->expectException(InsufficientInventoryException::class);

        $this->inventoryService->reserveInventory($invoice);

        // Verify inventory was not changed
        $item->refresh();
        $this->assertEquals(2, $item->quantity);
    }

    public function test_restore_inventory_success()
    {
        // Create inventory item
        $item = InventoryItem::factory()->create([
            'quantity' => 7, // Already reduced from previous sale
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        // Create invoice with items
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-003'
        ]);

        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'inventory_item_id' => $item->id,
            'quantity' => 3
        ]);

        // Restore inventory
        $this->inventoryService->restoreInventory($invoice);

        // Check that inventory was increased
        $item->refresh();
        $this->assertEquals(10, $item->quantity);

        // Check that inventory movement was created
        $movement = InventoryMovement::where('inventory_item_id', $item->id)
            ->where('type', 'return')
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(3, $movement->quantity);
        $this->assertEquals('invoice_cancellation', $movement->reference_type);
        $this->assertEquals($invoice->id, $movement->reference_id);
        $this->assertStringContainsString('INV-003', $movement->notes);
    }

    public function test_validate_inventory_availability_success()
    {
        // Create inventory item with sufficient stock
        $item = InventoryItem::factory()->create([
            'quantity' => 10,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        $items = [
            ['inventory_item_id' => $item->id, 'quantity' => 5]
        ];

        // Should not throw exception
        $this->inventoryService->validateInventoryAvailability($items);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function test_validate_inventory_availability_throws_exception()
    {
        // Create inventory item with insufficient stock
        $item = InventoryItem::factory()->create([
            'quantity' => 3,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        $items = [
            ['inventory_item_id' => $item->id, 'quantity' => 5]
        ];

        $this->expectException(InsufficientInventoryException::class);
        $this->inventoryService->validateInventoryAvailability($items);
    }

    public function test_get_low_stock_items()
    {
        // Create items with different stock levels
        $lowStockItem = InventoryItem::factory()->create([
            'quantity' => 2,
            'minimum_stock' => 5,
            'is_active' => true,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        $normalStockItem = InventoryItem::factory()->create([
            'quantity' => 10,
            'minimum_stock' => 5,
            'is_active' => true,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        $inactiveItem = InventoryItem::factory()->create([
            'quantity' => 1,
            'minimum_stock' => 5,
            'is_active' => false,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        $lowStockItems = $this->inventoryService->getLowStockItems();

        $this->assertCount(1, $lowStockItems);
        $this->assertEquals($lowStockItem->id, $lowStockItems->first()->id);
    }

    public function test_get_inventory_movements()
    {
        // Create inventory item
        $item = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        // Create some movements
        InventoryMovement::factory()->count(3)->create([
            'inventory_item_id' => $item->id,
            'created_by' => $this->user->id
        ]);

        // Create movements for different item (should not be included)
        $otherItem = InventoryItem::factory()->create([
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);
        InventoryMovement::factory()->create([
            'inventory_item_id' => $otherItem->id,
            'created_by' => $this->user->id
        ]);

        $movements = $this->inventoryService->getInventoryMovements($item->id);

        $this->assertCount(3, $movements);
        $movements->each(function ($movement) use ($item) {
            $this->assertEquals($item->id, $movement->inventory_item_id);
        });
    }
}