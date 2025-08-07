<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryHierarchyValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->categoryService = app(CategoryService::class);
    }

    /** @test */
    public function it_prevents_category_from_being_its_own_parent()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => $category->name,
            'code' => $category->code,
            'parent_id' => $category->id,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['parent_id']);
    }

    /** @test */
    public function it_prevents_direct_circular_reference()
    {
        $this->actingAs($this->user);
        
        $categoryA = Category::factory()->create(['name' => 'Category A']);
        $categoryB = Category::factory()->create([
            'name' => 'Category B',
            'parent_id' => $categoryA->id,
        ]);

        // Try to make A a child of B (direct circular reference)
        $response = $this->putJson("/api/categories/{$categoryA->id}", [
            'name' => $categoryA->name,
            'code' => $categoryA->code,
            'parent_id' => $categoryB->id,
        ]);

        $response->assertStatus(422)
                ->assertJsonFragment([
                    'message' => 'This would create a circular reference'
                ]);
    }

    /** @test */
    public function it_prevents_indirect_circular_reference()
    {
        $this->actingAs($this->user);
        
        // Create hierarchy: A -> B -> C
        $categoryA = Category::factory()->create(['name' => 'Category A']);
        $categoryB = Category::factory()->create([
            'name' => 'Category B',
            'parent_id' => $categoryA->id,
        ]);
        $categoryC = Category::factory()->create([
            'name' => 'Category C',
            'parent_id' => $categoryB->id,
        ]);

        // Try to make A a child of C (indirect circular reference)
        $response = $this->putJson("/api/categories/{$categoryA->id}", [
            'name' => $categoryA->name,
            'code' => $categoryA->code,
            'parent_id' => $categoryC->id,
        ]);

        $response->assertStatus(422)
                ->assertJsonFragment([
                    'message' => 'This would create a circular reference'
                ]);
    }

    /** @test */
    public function it_prevents_deep_circular_reference()
    {
        $this->actingAs($this->user);
        
        // Create deep hierarchy: A -> B -> C -> D -> E
        $categoryA = Category::factory()->create(['name' => 'Category A']);
        $categoryB = Category::factory()->create([
            'name' => 'Category B',
            'parent_id' => $categoryA->id,
        ]);
        $categoryC = Category::factory()->create([
            'name' => 'Category C',
            'parent_id' => $categoryB->id,
        ]);
        $categoryD = Category::factory()->create([
            'name' => 'Category D',
            'parent_id' => $categoryC->id,
        ]);
        $categoryE = Category::factory()->create([
            'name' => 'Category E',
            'parent_id' => $categoryD->id,
        ]);

        // Try to make A a child of E (deep circular reference)
        $response = $this->putJson("/api/categories/{$categoryA->id}", [
            'name' => $categoryA->name,
            'code' => $categoryA->code,
            'parent_id' => $categoryE->id,
        ]);

        $response->assertStatus(422)
                ->assertJsonFragment([
                    'message' => 'This would create a circular reference'
                ]);
    }

    /** @test */
    public function it_allows_valid_parent_changes()
    {
        $this->actingAs($this->user);
        
        $categoryA = Category::factory()->create(['name' => 'Category A']);
        $categoryB = Category::factory()->create(['name' => 'Category B']);
        $categoryC = Category::factory()->create([
            'name' => 'Category C',
            'parent_id' => $categoryA->id,
        ]);

        // Move C from A to B (valid operation)
        $response = $this->putJson("/api/categories/{$categoryC->id}", [
            'name' => $categoryC->name,
            'code' => $categoryC->code,
            'parent_id' => $categoryB->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($categoryB->id, $categoryC->fresh()->parent_id);
    }

    /** @test */
    public function it_allows_making_category_root_level()
    {
        $this->actingAs($this->user);
        
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        // Make child a root category
        $response = $this->putJson("/api/categories/{$child->id}", [
            'name' => $child->name,
            'code' => $child->code,
            'parent_id' => null,
        ]);

        $response->assertStatus(200);
        $this->assertNull($child->fresh()->parent_id);
    }

    /** @test */
    public function it_validates_hierarchy_when_creating_new_category()
    {
        $this->actingAs($this->user);
        
        $parent = Category::factory()->create();

        // Valid creation
        $response = $this->postJson('/api/categories', [
            'name' => 'Child Category',
            'code' => 'CHILD',
            'parent_id' => $parent->id,
        ]);

        $response->assertStatus(201);
        
        $newCategory = Category::where('code', 'CHILD')->first();
        $this->assertEquals($parent->id, $newCategory->parent_id);
    }

    /** @test */
    public function it_validates_parent_exists_when_creating_category()
    {
        $this->actingAs($this->user);
        
        $response = $this->postJson('/api/categories', [
            'name' => 'Child Category',
            'code' => 'CHILD',
            'parent_id' => 999, // Non-existent parent
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['parent_id']);
    }

    /** @test */
    public function it_validates_parent_exists_when_updating_category()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();

        $response = $this->putJson("/api/categories/{$category->id}", [
            'name' => $category->name,
            'code' => $category->code,
            'parent_id' => 999, // Non-existent parent
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['parent_id']);
    }

    /** @test */
    public function it_allows_complex_valid_hierarchy_changes()
    {
        $this->actingAs($this->user);
        
        // Create initial structure:
        // A -> B -> C
        // D -> E
        $categoryA = Category::factory()->create(['name' => 'A']);
        $categoryB = Category::factory()->create(['name' => 'B', 'parent_id' => $categoryA->id]);
        $categoryC = Category::factory()->create(['name' => 'C', 'parent_id' => $categoryB->id]);
        $categoryD = Category::factory()->create(['name' => 'D']);
        $categoryE = Category::factory()->create(['name' => 'E', 'parent_id' => $categoryD->id]);

        // Move B (and its children) under D
        // Result should be: A, D -> B -> C, D -> E
        $response = $this->putJson("/api/categories/{$categoryB->id}", [
            'name' => $categoryB->name,
            'code' => $categoryB->code,
            'parent_id' => $categoryD->id,
        ]);

        $response->assertStatus(200);
        
        $this->assertEquals($categoryD->id, $categoryB->fresh()->parent_id);
        $this->assertEquals($categoryB->id, $categoryC->fresh()->parent_id); // C should still be under B
    }

    /** @test */
    public function it_detects_existing_circular_references_in_corrupted_data()
    {
        // This test simulates detecting circular references in already corrupted data
        // In practice, this shouldn't happen due to validation, but we test the detection logic
        
        $categoryA = Category::factory()->create(['name' => 'A']);
        $categoryB = Category::factory()->create(['name' => 'B', 'parent_id' => $categoryA->id]);
        
        // Manually create circular reference in database (bypassing validation)
        \DB::table('categories')
            ->where('id', $categoryA->id)
            ->update(['parent_id' => $categoryB->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This would create a circular reference');

        // This should detect the existing circular reference
        $this->categoryService->validateHierarchy($categoryA->id, $categoryB->id);
    }

    /** @test */
    public function it_handles_null_parent_id_correctly()
    {
        $category = Category::factory()->create();

        // Validate with null parent (making it root) should always pass
        $result = $this->categoryService->validateHierarchy($category->id, null);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_validates_hierarchy_in_service_layer()
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        // Test service layer validation directly
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('This would create a circular reference');

        $this->categoryService->validateHierarchy($parent->id, $child->id);
    }

    /** @test */
    public function it_allows_sibling_relationships()
    {
        $this->actingAs($this->user);
        
        $parent = Category::factory()->create();
        $sibling1 = Category::factory()->create(['parent_id' => $parent->id]);
        $sibling2 = Category::factory()->create(['parent_id' => $parent->id]);

        // Move sibling1 to be under sibling2 (valid - not circular)
        $response = $this->putJson("/api/categories/{$sibling1->id}", [
            'name' => $sibling1->name,
            'code' => $sibling1->code,
            'parent_id' => $sibling2->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($sibling2->id, $sibling1->fresh()->parent_id);
    }

    /** @test */
    public function it_prevents_moving_parent_under_its_descendant()
    {
        $this->actingAs($this->user);
        
        // Create hierarchy: A -> B -> C -> D
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create(['parent_id' => $categoryA->id]);
        $categoryC = Category::factory()->create(['parent_id' => $categoryB->id]);
        $categoryD = Category::factory()->create(['parent_id' => $categoryC->id]);

        // Try to move B under D (B is ancestor of D)
        $response = $this->putJson("/api/categories/{$categoryB->id}", [
            'name' => $categoryB->name,
            'code' => $categoryB->code,
            'parent_id' => $categoryD->id,
        ]);

        $response->assertStatus(422)
                ->assertJsonFragment([
                    'message' => 'This would create a circular reference'
                ]);
    }

    /** @test */
    public function it_handles_reordering_with_hierarchy_validation()
    {
        $this->actingAs($this->user);
        
        $parent = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 1]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 2]);

        // Valid reordering within same parent
        $response = $this->postJson('/api/categories/reorder', [
            'categories' => [
                ['id' => $child2->id, 'sort_order' => 1, 'parent_id' => $parent->id],
                ['id' => $child1->id, 'sort_order' => 2, 'parent_id' => $parent->id],
            ]
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_maximum_hierarchy_depth()
    {
        $this->actingAs($this->user);
        
        // Create a deep hierarchy (assuming we want to limit depth)
        $current = null;
        $categories = [];
        
        // Create 10 levels deep
        for ($i = 1; $i <= 10; $i++) {
            $category = Category::factory()->create([
                'name' => "Level {$i}",
                'code' => "LEVEL{$i}",
                'parent_id' => $current,
            ]);
            $categories[] = $category;
            $current = $category->id;
        }

        // This should still work (no depth limit implemented yet)
        $response = $this->postJson('/api/categories', [
            'name' => 'Level 11',
            'code' => 'LEVEL11',
            'parent_id' => $current,
        ]);

        $response->assertStatus(201);
    }
}