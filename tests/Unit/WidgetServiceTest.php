<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WidgetService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class WidgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private WidgetService $widgetService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->widgetService = new WidgetService();
    }

    public function test_get_user_dashboard_layout_returns_default_for_new_user()
    {
        $user = User::factory()->create();

        $layout = $this->widgetService->getUserDashboardLayout($user->id);

        $this->assertIsArray($layout);
        $this->assertArrayHasKey('version', $layout);
        $this->assertArrayHasKey('preset', $layout);
        $this->assertArrayHasKey('widgets', $layout);
        $this->assertArrayHasKey('settings', $layout);
        $this->assertEquals('1.0', $layout['version']);
        $this->assertEquals('default', $layout['preset']);
    }

    public function test_get_user_dashboard_layout_returns_saved_layout()
    {
        $customLayout = [
            'version' => '1.0',
            'preset' => 'custom',
            'widgets' => [
                'kpi_summary' => [
                    'id' => 'kpi_summary',
                    'name' => 'KPI Summary',
                    'type' => 'kpi',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 12, 'h' => 2],
                    'enabled' => true
                ]
            ],
            'settings' => [
                'auto_refresh' => false,
                'refresh_interval' => 600,
                'theme' => 'dark'
            ]
        ];

        $user = User::factory()->create([
            'dashboard_layout' => json_encode($customLayout)
        ]);

        $layout = $this->widgetService->getUserDashboardLayout($user->id);

        $this->assertEquals($customLayout, $layout);
        $this->assertEquals('custom', $layout['preset']);
        $this->assertFalse($layout['settings']['auto_refresh']);
    }

    public function test_save_user_dashboard_layout()
    {
        $user = User::factory()->create();

        $newLayout = [
            'version' => '1.0',
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

        $result = $this->widgetService->saveUserDashboardLayout($user->id, $newLayout);

        $this->assertTrue($result);

        $user->refresh();
        $savedLayout = json_decode($user->dashboard_layout, true);
        $this->assertEquals($newLayout, $savedLayout);
    }

    public function test_save_user_dashboard_layout_validates_structure()
    {
        $user = User::factory()->create();

        // Invalid layout - missing required fields
        $invalidLayout = [
            'widgets' => [
                'invalid_widget' => [
                    'id' => 'invalid_widget'
                    // Missing type and position
                ]
            ]
        ];

        $result = $this->widgetService->saveUserDashboardLayout($user->id, $invalidLayout);

        $this->assertFalse($result);
    }

    public function test_get_dashboard_presets()
    {
        $presets = $this->widgetService->getDashboardPresets();

        $this->assertIsArray($presets);
        $this->assertArrayHasKey('default', $presets);
        $this->assertArrayHasKey('accountant', $presets);
        $this->assertArrayHasKey('sales', $presets);
        $this->assertArrayHasKey('inventory', $presets);

        // Check preset structure
        $defaultPreset = $presets['default'];
        $this->assertArrayHasKey('name', $defaultPreset);
        $this->assertArrayHasKey('description', $defaultPreset);
        $this->assertArrayHasKey('widgets', $defaultPreset);
        $this->assertIsArray($defaultPreset['widgets']);
    }

    public function test_apply_preset()
    {
        $user = User::factory()->create();

        $result = $this->widgetService->applyPreset($user->id, 'accountant');

        $this->assertTrue($result);

        $layout = $this->widgetService->getUserDashboardLayout($user->id);
        $this->assertEquals('accountant', $layout['preset']);

        // Check that accountant preset widgets are applied
        $this->assertArrayHasKey('kpi_summary', $layout['widgets']);
        $this->assertArrayHasKey('sales_chart', $layout['widgets']);
        $this->assertArrayNotHasKey('inventory_summary', $layout['widgets']); // Not in accountant preset
    }

    public function test_apply_invalid_preset()
    {
        $user = User::factory()->create();

        $result = $this->widgetService->applyPreset($user->id, 'invalid_preset');

        $this->assertFalse($result);
    }

    public function test_get_available_widgets()
    {
        $widgets = $this->widgetService->getAvailableWidgets();

        $this->assertIsArray($widgets);
        $this->assertArrayHasKey('kpi_summary', $widgets);
        $this->assertArrayHasKey('sales_chart', $widgets);
        $this->assertArrayHasKey('alerts_widget', $widgets);

        // Check widget structure
        $kpiWidget = $widgets['kpi_summary'];
        $this->assertArrayHasKey('id', $kpiWidget);
        $this->assertArrayHasKey('name', $kpiWidget);
        $this->assertArrayHasKey('type', $kpiWidget);
        $this->assertArrayHasKey('position', $kpiWidget);
        $this->assertArrayHasKey('enabled', $kpiWidget);
    }

    public function test_get_widget_config()
    {
        $config = $this->widgetService->getWidgetConfig('kpi_summary');

        $this->assertIsArray($config);
        $this->assertEquals('kpi_summary', $config['id']);
        $this->assertEquals('KPI Summary', $config['name']);
        $this->assertEquals('kpi', $config['type']);

        // Test invalid widget
        $invalidConfig = $this->widgetService->getWidgetConfig('invalid_widget');
        $this->assertNull($invalidConfig);
    }

    public function test_update_widget_config()
    {
        $user = User::factory()->create();

        // First, add the widget to user's dashboard
        $this->widgetService->addWidget($user->id, 'kpi_summary');

        $newConfig = [
            'metrics' => ['total_sales', 'total_profits'],
            'show_comparison' => true
        ];

        $result = $this->widgetService->updateWidgetConfig($user->id, 'kpi_summary', $newConfig);

        $this->assertTrue($result);

        $layout = $this->widgetService->getUserDashboardLayout($user->id);
        $widgetConfig = $layout['widgets']['kpi_summary']['config'];

        $this->assertEquals(['total_sales', 'total_profits'], $widgetConfig['metrics']);
        $this->assertTrue($widgetConfig['show_comparison']);
    }

    public function test_add_widget()
    {
        $user = User::factory()->create();

        $position = ['x' => 0, 'y' => 0, 'w' => 6, 'h' => 3];
        $result = $this->widgetService->addWidget($user->id, 'sales_chart', $position);

        $this->assertTrue($result);

        $layout = $this->widgetService->getUserDashboardLayout($user->id);
        $this->assertArrayHasKey('sales_chart', $layout['widgets']);
        $this->assertEquals($position, $layout['widgets']['sales_chart']['position']);
    }

    public function test_add_invalid_widget()
    {
        $user = User::factory()->create();

        $result = $this->widgetService->addWidget($user->id, 'invalid_widget');

        $this->assertFalse($result);
    }

    public function test_remove_widget()
    {
        $user = User::factory()->create();

        // First add a widget
        $this->widgetService->addWidget($user->id, 'sales_chart');

        // Verify it's added
        $layout = $this->widgetService->getUserDashboardLayout($user->id);
        $this->assertArrayHasKey('sales_chart', $layout['widgets']);

        // Remove the widget
        $result = $this->widgetService->removeWidget($user->id, 'sales_chart');

        $this->assertTrue($result);

        // Verify it's removed
        $layout = $this->widgetService->getUserDashboardLayout($user->id);
        $this->assertArrayNotHasKey('sales_chart', $layout['widgets']);
    }

    public function test_remove_nonexistent_widget()
    {
        $user = User::factory()->create();

        $result = $this->widgetService->removeWidget($user->id, 'nonexistent_widget');

        $this->assertFalse($result);
    }

    public function test_reset_to_default()
    {
        $user = User::factory()->create([
            'dashboard_layout' => json_encode(['custom' => 'layout'])
        ]);

        $result = $this->widgetService->resetToDefault($user->id);

        $this->assertTrue($result);

        $user->refresh();
        $this->assertNull($user->dashboard_layout);

        // Getting layout should now return default
        $layout = $this->widgetService->getUserDashboardLayout($user->id);
        $this->assertEquals('default', $layout['preset']);
    }

    public function test_validate_layout_structure()
    {
        $user = User::factory()->create();

        // Valid layout
        $validLayout = [
            'widgets' => [
                'test_widget' => [
                    'id' => 'test_widget',
                    'type' => 'test',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 6, 'h' => 3]
                ]
            ]
        ];

        $result = $this->widgetService->saveUserDashboardLayout($user->id, $validLayout);
        $this->assertTrue($result);

        // Invalid layout - missing widgets
        $invalidLayout1 = [];
        $result = $this->widgetService->saveUserDashboardLayout($user->id, $invalidLayout1);
        $this->assertFalse($result);

        // Invalid layout - widget missing required fields
        $invalidLayout2 = [
            'widgets' => [
                'test_widget' => [
                    'id' => 'test_widget'
                    // Missing type and position
                ]
            ]
        ];
        $result = $this->widgetService->saveUserDashboardLayout($user->id, $invalidLayout2);
        $this->assertFalse($result);

        // Invalid layout - position missing required fields
        $invalidLayout3 = [
            'widgets' => [
                'test_widget' => [
                    'id' => 'test_widget',
                    'type' => 'test',
                    'position' => ['x' => 0] // Missing y, w, h
                ]
            ]
        ];
        $result = $this->widgetService->saveUserDashboardLayout($user->id, $invalidLayout3);
        $this->assertFalse($result);
    }

    public function test_cache_is_used()
    {
        $user = User::factory()->create();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(['cached' => 'layout']);

        $layout = $this->widgetService->getUserDashboardLayout($user->id);

        $this->assertEquals(['cached' => 'layout'], $layout);
    }

    public function test_cache_is_cleared_on_save()
    {
        $user = User::factory()->create();

        Cache::shouldReceive('forget')
            ->once()
            ->with("user_dashboard_layout_{$user->id}");

        $layout = [
            'widgets' => [
                'test_widget' => [
                    'id' => 'test_widget',
                    'type' => 'test',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 6, 'h' => 3]
                ]
            ]
        ];

        $this->widgetService->saveUserDashboardLayout($user->id, $layout);
    }
}