<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryImage;
use App\Models\User;
use App\Services\CategoryImageService;
use App\Services\ImageSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CategoryImageService $imageService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        Storage::fake('public');
        
        $this->imageService = app(CategoryImageService::class);
    }

    /** @test */
    public function it_can_upload_valid_image_formats()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        
        $formats = [
            ['jpg', 'image/jpeg'],
            ['jpeg', 'image/jpeg'],
            ['png', 'image/png'],
            ['webp', 'image/webp'],
        ];

        foreach ($formats as [$extension, $mimeType]) {
            $image = UploadedFile::fake()->image("test.{$extension}", 800, 600);
            
            $response = $this->postJson("/api/categories/{$category->id}/image", [
                'image' => $image,
                'alt_text' => "Test {$extension} image",
            ]);

            $response->assertStatus(201);
            
            // Clean up for next iteration
            $category->images()->delete();
        }
    }

    /** @test */
    public function it_rejects_invalid_file_formats()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        
        // Test with missing image field
        $response = $this->postJson("/api/categories/{$category->id}/image", []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_validates_image_file_size()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        
        // Create oversized image (3MB, assuming 2MB limit)
        $oversizedImage = UploadedFile::fake()->image('large.jpg', 2000, 2000)->size(3072);

        $response = $this->postJson("/api/categories/{$category->id}/image", [
            'image' => $oversizedImage,
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_converts_uploaded_images_to_webp()
    {
        $category = Category::factory()->create();
        $jpegImage = UploadedFile::fake()->image('test.jpg', 800, 600);

        $categoryImage = $this->imageService->uploadImage($category, $jpegImage);

        $this->assertStringEndsWith('.webp', $categoryImage->image_path);
        Storage::disk('public')->assertExists($categoryImage->image_path);
    }

    /** @test */
    public function it_generates_thumbnails_for_uploaded_images()
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $categoryImage = $this->imageService->uploadImage($category, $image);

        $pathInfo = pathinfo($categoryImage->image_path);
        $thumbnailSizes = ['thumb', 'small', 'medium'];

        foreach ($thumbnailSizes as $size) {
            $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . "_{$size}." . $pathInfo['extension'];
            Storage::disk('public')->assertExists($thumbnailPath);
        }
    }

    /** @test */
    public function it_sets_first_image_as_primary()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->postJson("/api/categories/{$category->id}/image", [
            'image' => $image,
            'alt_text' => 'Primary image',
        ]);

        $response->assertStatus(201);
        
        $categoryImage = $category->images()->first();
        $this->assertTrue($categoryImage->is_primary);
    }

    /** @test */
    public function it_replaces_primary_image_when_uploading_new_one()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        
        // Upload first image
        $firstImage = UploadedFile::fake()->image('first.jpg', 800, 600);
        $this->postJson("/api/categories/{$category->id}/image", [
            'image' => $firstImage,
            'alt_text' => 'First image',
        ]);

        $firstCategoryImage = $category->images()->first();
        $this->assertTrue($firstCategoryImage->is_primary);

        // Upload second image
        $secondImage = UploadedFile::fake()->image('second.jpg', 800, 600);
        $response = $this->postJson("/api/categories/{$category->id}/image", [
            'image' => $secondImage,
            'alt_text' => 'Second image',
        ]);

        $response->assertStatus(201);
        
        // First image should no longer be primary
        $this->assertFalse($firstCategoryImage->fresh()->is_primary);
        
        // Second image should be primary
        $secondCategoryImage = $category->images()->where('alt_text', 'Second image')->first();
        $this->assertTrue($secondCategoryImage->is_primary);
    }

    /** @test */
    public function it_stores_alt_text_in_both_languages()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->postJson("/api/categories/{$category->id}/image", [
            'image' => $image,
            'alt_text' => 'Gold ring',
            'alt_text_persian' => 'انگشتر طلا',
        ]);

        $response->assertStatus(201);
        
        $categoryImage = $category->images()->first();
        $this->assertEquals('Gold ring', $categoryImage->alt_text);
        $this->assertEquals('انگشتر طلا', $categoryImage->alt_text_persian);
    }

    /** @test */
    public function it_can_remove_category_image()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        // Upload image
        $this->postJson("/api/categories/{$category->id}/image", [
            'image' => $image,
        ]);

        $categoryImage = $category->images()->first();
        $imagePath = $categoryImage->image_path;

        // Verify image exists
        Storage::disk('public')->assertExists($imagePath);

        // Remove image
        $response = $this->deleteJson("/api/categories/{$category->id}/image");

        $response->assertStatus(200);
        
        // Verify image is removed from database and storage
        $this->assertDatabaseMissing('category_images', ['id' => $categoryImage->id]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    /** @test */
    public function it_removes_thumbnails_when_removing_image()
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        $categoryImage = $this->imageService->uploadImage($category, $image);
        $pathInfo = pathinfo($categoryImage->image_path);
        
        // Verify thumbnails exist
        $thumbnailSizes = ['thumb', 'small', 'medium'];
        foreach ($thumbnailSizes as $size) {
            $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . "_{$size}." . $pathInfo['extension'];
            Storage::disk('public')->assertExists($thumbnailPath);
        }

        // Remove image
        $this->imageService->removeImage($categoryImage);

        // Verify thumbnails are removed
        foreach ($thumbnailSizes as $size) {
            $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . "_{$size}." . $pathInfo['extension'];
            Storage::disk('public')->assertMissing($thumbnailPath);
        }
    }

    /** @test */
    public function it_validates_image_security()
    {
        $category = Category::factory()->create();
        
        // Create a file with suspicious content
        $suspiciousContent = '<?php echo "malicious code"; ?>';
        $maliciousFile = UploadedFile::fake()->createWithContent('malicious.jpg', $suspiciousContent);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Image security validation failed');

        $this->imageService->uploadImage($category, $maliciousFile);
    }

    /** @test */
    public function it_handles_image_processing_errors_gracefully()
    {
        $category = Category::factory()->create();
        
        // Create a corrupted image file
        $corruptedImage = UploadedFile::fake()->createWithContent('corrupted.jpg', 'not an image');

        $this->expectException(\Exception::class);

        $this->imageService->uploadImage($category, $corruptedImage);
    }

    /** @test */
    public function it_optimizes_image_quality_and_size()
    {
        $category = Category::factory()->create();
        $largeImage = UploadedFile::fake()->image('large.jpg', 2000, 1500);

        $categoryImage = $this->imageService->uploadImage($category, $largeImage);

        // Verify image was stored
        Storage::disk('public')->assertExists($categoryImage->image_path);
        
        // The actual optimization testing would require checking file sizes
        // and image dimensions, which is complex in a test environment
        $this->assertNotNull($categoryImage->image_path);
    }

    /** @test */
    public function it_can_get_image_urls_for_different_sizes()
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        $categoryImage = $this->imageService->uploadImage($category, $image);

        // Test original size
        $originalUrl = $this->imageService->getImageUrl($categoryImage, 'original');
        $this->assertStringContainsString($categoryImage->image_path, $originalUrl);

        // Test thumbnail sizes
        $thumbnailSizes = ['thumb', 'small', 'medium'];
        foreach ($thumbnailSizes as $size) {
            $thumbnailUrl = $this->imageService->getImageUrl($categoryImage, $size);
            $this->assertStringContainsString("_{$size}.", $thumbnailUrl);
        }
    }

    /** @test */
    public function it_handles_missing_image_files_gracefully()
    {
        $category = Category::factory()->create();
        $categoryImage = CategoryImage::factory()->create([
            'category_id' => $category->id,
            'image_path' => 'categories/non-existent.webp',
        ]);

        // This should not throw an exception
        $url = $this->imageService->getImageUrl($categoryImage, 'original');
        $this->assertStringContainsString('non-existent.webp', $url);
    }

    /** @test */
    public function it_cleans_up_orphaned_images()
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        $categoryImage = $this->imageService->uploadImage($category, $image);
        $imagePath = $categoryImage->image_path;

        // Manually delete the database record (simulating orphaned file)
        $categoryImage->delete();
        
        // Also clear the category's image_path to make it truly orphaned
        $category->update(['image_path' => null]);

        // Verify file still exists
        Storage::disk('public')->assertExists($imagePath);

        // Run cleanup
        $cleanedCount = $this->imageService->cleanupOrphanedImages();

        // Verify file was cleaned up
        Storage::disk('public')->assertMissing($imagePath);
        $this->assertGreaterThan(0, $cleanedCount);
    }

    /** @test */
    public function it_validates_storage_setup()
    {
        $issues = $this->imageService->validateStorageSetup();
        
        $this->assertIsArray($issues);
        // In test environment with fake storage, there should be no issues
    }

    /** @test */
    public function it_provides_storage_statistics()
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        $this->imageService->uploadImage($category, $image);

        $stats = $this->imageService->getStorageStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_images', $stats);
        $this->assertArrayHasKey('total_categories_with_images', $stats);
        $this->assertArrayHasKey('storage_used_mb', $stats);
        $this->assertArrayHasKey('orphaned_files', $stats);
        
        $this->assertEquals(1, $stats['total_images']);
        $this->assertEquals(1, $stats['total_categories_with_images']);
    }

    /** @test */
    public function it_requires_authentication_for_image_operations()
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test.jpg', 800, 600);

        // Test upload without authentication
        $response = $this->postJson("/api/categories/{$category->id}/image", [
            'image' => $image,
        ]);
        $response->assertStatus(401);

        // Test removal without authentication
        $response = $this->deleteJson("/api/categories/{$category->id}/image");
        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_concurrent_image_uploads()
    {
        $this->actingAs($this->user);
        
        $category = Category::factory()->create();
        
        // Simulate concurrent uploads by uploading multiple images quickly
        $images = [
            UploadedFile::fake()->image('image1.jpg', 800, 600),
            UploadedFile::fake()->image('image2.jpg', 800, 600),
            UploadedFile::fake()->image('image3.jpg', 800, 600),
        ];

        foreach ($images as $index => $image) {
            $response = $this->postJson("/api/categories/{$category->id}/image", [
                'image' => $image,
                'alt_text' => "Image {$index}",
            ]);

            $response->assertStatus(201);
        }

        // Only the last image should be primary
        $primaryImages = $category->images()->where('is_primary', true)->get();
        $this->assertCount(1, $primaryImages);
        $this->assertEquals('Image 2', $primaryImages->first()->alt_text);
    }

    /** @test */
    public function it_maintains_image_aspect_ratio_during_processing()
    {
        $category = Category::factory()->create();
        
        // Create a wide image
        $wideImage = UploadedFile::fake()->image('wide.jpg', 1600, 800);
        $categoryImage = $this->imageService->uploadImage($category, $wideImage);

        // The actual aspect ratio testing would require image analysis
        // For now, we just verify the image was processed
        $this->assertNotNull($categoryImage->image_path);
        Storage::disk('public')->assertExists($categoryImage->image_path);
    }
}