<?php

require_once 'test_dashboard_endpoints.php';

echo "🧪 Testing Activity API Endpoints\n";
echo "================================\n\n";

// Test 1: Get recent activities
echo "1. Testing GET /api/activities (Recent Activities)\n";
$response = makeApiRequest('GET', '/api/activities?limit=5');
if ($response && isset($response['success']) && $response['success']) {
    echo "✅ Recent activities retrieved successfully\n";
    echo "   Count: " . $response['meta']['count'] . "\n";
    if (!empty($response['data'])) {
        echo "   Latest activity: " . $response['data'][0]['description'] . "\n";
    }
} else {
    echo "❌ Failed to get recent activities\n";
    if ($response) {
        echo "   Error: " . json_encode($response) . "\n";
    }
}
echo "\n";

// Test 2: Get activity statistics
echo "2. Testing GET /api/activities/stats (Activity Statistics)\n";
$response = makeApiRequest('GET', '/api/activities/stats');
if ($response && isset($response['success']) && $response['success']) {
    echo "✅ Activity statistics retrieved successfully\n";
    if (isset($response['data']['activity_log_counts']['today']['total'])) {
        echo "   Today's activities: " . $response['data']['activity_log_counts']['today']['total'] . "\n";
    }
    if (isset($response['data']['database_stats']['today']['invoices'])) {
        echo "   Today's invoices: " . $response['data']['database_stats']['today']['invoices'] . "\n";
    }
} else {
    echo "❌ Failed to get activity statistics\n";
    if ($response) {
        echo "   Error: " . json_encode($response) . "\n";
    }
}
echo "\n";

// Test 3: Get pending activities
echo "3. Testing GET /api/activities/pending (Pending Activities)\n";
$response = makeApiRequest('GET', '/api/activities/pending?limit=3');
if ($response && isset($response['success']) && $response['success']) {
    echo "✅ Pending activities retrieved successfully\n";
    echo "   Count: " . $response['meta']['count'] . "\n";
} else {
    echo "❌ Failed to get pending activities\n";
    if ($response) {
        echo "   Error: " . json_encode($response) . "\n";
    }
}
echo "\n";

// Test 4: Get activities by type
echo "4. Testing GET /api/activities/type/{type} (Activities by Type)\n";
$response = makeApiRequest('GET', '/api/activities/type/system_test?limit=3');
if ($response && isset($response['success']) && $response['success']) {
    echo "✅ Activities by type retrieved successfully\n";
    echo "   Type: " . $response['meta']['type'] . "\n";
    echo "   Count: " . $response['meta']['count'] . "\n";
} else {
    echo "❌ Failed to get activities by type\n";
    if ($response) {
        echo "   Error: " . json_encode($response) . "\n";
    }
}
echo "\n";

// Test 5: Log a custom activity
echo "5. Testing POST /api/activities (Log Custom Activity)\n";
$activityData = [
    'type' => 'api_test',
    'description' => 'Testing activity logging via API endpoint',
    'status' => 'completed',
    'reference_type' => 'test',
    'reference_id' => 999,
    'metadata' => [
        'test_run' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => 'api_test'
    ]
];

$response = makeApiRequest('POST', '/api/activities', $activityData);
if ($response && isset($response['success']) && $response['success']) {
    echo "✅ Custom activity logged successfully\n";
    echo "   Activity ID: " . $response['data']['id'] . "\n";
    echo "   Type: " . $response['data']['type'] . "\n";
    echo "   Status: " . $response['data']['status'] . "\n";
} else {
    echo "❌ Failed to log custom activity\n";
    if ($response) {
        echo "   Error: " . json_encode($response) . "\n";
    }
}
echo "\n";

// Test 6: Get activities for reference
echo "6. Testing GET /api/activities/reference/{type}/{id} (Activities for Reference)\n";
$response = makeApiRequest('GET', '/api/activities/reference/test/999?limit=3');
if ($response && isset($response['success']) && $response['success']) {
    echo "✅ Activities for reference retrieved successfully\n";
    echo "   Reference Type: " . $response['meta']['reference_type'] . "\n";
    echo "   Reference ID: " . $response['meta']['reference_id'] . "\n";
    echo "   Count: " . $response['meta']['count'] . "\n";
} else {
    echo "❌ Failed to get activities for reference\n";
    if ($response) {
        echo "   Error: " . json_encode($response) . "\n";
    }
}
echo "\n";

echo "🎯 Activity API Testing Complete!\n";
echo "================================\n";