<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $events = ['created', 'updated', 'deleted', 'viewed', 'exported', 'login', 'logout'];
        $auditableTypes = ['Customer', 'Invoice', 'InventoryItem', 'Transaction', 'User'];
        $severities = ['info', 'warning', 'error', 'critical'];
        
        return [
            'user_id' => User::factory(),
            'event' => $this->faker->randomElement($events),
            'auditable_type' => $this->faker->randomElement($auditableTypes),
            'auditable_id' => $this->faker->numberBetween(1, 100),
            'old_values' => $this->faker->randomElement([null, ['field' => 'old_value']]),
            'new_values' => $this->faker->randomElement([null, ['field' => 'new_value']]),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'url' => $this->faker->url(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'request_data' => $this->faker->randomElement([null, ['param' => 'value']]),
            'description' => $this->faker->sentence(),
            'severity' => $this->faker->randomElement($severities),
            'metadata' => $this->faker->randomElement([null, ['key' => 'value']]),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function withEvent(string $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => $event,
        ]);
    }

    public function withSeverity(string $severity): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => $severity,
        ]);
    }

    public function info(): static
    {
        return $this->withSeverity('info');
    }

    public function warning(): static
    {
        return $this->withSeverity('warning');
    }

    public function error(): static
    {
        return $this->withSeverity('error');
    }

    public function critical(): static
    {
        return $this->withSeverity('critical');
    }
}