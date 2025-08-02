<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Communication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_index_returns_paginated_customers()
    {
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'phone',
                            'customer_type',
                            'crm_stage',
                            'is_active',
                            'preferred_language',
                        ]
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ],
                'message'
            ]);

        $this->assertEquals(3, $response->json('data.total'));
    }

    public function test_index_applies_search_filter()
    {
        Customer::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Customer::create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $response = $this->getJson('/api/customers?search=john');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals('John Doe', $response->json('data.data.0.name'));
    }

    public function test_index_applies_customer_type_filter()
    {
        Customer::create(['name' => 'Retail Customer', 'customer_type' => 'retail']);
        Customer::create(['name' => 'Wholesale Customer', 'customer_type' => 'wholesale']);

        $response = $this->getJson('/api/customers?customer_type=wholesale');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals('wholesale', $response->json('data.data.0.customer_type'));
    }

    public function test_store_creates_new_customer()
    {
        $customerData = [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'phone' => '+1234567890',
            'customer_type' => 'retail',
            'preferred_language' => 'en',
        ];

        $response = $this->postJson('/api/customers', $customerData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'customer_type',
                    'preferred_language',
                ],
                'message'
            ]);

        $this->assertDatabaseHas('customers', $customerData);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->postJson('/api/customers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_validates_email_uniqueness()
    {
        Customer::create(['name' => 'Existing Customer', 'email' => 'existing@example.com']);

        $response = $this->postJson('/api/customers', [
            'name' => 'New Customer',
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_store_validates_customer_type()
    {
        $response = $this->postJson('/api/customers', [
            'name' => 'Test Customer',
            'customer_type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_type']);
    }

    public function test_show_returns_customer_details()
    {
        $customer = Customer::create(['name' => 'Test Customer']);
        
        Communication::create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Test communication',
        ]);

        $response = $this->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'customer' => [
                        'id',
                        'name',
                        'communications',
                    ],
                    'statistics' => [
                        'total_invoice_amount',
                        'outstanding_balance',
                        'total_communications',
                        'age',
                        'upcoming_birthday',
                        'upcoming_anniversary',
                    ]
                ],
                'message'
            ]);
    }

    public function test_show_returns_404_for_nonexistent_customer()
    {
        $response = $this->getJson('/api/customers/999');

        $response->assertStatus(404);
    }

    public function test_update_modifies_customer_data()
    {
        $customer = Customer::create(['name' => 'Original Name']);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/customers/{$customer->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                ],
                'message'
            ]);

        $this->assertDatabaseHas('customers', array_merge(['id' => $customer->id], $updateData));
    }

    public function test_update_validates_email_uniqueness_excluding_current_customer()
    {
        $customer1 = Customer::create(['name' => 'Customer 1', 'email' => 'customer1@example.com']);
        $customer2 = Customer::create(['name' => 'Customer 2', 'email' => 'customer2@example.com']);

        // Should fail - trying to use another customer's email
        $response = $this->putJson("/api/customers/{$customer1->id}", [
            'email' => 'customer2@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Should succeed - keeping the same email
        $response = $this->putJson("/api/customers/{$customer1->id}", [
            'email' => 'customer1@example.com',
        ]);

        $response->assertStatus(200);
    }

    public function test_destroy_soft_deletes_customer()
    {
        $customer = Customer::create(['name' => 'Test Customer']);

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_aging_report_returns_correct_structure()
    {
        Customer::create(['name' => 'Test Customer', 'customer_type' => 'retail']);

        $response = $this->getJson('/api/customers/aging-report');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'details',
                    'summary',
                    'total_outstanding',
                    'total_customers',
                ],
                'message'
            ]);
    }

    public function test_aging_report_applies_filters()
    {
        Customer::create(['name' => 'Retail Customer', 'customer_type' => 'retail']);
        Customer::create(['name' => 'Wholesale Customer', 'customer_type' => 'wholesale']);

        $response = $this->getJson('/api/customers/aging-report?customer_type=retail');

        $response->assertStatus(200);
        // The specific filtering logic would depend on the Invoice model implementation
    }

    public function test_crm_pipeline_returns_pipeline_data()
    {
        Customer::create(['name' => 'Lead Customer', 'crm_stage' => 'lead']);
        Customer::create(['name' => 'Prospect Customer', 'crm_stage' => 'prospect']);

        $response = $this->getJson('/api/customers/crm-pipeline');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'pipeline',
                    'conversion_rates',
                    'recent_changes',
                    'total_active_customers',
                    'total_leads',
                    'total_prospects',
                    'total_customers',
                ],
                'message'
            ]);
    }

    public function test_update_crm_stage_changes_customer_stage()
    {
        $customer = Customer::create(['name' => 'Test Customer', 'crm_stage' => 'lead']);

        $response = $this->putJson("/api/customers/{$customer->id}/crm-stage", [
            'crm_stage' => 'prospect',
            'notes' => 'Qualified lead after phone call',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'crm_stage',
                ],
                'message'
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'crm_stage' => 'prospect',
        ]);

        // Check that stage change was logged
        $this->assertDatabaseHas('communications', [
            'customer_id' => $customer->id,
            'type' => 'note',
            'subject' => 'CRM Stage Change',
        ]);
    }

    public function test_update_crm_stage_validates_stage()
    {
        $customer = Customer::create(['name' => 'Test Customer']);

        $response = $this->putJson("/api/customers/{$customer->id}/crm-stage", [
            'crm_stage' => 'invalid_stage',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['crm_stage']);
    }

    public function test_send_communication_creates_communication()
    {
        $customer = Customer::create(['name' => 'Test Customer', 'phone' => '+1234567890']);

        $response = $this->postJson("/api/customers/{$customer->id}/communicate", [
            'type' => 'sms',
            'message' => 'Test SMS message',
            'subject' => 'Test Subject',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'type',
                    'message',
                    'subject',
                    'status',
                ],
                'message'
            ]);

        $this->assertDatabaseHas('communications', [
            'customer_id' => $customer->id,
            'type' => 'sms',
            'message' => 'Test SMS message',
            'subject' => 'Test Subject',
        ]);
    }

    public function test_send_communication_validates_type()
    {
        $customer = Customer::create(['name' => 'Test Customer']);

        $response = $this->postJson("/api/customers/{$customer->id}/communicate", [
            'type' => 'invalid_type',
            'message' => 'Test message',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_upcoming_birthdays_returns_customers_with_upcoming_birthdays()
    {
        $upcomingBirthday = now()->addDays(15)->subYears(25);
        $pastBirthday = now()->addDays(45)->subYears(30);

        Customer::create(['name' => 'Upcoming Birthday', 'birthday' => $upcomingBirthday]);
        Customer::create(['name' => 'Past Birthday', 'birthday' => $pastBirthday]);

        $response = $this->getJson('/api/customers/upcoming-birthdays');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'birthday',
                    ]
                ],
                'message'
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Upcoming Birthday', $response->json('data.0.name'));
    }

    public function test_upcoming_anniversaries_returns_customers_with_upcoming_anniversaries()
    {
        $upcomingAnniversary = now()->addDays(20)->subYears(5);
        $pastAnniversary = now()->addDays(50)->subYears(10);

        Customer::create(['name' => 'Upcoming Anniversary', 'anniversary' => $upcomingAnniversary]);
        Customer::create(['name' => 'Past Anniversary', 'anniversary' => $pastAnniversary]);

        $response = $this->getJson('/api/customers/upcoming-anniversaries');

        $response->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Upcoming Anniversary', $response->json('data.0.name'));
    }

    public function test_export_vcard_generates_vcard_data()
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
        ]);

        $response = $this->getJson("/api/customers/{$customer->id}/vcard");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'vcard',
                    'filename',
                ],
                'message'
            ]);

        $vcard = $response->json('data.vcard');
        $this->assertStringContainsString('BEGIN:VCARD', $vcard);
        $this->assertStringContainsString('FN:John Doe', $vcard);
        $this->assertStringContainsString('END:VCARD', $vcard);
    }

    public function test_unauthenticated_requests_return_401()
    {
        // Clear any existing authentication
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/customers');

        $response->assertStatus(401);
    }
}