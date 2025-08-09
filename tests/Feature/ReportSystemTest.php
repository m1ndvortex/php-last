<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\ReportSchedule;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReportSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_report_types()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/reports/types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'sales' => ['name', 'description', 'types'],
                    'inventory' => ['name', 'description', 'types'],
                    'financial' => ['name', 'description', 'types'],
                    'customer' => ['name', 'description', 'types']
                ]
            ]);
    }

    public function test_can_generate_sales_summary_report()
    {
        // Create test data
        $customer = Customer::factory()->create();
        $item = InventoryItem::factory()->create();
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 1000,
            'issue_date' => now()->subDays(5)
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/reports/generate', [
                'type' => 'sales',
                'subtype' => 'summary',
                'date_range' => [
                    'start' => now()->subDays(30)->toDateString(),
                    'end' => now()->toDateString()
                ],
                'filters' => [],
                'language' => 'en',
                'format' => 'json'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'type',
                    'subtype',
                    'date_range',
                    'summary',
                    'charts',
                    'data'
                ]
            ]);

        $this->assertEquals('sales', $response->json('data.type'));
        $this->assertEquals('summary', $response->json('data.subtype'));
    }

    public function test_can_generate_inventory_stock_levels_report()
    {
        // Create test inventory items
        InventoryItem::factory()->count(5)->create([
            'quantity' => $this->faker->numberBetween(0, 100),
            'cost_price' => $this->faker->randomFloat(2, 10, 1000),
            'unit_price' => $this->faker->randomFloat(2, 15, 1500)
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/reports/generate', [
                'type' => 'inventory',
                'subtype' => 'stock_levels',
                'date_range' => [
                    'start' => now()->subDays(30)->toDateString(),
                    'end' => now()->toDateString()
                ],
                'filters' => [],
                'language' => 'en',
                'format' => 'json'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'type',
                    'subtype',
                    'summary' => [
                        'total_items',
                        'total_quantity',
                        'total_value'
                    ],
                    'charts',
                    'data'
                ]
            ]);

        $this->assertEquals('inventory', $response->json('data.type'));
        $this->assertEquals('stock_levels', $response->json('data.subtype'));
    }

    public function test_can_schedule_report()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/reports/schedule', [
                'name' => 'Monthly Sales Report',
                'type' => 'sales',
                'subtype' => 'summary',
                'parameters' => [
                    'filters' => [],
                    'language' => 'en'
                ],
                'schedule' => [
                    'frequency' => 'monthly',
                    'time' => '09:00'
                ],
                'delivery' => [
                    'method' => 'email',
                    'recipients' => ['test@example.com']
                ]
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'subtype'
                ]
            ]);

        $this->assertDatabaseHas('report_schedules', [
            'name' => 'Monthly Sales Report',
            'type' => 'sales',
            'subtype' => 'summary',
            'is_active' => true
        ]);
    }

    public function test_can_get_scheduled_reports()
    {
        // Create test scheduled reports
        ReportSchedule::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/reports/scheduled');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'subtype',
                        'schedule',
                        'delivery',
                        'next_run_at'
                    ]
                ]
            ]);
    }

    public function test_can_delete_scheduled_report()
    {
        $schedule = ReportSchedule::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/reports/scheduled/{$schedule->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('report_schedules', [
            'id' => $schedule->id
        ]);
    }

    public function test_report_service_can_process_scheduled_reports()
    {
        // Create a scheduled report that's due
        $schedule = ReportSchedule::factory()->create([
            'next_run_at' => now()->subHour(),
            'is_active' => true
        ]);

        $reportService = app(ReportService::class);
        
        // This should not throw an exception
        $reportService->processScheduledReports();

        // Verify the schedule was updated
        $schedule->refresh();
        $this->assertNotNull($schedule->last_run_at);
        $this->assertGreaterThan(now(), $schedule->next_run_at);
    }

    public function test_validates_report_generation_parameters()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/reports/generate', [
                'type' => 'invalid_type',
                'subtype' => 'summary'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'date_range']);
    }

    public function test_validates_report_scheduling_parameters()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/reports/schedule', [
                'name' => '',
                'type' => 'sales'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'subtype', 'parameters', 'schedule', 'delivery']);
    }

    public function test_unauthorized_access_returns_401()
    {
        $response = $this->getJson('/api/reports/types');
        $response->assertStatus(401);

        $response = $this->postJson('/api/reports/generate', []);
        $response->assertStatus(401);
    }
}
