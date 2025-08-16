<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "Checking users in database...\n";
$userCount = User::count();
echo "Users found: $userCount\n";

if ($userCount == 0) {
    echo "No users found. Creating test user...\n";
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'is_active' => true,
        'preferred_language' => 'en',
        'role' => 'admin'
    ]);
    echo "Test user created successfully! ID: {$user->id}\n";
} else {
    echo "Existing users:\n";
    $users = User::all(['id', 'name', 'email', 'is_active']);
    foreach ($users as $user) {
        $active = $user->is_active ? 'Yes' : 'No';
        echo "ID: {$user->id}, Name: {$user->name}, Email: {$user->email}, Active: $active\n";
    }
    
    // Check if test user exists
    $testUser = User::where('email', 'test@example.com')->first();
    if (!$testUser) {
        echo "Test user doesn't exist. Creating...\n";
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
            'preferred_language' => 'en',
            'role' => 'admin'
        ]);
        echo "Test user created successfully! ID: {$user->id}\n";
    } else {
        echo "Test user already exists. Updating password...\n";
        $testUser->password = bcrypt('password123');
        $testUser->is_active = true;
        $testUser->save();
        echo "Test user password updated!\n";
    }
}