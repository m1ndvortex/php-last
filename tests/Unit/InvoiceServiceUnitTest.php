<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Tests\TestCase;
use Mockery;

class InvoiceServiceUnitTest extends TestCase
{
    protected InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = new InvoiceService();
    }

    public function test_invoice_number_generation_format()
    {
        // Test the invoice number format using reflection to access protected method
        $reflection = new \ReflectionClass($this->invoiceService);
        $method = $reflection->getMethod('generateInvoiceNumber');
        $method->setAccessible(true);

        $invoiceNumber = $method->invoke($this->invoiceService);

        // Should follow format: INV-YYYYMM-XXXX
        $this->assertMatchesRegularExpression('/^INV-\d{6}-\d{4}$/', $invoiceNumber);
        $this->assertStringContainsString(now()->format('Ym'), $invoiceNumber);
    }

    public function test_business_tax_rate_retrieval()
    {
        // Test tax rate retrieval using reflection
        $reflection = new \ReflectionClass($this->invoiceService);
        $method = $reflection->getMethod('getBusinessTaxRate');
        $method->setAccessible(true);

        $taxRate = $method->invoke($this->invoiceService);

        // Should return a numeric value (default 9.0 if no config)
        $this->assertIsFloat($taxRate);
        $this->assertGreaterThanOrEqual(0, $taxRate);
    }

    public function test_invoice_totals_calculation()
    {
        // Create a mock invoice with items
        $invoice = Mockery::mock(Invoice::class);
        
        $invoice->shouldReceive('items->sum')->with('total_price')->andReturn(450.00);
        $invoice->shouldReceive('offsetExists')->with('discount_amount')->andReturn(true);
        $invoice->shouldReceive('offsetGet')->with('discount_amount')->andReturn(50.00);
        $invoice->shouldReceive('update')->once()->with([
            'subtotal' => 450.00,
            'tax_amount' => 36.00, // 9% of (450 - 50)
            'total_amount' => 436.00,
        ]);

        $result = $this->invoiceService->calculateInvoiceTotals($invoice);

        $this->assertSame($invoice, $result);
    }

    public function test_inventory_validation_logic()
    {
        // Test validation logic using reflection
        $reflection = new \ReflectionClass($this->invoiceService);
        $method = $reflection->getMethod('validateInventoryAvailability');
        $method->setAccessible(true);

        // Mock inventory item
        $inventoryItem = Mockery::mock(\App\Models\InventoryItem::class);
        $inventoryItem->name = 'Test Item';
        $inventoryItem->is_active = true;
        $inventoryItem->quantity = 10;

        // Mock the InventoryItem model static method
        Mockery::mock('alias:App\Models\InventoryItem')
            ->shouldReceive('find')
            ->with(1)
            ->andReturn($inventoryItem);

        $items = [
            [
                'inventory_item_id' => 1,
                'quantity' => 5, // Less than available (10)
            ]
        ];

        // Should not throw exception
        $method->invoke($this->invoiceService, $items);
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function test_inventory_validation_insufficient_stock()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        // Test validation logic using reflection
        $reflection = new \ReflectionClass($this->invoiceService);
        $method = $reflection->getMethod('validateInventoryAvailability');
        $method->setAccessible(true);

        // Mock inventory item with insufficient stock
        $inventoryItem = Mockery::mock(\App\Models\InventoryItem::class);
        $inventoryItem->name = 'Test Item';
        $inventoryItem->is_active = true;
        $inventoryItem->quantity = 3;

        // Mock the InventoryItem model static method
        Mockery::mock('alias:App\Models\InventoryItem')
            ->shouldReceive('find')
            ->with(1)
            ->andReturn($inventoryItem);

        $items = [
            [
                'inventory_item_id' => 1,
                'quantity' => 5, // More than available (3)
            ]
        ];

        $method->invoke($this->invoiceService, $items);
    }

    public function test_invoice_status_tracking_methods_exist()
    {
        // Test that all required methods exist
        $this->assertTrue(method_exists($this->invoiceService, 'markAsSent'));
        $this->assertTrue(method_exists($this->invoiceService, 'markAsPaid'));
        $this->assertTrue(method_exists($this->invoiceService, 'markAsOverdue'));
        $this->assertTrue(method_exists($this->invoiceService, 'cancelInvoice'));
        $this->assertTrue(method_exists($this->invoiceService, 'processOverdueInvoices'));
    }

    public function test_error_handling_structure()
    {
        // Test that error handling is properly structured
        $reflection = new \ReflectionClass($this->invoiceService);
        $method = $reflection->getMethod('logInvoiceActivity');
        $method->setAccessible(true);

        // Mock invoice
        $invoice = Mockery::mock(Invoice::class);
        $invoice->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $invoice->shouldReceive('getAttribute')->with('invoice_number')->andReturn('INV-202508-0001');

        // Should not throw exception and return log data
        $result = $method->invoke($this->invoiceService, $invoice, 'test', 'Test description', []);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('invoice_id', $result);
        $this->assertArrayHasKey('action', $result);
        $this->assertArrayHasKey('description', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}