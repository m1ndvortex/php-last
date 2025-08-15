<?php

require_once 'vendor/autoload.php';

use App\Services\AlertService;
use App\Models\Alert;

// Test the AlertService
$alertService = new AlertService();

echo "Testing Alert Service...\n";

// Get alerts
$alerts = $alertService->getAlerts(10, 0);
echo "Number of alerts: " . count($alerts) . "\n";

foreach ($alerts as $alert) {
    echo "Alert ID: " . $alert['id'] . "\n";
    echo "Title: " . $alert['title'] . "\n";
    echo "Message: " . $alert['message'] . "\n";
    echo "Severity: " . $alert['severity'] . "\n";
    echo "---\n";
}

// Get total count
$totalCount = $alertService->getTotalAlertsCount();
echo "Total alerts count: " . $totalCount . "\n";

// Get alert counts
$alertCounts = $alertService->getAlertCounts();
echo "Alert counts: " . json_encode($alertCounts) . "\n";

echo "Test completed!\n";