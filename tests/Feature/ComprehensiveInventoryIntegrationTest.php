<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\InventoryManagementService;
use App\Services\InvoiceService;
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
use Illuminate\Support\Facades\DB;

class ComprehensiveInventoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $inventoryService;
    protected $invoiceService;
    protected $user;
    protected $category;
    protected $location;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = app(InventoryManagementService::class);
        $this->invoiceService = app(InvoiceService::class);
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->category = Category::factory()->create(['name' => 'Gold Jewelry']);
        $this->location = Location::factory()->create(['name' => 'Main Store']);
        $this->customer = Customer::factory()->create();
    }

    /** @test */
    public function it_handles_complex_multi_item_inventory_scenarios()
    {
        // Create multiple inventory items with different stock levels
        $items = collect();
        for ($i = 1; $i <= 5; $i++) {
            $items->push(InventoryItem::factory()->create([
                'sku' => "GOLD-ITEM-{$i}",
                'name' => "Gold Item {$i}",
                'quantity' => $i * 10, // 10, 20, 30, 40, 50
                'minimum_stock' => $i * 2, // 2, 4, 6, 8, 10
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'weight' => $i * 2.5,
                'gold_purity' => 18.0
            ]));
        }

        // Create invoice with multiple items
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-MULTI-001'
        ]);

        $invoiceItems = [];
        foreach ($items as $index => $item) {
            $quantity = ($index + 1) * 2; // 2, 4, 6, 8, 10
            $invoiceItems[] = InvoiceItem::factory()->create([
                'invoice_id' => $invoice->id,
                'inventory_item_id' => $item->id,
                'quantity' => $quantity
            ]);
        }

        // Test availability check
        $itemsToCheck = $items->map(function ($item, $index) {
            return [
                'inventory_item_id' => $item->id,
                'quantity' => ($index + 1) * 2
            ];
        })->toArray();

        $unavailableItems = $this->inventoryService->checkInventoryAvailability($itemsToCheck);
        $this->assertEmpty($unavailableItems, 'All items should be available');

        // Reserve inventory
        $this->inventoryService->reserveInventory($invoice);

        // Verify all quantities were reduced correctly
        foreach ($items as $index => $item) {
            $item->refresh();
            $expectedQuantity = ($index + 1) * 10 - ($index + 1) * 2; // Original - Reserved
            $this->assertEquals($expectedQuantity, $item->quantity, "Item {$item->sku} quantity mismatch");
        }

        // Verify movements were created
        $movements = InventoryMovement::where('reference_type', 'invoice')
            ->where('reference_id', $invoice->id)
            ->get();

        $this->assertCount(5, $movements);

        foreach ($movements as $movement) {
            $this->assertEquals('sale', $movement->type);
            $this->assertLessThan(0, $movement->quantity);
            $this->assertStringContainsString('INV-MULTI-001', $movement->notes);
        }

        // Test restoration
        $this->inventoryService->restoreInventory($invoice);

        // Verify all quantities were restored
        foreach ($items as $index => $item) {
            $item->refresh();
            $originalQuantity = ($index + 1) * 10;
            $this->assertEquals($originalQuantity, $item->quantity, "Item {$item->sku} not properly restored");
        }
    }

    /** @test */
    public function it_handles_concurrent_inventory_operations()
    {
        $item = InventoryItem::factory()->create([
            'quantity' => 10,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        // Simulate concurrent operations using database transactions
        $results = [];
        
        // First transaction - reserve 6 items
        DB::transaction(function () use ($item, &$results) {
            $invoice1 = Invoice::factory()->create([
                'customer_id' => $this->customer->id,
                'invoice_number' => 'INV-CONCURRENT-001'
            ]);

            InvoiceItem::factory()->create([
                'invoice_id' => $invoice1->id,
                'inventory_item_id' => $item->id,
                'quantity' => 6
            ]);

            try {
                $this->inventoryService->reserveInventory($invoice1);
                $results['first'] = 'success';
            } catch (InsufficientInventoryException $e) {
                $results['first'] = 'failed';
            }
        });

        // Second transaction - try to reserve 8 items (should fail)
        DB::transaction(function () use ($item, &$results) {
            $invoice2 = Invoice::factory()->create([
                'customer_id' => $this->customer->id,
                'invoice_number' => 'INV-CONCURRENT-002'
            ]);

            InvoiceItem::factory()->create([
                'invoice_id' => $invoice2->id,
                'inventory_item_id' => $item->id,
                'quantity' => 8
            ]);

            try {
                $this->inventoryService->reserveInventory($invoice2);
                $results['second'] = 'success';
            } catch (InsufficientInventoryException $e) {
                $results['second'] = 'failed';
            }
        });

        $this->assertEquals('success', $results['first']);
        $this->assertEquals('failed', $results['second']);

        // Verify final quantity
        $item->refresh();
        $this->assertEquals(4, $item->quantity); // 10 - 6 = 4
    }

    /** @test */
    public function it_tracks_inventory_movements_with_detailed_history()
    {
        $item = InventoryItem::factory()->create([
            'quantity' => 100,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        // Create multiple invoices to generate movement history
        $invoices = [];
        for ($i = 1; $i <= 5; $i++) {
            $invoice = Invoice::factory()->create([
                'customer_id' => $this->customer->id,
                'invoice_number' => "INV-HISTORY-{$i}"
            ]);

            InvoiceItem::factory()->create([
                'invoice_id' => $invoice->id,
                'inventory_item_id' => $item->id,
                'quantity' => $i * 3 // 3, 6, 9, 12, 15
            ]);

            $this->inventoryService->reserveInventory($invoice);
            $invoices[] = $invoice;
        }

        // Cancel some invoices to create return movements
        $this->inventoryService->restoreInventory($invoices[1]); // Restore 6 items
        $this->inventoryService->restoreInventory($invoices[3]); // Restore 12 items

        // Get movement history
        $movements = $this->inventoryService->getInventoryMovements($item->id);

        // Should have 5 sales + 2 returns = 7 movements
        $this->assertCount(7, $movements);

        // Verify movement types
        $saleMovements = $movements->where('type', 'sale');
        $returnMovements = $movements->where('type', 'return');

        $this->assertCount(5, $saleMovements);
        $this->assertCount(2, $returnMovements);

        // Verify quantities
        $totalSold = $saleMovements->sum('quantity'); // Should be negative
        $totalReturned = $returnMovements->sum('quantity'); // Should be positive

        $this->assertEquals(-45, $totalSold); // -(3+6+9+12+15)
        $this->assertEquals(18, $totalReturned); // 6+12

        // Verify final quantity
        $item->refresh();
        $this->assertEquals(73, $item->quantity); // 100 - 45 + 18 = 73
    }

    /** @test */
    public function it_identifies_low_stock_items_accurately()
    {
        // Create items with various stock levels
        $items = collect();

        // Low stock items
        $items->push(InventoryItem::factory()->create([
            'name' => 'Low Stock Ring',
            'quantity' => 2,
            'minimum_stock' => 10,
            'is_active' => true,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]));

        $items->push(InventoryItem::factory()->create([
            'name' => 'Critical Stock Necklace',
            'quantity' => 0,
            'minimum_stock' => 5,
            'is_active' => true,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]));

        // Normal stock items
        $items->push(InventoryItem::factory()->create([
            'name' => 'Normal Stock Bracelet',
            'quantity' => 20,
            'minimum_stock' => 5,
            'is_active' => true,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]));

        // Inactive item (should not appear in low stock)
        $items->push(InventoryItem::factory()->create([
            'name' => 'Inactive Item',
            'quantity' => 1,
            'minimum_stock' => 10,
            'is_active' => false,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]));

        $lowStockItems = $this->inventoryService->getLowStockItems();

        $this->assertCount(2, $lowStockItems);

        $lowStockNames = $lowStockItems->pluck('name')->toArray();
        $this->assertContains('Low Stock Ring', $lowStockNames);
        $this->assertContains('Critical Stock Necklace', $lowStockNames);
        $this->assertNotContains('Normal Stock Bracelet', $lowStockNames);
        $this->assertNotContains('Inactive Item', $lowStockNames);
    }

    /** @test */
    public function it_handles_partial_inventory_failures_gracefully()
    {
        // Create items with different availability
        $availableItem = InventoryItem::factory()->create([
            'quantity' => 10,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        $unavailableItem = InventoryItem::factory()->create([
            'quantity' => 2,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        $itemsToCheck = [
            ['inventory_item_id' => $availableItem->id, 'quantity' => 5],
            ['inventory_item_id' => $unavailableItem->id, 'quantity' => 5], // More than available
            ['inventory_item_id' => 999999, 'quantity' => 1] // Non-existent item
        ];

        $unavailableItems = $this->inventoryService->checkInventoryAvailability($itemsToCheck);

        $this->assertCount(2, $unavailableItems);

        // Check unavailable item details
        $unavailableItemIds = array_column($unavailableItems, 'item_id');
        $this->assertContains($unavailableItem->id, $unavailableItemIds);
        $this->assertContains(999999, $unavailableItemIds);

        // Verify error messages
        foreach ($unavailableItems as $unavailable) {
            if ($unavailable['item_id'] == $unavailableItem->id) {
                $this->assertEquals('Insufficient inventory', $unavailable['error']);
                $this->assertEquals(5, $unavailable['requested_quantity']);
                $this->assertEquals(2, $unavailable['available_quantity']);
            } elseif ($unavailable['item_id'] == 999999) {
                $this->assertEquals('Item not found', $unavailable['error']);
            }
        }
    }

    /** @test */
    public function it_maintains_inventory_integrity_during_complex_operations()
    {
        $item = InventoryItem::factory()->create([
            'quantity' => 50,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id
        ]);

        $initialQuantity = $item->quantity;
        $operations = [];

        // Perform multiple operations
        for ($i = 1; $i <= 10; $i++) {
            $invoice = Invoice::factory()->create([
                'customer_id' => $this->customer->id,
                'invoice_number' => "INV-INTEGRITY-{$i}"
            ]);

            $quantity = rand(1, 5);
            InvoiceItem::factory()->create([
                'invoice_id' => $invoice->id,
                'inventory_item_id' => $item->id,
                'quantity' => $quantity
            ]);

            try {
                $this->inventoryService->reserveInventory($invoice);
                $operations[] = ['type' => 'reserve', 'quantity' => $quantity, 'invoice' => $invoice];
            } catch (InsufficientInventoryException $e) {
                $operations[] = ['type' => 'failed', 'quantity' => $quantity];
            }
        }

        // Cancel some random invoices
        $reservedOperations = array_filter($operations, fn($op) => $op['type'] === 'reserve');
        $toCancel = array_slice($reservedOperations, 0, 3);

        foreach ($toCancel as $operation) {
            $this->inventoryService->restoreInventory($operation['invoice']);
            $operations[] = ['type' => 'restore', 'quantity' => $operation['quantity']];
        }

        // Calculate expected final quantity
        $totalReserved = array_sum(array_column(array_filter($operations, fn($op) => $op['type'] === 'reserve'), 'quantity'));
        $totalRestored = array_sum(array_column(array_filter($operations, fn($op) => $op['type'] === 'restore'), 'quantity'));
        $expectedQuantity = $initialQuantity - $totalReserved + $totalRestored;

        // Verify final quantity matches expected
        $item->refresh();
        $this->assertEquals($expectedQuantity, $item->quantity);

        // Verify movement history matches operations
        $movements = InventoryMovement::where('inventory_item_id', $item->id)->get();
        $saleMovements = $movements->where('type', 'sale');
        $returnMovements = $movements->where('type', 'return');

        $this->assertEquals($totalReserved, abs($saleMovements->sum('quantity')));
        $this->assertEquals($totalRestored, $returnMovements->sum('quantity'));
    }

    /** @test */
    public function it_calculates_inventory_statistics_manually()
    {
        // Create diverse inventory items
        $items = collect();
        
        // High value items
        for ($i = 1; $i <= 3; $i++) {
            $items->push(InventoryItem::factory()->create([
                'quantity' => rand(5, 15),
                'unit_price' => rand(1000, 3000),
                'cost_price' => rand(800, 2400),
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'is_active' => true
            ]));
        }

        // Low value items
        for ($i = 1; $i <= 5; $i++) {
            $items->push(InventoryItem::factory()->create([
                'quantity' => rand(10, 50),
                'unit_price' => rand(100, 500),
                'cost_price' => rand(80, 400),
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'is_active' => true
            ]));
        }

        // Items without prices
        for ($i = 1; $i <= 2; $i++) {
            $items->push(InventoryItem::factory()->create([
                'quantity' => rand(5, 20),
                'unit_price' => null,
                'cost_price' => null,
                'category_id' => $this->category->id,
                'location_id' => $this->location->id,
                'is_active' => true
            ]));
        }

        // Calculate statistics manually
        $totalItems = InventoryItem::count();
        $totalQuantity = InventoryItem::sum('quantity');
        $itemsWithPrices = InventoryItem::whereNotNull('unit_price')->count();
        $itemsWithoutPrices = InventoryItem::whereNull('unit_price')->count();
        $lowStockItems = $this->inventoryService->getLowStockItems();

        $this->assertEquals(10, $totalItems);
        $this->assertEquals(8, $itemsWithPrices);
        $this->assertEquals(2, $itemsWithoutPrices);
        $this->assertGreaterThan(0, $totalQuantity);
        $this->assertIsInt($lowStockItems->count());
    }
}