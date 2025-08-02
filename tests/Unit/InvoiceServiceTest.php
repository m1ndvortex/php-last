<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InvoiceService;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoiceItem;
use App\Models\InvoiceTag;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = app(InvoiceService::class);
    }

    public function test_can_create_invoice()
    {
        $customer = Customer::factory()->create();
        
        $invoiceData = [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'language' => 'en',
            'items' => [
                [
                    'name' => 'Gold Ring',
                    'description' => '18k Gold Ring',
                    'quantity' => 1,
                    'unit_price' => 500.00,
                    'gold_purity' => 18.0,
                    'weight' => 5.5,
                ]
            ],
            'tags' => ['jewelry', 'gold']
        ];

        $invoice = $this->invoiceService->createInvoice($invoiceData);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($customer->id, $invoice->customer_id);
        $this->assertEquals('en', $invoice->language);
        $this->assertCount(1, $invoice->items);
        $this->assertCount(2, $invoice->tags);
        $this->assertEquals(500.00, $invoice->subtotal);
    }

    public function test_can_update_invoice()
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        
        $updateData = [
            'notes' => 'Updated notes',
            'status' => 'sent',
            'items' => [
                [
                    'name' => 'Updated Item',
                    'quantity' => 2,
                    'unit_price' => 100.00,
                ]
            ]
        ];

        $updatedInvoice = $this->invoiceService->updateInvoice($invoice, $updateData);

        $this->assertEquals('Updated notes', $updatedInvoice->notes);
        $this->assertEquals('sent', $updatedInvoice->status);
        $this->assertCount(1, $updatedInvoice->items);
        $this->assertEquals('Updated Item', $updatedInvoice->items->first()->name);
    }

    public function test_can_calculate_invoice_totals()
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'discount_amount' => 0.00
        ]);
        
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'total_price' => 200.00,
        ]);
        
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => 300.00,
            'total_price' => 300.00,
        ]);

        $this->invoiceService->calculateInvoiceTotals($invoice);

        $this->assertEquals(500.00, $invoice->subtotal);
        $this->assertEquals(45.00, $invoice->tax_amount); // 9% tax
        $this->assertEquals(545.00, $invoice->total_amount);
    }

    public function test_can_duplicate_invoice()
    {
        $customer = Customer::factory()->create();
        $originalInvoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        
        InvoiceItem::factory()->create(['invoice_id' => $originalInvoice->id]);
        InvoiceTag::factory()->create(['invoice_id' => $originalInvoice->id, 'tag' => 'original']);

        $duplicatedInvoice = $this->invoiceService->duplicateInvoice($originalInvoice);

        $this->assertNotEquals($originalInvoice->id, $duplicatedInvoice->id);
        $this->assertEquals($originalInvoice->customer_id, $duplicatedInvoice->customer_id);
        $this->assertEquals('draft', $duplicatedInvoice->status);
        $this->assertCount(1, $duplicatedInvoice->items);
        $this->assertCount(1, $duplicatedInvoice->tags);
    }

    public function test_can_mark_invoice_as_sent()
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $updatedInvoice = $this->invoiceService->markAsSent($invoice);

        $this->assertEquals('sent', $updatedInvoice->status);
        $this->assertNotNull($updatedInvoice->sent_at);
    }

    public function test_can_mark_invoice_as_paid()
    {
        $invoice = Invoice::factory()->create(['status' => 'sent']);

        $updatedInvoice = $this->invoiceService->markAsPaid($invoice);

        $this->assertEquals('paid', $updatedInvoice->status);
        $this->assertNotNull($updatedInvoice->paid_at);
    }

    public function test_generates_unique_invoice_number()
    {
        $invoiceNumber = Invoice::generateInvoiceNumber();
        
        $this->assertStringStartsWith('INV-', $invoiceNumber);
        $this->assertEquals(10, strlen($invoiceNumber)); // INV- + 6 digits
    }
}
