<?php

namespace Tests\Feature;

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

class InventoryManagementServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $inventoryService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = app(InventoryManagementService::class);
        
        // Create and authenticate user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_complete_inventory_workflow_with_invoice_creation_and_cancellation()
    {
        // Create test data
        $category = Category::factory()->create();
        $location = Location::factory()->create();
        $customer = Customer::factory()->create();

        // Create inventory items
        $item1 = InventoryItem::factory()->create([
            'sku' => 'GOLD-RING-001',
            'name' => 'Gold Ring 18K',
            'quantity' => 10,
            'minimum_stock' => 2,
            'category_id' => $category->id,
            'location_id' => $location->id
        ]);

        $item2 = InventoryItem::factory()->create([
            'sku' => 'GOLD-NECKLACE-001',
            'name' => 'Gold Necklace 21K',
            'quantity' => 5,
            'minimum_stock' => 1,
            'category_id' => $category->id,
            'location_id' => $location->id
        ]);

        // Create invoice
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001'
        ]);

        // Create invoice items
        $invoiceItem1 = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'inventory_item_id' => $item1->id,
            'quantity' => 3
        ]);

        $invoiceItem2 = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'inventory_item_id' => $item2->id,
            'quantity' => 2
        ]);

        // Step 1: Check inventory availability
        $items = [
            ['inventory_item_id' => $item1->id, 'quantity' => 3],
            ['inventory_item_id' => $item2->id, 'quantity' => 2]
        ];

        $unavailableItems = $this->inventoryService->checkInventoryAvailability($items);
        $this->assertEmpty($unavailableItems, 'All items should be available');

        // Step 2: Reserve inventory
        $this->inventoryService->reserveInventory($invoice);

        // Verify inventory quantities were reduced
        $item1->refresh();
        $item2->refresh();
        $this->assertEquals(7, $item1->quantity);
        $this->assertEquals(3, $item2->quantity);

        // Verify inventory movements were created
        $movements = InventoryMovement::where('reference_type', 'invoice')
            ->where('reference_id', $invoice->id)
            ->get();

        $this->assertCount(2, $movements);

        $movement1 = $movements->where('inventory_item_id', $item1->id)->first();
        $movement2 = $movements->where('inventory_item_id', $item2->id)->first();

        $this->assertEquals('sale', $movement1->type);
        $this->assertEquals(-3, $movement1->quantity);
        $this->assertStringContainsString('INV-TEST-001', $movement1->notes);

        $this->assertEquals('sale', $movement2->type);
        $this->assertEquals(-2, $movement2->quantity);
        $this->assertStringContainsString('INV-TEST-001', $movement2->notes);

        // Step 3: Try to reserve more than available (should fail)
        $item3 = InventoryItem::factory()->create([
            'quantity' => 1,
            'category_id' => $category->id,
            'location_id' => $location->id
        ]);

        $invoice2 = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-002'
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice2->id,
            'inventory_item_id' => $item3->id,
            'quantity' => 5 // More than available
        ]);

        $this->expectException(InsufficientInventoryException::class);
        $this->inventoryService->reserveInventory($invoice2);

        // Step 4: Restore inventory (cancel original invoice)
        $this->inventoryService->restoreInventory($invoice);

        // Verify inventory quantities were restored
        $item1->refresh();
        $item2->refresh();
        $this->assertEquals(10, $item1->quantity);
        $this->assertEquals(5, $item2->quantity);

        // Verify return movements were created
        $returnMovements = InventoryMovement::where('reference_type', 'invoice_cancellation')
            ->where('reference_id', $invoice->id)
            ->get();

        $this->assertCount(2, $returnMovements);

        $returnMovement1 = $returnMovements->where('inventory_item_id', $item1->id)->first();
        $returnMovement2 = $returnMovements->where('inventory_item_id', $item2->id)->first();

        $this->assertEquals('return', $returnMovement1->type);
        $this->assertEquals(3, $returnMovement1->quantity);
        $this->assertStringContainsString('cancelled Invoice #INV-TEST-001', $returnMovement1->notes);

        $this->assertEquals('return', $returnMovement2->type);
        $this->assertEquals(2, $returnMovement2->quantity);
        $this->assertStringContainsString('cancelled Invoice #INV-TEST-001', $returnMovement2->notes);
    }

    public function test_low_stock_items_detection()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        // Create items with different stock levels
        $lowStockItem1 = InventoryItem::factory()->create([
            'name' => 'Low Stock Item 1',
            'quantity' => 1,
            'minimum_stock' => 5,
            'is_active' => true,
            'category_id' => $category->id,
            'location_id' => $location->id
        ]);

        $lowStockItem2 = InventoryItem::factory()->create([
            'name' => 'Low Stock Item 2',
            'quantity' => 3,
            'minimum_stock' => 10,
            'is_active' => true,
            'category_id' => $category->id,
            'location_id' => $location->id
        ]);

        $normalStockItem = InventoryItem::factory()->create([
            'name' => 'Normal Stock Item',
            'quantity' => 20,
            'minimum_stock' => 5,
            'is_active' => true,
            'category_id' => $category->id,
            'location_id' => $location->id
        ]);

        $inactiveItem = InventoryItem::factory()->create([
            'name' => 'Inactive Item',
            'quantity' => 1,
            'minimum_stock' => 5,
            'is_active' => false,
            'category_id' => $category->id,
            'location_id' => $location->id
        ]);

        $lowStockItems = $this->inventoryService->getLowStockItems();

        $this->assertCount(2, $lowStockItems);
        
        $itemNames = $lowStockItems->pluck('name')->toArray();
        $this->assertContains('Low Stock Item 1', $itemNames);
        $this->assertContains('Low Stock Item 2', $itemNames);
        $this->assertNotContains('Normal Stock Item', $itemNames);
        $this->assertNotContains('Inactive Item', $itemNames);
    }

    public function test_inventory_movements_tracking()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();
        $customer = Customer::factory()->create();

        $item = InventoryItem::factory()->create([
            'quantity' => 10,
            'category_id' => $category->id,
            'location_id' => $location->id
        ]);

        // Create multiple invoices to generate movements
        for ($i = 1; $i <= 3; $i++) {
            $invoice = Invoice::factory()->create([
                'customer_id' => $customer->id,
                'invoice_number' => "INV-TRACK-{$i}"
            ]);

            InvoiceItem::factory()->create([
                'invoice_id' => $invoice->id,
                'inventory_item_id' => $item->id,
                'quantity' => 1
            ]);

            $this->inventoryService->reserveInventory($invoice);
        }

        // Get movements for the item
        $movements = $this->inventoryService->getInventoryMovements($item->id);

        $this->assertCount(3, $movements);
        
        // Verify movements are ordered by created_at desc
        $this->assertStringContainsString('INV-TRACK-3', $movements[0]->notes);
        $this->assertStringContainsString('INV-TRACK-2', $movements[1]->notes);
        $this->assertStringContainsString('INV-TRACK-1', $movements[2]->notes);

        // Verify final quantity
        $item->refresh();
        $this->assertEquals(7, $item->quantity);
    }

    public function test_validate_inventory_availability_method()
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();

        $item = InventoryItem::factory()->create([
            'quantity' => 5,
            'category_id' => $category->id,
            'location_id' => $location->id
        ]);

        // Test with sufficient stock - should not throw exception
        $items = [['inventory_item_id' => $item->id, 'quantity' => 3]];
        
        try {
            $this->inventoryService->validateInventoryAvailability($items);
            $this->assertTrue(true); // If we get here, no exception was thrown
        } catch (InsufficientInventoryException $e) {
            $this->fail('Should not throw exception with sufficient stock');
        }

        // Test with insufficient stock - should throw exception
        $items = [['inventory_item_id' => $item->id, 'quantity' => 10]];
        
        $this->expectException(InsufficientInventoryException::class);
        $this->inventoryService->validateInventoryAvailability($items);
    }
}