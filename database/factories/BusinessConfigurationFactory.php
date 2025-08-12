<?php

namespace Database\Factories;

use App\Models\BusinessConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessConfigurationFactory extends Factory
{
    protected $model = BusinessConfiguration::class;

    public function definition(): array
    {
        return [
            'business_name' => $this->faker->company(),
            'business_name_persian' => 'شرکت نمونه',
            'address' => $this->faker->address(),
            'address_persian' => 'آدرس نمونه',
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'website' => $this->faker->url(),
            'tax_number' => $this->faker->numerify('##########'),
            'registration_number' => $this->faker->numerify('##########'),
            'default_currency' => 'USD',
            'default_language' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'default_labor_percentage' => $this->faker->randomFloat(2, 5, 20),
            'default_profit_percentage' => $this->faker->randomFloat(2, 10, 30),
            'default_tax_percentage' => $this->faker->randomFloat(2, 5, 15),
            'logo_path' => null,
            'invoice_terms' => $this->faker->paragraph(),
            'invoice_terms_persian' => 'شرایط و ضوابط فاکتور',
            'invoice_footer' => $this->faker->sentence(),
            'invoice_footer_persian' => 'پاورقی فاکتور',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}