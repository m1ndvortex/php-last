<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['storage', 'showcase', 'safe', 'exhibition'];
        
        return [
            'name' => $this->faker->words(2, true),
            'name_persian' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'description_persian' => $this->faker->sentence(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'type' => $this->faker->randomElement($types),
            'is_active' => true,
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the location is a showcase.
     */
    public function showcase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'showcase',
        ]);
    }

    /**
     * Indicate that the location is a safe.
     */
    public function safe(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'safe',
        ]);
    }
}
