<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Location;
use App\Models\InventoryItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventoryTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create categories
        $goldCategory = Category::factory()->create([
            'name' => 'Gold Jewelry',
            'name_persian' => 'جواهرات طلا',
            'code' => 'GLD',
        ]);

        $silverCategory = Category::factory()->create([
            'name' => 'Silver Jewelry',
            'name_persian' => 'جواهرات نقره',
            'code' => 'SLV',
        ]);

        // Create locations
        $showcase = Location::factory()->create([
            'name' => 'Main Showcase',
            'name_persian' => 'ویترین اصلی',
            'code' => 'MS1',
            'type' => 'showcase',
        ]);

        $storage = Location::factory()->create([
            'name' => 'Storage Room',
            'name_persian' => 'انبار',
            'code' => 'STR',
            'type' => 'storage',
        ]);

        // Create inventory items
        InventoryItem::factory()->create([
            'name' => 'Gold Ring 18K',
            'name_persian' => 'حلقه طلای 18 عیار',
            'sku' => 'GLD001',
            'category_id' => $goldCategory->id,
            'location_id' => $showcase->id,
            'quantity' => 10,
            'unit_price' => 500.00,
            'cost_price' => 400.00,
            'gold_purity' => 18.000,
            'weight' => 5.5,
            'minimum_stock' => 5,
        ]);

        InventoryItem::factory()->create([
            'name' => 'Silver Necklace',
            'name_persian' => 'گردنبند نقره',
            'sku' => 'SLV001',
            'category_id' => $silverCategory->id,
            'location_id' => $storage->id,
            'quantity' => 3,
            'unit_price' => 150.00,
            'cost_price' => 100.00,
            'weight' => 12.0,
            'minimum_stock' => 5, // This will be low stock
        ]);

        InventoryItem::factory()->create([
            'name' => 'Gold Bracelet 22K',
            'name_persian' => 'دستبند طلای 22 عیار',
            'sku' => 'GLD002',
            'category_id' => $goldCategory->id,
            'location_id' => $showcase->id,
            'quantity' => 7,
            'unit_price' => 800.00,
            'cost_price' => 650.00,
            'gold_purity' => 22.000,
            'weight' => 8.2,
            'minimum_stock' => 3,
            'expiry_date' => now()->addDays(20), // This will be expiring
        ]);

        $this->command->info('Inventory test data created successfully!');
    }
}
