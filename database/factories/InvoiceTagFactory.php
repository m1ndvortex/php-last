<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceTag>
 */
class InvoiceTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => \App\Models\Invoice::factory(),
            'tag' => $this->faker->randomElement(['jewelry', 'gold', 'silver', 'diamond', 'ring', 'necklace', 'bracelet', 'earrings']),
        ];
    }
}
