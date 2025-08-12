<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use App\Services\InvoiceService;
use App\Services\IntegrationEventService;

class TestCrossModuleIntegration extends Command
{
    protected $signature = 'test:integration';
    protected $description = 'Test cross-module integration functionality';

    public function handle()
    {
        $this->info('Testing Cross-Module Integration...');

        try {
            // Create test user
            $user = User::first();
            if (!$user) {
                $user = User::factory()->create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ]);
                $this->info('Created test user');
            }

            // Authenticate as the test user
            auth()->login($user);

            // Create test customer
            $customer = Customer::factory()->create([
                'name' => 'Test Customer',
                'email' => 'customer@example.com',
            ]);
            $this->info('Created test customer');

            // Create test category and location
            $category = Category::factory()->create(['name' => 'Test Category']);
            $location = Location::factory()->create(['name' => 'Test Location']);

            // Create test inventory item
            $inventoryItem = InventoryItem::factory()->create([
                'name' => 'Test Gold Ring',
                'category_id' => $category->id,
                'location_id' => $location->id,
                'quantity' => 10,
                'unit_price' => 500,
                'cost_price' => 400,
                'reorder_level' => 5,
            ]);
            $this->info('Created test inventory item');

            // Test invoice creation with integration
            $invoiceService = app(InvoiceService::class);
            
            $invoiceData = [
                'customer_id' => $customer->id,
                'items' => [
                    [
                        'inventory_item_id' => $inventoryItem->id,
                        'quantity' => 2,
                        'unit_price' => 500,
                    ]
                ],
                'gold_pricing' => [
                    'gold_price_per_gram' => 0, // Use static pricing
                    'labor_percentage' => 10,
                    'profit_percentage' => 15,
                    'tax_percentage' => 9,
                ]
            ];

            $this->info('Creating invoice with integration...');
            $invoice = $invoiceService->createInvoice($invoiceData);

            // Verify integration results
            $inventoryItem->refresh();
            $this->info("Inventory updated: {$inventoryItem->quantity} units remaining (was 10)");

            $customer->refresh();
            $this->info("Customer updated: Last purchase date: {$customer->last_purchase_date}");

            // Test data consistency validation
            $integrationService = app(IntegrationEventService::class);
            $issues = $integrationService->validateDataConsistency();
            
            if (empty($issues)) {
                $this->info('✅ Data consistency validation passed - no issues found');
            } else {
                $this->warn('⚠️  Data consistency issues found:');
                foreach ($issues as $category => $categoryIssues) {
                    $this->warn("  {$category}:");
                    foreach ($categoryIssues as $issue) {
                        $this->warn("    - {$issue}");
                    }
                }
            }

            $this->info('✅ Cross-module integration test completed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Integration test failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}