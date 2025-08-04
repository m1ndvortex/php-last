<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\InvoiceTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecurringInvoice>
 */
class RecurringInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'template_id' => null,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly', 'quarterly', 'yearly']),
            'interval' => $this->faker->numberBetween(1, 3),
            'start_date' => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
            'end_date' => $this->faker->optional()->dateTimeBetween('+1 month', '+1 year'),
            'next_invoice_date' => Carbon::now()->addDays($this->faker->numberBetween(1, 30)),
            'max_invoices' => $this->faker->optional()->numberBetween(5, 50),
            'invoices_generated' => $this->faker->numberBetween(0, 5),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'language' => $this->faker->randomElement(['en', 'fa']),
            'is_active' => $this->faker->boolean(80),
            'invoice_data' => [
                'items' => [
                    [
                        'name' => $this->faker->words(3, true),
                        'quantity' => $this->faker->numberBetween(1, 10),
                        'unit_price' => $this->faker->randomFloat(2, 10, 500),
                        'total' => $this->faker->randomFloat(2, 10, 5000)
                    ]
                ],
                'notes' => $this->faker->optional()->sentence(),
                'terms' => $this->faker->optional()->paragraph()
            ]
        ];
    }

    /**
     * Indicate that the recurring invoice is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the recurring invoice is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the recurring invoice is due for generation.
     */
    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_invoice_date' => Carbon::now()->subDays(1),
            'is_active' => true,
        ]);
    }

    /**
     * Set the recurring invoice frequency.
     */
    public function frequency(string $frequency): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => $frequency,
        ]);
    }

    /**
     * Set the recurring invoice to monthly frequency.
     */
    public function monthly(): static
    {
        return $this->frequency('monthly');
    }

    /**
     * Set the recurring invoice to weekly frequency.
     */
    public function weekly(): static
    {
        return $this->frequency('weekly');
    }

    /**
     * Set the recurring invoice to yearly frequency.
     */
    public function yearly(): static
    {
        return $this->frequency('yearly');
    }
}
