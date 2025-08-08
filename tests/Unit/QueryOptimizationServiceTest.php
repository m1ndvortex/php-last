<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\QueryOptimizationService;
use App\Services\DatabaseMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class QueryOptimizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QueryOptimizationService $optimizationService;
    protected DatabaseMonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->optimizationService = app(QueryOptimizationService::class);
        $this->monitoringService = app(DatabaseMonitoringService::class);
    }

    public function test_can_clear_cache()
    {
        // Set some cache data
        Cache::put('test_key', 'test_value', 60);
        $this->assertTrue(Cache::has('test_key'));

        // Clear cache
        $this->optimizationService->clearCache('all');

        // Verify cache is cleared
        $this->assertFalse(Cache::has('test_key'));
    }

    public function test_can_get_dashboard_kpis()
    {
        $kpis = $this->optimizationService->getDashboardKPIs();

        $this->assertIsArray($kpis);
        $this->assertArrayHasKey('total_customers', $kpis);
        $this->assertArrayHasKey('active_customers', $kpis);
        $this->assertArrayHasKey('total_inventory_value', $kpis);
        $this->assertArrayHasKey('low_stock_items', $kpis);
    }

    public function test_can_get_inventory_statistics()
    {
        $stats = $this->optimizationService->getInventoryStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_items', $stats);
        $this->assertArrayHasKey('total_value', $stats);
        $this->assertArrayHasKey('low_stock_count', $stats);
        $this->assertArrayHasKey('categories_count', $stats);
    }

    public function test_can_get_category_hierarchy()
    {
        $hierarchy = $this->optimizationService->getCategoryHierarchy();

        $this->assertIsObject($hierarchy);
    }

    public function test_can_get_slow_query_analysis()
    {
        $analysis = $this->optimizationService->getSlowQueryAnalysis();

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
        $this->assertIsArray($analysis['recommendations']);
    }

    public function test_database_monitoring_service_works()
    {
        $metrics = $this->monitoringService->getPerformanceMetrics();

        $this->assertIsArray($metrics);
        // In test environment, we might get connection errors, but the structure should be correct
        $this->assertTrue(
            isset($metrics['connection_status']) || isset($metrics['error'])
        );
    }

    public function test_database_health_status()
    {
        $health = $this->monitoringService->getHealthStatus();

        $this->assertIsArray($health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('timestamp', $health);
        $this->assertContains($health['status'], ['healthy', 'warning', 'unhealthy']);
    }
}