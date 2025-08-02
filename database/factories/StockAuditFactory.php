<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use App\Models\StockAudit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockAudit>
 */
class StockAuditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_number' => 'AUD' . $this->faker->date('Ymd') . $this->faker->unique()->numberBetween(100, 999),
            'location_id' => $this->faker->boolean(70) ? Location::factory() : null,
            'status' => StockAudit::STATUS_PENDING,
            'audit_date' => $this->faker->dateTimeBetween('-1 month', '+1 week'),
            'auditor_id' => User::factory(),
            'notes' => $this->faker->boolean(60) ? $this->faker->sentence() : null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the audit is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockAudit::STATUS_IN_PROGRESS,
            'started_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the audit is completed.
     */
    public function completed(): static
    {
        $startedAt = $this->faker->dateTimeBetween('-1 month', '-1 day');
        
        return $this->state(fn (array $attributes) => [
            'status' => StockAudit::STATUS_COMPLETED,
            'started_at' => $startedAt,
            'completed_at' => $this->faker->dateTimeBetween($startedAt, 'now'),
        ]);
    }

    /**
     * Indicate that the audit is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockAudit::STATUS_CANCELLED,
        ]);
    }
}
