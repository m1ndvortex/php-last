<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Database\Seeders\Helpers\CategoryImageGenerator;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample category images first
        $this->createSampleImages();

        // Create main categories
        $goldJewelry = $this->createMainCategory([
            'name' => 'Gold Jewelry',
            'name_persian' => 'جواهرات طلا',
            'code' => 'GOLD',
            'description' => 'Premium gold jewelry collection with various purity levels',
            'description_persian' => 'مجموعه جواهرات طلای درجه یک با عیارهای مختلف',
            'default_gold_purity' => 18.000,
            'image_path' => 'categories/gold-jewelry.svg',
            'sort_order' => 1,
            'specifications' => [
                'material' => 'gold',
                'care_instructions' => 'Clean with soft cloth, avoid chemicals',
                'warranty' => '1 year manufacturing warranty'
            ]
        ]);

        $silverJewelry = $this->createMainCategory([
            'name' => 'Silver Jewelry',
            'name_persian' => 'جواهرات نقره',
            'code' => 'SILVER',
            'description' => 'Elegant silver jewelry collection',
            'description_persian' => 'مجموعه جواهرات نقره زیبا',
            'default_gold_purity' => null,
            'image_path' => 'categories/silver-jewelry.svg',
            'sort_order' => 2,
            'specifications' => [
                'material' => 'sterling_silver',
                'purity' => '925',
                'care_instructions' => 'Store in dry place, polish regularly'
            ]
        ]);

        $gemstoneJewelry = $this->createMainCategory([
            'name' => 'Gemstone Jewelry',
            'name_persian' => 'جواهرات سنگی',
            'code' => 'GEMS',
            'description' => 'Precious and semi-precious gemstone jewelry',
            'description_persian' => 'جواهرات سنگ‌های قیمتی و نیمه قیمتی',
            'default_gold_purity' => 14.000,
            'image_path' => 'categories/gemstone-jewelry.svg',
            'sort_order' => 3,
            'specifications' => [
                'material' => 'mixed',
                'gemstone_types' => ['diamond', 'ruby', 'emerald', 'sapphire'],
                'certification' => 'GIA certified stones available'
            ]
        ]);

        $watchesTimepieces = $this->createMainCategory([
            'name' => 'Watches & Timepieces',
            'name_persian' => 'ساعت و زمان‌سنج',
            'code' => 'WATCH',
            'description' => 'Luxury watches and timepieces',
            'description_persian' => 'ساعت‌های لوکس و زمان‌سنج',
            'default_gold_purity' => 18.000,
            'image_path' => 'categories/watches.svg',
            'sort_order' => 4,
            'specifications' => [
                'material' => 'mixed',
                'movement_types' => ['automatic', 'quartz', 'mechanical'],
                'water_resistance' => 'varies by model'
            ]
        ]);

        // Create subcategories for Gold Jewelry
        $this->createSubcategories($goldJewelry->id, [
            [
                'name' => 'Gold Rings',
                'name_persian' => 'حلقه‌های طلا',
                'code' => 'GOLD-RING',
                'description' => 'Wedding bands, engagement rings, and fashion rings',
                'description_persian' => 'حلقه ازدواج، حلقه نامزدی و حلقه‌های فشن',
                'default_gold_purity' => 18.000,
                'image_path' => 'categories/gold-rings.svg',
                'sort_order' => 1,
                'specifications' => [
                    'sizes_available' => '5-12',
                    'styles' => ['solitaire', 'band', 'cocktail', 'eternity'],
                    'customization' => 'engraving available'
                ]
            ],
            [
                'name' => 'Gold Necklaces',
                'name_persian' => 'گردنبندهای طلا',
                'code' => 'GOLD-NECK',
                'description' => 'Chains, pendants, and statement necklaces',
                'description_persian' => 'زنجیر، آویز و گردنبندهای جلب توجه',
                'default_gold_purity' => 21.000,
                'image_path' => 'categories/gold-necklaces.svg',
                'sort_order' => 2,
                'specifications' => [
                    'chain_types' => ['box', 'rope', 'figaro', 'curb'],
                    'lengths' => ['16"', '18"', '20"', '24"'],
                    'clasp_types' => ['lobster', 'spring', 'magnetic']
                ]
            ],
            [
                'name' => 'Gold Bracelets',
                'name_persian' => 'دستبندهای طلا',
                'code' => 'GOLD-BRAC',
                'description' => 'Tennis bracelets, bangles, and charm bracelets',
                'description_persian' => 'دستبند تنیس، النگو و دستبند آویزدار',
                'default_gold_purity' => 18.000,
                'image_path' => 'categories/gold-bracelets.svg',
                'sort_order' => 3,
                'specifications' => [
                    'styles' => ['tennis', 'bangle', 'charm', 'cuff'],
                    'sizes' => ['small', 'medium', 'large'],
                    'adjustable' => 'some models'
                ]
            ],
            [
                'name' => 'Gold Earrings',
                'name_persian' => 'گوشواره‌های طلا',
                'code' => 'GOLD-EAR',
                'description' => 'Studs, hoops, and drop earrings',
                'description_persian' => 'گوشواره میخی، حلقه‌ای و آویزان',
                'default_gold_purity' => 14.000,
                'image_path' => 'categories/gold-earrings.svg',
                'sort_order' => 4,
                'specifications' => [
                    'styles' => ['stud', 'hoop', 'drop', 'chandelier'],
                    'back_types' => ['push', 'screw', 'lever'],
                    'hypoallergenic' => 'yes'
                ]
            ]
        ]);

        // Create subcategories for Silver Jewelry
        $this->createSubcategories($silverJewelry->id, [
            [
                'name' => 'Silver Rings',
                'name_persian' => 'حلقه‌های نقره',
                'code' => 'SILV-RING',
                'description' => 'Sterling silver rings in various styles',
                'description_persian' => 'حلقه‌های نقره استرلینگ در طرح‌های مختلف',
                'default_gold_purity' => null,
                'image_path' => 'categories/silver-rings.svg',
                'sort_order' => 1,
                'specifications' => [
                    'silver_purity' => '925',
                    'styles' => ['band', 'statement', 'stackable'],
                    'tarnish_resistant' => 'rhodium plated options'
                ]
            ],
            [
                'name' => 'Silver Necklaces',
                'name_persian' => 'گردنبندهای نقره',
                'code' => 'SILV-NECK',
                'description' => 'Sterling silver chains and pendants',
                'description_persian' => 'زنجیر و آویز نقره استرلینگ',
                'default_gold_purity' => null,
                'image_path' => 'categories/silver-necklaces.svg',
                'sort_order' => 2,
                'specifications' => [
                    'silver_purity' => '925',
                    'chain_styles' => ['snake', 'box', 'curb'],
                    'pendant_options' => 'available separately'
                ]
            ],
            [
                'name' => 'Silver Bracelets',
                'name_persian' => 'دستبندهای نقره',
                'code' => 'SILV-BRAC',
                'description' => 'Sterling silver bracelets and bangles',
                'description_persian' => 'دستبند و النگوی نقره استرلینگ',
                'default_gold_purity' => null,
                'image_path' => 'categories/silver-bracelets.svg',
                'sort_order' => 3,
                'specifications' => [
                    'silver_purity' => '925',
                    'styles' => ['chain', 'bangle', 'cuff'],
                    'adjustable_sizes' => 'most models'
                ]
            ]
        ]);

        // Create subcategories for Gemstone Jewelry
        $this->createSubcategories($gemstoneJewelry->id, [
            [
                'name' => 'Diamond Jewelry',
                'name_persian' => 'جواهرات الماس',
                'code' => 'DIAM-JEW',
                'description' => 'Certified diamond jewelry pieces',
                'description_persian' => 'قطعات جواهرات الماس گواهی‌دار',
                'default_gold_purity' => 18.000,
                'image_path' => 'categories/diamond-jewelry.svg',
                'sort_order' => 1,
                'specifications' => [
                    'diamond_cuts' => ['round', 'princess', 'emerald', 'oval'],
                    'clarity_grades' => ['FL', 'IF', 'VVS1', 'VVS2', 'VS1', 'VS2'],
                    'certification' => 'GIA, AGS certified'
                ]
            ],
            [
                'name' => 'Ruby Jewelry',
                'name_persian' => 'جواهرات یاقوت سرخ',
                'code' => 'RUBY-JEW',
                'description' => 'Natural ruby jewelry collection',
                'description_persian' => 'مجموعه جواهرات یاقوت سرخ طبیعی',
                'default_gold_purity' => 18.000,
                'image_path' => 'categories/ruby-jewelry.svg',
                'sort_order' => 2,
                'specifications' => [
                    'origin' => ['Burma', 'Thailand', 'Madagascar'],
                    'treatments' => 'heat treated, natural',
                    'quality_grades' => ['AAA', 'AA', 'A']
                ]
            ],
            [
                'name' => 'Emerald Jewelry',
                'name_persian' => 'جواهرات زمرد',
                'code' => 'EMER-JEW',
                'description' => 'Premium emerald jewelry pieces',
                'description_persian' => 'قطعات جواهرات زمرد درجه یک',
                'default_gold_purity' => 18.000,
                'image_path' => 'categories/emerald-jewelry.svg',
                'sort_order' => 3,
                'specifications' => [
                    'origin' => ['Colombia', 'Zambia', 'Brazil'],
                    'clarity' => 'eye clean to slightly included',
                    'treatments' => 'oiled, natural'
                ]
            ]
        ]);

        // Create subcategories for Watches
        $this->createSubcategories($watchesTimepieces->id, [
            [
                'name' => 'Men\'s Watches',
                'name_persian' => 'ساعت مردانه',
                'code' => 'WATCH-MEN',
                'description' => 'Luxury men\'s timepieces',
                'description_persian' => 'ساعت‌های لوکس مردانه',
                'default_gold_purity' => 18.000,
                'image_path' => 'categories/mens-watches.svg',
                'sort_order' => 1,
                'specifications' => [
                    'case_sizes' => ['38mm', '40mm', '42mm', '44mm'],
                    'movements' => ['automatic', 'quartz'],
                    'water_resistance' => '50m-300m'
                ]
            ],
            [
                'name' => 'Women\'s Watches',
                'name_persian' => 'ساعت زنانه',
                'code' => 'WATCH-WOM',
                'description' => 'Elegant women\'s timepieces',
                'description_persian' => 'ساعت‌های زیبای زنانه',
                'default_gold_purity' => 14.000,
                'image_path' => 'categories/womens-watches.svg',
                'sort_order' => 2,
                'specifications' => [
                    'case_sizes' => ['26mm', '28mm', '32mm', '36mm'],
                    'styles' => ['dress', 'sport', 'casual'],
                    'strap_options' => ['leather', 'metal', 'ceramic']
                ]
            ]
        ]);

        $this->command->info('Category seeder completed successfully!');
        $this->command->info('Created main categories with subcategories and sample images.');
    }

    /**
     * Create a main category.
     */
    private function createMainCategory(array $data): Category
    {
        return Category::updateOrCreate(
            ['code' => $data['code']],
            $data
        );
    }

    /**
     * Create subcategories for a parent category.
     */
    private function createSubcategories(int $parentId, array $subcategories): void
    {
        foreach ($subcategories as $subcategoryData) {
            $subcategoryData['parent_id'] = $parentId;
            Category::updateOrCreate(
                ['code' => $subcategoryData['code']],
                $subcategoryData
            );
        }
    }

    /**
     * Create sample category images for testing.
     */
    private function createSampleImages(): void
    {
        $categories = [
            'gold-jewelry.svg' => 'Gold Jewelry',
            'silver-jewelry.svg' => 'Silver Jewelry',
            'gemstone-jewelry.svg' => 'Gemstone Jewelry',
            'watches.svg' => 'Watches & Timepieces',
            'gold-rings.svg' => 'Gold Rings',
            'gold-necklaces.svg' => 'Gold Necklaces',
            'gold-bracelets.svg' => 'Gold Bracelets',
            'gold-earrings.svg' => 'Gold Earrings',
            'silver-rings.svg' => 'Silver Rings',
            'silver-necklaces.svg' => 'Silver Necklaces',
            'silver-bracelets.svg' => 'Silver Bracelets',
            'diamond-jewelry.svg' => 'Diamond Jewelry',
            'ruby-jewelry.svg' => 'Ruby Jewelry',
            'emerald-jewelry.svg' => 'Emerald Jewelry',
            'mens-watches.svg' => 'Men\'s Watches',
            'womens-watches.svg' => 'Women\'s Watches',
        ];

        foreach ($categories as $filename => $categoryName) {
            $color = CategoryImageGenerator::getColorForCategory($categoryName);
            $svgContent = CategoryImageGenerator::generateSVG($categoryName, $color);
            Storage::disk('public')->put("categories/{$filename}", $svgContent);
        }

        $this->command->info('Sample category images created with decorative patterns.');
    }
}