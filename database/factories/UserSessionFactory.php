<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSessionFactory extends Factory
{
    protected $model = UserSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'platform' => $this->faker->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
            'location' => $this->faker->city() . ', ' . $this->faker->country(),
            'last_activity' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('now', '+2 hours'),
            'is_active' => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-2 hours', '-1 hour'),
            'is_active' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}