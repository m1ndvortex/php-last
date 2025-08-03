<?php

namespace Database\Factories;

use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TwoFactorCodeFactory extends Factory
{
    protected $model = TwoFactorCode::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'type' => $this->faker->randomElement(['sms', 'email']),
            'expires_at' => $this->faker->dateTimeBetween('now', '+10 minutes'),
            'used' => false,
            'ip_address' => $this->faker->ipv4(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-10 minutes', '-1 minute'),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used' => true,
            'used_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    public function sms(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sms',
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'email',
        ]);
    }
}