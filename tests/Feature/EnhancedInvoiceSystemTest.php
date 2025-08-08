<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Models\BusinessConfiguration;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EnhancedInvoiceSystemTest extends TestCase
{
    use DatabaseTransactions;

    protected InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = app(InvoiceService::class);
        
        // Create a test user for authentication context
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        
        // Set up basic business configuration
        BusinessConfiguration::setValue('business_name', 'Test Jewelry Store');
        BusinessConfiguration::setValue('tax_rate', 10.0);
        BusinessConfiguration::setValue('currency_symbol', '$');
    }

    public function test_can_create_invoice_with_real_data()
    {
        // Create test data
        $customer = Customer::factory()->create(['preferred_language' => 'en']);
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $inventoryItem = InventoryItem::factory()->create([
            'quantity' => 10,
            'unit_price' => 500.00,
            'category_id' => $category->id,
            'location_id' => $location->id,
            'is_active' => true
        ]);

        $invoiceData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'name' => $inventoryItem->name,
                    'quantity' => 2,
                    'unit_price' => $inventoryItem->unit_price,
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($invoiceData);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($customer->id, $invoice->customer_id);
        $this->assertCount(1, $invoice->items);
        $this->assertEquals(2, $invoice->items->first()->quantity);
        $this->assertEquals(1000.00, $invoice->subtotal);
        $this->assertEquals(100.00, $invoice->tax_amount);
        $this->assertEquals(1100.00, $invoice->total_amount);
    }

    public function test_inventory_is_deducted_when_invoice_created()
    {
        $customer = Customer::factory()->create();
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $inventoryItem = InventoryItem::factory()->create([
            'quantity' => 10, 
            'is_active' => true,
            'location_id' => $location->id,
            'category_id' => $category->id
        ]);
        
        $invoiceData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'name' => $inventoryItem->name,
                    'quantity' => 3,
                    'unit_price' => $inventoryItem->unit_price,
                ]
            ]
        ];

        $this->invoiceService->createInvoice($invoiceData);

        $inventoryItem->refresh();
        $this->assertEquals(7, $inventoryItem->quantity);
    }

    public function test_invoice_numbering_system_works()
    {
        $customer = Customer::factory()->create();
        
        $invoice1 = $this->invoiceService->createInvoice([
            'customer_id' => $customer->id,
            'items' => []
        ]);

        $invoice2 = $this->invoiceService->createInvoice([
            'customer_id' => $customer->id,
            'items' => []
        ]);

        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
        $this->assertStringContainsString(now()->format('Ym'), $invoice1->invoice_number);
    }

    public function test_cannot_create_invoice_with_insufficient_inventory()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $customer = Customer::factory()->create();
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $inventoryItem = InventoryItem::factory()->create([
            'quantity' => 5, 
            'is_active' => true,
            'location_id' => $location->id,
            'category_id' => $category->id
        ]);

        $invoiceData = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'name' => $inventoryItem->name,
                    'quantity' => 10, // More than available (5)
                    'unit_price' => $inventoryItem->unit_price,
                ]
            ]
        ];

        $this->invoiceService->createInvoice($invoiceData);
    }

    public function test_can_mark_invoice_as_paid()
    {
        $invoice = Invoice::factory()->create(['status' => 'sent']);

        $paymentData = [
            'payment_method' => 'cash',
            'amount' => $invoice->total_amount,
            'notes' => 'Paid in full'
        ];

        $paidInvoice = $this->invoiceService->markAsPaid($invoice, $paymentData);

        $this->assertEquals('paid', $paidInvoice->status);
        $this->assertNotNull($paidInvoice->paid_at);
        $this->assertEquals('cash', $paidInvoice->payment_method);
    }

    public function test_can_cancel_invoice_and_restore_inventory()
    {
        $customer = Customer::factory()->create();
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $inventoryItem = InventoryItem::factory()->create([
            'quantity' => 10, 
            'is_active' => true,
            'location_id' => $location->id,
            'category_id' => $category->id
        ]);
        
        $invoice = $this->invoiceService->createInvoice([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'name' => $inventoryItem->name,
                    'quantity' => 2,
                    'unit_price' => $inventoryItem->unit_price,
                ]
            ]
        ]);

        // Verify inventory was deducted
        $inventoryItem->refresh();
        $this->assertEquals(8, $inventoryItem->quantity);

        // Cancel the invoice
        $cancelledInvoice = $this->invoiceService->cancelInvoice($invoice, 'Customer request');

        // Verify status and inventory restoration
        $this->assertEquals('cancelled', $cancelledInvoice->status);
        $inventoryItem->refresh();
        $this->assertEquals(10, $inventoryItem->quantity);
    }

    public function test_overdue_invoices_are_processed_correctly()
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'due_date' => now()->subDays(5),
            'status' => 'sent'
        ]);

        $processed = $this->invoiceService->processOverdueInvoices();

        $this->assertEquals(1, $processed);
        $invoice->refresh();
        $this->assertEquals('overdue', $invoice->status);
    }

    public function test_invoice_uses_business_tax_rate()
    {
        BusinessConfiguration::setValue('tax_rate', 15.0);
        
        $customer = Customer::factory()->create();
        $location = Location::factory()->create();
        $category = Category::factory()->create();
        $inventoryItem = InventoryItem::factory()->create([
            'unit_price' => 100.00, 
            'is_active' => true,
            'location_id' => $location->id,
            'category_id' => $category->id
        ]);

        $invoice = $this->invoiceService->createInvoice([
            'customer_id' => $customer->id,
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'name' => $inventoryItem->name,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                ]
            ]
        ]);

        // Should use 15% tax rate
        $this->assertEquals(15.00, $invoice->tax_amount);
        $this->assertEquals(115.00, $invoice->total_amount);
    }
}