<?php

require_once 'vendor/autoload.php';

use App\Services\Reports\FinancialReportGenerator;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Financial Report Generator...\n\n";

try {
    $generator = new FinancialReportGenerator();
    $generator->setSubtype('profit_loss');
    $generator->setDateRange('2025-08-01', '2025-08-31');
    $generator->setLanguage('en');
    
    $report = $generator->generate();
    
    echo "Report generated successfully!\n";
    echo "Title: " . $report['title'] . "\n";
    echo "Total Revenue: " . $report['summary']['total_revenue']['formatted'] . "\n";
    echo "Gross Profit: " . $report['summary']['gross_profit']['formatted'] . "\n";
    echo "Net Profit: " . $report['summary']['net_profit']['formatted'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}