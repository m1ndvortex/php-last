<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Frontend API Access...\n\n";

// Test the actual API endpoint that the frontend would call
$baseUrl = env('APP_URL', 'http://localhost');
$apiUrl = $baseUrl . '/api/invoice-templates';

echo "Testing API URL: $apiUrl\n";

// Test with curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}

if ($response) {
    echo "Response: $response\n";
} else {
    echo "No response received\n";
}

// Also test the internal route
echo "\n--- Testing Internal Route ---\n";
try {
    $request = \Illuminate\Http\Request::create('/api/invoice-templates', 'GET');
    $request->headers->set('Accept', 'application/json');
    
    $response = app()->handle($request);
    echo "Internal Status: " . $response->getStatusCode() . "\n";
    echo "Internal Response: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Internal Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";