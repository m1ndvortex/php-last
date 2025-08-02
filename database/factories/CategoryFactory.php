<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'name_persian' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'description_persian' => $this->faker->sentence(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'is_active' => true,
            'parent_id' => null,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category has a parent.
     */
    public function withParent($parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }
}
