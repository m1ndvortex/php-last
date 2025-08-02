<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class WidgetService
{
    /**
     * Default widget configurations
     */
    private const DEFAULT_WIDGETS = [
        'kpi_summary' => [
            'id' => 'kpi_summary',
            'name' => 'KPI Summary',
            'type' => 'kpi',
            'size' => 'large',
            'position' => ['x' => 0, 'y' => 0, 'w' => 12, 'h' => 2],
            'config' => [
                'metrics' => ['gold_sold', 'total_profits', 'average_price', 'returns']
            ],
            'enabled' => true
        ],
        'sales_chart' => [
            'id' => 'sales_chart',
            'name' => 'Sales Chart',
            'type' => 'chart',
            'size' => 'large',
            'position' => ['x' => 0, 'y' => 2, 'w' => 8, 'h' => 4],
            'config' => [
                'chart_type' => 'line',
                'period' => 'month',
                'show_comparison' => true
            ],
            'enabled' => true
        ],
        'alerts_widget' => [
            'id' => 'alerts_widget',
            'name' => 'Alerts & Notifications',
            'type' => 'alerts',
            'size' => 'medium',
            'position' => ['x' => 8, 'y' => 2, 'w' => 4, 'h' => 4],
            'config' => [
                'max_alerts' => 10,
                'show_severity' => true
            ],
            'enabled' => true
        ],
        'category_performance' => [
            'id' => 'category_performance',
            'name' => 'Category Performance',
            'type' => 'table',
            'size' => 'large',
            'position' => ['x' => 0, 'y' => 6, 'w' => 12, 'h' => 3],
            'config' => [
                'columns' => ['name', 'total_revenue', 'profit', 'margin_percentage'],
                'sort_by' => 'total_revenue',
                'sort_order' => 'desc'
            ],
            'enabled' => true
        ],
        'recent_invoices' => [
            'id' => 'recent_invoices',
            'name' => 'Recent Invoices',
            'type' => 'list',
            'size' => 'medium',
            'position' => ['x' => 0, 'y' => 9, 'w' => 6, 'h' => 3],
            'config' => [
                'limit' => 5,
                'show_status' => true
            ],
            'enabled' => true
        ],
        'inventory_summary' => [
            'id' => 'inventory_summary',
            'name' => 'Inventory Summary',
            'type' => 'summary',
            'size' => 'medium',
            'position' => ['x' => 6, 'y' => 9, 'w' => 6, 'h' => 3],
            'config' => [
                'show_value' => true,
                'show_low_stock' => true
            ],
            'enabled' => true
        ]
    ];

    /**
     * Role-based dashboard presets
     */
    private const DASHBOARD_PRESETS = [
        'default' => [
            'name' => 'Default View',
            'description' => 'Standard dashboard with all widgets',
            'widgets' => ['kpi_summary', 'sales_chart', 'alerts_widget', 'category_performance', 'recent_invoices', 'inventory_summary']
        ],
        'accountant' => [
            'name' => 'Accountant View',
            'description' => 'Financial focused dashboard',
            'widgets' => ['kpi_summary', 'sales_chart', 'category_performance', 'recent_invoices'],
            'custom_config' => [
                'kpi_summary' => [
                    'config' => [
                        'metrics' => ['total_profits', 'gross_margin', 'net_margin', 'pending_invoices']
                    ]
                ],
                'sales_chart' => [
                    'config' => [
                        'chart_type' => 'bar',
                        'period' => 'month',
                        'show_profit_margin' => true
                    ]
                ]
            ]
        ],
        'sales' => [
            'name' => 'Sales View',
            'description' => 'Sales focused dashboard',
            'widgets' => ['kpi_summary', 'sales_chart', 'alerts_widget', 'recent_invoices', 'inventory_summary'],
            'custom_config' => [
                'kpi_summary' => [
                    'config' => [
                        'metrics' => ['gold_sold', 'total_sales', 'active_customers', 'average_price']
                    ]
                ],
                'alerts_widget' => [
                    'config' => [
                        'filter_types' => ['pending_cheque', 'overdue_invoice', 'high_value_pending']
                    ]
                ]
            ]
        ],
        'inventory' => [
            'name' => 'Inventory View',
            'description' => 'Inventory focused dashboard',
            'widgets' => ['inventory_summary', 'alerts_widget', 'category_performance'],
            'custom_config' => [
                'alerts_widget' => [
                    'config' => [
                        'filter_types' => ['low_stock', 'out_of_stock', 'expiring_item']
                    ]
                ],
                'category_performance' => [
                    'config' => [
                        'columns' => ['name', 'total_quantity', 'inventory_value', 'turnover_rate']
                    ]
                ]
            ]
        ]
    ];

    /**
     * Get user's dashboard layout
     */
    public function getUserDashboardLayout(int $userId): array
    {
        $cacheKey = "user_dashboard_layout_{$userId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            $user = User::find($userId);
            
            // Get saved layout from user preferences or use default
            $savedLayout = $user->dashboard_layout ?? null;
            
            if ($savedLayout) {
                return json_decode($savedLayout, true);
            }
            
            // Return default layout
            return $this->getDefaultLayout();
        });
    }

    /**
     * Save user's dashboard layout
     */
    public function saveUserDashboardLayout(int $userId, array $layout): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        // Validate layout structure
        if (!$this->validateLayout($layout)) {
            return false;
        }

        $user->dashboard_layout = json_encode($layout);
        $user->save();

        // Clear cache
        Cache::forget("user_dashboard_layout_{$userId}");

        return true;
    }

    /**
     * Get available dashboard presets
     */
    public function getDashboardPresets(): array
    {
        return self::DASHBOARD_PRESETS;
    }

    /**
     * Apply dashboard preset to user
     */
    public function applyPreset(int $userId, string $presetName): bool
    {
        if (!isset(self::DASHBOARD_PRESETS[$presetName])) {
            return false;
        }

        $preset = self::DASHBOARD_PRESETS[$presetName];
        $layout = $this->buildLayoutFromPreset($preset);

        return $this->saveUserDashboardLayout($userId, $layout);
    }

    /**
     * Get available widgets
     */
    public function getAvailableWidgets(): array
    {
        return self::DEFAULT_WIDGETS;
    }

    /**
     * Get widget configuration
     */
    public function getWidgetConfig(string $widgetId): ?array
    {
        return self::DEFAULT_WIDGETS[$widgetId] ?? null;
    }

    /**
     * Update widget configuration
     */
    public function updateWidgetConfig(int $userId, string $widgetId, array $config): bool
    {
        $layout = $this->getUserDashboardLayout($userId);
        
        if (!isset($layout['widgets'][$widgetId])) {
            return false;
        }

        $layout['widgets'][$widgetId]['config'] = array_merge(
            $layout['widgets'][$widgetId]['config'] ?? [],
            $config
        );

        return $this->saveUserDashboardLayout($userId, $layout);
    }

    /**
     * Add widget to user dashboard
     */
    public function addWidget(int $userId, string $widgetId, array $position = null): bool
    {
        if (!isset(self::DEFAULT_WIDGETS[$widgetId])) {
            return false;
        }

        $layout = $this->getUserDashboardLayout($userId);
        $widget = self::DEFAULT_WIDGETS[$widgetId];

        if ($position) {
            $widget['position'] = $position;
        }

        $layout['widgets'][$widgetId] = $widget;

        return $this->saveUserDashboardLayout($userId, $layout);
    }

    /**
     * Remove widget from user dashboard
     */
    public function removeWidget(int $userId, string $widgetId): bool
    {
        $layout = $this->getUserDashboardLayout($userId);
        
        if (!isset($layout['widgets'][$widgetId])) {
            return false;
        }

        unset($layout['widgets'][$widgetId]);

        return $this->saveUserDashboardLayout($userId, $layout);
    }

    /**
     * Get default dashboard layout
     */
    private function getDefaultLayout(): array
    {
        return [
            'version' => '1.0',
            'preset' => 'default',
            'widgets' => self::DEFAULT_WIDGETS,
            'settings' => [
                'auto_refresh' => true,
                'refresh_interval' => 300, // 5 minutes
                'theme' => 'light'
            ]
        ];
    }

    /**
     * Build layout from preset configuration
     */
    private function buildLayoutFromPreset(array $preset): array
    {
        $layout = $this->getDefaultLayout();
        $layout['preset'] = array_search($preset, self::DASHBOARD_PRESETS);

        // Filter widgets based on preset
        $enabledWidgets = [];
        foreach ($preset['widgets'] as $widgetId) {
            if (isset(self::DEFAULT_WIDGETS[$widgetId])) {
                $widget = self::DEFAULT_WIDGETS[$widgetId];
                
                // Apply custom configuration if exists
                if (isset($preset['custom_config'][$widgetId])) {
                    $widget = array_merge_recursive($widget, $preset['custom_config'][$widgetId]);
                }
                
                $enabledWidgets[$widgetId] = $widget;
            }
        }

        $layout['widgets'] = $enabledWidgets;

        return $layout;
    }

    /**
     * Validate layout structure
     */
    private function validateLayout(array $layout): bool
    {
        // Check required fields
        if (!isset($layout['widgets']) || !is_array($layout['widgets'])) {
            return false;
        }

        // Validate each widget
        foreach ($layout['widgets'] as $widgetId => $widget) {
            if (!isset($widget['id'], $widget['type'], $widget['position'])) {
                return false;
            }

            // Validate position
            $position = $widget['position'];
            if (!isset($position['x'], $position['y'], $position['w'], $position['h'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reset user dashboard to default
     */
    public function resetToDefault(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        $user->dashboard_layout = null;
        $user->save();

        Cache::forget("user_dashboard_layout_{$userId}");

        return true;
    }
}