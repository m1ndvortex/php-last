<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\InvoiceService;
use App\Services\InventoryManagementService;
use App\Services\GoldPricingService;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Models\BusinessConfiguration;
use App\Exceptions\InsufficientInventoryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class EnhancedInvoiceServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $invoiceService;
    protected $customer;
    protected $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->invoiceService = app(InvoiceService::class);
        
        // Create and authenticate a test user
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        
        // Create test data
        $this->customer = Customer::factory()->create();
        
        $category = Category::factory()->create(['name' => 'Gold Jewelry']);
        $location = Location::factory()->create(['name' => 'Main Store']);
        
        $this->inventoryItem = InventoryItem::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 10,
            'weight' => 5.5,
            'gold_purity' => 18.0,
            'unit_price' => 1000
        ]);

        // Set up business configuration
        BusinessConfiguration::updateOrCreate(
            ['key' => 'default_labor_percentage'],
            ['value' => '10.0']
        );
        BusinessConfiguration::updateOrCreate(
            ['key' => 'default_profit_percentage'],
            ['value' => '15.0']
        );
        BusinessConfiguration::updateOrCreate(
            ['key' => 'default_tax_percentage'],
            ['value' => '9.0']
        );
    }

    public function test_create_invoice_with_dynamic_pricing_and_inventory_integration()
    {
        $invoiceData = [
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => 50.0,
                'labor_percentage' => 12.0,
                'profit_percentage' => 18.0,
                'tax_percentage' => 9.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 2,
                    'name' => $this->inventoryItem->name
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($invoiceData);

        // Verify invoice creation
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($this->customer->id, $invoice->customer_id);
        $this->assertEquals(50.0, $invoice->gold_price_per_gram);
        $this->assertEquals(12.0, $invoice->labor_percentage);
        $this->assertEquals(18.0, $invoice->profit_percentage);
        $this->assertEquals(9.0, $invoice->tax_percentage);

        // Verify inventory reduction
        $this->inventoryItem->refresh();
        $this->assertEquals(8, $this->inventoryItem->quantity); // 10 - 2 = 8

        // Verify invoice item with price breakdown
        $invoiceItem = $invoice->items->first();
        $this->assertEquals(2, $invoiceItem->quantity);
        $this->assertGreaterThan(0, $invoiceItem->base_gold_cost);
        $this->assertGreaterThan(0, $invoiceItem->labor_cost);
        $this->assertGreaterThan(0, $invoiceItem->profit_amount);
        $this->assertGreaterThan(0, $invoiceItem->tax_amount);
        $this->assertGreaterThan(0, $invoiceItem->unit_price);
        $this->assertGreaterThan(0, $invoiceItem->total_price);
    }

    public function test_create_invoice_with_insufficient_inventory_throws_exception()
    {
        $invoiceData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 15, // More than available (10)
                    'name' => $this->inventoryItem->name
                ]
            ]
        ];

        $this->expectException(InsufficientInventoryException::class);
        $this->invoiceService->createInvoice($invoiceData);

        // Verify inventory was not changed
        $this->inventoryItem->refresh();
        $this->assertEquals(10, $this->inventoryItem->quantity);
    }

    public function test_update_invoice_with_inventory_and_pricing_integration()
    {
        // Create initial invoice
        $initialData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 2,
                    'name' => $this->inventoryItem->name
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($initialData);
        
        // Verify initial inventory reduction
        $this->inventoryItem->refresh();
        $this->assertEquals(8, $this->inventoryItem->quantity);

        // Update invoice with new items and pricing
        $updateData = [
            'gold_pricing' => [
                'gold_price_per_gram' => 60.0,
                'labor_percentage' => 15.0,
                'profit_percentage' => 20.0,
                'tax_percentage' => 9.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 3, // Changed from 2 to 3
                    'name' => $this->inventoryItem->name
                ]
            ]
        ];

        $updatedInvoice = $this->invoiceService->updateInvoice($invoice, $updateData);

        // Verify pricing parameters updated
        $this->assertEquals(60.0, $updatedInvoice->gold_price_per_gram);
        $this->assertEquals(15.0, $updatedInvoice->labor_percentage);
        $this->assertEquals(20.0, $updatedInvoice->profit_percentage);

        // Verify inventory adjustment (restored 2, then reserved 3)
        $this->inventoryItem->refresh();
        // Debug: Check what the actual quantity is
        // Expected: 10 (initial) - 2 (first invoice) + 2 (restored) - 3 (new invoice) = 7
        $this->assertEquals(7, $this->inventoryItem->quantity); // 10 - 3 = 7

        // Verify new pricing calculation
        $invoiceItem = $updatedInvoice->items->first();
        $this->assertEquals(3, $invoiceItem->quantity);
        $this->assertGreaterThan(0, $invoiceItem->base_gold_cost);
    }

    public function test_cancel_invoice_restores_inventory()
    {
        $invoiceData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 3,
                    'name' => $this->inventoryItem->name
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($invoiceData);
        
        // Verify inventory reduction
        $this->inventoryItem->refresh();
        $this->assertEquals(7, $this->inventoryItem->quantity);

        // Cancel invoice
        $cancelledInvoice = $this->invoiceService->cancelInvoice($invoice, 'Test cancellation');

        // Verify inventory restoration
        $this->inventoryItem->refresh();
        $this->assertEquals(10, $this->inventoryItem->quantity);
        $this->assertEquals('cancelled', $cancelledInvoice->status);
    }

    public function test_create_invoice_with_invalid_pricing_parameters_throws_exception()
    {
        $invoiceData = [
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => -10.0, // Invalid negative price
                'labor_percentage' => 10.0,
                'profit_percentage' => 15.0,
                'tax_percentage' => 9.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 1,
                    'name' => $this->inventoryItem->name
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to process gold pricing parameters');
        
        $this->invoiceService->createInvoice($invoiceData);
    }

    public function test_bulk_invoice_creation_with_mixed_results()
    {
        // Create additional inventory item
        $inventoryItem2 = InventoryItem::factory()->create([
            'category_id' => $this->inventoryItem->category_id,
            'location_id' => $this->inventoryItem->location_id,
            'quantity' => 5,
            'weight' => 3.0,
            'gold_purity' => 21.0
        ]);

        $bulkData = [
            // Valid invoice
            [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'inventory_item_id' => $this->inventoryItem->id,
                        'quantity' => 2,
                        'name' => $this->inventoryItem->name
                    ]
                ]
            ],
            // Invalid invoice - insufficient inventory
            [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'inventory_item_id' => $inventoryItem2->id,
                        'quantity' => 10, // More than available (5)
                        'name' => $inventoryItem2->name
                    ]
                ]
            ]
        ];

        $results = $this->invoiceService->createBulkInvoices($bulkData);

        $this->assertEquals(2, $results['total_processed']);
        $this->assertEquals(1, $results['success_count']);
        $this->assertEquals(1, $results['failure_count']);
        $this->assertCount(1, $results['successful']);
        $this->assertCount(1, $results['failed']);
        $this->assertEquals('insufficient_inventory', $results['failed'][0]['error_type']);
    }

    public function test_validate_invoice_data_returns_errors_for_invalid_data()
    {
        $invalidData = [
            'customer_id' => 999999, // Non-existent customer
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => -1, // Invalid quantity
                    'name' => $this->inventoryItem->name
                ]
            ],
            'gold_pricing' => [
                'gold_price_per_gram' => -50.0, // Invalid negative price
            ],
            'issue_date' => 'invalid-date',
            'due_date' => 'invalid-date'
        ];

        $errors = $this->invoiceService->validateInvoiceData($invalidData);

        $this->assertArrayHasKey('customer_id', $errors);
        $this->assertArrayHasKey('items.0.quantity', $errors);
        $this->assertArrayHasKey('gold_pricing', $errors);
        $this->assertArrayHasKey('issue_date', $errors);
        $this->assertArrayHasKey('due_date', $errors);
    }

    public function test_get_invoice_statistics_returns_comprehensive_data()
    {
        // Create multiple invoices with different data
        $invoice1 = $this->invoiceService->createInvoice([
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => 50.0,
                'labor_percentage' => 10.0,
                'profit_percentage' => 15.0,
                'tax_percentage' => 9.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 2,
                    'name' => $this->inventoryItem->name
                ]
            ]
        ]);

        $invoice2 = $this->invoiceService->createInvoice([
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 1,
                    'name' => $this->inventoryItem->name,
                    'unit_price' => 500,
                    'total_price' => 500
                ]
            ]
        ]);

        $stats = $this->invoiceService->getInvoiceStatistics();

        $this->assertEquals(2, $stats['total_invoices']);
        $this->assertGreaterThan(0, $stats['total_amount']);
        $this->assertGreaterThan(0, $stats['average_amount']);
        $this->assertArrayHasKey('by_status', $stats);
        $this->assertArrayHasKey('by_month', $stats);
        $this->assertArrayHasKey('inventory_impact', $stats);
        $this->assertArrayHasKey('pricing_breakdown', $stats);
        
        // Verify inventory impact
        $this->assertEquals(3, $stats['inventory_impact']['total_items_sold']); // 2 + 1
        $this->assertEquals(1, $stats['inventory_impact']['unique_items_sold']);
    }

    public function test_pricing_fallback_when_gold_pricing_service_fails()
    {
        // Create invoice with item that has static pricing
        $invoiceData = [
            'customer_id' => $this->customer->id,
            'gold_pricing' => [
                'gold_price_per_gram' => 0, // This will trigger fallback
                'labor_percentage' => 10.0,
                'profit_percentage' => 15.0,
                'tax_percentage' => 9.0
            ],
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 1,
                    'name' => $this->inventoryItem->name
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($invoiceData);
        $invoiceItem = $invoice->items->first();

        // Should use inventory item's unit price as fallback
        $this->assertEquals($this->inventoryItem->unit_price, $invoiceItem->unit_price);
        $this->assertEquals(0, $invoiceItem->base_gold_cost);
        $this->assertEquals(0, $invoiceItem->labor_cost);
        $this->assertEquals(0, $invoiceItem->profit_amount);
        $this->assertEquals(0, $invoiceItem->tax_amount);
    }
}