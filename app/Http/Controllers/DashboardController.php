<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\AlertService;
use App\Services\WidgetService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;
    private AlertService $alertService;
    private WidgetService $widgetService;

    public function __construct(
        DashboardService $dashboardService,
        AlertService $alertService,
        WidgetService $widgetService
    ) {
        $this->dashboardService = $dashboardService;
        $this->alertService = $alertService;
        $this->widgetService = $widgetService;
    }

    /**
     * Get dashboard KPIs
     */
    public function getKPIs(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date'
            ]);

            $dateRange = null;
            if ($request->start_date && $request->end_date) {
                $dateRange = [
                    'start' => $request->start_date,
                    'end' => $request->end_date
                ];
            }

            $kpis = $this->dashboardService->getKPIs($dateRange);

            return response()->json([
                'success' => true,
                'data' => $kpis,
                'meta' => [
                    'date_range' => $dateRange,
                    'generated_at' => now()->toISOString(),
                    'cache_duration' => 300
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let validation exceptions bubble up to be handled by Laravel
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to get dashboard KPIs', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard KPIs',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales chart data
     */
    public function getSalesChart(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'period' => ['nullable', Rule::in(['week', 'month', 'year'])]
            ]);

            $period = $request->get('period', 'month');
            $chartData = $this->dashboardService->getSalesChartData($period);

            return response()->json([
                'success' => true,
                'data' => $chartData,
                'meta' => [
                    'period' => $period,
                    'data_points' => count($chartData),
                    'generated_at' => now()->toISOString()
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let validation exceptions bubble up to be handled by Laravel
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to get sales chart data', [
                'error' => $e->getMessage(),
                'period' => $request->get('period', 'month'),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sales chart data',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category performance analytics
     */
    public function getCategoryPerformance(): JsonResponse
    {
        $performance = $this->dashboardService->getCategoryPerformance();

        return response()->json([
            'success' => true,
            'data' => $performance
        ]);
    }

    /**
     * Get gold purity performance analytics
     */
    public function getGoldPurityPerformance(): JsonResponse
    {
        $performance = $this->dashboardService->getGoldPurityPerformance();

        return response()->json([
            'success' => true,
            'data' => $performance
        ]);
    }

    /**
     * Get category stock alerts
     */
    public function getCategoryStockAlerts(): JsonResponse
    {
        $alerts = $this->dashboardService->getCategoryStockAlerts();

        return response()->json([
            'success' => true,
            'data' => $alerts
        ]);
    }

    /**
     * Get dashboard alerts
     */
    public function getAlerts(): JsonResponse
    {
        $alerts = $this->alertService->getAlerts();
        $alertCounts = $this->alertService->getAlertCounts();

        return response()->json([
            'success' => true,
            'data' => [
                'alerts' => $alerts,
                'counts' => $alertCounts
            ]
        ]);
    }

    /**
     * Mark alert as read
     */
    public function markAlertAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'alert_id' => 'required|string'
        ]);

        $success = $this->alertService->markAsRead($request->alert_id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Alert marked as read' : 'Failed to mark alert as read'
        ]);
    }

    /**
     * Get user dashboard layout
     */
    public function getDashboardLayout(): JsonResponse
    {
        $userId = Auth::id();
        $layout = $this->widgetService->getUserDashboardLayout($userId);

        return response()->json([
            'success' => true,
            'data' => $layout
        ]);
    }

    /**
     * Save user dashboard layout
     */
    public function saveDashboardLayout(Request $request): JsonResponse
    {
        $request->validate([
            'widgets' => 'required|array',
            'widgets.*.id' => 'required|string',
            'widgets.*.type' => 'required|string',
            'widgets.*.position' => 'required|array',
            'widgets.*.position.x' => 'required|integer|min:0',
            'widgets.*.position.y' => 'required|integer|min:0',
            'widgets.*.position.w' => 'required|integer|min:1',
            'widgets.*.position.h' => 'required|integer|min:1',
            'settings' => 'nullable|array'
        ]);

        $userId = Auth::id();
        $layout = $request->only(['widgets', 'settings']);
        
        if (isset($layout['settings'])) {
            $layout['settings'] = array_merge([
                'auto_refresh' => true,
                'refresh_interval' => 300,
                'theme' => 'light'
            ], $layout['settings']);
        }

        $success = $this->widgetService->saveUserDashboardLayout($userId, $layout);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Dashboard layout saved successfully' : 'Failed to save dashboard layout'
        ]);
    }

    /**
     * Get available dashboard presets
     */
    public function getDashboardPresets(): JsonResponse
    {
        $presets = $this->widgetService->getDashboardPresets();

        return response()->json([
            'success' => true,
            'data' => $presets
        ]);
    }

    /**
     * Apply dashboard preset
     */
    public function applyDashboardPreset(Request $request): JsonResponse
    {
        $request->validate([
            'preset' => 'required|string|in:default,accountant,sales,inventory'
        ]);

        $userId = Auth::id();
        $success = $this->widgetService->applyPreset($userId, $request->preset);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Dashboard preset applied successfully' : 'Failed to apply dashboard preset'
        ]);
    }

    /**
     * Get available widgets
     */
    public function getAvailableWidgets(): JsonResponse
    {
        $widgets = $this->widgetService->getAvailableWidgets();

        return response()->json([
            'success' => true,
            'data' => $widgets
        ]);
    }

    /**
     * Add widget to dashboard
     */
    public function addWidget(Request $request): JsonResponse
    {
        $request->validate([
            'widget_id' => 'required|string',
            'position' => 'nullable|array',
            'position.x' => 'required_with:position|integer|min:0',
            'position.y' => 'required_with:position|integer|min:0',
            'position.w' => 'required_with:position|integer|min:1',
            'position.h' => 'required_with:position|integer|min:1'
        ]);

        $userId = Auth::id();
        $success = $this->widgetService->addWidget(
            $userId,
            $request->widget_id,
            $request->position
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Widget added successfully' : 'Failed to add widget'
        ]);
    }

    /**
     * Remove widget from dashboard
     */
    public function removeWidget(Request $request): JsonResponse
    {
        $request->validate([
            'widget_id' => 'required|string'
        ]);

        $userId = Auth::id();
        $success = $this->widgetService->removeWidget($userId, $request->widget_id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Widget removed successfully' : 'Failed to remove widget'
        ]);
    }

    /**
     * Update widget configuration
     */
    public function updateWidgetConfig(Request $request): JsonResponse
    {
        $request->validate([
            'widget_id' => 'required|string',
            'config' => 'required|array'
        ]);

        $userId = Auth::id();
        $success = $this->widgetService->updateWidgetConfig(
            $userId,
            $request->widget_id,
            $request->config
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Widget configuration updated successfully' : 'Failed to update widget configuration'
        ]);
    }

    /**
     * Reset dashboard to default
     */
    public function resetDashboard(): JsonResponse
    {
        $userId = Auth::id();
        $success = $this->widgetService->resetToDefault($userId);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Dashboard reset to default successfully' : 'Failed to reset dashboard'
        ]);
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(): JsonResponse
    {
        $this->dashboardService->clearCache();
        $this->alertService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard cache cleared successfully'
        ]);
    }
}