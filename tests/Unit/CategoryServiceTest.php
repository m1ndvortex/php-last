<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CategoryImage;
use App\Models\InventoryItem;
use App\Services\CategoryService;
use App\Services\CategoryImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryService = app(CategoryService::class);
    }

    /** @test */
    public function it_can_create_category_with_valid_data()
    {
        $data = [
            'name' => 'Rings',
            'name_persian' => 'انگشتر',
            'code' => 'RING',
            'description' => 'Beautiful rings',
            'is_active' => true,
            'default_gold_purity' => 18.0,
        ];

        $category = $this->categoryService->createCategory($data);

        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('Rings', $category->name);
        $this->assertEquals('انگشتر', $category->name_persian);
        $this->assertEquals('RING', $category->code);
        $this->assertEquals(18.0, $category->default_gold_purity);
        $this->assertTrue($category->is_active);
    }

    /** @test */
    public function it_sets_sort_order_automatically_when_creating_category()
    {
        // Create a parent category
        $parent = Category::factory()->create();
        
        // Create first child
        $child1 = $this->categoryService->createCategory([
            'name' => 'Child 1',
            'code' => 'CHILD1',
            'parent_id' => $parent->id,
        ]);

        // Create second child
        $child2 = $this->categoryService->createCategory([
            'name' => 'Child 2',
            'code' => 'CHILD2',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals(1, $child1->sort_order);
        $this->assertEquals(2, $child2->sort_order);
    }

    /** @test */
    public function it_validates_hierarchy_when_creating_category()
    {
        $parent = Category::factory()->create();
        
        // This should work fine - creating a valid child
        $validCategory = $this->categoryService->createCategory([
            'name' => 'Valid Child',
            'code' => 'VALID',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $validCategory->parent_id);
    }

    /** @test */
    public function it_can_update_category_with_valid_data()
    {
        $category = Category::factory()->create([
            'name' => 'Original Name',
            'default_gold_purity' => 18.0,
        ]);

        $updatedCategory = $this->categoryService->updateCategory($category, [
            'name' => 'Updated Name',
            'default_gold_purity' => 21.0,
        ]);

        $this->assertEquals('Updated Name', $updatedCategory->name);
        $this->assertEquals(21.0, $updatedCategory->default_gold_purity);
    }

    /** @test */
    public function it_validates_hierarchy_when_updating_category()
    {
        $grandparent = Category::factory()->create();
        $parent = Category::factory()->create(['parent_id' => $grandparent->id]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This would create a circular reference');

        // Try to make grandparent a child of child (circular reference)
        $this->categoryService->updateCategory($grandparent, [
            'parent_id' => $child->id,
        ]);
    }

    /** @test */
    public function it_prevents_category_from_being_its_own_parent()
    {
        $category = Category::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('A category cannot be its own parent');

        $this->categoryService->updateCategory($category, [
            'parent_id' => $category->id,
        ]);
    }

    /** @test */
    public function it_can_delete_category_without_items_or_children()
    {
        $category = Category::factory()->create();

        $result = $this->categoryService->deleteCategory($category);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /** @test */
    public function it_prevents_deleting_category_with_inventory_items()
    {
        $category = Category::factory()->create();
        InventoryItem::factory()->create(['category_id' => $category->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete category with existing inventory items');

        $this->categoryService->deleteCategory($category);
    }

    /** @test */
    public function it_prevents_deleting_category_with_main_category_items()
    {
        $category = Category::factory()->create();
        InventoryItem::factory()->create(['main_category_id' => $category->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete category with existing inventory items');

        $this->categoryService->deleteCategory($category);
    }

    /** @test */
    public function it_prevents_deleting_category_with_children()
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete category with child categories');

        $this->categoryService->deleteCategory($parent);
    }

    /** @test */
    public function it_deletes_category_images_when_deleting_category()
    {
        $category = Category::factory()->create();
        $image = CategoryImage::factory()->create(['category_id' => $category->id]);

        // Mock the CategoryImageService
        $imageService = $this->createMock(CategoryImageService::class);
        $imageService->expects($this->once())
                    ->method('removeImage')
                    ->with($this->callback(function ($categoryImage) use ($image) {
                        return $categoryImage->id === $image->id;
                    }))
                    ->willReturn(true);

        $this->app->instance(CategoryImageService::class, $imageService);

        $result = $this->categoryService->deleteCategory($category);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_hierarchical_tree()
    {
        // Create test hierarchy
        $parent1 = Category::factory()->create(['name' => 'Jewelry', 'sort_order' => 1]);
        $parent2 = Category::factory()->create(['name' => 'Accessories', 'sort_order' => 2]);
        
        $child1 = Category::factory()->create([
            'name' => 'Rings',
            'parent_id' => $parent1->id,
            'sort_order' => 1,
        ]);
        $child2 = Category::factory()->create([
            'name' => 'Necklaces',
            'parent_id' => $parent1->id,
            'sort_order' => 2,
        ]);

        $tree = $this->categoryService->getHierarchicalTree();

        $this->assertCount(2, $tree);
        
        $firstParent = $tree->first();
        $this->assertEquals('Jewelry', $firstParent['name']);
        $this->assertCount(2, $firstParent['children']);
        $this->assertEquals('Rings', $firstParent['children'][0]['name']);
        $this->assertEquals('Necklaces', $firstParent['children'][1]['name']);
    }

    /** @test */
    public function it_can_reorder_categories()
    {
        $category1 = Category::factory()->create(['sort_order' => 1]);
        $category2 = Category::factory()->create(['sort_order' => 2]);
        $category3 = Category::factory()->create(['sort_order' => 3]);

        $orderData = [
            ['id' => $category1->id, 'sort_order' => 3, 'parent_id' => null],
            ['id' => $category2->id, 'sort_order' => 1, 'parent_id' => null],
            ['id' => $category3->id, 'sort_order' => 2, 'parent_id' => null],
        ];

        $result = $this->categoryService->reorderCategories($orderData);

        $this->assertTrue($result);
        
        $this->assertEquals(3, $category1->fresh()->sort_order);
        $this->assertEquals(1, $category2->fresh()->sort_order);
        $this->assertEquals(2, $category3->fresh()->sort_order);
    }

    /** @test */
    public function it_validates_hierarchy_correctly()
    {
        $grandparent = Category::factory()->create();
        $parent = Category::factory()->create(['parent_id' => $grandparent->id]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        // Valid hierarchy
        $this->assertTrue($this->categoryService->validateHierarchy(null, $parent->id));
        
        // Self-reference
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('A category cannot be its own parent');
        $this->categoryService->validateHierarchy($parent->id, $parent->id);
    }

    /** @test */
    public function it_detects_circular_references()
    {
        $grandparent = Category::factory()->create();
        $parent = Category::factory()->create(['parent_id' => $grandparent->id]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This would create a circular reference');

        // Try to make grandparent a child of child
        $this->categoryService->validateHierarchy($grandparent->id, $child->id);
    }

    /** @test */
    public function it_can_get_categories_for_select()
    {
        $category1 = Category::factory()->create([
            'name' => 'Rings',
            'name_persian' => 'انگشتر',
            'is_active' => true,
        ]);
        $category2 = Category::factory()->create([
            'name' => 'Necklaces',
            'is_active' => false, // Inactive, should not appear
        ]);

        app()->setLocale('fa');
        $selectOptions = $this->categoryService->getCategoriesForSelect();

        $this->assertCount(1, $selectOptions);
        $this->assertEquals('انگشتر', $selectOptions->first()['name']);
    }

    /** @test */
    public function it_excludes_specified_category_from_select_options()
    {
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => true]);

        $selectOptions = $this->categoryService->getCategoriesForSelect($category1->id);

        $this->assertCount(1, $selectOptions);
        $this->assertEquals($category2->id, $selectOptions->first()['id']);
    }

    /** @test */
    public function it_can_get_main_categories()
    {
        $mainCategory = Category::factory()->create(['parent_id' => null, 'is_active' => true]);
        $subCategory = Category::factory()->create(['parent_id' => $mainCategory->id, 'is_active' => true]);

        $mainCategories = $this->categoryService->getMainCategories();

        $this->assertCount(1, $mainCategories);
        $this->assertEquals($mainCategory->id, $mainCategories->first()->id);
    }

    /** @test */
    public function it_can_get_subcategories()
    {
        $parent = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parent->id, 'is_active' => true]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id, 'is_active' => true]);
        Category::factory()->create(['parent_id' => $parent->id, 'is_active' => false]); // Inactive

        $subcategories = $this->categoryService->getSubcategories($parent->id);

        $this->assertCount(2, $subcategories);
    }

    /** @test */
    public function it_can_search_categories()
    {
        Category::factory()->create([
            'name' => 'Gold Rings',
            'name_persian' => 'انگشتر طلا',
            'code' => 'GOLD_RING',
            'is_active' => true,
        ]);
        Category::factory()->create([
            'name' => 'Silver Necklaces',
            'name_persian' => 'گردنبند نقره',
            'is_active' => true,
        ]);

        // Search by English name
        $results = $this->categoryService->searchCategories('Gold');
        $this->assertCount(1, $results);
        $this->assertEquals('Gold Rings', $results->first()->name);

        // Search by Persian name
        $results = $this->categoryService->searchCategories('گردنبند');
        $this->assertCount(1, $results);
        $this->assertEquals('Silver Necklaces', $results->first()->name);

        // Search by code
        $results = $this->categoryService->searchCategories('GOLD_RING');
        $this->assertCount(1, $results);
        $this->assertEquals('Gold Rings', $results->first()->name);
    }

    /** @test */
    public function it_can_get_category_path()
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

        $path = $this->categoryService->getCategoryPath($child);

        $this->assertCount(3, $path);
        $this->assertEquals('Jewelry', $path[0]['name']);
        $this->assertEquals('Rings', $path[1]['name']);
        $this->assertEquals('Wedding Rings', $path[2]['name']);
    }

    /** @test */
    public function it_returns_empty_path_for_root_category()
    {
        $root = Category::factory()->create(['parent_id' => null]);

        $path = $this->categoryService->getCategoryPath($root);

        $this->assertCount(1, $path);
        $this->assertEquals($root->name, $path[0]['name']);
    }

    /** @test */
    public function it_uses_database_transactions_for_create_operations()
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->categoryService->createCategory([
            'name' => 'Test Category',
            'code' => 'TEST',
        ]);
    }

    /** @test */
    public function it_uses_database_transactions_for_update_operations()
    {
        $category = Category::factory()->create();

        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->categoryService->updateCategory($category, [
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_uses_database_transactions_for_delete_operations()
    {
        $category = Category::factory()->create();

        DB::shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->categoryService->deleteCategory($category);
    }
}