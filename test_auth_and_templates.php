<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Authentication and Templates...\n\n";

// Test 1: Create a user token
echo "1. Creating authentication token...\n";
$user = \App\Models\User::first();
if (!$user) {
    echo "No user found! Creating one...\n";
    $user = \App\Models\User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'preferred_language' => 'en',
        'is_active' => true,
    ]);
}

$token = $user->createToken('test-token')->plainTextToken;
echo "Created token for user: {$user->email}\n";
echo "Token: {$token}\n\n";

// Test 2: Test API with authentication
echo "2. Testing API with authentication...\n";
$request = \Illuminate\Http\Request::create('/api/invoice-templates', 'GET');
$request->headers->set('Accept', 'application/json');
$request->headers->set('Authorization', 'Bearer ' . $token);

try {
    $response = app()->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    if ($data && isset($data['success']) && $data['success']) {
        echo "Success: true\n";
        if (isset($data['data']['data'])) {
            echo "Templates found: " . count($data['data']['data']) . "\n";
            foreach ($data['data']['data'] as $template) {
                echo "- {$template['name']} ({$template['language']})\n";
            }
        }
    } else {
        echo "API call failed\n";
        echo "Response: $content\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
echo "\nTo use this token in your frontend:\n";
echo "1. Login with: admin@jewelry.com / password123\n";
echo "2. Or manually set token in localStorage: auth_token = '$token'\n";