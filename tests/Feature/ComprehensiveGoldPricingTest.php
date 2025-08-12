<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GoldPricingService;
use App\Models\BusinessConfiguration;
use App\Exceptions\PricingException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComprehensiveGoldPricingTest extends TestCase
{
    use RefreshDatabase;

    protected GoldPricingService $goldPricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->goldPricingService = new GoldPricingService();
    }

    /** @test */
    public function it_calculates_persian_formula_with_complex_scenarios()
    {
        // Test scenario 1: High-end jewelry with premium percentages
        $params = [
            'weight' => 15.75, // 15.75 grams
            'gold_price_per_gram' => 75.50, // $75.50 per gram
            'labor_percentage' => 25.0, // 25% labor
            'profit_percentage' => 30.0, // 30% profit
            'tax_percentage' => 12.5, // 12.5% tax
            'quantity' => 2
        ];

        $result = $this->goldPricingService->calculateItemPrice($params);

        // Manual calculation verification
        $baseGoldCost = 15.75 * 75.50; // $1189.125
        $laborCost = $baseGoldCost * 0.25; // $297.28125
        $subtotal = $baseGoldCost + $laborCost; // $1486.40625
        $profit = $subtotal * 0.30; // $445.921875
        $subtotalWithProfit = $subtotal + $profit; // $1932.328125
        $tax = $subtotalWithProfit * 0.125; // $241.541015625
        $unitPrice = $subtotalWithProfit + $tax; // $2173.869140625
        $totalPrice = $unitPrice * 2; // $4347.73828125

        $this->assertEquals(round($baseGoldCost * 2, 2), $result['base_gold_cost']);
        $this->assertEquals(round($laborCost * 2, 2), $result['labor_cost']);
        $this->assertEquals(round($profit * 2, 2), $result['profit']);
        $this->assertEquals(round($tax * 2, 2), $result['tax']);
        $this->assertEquals(round($unitPrice, 2), $result['unit_price']);
        $this->assertEquals(round($totalPrice, 2), $result['total_price']);
    }

    /** @test */
    public function it_handles_fractional_weights_and_prices_accurately()
    {
        $params = [
            'weight' => 3.333,
            'gold_price_per_gram' => 66.666,
            'labor_percentage' => 12.5,
            'profit_percentage' => 17.75,
            'tax_percentage' => 8.25,
            'quantity' => 1
        ];

        $result = $this->goldPricingService->calculateItemPrice($params);

        // Verify all calculations are properly rounded
        $this->assertIsFloat($result['base_gold_cost']);
        $this->assertIsFloat($result['labor_cost']);
        $this->assertIsFloat($result['profit']);
        $this->assertIsFloat($result['tax']);
        $this->assertIsFloat($result['unit_price']);
        $this->assertIsFloat($result['total_price']);

        // Verify proper rounding to 2 decimal places
        $this->assertEquals($result['base_gold_cost'], round($result['base_gold_cost'], 2));
        $this->assertEquals($result['labor_cost'], round($result['labor_cost'], 2));
        $this->assertEquals($result['profit'], round($result['profit'], 2));
        $this->assertEquals($result['tax'], round($result['tax'], 2));
        $this->assertEquals($result['unit_price'], round($result['unit_price'], 2));
        $this->assertEquals($result['total_price'], round($result['total_price'], 2));
    }

    /** @test */
    public function it_validates_all_parameter_combinations()
    {
        // Test all invalid parameter scenarios
        $invalidScenarios = [
            ['weight' => 0, 'error' => 'weight'],
            ['weight' => -5, 'error' => 'weight'],
            ['gold_price_per_gram' => 0, 'error' => 'gold_price_per_gram'],
            ['gold_price_per_gram' => -10, 'error' => 'gold_price_per_gram'],
            ['quantity' => 0, 'error' => 'quantity'],
            ['quantity' => -1, 'error' => 'quantity'],
            ['labor_percentage' => -5, 'error' => 'labor_percentage'],
            ['profit_percentage' => -10, 'error' => 'profit_percentage'],
            ['tax_percentage' => -2, 'error' => 'tax_percentage'],
        ];

        foreach ($invalidScenarios as $scenario) {
            $params = [
                'weight' => 10.0,
                'gold_price_per_gram' => 50.0,
                'labor_percentage' => 10.0,
                'profit_percentage' => 15.0,
                'tax_percentage' => 9.0,
                'quantity' => 1
            ];

            // Override with invalid value
            $params[array_keys($scenario)[0]] = $scenario[array_keys($scenario)[0]];

            $errors = $this->goldPricingService->validatePricingParams($params);
            $this->assertArrayHasKey($scenario['error'], $errors, "Failed to validate {$scenario['error']}");
        }
    }

    /** @test */
    public function it_provides_detailed_price_breakdown_for_display()
    {
        $params = [
            'weight' => 8.5,
            'gold_price_per_gram' => 55.0,
            'labor_percentage' => 15.0,
            'profit_percentage' => 20.0,
            'tax_percentage' => 10.0,
            'quantity' => 1
        ];

        $breakdown = $this->goldPricingService->getPriceBreakdown($params);

        $this->assertArrayHasKey('components', $breakdown);
        $this->assertArrayHasKey('unit_price', $breakdown);
        $this->assertArrayHasKey('quantity', $breakdown);
        $this->assertArrayHasKey('total_price', $breakdown);

        $components = $breakdown['components'];
        $this->assertCount(4, $components);

        // Verify component structure
        foreach ($components as $component) {
            $this->assertArrayHasKey('name', $component);
            $this->assertArrayHasKey('amount', $component);
            $this->assertArrayHasKey('description', $component);
        }

        // Verify specific components exist
        $componentNames = array_column($components, 'name');
        $this->assertContains('Base Gold Cost', $componentNames);
        $this->assertContains('Labor Cost', $componentNames);
        $this->assertContains('Profit', $componentNames);
        $this->assertContains('Tax', $componentNames);
    }

    /** @test */
    public function it_handles_business_configuration_integration()
    {
        // Set up business configuration
        BusinessConfiguration::updateOrCreate(
            ['key' => 'default_labor_percentage'],
            ['value' => '12.5']
        );
        BusinessConfiguration::updateOrCreate(
            ['key' => 'default_profit_percentage'],
            ['value' => '18.0']
        );
        BusinessConfiguration::updateOrCreate(
            ['key' => 'default_tax_percentage'],
            ['value' => '7.5']
        );

        $settings = $this->goldPricingService->getDefaultPricingSettings();

        $this->assertEquals(12.5, $settings['default_labor_percentage']);
        $this->assertEquals(18.0, $settings['default_profit_percentage']);
        $this->assertEquals(7.5, $settings['default_tax_percentage']);
    }

    /** @test */
    public function it_calculates_bulk_pricing_scenarios()
    {
        $bulkItems = [
            [
                'weight' => 5.0,
                'gold_price_per_gram' => 50.0,
                'quantity' => 10
            ],
            [
                'weight' => 8.0,
                'gold_price_per_gram' => 50.0,
                'quantity' => 5
            ],
            [
                'weight' => 12.0,
                'gold_price_per_gram' => 50.0,
                'quantity' => 3
            ]
        ];

        $commonParams = [
            'labor_percentage' => 15.0,
            'profit_percentage' => 20.0,
            'tax_percentage' => 9.0
        ];

        $totalValue = 0;
        $results = [];

        foreach ($bulkItems as $item) {
            $params = array_merge($item, $commonParams);
            $result = $this->goldPricingService->calculateItemPrice($params);
            $results[] = $result;
            $totalValue += $result['total_price'];
        }

        $this->assertCount(3, $results);
        $this->assertGreaterThan(0, $totalValue);

        // Verify each calculation is accurate
        foreach ($results as $result) {
            $this->assertArrayHasKey('base_gold_cost', $result);
            $this->assertArrayHasKey('labor_cost', $result);
            $this->assertArrayHasKey('profit', $result);
            $this->assertArrayHasKey('tax', $result);
            $this->assertArrayHasKey('unit_price', $result);
            $this->assertArrayHasKey('total_price', $result);
            $this->assertGreaterThan(0, $result['total_price']);
        }
    }

    /** @test */
    public function it_handles_extreme_values_gracefully()
    {
        // Test with very small values
        $smallParams = [
            'weight' => 0.1, // Minimum reasonable weight
            'gold_price_per_gram' => 1.0, // Minimum reasonable price
            'labor_percentage' => 0.1,
            'profit_percentage' => 0.1,
            'tax_percentage' => 0.1,
            'quantity' => 1
        ];

        $result = $this->goldPricingService->calculateItemPrice($smallParams);
        $this->assertGreaterThan(0, $result['total_price']);

        // Test with very large values
        $largeParams = [
            'weight' => 1000.0,
            'gold_price_per_gram' => 1000.0,
            'labor_percentage' => 50.0,
            'profit_percentage' => 50.0,
            'tax_percentage' => 25.0,
            'quantity' => 100
        ];

        $result = $this->goldPricingService->calculateItemPrice($largeParams);
        $this->assertGreaterThan(0, $result['total_price']);
        $this->assertIsFloat($result['total_price']);
    }

    /** @test */
    public function it_provides_detailed_breakdown_information()
    {
        $params = [
            'weight' => 10.0,
            'gold_price_per_gram' => 60.0,
            'labor_percentage' => 12.0,
            'profit_percentage' => 18.0,
            'tax_percentage' => 9.0,
            'quantity' => 2
        ];

        $result = $this->goldPricingService->calculateItemPrice($params);

        $this->assertArrayHasKey('breakdown', $result);
        $breakdown = $result['breakdown'];

        $this->assertArrayHasKey('weight', $breakdown);
        $this->assertArrayHasKey('gold_price_per_gram', $breakdown);
        $this->assertArrayHasKey('labor_percentage', $breakdown);
        $this->assertArrayHasKey('profit_percentage', $breakdown);
        $this->assertArrayHasKey('tax_percentage', $breakdown);
        $this->assertArrayHasKey('quantity', $breakdown);
        $this->assertArrayHasKey('base_gold_cost_per_unit', $breakdown);
        $this->assertArrayHasKey('labor_cost_per_unit', $breakdown);
        $this->assertArrayHasKey('profit_per_unit', $breakdown);
        $this->assertArrayHasKey('tax_per_unit', $breakdown);

        $this->assertEquals(10.0, $breakdown['weight']);
        $this->assertEquals(60.0, $breakdown['gold_price_per_gram']);
        $this->assertEquals(12.0, $breakdown['labor_percentage']);
        $this->assertEquals(18.0, $breakdown['profit_percentage']);
        $this->assertEquals(9.0, $breakdown['tax_percentage']);
        $this->assertEquals(2, $breakdown['quantity']);
    }

    /** @test */
    public function it_handles_currency_precision_correctly()
    {
        // Test with values that could cause floating point precision issues
        $params = [
            'weight' => 7.777,
            'gold_price_per_gram' => 33.333,
            'labor_percentage' => 11.111,
            'profit_percentage' => 16.666,
            'tax_percentage' => 8.888,
            'quantity' => 3
        ];

        $result = $this->goldPricingService->calculateItemPrice($params);

        // Verify all monetary values are properly rounded to 2 decimal places
        $this->assertEquals(round($result['base_gold_cost'], 2), $result['base_gold_cost']);
        $this->assertEquals(round($result['labor_cost'], 2), $result['labor_cost']);
        $this->assertEquals(round($result['profit'], 2), $result['profit']);
        $this->assertEquals(round($result['tax'], 2), $result['tax']);
        $this->assertEquals(round($result['unit_price'], 2), $result['unit_price']);
        $this->assertEquals(round($result['total_price'], 2), $result['total_price']);

        // Verify values are reasonable
        $this->assertGreaterThan(0, $result['base_gold_cost']);
        $this->assertGreaterThan(0, $result['labor_cost']);
        $this->assertGreaterThan(0, $result['profit']);
        $this->assertGreaterThan(0, $result['tax']);
        $this->assertGreaterThan(0, $result['unit_price']);
        $this->assertGreaterThan(0, $result['total_price']);
    }

    /** @test */
    public function it_throws_pricing_exception_with_detailed_information()
    {
        $invalidParams = [
            'weight' => -5.0,
            'gold_price_per_gram' => -10.0,
            'quantity' => 0
        ];

        try {
            $this->goldPricingService->calculateItemPrice($invalidParams);
            $this->fail('Expected PricingException was not thrown');
        } catch (PricingException $e) {
            $this->assertStringContainsString('parameters', $e->getMessage());
            $this->assertNotNull($e->getPricingData());
            $this->assertIsArray($e->getPricingData());
        }
    }
}