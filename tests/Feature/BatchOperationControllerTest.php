<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\BatchOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class BatchOperationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_get_batch_operations_index()
    {
        // Create some batch operations
        BatchOperation::factory(3)->create([
            'created_by' => $this->user->id
        ]);

        $response = $this->getJson('/api/batch-operations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]);
    }

    public function test_can_get_batch_operation_statistics()
    {
        // Create some batch operations
        BatchOperation::factory(2)->invoiceGeneration()->create([
            'created_by' => $this->user->id
        ]);
        
        BatchOperation::factory(1)->pdfGeneration()->create([
            'created_by' => $this->user->id
        ]);

        $response = $this->getJson('/api/batch-operations/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_operations',
                    'operations_today',
                    'operations_this_week',
                    'operations_this_month',
                    'by_type',
                    'by_status',
                    'recent_operations'
                ]
            ]);
    }

    public function test_can_create_batch_invoices()
    {
        // Create test data
        $customers = Customer::factory(2)->create();
        $inventoryItem = InventoryItem::factory()->create(['quantity' => 100]);

        $requestData = [
            'customer_ids' => $customers->pluck('id')->toArray(),
            'options' => [
                'language' => 'en',
                'due_days' => 30,
                'generate_pdf' => false,
                'send_immediately' => false,
                'items' => [
                    [
                        'inventory_item_id' => $inventoryItem->id,
                        'quantity' => 2,
                        'unit_price' => 100.00
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/batch-operations/invoices', $requestData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'batch_id',
                    'status',
                    'progress',
                    'total_customers'
                ]
            ]);

        // Check that batch operation was created
        $this->assertDatabaseHas('batch_operations', [
            'type' => 'invoice_generation',
            'created_by' => $this->user->id
        ]);
    }

    public function test_can_generate_batch_pdfs()
    {
        // Create test invoices
        $invoices = Invoice::factory(2)->create();

        $requestData = [
            'invoice_ids' => $invoices->pluck('id')->toArray(),
            'options' => [
                'language' => 'en',
                'create_combined_pdf' => false
            ]
        ];

        $response = $this->postJson('/api/batch-operations/pdfs', $requestData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'batch_id',
                    'status',
                    'progress',
                    'total_invoices'
                ]
            ]);

        // Check that batch operation was created
        $this->assertDatabaseHas('batch_operations', [
            'type' => 'pdf_generation',
            'created_by' => $this->user->id
        ]);
    }

    public function test_can_send_batch_communications()
    {
        // Create test invoices with customers
        $customers = Customer::factory(2)->create(['email' => $this->faker->email]);
        $invoices = Invoice::factory(2)->create([
            'customer_id' => function () use ($customers) {
                return $customers->random()->id;
            }
        ]);

        $requestData = [
            'invoice_ids' => $invoices->pluck('id')->toArray(),
            'method' => 'email',
            'options' => [
                'subject' => 'Your Invoice',
                'include_pdf' => true
            ]
        ];

        $response = $this->postJson('/api/batch-operations/communications', $requestData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'batch_id',
                    'status',
                    'progress',
                    'method',
                    'total_invoices'
                ]
            ]);

        // Check that batch operation was created
        $this->assertDatabaseHas('batch_operations', [
            'type' => 'communication_sending',
            'created_by' => $this->user->id
        ]);
    }

    public function test_can_get_specific_batch_operation()
    {
        $batchOperation = BatchOperation::factory()->create([
            'created_by' => $this->user->id
        ]);

        $response = $this->getJson("/api/batch-operations/{$batchOperation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'type',
                    'status',
                    'progress',
                    'created_at',
                    'items'
                ]
            ]);
    }

    public function test_validates_batch_invoice_request()
    {
        $requestData = [
            'customer_ids' => [], // Empty array should fail
            'options' => []
        ];

        $response = $this->postJson('/api/batch-operations/invoices', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_ids']);
    }

    public function test_validates_batch_pdf_request()
    {
        $requestData = [
            'invoice_ids' => [], // Empty array should fail
        ];

        $response = $this->postJson('/api/batch-operations/pdfs', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['invoice_ids']);
    }

    public function test_validates_batch_communication_request()
    {
        $requestData = [
            'invoice_ids' => [1, 2],
            'method' => 'invalid_method', // Invalid method should fail
        ];

        $response = $this->postJson('/api/batch-operations/communications', $requestData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['method']);
    }

    public function test_can_cancel_batch_operation()
    {
        $batchOperation = BatchOperation::factory()->processing()->create([
            'created_by' => $this->user->id
        ]);

        $response = $this->postJson("/api/batch-operations/{$batchOperation->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Batch operation cancelled successfully'
            ]);

        // Check that batch operation was cancelled
        $batchOperation->refresh();
        $this->assertEquals('failed', $batchOperation->status);
        $this->assertEquals('Operation cancelled by user', $batchOperation->error_message);
    }

    public function test_cannot_cancel_completed_batch_operation()
    {
        $batchOperation = BatchOperation::factory()->completed()->create([
            'created_by' => $this->user->id
        ]);

        $response = $this->postJson("/api/batch-operations/{$batchOperation->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot cancel completed batch operation'
            ]);
    }
}