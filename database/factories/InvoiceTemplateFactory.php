<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceTemplate>
 */
class InvoiceTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Template',
            'description' => $this->faker->optional()->sentence(),
            'template_data' => [
                'header' => [
                    'type' => 'header',
                    'position' => ['x' => 0, 'y' => 0],
                    'size' => ['width' => 100, 'height' => 15],
                ],
                'invoice_info' => [
                    'type' => 'invoice_info',
                    'position' => ['x' => 0, 'y' => 15],
                    'size' => ['width' => 100, 'height' => 20],
                ],
                'items_table' => [
                    'type' => 'items_table',
                    'position' => ['x' => 0, 'y' => 35],
                    'size' => ['width' => 100, 'height' => 40],
                ],
                'totals' => [
                    'type' => 'totals',
                    'position' => ['x' => 60, 'y' => 75],
                    'size' => ['width' => 35, 'height' => 15],
                ],
            ],
            'language' => $this->faker->randomElement(['en', 'fa']),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
