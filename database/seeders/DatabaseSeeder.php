<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CustomerSeeder::class,
            CategorySeeder::class,
            InventoryTestSeeder::class,
            InvoiceSeeder::class,
            AccountingSeeder::class,
        ]);
    }
}