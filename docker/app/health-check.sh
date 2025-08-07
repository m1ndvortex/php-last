#!/bin/bash

# Health check script for category image processing in Docker
set -e

echo "Checking category image processing health..."

# Check if storage directories exist and are writable
STORAGE_DIRS=(
    "/var/www/storage/app/public/categories"
    "/var/www/storage/app/public/categories/thumbnails"
    "/var/www/storage/backups/categories"
)

for dir in "${STORAGE_DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        echo "Creating directory: $dir"
        mkdir -p "$dir"
    fi
    
    if [ ! -w "$dir" ]; then
        echo "Setting permissions for: $dir"
        chmod 755 "$dir"
        chown www-data:www-data "$dir"
    fi
done

# Check if GD extension is loaded
php -m | grep -q "gd" || {
    echo "ERROR: GD extension not loaded"
    exit 1
}

# Check if required image processing tools are available
command -v jpegoptim >/dev/null 2>&1 || {
    echo "WARNING: jpegoptim not found"
}

command -v optipng >/dev/null 2>&1 || {
    echo "WARNING: optipng not found"
}

command -v webp >/dev/null 2>&1 || {
    echo "WARNING: webp not found"
}

# Test basic image processing capability
php -r "
if (!extension_loaded('gd')) {
    echo 'ERROR: GD extension not available';
    exit(1);
}

\$info = gd_info();
if (!isset(\$info['JPEG Support']) || !\$info['JPEG Support']) {
    echo 'ERROR: JPEG support not available';
    exit(1);
}

if (!isset(\$info['PNG Support']) || !\$info['PNG Support']) {
    echo 'ERROR: PNG support not available';
    exit(1);
}

if (!isset(\$info['WebP Support']) || !\$info['WebP Support']) {
    echo 'WARNING: WebP support not available';
}

echo 'Image processing capabilities verified';
"

# Check database connectivity for category operations
php artisan tinker --execute="
try {
    \DB::connection()->getPdo();
    echo 'Database connection: OK';
} catch (Exception \$e) {
    echo 'Database connection: FAILED - ' . \$e->getMessage();
    exit(1);
}
"

echo "Category image processing health check completed successfully!"