<?php

// Test the inventory API endpoint directly
$url = 'http://localhost/api/inventory';

// Get auth token first
$loginData = [
    'email' => 'admin@jewelry.com',
    'password' => 'password123'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponse = curl_exec($ch);
$loginData = json_decode($loginResponse, true);

if (!isset($loginData['data']['token'])) {
    echo "Failed to login: " . $loginResponse . "\n";
    exit(1);
}

$token = $loginData['data']['token'];
echo "Got auth token: " . substr($token, 0, 20) . "...\n";

// Now test inventory creation
$inventoryData = [
    'name' => 'Test Gold Ring',
    'name_persian' => 'حلقه طلا تست',
    'sku' => 'TEST-' . time(),
    'category_id' => '11',
    'location_id' => '7',
    'quantity' => '10',
    'unit_price' => '1000',
    'cost_price' => '800',
    'gold_purity' => '14.0',
    'weight' => '5.5',
    'is_active' => '1',
    'track_serial' => '0',
    'track_batch' => '0',
];

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $inventoryData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";

curl_close($ch);