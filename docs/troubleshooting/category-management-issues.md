# Category Management Troubleshooting Guide

## Overview

This guide covers common issues encountered with the jewelry category management system and their solutions. Issues are organized by component and include step-by-step resolution instructions.

## Quick Diagnostic Checklist

Before diving into specific issues, run this quick diagnostic:

```bash
# Check system status
php artisan category:health-check

# Verify database connectivity
php artisan migrate:status

# Check file permissions
ls -la storage/app/public/categories/

# Test image processing
php artisan tinker
>>> Image::make('test.jpg')->resize(100, 100);

# Check API endpoints
curl -H "Authorization: Bearer {token}" http://localhost/api/categories
```

## Database Issues

### Issue: Migration Fails - Categories Table

**Symptoms:**
- Error during `php artisan migrate`
- "Table 'categories' already exists" error
- Missing columns in existing categories table

**Diagnosis:**
```bash
# Check current table structure
php artisan tinker
>>> Schema::hasTable('categories');
>>> Schema::getColumnListing('categories');
```

**Solutions:**

1. **Fresh Migration (Development Only):**
```bash
php artisan migrate:fresh --seed
```

2. **Add Missing Columns:**
```bash
# Create specific migration
php artisan make:migration add_jewelry_fields_to_categories_table

# Add in migration file:
Schema::table('categories', function (Blueprint $table) {
    $table->decimal('default_gold_purity', 5, 3)->nullable();
    $table->string('image_path')->nullable();
    $table->integer('sort_order')->default(0);
    $table->json('specifications')->nullable();
});
```

3. **Fix Existing Data:**
```sql
-- Update existing categories with default values
UPDATE categories SET sort_order = id WHERE sort_order IS NULL;
UPDATE categories SET specifications = '{}' WHERE specifications IS NULL;
```

### Issue: Foreign Key Constraint Errors

**Symptoms:**
- Cannot delete categories
- "Cannot add or update a child row" error
- Constraint violation on category_id

**Diagnosis:**
```sql
-- Check foreign key constraints
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_NAME = 'categories';
```

**Solutions:**

1. **Check Dependencies:**
```php
// In tinker
$category = Category::find(1);
echo "Items: " . $category->inventoryItems()->count();
echo "Children: " . $category->children()->count();
```

2. **Safe Deletion:**
```php
// Move items to another category first
$oldCategory = Category::find(1);
$newCategory = Category::find(2);

$oldCategory->inventoryItems()->update(['category_id' => $newCategory->id]);
$oldCategory->delete();
```

3. **Fix Orphaned Records:**
```sql
-- Find orphaned inventory items
SELECT * FROM inventory_items 
WHERE category_id NOT IN (SELECT id FROM categories);

-- Fix by setting to null or default category
UPDATE inventory_items 
SET category_id = NULL 
WHERE category_id NOT IN (SELECT id FROM categories);
```

## Image Upload Issues

### Issue: Images Not Uploading

**Symptoms:**
- Upload button not working
- "File too large" errors
- Images not appearing after upload

**Diagnosis:**
```bash
# Check PHP upload settings
php -i | grep -E "(upload_max_filesize|post_max_size|max_file_uploads)"

# Check storage permissions
ls -la storage/app/public/categories/

# Check disk space
df -h storage/

# Test image processing
php artisan tinker
>>> $image = \Intervention\Image\ImageManagerStatic::make('test.jpg');
>>> echo "GD available: " . (extension_loaded('gd') ? 'Yes' : 'No');
```

**Solutions:**

1. **Fix PHP Configuration:**
```ini
; In php.ini
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 20
memory_limit = 256M
```

2. **Fix Directory Permissions:**
```bash
# Create directories if missing
mkdir -p storage/app/public/categories
mkdir -p storage/app/public/thumbnails

# Set proper permissions
chmod -R 775 storage/app/public/
chown -R www-data:www-data storage/app/public/

# Recreate storage link
php artisan storage:link
```

3. **Fix Web Server Limits:**
```nginx
# In nginx.conf
client_max_body_size 10M;
client_body_timeout 60s;
```

```apache
# In .htaccess or virtual host
LimitRequestBody 10485760
```

### Issue: Images Display as Broken

**Symptoms:**
- Broken image icons in interface
- 404 errors for image URLs
- Images upload but don't display

**Diagnosis:**
```bash
# Check if files exist
ls -la storage/app/public/categories/

# Check symbolic link
ls -la public/storage

# Test direct access
curl -I http://localhost/storage/categories/test-image.webp

# Check nginx/apache logs
tail -f /var/log/nginx/error.log
```

**Solutions:**

1. **Fix Storage Link:**
```bash
# Remove existing link
rm public/storage

# Recreate storage link
php artisan storage:link

# Verify link
ls -la public/storage
```

2. **Fix Web Server Configuration:**
```nginx
# Add to nginx config
location ~* ^/storage/.*\.(jpg|jpeg|png|gif|webp|svg)$ {
    root /var/www/html/storage/app/public;
    try_files $uri =404;
}
```

3. **Fix File Paths:**
```php
// Check in tinker
$category = Category::find(1);
echo "Image path: " . $category->image_path;
echo "Full URL: " . Storage::url($category->image_path);
echo "File exists: " . (Storage::exists($category->image_path) ? 'Yes' : 'No');
```

### Issue: Image Processing Fails

**Symptoms:**
- Images upload but aren't resized
- Memory limit errors during processing
- Corrupted image files

**Diagnosis:**
```bash
# Check image processing extensions
php -m | grep -E "(gd|imagick)"

# Check memory usage
php -i | grep memory_limit

# Test image processing
php artisan tinker
>>> $manager = new \Intervention\Image\ImageManager(['driver' => 'gd']);
>>> $image = $manager->make('test.jpg');
>>> echo "Success";
```

**Solutions:**

1. **Install Missing Extensions:**
```bash
# Ubuntu/Debian
sudo apt-get install php8.2-gd php8.2-imagick

# CentOS/RHEL
sudo yum install php-gd php-imagick

# Restart web server
sudo systemctl restart nginx php8.2-fpm
```

2. **Increase Memory Limit:**
```ini
; In php.ini
memory_limit = 512M
max_execution_time = 300
```

3. **Fix Image Processing Service:**
```php
// In CategoryImageService, add error handling
public function processImage(UploadedFile $file): string
{
    try {
        $manager = new ImageManager(['driver' => 'gd']);
        $image = $manager->make($file);
        
        // Process with error handling
        $processed = $image->resize(400, 400, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        return $this->storeImage($processed);
    } catch (\Exception $e) {
        \Log::error('Image processing failed: ' . $e->getMessage());
        throw new \Exception('Image processing failed: ' . $e->getMessage());
    }
}
```

## Category Hierarchy Issues

### Issue: Circular Reference Error

**Symptoms:**
- Cannot save category with parent
- "Circular reference detected" error
- Category tree displays incorrectly

**Diagnosis:**
```php
// Check for circular references
php artisan tinker
>>> $category = Category::find(1);
>>> $parent = Category::find(2);
>>> echo "Would create circular reference: " . ($category->wouldCreateCircularReference($parent->id) ? 'Yes' : 'No');
```

**Solutions:**

1. **Fix Circular Reference:**
```php
// In CategoryService
public function validateHierarchy(int $categoryId, ?int $parentId): bool
{
    if (!$parentId) return true;
    
    // Check if parent is descendant of category
    $descendants = $this->getDescendantIds($categoryId);
    return !in_array($parentId, $descendants);
}

private function getDescendantIds(int $categoryId): array
{
    $descendants = [];
    $children = Category::where('parent_id', $categoryId)->get();
    
    foreach ($children as $child) {
        $descendants[] = $child->id;
        $descendants = array_merge($descendants, $this->getDescendantIds($child->id));
    }
    
    return $descendants;
}
```

2. **Fix Broken Hierarchy:**
```sql
-- Find categories with invalid parent references
SELECT c1.id, c1.name, c1.parent_id, c2.name as parent_name
FROM categories c1
LEFT JOIN categories c2 ON c1.parent_id = c2.id
WHERE c1.parent_id IS NOT NULL AND c2.id IS NULL;

-- Fix by setting parent_id to NULL
UPDATE categories SET parent_id = NULL WHERE parent_id NOT IN (SELECT id FROM categories);
```

### Issue: Category Tree Not Loading

**Symptoms:**
- Empty category tree in interface
- Loading spinner never stops
- JavaScript errors in console

**Diagnosis:**
```bash
# Check API endpoint
curl -H "Authorization: Bearer {token}" http://localhost/api/categories/hierarchy

# Check browser console for errors
# Check network tab for failed requests

# Test in tinker
php artisan tinker
>>> Category::with('children')->whereNull('parent_id')->get();
```

**Solutions:**

1. **Fix API Response:**
```php
// In CategoryController
public function getHierarchy(): JsonResponse
{
    try {
        $categories = Category::with(['children' => function ($query) {
            $query->orderBy('sort_order');
        }])
        ->whereNull('parent_id')
        ->orderBy('sort_order')
        ->get();

        return response()->json(['data' => $categories]);
    } catch (\Exception $e) {
        \Log::error('Category hierarchy error: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to load categories'], 500);
    }
}
```

2. **Fix Frontend Component:**
```vue
<!-- In CategoryTree.vue -->
<template>
  <div v-if="loading" class="text-center py-4">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
    <p class="mt-2 text-gray-600">{{ $t('categories.loading') }}</p>
  </div>
  
  <div v-else-if="error" class="text-center py-4 text-red-600">
    <p>{{ error }}</p>
    <button @click="loadCategories" class="mt-2 btn-primary">
      {{ $t('common.retry') }}
    </button>
  </div>
  
  <div v-else-if="categories.length === 0" class="text-center py-8 text-gray-500">
    <p>{{ $t('categories.no_categories') }}</p>
  </div>
  
  <div v-else>
    <!-- Category tree content -->
  </div>
</template>

<script setup>
const loadCategories = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    const response = await api.get('/api/categories/hierarchy');
    categories.value = response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load categories';
    console.error('Category loading error:', err);
  } finally {
    loading.value = false;
  }
};
</script>
```

## Gold Purity Issues

### Issue: Gold Purity Not Displaying Correctly

**Symptoms:**
- Purity shows as numbers instead of formatted text
- Persian numerals not displaying
- Conversion errors between karat and per-mille

**Diagnosis:**
```php
// Test gold purity service
php artisan tinker
>>> $service = app(\App\Services\GoldPurityService::class);
>>> echo $service->formatPurityDisplay(18, 'en');
>>> echo $service->formatPurityDisplay(18, 'fa');
>>> echo $service->convertKaratToPermille(18);
```

**Solutions:**

1. **Fix Service Implementation:**
```php
// In GoldPurityService
public function formatPurityDisplay(float $purity, string $locale): string
{
    $permille = $this->convertKaratToPermille($purity);
    
    if ($locale === 'fa') {
        $persianKarat = $this->convertToPersianNumerals($purity);
        return "{$persianKarat} عیار";
    }
    
    return "{$purity}K ({$permille}‰)";
}

private function convertToPersianNumerals(float $number): string
{
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'];
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٫'];
    
    return str_replace($english, $persian, (string)$number);
}
```

2. **Fix Frontend Display:**
```vue
<!-- In GoldPuritySelector.vue -->
<template>
  <div class="gold-purity-display">
    <span v-if="currentLocale === 'fa'" dir="rtl">
      {{ formatPersianPurity(purity) }}
    </span>
    <span v-else>
      {{ formatEnglishPurity(purity) }}
    </span>
  </div>
</template>

<script setup>
const formatPersianPurity = (purity) => {
  if (!purity) return '';
  const persianNumber = purity.toString().replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
  return `${persianNumber} عیار`;
};

const formatEnglishPurity = (purity) => {
  if (!purity) return '';
  const permille = Math.round(purity * 41.666667);
  return `${purity}K (${permille}‰)`;
};
</script>
```

### Issue: Gold Purity Validation Errors

**Symptoms:**
- Valid purity values rejected
- Decimal precision issues
- Range validation failures

**Solutions:**

1. **Fix Validation Rules:**
```php
// In CategoryRequest
public function rules(): array
{
    return [
        'default_gold_purity' => [
            'nullable',
            'numeric',
            'min:1',
            'max:24',
            'regex:/^\d+(\.\d{1,3})?$/', // Allow up to 3 decimal places
        ],
    ];
}
```

2. **Fix Database Precision:**
```php
// In migration
$table->decimal('default_gold_purity', 5, 3)->nullable(); // 5 digits total, 3 after decimal
```

## Localization Issues

### Issue: Persian Text Not Displaying

**Symptoms:**
- Persian text shows as question marks
- RTL layout not working
- Font issues with Persian characters

**Diagnosis:**
```bash
# Check database charset
mysql -e "SHOW VARIABLES LIKE 'character_set%';"

# Check browser console for font errors
# Verify UTF-8 encoding in responses
curl -H "Accept-Language: fa" http://localhost/api/categories
```

**Solutions:**

1. **Fix Database Encoding:**
```sql
-- Check table charset
SHOW CREATE TABLE categories;

-- Fix if needed
ALTER TABLE categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Fix Frontend Fonts:**
```css
/* Add to main CSS */
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');

body {
  font-family: 'Vazirmatn', 'Inter', sans-serif;
}

/* RTL support */
[dir="rtl"] {
  text-align: right;
  direction: rtl;
}

[dir="rtl"] .flex {
  flex-direction: row-reverse;
}
```

3. **Fix API Headers:**
```php
// In middleware or controller
response()->header('Content-Type', 'application/json; charset=utf-8');
```

### Issue: Language Switching Not Working

**Symptoms:**
- Interface doesn't change language
- Mixed language content
- Translations not loading

**Solutions:**

1. **Fix Language Detection:**
```php
// In LocalizationMiddleware
public function handle($request, Closure $next)
{
    $locale = $request->header('Accept-Language', 'en');
    $locale = substr($locale, 0, 2); // Get first 2 characters
    
    if (!in_array($locale, ['en', 'fa'])) {
        $locale = 'en';
    }
    
    app()->setLocale($locale);
    
    return $next($request);
}
```

2. **Fix Frontend Language Store:**
```typescript
// In stores/locale.ts
export const useLocaleStore = defineStore('locale', {
  state: () => ({
    currentLocale: 'en',
    availableLocales: ['en', 'fa']
  }),
  
  actions: {
    setLocale(locale: string) {
      if (this.availableLocales.includes(locale)) {
        this.currentLocale = locale;
        document.documentElement.lang = locale;
        document.documentElement.dir = locale === 'fa' ? 'rtl' : 'ltr';
        
        // Update API headers
        api.defaults.headers.common['Accept-Language'] = locale;
      }
    }
  }
});
```

## Performance Issues

### Issue: Slow Category Loading

**Symptoms:**
- Long loading times for category tree
- Timeout errors
- High database query count

**Diagnosis:**
```bash
# Enable query logging
php artisan tinker
>>> DB::enableQueryLog();
>>> Category::with('children')->get();
>>> dd(DB::getQueryLog());

# Check database performance
EXPLAIN SELECT * FROM categories WHERE parent_id IS NULL;
```

**Solutions:**

1. **Add Database Indexes:**
```sql
-- Add indexes for performance
CREATE INDEX idx_categories_parent_id ON categories(parent_id);
CREATE INDEX idx_categories_sort_order ON categories(sort_order);
CREATE INDEX idx_categories_is_active ON categories(is_active);
```

2. **Optimize Queries:**
```php
// Use eager loading to prevent N+1 queries
$categories = Category::with(['children' => function ($query) {
    $query->select('id', 'name', 'name_persian', 'parent_id', 'sort_order')
          ->orderBy('sort_order');
}])
->select('id', 'name', 'name_persian', 'parent_id', 'sort_order', 'image_path')
->whereNull('parent_id')
->orderBy('sort_order')
->get();
```

3. **Implement Caching:**
```php
// Cache category hierarchy
public function getCachedHierarchy(): Collection
{
    return Cache::remember('category_hierarchy', 3600, function () {
        return Category::with('children')->whereNull('parent_id')->get();
    });
}

// Clear cache when categories change
public function clearCategoryCache(): void
{
    Cache::forget('category_hierarchy');
    Cache::tags(['categories'])->flush();
}
```

## Docker-Specific Issues

### Issue: File Permissions in Docker

**Symptoms:**
- Cannot upload images in Docker
- Permission denied errors
- Files created with wrong ownership

**Solutions:**

1. **Fix Dockerfile Permissions:**
```dockerfile
# In Dockerfile
RUN chown -R www-data:www-data /var/www/html/storage
RUN chmod -R 775 /var/www/html/storage

# Create directories with proper permissions
RUN mkdir -p /var/www/html/storage/app/public/categories && \
    chown -R www-data:www-data /var/www/html/storage/app/public && \
    chmod -R 775 /var/www/html/storage/app/public
```

2. **Fix Docker Compose Volumes:**
```yaml
# In docker-compose.yml
services:
  app:
    volumes:
      - ./storage/app/public:/var/www/html/storage/app/public:rw
    user: "1000:1000"  # Match host user
```

3. **Fix Runtime Permissions:**
```bash
# Run after container starts
docker exec -it app_container chown -R www-data:www-data /var/www/html/storage
docker exec -it app_container chmod -R 775 /var/www/html/storage
```

### Issue: Images Not Served by Nginx

**Symptoms:**
- 404 errors for image URLs in Docker
- Nginx cannot find image files
- Volume mounting issues

**Solutions:**

1. **Fix Nginx Configuration:**
```nginx
# In nginx container config
server {
    location ~* ^/storage/.*\.(jpg|jpeg|png|gif|webp|svg)$ {
        root /var/www/html/storage/app/public;
        try_files $uri =404;
        
        # Add CORS headers if needed
        add_header Access-Control-Allow-Origin *;
    }
}
```

2. **Fix Volume Mounts:**
```yaml
# Ensure both containers have access
services:
  app:
    volumes:
      - storage_data:/var/www/html/storage/app/public
      
  nginx:
    volumes:
      - storage_data:/var/www/html/storage/app/public:ro

volumes:
  storage_data:
```

## Emergency Recovery Procedures

### Complete System Reset (Development)

```bash
# Stop all services
docker-compose down

# Clear all data
rm -rf storage/app/public/categories/*
php artisan migrate:fresh

# Recreate storage structure
mkdir -p storage/app/public/categories
chmod -R 775 storage/app/public
php artisan storage:link

# Restart services
docker-compose up -d

# Run seeders
php artisan db:seed --class=CategorySeeder
```

### Backup Recovery

```bash
# Restore database
mysql -u root -p jewelry_db < backups/categories_backup.sql

# Restore images
tar -xzf backups/category_images_backup.tar.gz -C storage/app/public/categories/

# Fix permissions
chown -R www-data:www-data storage/app/public/categories
chmod -R 775 storage/app/public/categories

# Clear caches
php artisan cache:clear
php artisan config:clear
```

## Getting Help

### Log Files to Check

1. **Laravel Logs:** `storage/logs/laravel.log`
2. **Nginx Logs:** `/var/log/nginx/error.log`
3. **PHP-FPM Logs:** `/var/log/php8.2-fpm.log`
4. **MySQL Logs:** `/var/log/mysql/error.log`

### Debug Commands

```bash
# System information
php artisan about

# Check configuration
php artisan config:show

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check routes
php artisan route:list | grep category

# Check permissions
namei -l storage/app/public/categories/
```

### Support Contacts

- **System Administrator:** For server and Docker issues
- **Database Administrator:** For database performance and corruption issues
- **Development Team:** For application bugs and feature issues
- **Documentation:** Refer to API documentation and user guides

Remember to always backup your data before attempting any fixes, especially in production environments.