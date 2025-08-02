<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Services\DashboardService;
use App\Services\AlertService;
use App\Services\WidgetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_get_kpis_requires_authentication()
    {
        $response = $this->getJson('/api/dashboard/kpis');

        $response->assertStatus(401);
    }

    public function test_get_kpis_returns_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/kpis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'gold_sold',
                    'total_profits',
                    'average_price',
                    'returns',
                    'gross_margin',
                    'net_margin',
                    'total_sales',
                    'active_customers',
                    'inventory_value',
                    'pending_invoices'
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_get_kpis_with_date_range()
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/dashboard/kpis?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_get_kpis_validates_date_range()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/kpis?start_date=invalid-date');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_get_sales_chart_returns_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/sales-chart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'date',
                        'label',
                        'sales'
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_get_sales_chart_with_period()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/sales-chart?period=week');

        $response->assertStatus(200);
        $this->assertCount(7, $response->json('data')); // 7 days in a week
    }

    public function test_get_sales_chart_validates_period()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/sales-chart?period=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    public function test_get_category_performance_returns_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/category-performance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data'
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_get_alerts_returns_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'alerts' => [
                        'pending_cheques',
                        'stock_warnings',
                        'overdue_invoices',
                        'expiring_items',
                        'low_stock',
                        'high_value_pending'
                    ],
                    'counts' => [
                        'total',
                        'high',
                        'medium',
                        'low'
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_mark_alert_as_read()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/alerts/mark-read', [
                'alert_id' => 'test_alert_123'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Alert marked as read'
            ]);
    }

    public function test_mark_alert_as_read_validates_input()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/alerts/mark-read', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['alert_id']);
    }

    public function test_get_dashboard_layout_returns_default()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/layout');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'version',
                    'preset',
                    'widgets',
                    'settings'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('default', $response->json('data.preset'));
    }

    public function test_save_dashboard_layout()
    {
        $layout = [
            'widgets' => [
                'kpi_summary' => [
                    'id' => 'kpi_summary',
                    'type' => 'kpi',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 12, 'h' => 2]
                ]
            ],
            'settings' => [
                'auto_refresh' => true,
                'refresh_interval' => 300
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/layout', $layout);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dashboard layout saved successfully'
            ]);

        // Verify layout was saved
        $this->user->refresh();
        $savedLayout = json_decode($this->user->dashboard_layout, true);
        $this->assertEquals($layout['widgets'], $savedLayout['widgets']);
    }

    public function test_save_dashboard_layout_validates_structure()
    {
        $invalidLayout = [
            'widgets' => [
                'invalid_widget' => [
                    'id' => 'invalid_widget'
                    // Missing required fields
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/layout', $invalidLayout);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['widgets.invalid_widget.type']);
    }

    public function test_get_dashboard_presets()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/presets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'default' => [
                        'name',
                        'description',
                        'widgets'
                    ],
                    'accountant' => [
                        'name',
                        'description',
                        'widgets'
                    ],
                    'sales' => [
                        'name',
                        'description',
                        'widgets'
                    ],
                    'inventory' => [
                        'name',
                        'description',
                        'widgets'
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_apply_dashboard_preset()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/presets/apply', [
                'preset' => 'accountant'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dashboard preset applied successfully'
            ]);

        // Verify preset was applied
        $layoutResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/layout');

        $this->assertEquals('accountant', $layoutResponse->json('data.preset'));
    }

    public function test_apply_dashboard_preset_validates_preset()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/presets/apply', [
                'preset' => 'invalid_preset'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preset']);
    }

    public function test_get_available_widgets()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/widgets/available');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'kpi_summary' => [
                        'id',
                        'name',
                        'type',
                        'position',
                        'enabled'
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_add_widget()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/widgets/add', [
                'widget_id' => 'sales_chart',
                'position' => ['x' => 0, 'y' => 0, 'w' => 8, 'h' => 4]
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Widget added successfully'
            ]);
    }

    public function test_add_widget_validates_input()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/widgets/add', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['widget_id']);
    }

    public function test_remove_widget()
    {
        // First add a widget
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/widgets/add', [
                'widget_id' => 'sales_chart'
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/dashboard/widgets/remove', [
                'widget_id' => 'sales_chart'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Widget removed successfully'
            ]);
    }

    public function test_update_widget_config()
    {
        // First add a widget
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/widgets/add', [
                'widget_id' => 'kpi_summary'
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/dashboard/widgets/config', [
                'widget_id' => 'kpi_summary',
                'config' => [
                    'metrics' => ['total_sales', 'total_profits'],
                    'show_comparison' => true
                ]
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Widget configuration updated successfully'
            ]);
    }

    public function test_update_widget_config_validates_input()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/dashboard/widgets/config', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['widget_id', 'config']);
    }

    public function test_reset_dashboard()
    {
        // First customize the dashboard
        $this->user->update([
            'dashboard_layout' => json_encode(['custom' => 'layout'])
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/reset');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dashboard reset to default successfully'
            ]);

        // Verify dashboard was reset
        $this->user->refresh();
        $this->assertNull($this->user->dashboard_layout);
    }

    public function test_clear_cache()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/dashboard/clear-cache');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Dashboard cache cleared successfully'
            ]);
    }

    public function test_dashboard_endpoints_with_real_data()
    {
        // Create test data
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();
        $location = \App\Models\Location::factory()->create();
        
        $inventoryItem = InventoryItem::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'quantity' => 10,
            'unit_price' => 100,
            'cost_price' => 80,
            'weight' => 5.5
        ]);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'issue_date' => Carbon::now(),
            'total_amount' => 500
        ]);

        \App\Models\InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity' => 2,
            'unit_price' => 250
        ]);

        // Test KPIs with real data
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/kpis');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(11.0, $data['gold_sold']); // 2 * 5.5
        $this->assertEquals(500, $data['total_sales']);
        $this->assertEquals(1000, $data['inventory_value']); // 10 * 100
        $this->assertEquals(1, $data['active_customers']);

        // Test category performance with real data
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/category-performance');

        $response->assertStatus(200);
        $performance = $response->json('data');

        $this->assertNotEmpty($performance);
        $categoryData = collect($performance)->first();
        $this->assertEquals('500.00', $categoryData['total_revenue']);
        $this->assertEquals('340.00', $categoryData['profit']); // 500 - (2 * 80)
    }
}