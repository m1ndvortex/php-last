<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryImage;
use App\Services\CategoryImageService;
use App\Services\ImageSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryImageTest extends TestCase
{
    use RefreshDatabase;

    protected CategoryImageService $imageService;
    protected ImageSecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        $this->securityService = new ImageSecurityService();
        $this->imageService = new CategoryImageService($this->securityService);
    }

    /** @test */
    public function it_can_upload_and_process_category_image()
    {
        $category = Category::factory()->create();
        
        // Create a fake image
        $image = UploadedFile::fake()->image('test-image.jpg', 800, 600);
        
        $categoryImage = $this->imageService->uploadImage($category, $image);
        
        $this->assertInstanceOf(CategoryImage::class, $categoryImage);
        $this->assertEquals($category->id, $categoryImage->category_id);
        $this->assertTrue($categoryImage->is_primary);
        $this->assertNotNull($categoryImage->image_path);
        
        // Check that the image file was stored
        Storage::disk('public')->assertExists($categoryImage->image_path);
        
        // Check that thumbnails were generated
        $pathInfo = pathinfo($categoryImage->image_path);
        $thumbnailSizes = ['thumb', 'small', 'medium'];
        
        foreach ($thumbnailSizes as $size) {
            $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . "_{$size}." . $pathInfo['extension'];
            Storage::disk('public')->assertExists($thumbnailPath);
        }
    }

    /** @test */
    public function it_validates_image_security()
    {
        $category = Category::factory()->create();
        
        // Create a fake non-image file
        $file = UploadedFile::fake()->create('test.txt', 100, 'text/plain');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Image security validation failed');
        
        $this->imageService->uploadImage($category, $file);
    }

    /** @test */
    public function it_can_remove_category_image()
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test-image.jpg', 800, 600);
        
        $categoryImage = $this->imageService->uploadImage($category, $image);
        $imagePath = $categoryImage->image_path;
        
        // Verify image exists
        Storage::disk('public')->assertExists($imagePath);
        
        // Remove the image
        $result = $this->imageService->removeImage($categoryImage);
        
        $this->assertTrue($result);
        Storage::disk('public')->assertMissing($imagePath);
        $this->assertDatabaseMissing('category_images', ['id' => $categoryImage->id]);
    }

    /** @test */
    public function it_can_cleanup_category_images_on_deletion()
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test-image.jpg', 800, 600);
        
        $categoryImage = $this->imageService->uploadImage($category, $image);
        $imagePath = $categoryImage->image_path;
        
        // Verify image exists
        Storage::disk('public')->assertExists($imagePath);
        
        // Cleanup category images
        $cleanedCount = $this->imageService->cleanupCategoryImages($category);
        
        $this->assertEquals(1, $cleanedCount);
        Storage::disk('public')->assertMissing($imagePath);
        $this->assertDatabaseMissing('category_images', ['category_id' => $category->id]);
    }

    /** @test */
    public function it_can_get_image_url_with_different_sizes()
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test-image.jpg', 800, 600);
        
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
    public function it_can_validate_storage_setup()
    {
        $issues = $this->imageService->validateStorageSetup();
        
        // In test environment with fake storage, there should be no issues
        $this->assertIsArray($issues);
    }

    /** @test */
    public function it_can_get_storage_statistics()
    {
        $stats = $this->imageService->getStorageStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_images', $stats);
        $this->assertArrayHasKey('total_categories_with_images', $stats);
        $this->assertArrayHasKey('storage_used_mb', $stats);
        $this->assertArrayHasKey('orphaned_files', $stats);
    }

    /** @test */
    public function it_prevents_uploading_files_with_suspicious_content()
    {
        $category = Category::factory()->create();
        
        // Create a file with PHP content
        $suspiciousContent = '<?php echo "malicious code"; ?>';
        $file = UploadedFile::fake()->createWithContent('malicious.jpg', $suspiciousContent);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Image security validation failed');
        
        $this->imageService->uploadImage($category, $file);
    }

    /** @test */
    public function it_converts_images_to_webp_format()
    {
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->image('test-image.jpg', 800, 600);
        
        $categoryImage = $this->imageService->uploadImage($category, $image);
        
        // Check that the stored image has .webp extension
        $this->assertStringEndsWith('.webp', $categoryImage->image_path);
        
        // Verify the file exists in storage
        Storage::disk('public')->assertExists($categoryImage->image_path);
    }
}