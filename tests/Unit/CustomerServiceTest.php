<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Communication;
use App\Models\User;
use App\Services\CustomerService;
use App\Services\CommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;

class CustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerService $customerService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $communicationService = Mockery::mock(CommunicationService::class);
        $this->customerService = new CustomerService($communicationService);
    }

    public function test_get_customers_returns_paginated_results()
    {
        Customer::factory()->count(5)->create();

        $result = $this->customerService->getCustomers();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());
    }

    public function test_get_customers_applies_search_filter()
    {
        Customer::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Customer::create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $result = $this->customerService->getCustomers(['search' => 'john']);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('John Doe', $result->items()[0]->name);
    }

    public function test_get_customers_applies_customer_type_filter()
    {
        Customer::create(['name' => 'Retail Customer', 'customer_type' => 'retail']);
        Customer::create(['name' => 'Wholesale Customer', 'customer_type' => 'wholesale']);

        $result = $this->customerService->getCustomers(['customer_type' => 'wholesale']);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('wholesale', $result->items()[0]->customer_type);
    }

    public function test_get_customers_applies_crm_stage_filter()
    {
        Customer::create(['name' => 'Lead Customer', 'crm_stage' => 'lead']);
        Customer::create(['name' => 'Prospect Customer', 'crm_stage' => 'prospect']);

        $result = $this->customerService->getCustomers(['crm_stage' => 'prospect']);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('prospect', $result->items()[0]->crm_stage);
    }

    public function test_get_customers_applies_active_status_filter()
    {
        Customer::create(['name' => 'Active Customer', 'is_active' => true]);
        Customer::create(['name' => 'Inactive Customer', 'is_active' => false]);

        $result = $this->customerService->getCustomers(['is_active' => false]);

        $this->assertEquals(1, $result->total());
        $this->assertFalse($result->items()[0]->is_active);
    }

    public function test_get_customers_applies_preferred_language_filter()
    {
        Customer::create(['name' => 'English Customer', 'preferred_language' => 'en']);
        Customer::create(['name' => 'Persian Customer', 'preferred_language' => 'fa']);

        $result = $this->customerService->getCustomers(['preferred_language' => 'fa']);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('fa', $result->items()[0]->preferred_language);
    }

    public function test_get_customers_applies_sorting()
    {
        Customer::create(['name' => 'B Customer']);
        Customer::create(['name' => 'A Customer']);

        $result = $this->customerService->getCustomers([
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);

        $this->assertEquals('A Customer', $result->items()[0]->name);
        $this->assertEquals('B Customer', $result->items()[1]->name);
    }

    public function test_create_customer_creates_new_customer()
    {
        $customerData = [
            'name' => 'New Customer',
            'email' => 'new@example.com',
            'phone' => '+1234567890',
            'customer_type' => 'retail',
        ];

        $customer = $this->customerService->createCustomer($customerData);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('New Customer', $customer->name);
        $this->assertEquals('new@example.com', $customer->email);
        $this->assertDatabaseHas('customers', $customerData);
    }

    public function test_create_customer_logs_activity()
    {
        $customerData = ['name' => 'Test Customer'];

        $customer = $this->customerService->createCustomer($customerData);

        $this->assertDatabaseHas('communications', [
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Customer created',
        ]);
    }

    public function test_get_customer_details_returns_detailed_information()
    {
        $customer = Customer::create(['name' => 'Test Customer']);
        
        Communication::create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Test communication',
        ]);

        $result = $this->customerService->getCustomerDetails($customer);

        $this->assertArrayHasKey('customer', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertInstanceOf(Customer::class, $result['customer']);
        $this->assertIsArray($result['statistics']);
        $this->assertArrayHasKey('total_communications', $result['statistics']);
    }

    public function test_update_customer_updates_customer_data()
    {
        $customer = Customer::create(['name' => 'Original Name']);

        $updateData = ['name' => 'Updated Name', 'email' => 'updated@example.com'];
        $updatedCustomer = $this->customerService->updateCustomer($customer, $updateData);

        $this->assertEquals('Updated Name', $updatedCustomer->name);
        $this->assertEquals('updated@example.com', $updatedCustomer->email);
        $this->assertDatabaseHas('customers', $updateData);
    }

    public function test_update_customer_logs_significant_changes()
    {
        $customer = Customer::create(['name' => 'Original Name', 'customer_type' => 'retail']);

        $updateData = ['name' => 'New Name', 'customer_type' => 'wholesale'];
        $this->customerService->updateCustomer($customer, $updateData);

        $this->assertDatabaseHas('communications', [
            'customer_id' => $customer->id,
            'type' => 'note',
        ]);

        $communication = Communication::where('customer_id', $customer->id)
            ->where('message', 'like', 'Customer updated:%')
            ->first();

        $this->assertNotNull($communication);
        $this->assertStringContainsString('name: Original Name → New Name', $communication->message);
        $this->assertStringContainsString('customer_type: retail → wholesale', $communication->message);
    }

    public function test_delete_customer_soft_deletes_customer()
    {
        $customer = Customer::create(['name' => 'Test Customer']);

        $result = $this->customerService->deleteCustomer($customer);

        $this->assertTrue($result);
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_delete_customer_logs_deletion()
    {
        $customer = Customer::create(['name' => 'Test Customer']);

        $this->customerService->deleteCustomer($customer);

        $this->assertDatabaseHas('communications', [
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Customer deleted',
        ]);
    }

    public function test_get_customers_with_upcoming_birthdays()
    {
        $upcomingBirthday = now()->addDays(15)->subYears(25);
        $pastBirthday = now()->addDays(45)->subYears(30);

        Customer::create(['name' => 'Upcoming Birthday', 'birthday' => $upcomingBirthday]);
        Customer::create(['name' => 'Past Birthday', 'birthday' => $pastBirthday]);

        $customers = $this->customerService->getCustomersWithUpcomingBirthdays();

        $this->assertCount(1, $customers);
        $this->assertEquals('Upcoming Birthday', $customers->first()->name);
    }

    public function test_get_customers_with_upcoming_anniversaries()
    {
        $upcomingAnniversary = now()->addDays(20)->subYears(5);
        $pastAnniversary = now()->addDays(50)->subYears(10);

        Customer::create(['name' => 'Upcoming Anniversary', 'anniversary' => $upcomingAnniversary]);
        Customer::create(['name' => 'Past Anniversary', 'anniversary' => $pastAnniversary]);

        $customers = $this->customerService->getCustomersWithUpcomingAnniversaries();

        $this->assertCount(1, $customers);
        $this->assertEquals('Upcoming Anniversary', $customers->first()->name);
    }

    public function test_generate_vcard_creates_valid_vcard()
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St, City, State',
            'birthday' => '1990-05-15',
        ]);

        $vcard = $this->customerService->generateVCard($customer);

        $this->assertStringContainsString('BEGIN:VCARD', $vcard);
        $this->assertStringContainsString('END:VCARD', $vcard);
        $this->assertStringContainsString('FN:John Doe', $vcard);
        $this->assertStringContainsString('EMAIL:john@example.com', $vcard);
        $this->assertStringContainsString('TEL:+1234567890', $vcard);
        $this->assertStringContainsString('ADR:;;123 Main St, City, State;;;;', $vcard);
        $this->assertStringContainsString('BDAY:1990-05-15', $vcard);
    }

    public function test_get_aging_report_returns_correct_structure()
    {
        // This test would require Invoice model to be implemented
        // For now, we'll test the basic structure
        $result = $this->customerService->getAgingReport();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('total_outstanding', $result);
        $this->assertArrayHasKey('total_customers', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}