<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Batch PDF API...\n\n";

// Get some invoice IDs
$invoices = \App\Models\Invoice::take(3)->pluck('id')->toArray();
echo "Found " . count($invoices) . " invoices: " . json_encode($invoices) . "\n\n";

if (empty($invoices)) {
    echo "No invoices found! Cannot test batch PDF generation.\n";
    exit(1);
}

// Test the API endpoint
try {
    $request = new \Illuminate\Http\Request();
    $request->merge(['invoice_ids' => $invoices]);
    
    $controller = new \App\Http\Controllers\InvoiceController(
        new \App\Services\InvoiceService(),
        new \App\Services\PDFGenerationService()
    );
    
    $response = $controller->generateBatchPDFs($request);
    
    echo "API Response Status: " . $response->getStatusCode() . "\n";
    echo "API Response Content: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "Error testing API: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTest completed.\n";