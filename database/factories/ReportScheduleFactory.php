<?php

namespace Database\Factories;

use App\Models\ReportSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportSchedule>
 */
class ReportScheduleFactory extends Factory
{
    protected $model = ReportSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['sales', 'inventory', 'financial', 'customer'];
        $subtypes = [
            'sales' => ['summary', 'detailed', 'by_period', 'by_customer', 'by_product'],
            'inventory' => ['stock_levels', 'movements', 'valuation', 'aging', 'reorder'],
            'financial' => ['profit_loss', 'balance_sheet', 'cash_flow', 'trial_balance'],
            'customer' => ['aging', 'purchase_history', 'communication_log', 'analytics']
        ];
        
        $type = $this->faker->randomElement($types);
        $subtype = $this->faker->randomElement($subtypes[$type]);
        
        return [
            'name' => $this->faker->sentence(3),
            'type' => $type,
            'subtype' => $subtype,
            'parameters' => [
                'filters' => [],
                'language' => $this->faker->randomElement(['en', 'fa'])
            ],
            'schedule' => [
                'frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly', 'quarterly']),
                'time' => $this->faker->time('H:i')
            ],
            'delivery' => [
                'method' => $this->faker->randomElement(['email', 'download']),
                'recipients' => $this->faker->randomElement([
                    [$this->faker->email],
                    [$this->faker->email, $this->faker->email]
                ])
            ],
            'is_active' => $this->faker->boolean(80),
            'next_run_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'last_run_at' => $this->faker->optional(0.6)->dateTimeBetween('-1 month', 'now')
        ];
    }

    /**
     * Indicate that the report schedule is due for execution.
     */
    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_run_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'is_active' => true
        ]);
    }

    /**
     * Indicate that the report schedule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false
        ]);
    }
}
