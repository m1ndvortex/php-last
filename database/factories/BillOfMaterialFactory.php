<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillOfMaterial>
 */
class BillOfMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'finished_item_id' => InventoryItem::factory(),
            'component_item_id' => InventoryItem::factory(),
            'quantity_required' => $this->faker->randomFloat(3, 0.1, 10),
            'wastage_percentage' => $this->faker->randomFloat(2, 0, 20),
            'is_active' => true,
            'notes' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
        ];
    }

    /**
     * Indicate that the BOM entry is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the BOM entry has no wastage.
     */
    public function noWastage(): static
    {
        return $this->state(fn (array $attributes) => [
            'wastage_percentage' => 0,
        ]);
    }

    /**
     * Indicate that the BOM entry has high wastage.
     */
    public function highWastage(): static
    {
        return $this->state(fn (array $attributes) => [
            'wastage_percentage' => $this->faker->randomFloat(2, 15, 30),
        ]);
    }
}
