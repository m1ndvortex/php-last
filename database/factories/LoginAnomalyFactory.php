<?php

namespace Database\Factories;

use App\Models\LoginAnomaly;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoginAnomalyFactory extends Factory
{
    protected $model = LoginAnomaly::class;

    public function definition(): array
    {
        $types = ['suspicious_ip', 'new_device', 'rapid_attempts', 'geo_anomaly', 'time_anomaly', 'brute_force'];
        $severities = ['low', 'medium', 'high', 'critical'];
        
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement($types),
            'severity' => $this->faker->randomElement($severities),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'location' => $this->faker->city() . ', ' . $this->faker->country(),
            'detection_data' => [
                'timestamp' => now()->toISOString(),
                'additional_info' => $this->faker->sentence(),
            ],
            'is_resolved' => false,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => true,
            'resolved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'resolution_notes' => $this->faker->sentence(),
        ]);
    }

    public function falsePositive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => true,
            'is_false_positive' => true,
            'resolved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'resolution_notes' => 'Marked as false positive',
        ]);
    }

    public function highSeverity(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'high',
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }
}