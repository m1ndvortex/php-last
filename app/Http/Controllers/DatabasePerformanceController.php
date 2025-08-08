<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\DatabaseMonitoringService;
use App\Services\QueryOptimizationService;

class DatabasePerformanceController extends Controller
{
    protected DatabaseMonitoringService $monitoringService;
    protected QueryOptimizationService $optimizationService;

    public function __construct(
        DatabaseMonitoringService $monitoringService,
        QueryOptimizationService $optimizationService
    ) {
        $this->monitoringService = $monitoringService;
        $this->optimizationService = $optimizationService;
    }

    /**
     * Get database performance metrics
     */
    public function metrics(): JsonResponse
    {
        try {
            $metrics = $this->monitoringService->getPerformanceMetrics();
            
            return response()->json([
                'success' => true,
                'data' => $metrics,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve database metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get database health status
     */
    public function health(): JsonResponse
    {
        try {
            $health = $this->monitoringService->getHealthStatus();
            
            $statusCode = match($health['status']) {
                'healthy' => 200,
                'warning' => 200,
                'unhealthy' => 503,
                default => 500
            };
            
            return response()->json([
                'success' => true,
                'data' => $health,
            ], $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve health status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear query cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'all');
            $this->optimizationService->clearCache($type);
            
            return response()->json([
                'success' => true,
                'message' => "Cache cleared for type: {$type}",
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get slow query analysis
     */
    public function slowQueries(): JsonResponse
    {
        try {
            $analysis = $this->optimizationService->getSlowQueryAnalysis();
            
            return response()->json([
                'success' => true,
                'data' => $analysis,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve slow query analysis',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get optimized dashboard KPIs
     */
    public function dashboardKpis(): JsonResponse
    {
        try {
            $kpis = $this->optimizationService->getDashboardKPIs();
            
            return response()->json([
                'success' => true,
                'data' => $kpis,
                'cached' => true,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard KPIs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get inventory statistics with caching
     */
    public function inventoryStats(): JsonResponse
    {
        try {
            $stats = $this->optimizationService->getInventoryStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'cached' => true,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}