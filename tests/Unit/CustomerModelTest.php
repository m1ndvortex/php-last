<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Communication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CustomerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_created_with_required_fields()
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('John Doe', $customer->name);
        $this->assertEquals('john@example.com', $customer->email);
        $this->assertEquals('+1234567890', $customer->phone);
        $this->assertEquals('en', $customer->preferred_language);
        $this->assertEquals('retail', $customer->customer_type);
        $this->assertTrue($customer->is_active);
    }

    public function test_customer_has_default_values()
    {
        $customer = Customer::create(['name' => 'Test Customer']);

        $this->assertEquals('en', $customer->preferred_language);
        $this->assertEquals('retail', $customer->customer_type);
        $this->assertEquals(0.00, $customer->credit_limit);
        $this->assertEquals(30, $customer->payment_terms);
        $this->assertTrue($customer->is_active);
        $this->assertEquals('lead', $customer->crm_stage);
    }

    public function test_customer_casts_attributes_correctly()
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'credit_limit' => '1500.50',
            'payment_terms' => '45',
            'is_active' => '1',
            'birthday' => '1990-05-15',
            'tags' => ['vip', 'wholesale'],
        ]);

        $this->assertIsString($customer->credit_limit);
        $this->assertEquals('1500.50', $customer->credit_limit);
        $this->assertIsInt($customer->payment_terms);
        $this->assertEquals(45, $customer->payment_terms);
        $this->assertIsBool($customer->is_active);
        $this->assertTrue($customer->is_active);
        $this->assertInstanceOf(Carbon::class, $customer->birthday);
        $this->assertIsArray($customer->tags);
        $this->assertEquals(['vip', 'wholesale'], $customer->tags);
    }

    public function test_customer_display_name_includes_type()
    {
        $customer = Customer::create([
            'name' => 'VIP Customer',
            'customer_type' => 'vip',
        ]);

        $this->assertEquals('VIP Customer (VIP Customer)', $customer->display_name);
    }

    public function test_customer_age_calculation()
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'birthday' => Carbon::now()->subYears(30),
        ]);

        $this->assertEquals(30, $customer->age);
    }

    public function test_customer_age_is_null_without_birthday()
    {
        $customer = Customer::create(['name' => 'Test Customer']);

        $this->assertNull($customer->age);
    }

    public function test_customer_has_upcoming_birthday()
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'birthday' => Carbon::now()->addDays(15)->subYears(25),
        ]);

        $this->assertTrue($customer->hasUpcomingBirthday());
    }

    public function test_customer_does_not_have_upcoming_birthday()
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'birthday' => Carbon::now()->addDays(45)->subYears(25),
        ]);

        $this->assertFalse($customer->hasUpcomingBirthday());
    }

    public function test_customer_has_upcoming_anniversary()
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'anniversary' => Carbon::now()->addDays(20)->subYears(5),
        ]);

        $this->assertTrue($customer->hasUpcomingAnniversary());
    }

    public function test_customer_scope_active()
    {
        Customer::create(['name' => 'Active Customer', 'is_active' => true]);
        Customer::create(['name' => 'Inactive Customer', 'is_active' => false]);

        $activeCustomers = Customer::active()->get();

        $this->assertCount(1, $activeCustomers);
        $this->assertEquals('Active Customer', $activeCustomers->first()->name);
    }

    public function test_customer_scope_of_type()
    {
        Customer::create(['name' => 'Retail Customer', 'customer_type' => 'retail']);
        Customer::create(['name' => 'Wholesale Customer', 'customer_type' => 'wholesale']);

        $retailCustomers = Customer::ofType('retail')->get();

        $this->assertCount(1, $retailCustomers);
        $this->assertEquals('Retail Customer', $retailCustomers->first()->name);
    }

    public function test_customer_scope_in_stage()
    {
        Customer::create(['name' => 'Lead Customer', 'crm_stage' => 'lead']);
        Customer::create(['name' => 'Prospect Customer', 'crm_stage' => 'prospect']);

        $leadCustomers = Customer::inStage('lead')->get();

        $this->assertCount(1, $leadCustomers);
        $this->assertEquals('Lead Customer', $leadCustomers->first()->name);
    }

    public function test_customer_scope_search()
    {
        Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
        ]);
        Customer::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+0987654321',
        ]);

        $searchResults = Customer::search('john')->get();
        $this->assertCount(1, $searchResults);
        $this->assertEquals('John Doe', $searchResults->first()->name);

        $emailSearchResults = Customer::search('jane@example.com')->get();
        $this->assertCount(1, $emailSearchResults);
        $this->assertEquals('Jane Smith', $emailSearchResults->first()->name);

        $phoneSearchResults = Customer::search('1234567890')->get();
        $this->assertCount(1, $phoneSearchResults);
        $this->assertEquals('John Doe', $phoneSearchResults->first()->name);
    }

    public function test_customer_soft_deletes()
    {
        $customer = Customer::create(['name' => 'Test Customer']);
        $customerId = $customer->id;

        $customer->delete();

        $this->assertSoftDeleted('customers', ['id' => $customerId]);
        $this->assertCount(0, Customer::all());
        $this->assertCount(1, Customer::withTrashed()->get());
    }

    public function test_customer_constants_are_defined()
    {
        $this->assertIsArray(Customer::CUSTOMER_TYPES);
        $this->assertArrayHasKey('retail', Customer::CUSTOMER_TYPES);
        $this->assertArrayHasKey('wholesale', Customer::CUSTOMER_TYPES);
        $this->assertArrayHasKey('vip', Customer::CUSTOMER_TYPES);

        $this->assertIsArray(Customer::CRM_STAGES);
        $this->assertArrayHasKey('lead', Customer::CRM_STAGES);
        $this->assertArrayHasKey('prospect', Customer::CRM_STAGES);
        $this->assertArrayHasKey('customer', Customer::CRM_STAGES);
        $this->assertArrayHasKey('inactive', Customer::CRM_STAGES);

        $this->assertIsArray(Customer::LEAD_SOURCES);
        $this->assertArrayHasKey('referral', Customer::LEAD_SOURCES);
        $this->assertArrayHasKey('website', Customer::LEAD_SOURCES);
    }
}