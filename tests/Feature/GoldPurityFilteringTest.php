<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GoldPurityFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
        
        // Create test inventory items with different gold purities
        InventoryItem::factory()->create([
            'name' => 'Gold Ring 18K',
            'gold_purity' => 18.0,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);
        
        InventoryItem::factory()->create([
            'name' => 'Gold Necklace 21K',
            'gold_purity' => 21.0,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);
        
        InventoryItem::factory()->create([
            'name' => 'Gold Bracelet 24K',
            'gold_purity' => 24.0,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);
        
        InventoryItem::factory()->create([
            'name' => 'Silver Ring',
            'gold_purity' => null, // No gold purity
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);
    }

    public function test_can_get_gold_purity_options()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory/gold-purity-options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'standard_purities' => [
                        '*' => [
                            'karat',
                            'purity',
                            'percentage',
                            'display',
                            'label',
                        ]
                    ],
                    'purity_ranges' => [
                        '*' => [
                            'min',
                            'max',
                            'label',
                            'min_display',
                            'max_display',
                        ]
                    ]
                ]
            ]);
    }

    public function test_can_filter_by_minimum_gold_purity()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory?gold_purity_min=20');

        $response->assertStatus(200);
        
        $items = $response->json('data');
        $this->assertCount(2, $items); // 21K and 24K items
        
        foreach ($items as $item) {
            $this->assertGreaterThanOrEqual(20, $item['gold_purity']);
        }
    }

    public function test_can_filter_by_maximum_gold_purity()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory?gold_purity_max=18');

        $response->assertStatus(200);
        
        $items = $response->json('data');
        $this->assertCount(1, $items); // Only 18K item
        
        foreach ($items as $item) {
            $this->assertLessThanOrEqual(18, $item['gold_purity']);
        }
    }

    public function test_can_filter_by_gold_purity_range()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory?gold_purity_min=18&gold_purity_max=21');

        $response->assertStatus(200);
        
        $items = $response->json('data');
        $this->assertCount(2, $items); // 18K and 21K items
        
        foreach ($items as $item) {
            $this->assertGreaterThanOrEqual(18, $item['gold_purity']);
            $this->assertLessThanOrEqual(21, $item['gold_purity']);
        }
    }

    public function test_can_filter_by_purity_range_category()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory?gold_purity_range=Pure Gold (22-24K)');

        $response->assertStatus(200);
        
        $items = $response->json('data');
        $this->assertCount(1, $items); // Only 24K item
        
        foreach ($items as $item) {
            $this->assertGreaterThanOrEqual(22, $item['gold_purity']);
            $this->assertLessThanOrEqual(24, $item['gold_purity']);
        }
    }

    public function test_gold_purity_filtering_excludes_null_values()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/inventory?gold_purity_min=1');

        $response->assertStatus(200);
        
        $items = $response->json('data');
        $this->assertCount(3, $items); // Excludes the silver ring with null gold_purity
        
        foreach ($items as $item) {
            $this->assertNotNull($item['gold_purity']);
        }
    }
}