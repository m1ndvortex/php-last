<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GoldPricingService;
use App\Models\BusinessConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class GoldPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GoldPricingService $goldPricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->goldPricingService = new GoldPricingService();
    }

    /** @test */
    public function it_calculates_item_price_using_persian_formula()
    {
        // Arrange
        $params = [
            'weight' => 10.0, // 10 grams
            'gold_price_per_gram' => 100.0, // $100 per gram
            'labor_percentage' => 10.0, // 10%
            'profit_percentage' => 15.0, // 15%
            'tax_percentage' => 9.0, // 9%
            'quantity' => 1
        ];

        // Act
        $result = $this->goldPricingService->calculateItemPrice($params);

        // Assert - Manual calculation verification
        // Base gold cost: 10g × $100 = $1000
        $expectedBaseGoldCost = 1000.0;
        
        // Labor cost: $1000 × 10% = $100
        $expectedLaborCost = 100.0;
        
        // Subtotal: $1000 + $100 = $1100
        $subtotal = 1100.0;
        
        // Profit: $1100 × 15% = $165
        $expectedProfit = 165.0;
        
        // Subtotal with profit: $1100 + $165 = $1265
        $subtotalWithProfit = 1265.0;
        
        // Tax: $1265 × 9% = $113.85
        $expectedTax = 113.85;
        
        // Final unit price: $1265 + $113.85 = $1378.85
        $expectedUnitPrice = 1378.85;
        
        // Total price for quantity 1: $1378.85
        $expectedTotalPrice = 1378.85;

        $this->assertEquals($expectedBaseGoldCost, $result['base_gold_cost']);
        $this->assertEquals($expectedLaborCost, $result['labor_cost']);
        $this->assertEquals($expectedProfit, $result['profit']);
        $this->assertEquals($expectedTax, $result['tax']);
        $this->assertEquals($expectedUnitPrice, $result['unit_price']);
        $this->assertEquals($expectedTotalPrice, $result['total_price']);
    }

    /** @test */
    public function it_calculates_price_for_multiple_quantities()
    {
        // Arrange
        $params = [
            'weight' => 5.0, // 5 grams
            'gold_price_per_gram' => 50.0, // $50 per gram
            'labor_percentage' => 20.0, // 20%
            'profit_percentage' => 10.0, // 10%
            'tax_percentage' => 5.0, // 5%
            'quantity' => 3 // 3 items
        ];

        // Act
        $result = $this->goldPricingService->calculateItemPrice($params);

        // Assert - Manual calculation verification
        // Base gold cost per unit: 5g × $50 = $250
        // Labor cost per unit: $250 × 20% = $50
        // Subtotal per unit: $250 + $50 = $300
        // Profit per unit: $300 × 10% = $30
        // Subtotal with profit per unit: $300 + $30 = $330
        // Tax per unit: $330 × 5% = $16.50
        // Unit price: $330 + $16.50 = $346.50
        // Total for 3 units: $346.50 × 3 = $1039.50

        $this->assertEquals(750.0, $result['base_gold_cost']); // $250 × 3
        $this->assertEquals(150.0, $result['labor_cost']); // $50 × 3
        $this->assertEquals(90.0, $result['profit']); // $30 × 3
        $this->assertEquals(49.5, $result['tax']); // $16.50 × 3
        $this->assertEquals(346.5, $result['unit_price']);
        $this->assertEquals(1039.5, $result['total_price']);
    }

    /** @test */
    public function it_handles_zero_percentages()
    {
        // Arrange
        $params = [
            'weight' => 2.0,
            'gold_price_per_gram' => 75.0,
            'labor_percentage' => 0.0,
            'profit_percentage' => 0.0,
            'tax_percentage' => 0.0,
            'quantity' => 1
        ];

        // Act
        $result = $this->goldPricingService->calculateItemPrice($params);

        // Assert - Only base gold cost should apply
        $expectedBaseGoldCost = 150.0; // 2g × $75
        
        $this->assertEquals($expectedBaseGoldCost, $result['base_gold_cost']);
        $this->assertEquals(0.0, $result['labor_cost']);
        $this->assertEquals(0.0, $result['profit']);
        $this->assertEquals(0.0, $result['tax']);
        $this->assertEquals($expectedBaseGoldCost, $result['unit_price']);
        $this->assertEquals($expectedBaseGoldCost, $result['total_price']);
    }

    /** @test */
    public function it_rounds_results_to_two_decimal_places()
    {
        // Arrange
        $params = [
            'weight' => 3.333, // Decimal weight
            'gold_price_per_gram' => 33.333, // Decimal price
            'labor_percentage' => 12.5,
            'profit_percentage' => 17.5,
            'tax_percentage' => 8.75,
            'quantity' => 1
        ];

        // Act
        $result = $this->goldPricingService->calculateItemPrice($params);

        // Assert - All values should be rounded to 2 decimal places
        $this->assertIsFloat($result['base_gold_cost']);
        $this->assertIsFloat($result['labor_cost']);
        $this->assertIsFloat($result['profit']);
        $this->assertIsFloat($result['tax']);
        $this->assertIsFloat($result['unit_price']);
        $this->assertIsFloat($result['total_price']);
        
        // Check that all values are properly rounded (verify by checking specific calculation)
        // Base gold cost: 3.333 × 33.333 = 110.998889 → should round to 111.10
        $this->assertEquals(111.10, $result['base_gold_cost']);
        
        // Verify that the values are reasonable and properly calculated
        $this->assertGreaterThan(0, $result['unit_price']);
        $this->assertGreaterThan(0, $result['total_price']);
        
        // Verify that all monetary values are rounded to 2 decimal places
        $this->assertEquals(round($result['base_gold_cost'], 2), $result['base_gold_cost']);
        $this->assertEquals(round($result['labor_cost'], 2), $result['labor_cost']);
        $this->assertEquals(round($result['profit'], 2), $result['profit']);
        $this->assertEquals(round($result['tax'], 2), $result['tax']);
        $this->assertEquals(round($result['unit_price'], 2), $result['unit_price']);
        $this->assertEquals(round($result['total_price'], 2), $result['total_price']);
    }

    /** @test */
    public function it_throws_exception_for_invalid_weight()
    {
        // Arrange
        $params = [
            'weight' => 0,
            'gold_price_per_gram' => 100.0,
            'labor_percentage' => 10.0,
            'profit_percentage' => 15.0,
            'tax_percentage' => 9.0,
            'quantity' => 1
        ];

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Weight, gold price per gram, and quantity must be greater than zero');
        
        $this->goldPricingService->calculateItemPrice($params);
    }

    /** @test */
    public function it_throws_exception_for_invalid_gold_price()
    {
        // Arrange
        $params = [
            'weight' => 10.0,
            'gold_price_per_gram' => 0,
            'labor_percentage' => 10.0,
            'profit_percentage' => 15.0,
            'tax_percentage' => 9.0,
            'quantity' => 1
        ];

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Weight, gold price per gram, and quantity must be greater than zero');
        
        $this->goldPricingService->calculateItemPrice($params);
    }

    /** @test */
    public function it_throws_exception_for_invalid_quantity()
    {
        // Arrange
        $params = [
            'weight' => 10.0,
            'gold_price_per_gram' => 100.0,
            'labor_percentage' => 10.0,
            'profit_percentage' => 15.0,
            'tax_percentage' => 9.0,
            'quantity' => 0
        ];

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Weight, gold price per gram, and quantity must be greater than zero');
        
        $this->goldPricingService->calculateItemPrice($params);
    }

    /** @test */
    public function it_returns_breakdown_information()
    {
        // Arrange
        $params = [
            'weight' => 5.0,
            'gold_price_per_gram' => 80.0,
            'labor_percentage' => 15.0,
            'profit_percentage' => 20.0,
            'tax_percentage' => 10.0,
            'quantity' => 2
        ];

        // Act
        $result = $this->goldPricingService->calculateItemPrice($params);

        // Assert
        $this->assertArrayHasKey('breakdown', $result);
        $breakdown = $result['breakdown'];
        
        $this->assertEquals(5.0, $breakdown['weight']);
        $this->assertEquals(80.0, $breakdown['gold_price_per_gram']);
        $this->assertEquals(15.0, $breakdown['labor_percentage']);
        $this->assertEquals(20.0, $breakdown['profit_percentage']);
        $this->assertEquals(10.0, $breakdown['tax_percentage']);
        $this->assertEquals(2, $breakdown['quantity']);
        
        $this->assertArrayHasKey('base_gold_cost_per_unit', $breakdown);
        $this->assertArrayHasKey('labor_cost_per_unit', $breakdown);
        $this->assertArrayHasKey('profit_per_unit', $breakdown);
        $this->assertArrayHasKey('tax_per_unit', $breakdown);
    }

    /** @test */
    public function it_gets_default_pricing_settings_from_database()
    {
        // Arrange
        BusinessConfiguration::setValue('default_labor_percentage', 12.5, 'float', 'pricing');
        BusinessConfiguration::setValue('default_profit_percentage', 18.0, 'float', 'pricing');
        BusinessConfiguration::setValue('default_tax_percentage', 7.5, 'float', 'pricing');

        // Act
        $result = $this->goldPricingService->getDefaultPricingSettings();

        // Assert
        $this->assertEquals(12.5, $result['default_labor_percentage']);
        $this->assertEquals(18.0, $result['default_profit_percentage']);
        $this->assertEquals(7.5, $result['default_tax_percentage']);
    }

    /** @test */
    public function it_returns_hardcoded_defaults_when_no_database_settings()
    {
        // Act - No database settings exist
        $result = $this->goldPricingService->getDefaultPricingSettings();

        // Assert
        $this->assertEquals(10.0, $result['default_labor_percentage']);
        $this->assertEquals(15.0, $result['default_profit_percentage']);
        $this->assertEquals(9.0, $result['default_tax_percentage']);
    }

    /** @test */
    public function it_provides_price_breakdown_for_display()
    {
        // Arrange
        $params = [
            'weight' => 8.0,
            'gold_price_per_gram' => 60.0,
            'labor_percentage' => 25.0,
            'profit_percentage' => 12.0,
            'tax_percentage' => 6.0,
            'quantity' => 1
        ];

        // Act
        $result = $this->goldPricingService->getPriceBreakdown($params);

        // Assert
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('unit_price', $result);
        $this->assertArrayHasKey('quantity', $result);
        $this->assertArrayHasKey('total_price', $result);
        
        $components = $result['components'];
        $this->assertCount(4, $components);
        
        // Check component structure
        foreach ($components as $component) {
            $this->assertArrayHasKey('name', $component);
            $this->assertArrayHasKey('amount', $component);
            $this->assertArrayHasKey('description', $component);
        }
        
        // Check component names
        $componentNames = array_column($components, 'name');
        $this->assertContains('Base Gold Cost', $componentNames);
        $this->assertContains('Labor Cost', $componentNames);
        $this->assertContains('Profit', $componentNames);
        $this->assertContains('Tax', $componentNames);
    }

    /** @test */
    public function it_validates_pricing_parameters()
    {
        // Test valid parameters
        $validParams = [
            'weight' => 5.0,
            'gold_price_per_gram' => 100.0,
            'quantity' => 2,
            'labor_percentage' => 10.0,
            'profit_percentage' => 15.0,
            'tax_percentage' => 9.0
        ];
        
        $errors = $this->goldPricingService->validatePricingParams($validParams);
        $this->assertEmpty($errors);
        
        // Test invalid parameters
        $invalidParams = [
            'weight' => 0,
            'gold_price_per_gram' => -10,
            'quantity' => -1,
            'labor_percentage' => -5,
            'profit_percentage' => -10,
            'tax_percentage' => -2
        ];
        
        $errors = $this->goldPricingService->validatePricingParams($invalidParams);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('weight', $errors);
        $this->assertArrayHasKey('gold_price_per_gram', $errors);
        $this->assertArrayHasKey('quantity', $errors);
        $this->assertArrayHasKey('labor_percentage', $errors);
        $this->assertArrayHasKey('profit_percentage', $errors);
        $this->assertArrayHasKey('tax_percentage', $errors);
    }

    /** @test */
    public function it_handles_missing_optional_parameters()
    {
        // Arrange - Only required parameters
        $params = [
            'weight' => 3.0,
            'gold_price_per_gram' => 90.0,
            'quantity' => 1
        ];

        // Act
        $result = $this->goldPricingService->calculateItemPrice($params);

        // Assert - Should use 0% for missing percentages
        $expectedBaseGoldCost = 270.0; // 3g × $90
        
        $this->assertEquals($expectedBaseGoldCost, $result['base_gold_cost']);
        $this->assertEquals(0.0, $result['labor_cost']);
        $this->assertEquals(0.0, $result['profit']);
        $this->assertEquals(0.0, $result['tax']);
        $this->assertEquals($expectedBaseGoldCost, $result['unit_price']);
        $this->assertEquals($expectedBaseGoldCost, $result['total_price']);
    }

    /** @test */
    public function it_logs_calculation_details()
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->with('Gold pricing calculation', \Mockery::type('array'));

        $params = [
            'weight' => 1.0,
            'gold_price_per_gram' => 100.0,
            'labor_percentage' => 10.0,
            'profit_percentage' => 15.0,
            'tax_percentage' => 9.0,
            'quantity' => 1
        ];

        // Act
        $this->goldPricingService->calculateItemPrice($params);

        // Assert - Log expectation is verified by Mockery
    }
}