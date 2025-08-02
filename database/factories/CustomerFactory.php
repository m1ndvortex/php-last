<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'preferred_language' => fake()->randomElement(['en', 'fa']),
            'customer_type' => fake()->randomElement(array_keys(Customer::CUSTOMER_TYPES)),
            'credit_limit' => fake()->randomFloat(2, 0, 10000),
            'payment_terms' => fake()->numberBetween(15, 90),
            'notes' => fake()->optional()->paragraph(),
            'birthday' => fake()->optional()->dateTimeBetween('-80 years', '-18 years'),
            'anniversary' => fake()->optional()->dateTimeBetween('-30 years', 'now'),
            'is_active' => fake()->boolean(90), // 90% chance of being active
            'crm_stage' => fake()->randomElement(array_keys(Customer::CRM_STAGES)),
            'lead_source' => fake()->optional()->randomElement(array_keys(Customer::LEAD_SOURCES)),
            'tags' => fake()->optional()->randomElements(['vip', 'wholesale', 'loyal', 'new', 'referral'], fake()->numberBetween(0, 3)),
        ];
    }

    /**
     * Indicate that the customer should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the customer should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the customer should be a lead.
     */
    public function lead(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_stage' => 'lead',
        ]);
    }

    /**
     * Indicate that the customer should be a prospect.
     */
    public function prospect(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_stage' => 'prospect',
        ]);
    }

    /**
     * Indicate that the customer should be a customer.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'crm_stage' => 'customer',
        ]);
    }

    /**
     * Indicate that the customer should be retail type.
     */
    public function retail(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'retail',
        ]);
    }

    /**
     * Indicate that the customer should be wholesale type.
     */
    public function wholesale(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'wholesale',
        ]);
    }

    /**
     * Indicate that the customer should be VIP type.
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'vip',
        ]);
    }

    /**
     * Indicate that the customer should prefer English.
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferred_language' => 'en',
        ]);
    }

    /**
     * Indicate that the customer should prefer Persian.
     */
    public function persian(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferred_language' => 'fa',
        ]);
    }

    /**
     * Indicate that the customer should have an upcoming birthday.
     */
    public function withUpcomingBirthday(): static
    {
        return $this->state(fn (array $attributes) => [
            'birthday' => now()->addDays(fake()->numberBetween(1, 30))->subYears(fake()->numberBetween(20, 60)),
        ]);
    }

    /**
     * Indicate that the customer should have an upcoming anniversary.
     */
    public function withUpcomingAnniversary(): static
    {
        return $this->state(fn (array $attributes) => [
            'anniversary' => now()->addDays(fake()->numberBetween(1, 30))->subYears(fake()->numberBetween(1, 20)),
        ]);
    }
}
