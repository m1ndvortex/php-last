<?php

// Test script to debug inventory creation issue
require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\InventoryController;
use App\Services\InventoryService;

// Create a mock request with the data that should work
$requestData = [
    'name' => 'Test Gold Ring',
    'name_persian' => 'حلقه طلا تست',
    'sku' => 'TEST-001',
    'category_id' => 11, // Gold Rings
    'location_id' => 7,  // Main Showcase
    'quantity' => 10,
    'unit_price' => 1000,
    'cost_price' => 800,
    'gold_purity' => 14.0,
    'weight' => 5.5,
    'is_active' => true,
    'track_serial' => false,
    'track_batch' => false,
];

echo "Testing inventory creation with data:\n";
print_r($requestData);

try {
    // Create a request object
    $request = new Request($requestData);
    
    // Create controller instance
    $inventoryService = app(InventoryService::class);
    $controller = new InventoryController($inventoryService);
    
    // Try to create the item
    $response = $controller->store($request);
    
    echo "Success! Response:\n";
    echo $response->getContent();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}