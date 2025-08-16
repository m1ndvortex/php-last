<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\AuthController;
use App\Models\User;

echo "Testing login functionality...\n";

// Check if user exists and is active
$user = User::where('email', 'test@example.com')->first();
if (!$user) {
    echo "ERROR: User not found!\n";
    exit(1);
}

echo "User found: {$user->name} ({$user->email})\n";
echo "User active: " . ($user->is_active ? 'Yes' : 'No') . "\n";

// Test password verification
$passwordCheck = password_verify('password123', $user->password);
echo "Password verification: " . ($passwordCheck ? 'PASS' : 'FAIL') . "\n";

if (!$passwordCheck) {
    echo "Password hash in database: " . substr($user->password, 0, 20) . "...\n";
    echo "Updating password hash...\n";
    $user->password = bcrypt('password123');
    $user->save();
    echo "Password updated. Testing again...\n";
    $passwordCheck = password_verify('password123', $user->password);
    echo "Password verification after update: " . ($passwordCheck ? 'PASS' : 'FAIL') . "\n";
}

// Create a mock request for login
$request = Request::create('/api/auth/login', 'POST', [
    'email' => 'test@example.com',
    'password' => 'password123'
]);

$request->headers->set('Accept', 'application/json');
$request->headers->set('Content-Type', 'application/json');

echo "\nTesting login endpoint...\n";

try {
    $controller = new AuthController();
    $response = $controller->login($request);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success'] ?? false) {
        echo "LOGIN SUCCESS!\n";
        echo "User ID: " . $responseData['data']['user']['id'] . "\n";
        echo "User Name: " . $responseData['data']['user']['name'] . "\n";
        echo "Token generated: " . (isset($responseData['data']['token']) ? 'Yes' : 'No') . "\n";
    } else {
        echo "LOGIN FAILED!\n";
        echo "Error: " . ($responseData['error']['message'] ?? 'Unknown error') . "\n";
        if (isset($responseData['error']['details'])) {
            echo "Details: " . json_encode($responseData['error']['details']) . "\n";
        }
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nDone.\n";