<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(3, 0.1, 100);
        $unitPrice = $this->faker->randomFloat(2, 10, 1000);
        $totalPrice = $quantity * $unitPrice;

        return [
            'invoice_id' => \App\Models\Invoice::factory(),
            'inventory_item_id' => null,
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'gold_purity' => $this->faker->optional()->randomFloat(3, 10, 24),
            'weight' => $this->faker->optional()->randomFloat(3, 0.1, 100),
            'serial_number' => $this->faker->optional()->bothify('SN-####-????'),
        ];
    }
}
