<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'template_id' => null,
            'invoice_number' => 'INV-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'issue_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'subtotal' => $this->faker->randomFloat(2, 100, 5000),
            'tax_amount' => $this->faker->randomFloat(2, 10, 500),
            'discount_amount' => $this->faker->randomFloat(2, 0, 200),
            'total_amount' => $this->faker->randomFloat(2, 110, 5500),
            'status' => $this->faker->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']),
            'language' => $this->faker->randomElement(['en', 'fa']),
            'notes' => $this->faker->optional()->sentence(),
            'internal_notes' => $this->faker->optional()->sentence(),
            'pdf_path' => null,
            'sent_at' => null,
            'paid_at' => null,
        ];
    }
}
