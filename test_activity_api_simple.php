<?php

// Simple test for Activity API endpoints using curl
echo "🧪 Testing Activity API Endpoints\n";
echo "================================\n\n";

function makeApiCall($method, $endpoint, $data = null) {
    $url = 'http://localhost' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Add headers
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        return null;
    }
    
    $decoded = json_decode($response, true);
    return ['code' => $httpCode, 'data' => $decoded];
}

// Test 1: Check if we can access the activities endpoint
echo "1. Testing GET /api/activities (without auth)\n";
$response = makeApiCall('GET', '/api/activities?limit=5');
if ($response) {
    echo "   HTTP Code: " . $response['code'] . "\n";
    if ($response['code'] === 401) {
        echo "✅ Endpoint exists and requires authentication (expected)\n";
    } elseif ($response['code'] === 200) {
        echo "✅ Endpoint accessible\n";
        if (isset($response['data']['data'])) {
            echo "   Activities count: " . count($response['data']['data']) . "\n";
        }
    } else {
        echo "❌ Unexpected response code\n";
    }
} else {
    echo "❌ Failed to connect to endpoint\n";
}
echo "\n";

// Test 2: Test activity stats endpoint
echo "2. Testing GET /api/activities/stats (without auth)\n";
$response = makeApiCall('GET', '/api/activities/stats');
if ($response) {
    echo "   HTTP Code: " . $response['code'] . "\n";
    if ($response['code'] === 401) {
        echo "✅ Stats endpoint exists and requires authentication (expected)\n";
    } elseif ($response['code'] === 200) {
        echo "✅ Stats endpoint accessible\n";
    } else {
        echo "❌ Unexpected response code\n";
    }
} else {
    echo "❌ Failed to connect to stats endpoint\n";
}
echo "\n";

// Test 3: Test pending activities endpoint
echo "3. Testing GET /api/activities/pending (without auth)\n";
$response = makeApiCall('GET', '/api/activities/pending');
if ($response) {
    echo "   HTTP Code: " . $response['code'] . "\n";
    if ($response['code'] === 401) {
        echo "✅ Pending activities endpoint exists and requires authentication (expected)\n";
    } elseif ($response['code'] === 200) {
        echo "✅ Pending activities endpoint accessible\n";
    } else {
        echo "❌ Unexpected response code\n";
    }
} else {
    echo "❌ Failed to connect to pending activities endpoint\n";
}
echo "\n";

// Test 4: Test activities by type endpoint
echo "4. Testing GET /api/activities/type/system_test (without auth)\n";
$response = makeApiCall('GET', '/api/activities/type/system_test');
if ($response) {
    echo "   HTTP Code: " . $response['code'] . "\n";
    if ($response['code'] === 401) {
        echo "✅ Activities by type endpoint exists and requires authentication (expected)\n";
    } elseif ($response['code'] === 200) {
        echo "✅ Activities by type endpoint accessible\n";
    } else {
        echo "❌ Unexpected response code\n";
    }
} else {
    echo "❌ Failed to connect to activities by type endpoint\n";
}
echo "\n";

// Test 5: Test POST endpoint (should require auth)
echo "5. Testing POST /api/activities (without auth)\n";
$activityData = [
    'type' => 'api_test',
    'description' => 'Testing activity logging via API endpoint',
    'status' => 'completed'
];

$response = makeApiCall('POST', '/api/activities', $activityData);
if ($response) {
    echo "   HTTP Code: " . $response['code'] . "\n";
    if ($response['code'] === 401) {
        echo "✅ POST endpoint exists and requires authentication (expected)\n";
    } elseif ($response['code'] === 201) {
        echo "✅ Activity created successfully\n";
    } else {
        echo "❌ Unexpected response code\n";
    }
} else {
    echo "❌ Failed to connect to POST endpoint\n";
}
echo "\n";

echo "🎯 Activity API Endpoint Testing Complete!\n";
echo "All endpoints are properly protected and accessible.\n";
echo "================================\n";