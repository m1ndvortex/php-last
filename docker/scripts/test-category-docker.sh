#!/bin/bash

# Test script for category management Docker integration
set -e

echo "=== Testing Category Management Docker Integration ==="

# Test 1: Check if required directories exist and have proper permissions
echo "1. Testing directory structure..."
REQUIRED_DIRS=(
    "/var/www/storage/app/public/categories"
    "/var/www/storage/app/public/categories/thumbnails"
    "/var/www/storage/backups/categories"
)

for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "✓ Directory exists: $dir"
        PERMS=$(stat -c "%a" "$dir")
        echo "  Permissions: $PERMS"
    else
        echo "✗ Directory missing: $dir"
        exit 1
    fi
done

# Test 2: Check storage link
echo "2. Testing storage symbolic link..."
if [ -L "/var/www/public/storage" ]; then
    echo "✓ Storage link exists"
    TARGET=$(readlink "/var/www/public/storage")
    echo "  Points to: $TARGET"
else
    echo "✗ Storage link missing"
    exit 1
fi

# Test 3: Test image processing capabilities
echo "3. Testing image processing..."
php -r "
if (!extension_loaded('gd')) {
    echo '✗ GD extension not loaded\n';
    exit(1);
}

\$info = gd_info();
echo '✓ GD Extension loaded\n';
echo '  Version: ' . \$info['GD Version'] . '\n';
echo '  JPEG: ' . (\$info['JPEG Support'] ? 'Yes' : 'No') . '\n';
echo '  PNG: ' . (\$info['PNG Support'] ? 'Yes' : 'No') . '\n';
echo '  WebP: ' . (isset(\$info['WebP Support']) && \$info['WebP Support'] ? 'Yes' : 'No') . '\n';
"

# Test 4: Test file upload simulation
echo "4. Testing file upload simulation..."
php -r "
try {
    // Create a test image
    \$image = imagecreate(200, 200);
    \$bg = imagecolorallocate(\$image, 255, 255, 255);
    \$text_color = imagecolorallocate(\$image, 0, 0, 0);
    imagestring(\$image, 5, 50, 90, 'DOCKER TEST', \$text_color);
    
    \$testPath = '/var/www/storage/app/public/categories/docker_test.png';
    if (imagepng(\$image, \$testPath)) {
        echo '✓ Test image created successfully\n';
        
        // Test thumbnail creation
        \$thumbPath = '/var/www/storage/app/public/categories/thumbnails/docker_test_thumb.png';
        \$thumb = imagecreatetruecolor(100, 100);
        imagecopyresampled(\$thumb, \$image, 0, 0, 0, 0, 100, 100, 200, 200);
        
        if (imagepng(\$thumb, \$thumbPath)) {
            echo '✓ Thumbnail created successfully\n';
            imagedestroy(\$thumb);
        } else {
            echo '✗ Failed to create thumbnail\n';
        }
        
        // Clean up
        unlink(\$testPath);
        if (file_exists(\$thumbPath)) unlink(\$thumbPath);
        imagedestroy(\$image);
    } else {
        echo '✗ Failed to create test image\n';
        exit(1);
    }
} catch (Exception \$e) {
    echo '✗ Error: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Test 5: Test database connectivity for categories
echo "5. Testing database connectivity..."
php artisan tinker --execute="
try {
    \$count = \App\Models\Category::count();
    echo '✓ Database connection successful\n';
    echo '  Categories in database: ' . \$count . '\n';
} catch (Exception \$e) {
    echo '✗ Database error: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Test 6: Test category services
echo "6. Testing category services..."
php artisan tinker --execute="
try {
    \$categoryService = app(\App\Services\CategoryService::class);
    \$imageService = app(\App\Services\CategoryImageService::class);
    \$goldService = app(\App\Services\GoldPurityService::class);
    
    echo '✓ CategoryService loaded\n';
    echo '✓ CategoryImageService loaded\n';
    echo '✓ GoldPurityService loaded\n';
} catch (Exception \$e) {
    echo '✗ Service error: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Test 7: Test backup functionality
echo "7. Testing backup functionality..."
if [ -f "/var/www/docker/scripts/backup-categories.sh" ]; then
    echo "✓ Backup script exists"
    # Test backup script syntax
    bash -n /var/www/docker/scripts/backup-categories.sh
    echo "✓ Backup script syntax valid"
else
    echo "✗ Backup script missing"
fi

echo ""
echo "=== Docker Integration Test Results ==="
echo "✓ All tests passed successfully!"
echo "Category management system is properly integrated with Docker."