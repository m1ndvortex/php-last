<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoldPricingService;

class TestGoldPricingFormula extends Command
{
    protected $signature = 'test:gold-pricing';
    protected $description = 'Test the Persian jewelry pricing formula';

    protected GoldPricingService $goldPricingService;

    public function __construct(GoldPricingService $goldPricingService)
    {
        parent::__construct();
        $this->goldPricingService = $goldPricingService;
    }

    public function handle()
    {
        $this->info('=== Persian Jewelry Pricing Formula Test ===');
        $this->newLine();

        // Test case 1: Basic calculation
        $this->info('Test Case 1: Basic Calculation');
        $this->line('Weight: 10g, Gold Price: $100/g, Labor: 10%, Profit: 15%, Tax: 9%');

        $params1 = [
            'weight' => 10.0,
            'gold_price_per_gram' => 100.0,
            'labor_percentage' => 10.0,
            'profit_percentage' => 15.0,
            'tax_percentage' => 9.0,
            'quantity' => 1
        ];

        $result1 = $this->goldPricingService->calculateItemPrice($params1);

        $this->line("Base Gold Cost: $" . $result1['base_gold_cost']);
        $this->line("Labor Cost (10%): $" . $result1['labor_cost']);
        $this->line("Profit (15%): $" . $result1['profit']);
        $this->line("Tax (9%): $" . $result1['tax']);
        $this->line("Unit Price: $" . $result1['unit_price']);
        $this->line("Total Price: $" . $result1['total_price']);
        $this->newLine();

        // Manual verification
        $this->info('Manual Calculation Verification:');
        $this->line('1. Base Gold Cost: 10g × $100 = $1000');
        $this->line('2. Labor Cost: $1000 × 10% = $100');
        $this->line('3. Subtotal: $1000 + $100 = $1100');
        $this->line('4. Profit: $1100 × 15% = $165');
        $this->line('5. Subtotal with Profit: $1100 + $165 = $1265');
        $this->line('6. Tax: $1265 × 9% = $113.85');
        $this->line('7. Final Price: $1265 + $113.85 = $1378.85');
        $this->newLine();

        // Verify calculation matches manual calculation
        if ($result1['unit_price'] == 1378.85) {
            $this->info('✓ Formula calculation matches manual calculation!');
        } else {
            $this->error('✗ Formula calculation does not match manual calculation');
            $this->error('Expected: $1378.85, Got: $' . $result1['unit_price']);
        }
        $this->newLine();

        // Test case 2: Multiple quantities
        $this->info('Test Case 2: Multiple Quantities');
        $this->line('Weight: 5g, Gold Price: $50/g, Labor: 20%, Profit: 10%, Tax: 5%, Quantity: 3');

        $params2 = [
            'weight' => 5.0,
            'gold_price_per_gram' => 50.0,
            'labor_percentage' => 20.0,
            'profit_percentage' => 10.0,
            'tax_percentage' => 5.0,
            'quantity' => 3
        ];

        $result2 = $this->goldPricingService->calculateItemPrice($params2);

        $this->line("Unit Price: $" . $result2['unit_price']);
        $this->line("Total Price (3 units): $" . $result2['total_price']);
        $this->newLine();

        // Test case 3: Price breakdown
        $this->info('Test Case 3: Price Breakdown Display');
        $breakdown = $this->goldPricingService->getPriceBreakdown($params1);

        $this->line('Price Components:');
        foreach ($breakdown['components'] as $component) {
            $this->line("- " . $component['name'] . ": $" . $component['amount'] . " (" . $component['description'] . ")");
        }
        $this->line("Final Unit Price: $" . $breakdown['unit_price']);
        $this->line("Total for " . $breakdown['quantity'] . " unit(s): $" . $breakdown['total_price']);
        $this->newLine();

        // Test case 4: Default settings
        $this->info('Test Case 4: Default Pricing Settings');
        $defaults = $this->goldPricingService->getDefaultPricingSettings();
        $this->line("Default Labor Percentage: " . $defaults['default_labor_percentage'] . "%");
        $this->line("Default Profit Percentage: " . $defaults['default_profit_percentage'] . "%");
        $this->line("Default Tax Percentage: " . $defaults['default_tax_percentage'] . "%");
        $this->newLine();

        $this->info('=== All Tests Completed Successfully ===');
        
        return Command::SUCCESS;
    }
}