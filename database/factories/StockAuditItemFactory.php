<?php

namespace Database\Factories;

use App\Models\StockAudit;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockAuditItem>
 */
class StockAuditItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $systemQuantity = $this->faker->randomFloat(3, 0, 100);
        $unitCost = $this->faker->randomFloat(2, 5, 100);
        
        return [
            'stock_audit_id' => StockAudit::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'system_quantity' => $systemQuantity,
            'physical_quantity' => null,
            'variance' => 0,
            'unit_cost' => $unitCost,
            'variance_value' => 0,
            'notes' => null,
            'is_counted' => false,
            'counted_at' => null,
        ];
    }

    /**
     * Indicate that the item has been counted.
     */
    public function counted(): static
    {
        return $this->state(function (array $attributes) {
            $systemQuantity = $attributes['system_quantity'];
            $physicalQuantity = $this->faker->randomFloat(3, max(0, $systemQuantity - 5), $systemQuantity + 5);
            $variance = $physicalQuantity - $systemQuantity;
            $varianceValue = $variance * $attributes['unit_cost'];
            
            return [
                'physical_quantity' => $physicalQuantity,
                'variance' => $variance,
                'variance_value' => $varianceValue,
                'is_counted' => true,
                'counted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            ];
        });
    }

    /**
     * Indicate that the item has a positive variance (overage).
     */
    public function withOverage(): static
    {
        return $this->state(function (array $attributes) {
            $systemQuantity = $attributes['system_quantity'];
            $physicalQuantity = $systemQuantity + $this->faker->randomFloat(3, 0.1, 5);
            $variance = $physicalQuantity - $systemQuantity;
            $varianceValue = $variance * $attributes['unit_cost'];
            
            return [
                'physical_quantity' => $physicalQuantity,
                'variance' => $variance,
                'variance_value' => $varianceValue,
                'is_counted' => true,
                'counted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Indicate that the item has a negative variance (shortage).
     */
    public function withShortage(): static
    {
        return $this->state(function (array $attributes) {
            $systemQuantity = $attributes['system_quantity'];
            $physicalQuantity = max(0, $systemQuantity - $this->faker->randomFloat(3, 0.1, 5));
            $variance = $physicalQuantity - $systemQuantity;
            $varianceValue = $variance * $attributes['unit_cost'];
            
            return [
                'physical_quantity' => $physicalQuantity,
                'variance' => $variance,
                'variance_value' => $varianceValue,
                'is_counted' => true,
                'counted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }
}
