<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\BatchOperation;
use App\Models\BatchOperationItem;
use App\Services\BatchOperationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessBatchOperationJob;

class BatchOperationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected BatchOperationService $batchService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->batchService = app(BatchOperationService::class);
        $this->actingAs($this->user);
    }

    public function test_can_create_batch_invoice_operation()
    {
        // Create test data
        $customers = Customer::factory(3)->create();
        $inventoryItem = InventoryItem::factory()->create(['quantity' => 100]);

        $customerIds = $customers->pluck('id')->toArray();
        $options = [
            'language' => 'en',
            'due_days' => 30,
            'generate_pdf' => true,
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 2,
                    'unit_price' => 100.00
                ]
            ]
        ];

        // Process batch operation
        $batchOperation = $this->batchService->processBatchInvoices($customerIds, $options);

        // Assertions
        $this->assertInstanceOf(BatchOperation::class, $batchOperation);
        $this->assertEquals('invoice_generation', $batchOperation->type);
        $this->assertContains($batchOperation->status, ['completed', 'completed_with_errors']);
        $this->assertEquals(3, $batchOperation->items()->count());
        
        // Check that invoices were created
        $this->assertEquals(3, Invoice::count());
        
        // Check that inventory was updated
        $inventoryItem->refresh();
        $this->assertEquals(94, $inventoryItem->quantity); // 100 - (3 customers * 2 quantity)
    }

    public function test_can_create_batch_pdf_operation()
    {
        // Create test invoices
        $invoices = Invoice::factory(3)->create();
        $invoiceIds = $invoices->pluck('id')->toArray();

        $options = [
            'create_combined_pdf' => true,
            'language' => 'en'
        ];

        // Process batch PDF generation
        $batchOperation = $this->batchService->processBatchPDFGeneration($invoiceIds, $options);

        // Assertions
        $this->assertInstanceOf(BatchOperation::class, $batchOperation);
        $this->assertEquals('pdf_generation', $batchOperation->type);
        $this->assertContains($batchOperation->status, ['completed', 'completed_with_errors']);
        $this->assertEquals(3, $batchOperation->items()->count());
    }

    public function test_can_create_batch_communication_operation()
    {
        // Create test invoices with customers
        $customers = Customer::factory(3)->create(['email' => $this->faker->email]);
        $invoices = Invoice::factory(3)->create([
            'customer_id' => function () use ($customers) {
                return $customers->random()->id;
            }
        ]);
        
        $invoiceIds = $invoices->pluck('id')->toArray();
        $method = 'email';
        $options = [
            'subject' => 'Your Invoice',
            'include_pdf' => true
        ];

        // Process batch communication
        $batchOperation = $this->batchService->processBatchCommunication($invoiceIds, $method, $options);

        // Assertions
        $this->assertInstanceOf(BatchOperation::class, $batchOperation);
        $this->assertEquals('communication_sending', $batchOperation->type);
        $this->assertContains($batchOperation->status, ['completed', 'completed_with_errors']);
        $this->assertEquals(3, $batchOperation->items()->count());
    }

    public function test_batch_operation_handles_errors_gracefully()
    {
        // Create customers with one invalid customer ID
        $customers = Customer::factory(2)->create();
        $customerIds = $customers->pluck('id')->toArray();
        $customerIds[] = 99999; // Non-existent customer ID

        $options = [
            'language' => 'en',
            'items' => []
        ];

        // Process batch operation
        $batchOperation = $this->batchService->processBatchInvoices($customerIds, $options);

        // Assertions
        $this->assertEquals('completed_with_errors', $batchOperation->status);
        $this->assertEquals(3, $batchOperation->items()->count());
        $this->assertEquals(2, $batchOperation->successfulItems()->count());
        $this->assertEquals(1, $batchOperation->failedItems()->count());
        
        // Check summary
        $summary = $batchOperation->summary;
        $this->assertEquals(3, $summary['total_processed']);
        $this->assertEquals(2, $summary['successful']);
        $this->assertEquals(1, $summary['failed']);
    }

    public function test_can_get_batch_operation_status()
    {
        // Create a batch operation
        $batchOperation = BatchOperation::factory()->create([
            'created_by' => $this->user->id
        ]);
        
        BatchOperationItem::factory(3)->create([
            'batch_operation_id' => $batchOperation->id
        ]);

        // Get status
        $status = $this->batchService->getBatchOperationStatus($batchOperation->id);

        // Assertions
        $this->assertIsArray($status);
        $this->assertEquals($batchOperation->id, $status['id']);
        $this->assertEquals($batchOperation->type, $status['type']);
        $this->assertEquals($batchOperation->status, $status['status']);
        $this->assertCount(3, $status['items']);
    }

    public function test_can_get_batch_operation_history()
    {
        // Create multiple batch operations
        BatchOperation::factory(5)->create([
            'created_by' => $this->user->id
        ]);

        $filters = [
            'per_page' => 10
        ];

        // Get history
        $history = $this->batchService->getBatchOperationHistory($filters);

        // Assertions
        $this->assertEquals(5, $history->total());
        $this->assertCount(5, $history->items());
    }

    public function test_can_filter_batch_operation_history()
    {
        // Create batch operations of different types
        BatchOperation::factory(2)->create([
            'type' => 'invoice_generation',
            'created_by' => $this->user->id
        ]);
        
        BatchOperation::factory(3)->create([
            'type' => 'pdf_generation',
            'created_by' => $this->user->id
        ]);

        $filters = [
            'type' => 'invoice_generation',
            'per_page' => 10
        ];

        // Get filtered history
        $history = $this->batchService->getBatchOperationHistory($filters);

        // Assertions
        $this->assertEquals(2, $history->total());
        $this->assertCount(2, $history->items());
    }

    public function test_batch_operation_calculates_progress_correctly()
    {
        $customers = Customer::factory(4)->create();
        $customerIds = $customers->pluck('id')->toArray();
        
        $options = [
            'language' => 'en',
            'items' => []
        ];

        // Process batch operation
        $batchOperation = $this->batchService->processBatchInvoices($customerIds, $options);

        // Check final progress
        $this->assertEquals(100.00, $batchOperation->progress);
        $this->assertEquals(4, $batchOperation->processed_count);
        $this->assertEquals(4, $batchOperation->total_count);
    }

    public function test_batch_operation_creates_proper_audit_trail()
    {
        $customers = Customer::factory(2)->create();
        $customerIds = $customers->pluck('id')->toArray();
        
        $options = [
            'language' => 'en',
            'notes' => 'Test batch operation',
            'items' => []
        ];

        // Process batch operation
        $batchOperation = $this->batchService->processBatchInvoices($customerIds, $options);

        // Check audit trail
        $this->assertEquals($this->user->id, $batchOperation->created_by);
        $this->assertNotNull($batchOperation->started_at);
        $this->assertNotNull($batchOperation->completed_at);
        $this->assertIsArray($batchOperation->metadata);
        $this->assertIsArray($batchOperation->summary);
        
        // Check metadata contains options
        $this->assertEquals($options, $batchOperation->metadata['options']);
        $this->assertEquals(2, $batchOperation->metadata['customer_count']);
    }

    public function test_batch_operation_items_have_correct_data()
    {
        $customer = Customer::factory()->create();
        $inventoryItem = InventoryItem::factory()->create(['quantity' => 10]);
        
        $options = [
            'language' => 'en',
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => 1,
                    'unit_price' => 50.00
                ]
            ]
        ];

        // Process batch operation
        $batchOperation = $this->batchService->processBatchInvoices([$customer->id], $options);

        // Check batch operation item
        $item = $batchOperation->items()->first();
        $this->assertEquals('invoice', $item->reference_type);
        $this->assertEquals($customer->id, $item->customer_id);
        $this->assertEquals('completed', $item->status);
        $this->assertIsArray($item->data);
        $this->assertArrayHasKey('invoice_number', $item->data);
        $this->assertArrayHasKey('total_amount', $item->data);
    }
}