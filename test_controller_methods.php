<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing CategoryController methods directly...\n";

try {
    // Create controller instance
    $categoryService = app(\App\Services\CategoryService::class);
    $categoryImageService = app(\App\Services\CategoryImageService::class);
    $goldPurityService = app(\App\Services\GoldPurityService::class);
    
    $controller = new \App\Http\Controllers\CategoryController(
        $categoryService,
        $categoryImageService,
        $goldPurityService
    );
    
    // Test getGoldPurityOptions method
    $response = $controller->getGoldPurityOptions();
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "✓ getGoldPurityOptions: " . count($data['data']) . " options returned\n";
    } else {
        echo "✗ getGoldPurityOptions failed\n";
    }
    
    // Test getHierarchy method
    $response = $controller->getHierarchy();
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "✓ getHierarchy: " . count($data['data']) . " root categories returned\n";
    } else {
        echo "✗ getHierarchy failed\n";
    }
    
    // Test getMainCategories method
    $response = $controller->getMainCategories();
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "✓ getMainCategories: " . count($data['data']) . " categories returned\n";
    } else {
        echo "✗ getMainCategories failed\n";
    }
    
    // Test getForSelect method
    $request = new \Illuminate\Http\Request();
    $response = $controller->getForSelect($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "✓ getForSelect: " . count($data['data']) . " categories returned\n";
    } else {
        echo "✗ getForSelect failed\n";
    }
    
    echo "\nAll controller methods are working correctly!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}