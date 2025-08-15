<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DashboardService;
use App\Services\AlertService;

echo "Testing Dashboard API Data...\n\n";

try {
    // Test Dashboard Service
    $dashboardService = app(DashboardService::class);
    
    echo "=== Testing KPIs ===\n";
    $kpis = $dashboardService->getKPIs();
    foreach ($kpis as $key => $value) {
        echo "$key: $value\n";
    }
    
    echo "\n=== Testing Sales Chart Data ===\n";
    $salesData = $dashboardService->getSalesChartData('month');
    echo "Sales data points: " . count($salesData) . "\n";
    foreach (array_slice($salesData, 0, 3) as $data) {
        echo "- {$data['label']}: \${$data['sales']}\n";
    }
    
    echo "\n=== Testing Alert Service ===\n";
    $alertService = app(AlertService::class);
    $alerts = $alertService->getAlerts(5);
    echo "Total alerts: " . count($alerts) . "\n";
    foreach ($alerts as $alert) {
        echo "- {$alert['title']}: {$alert['message']}\n";
    }
    
    echo "\n=== Testing Alert Counts ===\n";
    $alertCounts = $alertService->getAlertCounts();
    foreach ($alertCounts as $key => $count) {
        echo "$key: $count\n";
    }
    
    echo "\nDashboard API test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}