<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CategoryImageService
{
    private ImageManager $imageManager;
    private ImageSecurityService $securityService;
    
    public function __construct(ImageSecurityService $securityService)
    {
        // Use GD driver for better Docker compatibility
        $this->imageManager = new ImageManager(new Driver());
        $this->securityService = $securityService;
    }

    /**
     * Upload and process a category image.
     */
    public function uploadImage(Category $category, UploadedFile $image, array $options = []): CategoryImage
    {
        // Perform security validation
        $securityErrors = $this->securityService->validateImageSecurity($image);
        if (!empty($securityErrors)) {
            throw new \Exception('Image security validation failed: ' . implode(', ', $securityErrors));
        }

        // Additional validation
        $this->validateImage($image);

        // Generate secure filename
        $filename = $this->securityService->generateSecureFilename($image->getClientOriginalExtension());
        $path = "categories/{$category->id}/{$filename}";

        // Process and store the image
        $processedImagePath = $this->processAndStoreImage($image, $path);

        // Create CategoryImage record
        $categoryImage = CategoryImage::create([
            'category_id' => $category->id,
            'image_path' => $processedImagePath,
            'alt_text' => $options['alt_text'] ?? $category->name,
            'alt_text_persian' => $options['alt_text_persian'] ?? $category->name_persian,
            'is_primary' => $options['is_primary'] ?? $this->shouldBePrimary($category),
            'sort_order' => $options['sort_order'] ?? $this->getNextSortOrder($category),
        ]);

        // If this is set as primary, unset other primary images
        if ($categoryImage->is_primary) {
            $this->setPrimaryImage($category, $categoryImage);
        }

        // Update category's direct image_path for backward compatibility
        if ($categoryImage->is_primary) {
            $category->update(['image_path' => $processedImagePath]);
        }

        Log::info('Category image uploaded', [
            'category_id' => $category->id,
            'image_id' => $categoryImage->id,
            'path' => $processedImagePath,
            'original_name' => $image->getClientOriginalName()
        ]);

        return $categoryImage->fresh();
    }

    /**
     * Remove a category image.
     */
    public function removeImage(CategoryImage $categoryImage): bool
    {
        $category = $categoryImage->category;
        $imagePath = $categoryImage->image_path;
        $wasPrimary = $categoryImage->is_primary;

        // Delete the file from storage
        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

        // Delete thumbnails if they exist
        $this->deleteThumbnails($imagePath);

        // Delete the database record
        $categoryImage->delete();

        // If this was the primary image, set another image as primary or clear category image_path
        if ($wasPrimary) {
            $nextPrimaryImage = $category->images()->first();
            if ($nextPrimaryImage) {
                $nextPrimaryImage->update(['is_primary' => true]);
                $category->update(['image_path' => $nextPrimaryImage->image_path]);
            } else {
                $category->update(['image_path' => null]);
            }
        }

        Log::info('Category image removed', [
            'category_id' => $category->id,
            'path' => $imagePath
        ]);

        return true;
    }

    /**
     * Remove category image by category (for backward compatibility).
     */
    public function removeCategoryImage(Category $category): bool
    {
        $primaryImage = $category->primaryImage;
        
        if ($primaryImage) {
            return $this->removeImage($primaryImage);
        }

        // Fallback: clear direct image_path
        if ($category->image_path) {
            if (Storage::disk('public')->exists($category->image_path)) {
                Storage::disk('public')->delete($category->image_path);
            }
            $category->update(['image_path' => null]);
            return true;
        }

        return false;
    }

    /**
     * Process and store image with optimization.
     */
    private function processAndStoreImage(UploadedFile $image, string $path): string
    {
        // Read and process the image
        $processedImage = $this->imageManager->read($image->getPathname());

        // Resize image while maintaining aspect ratio
        $processedImage->resize(400, 400, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Convert to WebP for better compression and compatibility
        $webpPath = $this->changeExtensionToWebp($path);
        
        // Encode as WebP with 85% quality
        $encodedImage = $processedImage->toWebp(85);

        // Store the processed image
        Storage::disk('public')->put($webpPath, $encodedImage);

        // Generate thumbnails
        $this->generateThumbnails($processedImage, $webpPath);

        return $webpPath;
    }

    /**
     * Generate thumbnails for different sizes.
     */
    private function generateThumbnails($image, string $originalPath): array
    {
        $thumbnails = [];
        $sizes = [
            'thumb' => [100, 100],
            'small' => [200, 200],
            'medium' => [300, 300],
        ];

        foreach ($sizes as $size => [$width, $height]) {
            $thumbnailPath = $this->getThumbnailPath($originalPath, $size);
            
            // Create thumbnail
            $thumbnail = clone $image;
            $thumbnail->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Store thumbnail
            Storage::disk('public')->put($thumbnailPath, $thumbnail->toWebp(80));
            $thumbnails[$size] = $thumbnailPath;
        }

        return $thumbnails;
    }

    /**
     * Delete thumbnails for an image.
     */
    private function deleteThumbnails(string $imagePath): void
    {
        $sizes = ['thumb', 'small', 'medium'];
        
        foreach ($sizes as $size) {
            $thumbnailPath = $this->getThumbnailPath($imagePath, $size);
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }
    }

    /**
     * Get thumbnail path for a given size.
     */
    private function getThumbnailPath(string $originalPath, string $size): string
    {
        $pathInfo = pathinfo($originalPath);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . "_{$size}." . $pathInfo['extension'];
    }

    /**
     * Validate uploaded image.
     */
    private function validateImage(UploadedFile $image): void
    {
        // Check file size (max 2MB)
        if ($image->getSize() > 2048 * 1024) {
            throw new \Exception('Image file size must be less than 2MB');
        }

        // Check image dimensions
        $imageInfo = getimagesize($image->getPathname());
        if (!$imageInfo) {
            throw new \Exception('Invalid image file');
        }

        [$width, $height] = $imageInfo;
        
        // Minimum dimensions
        if ($width < 100 || $height < 100) {
            throw new \Exception('Image dimensions must be at least 100x100 pixels');
        }

        // Maximum dimensions
        if ($width > 2000 || $height > 2000) {
            throw new \Exception('Image dimensions must not exceed 2000x2000 pixels');
        }

        // Check MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($image->getMimeType(), $allowedMimes)) {
            throw new \Exception('Image must be JPEG, PNG, WebP, or GIF format');
        }
    }

    /**
     * Generate unique filename for image.
     */
    private function generateFilename(UploadedFile $image): string
    {
        $extension = $image->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    /**
     * Change file extension to WebP.
     */
    private function changeExtensionToWebp(string $path): string
    {
        $pathInfo = pathinfo($path);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
    }

    /**
     * Determine if this should be the primary image.
     */
    private function shouldBePrimary(Category $category): bool
    {
        return !$category->images()->where('is_primary', true)->exists();
    }

    /**
     * Get next sort order for category images.
     */
    private function getNextSortOrder(Category $category): int
    {
        $maxOrder = $category->images()->max('sort_order');
        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Set an image as primary and unset others.
     */
    private function setPrimaryImage(Category $category, CategoryImage $primaryImage): void
    {
        // Unset all other primary images for this category
        $category->images()
            ->where('id', '!=', $primaryImage->id)
            ->update(['is_primary' => false]);
    }

    /**
     * Get image URL with size option.
     */
    public function getImageUrl(CategoryImage $categoryImage, string $size = 'original'): string
    {
        if ($size === 'original') {
            return Storage::disk('public')->url($categoryImage->image_path);
        }

        $thumbnailPath = $this->getThumbnailPath($categoryImage->image_path, $size);
        
        if (Storage::disk('public')->exists($thumbnailPath)) {
            return Storage::disk('public')->url($thumbnailPath);
        }

        // Fallback to original if thumbnail doesn't exist
        return Storage::disk('public')->url($categoryImage->image_path);
    }

    /**
     * Optimize existing category images (for maintenance).
     */
    public function optimizeExistingImages(): int
    {
        $optimizedCount = 0;
        $categoryImages = CategoryImage::all();

        foreach ($categoryImages as $categoryImage) {
            try {
                if (Storage::disk('public')->exists($categoryImage->image_path)) {
                    $imageContent = Storage::disk('public')->get($categoryImage->image_path);
                    $image = $this->imageManager->read($imageContent);
                    
                    // Re-process and save
                    $this->generateThumbnails($image, $categoryImage->image_path);
                    $optimizedCount++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to optimize category image', [
                    'image_id' => $categoryImage->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Category images optimized', ['count' => $optimizedCount]);
        
        return $optimizedCount;
    }

    /**
     * Clean up orphaned image files.
     */
    public function cleanupOrphanedImages(): int
    {
        $cleanedCount = 0;
        $categoryImagePaths = CategoryImage::pluck('image_path')->toArray();
        $categoryDirectPaths = Category::whereNotNull('image_path')->pluck('image_path')->toArray();
        $validPaths = array_merge($categoryImagePaths, $categoryDirectPaths);

        // Get all files in categories directory
        $allFiles = Storage::disk('public')->allFiles('categories');

        foreach ($allFiles as $file) {
            // Skip if this is a valid image or thumbnail
            $isValid = false;
            foreach ($validPaths as $validPath) {
                if ($file === $validPath || $this->isThumbnailOf($file, $validPath)) {
                    $isValid = true;
                    break;
                }
            }

            if (!$isValid) {
                Storage::disk('public')->delete($file);
                $cleanedCount++;
            }
        }

        Log::info('Orphaned category images cleaned', ['count' => $cleanedCount]);
        
        return $cleanedCount;
    }

    /**
     * Check if a file is a thumbnail of another file.
     */
    private function isThumbnailOf(string $file, string $originalFile): bool
    {
        $sizes = ['thumb', 'small', 'medium'];
        
        foreach ($sizes as $size) {
            if ($file === $this->getThumbnailPath($originalFile, $size)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clean up all images for a deleted category.
     */
    public function cleanupCategoryImages(Category $category): int
    {
        $cleanedCount = 0;
        
        // Get all images for this category
        $categoryImages = $category->images;
        
        foreach ($categoryImages as $categoryImage) {
            try {
                // Delete the main image file
                if (Storage::disk('public')->exists($categoryImage->image_path)) {
                    Storage::disk('public')->delete($categoryImage->image_path);
                    $cleanedCount++;
                }
                
                // Delete thumbnails
                $this->deleteThumbnails($categoryImage->image_path);
                
                // Delete the database record
                $categoryImage->delete();
                
            } catch (\Exception $e) {
                Log::warning('Failed to cleanup category image', [
                    'category_id' => $category->id,
                    'image_id' => $categoryImage->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Clean up the category directory if it's empty
        $categoryDir = "categories/{$category->id}";
        $files = Storage::disk('public')->files($categoryDir);
        
        if (empty($files)) {
            try {
                Storage::disk('public')->deleteDirectory($categoryDir);
                Log::info('Deleted empty category directory', ['directory' => $categoryDir]);
            } catch (\Exception $e) {
                Log::warning('Failed to delete category directory', [
                    'directory' => $categoryDir,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Clear category's direct image_path
        if ($category->image_path) {
            $category->update(['image_path' => null]);
        }
        
        Log::info('Category images cleaned up', [
            'category_id' => $category->id,
            'cleaned_count' => $cleanedCount
        ]);
        
        return $cleanedCount;
    }

    /**
     * Validate storage permissions and directory structure.
     */
    public function validateStorageSetup(): array
    {
        $issues = [];
        
        // Check if storage directory exists and is writable
        $storageDir = storage_path('app/public/categories');
        
        if (!is_dir($storageDir)) {
            try {
                mkdir($storageDir, 0755, true);
                Log::info('Created categories storage directory', ['path' => $storageDir]);
            } catch (\Exception $e) {
                $issues[] = "Cannot create storage directory: {$storageDir}";
            }
        }
        
        if (!is_writable($storageDir)) {
            $issues[] = "Storage directory is not writable: {$storageDir}";
        }
        
        // Check if public storage link exists
        $publicLink = public_path('storage');
        if (!is_link($publicLink) && !is_dir($publicLink)) {
            $issues[] = "Public storage link does not exist. Run 'php artisan storage:link'";
        }
        
        // Check GD extension
        if (!extension_loaded('gd')) {
            $issues[] = "GD extension is not loaded. Image processing will not work.";
        }
        
        // Check available disk space (warn if less than 1GB)
        $freeBytes = disk_free_space($storageDir);
        if ($freeBytes !== false && $freeBytes < 1024 * 1024 * 1024) {
            $issues[] = "Low disk space available: " . round($freeBytes / (1024 * 1024), 2) . " MB";
        }
        
        return $issues;
    }

    /**
     * Get storage statistics.
     */
    public function getStorageStats(): array
    {
        $stats = [
            'total_images' => CategoryImage::count(),
            'total_categories_with_images' => Category::whereHas('images')->count(),
            'storage_used_mb' => 0,
            'orphaned_files' => 0,
        ];
        
        // Calculate storage usage
        $allFiles = Storage::disk('public')->allFiles('categories');
        $totalSize = 0;
        
        foreach ($allFiles as $file) {
            $totalSize += Storage::disk('public')->size($file);
        }
        
        $stats['storage_used_mb'] = round($totalSize / (1024 * 1024), 2);
        
        // Count orphaned files
        $categoryImagePaths = CategoryImage::pluck('image_path')->toArray();
        $categoryDirectPaths = Category::whereNotNull('image_path')->pluck('image_path')->toArray();
        $validPaths = array_merge($categoryImagePaths, $categoryDirectPaths);
        
        $orphanedCount = 0;
        foreach ($allFiles as $file) {
            $isValid = false;
            foreach ($validPaths as $validPath) {
                if ($file === $validPath || $this->isThumbnailOf($file, $validPath)) {
                    $isValid = true;
                    break;
                }
            }
            if (!$isValid) {
                $orphanedCount++;
            }
        }
        
        $stats['orphaned_files'] = $orphanedCount;
        
        return $stats;
    }
}