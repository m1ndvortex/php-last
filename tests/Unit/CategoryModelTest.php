<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CategoryImage;
use App\Models\InventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'name',
            'name_persian',
            'description',
            'description_persian',
            'code',
            'is_active',
            'parent_id',
            'default_gold_purity',
            'image_path',
            'sort_order',
            'specifications',
        ];

        $category = new Category();
        $this->assertEquals($fillable, $category->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $category = Category::factory()->create([
            'is_active' => 1,
            'default_gold_purity' => 18.500,
            'sort_order' => 5,
            'specifications' => ['type' => 'ring', 'material' => 'gold'],
        ]);

        $this->assertIsBool($category->is_active);
        $this->assertEquals(18.500, $category->default_gold_purity);
        $this->assertIsInt($category->sort_order);
        $this->assertIsArray($category->specifications);
    }

    /** @test */
    public function it_belongs_to_parent_category()
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    /** @test */
    public function it_has_many_children_categories()
    {
        $parent = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parent->id]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertCount(2, $parent->children);
        $this->assertTrue($parent->children->contains($child1));
        $this->assertTrue($parent->children->contains($child2));
    }

    /** @test */
    public function it_has_many_inventory_items()
    {
        $category = Category::factory()->create();
        $item1 = InventoryItem::factory()->create(['category_id' => $category->id]);
        $item2 = InventoryItem::factory()->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->inventoryItems);
        $this->assertTrue($category->inventoryItems->contains($item1));
        $this->assertTrue($category->inventoryItems->contains($item2));
    }

    /** @test */
    public function it_has_many_main_category_items()
    {
        $category = Category::factory()->create();
        $item1 = InventoryItem::factory()->create(['main_category_id' => $category->id]);
        $item2 = InventoryItem::factory()->create(['main_category_id' => $category->id]);

        $this->assertCount(2, $category->mainCategoryItems);
        $this->assertTrue($category->mainCategoryItems->contains($item1));
        $this->assertTrue($category->mainCategoryItems->contains($item2));
    }

    /** @test */
    public function it_has_many_images()
    {
        $category = Category::factory()->create();
        $image1 = CategoryImage::factory()->create(['category_id' => $category->id]);
        $image2 = CategoryImage::factory()->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->images);
        $this->assertTrue($category->images->contains($image1));
        $this->assertTrue($category->images->contains($image2));
    }

    /** @test */
    public function it_has_one_primary_image()
    {
        $category = Category::factory()->create();
        $primaryImage = CategoryImage::factory()->create([
            'category_id' => $category->id,
            'is_primary' => true,
        ]);
        CategoryImage::factory()->create([
            'category_id' => $category->id,
            'is_primary' => false,
        ]);

        $this->assertInstanceOf(CategoryImage::class, $category->primaryImage);
        $this->assertEquals($primaryImage->id, $category->primaryImage->id);
        $this->assertTrue($category->primaryImage->is_primary);
    }

    /** @test */
    public function it_returns_localized_name_in_english()
    {
        app()->setLocale('en');
        
        $category = Category::factory()->create([
            'name' => 'Rings',
            'name_persian' => 'انگشتر',
        ]);

        $this->assertEquals('Rings', $category->localized_name);
    }

    /** @test */
    public function it_returns_localized_name_in_persian()
    {
        app()->setLocale('fa');
        
        $category = Category::factory()->create([
            'name' => 'Rings',
            'name_persian' => 'انگشتر',
        ]);

        $this->assertEquals('انگشتر', $category->localized_name);
    }

    /** @test */
    public function it_falls_back_to_english_name_when_persian_is_null()
    {
        app()->setLocale('fa');
        
        $category = Category::factory()->create([
            'name' => 'Rings',
            'name_persian' => null,
        ]);

        $this->assertEquals('Rings', $category->localized_name);
    }

    /** @test */
    public function it_returns_localized_description()
    {
        app()->setLocale('fa');
        
        $category = Category::factory()->create([
            'description' => 'Beautiful rings',
            'description_persian' => 'انگشترهای زیبا',
        ]);

        $this->assertEquals('انگشترهای زیبا', $category->localized_description);
    }

    /** @test */
    public function it_scopes_active_categories()
    {
        Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $activeCategories = Category::active()->get();
        
        $this->assertCount(1, $activeCategories);
        $this->assertTrue($activeCategories->first()->is_active);
    }

    /** @test */
    public function it_scopes_root_categories()
    {
        $parent = Category::factory()->create(['parent_id' => null]);
        Category::factory()->create(['parent_id' => $parent->id]);

        $rootCategories = Category::root()->get();
        
        $this->assertCount(1, $rootCategories);
        $this->assertNull($rootCategories->first()->parent_id);
    }

    /** @test */
    public function it_scopes_ordered_categories()
    {
        Category::factory()->create(['sort_order' => 3, 'name' => 'C']);
        Category::factory()->create(['sort_order' => 1, 'name' => 'A']);
        Category::factory()->create(['sort_order' => 2, 'name' => 'B']);

        $orderedCategories = Category::ordered()->get();
        
        $this->assertEquals('A', $orderedCategories->first()->name);
        $this->assertEquals('B', $orderedCategories->get(1)->name);
        $this->assertEquals('C', $orderedCategories->last()->name);
    }

    /** @test */
    public function it_formats_gold_purity_in_english()
    {
        app()->setLocale('en');
        
        $category = Category::factory()->create(['default_gold_purity' => 18.5]);
        
        $this->assertEquals('18.5K', $category->formatted_gold_purity);
    }

    /** @test */
    public function it_formats_gold_purity_in_persian()
    {
        app()->setLocale('fa');
        
        $category = Category::factory()->create(['default_gold_purity' => 18.5]);
        
        $formatted = $category->formatted_gold_purity;
        $this->assertStringContainsString('عیار', $formatted);
        $this->assertStringContainsString('۱۸', $formatted);
    }

    /** @test */
    public function it_returns_null_for_formatted_gold_purity_when_not_set()
    {
        $category = Category::factory()->create(['default_gold_purity' => null]);
        
        $this->assertNull($category->formatted_gold_purity);
    }

    /** @test */
    public function it_calculates_item_count_correctly()
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        
        // Direct items in parent category
        InventoryItem::factory()->count(2)->create(['category_id' => $parent->id]);
        
        // Main category items
        InventoryItem::factory()->count(3)->create(['main_category_id' => $parent->id]);
        
        // Items in child category
        InventoryItem::factory()->count(1)->create(['category_id' => $child->id]);
        
        $this->assertEquals(6, $parent->item_count); // 2 + 3 + 1
        $this->assertEquals(1, $child->item_count);
    }

    /** @test */
    public function it_detects_if_category_has_children()
    {
        $parent = Category::factory()->create();
        $childless = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertTrue($parent->has_children);
        $this->assertFalse($childless->has_children);
    }

    /** @test */
    public function it_returns_image_url_from_primary_image()
    {
        $category = Category::factory()->create();
        $primaryImage = CategoryImage::factory()->create([
            'category_id' => $category->id,
            'is_primary' => true,
            'image_path' => 'categories/test-image.webp',
        ]);

        $imageUrl = $category->image_url;
        $this->assertStringContainsString('categories/test-image.webp', $imageUrl);
    }

    /** @test */
    public function it_falls_back_to_image_path_when_no_primary_image()
    {
        $category = Category::factory()->create([
            'image_path' => 'categories/fallback-image.jpg',
        ]);

        $imageUrl = $category->image_url;
        $this->assertStringContainsString('categories/fallback-image.jpg', $imageUrl);
    }

    /** @test */
    public function it_returns_null_image_url_when_no_images()
    {
        $category = Category::factory()->create([
            'image_path' => null,
        ]);

        $this->assertNull($category->image_url);
    }

    /** @test */
    public function it_gets_ancestors_correctly()
    {
        $grandparent = Category::factory()->create(['name' => 'Jewelry']);
        $parent = Category::factory()->create([
            'name' => 'Rings',
            'parent_id' => $grandparent->id,
        ]);
        $child = Category::factory()->create([
            'name' => 'Wedding Rings',
            'parent_id' => $parent->id,
        ]);

        $ancestors = $child->ancestors();
        
        $this->assertCount(2, $ancestors);
        $this->assertEquals('Jewelry', $ancestors->first()->name);
        $this->assertEquals('Rings', $ancestors->last()->name);
    }

    /** @test */
    public function it_returns_empty_collection_for_root_category_ancestors()
    {
        $root = Category::factory()->create(['parent_id' => null]);
        
        $ancestors = $root->ancestors();
        
        $this->assertCount(0, $ancestors);
    }
}