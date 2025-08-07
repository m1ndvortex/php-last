#!/bin/bash

# Category system initialization script for Docker environment
set -e

echo "Initializing category management system..."

# Create required directories
DIRS=(
    "/var/www/storage/app/public/categories"
    "/var/www/storage/app/public/categories/thumbnails"
    "/var/www/storage/backups/categories"
    "/var/www/storage/logs"
)

for dir in "${DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        echo "Creating directory: $dir"
        mkdir -p "$dir"
    fi
    
    # Set proper permissions (ignore errors on Windows Docker)
    chown -R www-data:www-data "$dir" 2>/dev/null || true
    chmod -R 755 "$dir" 2>/dev/null || true
done

# Create symbolic link for public storage if it doesn't exist
if [ ! -L "/var/www/public/storage" ]; then
    echo "Creating storage symbolic link..."
    php artisan storage:link
fi

# Run category-related migrations
echo "Running category migrations..."
php artisan migrate --path=database/migrations --force

# Seed categories if requested
if [ "$1" = "--seed" ]; then
    echo "Seeding category data..."
    php artisan db:seed --class=CategorySeeder
fi

# Test image processing capabilities
echo "Testing image processing..."
php -r "
if (!extension_loaded('gd')) {
    echo 'ERROR: GD extension not loaded\n';
    exit(1);
}

\$info = gd_info();
echo 'GD Version: ' . \$info['GD Version'] . '\n';
echo 'JPEG Support: ' . (\$info['JPEG Support'] ? 'Yes' : 'No') . '\n';
echo 'PNG Support: ' . (\$info['PNG Support'] ? 'Yes' : 'No') . '\n';
echo 'WebP Support: ' . (isset(\$info['WebP Support']) && \$info['WebP Support'] ? 'Yes' : 'No') . '\n';
"

# Test category image service
echo "Testing category image service..."
php artisan tinker --execute="
try {
    \$service = app(\App\Services\CategoryImageService::class);
    echo 'CategoryImageService loaded successfully';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
    exit(1);
}
"

# Create a test category image to verify everything works
echo "Creating test category image..."
php artisan tinker --execute="
try {
    // Create a simple test image
    \$image = imagecreate(100, 100);
    \$bg = imagecolorallocate(\$image, 255, 255, 255);
    \$text_color = imagecolorallocate(\$image, 0, 0, 0);
    imagestring(\$image, 5, 30, 40, 'TEST', \$text_color);
    
    \$testPath = '/var/www/storage/app/public/categories/test.png';
    imagepng(\$image, \$testPath);
    imagedestroy(\$image);
    
    if (file_exists(\$testPath)) {
        echo 'Test image created successfully';
        unlink(\$testPath); // Clean up
    } else {
        echo 'ERROR: Failed to create test image';
        exit(1);
    }
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
    exit(1);
}
"

echo "Category management system initialization completed successfully!"