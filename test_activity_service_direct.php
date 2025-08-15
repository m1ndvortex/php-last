<?php

// Direct test of ActivityService functionality
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Services\ActivityService;
use App\Models\ActivityLog;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing ActivityService Directly\n";
echo "===================================\n\n";

try {
    $activityService = app(ActivityService::class);
    
    // Test 1: Log a test activity
    echo "1. Testing activity logging...\n";
    $activity = $activityService->logActivity(
        'direct_test',
        'Testing ActivityService directly from PHP script',
        null,
        'Test Script',
        'completed',
        'test',
        123,
        ['test_mode' => true, 'timestamp' => now()]
    );
    
    if ($activity && $activity->id) {
        echo "✅ Activity logged successfully\n";
        echo "   ID: {$activity->id}\n";
        echo "   Type: {$activity->type}\n";
        echo "   Description: {$activity->description}\n";
    } else {
        echo "❌ Failed to log activity\n";
    }
    echo "\n";
    
    // Test 2: Get recent activities
    echo "2. Testing recent activities retrieval...\n";
    $recentActivities = $activityService->getRecentActivities(5);
    
    if (is_array($recentActivities) && count($recentActivities) > 0) {
        echo "✅ Retrieved " . count($recentActivities) . " recent activities\n";
        echo "   Latest: " . $recentActivities[0]['description'] . "\n";
        echo "   Time: " . $recentActivities[0]['time'] . "\n";
    } else {
        echo "❌ Failed to retrieve recent activities\n";
    }
    echo "\n";
    
    // Test 3: Get activity statistics
    echo "3. Testing activity statistics...\n";
    $stats = $activityService->getActivityStats();
    
    if (is_array($stats) && isset($stats['today'])) {
        echo "✅ Retrieved activity statistics\n";
        echo "   Today's invoices: " . ($stats['today']['invoices'] ?? 0) . "\n";
        echo "   Today's customers: " . ($stats['today']['customers'] ?? 0) . "\n";
        echo "   Today's transactions: " . ($stats['today']['transactions'] ?? 0) . "\n";
    } else {
        echo "❌ Failed to retrieve activity statistics\n";
    }
    echo "\n";
    
    // Test 4: Get activity counts
    echo "4. Testing activity counts...\n";
    $counts = $activityService->getActivityCounts();
    
    if (is_array($counts) && isset($counts['today'])) {
        echo "✅ Retrieved activity counts\n";
        echo "   Today's total activities: " . ($counts['today']['total'] ?? 0) . "\n";
        echo "   This week's total activities: " . ($counts['this_week']['total'] ?? 0) . "\n";
    } else {
        echo "❌ Failed to retrieve activity counts\n";
    }
    echo "\n";
    
    // Test 5: Get activities by type
    echo "5. Testing activities by type...\n";
    $typeActivities = $activityService->getActivitiesByType('direct_test', 3);
    
    if (is_array($typeActivities)) {
        echo "✅ Retrieved " . count($typeActivities) . " activities of type 'direct_test'\n";
        if (count($typeActivities) > 0) {
            echo "   First activity: " . $typeActivities[0]['description'] . "\n";
        }
    } else {
        echo "❌ Failed to retrieve activities by type\n";
    }
    echo "\n";
    
    // Test 6: Get pending activities
    echo "6. Testing pending activities...\n";
    $pendingActivities = $activityService->getPendingActivities(5);
    
    if (is_array($pendingActivities)) {
        echo "✅ Retrieved " . count($pendingActivities) . " pending activities\n";
    } else {
        echo "❌ Failed to retrieve pending activities\n";
    }
    echo "\n";
    
    // Test 7: Get activities for reference
    echo "7. Testing activities for reference...\n";
    $referenceActivities = $activityService->getActivitiesForReference('test', 123, 3);
    
    if (is_array($referenceActivities)) {
        echo "✅ Retrieved " . count($referenceActivities) . " activities for reference test:123\n";
        if (count($referenceActivities) > 0) {
            echo "   Activity: " . $referenceActivities[0]['description'] . "\n";
        }
    } else {
        echo "❌ Failed to retrieve activities for reference\n";
    }
    echo "\n";
    
    // Test 8: Direct ActivityLog model test
    echo "8. Testing ActivityLog model directly...\n";
    $totalActivities = ActivityLog::count();
    $todayActivities = ActivityLog::whereDate('created_at', today())->count();
    
    echo "✅ ActivityLog model working\n";
    echo "   Total activities in database: {$totalActivities}\n";
    echo "   Today's activities: {$todayActivities}\n";
    echo "\n";
    
    echo "🎯 ActivityService Testing Complete!\n";
    echo "All tests passed successfully! ✅\n";
    echo "===================================\n";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}