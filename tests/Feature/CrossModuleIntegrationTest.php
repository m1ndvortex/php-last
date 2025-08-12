<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InventoryMovement;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\IntegrationEventService;
use App\Services\InvoiceService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class CrossModuleIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $customer;
    protected $inventoryItem;
    protected $integrationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->inventoryItem = InventoryItem::factory()->create([
            'quantity' => 10,
            'unit_price' => 100,
            'cost_price' => 80,
            'reorder_level' => 5,
        ]);

        // Create basic chart of accounts for testing
        Account::factory()->create(['code' => '1210', 'name' => 'Trade Receivables', 'type' => 'asset']);
        Account::factory()->create(['code' => '4110', 'name' => 'Gold Jewelry Sales', 'type' => 'revenue']);
        Account::factory()->create(['code' => '2310', 'name' => 'Sales Tax Payable', 'type' => 'liability']);
        Account::factory()->create(['code' => '1350', 'name' => 'Finished Goods', 'type' => 'asset']);
        Account::factory()->create(['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense']);

        $this->integrationService = app(IntegrationEventService::class);
    }

    public function test_invoice_creation_triggers_cross_module_integration()
    {
        $this->actingAs($this->user);

        $invoiceService = app(InvoiceService::class);

        $invoiceData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                ]
            ],
            'gold_pricing' => [
                'gold_price_per_gram' => 0, // Use static pricing
                'labor_percentage' => 10,
                'profit_percentage' => 15,
                'tax_percentage' => 9,
            ]
        ];

        $invoice = $invoiceService->createInvoice($invoiceData);

        // Verify invoice was created
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($this->customer->id, $invoice->customer_id);

        // Verify inventory was updated
        $this->inventoryItem->refresh();
        $this->assertEquals(8, $this->inventoryItem->quantity); // 10 - 2 = 8

        // Verify inventory movement was created
        $movement = InventoryMovement::where('inventory_item_id', $this->inventoryItem->id)
            ->where('reference_type', 'sale')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(-2, $movement->quantity_change ?? -$movement->quantity);

        // Verify accounting entries were created
        $transaction = Transaction::where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->first();
        $this->assertNotNull($transaction);

        // Verify customer statistics were updated
        $this->customer->refresh();
        $this->assertNotNull($this->customer->last_purchase_date);
    }

    public function test_inventory_adjustment_creates_accounting_entries()
    {
        $this->actingAs($this->user);

        $inventoryService = app(InventoryService::class);

        // Create an inventory adjustment
        $movement = $inventoryService->updateStock(
            $this->inventoryItem->id,
            -3, // Reduce by 3 units
            'adjustment',
            'Test adjustment'
        );

        // Verify movement was created
        $this->assertInstanceOf(InventoryMovement::class, $movement);

        // Verify inventory quantity was updated
        $this->inventoryItem->refresh();
        $this->assertEquals(7, $this->inventoryItem->quantity); // 10 - 3 = 7

        // Verify accounting entry was created
        $transaction = Transaction::where('source_type', 'inventory_movement')
            ->where('source_id', $movement->id)
            ->first();
        $this->assertNotNull($transaction);
    }

    public function test_customer_update_triggers_integration()
    {
        $this->actingAs($this->user);

        $originalName = $this->customer->name;
        $newName = 'Updated Customer Name';

        // Update customer
        $changes = ['name' => $newName];
        $this->customer->update($changes);

        // Trigger integration manually (normally done by observer)
        $this->integrationService->handleCustomerUpdated($this->customer, $changes);

        // Verify customer was updated
        $this->customer->refresh();
        $this->assertEquals($newName, $this->customer->name);

        // Verify communication log was created
        $communication = $this->customer->communications()
            ->where('message', 'like', '%Customer updated%')
            ->first();
        $this->assertNotNull($communication);
    }

    public function test_data_consistency_validation()
    {
        $this->actingAs($this->user);

        // Create some test data with potential inconsistencies
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        // Run consistency validation
        $issues = $this->integrationService->validateDataConsistency();

        // Should return an array (empty or with issues)
        $this->assertIsArray($issues);
    }

    public function test_integration_status_endpoint()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/integration/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'invoices_with_inventory_updates',
                    'inventory_movements_with_accounting',
                    'customers_with_purchase_history',
                    'active_alerts',
                    'recent_integrations',
                ]
            ]);
    }

    public function test_data_consistency_validation_endpoint()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/integration/validate-consistency');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'issues_found',
                    'issues',
                    'status',
                ]
            ]);
    }

    public function test_low_stock_alert_creation()
    {
        $this->actingAs($this->user);

        // Reduce inventory below reorder level
        $this->inventoryItem->update(['quantity' => 3]); // Below reorder level of 5

        $inventoryService = app(InventoryService::class);
        
        // Create movement that should trigger low stock alert
        $movement = $inventoryService->updateStock(
            $this->inventoryItem->id,
            -1, // Further reduce
            'adjustment',
            'Test reduction'
        );

        // Verify alert was created
        $alert = \App\Models\Alert::where('type', 'low_stock')
            ->where('reference_type', 'inventory_item')
            ->where('reference_id', $this->inventoryItem->id)
            ->first();

        $this->assertNotNull($alert);
        $this->assertEquals('medium', $alert->priority);
    }

    public function test_invoice_return_integration()
    {
        $this->actingAs($this->user);

        // First create an invoice
        $invoiceService = app(InvoiceService::class);
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'total_amount' => 200,
        ]);

        // Create invoice items
        $invoice->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'quantity' => 2,
            'unit_price' => 100,
            'total_price' => 200,
        ]);

        // Process return
        $returnItems = [
            [
                'inventory_item_id' => $this->inventoryItem->id,
                'quantity' => 1,
                'total_price' => 100,
            ]
        ];

        $this->integrationService->handleReturn($invoice, $returnItems);

        // Verify inventory was restored
        $this->inventoryItem->refresh();
        $this->assertEquals(11, $this->inventoryItem->quantity); // 10 + 1 returned

        // Verify return accounting entries were created
        $returnTransaction = Transaction::where('type', 'return')
            ->where('source_id', $invoice->id)
            ->first();
        $this->assertNotNull($returnTransaction);
    }
}