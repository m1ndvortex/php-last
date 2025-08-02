<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user (or create one if none exists)
        $user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        // Create customers in different stages
        $leadCustomers = \App\Models\Customer::factory()
            ->count(10)
            ->lead()
            ->active()
            ->create();

        $prospectCustomers = \App\Models\Customer::factory()
            ->count(5)
            ->prospect()
            ->active()
            ->create();

        $customers = \App\Models\Customer::factory()
            ->count(15)
            ->customer()
            ->active()
            ->create();

        // Create some inactive customers
        \App\Models\Customer::factory()
            ->count(3)
            ->inactive()
            ->create();

        // Create customers with upcoming birthdays
        \App\Models\Customer::factory()
            ->count(3)
            ->withUpcomingBirthday()
            ->active()
            ->create();

        // Create customers with upcoming anniversaries
        \App\Models\Customer::factory()
            ->count(2)
            ->withUpcomingAnniversary()
            ->active()
            ->create();

        // Create some communications for random customers
        $allCustomers = \App\Models\Customer::all();
        
        foreach ($allCustomers->random(10) as $customer) {
            \App\Models\Communication::create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'type' => fake()->randomElement(['email', 'sms', 'whatsapp', 'phone', 'meeting', 'note']),
                'subject' => fake()->sentence(),
                'message' => fake()->paragraph(),
                'status' => fake()->randomElement(['draft', 'sent', 'delivered', 'read']),
                'sent_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            ]);
        }

        // Create some specific test customers
        $testCustomers = [
            [
                'name' => 'احمد محمدی',
                'email' => 'ahmad@example.com',
                'phone' => '+98-912-345-6789',
                'preferred_language' => 'fa',
                'customer_type' => 'retail',
                'crm_stage' => 'customer',
                'address' => 'تهران، خیابان ولیعصر، پلاک ۱۲۳',
                'notes' => 'مشتری وفادار - خریدار طلا و جواهرات',
            ],
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+1-555-123-4567',
                'preferred_language' => 'en',
                'customer_type' => 'wholesale',
                'crm_stage' => 'customer',
                'address' => '123 Main Street, New York, NY 10001',
                'notes' => 'Wholesale buyer - bulk orders',
            ],
            [
                'name' => 'فاطمه احمدی',
                'email' => 'fateme@example.com',
                'phone' => '+98-911-987-6543',
                'preferred_language' => 'fa',
                'customer_type' => 'vip',
                'crm_stage' => 'customer',
                'address' => 'اصفهان، خیابان چهارباغ، پلاک ۴۵۶',
                'notes' => 'مشتری VIP - سفارشات سفارشی',
                'tags' => ['vip', 'custom_orders', 'loyal'],
            ],
        ];

        foreach ($testCustomers as $customerData) {
            $customer = \App\Models\Customer::create($customerData);
            
            // Add some communications for test customers
            \App\Models\Communication::create([
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'type' => 'note',
                'subject' => 'Customer created',
                'message' => 'Customer profile created and initial contact established.',
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        $this->command->info('Created ' . \App\Models\Customer::count() . ' customers with communications.');
    }
}
