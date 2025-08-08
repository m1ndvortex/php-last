<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\QueryOptimizationService;
use App\Services\DatabaseMonitoringService;

class DatabasePerformanceServicesTest extends TestCase
{
    public function test_query_optimization_service_can_be_instantiated()
    {
        $service = app(QueryOptimizationService::class);
        $this->assertInstanceOf(QueryOptimizationService::class, $service);
    }

    public function test_database_monitoring_service_can_be_instantiated()
    {
        $service = app(DatabaseMonitoringService::class);
        $this->assertInstanceOf(DatabaseMonitoringService::class, $service);
    }

    public function test_query_optimization_service_has_required_methods()
    {
        $service = app(QueryOptimizationService::class);
        
        $this->assertTrue(method_exists($service, 'clearCache'));
        $this->assertTrue(method_exists($service, 'getDashboardKPIs'));
        $this->assertTrue(method_exists($service, 'getInventoryStatistics'));
        $this->assertTrue(method_exists($service, 'getCategoryHierarchy'));
        $this->assertTrue(method_exists($service, 'getSlowQueryAnalysis'));
    }

    public function test_database_monitoring_service_has_required_methods()
    {
        $service = app(DatabaseMonitoringService::class);
        
        $this->assertTrue(method_exists($service, 'getPerformanceMetrics'));
        $this->assertTrue(method_exists($service, 'getHealthStatus'));
        $this->assertTrue(method_exists($service, 'logPerformanceMetrics'));
    }

    public function test_slow_query_analysis_returns_expected_structure()
    {
        $service = app(QueryOptimizationService::class);
        $analysis = $service->getSlowQueryAnalysis();
        
        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
        $this->assertIsArray($analysis['recommendations']);
        $this->assertNotEmpty($analysis['recommendations']);
    }
}