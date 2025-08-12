<?php

namespace App\Services;

use App\Models\BusinessConfiguration;
use App\Exceptions\PricingException;
use Illuminate\Support\Facades\Log;

class GoldPricingService
{
    /**
     * Calculate item price using Persian jewelry formula:
     * Weight × (Gold Price + Labor Cost + Profit + Tax) = Final Price
     *
     * @param array $params
     * @return array
     * @throws PricingException
     */
    public function calculateItemPrice(array $params): array
    {
        try {
            // Validate parameters first
            $validationErrors = $this->validatePricingParams($params);
            if (!empty($validationErrors)) {
                throw new PricingException(
                    __('errors.pricing.invalid_formula_parameters'),
                    ['validation_errors' => $validationErrors]
                );
            }

            $weight = (float) ($params['weight'] ?? 0);
            $goldPricePerGram = (float) ($params['gold_price_per_gram'] ?? 0);
            $laborPercentage = (float) ($params['labor_percentage'] ?? 0);
            $profitPercentage = (float) ($params['profit_percentage'] ?? 0);
            $taxPercentage = (float) ($params['tax_percentage'] ?? 0);
            $quantity = (int) ($params['quantity'] ?? 1);
            
            // Additional validation
            if ($weight <= 0) {
                throw new PricingException(
                    __('errors.pricing.zero_weight_not_allowed'),
                    ['weight' => $weight]
                );
            }
            
            if ($goldPricePerGram <= 0) {
                throw new PricingException(
                    __('errors.pricing.invalid_gold_price'),
                    ['gold_price_per_gram' => $goldPricePerGram]
                );
            }
            
            if ($quantity <= 0) {
                throw new PricingException(
                    __('errors.pricing.invalid_quantity'),
                    ['quantity' => $quantity]
                );
            }
        
        // Persian jewelry pricing formula implementation
        
        // Step 1: Base gold cost (Weight × Gold Price per gram)
        $baseGoldCost = $weight * $goldPricePerGram;
        
        // Step 2: Labor cost (percentage of base gold cost)
        $laborCost = $baseGoldCost * ($laborPercentage / 100);
        
        // Step 3: Subtotal before profit and tax
        $subtotal = $baseGoldCost + $laborCost;
        
        // Step 4: Profit (percentage of subtotal)
        $profit = $subtotal * ($profitPercentage / 100);
        
        // Step 5: Subtotal with profit
        $subtotalWithProfit = $subtotal + $profit;
        
        // Step 6: Tax (percentage of subtotal with profit)
        $tax = $subtotalWithProfit * ($taxPercentage / 100);
        
        // Step 7: Final price per unit
        $unitPrice = $subtotalWithProfit + $tax;
        
        // Step 8: Total price for quantity
        $totalPrice = $unitPrice * $quantity;
        
        Log::info('Gold pricing calculation', [
            'weight' => $weight,
            'gold_price_per_gram' => $goldPricePerGram,
            'labor_percentage' => $laborPercentage,
            'profit_percentage' => $profitPercentage,
            'tax_percentage' => $taxPercentage,
            'quantity' => $quantity,
            'base_gold_cost' => $baseGoldCost,
            'labor_cost' => $laborCost,
            'profit' => $profit,
            'tax' => $tax,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice
        ]);
        
            // Validate calculated prices are not negative
            if ($unitPrice < 0 || $totalPrice < 0) {
                throw new PricingException(
                    __('errors.pricing.negative_price_not_allowed'),
                    [
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'params' => $params
                    ]
                );
            }

            return [
                'base_gold_cost' => round($baseGoldCost * $quantity, 2),
                'labor_cost' => round($laborCost * $quantity, 2),
                'profit' => round($profit * $quantity, 2),
                'tax' => round($tax * $quantity, 2),
                'unit_price' => round($unitPrice, 2),
                'total_price' => round($totalPrice, 2),
                'breakdown' => [
                    'weight' => $weight,
                    'gold_price_per_gram' => $goldPricePerGram,
                    'labor_percentage' => $laborPercentage,
                    'profit_percentage' => $profitPercentage,
                    'tax_percentage' => $taxPercentage,
                    'quantity' => $quantity,
                    'base_gold_cost_per_unit' => round($baseGoldCost, 2),
                    'labor_cost_per_unit' => round($laborCost, 2),
                    'profit_per_unit' => round($profit, 2),
                    'tax_per_unit' => round($tax, 2)
                ]
            ];
        } catch (PricingException $e) {
            Log::error('Pricing calculation failed', [
                'params' => $params,
                'error' => $e->getMessage(),
                'pricing_data' => $e->getPricingData()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error in pricing calculation', [
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new PricingException(
                __('errors.pricing.calculation_failed'),
                ['params' => $params, 'error' => $e->getMessage()]
            );
        }
    }
    
    /**
     * Get default pricing settings from business configuration
     *
     * @return array
     */
    public function getDefaultPricingSettings(): array
    {
        try {
            $laborPercentage = BusinessConfiguration::getValue('default_labor_percentage', 10.0);
            $profitPercentage = BusinessConfiguration::getValue('default_profit_percentage', 15.0);
            $taxPercentage = BusinessConfiguration::getValue('default_tax_percentage', 9.0);
            
            return [
                'default_labor_percentage' => (float) $laborPercentage,
                'default_profit_percentage' => (float) $profitPercentage,
                'default_tax_percentage' => (float) $taxPercentage
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to load default pricing settings from database', [
                'error' => $e->getMessage()
            ]);
            
            return $this->getHardcodedDefaults();
        }
    }
    
    /**
     * Get hardcoded default settings as fallback
     *
     * @return array
     */
    private function getHardcodedDefaults(): array
    {
        return [
            'default_labor_percentage' => 10.0,
            'default_profit_percentage' => 15.0,
            'default_tax_percentage' => 9.0
        ];
    }
    
    /**
     * Calculate price breakdown for display purposes
     *
     * @param array $params
     * @return array
     */
    public function getPriceBreakdown(array $params): array
    {
        $calculation = $this->calculateItemPrice($params);
        
        return [
            'components' => [
                [
                    'name' => 'Base Gold Cost',
                    'amount' => $calculation['breakdown']['base_gold_cost_per_unit'],
                    'description' => "Weight ({$calculation['breakdown']['weight']}g) × Gold Price ({$calculation['breakdown']['gold_price_per_gram']} per gram)"
                ],
                [
                    'name' => 'Labor Cost',
                    'amount' => $calculation['breakdown']['labor_cost_per_unit'],
                    'description' => "{$calculation['breakdown']['labor_percentage']}% of base gold cost"
                ],
                [
                    'name' => 'Profit',
                    'amount' => $calculation['breakdown']['profit_per_unit'],
                    'description' => "{$calculation['breakdown']['profit_percentage']}% of subtotal"
                ],
                [
                    'name' => 'Tax',
                    'amount' => $calculation['breakdown']['tax_per_unit'],
                    'description' => "{$calculation['breakdown']['tax_percentage']}% of subtotal with profit"
                ]
            ],
            'unit_price' => $calculation['unit_price'],
            'quantity' => $calculation['breakdown']['quantity'],
            'total_price' => $calculation['total_price']
        ];
    }
    
    /**
     * Validate pricing parameters
     *
     * @param array $params
     * @return array
     */
    public function validatePricingParams(array $params): array
    {
        $errors = [];
        
        if (!isset($params['weight']) || !is_numeric($params['weight']) || $params['weight'] <= 0) {
            $errors['weight'] = __('errors.pricing.zero_weight_not_allowed');
        }
        
        if (!isset($params['gold_price_per_gram']) || !is_numeric($params['gold_price_per_gram']) || $params['gold_price_per_gram'] <= 0) {
            $errors['gold_price_per_gram'] = __('errors.pricing.invalid_gold_price');
        }
        
        if (!isset($params['quantity']) || !is_numeric($params['quantity']) || $params['quantity'] <= 0) {
            $errors['quantity'] = 'Quantity must be greater than zero';
        }
        
        if (isset($params['labor_percentage']) && (!is_numeric($params['labor_percentage']) || $params['labor_percentage'] < 0)) {
            $errors['labor_percentage'] = __('errors.pricing.invalid_percentage');
        }
        
        if (isset($params['profit_percentage']) && (!is_numeric($params['profit_percentage']) || $params['profit_percentage'] < 0)) {
            $errors['profit_percentage'] = __('errors.pricing.invalid_percentage');
        }
        
        if (isset($params['tax_percentage']) && (!is_numeric($params['tax_percentage']) || $params['tax_percentage'] < 0)) {
            $errors['tax_percentage'] = __('errors.pricing.invalid_percentage');
        }
        
        // Check for extremely high percentages that might indicate input errors
        if (isset($params['labor_percentage']) && $params['labor_percentage'] > 1000) {
            $errors['labor_percentage'] = 'Labor percentage seems unusually high (>1000%)';
        }
        
        if (isset($params['profit_percentage']) && $params['profit_percentage'] > 1000) {
            $errors['profit_percentage'] = 'Profit percentage seems unusually high (>1000%)';
        }
        
        if (isset($params['tax_percentage']) && $params['tax_percentage'] > 100) {
            $errors['tax_percentage'] = 'Tax percentage seems unusually high (>100%)';
        }
        
        return $errors;
    }
}