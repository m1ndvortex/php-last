<?php

require_once 'vendor/autoload.php';

use App\Models\InvoiceTemplate;
use App\Http\Controllers\InvoiceTemplateController;
use App\Services\InvoiceTemplateService;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Invoice Template API...\n\n";

// Test 1: Check if templates exist in database
echo "1. Checking database templates:\n";
$templates = InvoiceTemplate::all();
echo "Found " . $templates->count() . " templates in database\n";
foreach ($templates as $template) {
    echo "- ID: {$template->id}, Name: {$template->name}, Language: {$template->language}\n";
}
echo "\n";

// Test 2: Test the service
echo "2. Testing InvoiceTemplateService:\n";
$service = new InvoiceTemplateService();
$serviceTemplates = $service->getTemplatesWithFilters([]);
echo "Service returned " . $serviceTemplates->count() . " templates\n";
echo "\n";

// Test 3: Test the controller
echo "3. Testing InvoiceTemplateController:\n";
$controller = new InvoiceTemplateController($service);
$request = new Request();
$response = $controller->index($request);
$responseData = json_decode($response->getContent(), true);

echo "Controller response status: " . $response->getStatusCode() . "\n";
echo "Response success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
if (isset($responseData['data']['data'])) {
    echo "Templates in response: " . count($responseData['data']['data']) . "\n";
} else {
    echo "No templates data in response\n";
    echo "Response structure: " . print_r($responseData, true) . "\n";
}

echo "\nTest completed.\n";