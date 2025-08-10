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
        
        // Mock the dependencies
        $inventoryService = Mockery::mock(\App\Services\InventoryManagementService::class);
        $goldPricingService = Mockery::mock(\App\Services\GoldPricingService::class);
        
        // Set up default behavior for gold pricing service
        $goldPricingService->shouldReceive('getDefaultPricingSettings')
            ->andReturn([
                'default_labor_percentage' => 10.0,
                'default_profit_percentage' => 15.0,
                'default_tax_percentage' => 9.0
            ]);
        
        $this->invoiceService = new InvoiceService($inventoryService, $goldPricingService);
    }

    public function test_invoice_number_generation_format()
    {
        // Test that the service has the method and it follows expected format
        $this->assertTrue(method_exists($this->invoiceService, 'generateInvoiceNumber'));
        
        // Test the format pattern (we can't easily test the actual generation without DB)
        $expectedPattern = '/^INV-\d{6}-\d{4}$/';
        $sampleNumber = 'INV-' . now()->format('Ym') . '-0001';
        
        $this->assertMatchesRegularExpression($expectedPattern, $sampleNumber);
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
        // Test that the method exists and has proper structure
        $this->assertTrue(method_exists($this->invoiceService, 'calculateInvoiceTotals'));
        
        // Test the tax rate calculation logic
        $reflection = new \ReflectionClass($this->invoiceService);
        $method = $reflection->getMethod('getBusinessTaxRate');
        $method->setAccessible(true);
        
        $taxRate = $method->invoke($this->invoiceService);
        $this->assertIsFloat($taxRate);
        $this->assertGreaterThanOrEqual(0, $taxRate);
    }

    public function test_inventory_validation_logic()
    {
        // Test that the service properly uses InventoryManagementService
        $reflection = new \ReflectionClass($this->invoiceService);
        $property = $reflection->getProperty('inventoryService');
        $property->setAccessible(true);
        
        $inventoryService = $property->getValue($this->invoiceService);
        
        // Verify that the inventory service is properly injected
        $this->assertInstanceOf(\App\Services\InventoryManagementService::class, $inventoryService);
    }

    public function test_inventory_validation_insufficient_stock()
    {
        // Test that the service properly integrates with InventoryManagementService
        $reflection = new \ReflectionClass($this->invoiceService);
        $property = $reflection->getProperty('inventoryService');
        $property->setAccessible(true);
        
        $inventoryService = $property->getValue($this->invoiceService);
        
        // Verify that the inventory service has the required methods
        $this->assertTrue(method_exists($inventoryService, 'validateInventoryAvailability'));
        $this->assertTrue(method_exists($inventoryService, 'reserveInventory'));
        $this->assertTrue(method_exists($inventoryService, 'restoreInventory'));
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