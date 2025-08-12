<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense']);
        
        return [
            'code' => $this->faker->unique()->numerify('####'),
            'name' => $this->faker->words(3, true),
            'name_persian' => $this->faker->words(3, true),
            'type' => $type,
            'subtype' => $this->getSubtypeForType($type),
            'parent_id' => null,
            'is_active' => true,
            'description' => $this->faker->sentence(),
            'currency' => 'USD',
            'opening_balance' => $this->faker->randomFloat(2, 0, 10000),
        ];
    }

    /**
     * Get appropriate subtype for the given type
     */
    private function getSubtypeForType(string $type): string
    {
        $subtypes = [
            'asset' => ['current_asset', 'fixed_asset'],
            'liability' => ['current_liability', 'long_term_liability'],
            'equity' => ['owner_equity'],
            'revenue' => ['operating_revenue', 'other_revenue'],
            'expense' => ['operating_expense', 'other_expense'],
        ];

        return $this->faker->randomElement($subtypes[$type] ?? ['other_expense']);
    }

    /**
     * Indicate that the account is an asset account.
     */
    public function asset(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'asset',
        ]);
    }

    /**
     * Indicate that the account is a liability account.
     */
    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'liability',
        ]);
    }

    /**
     * Indicate that the account is an equity account.
     */
    public function equity(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'equity',
        ]);
    }

    /**
     * Indicate that the account is a revenue account.
     */
    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'revenue',
        ]);
    }

    /**
     * Indicate that the account is an expense account.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
        ]);
    }
}