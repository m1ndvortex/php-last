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
        // Get or create a test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        // Get categories from the CategorySeeder (assuming it has been run)
        $goldJewelry = Category::where('code', 'GOLD')->first();
        $silverJewelry = Category::where('code', 'SILVER')->first();
        $gemstoneJewelry = Category::where('code', 'GEMS')->first();
        
        // Get subcategories
        $goldRings = Category::where('code', 'GOLD-RING')->first();
        $goldNecklaces = Category::where('code', 'GOLD-NECK')->first();
        $goldBracelets = Category::where('code', 'GOLD-BRAC')->first();
        $silverNecklaces = Category::where('code', 'SILV-NECK')->first();
        $silverBracelets = Category::where('code', 'SILV-BRAC')->first();
        $diamondJewelry = Category::where('code', 'DIAM-JEW')->first();

        // If categories don't exist, create basic ones for testing
        if (!$goldJewelry) {
            $goldJewelry = Category::create([
                'name' => 'Gold Jewelry',
                'name_persian' => 'جواهرات طلا',
                'code' => 'GOLD',
                'default_gold_purity' => 18.000,
            ]);
            
            $goldRings = Category::create([
                'name' => 'Gold Rings',
                'name_persian' => 'حلقه‌های طلا',
                'code' => 'GOLD-RING',
                'parent_id' => $goldJewelry->id,
                'default_gold_purity' => 18.000,
            ]);
        }

        if (!$silverJewelry) {
            $silverJewelry = Category::create([
                'name' => 'Silver Jewelry',
                'name_persian' => 'جواهرات نقره',
                'code' => 'SILVER',
            ]);
            
            $silverNecklaces = Category::create([
                'name' => 'Silver Necklaces',
                'name_persian' => 'گردنبندهای نقره',
                'code' => 'SILV-NECK',
                'parent_id' => $silverJewelry->id,
            ]);
        }

        // Get or create locations
        $showcase = Location::firstOrCreate(
            ['code' => 'MS1'],
            [
                'name' => 'Main Showcase',
                'name_persian' => 'ویترین اصلی',
                'type' => 'showcase',
            ]
        );

        $storage = Location::firstOrCreate(
            ['code' => 'STR'],
            [
                'name' => 'Storage Room',
                'name_persian' => 'انبار',
                'type' => 'storage',
            ]
        );

        // Create inventory items with dual category structure
        InventoryItem::updateOrCreate(
            ['sku' => 'GLD001'],
            [
                'name' => 'Gold Ring 18K',
                'name_persian' => 'حلقه طلای 18 عیار',
            'main_category_id' => $goldJewelry->id,
            'category_id' => $goldRings ? $goldRings->id : null,
            'location_id' => $showcase->id,
            'quantity' => 10,
            'unit_price' => 500.00,
            'cost_price' => 400.00,
            'gold_purity' => 18.000,
            'weight' => 5.5,
                'minimum_stock' => 5,
            ]
        );

        InventoryItem::updateOrCreate(
            ['sku' => 'SLV001'],
            [
                'name' => 'Silver Necklace',
                'name_persian' => 'گردنبند نقره',
            'main_category_id' => $silverJewelry->id,
            'category_id' => $silverNecklaces ? $silverNecklaces->id : null,
            'location_id' => $storage->id,
            'quantity' => 3,
            'unit_price' => 150.00,
            'cost_price' => 100.00,
            'weight' => 12.0,
                'minimum_stock' => 5, // This will be low stock
            ]
        );

        InventoryItem::updateOrCreate(
            ['sku' => 'GLD002'],
            [
                'name' => 'Gold Bracelet 22K',
                'name_persian' => 'دستبند طلای 22 عیار',
            'main_category_id' => $goldJewelry->id,
            'category_id' => $goldBracelets ? $goldBracelets->id : null,
            'location_id' => $showcase->id,
            'quantity' => 7,
            'unit_price' => 800.00,
            'cost_price' => 650.00,
            'gold_purity' => 22.000,
            'weight' => 8.2,
            'minimum_stock' => 3,
                'expiry_date' => now()->addDays(20), // This will be expiring
            ]
        );

        // Add more diverse inventory items using the new category structure
        if ($goldNecklaces) {
            InventoryItem::updateOrCreate(
                ['sku' => 'GLD003'],
                [
                    'name' => 'Gold Chain Necklace 21K',
                    'name_persian' => 'گردنبند زنجیری طلای 21 عیار',
                'main_category_id' => $goldJewelry->id,
                'category_id' => $goldNecklaces->id,
                'location_id' => $showcase->id,
                'quantity' => 5,
                'unit_price' => 1200.00,
                'cost_price' => 950.00,
                'gold_purity' => 21.000,
                'weight' => 15.3,
                    'minimum_stock' => 2,
                ]
            );
        }

        if ($silverBracelets) {
            InventoryItem::updateOrCreate(
                ['sku' => 'SLV002'],
                [
                    'name' => 'Silver Tennis Bracelet',
                    'name_persian' => 'دستبند تنیس نقره',
                'main_category_id' => $silverJewelry->id,
                'category_id' => $silverBracelets->id,
                'location_id' => $showcase->id,
                'quantity' => 8,
                'unit_price' => 280.00,
                'cost_price' => 200.00,
                'weight' => 18.7,
                    'minimum_stock' => 3,
                ]
            );
        }

        if ($diamondJewelry) {
            InventoryItem::updateOrCreate(
                ['sku' => 'DIAM001'],
                [
                    'name' => 'Diamond Solitaire Ring',
                    'name_persian' => 'حلقه الماس تک نگین',
                'main_category_id' => $gemstoneJewelry->id,
                'category_id' => $diamondJewelry->id,
                'location_id' => $storage->id,
                'quantity' => 2,
                'unit_price' => 3500.00,
                'cost_price' => 2800.00,
                'gold_purity' => 18.000,
                'weight' => 4.2,
                'minimum_stock' => 1,
                    'metadata' => [
                        'diamond_carat' => 1.0,
                        'diamond_cut' => 'round',
                        'diamond_clarity' => 'VS1',
                        'diamond_color' => 'G'
                    ]
                ]
            );
        }

        $this->command->info('Inventory test data created successfully with new category structure!');
    }
}
