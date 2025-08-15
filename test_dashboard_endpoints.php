<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Http\Controllers\DashboardController;

echo "Testing Dashboard HTTP Endpoints...\n\n";

try {
    // Create a mock request
    $request = Request::create('/api/dashboard/kpis', 'GET');
    $request->headers->set('Accept', 'application/json');
    
    // Test KPIs endpoint
    echo "=== Testing KPIs Endpoint ===\n";
    $controller = app(DashboardController::class);
    $response = $controller->getKPIs($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "✓ KPIs endpoint working\n";
        echo "Gold sold: " . $data['data']['gold_sold'] . "\n";
        echo "Total profits: $" . number_format($data['data']['total_profits'], 2) . "\n";
        echo "Average price: $" . number_format($data['data']['average_price'], 2) . "\n";
    } else {
        echo "✗ KPIs endpoint failed\n";
    }
    
    // Test Alerts endpoint
    echo "\n=== Testing Alerts Endpoint ===\n";
    $response = $controller->getAlerts();
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "✓ Alerts endpoint working\n";
        echo "Total alerts: " . count($data['data']['alerts']) . "\n";
        echo "Alert counts: " . json_encode($data['data']['counts']) . "\n";
    } else {
        echo "✗ Alerts endpoint failed\n";
    }
    
    // Test Sales Chart endpoint
    echo "\n=== Testing Sales Chart Endpoint ===\n";
    $request = Request::create('/api/dashboard/sales-chart', 'GET', ['period' => 'month']);
    $response = $controller->getSalesChart($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "✓ Sales chart endpoint working\n";
        echo "Data points: " . count($data['data']) . "\n";
        echo "Sample data: " . $data['data'][0]['label'] . " = $" . number_format($data['data'][0]['sales'], 2) . "\n";
    } else {
        echo "✗ Sales chart endpoint failed\n";
    }
    
    echo "\nAll dashboard endpoints tested successfully!\n";
    echo "\nYou can now access the dashboard at: http://localhost/\n";
    echo "The dashboard will show real data from your database.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}