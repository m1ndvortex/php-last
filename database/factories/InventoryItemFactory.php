<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'name_persian' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'description_persian' => $this->faker->sentence(),
            'sku' => strtoupper($this->faker->unique()->bothify('???###')),
            'category_id' => Category::factory(),
            'location_id' => Location::factory(),
            'quantity' => $this->faker->randomFloat(3, 0, 100),
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'cost_price' => $this->faker->randomFloat(2, 5, 800),
            'gold_purity' => $this->faker->boolean(30) ? $this->faker->randomFloat(3, 14, 24) : null,
            'weight' => $this->faker->boolean(50) ? $this->faker->randomFloat(3, 0.1, 50) : null,
            'serial_number' => $this->faker->boolean(40) ? $this->faker->unique()->numerify('SN########') : null,
            'batch_number' => $this->faker->boolean(60) ? $this->faker->bothify('B###??') : null,
            'expiry_date' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween('now', '+2 years') : null,
            'minimum_stock' => $this->faker->randomFloat(3, 0, 10),
            'maximum_stock' => $this->faker->boolean(70) ? $this->faker->randomFloat(3, 50, 200) : null,
            'is_active' => true,
            'track_serial' => $this->faker->boolean(30),
            'track_batch' => $this->faker->boolean(40),
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the item is a gold item.
     */
    public function gold(): static
    {
        return $this->state(fn (array $attributes) => [
            'gold_purity' => $this->faker->randomFloat(3, 14, 24),
            'weight' => $this->faker->randomFloat(3, 1, 20),
        ]);
    }

    /**
     * Indicate that the item has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->randomFloat(3, 0, 5),
            'minimum_stock' => $this->faker->randomFloat(3, 10, 20),
        ]);
    }

    /**
     * Indicate that the item is expiring soon.
     */
    public function expiring(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('now', '+30 days'),
        ]);
    }

    /**
     * Indicate that the item is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the item tracks serial numbers.
     */
    public function withSerial(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_serial' => true,
            'serial_number' => $this->faker->unique()->numerify('SN########'),
        ]);
    }

    /**
     * Indicate that the item tracks batch numbers.
     */
    public function withBatch(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_batch' => true,
            'batch_number' => $this->faker->bothify('B###??'),
        ]);
    }
}
