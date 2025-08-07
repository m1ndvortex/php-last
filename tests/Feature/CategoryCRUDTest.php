<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryImage;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryCRUDTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    /** @test */
    public function it_can_list_categories()
    {
        $this->actingAs($this->user);
        
        Category::factory()->count(3)->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/categories?active=1');

        $response->assertStatus(200)
                ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_hierarchical_category_tree()
    {
        $this->actingAs($this->user);
        
        $parent = Category::factory()->create([
            'name' => 'Jewelry',
            'parent_id' => null,
        ]);
        $child = Category::factory()->create([
            'name' => 'Rings',
            'parent_id' => $parent->id,
        ]);

        $response = $this->getJson('/api/categories/hierarchy');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'children' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'children'
                                ]
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_create_category()
    {
        $this->actingAs($this->user);
        
        $categoryData = [
            'name' => 'Rings',
            'name_persian' => 'انگشتر',
            'code' => 'RING',
            'description' => 'Beautiful rings',
            'description_persian' => 'انگشترهای زیبا',
            'is_active' => true,
            'default_gold_purity' => 18.0,
            'specifications' => ['type' => 'jewelry'],
        ];

        $response = $this->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'name' => 'Rings',
                    'name_persian' => 'انگشتر',
                    'code' => 'RING',
                    'default_gold_purity' => '18.000',
                ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Rings',
            'code' => 'RING',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_category()
    {
        $this->actingAs($this->user);
        
        $response = $this->postJson('/api/categories', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'code']);
    }

    /** @test */
    public function it_validates_unique_code_when_creating_category()
    {
        $this->actingAs($this->user);
        
        Category::factory()->create(['code' => 'RING']);

        $response = $this->postJson('/api/categories', [
            'name' => 'New Ring',
            'code' => 'RING',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function it_validates_gold_purity_range_when_creating_category()
    {
        $this->actingAs($this->user);
        
        $response = $this->postJson('/api/categories', [
            'name' => 'Test Category',
            'code' => 'TEST',
            'default_gold_purity' => 25, // Invalid: > 24
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['default_gold_purity']);
    }

    /** @test */
    public function it_prevents_circular_reference_when_creating_category()
    {
        $this->actingAs($this->user);
        
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->postJson('/api/categories', [
            'name' => 'Invalid Category',
            'code' => 'INVALID',
            'parent_id' => $child->id,
        ]);

        // Try to make parent a child of child (circular reference)
        $response = $this->putJson("/api/categories/{$parent->id}", [
            'name' => $parent->name,
            'code' => $parent->code,
            'parent_id' => $child->id,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_show_category_details()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create([
            'name' => 'Rings',
            'default_gold_purity' => 18.0,
        ]);
        CategoryImage::factory()->create([
            'category_id' => $category->id,
            'is_primary' => true,
        ]);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'name' => 'Rings',
                    'default_gold_purity' => '18.000',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'images',
                        'primary_image',
                    ]
                ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_category()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/categories/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_category()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create([
            'name' => 'Original Name',
            'default_gold_purity' => 18.0,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'name_persian' => 'نام به‌روزشده',
            'default_gold_purity' => 21.0,
        ];

        $response = $this->putJson("/api/categories/{$category->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'name' => 'Updated Name',
                    'name_persian' => 'نام به‌روزشده',
                    'default_gold_purity' => '21.000',
                ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'default_gold_purity' => 21.0,
        ]);
    }

    /** @test */
    public function it_validates_unique_code_when_updating_category()
    {
        $this->actingAs($this->user);
        
        $category1 = Category::factory()->create(['code' => 'RING']);
        $category2 = Category::factory()->create(['code' => 'NECKLACE']);

        $response = $this->putJson("/api/categories/{$category2->id}", [
            'name' => $category2->name,
            'code' => 'RING', // Already exists
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function it_allows_keeping_same_code_when_updating_category()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create(['code' => 'RING']);

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => 'Updated Name',
            'code' => 'RING', // Same code, should be allowed
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_delete_category_without_dependencies()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /** @test */
    public function it_prevents_deleting_category_with_inventory_items()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        InventoryItem::factory()->create(['category_id' => $category->id]);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(422)
                ->assertJsonFragment([
                    'message' => 'Cannot delete category with existing inventory items'
                ]);
    }

    /** @test */
    public function it_prevents_deleting_category_with_main_category_items()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        InventoryItem::factory()->create(['main_category_id' => $category->id]);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(422)
                ->assertJsonFragment([
                    'message' => 'Cannot delete category with existing inventory items'
                ]);
    }

    /** @test */
    public function it_prevents_deleting_category_with_children()
    {
        $this->actingAs($this->user);
        
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->deleteJson("/api/categories/{$parent->id}");

        $response->assertStatus(422)
                ->assertJsonFragment([
                    'message' => 'Cannot delete category with child categories'
                ]);
    }

    /** @test */
    public function it_can_upload_category_image()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('category.jpg', 800, 600);

        $response = $this->postJson("/api/categories/{$category->id}/image", [
            'image' => $image,
            'alt_text' => 'Category image',
            'alt_text_persian' => 'تصویر دسته‌بندی',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'image_path',
                        'alt_text',
                        'alt_text_persian',
                        'is_primary',
                    ]
                ]);

        $this->assertDatabaseHas('category_images', [
            'category_id' => $category->id,
            'alt_text' => 'Category image',
            'is_primary' => true,
        ]);
    }

    /** @test */
    public function it_validates_image_upload()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();

        // Test missing image
        $response = $this->postJson("/api/categories/{$category->id}/image", []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_can_remove_category_image()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        $categoryImage = CategoryImage::factory()->create([
            'category_id' => $category->id,
            'image_path' => 'categories/test-image.webp',
            'is_primary' => true,
        ]);

        // Create the fake file
        Storage::disk('public')->put($categoryImage->image_path, 'fake image content');

        $response = $this->deleteJson("/api/categories/{$category->id}/image");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('category_images', ['id' => $categoryImage->id]);
        Storage::disk('public')->assertMissing($categoryImage->image_path);
    }

    /** @test */
    public function it_can_reorder_categories()
    {
        $this->actingAs($this->user);
        
        $category1 = Category::factory()->create(['sort_order' => 1]);
        $category2 = Category::factory()->create(['sort_order' => 2]);
        $category3 = Category::factory()->create(['sort_order' => 3]);

        $reorderData = [
            'categories' => [
                ['id' => $category2->id, 'sort_order' => 1, 'parent_id' => null],
                ['id' => $category3->id, 'sort_order' => 2, 'parent_id' => null],
                ['id' => $category1->id, 'sort_order' => 3, 'parent_id' => null],
            ]
        ];

        $response = $this->postJson('/api/categories/reorder', $reorderData);

        $response->assertStatus(200);
        
        $this->assertEquals(1, $category2->fresh()->sort_order);
        $this->assertEquals(2, $category3->fresh()->sort_order);
        $this->assertEquals(3, $category1->fresh()->sort_order);
    }

    /** @test */
    public function it_can_get_gold_purity_options()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/categories/gold-purity-options');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'karat',
                            'purity',
                            'percentage',
                            'display',
                            'label',
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_requires_authentication_for_category_operations()
    {
        $category = Category::factory()->create();

        // Test all endpoints without authentication
        $this->getJson('/api/categories')->assertStatus(401);
        $this->postJson('/api/categories', [])->assertStatus(401);
        $this->getJson("/api/categories/{$category->id}")->assertStatus(401);
        $this->putJson("/api/categories/{$category->id}", [])->assertStatus(401);
        $this->deleteJson("/api/categories/{$category->id}")->assertStatus(401);
        $this->getJson('/api/categories/hierarchy')->assertStatus(401);
        $this->postJson("/api/categories/{$category->id}/image", [])->assertStatus(401);
        $this->deleteJson("/api/categories/{$category->id}/image")->assertStatus(401);
        $this->postJson('/api/categories/reorder', [])->assertStatus(401);
        $this->getJson('/api/categories/gold-purity-options')->assertStatus(401);
    }

    /** @test */
    public function it_can_filter_categories_by_search_query()
    {
        $this->actingAs($this->user);
        
        Category::factory()->create([
            'name' => 'Gold Rings',
            'name_persian' => 'انگشتر طلا',
            'is_active' => true,
        ]);
        Category::factory()->create([
            'name' => 'Silver Necklaces',
            'name_persian' => 'گردنبند نقره',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/categories/search?query=Gold');

        $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJsonFragment(['name' => 'Gold Rings']);
    }

    /** @test */
    public function it_can_filter_categories_by_parent_id()
    {
        $this->actingAs($this->user);
        
        $parent = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parent->id]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id]);
        Category::factory()->create(); // Different parent

        $response = $this->getJson("/api/categories?parent_id={$parent->id}");

        $response->assertStatus(200)
                ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_includes_category_relationships_in_responses()
    {
        $this->actingAs($this->user);
        
        $parent = Category::factory()->create();
        $category = Category::factory()->create(['parent_id' => $parent->id]);
        CategoryImage::factory()->create([
            'category_id' => $category->id,
            'is_primary' => true,
        ]);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'parent',
                        'children',
                        'images',
                        'primary_image',
                    ]
                ]);
    }
}