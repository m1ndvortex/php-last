<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\CategoryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryImageModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'category_id',
            'image_path',
            'alt_text',
            'alt_text_persian',
            'is_primary',
            'sort_order',
        ];

        $categoryImage = new CategoryImage();
        $this->assertEquals($fillable, $categoryImage->getFillable());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $categoryImage = CategoryImage::factory()->create([
            'is_primary' => 1,
            'sort_order' => 5,
        ]);

        $this->assertIsBool($categoryImage->is_primary);
        $this->assertIsInt($categoryImage->sort_order);
    }

    /** @test */
    public function it_belongs_to_category()
    {
        $category = Category::factory()->create();
        $categoryImage = CategoryImage::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $categoryImage->category);
        $this->assertEquals($category->id, $categoryImage->category->id);
    }

    /** @test */
    public function it_returns_localized_alt_text_in_english()
    {
        app()->setLocale('en');
        
        $categoryImage = CategoryImage::factory()->create([
            'alt_text' => 'Gold ring',
            'alt_text_persian' => 'انگشتر طلا',
        ]);

        $this->assertEquals('Gold ring', $categoryImage->localized_alt_text);
    }

    /** @test */
    public function it_returns_localized_alt_text_in_persian()
    {
        app()->setLocale('fa');
        
        $categoryImage = CategoryImage::factory()->create([
            'alt_text' => 'Gold ring',
            'alt_text_persian' => 'انگشتر طلا',
        ]);

        $this->assertEquals('انگشتر طلا', $categoryImage->localized_alt_text);
    }

    /** @test */
    public function it_falls_back_to_english_alt_text_when_persian_is_null()
    {
        app()->setLocale('fa');
        
        $categoryImage = CategoryImage::factory()->create([
            'alt_text' => 'Gold ring',
            'alt_text_persian' => null,
        ]);

        $this->assertEquals('Gold ring', $categoryImage->localized_alt_text);
    }

    /** @test */
    public function it_returns_full_url_for_image()
    {
        Storage::fake('public');
        
        $categoryImage = CategoryImage::factory()->create([
            'image_path' => 'categories/test-image.webp',
        ]);

        $fullUrl = $categoryImage->full_url;
        $this->assertStringContainsString('categories/test-image.webp', $fullUrl);
    }

    /** @test */
    public function it_returns_absolute_path_for_image()
    {
        Storage::fake('public');
        
        $categoryImage = CategoryImage::factory()->create([
            'image_path' => 'categories/test-image.webp',
        ]);

        $absolutePath = $categoryImage->absolute_path;
        $this->assertStringContainsString('categories/test-image.webp', $absolutePath);
    }

    /** @test */
    public function it_scopes_primary_images()
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

        $primaryImages = CategoryImage::primary()->get();
        
        $this->assertCount(1, $primaryImages);
        $this->assertEquals($primaryImage->id, $primaryImages->first()->id);
        $this->assertTrue($primaryImages->first()->is_primary);
    }

    /** @test */
    public function it_scopes_ordered_images()
    {
        $category = Category::factory()->create();
        
        $image3 = CategoryImage::factory()->create([
            'category_id' => $category->id,
            'sort_order' => 3,
        ]);
        $image1 = CategoryImage::factory()->create([
            'category_id' => $category->id,
            'sort_order' => 1,
        ]);
        $image2 = CategoryImage::factory()->create([
            'category_id' => $category->id,
            'sort_order' => 2,
        ]);

        $orderedImages = CategoryImage::ordered()->get();
        
        $this->assertEquals($image1->id, $orderedImages->first()->id);
        $this->assertEquals($image2->id, $orderedImages->get(1)->id);
        $this->assertEquals($image3->id, $orderedImages->last()->id);
    }

    /** @test */
    public function it_orders_by_created_at_when_sort_order_is_same()
    {
        $category = Category::factory()->create();
        
        // Create images with same sort_order but different timestamps
        $olderImage = CategoryImage::factory()->create([
            'category_id' => $category->id,
            'sort_order' => 1,
            'created_at' => now()->subHour(),
        ]);
        $newerImage = CategoryImage::factory()->create([
            'category_id' => $category->id,
            'sort_order' => 1,
            'created_at' => now(),
        ]);

        $orderedImages = CategoryImage::ordered()->get();
        
        $this->assertEquals($olderImage->id, $orderedImages->first()->id);
        $this->assertEquals($newerImage->id, $orderedImages->last()->id);
    }

    /** @test */
    public function it_can_be_created_with_all_attributes()
    {
        $category = Category::factory()->create();
        
        $categoryImage = CategoryImage::create([
            'category_id' => $category->id,
            'image_path' => 'categories/test-image.webp',
            'alt_text' => 'Test image',
            'alt_text_persian' => 'تصویر تست',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('category_images', [
            'id' => $categoryImage->id,
            'category_id' => $category->id,
            'image_path' => 'categories/test-image.webp',
            'alt_text' => 'Test image',
            'alt_text_persian' => 'تصویر تست',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
    }

    /** @test */
    public function it_has_default_values_for_optional_attributes()
    {
        $category = Category::factory()->create();
        
        $categoryImage = CategoryImage::create([
            'category_id' => $category->id,
            'image_path' => 'categories/test-image.webp',
        ]);

        $this->assertFalse((bool)$categoryImage->is_primary);
        $this->assertEquals(0, $categoryImage->sort_order);
        $this->assertNull($categoryImage->alt_text);
        $this->assertNull($categoryImage->alt_text_persian);
    }

    /** @test */
    public function it_can_update_attributes()
    {
        $categoryImage = CategoryImage::factory()->create([
            'alt_text' => 'Original text',
            'is_primary' => false,
        ]);

        $categoryImage->update([
            'alt_text' => 'Updated text',
            'is_primary' => true,
        ]);

        $this->assertEquals('Updated text', $categoryImage->fresh()->alt_text);
        $this->assertTrue($categoryImage->fresh()->is_primary);
    }

    /** @test */
    public function it_can_be_deleted()
    {
        $categoryImage = CategoryImage::factory()->create();
        $imageId = $categoryImage->id;

        $categoryImage->delete();

        $this->assertDatabaseMissing('category_images', ['id' => $imageId]);
    }

    /** @test */
    public function it_is_deleted_when_category_is_deleted()
    {
        $category = Category::factory()->create();
        $categoryImage = CategoryImage::factory()->create(['category_id' => $category->id]);
        $imageId = $categoryImage->id;

        $category->delete();

        $this->assertDatabaseMissing('category_images', ['id' => $imageId]);
    }
}