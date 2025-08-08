<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BatchOperationService;
use App\Services\InvoiceService;
use App\Services\PDFGenerationService;
use App\Services\CommunicationService;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\BatchOperation;
use App\Models\BatchOperationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class BatchOperationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BatchOperationService $service;
    protected $mockInvoiceService;
    protected $mockPDFService;
    protected $mockCommunicationService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create mocks
        $this->mockInvoiceService = Mockery::mock(InvoiceService::class);
        $this->mockPDFService = Mockery::mock(PDFGenerationService::class);
        $this->mockCommunicationService = Mockery::mock(CommunicationService::class);

        // Create service with mocked dependencies
        $this->service = new BatchOperationService(
            $this->mockInvoiceService,
            $this->mockPDFService,
            $this->mockCommunicationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_creates_batch_operation_record()
    {
        $customers = Customer::factory(2)->create();
        $customerIds = $customers->pluck('id')->toArray();
        
        // Mock invoice creation
        $this->mockInvoiceService
            ->shouldReceive('createInvoice')
            ->times(2)
            ->andReturn(Invoice::factory()->make(['id' => 1, 'invoice_number' => 'INV-001', 'total_amount' => 100.00]));

        $options = ['language' => 'en'];

        $result = $this->service->processBatchInvoices($customerIds, $options);

        $this->assertInstanceOf(BatchOperation::class, $result);
        $this->assertEquals('invoice_generation', $result->type);
        $this->assertEquals($this->user->id, $result->created_by);
        $this->assertNotNull($result->started_at);
    }

    public function test_processes_all_customers_in_batch()
    {
        $customers = Customer::factory(3)->create();
        $customerIds = $customers->pluck('id')->toArray();
        
        // Mock invoice creation for each customer
        $this->mockInvoiceService
            ->shouldReceive('createInvoice')
            ->times(3)
            ->andReturn(Invoice::factory()->make(['id' => 1, 'invoice_number' => 'INV-001', 'total_amount' => 100.00]));

        $options = ['language' => 'en'];

        $result = $this->service->processBatchInvoices($customerIds, $options);

        $this->assertEquals(3, $result->items()->count());
        $this->assertEquals(3, $result->processed_count);
        $this->assertEquals(3, $result->total_count);
    }

    public function test_handles_individual_failures_gracefully()
    {
        $customers = Customer::factory(2)->create();
        $customerIds = $customers->pluck('id')->toArray();
        
        // Mock first invoice creation to succeed, second to fail
        $this->mockInvoiceService
            ->shouldReceive('createInvoice')
            ->once()
            ->andReturn(Invoice::factory()->make(['id' => 1, 'invoice_number' => 'INV-001', 'total_amount' => 100.00]));
            
        $this->mockInvoiceService
            ->shouldReceive('createInvoice')
            ->once()
            ->andThrow(new \Exception('Invoice creation failed'));

        $options = ['language' => 'en'];

        $result = $this->service->processBatchInvoices($customerIds, $options);

        $this->assertEquals('completed_with_errors', $result->status);
        $this->assertEquals(1, $result->successfulItems()->count());
        $this->assertEquals(1, $result->failedItems()->count());
    }

    public function test_calculates_progress_correctly()
    {
        $customers = Customer::factory(4)->create();
        $customerIds = $customers->pluck('id')->toArray();
        
        $this->mockInvoiceService
            ->shouldReceive('createInvoice')
            ->times(4)
            ->andReturn(Invoice::factory()->make(['id' => 1, 'invoice_number' => 'INV-001', 'total_amount' => 100.00]));

        $options = ['language' => 'en'];

        $result = $this->service->processBatchInvoices($customerIds, $options);

        $this->assertEquals(100.00, $result->progress);
        $this->assertEquals(4, $result->processed_count);
        $this->assertEquals(4, $result->total_count);
    }

    public function test_generates_pdfs_when_requested()
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->make(['id' => 1, 'invoice_number' => 'INV-001', 'total_amount' => 100.00]);
        
        $this->mockInvoiceService
            ->shouldReceive('createInvoice')
            ->once()
            ->andReturn($invoice);

        $this->mockPDFService
            ->shouldReceive('generateInvoicePDF')
            ->once()
            ->with($invoice)
            ->andReturn('path/to/pdf');

        $options = [
            'language' => 'en',
            'generate_pdf' => true
        ];

        $result = $this->service->processBatchInvoices([$customer->id], $options);

        $this->assertEquals('completed', $result->status);
        $item = $result->items()->first();
        $this->assertArrayHasKey('pdf_generated_at', $item->data);
    }

    public function test_sends_communications_when_requested()
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->make(['id' => 1, 'invoice_number' => 'INV-001', 'total_amount' => 100.00]);
        
        $this->mockInvoiceService
            ->shouldReceive('createInvoice')
            ->once()
            ->andReturn($invoice);

        $this->mockCommunicationService
            ->shouldReceive('sendInvoiceEmail')
            ->once()
            ->with($invoice, Mockery::any())
            ->andReturn(['recipient' => 'test@example.com']);

        $options = [
            'language' => 'en',
            'send_immediately' => true,
            'communication_method' => 'email'
        ];

        $result = $this->service->processBatchInvoices([$customer->id], $options);

        $this->assertEquals('completed', $result->status);
        $item = $result->items()->first();
        $this->assertArrayHasKey('communication_sent', $item->data);
        $this->assertTrue($item->data['communication_sent']);
    }

    public function test_processes_batch_pdf_generation()
    {
        $invoices = Invoice::factory(2)->create();
        $invoiceIds = $invoices->pluck('id')->toArray();

        $this->mockPDFService
            ->shouldReceive('generateInvoicePDF')
            ->times(2)
            ->andReturn('path/to/pdf');

        $options = ['language' => 'en'];

        $result = $this->service->processBatchPDFGeneration($invoiceIds, $options);

        $this->assertInstanceOf(BatchOperation::class, $result);
        $this->assertEquals('pdf_generation', $result->type);
        $this->assertEquals(2, $result->items()->count());
    }

    public function test_processes_batch_communication_sending()
    {
        $invoices = Invoice::factory(2)->create();
        $invoiceIds = $invoices->pluck('id')->toArray();

        $this->mockCommunicationService
            ->shouldReceive('sendInvoiceEmail')
            ->times(2)
            ->andReturn(['recipient' => 'test@example.com']);

        $method = 'email';
        $options = ['subject' => 'Test Subject'];

        $result = $this->service->processBatchCommunication($invoiceIds, $method, $options);

        $this->assertInstanceOf(BatchOperation::class, $result);
        $this->assertEquals('communication_sending', $result->type);
        $this->assertEquals(2, $result->items()->count());
    }

    public function test_gets_batch_operation_status()
    {
        $batchOperation = BatchOperation::factory()->create([
            'created_by' => $this->user->id
        ]);
        
        BatchOperationItem::factory(2)->create([
            'batch_operation_id' => $batchOperation->id
        ]);

        $status = $this->service->getBatchOperationStatus($batchOperation->id);

        $this->assertIsArray($status);
        $this->assertEquals($batchOperation->id, $status['id']);
        $this->assertEquals($batchOperation->type, $status['type']);
        $this->assertCount(2, $status['items']);
    }

    public function test_gets_batch_operation_history_with_filters()
    {
        // Create operations of different types
        BatchOperation::factory(2)->create([
            'type' => 'invoice_generation',
            'created_by' => $this->user->id
        ]);
        
        BatchOperation::factory(1)->create([
            'type' => 'pdf_generation',
            'created_by' => $this->user->id
        ]);

        $filters = ['type' => 'invoice_generation'];

        $history = $this->service->getBatchOperationHistory($filters);

        $this->assertEquals(2, $history->total());
    }

    public function test_stores_correct_metadata()
    {
        $customer = Customer::factory()->create();
        $options = [
            'language' => 'fa',
            'due_days' => 45,
            'notes' => 'Test notes'
        ];

        $this->mockInvoiceService
            ->shouldReceive('createInvoice')
            ->once()
            ->andReturn(Invoice::factory()->make(['id' => 1, 'invoice_number' => 'INV-001', 'total_amount' => 100.00]));

        $result = $this->service->processBatchInvoices([$customer->id], $options);

        $metadata = $result->metadata;
        $this->assertEquals(1, $metadata['customer_count']);
        $this->assertEquals($options, $metadata['options']);
    }

    public function test_creates_proper_summary_on_completion()
    {
        $customers = Customer::factory(3)->create();
        $customerIds = $customers->pluck('id')->toArray();

        // Mock 2 successful, 1 failed
        $this->mockInvoiceService
            ->shouldReceive('createInvoice')
            ->times(2)
            ->andReturn(Invoice::factory()->make(['id' => 1, 'invoice_number' => 'INV-001', 'total_amount' => 100.00]));
            
        $this->mockInvoiceService
            ->shouldReceive('createInvoice')
            ->once()
            ->andThrow(new \Exception('Failed'));

        $options = ['language' => 'en'];

        $result = $this->service->processBatchInvoices($customerIds, $options);

        $summary = $result->summary;
        $this->assertEquals(3, $summary['total_processed']);
        $this->assertEquals(2, $summary['successful']);
        $this->assertEquals(1, $summary['failed']);
        $this->assertEquals(66.67, $summary['success_rate']);
    }
}