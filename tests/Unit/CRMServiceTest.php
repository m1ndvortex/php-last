<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Communication;
use App\Models\User;
use App\Services\CRMService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CRMServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CRMService $crmService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->crmService = new CRMService();
    }

    public function test_get_pipeline_data_returns_correct_structure()
    {
        // Create customers in different stages
        Customer::create(['name' => 'Lead 1', 'crm_stage' => 'lead']);
        Customer::create(['name' => 'Lead 2', 'crm_stage' => 'lead']);
        Customer::create(['name' => 'Prospect 1', 'crm_stage' => 'prospect']);
        Customer::create(['name' => 'Customer 1', 'crm_stage' => 'customer']);

        $pipelineData = $this->crmService->getPipelineData();

        $this->assertIsArray($pipelineData);
        $this->assertArrayHasKey('pipeline', $pipelineData);
        $this->assertArrayHasKey('conversion_rates', $pipelineData);
        $this->assertArrayHasKey('recent_changes', $pipelineData);
        $this->assertArrayHasKey('total_active_customers', $pipelineData);
        $this->assertArrayHasKey('total_leads', $pipelineData);
        $this->assertArrayHasKey('total_prospects', $pipelineData);
        $this->assertArrayHasKey('total_customers', $pipelineData);

        // Check pipeline structure
        $pipeline = $pipelineData['pipeline'];
        $this->assertArrayHasKey('lead', $pipeline);
        $this->assertArrayHasKey('prospect', $pipeline);
        $this->assertArrayHasKey('customer', $pipeline);
        $this->assertArrayHasKey('inactive', $pipeline);

        // Check counts
        $this->assertEquals(2, $pipeline['lead']['count']);
        $this->assertEquals(1, $pipeline['prospect']['count']);
        $this->assertEquals(1, $pipeline['customer']['count']);
        $this->assertEquals(0, $pipeline['inactive']['count']);

        // Check totals
        $this->assertEquals(2, $pipelineData['total_leads']);
        $this->assertEquals(1, $pipelineData['total_prospects']);
        $this->assertEquals(1, $pipelineData['total_customers']);
    }

    public function test_update_customer_stage_updates_stage_and_logs_change()
    {
        $customer = Customer::create(['name' => 'Test Customer', 'crm_stage' => 'lead']);

        $updatedCustomer = $this->crmService->updateCustomerStage($customer, 'prospect', 'Qualified lead');

        $this->assertEquals('prospect', $updatedCustomer->crm_stage);
        
        // Check that stage change was logged
        $this->assertDatabaseHas('communications', [
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'subject' => 'CRM Stage Change',
        ]);

        $communication = Communication::where('customer_id', $customer->id)
            ->where('message', 'like', 'CRM stage changed%')
            ->first();

        $this->assertNotNull($communication);
        $this->assertStringContainsString('Lead to Prospect', $communication->message);
        $this->assertStringContainsString('Qualified lead', $communication->message);
    }

    public function test_get_customers_by_stage_returns_correct_customers()
    {
        Customer::create(['name' => 'Lead 1', 'crm_stage' => 'lead']);
        Customer::create(['name' => 'Lead 2', 'crm_stage' => 'lead']);
        Customer::create(['name' => 'Prospect 1', 'crm_stage' => 'prospect']);

        $leadCustomers = $this->crmService->getCustomersByStage('lead');

        $this->assertCount(2, $leadCustomers);
        $this->assertEquals('lead', $leadCustomers->first()->crm_stage);
    }

    public function test_get_customers_by_stage_excludes_inactive_customers()
    {
        Customer::create(['name' => 'Active Lead', 'crm_stage' => 'lead', 'is_active' => true]);
        Customer::create(['name' => 'Inactive Lead', 'crm_stage' => 'lead', 'is_active' => false]);

        $leadCustomers = $this->crmService->getCustomersByStage('lead');

        $this->assertCount(1, $leadCustomers);
        $this->assertEquals('Active Lead', $leadCustomers->first()->name);
    }

    public function test_get_customers_requiring_follow_up()
    {
        // Customer with no communications (needs follow-up)
        $customerNoComm = Customer::create([
            'name' => 'No Communication',
            'crm_stage' => 'lead',
            'created_at' => now()->subDays(10)
        ]);

        // Customer with recent communication (doesn't need follow-up)
        $customerRecentComm = Customer::create([
            'name' => 'Recent Communication',
            'crm_stage' => 'prospect',
        ]);

        Communication::create([
            'customer_id' => $customerRecentComm->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Recent note',
            'created_at' => now()->subDays(2)
        ]);

        // Customer with old communication (needs follow-up)
        $customerOldComm = Customer::create([
            'name' => 'Old Communication',
            'crm_stage' => 'lead',
        ]);

        Communication::create([
            'customer_id' => $customerOldComm->id,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Old note',
            'created_at' => now()->subDays(10)
        ]);

        // Customer who is already a customer (shouldn't need follow-up)
        Customer::create([
            'name' => 'Existing Customer',
            'crm_stage' => 'customer',
        ]);

        $customersNeedingFollowUp = $this->crmService->getCustomersRequiringFollowUp();

        $this->assertCount(2, $customersNeedingFollowUp);
        
        $names = $customersNeedingFollowUp->pluck('name')->toArray();
        $this->assertContains('No Communication', $names);
        $this->assertContains('Old Communication', $names);
        $this->assertNotContains('Recent Communication', $names);
        $this->assertNotContains('Existing Customer', $names);
    }

    public function test_get_crm_analytics_returns_correct_structure()
    {
        $dateRange = [
            'start' => now()->subDays(30),
            'end' => now()
        ];

        $analytics = $this->crmService->getCRMAnalytics($dateRange);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('new_customers_by_stage', $analytics);
        $this->assertArrayHasKey('stage_changes', $analytics);
        $this->assertArrayHasKey('activity_metrics', $analytics);
        $this->assertArrayHasKey('lead_source_performance', $analytics);
        $this->assertArrayHasKey('date_range', $analytics);

        // Check activity metrics structure
        $activityMetrics = $analytics['activity_metrics'];
        $this->assertArrayHasKey('total_communications', $activityMetrics);
        $this->assertArrayHasKey('communications_by_type', $activityMetrics);
        $this->assertArrayHasKey('active_customers', $activityMetrics);
    }

    public function test_get_dashboard_summary_returns_correct_structure()
    {
        // Create some test data
        Customer::create(['name' => 'Today Lead', 'crm_stage' => 'lead', 'created_at' => now()]);
        Customer::create(['name' => 'Week Lead', 'crm_stage' => 'lead', 'created_at' => now()->subDays(2)]);
        Customer::create(['name' => 'Month Customer', 'crm_stage' => 'customer', 'created_at' => now()->subDays(10)]);

        Communication::create([
            'customer_id' => 1,
            'user_id' => $this->user->id,
            'type' => 'note',
            'message' => 'Today communication',
            'created_at' => now()
        ]);

        $summary = $this->crmService->getDashboardSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('today', $summary);
        $this->assertArrayHasKey('this_week', $summary);
        $this->assertArrayHasKey('this_month', $summary);

        // Check today's data
        $today = $summary['today'];
        $this->assertArrayHasKey('new_leads', $today);
        $this->assertArrayHasKey('stage_changes', $today);
        $this->assertArrayHasKey('communications', $today);

        // Check this week's data
        $thisWeek = $summary['this_week'];
        $this->assertArrayHasKey('new_leads', $thisWeek);
        $this->assertArrayHasKey('conversions', $thisWeek);
        $this->assertArrayHasKey('active_customers', $thisWeek);

        // Check this month's data
        $thisMonth = $summary['this_month'];
        $this->assertArrayHasKey('new_customers', $thisMonth);
        $this->assertArrayHasKey('total_communications', $thisMonth);
        $this->assertArrayHasKey('follow_ups_needed', $thisMonth);
    }

    public function test_calculate_conversion_rates()
    {
        // Create customers in different stages
        Customer::create(['name' => 'Lead 1', 'crm_stage' => 'lead']);
        Customer::create(['name' => 'Lead 2', 'crm_stage' => 'lead']);
        Customer::create(['name' => 'Lead 3', 'crm_stage' => 'lead']);
        Customer::create(['name' => 'Lead 4', 'crm_stage' => 'lead']); // 4 leads

        Customer::create(['name' => 'Prospect 1', 'crm_stage' => 'prospect']);
        Customer::create(['name' => 'Prospect 2', 'crm_stage' => 'prospect']); // 2 prospects

        Customer::create(['name' => 'Customer 1', 'crm_stage' => 'customer']); // 1 customer

        $pipelineData = $this->crmService->getPipelineData();
        $conversionRates = $pipelineData['conversion_rates'];

        $this->assertArrayHasKey('lead_to_prospect', $conversionRates);
        $this->assertArrayHasKey('prospect_to_customer', $conversionRates);
        $this->assertArrayHasKey('lead_to_customer', $conversionRates);
        $this->assertArrayHasKey('total_leads', $conversionRates);
        $this->assertArrayHasKey('total_prospects', $conversionRates);
        $this->assertArrayHasKey('total_customers', $conversionRates);

        // Check calculations
        $this->assertEquals(4, $conversionRates['total_leads']);
        $this->assertEquals(2, $conversionRates['total_prospects']);
        $this->assertEquals(1, $conversionRates['total_customers']);

        // Lead to prospect: 2/4 = 50%
        $this->assertEquals(50.0, $conversionRates['lead_to_prospect']);
        
        // Prospect to customer: 1/2 = 50%
        $this->assertEquals(50.0, $conversionRates['prospect_to_customer']);
        
        // Lead to customer: 1/4 = 25%
        $this->assertEquals(25.0, $conversionRates['lead_to_customer']);
    }
}