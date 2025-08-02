<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\User;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            InventoryMovement::TYPE_IN,
            InventoryMovement::TYPE_OUT,
            InventoryMovement::TYPE_TRANSFER,
            InventoryMovement::TYPE_ADJUSTMENT,
            InventoryMovement::TYPE_WASTAGE,
            InventoryMovement::TYPE_PRODUCTION,
        ];

        return [
            'inventory_item_id' => InventoryItem::factory(),
            'from_location_id' => $this->faker->boolean(60) ? Location::factory() : null,
            'to_location_id' => $this->faker->boolean(60) ? Location::factory() : null,
            'type' => $this->faker->randomElement($types),
            'quantity' => $this->faker->randomFloat(3, 0.1, 50),
            'unit_cost' => $this->faker->randomFloat(2, 5, 100),
            'reference_type' => $this->faker->boolean(70) ? $this->faker->randomElement(['invoice', 'purchase', 'audit', 'production']) : null,
            'reference_id' => $this->faker->boolean(70) ? $this->faker->numberBetween(1, 1000) : null,
            'batch_number' => $this->faker->boolean(40) ? $this->faker->bothify('B###??') : null,
            'notes' => $this->faker->boolean(50) ? $this->faker->sentence() : null,
            'user_id' => User::factory(),
            'movement_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that this is an inbound movement.
     */
    public function inbound(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InventoryMovement::TYPE_IN,
            'from_location_id' => null,
            'to_location_id' => Location::factory(),
        ]);
    }

    /**
     * Indicate that this is an outbound movement.
     */
    public function outbound(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InventoryMovement::TYPE_OUT,
            'from_location_id' => Location::factory(),
            'to_location_id' => null,
        ]);
    }

    /**
     * Indicate that this is a transfer movement.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InventoryMovement::TYPE_TRANSFER,
            'from_location_id' => Location::factory(),
            'to_location_id' => Location::factory(),
        ]);
    }

    /**
     * Indicate that this is an adjustment movement.
     */
    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InventoryMovement::TYPE_ADJUSTMENT,
            'reference_type' => 'manual_adjustment',
        ]);
    }

    /**
     * Indicate that this is a wastage movement.
     */
    public function wastage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InventoryMovement::TYPE_WASTAGE,
            'from_location_id' => Location::factory(),
            'to_location_id' => null,
            'reference_type' => 'production_wastage',
        ]);
    }

    /**
     * Indicate that this is a production movement.
     */
    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => InventoryMovement::TYPE_PRODUCTION,
            'from_location_id' => null,
            'to_location_id' => Location::factory(),
            'reference_type' => 'production',
        ]);
    }
}
