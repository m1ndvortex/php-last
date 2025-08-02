<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Invoice;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user and authenticate
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
    }

    public function test_can_list_invoices()
    {
        // Create some test invoices
        $customer = Customer::factory()->create();
        Invoice::factory()->count(3)->create(['customer_id' => $customer->id]);

        $response = $this->getJson('/api/invoices');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'customer_id',
                                'invoice_number',
                                'issue_date',
                                'due_date',
                                'status',
                                'language',
                                'total_amount'
                            ]
                        ]
                    ]
                ]);
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

        $response = $this->postJson('/api/invoices', $invoiceData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'customer_id',
                        'invoice_number',
                        'items',
                        'tags'
                    ]
                ]);

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'language' => 'en'
        ]);
    }

    public function test_can_show_invoice()
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $response = $this->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'customer_id',
                        'invoice_number',
                        'customer',
                        'items',
                        'tags'
                    ]
                ]);
    }

    public function test_can_update_invoice()
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $updateData = [
            'notes' => 'Updated notes',
            'status' => 'sent'
        ];

        $response = $this->putJson("/api/invoices/{$invoice->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'notes' => 'Updated notes',
            'status' => 'sent'
        ]);
    }

    public function test_can_delete_invoice()
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $response = $this->deleteJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $this->assertDatabaseMissing('invoices', [
            'id' => $invoice->id
        ]);
    }
}
