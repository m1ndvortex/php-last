<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActivityService;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InventoryItem;

class TestActivityLogging extends Command
{
    protected $signature = 'activities:test-logging';
    protected $description = 'Test the enhanced activity logging system';

    public function handle()
    {
        $this->info('Testing Enhanced Activity Logging System...');
        
        $activityService = app(ActivityService::class);

        // Test 1: Manual activity logging
        $this->info('1. Testing manual activity logging...');
        $activityService->logActivity(
            'system_test',
            'Testing the activity logging system',
            null,
            'Test System',
            'completed',
            'system',
            null,
            ['test' => true, 'timestamp' => now()]
        );
        $this->info('✓ Manual activity logged');

        // Test 2: Test model activity logging (if we have data)
        $this->info('2. Testing model activity logging...');
        
        $customer = Customer::first();
        if ($customer) {
            $customer->logCustomActivity(
                'customer_test',
                "Testing activity logging for customer: {$customer->name}",
                'completed',
                ['test_type' => 'model_logging']
            );
            $this->info('✓ Customer activity logged');
        }

        $invoice = Invoice::first();
        if ($invoice) {
            $invoice->logCustomActivity(
                'invoice_test',
                "Testing activity logging for invoice: #{$invoice->invoice_number}",
                'completed',
                ['test_type' => 'model_logging']
            );
            $this->info('✓ Invoice activity logged');
        }

        // Test 3: Get recent activities
        $this->info('3. Testing activity retrieval...');
        $recentActivities = $activityService->getRecentActivities(5);
        $this->info('✓ Retrieved ' . count($recentActivities) . ' recent activities');

        // Test 4: Get activity counts
        $this->info('4. Testing activity counts...');
        $counts = $activityService->getActivityCounts();
        $this->info('✓ Today: ' . $counts['today']['total'] . ' activities');
        $this->info('✓ This week: ' . $counts['this_week']['total'] . ' activities');

        // Test 5: Get activities by type
        $this->info('5. Testing activities by type...');
        $testActivities = $activityService->getActivitiesByType('system_test', 3);
        $this->info('✓ Found ' . count($testActivities) . ' system_test activities');

        // Test 6: Get pending activities
        $this->info('6. Testing pending activities...');
        $pendingActivities = $activityService->getPendingActivities(5);
        $this->info('✓ Found ' . count($pendingActivities) . ' pending activities');

        // Display recent activities
        $this->info('');
        $this->info('Recent Activities:');
        $this->table(
            ['Type', 'Description', 'User', 'Time', 'Status'],
            array_map(function ($activity) {
                return [
                    $activity['type'],
                    substr($activity['description'], 0, 50) . (strlen($activity['description']) > 50 ? '...' : ''),
                    $activity['user'],
                    $activity['time'],
                    $activity['status'] ?? 'N/A'
                ];
            }, array_slice($recentActivities, 0, 5))
        );

        $this->info('');
        $this->info('✅ Activity logging system test completed successfully!');
        
        return 0;
    }
}