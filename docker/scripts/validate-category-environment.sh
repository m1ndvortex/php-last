#!/bin/bash

# Comprehensive validation script for category management Docker environment
set -e

echo "=== Category Management Docker Environment Validation ==="
echo "Starting validation at $(date)"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counters
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print test results
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}✗${NC} $2"
        ((TESTS_FAILED++))
    fi
}

# Test 1: Docker volume mounts (check if directories are mounted)
echo "1. Testing Docker volume mounts..."
VOLUME_PATHS=(
    "/var/www/storage/app/public/categories"
    "/var/www/storage/app/public/categories/thumbnails"
    "/var/www/storage/backups/categories"
)

for path in "${VOLUME_PATHS[@]}"; do
    if [ -d "$path" ]; then
        print_result 0 "Volume mount exists: $path"
    else
        print_result 1 "Volume mount missing: $path"
    fi
done

# Test 2: Directory structure inside containers
echo ""
echo "2. Testing directory structure..."
REQUIRED_DIRS=(
    "/var/www/storage/app/public/categories"
    "/var/www/storage/app/public/categories/thumbnails"
    "/var/www/storage/backups/categories"
)

for dir in "${REQUIRED_DIRS[@]}"; do
    if docker exec jewelry_app test -d "$dir" 2>/dev/null; then
        print_result 0 "Directory exists: $dir"
        
        # Check permissions
        PERMS=$(docker exec jewelry_app stat -c "%a" "$dir" 2>/dev/null || echo "unknown")
        if [ "$PERMS" = "755" ] || [ "$PERMS" = "775" ]; then
            print_result 0 "Permissions correct for $dir: $PERMS"
        else
            print_result 1 "Permissions incorrect for $dir: $PERMS"
        fi
    else
        print_result 1 "Directory missing: $dir"
    fi
done

# Test 3: Storage symbolic link
echo ""
echo "3. Testing storage symbolic link..."
if docker exec jewelry_app test -L "/var/www/public/storage" 2>/dev/null; then
    print_result 0 "Storage symbolic link exists"
    
    TARGET=$(docker exec jewelry_app readlink "/var/www/public/storage" 2>/dev/null || echo "unknown")
    if [ "$TARGET" = "/var/www/storage/app/public" ]; then
        print_result 0 "Storage link points to correct target: $TARGET"
    else
        print_result 1 "Storage link points to wrong target: $TARGET"
    fi
else
    print_result 1 "Storage symbolic link missing"
fi

# Test 4: PHP GD extension
echo ""
echo "4. Testing PHP GD extension..."
if docker exec jewelry_app php -m | grep -q "gd" 2>/dev/null; then
    print_result 0 "GD extension loaded"
    
    # Test GD capabilities
    GD_INFO=$(docker exec jewelry_app php -r "
    if (extension_loaded('gd')) {
        \$info = gd_info();
        echo 'JPEG:' . (\$info['JPEG Support'] ? 'yes' : 'no') . '|';
        echo 'PNG:' . (\$info['PNG Support'] ? 'yes' : 'no') . '|';
        echo 'WebP:' . (isset(\$info['WebP Support']) && \$info['WebP Support'] ? 'yes' : 'no');
    }
    " 2>/dev/null)
    
    if echo "$GD_INFO" | grep -q "JPEG:yes"; then
        print_result 0 "JPEG support enabled"
    else
        print_result 1 "JPEG support disabled"
    fi
    
    if echo "$GD_INFO" | grep -q "PNG:yes"; then
        print_result 0 "PNG support enabled"
    else
        print_result 1 "PNG support disabled"
    fi
    
    if echo "$GD_INFO" | grep -q "WebP:yes"; then
        print_result 0 "WebP support enabled"
    else
        print_result 1 "WebP support disabled"
    fi
else
    print_result 1 "GD extension not loaded"
fi

# Test 5: Database connectivity
echo ""
echo "5. Testing database connectivity..."
if docker exec jewelry_app php artisan tinker --execute="echo 'db_test';" >/dev/null 2>&1; then
    print_result 0 "Database connection successful"
    
    # Test category table exists
    if docker exec jewelry_app php artisan tinker --execute="
    try {
        \$count = \App\Models\Category::count();
        echo 'categories_table_exists';
    } catch (Exception \$e) {
        echo 'categories_table_missing';
    }
    " 2>/dev/null | grep -q "categories_table_exists"; then
        print_result 0 "Categories table exists"
    else
        print_result 1 "Categories table missing"
    fi
else
    print_result 1 "Database connection failed"
fi

# Test 6: Category services
echo ""
echo "6. Testing category services..."
SERVICES=(
    "CategoryService"
    "CategoryImageService" 
    "GoldPurityService"
)

for service in "${SERVICES[@]}"; do
    if docker exec jewelry_app php artisan tinker --execute="
    try {
        app(\App\Services\\${service}::class);
        echo 'service_loaded';
    } catch (Exception \$e) {
        echo 'service_failed';
    }
    " 2>/dev/null | grep -q "service_loaded"; then
        print_result 0 "$service loaded successfully"
    else
        print_result 1 "$service failed to load"
    fi
done

# Test 7: Image processing test
echo ""
echo "7. Testing image processing..."
if docker exec jewelry_app php -r "
try {
    \$image = imagecreate(100, 100);
    \$bg = imagecolorallocate(\$image, 255, 255, 255);
    \$testPath = '/var/www/storage/app/public/categories/validation_test.png';
    \$result = imagepng(\$image, \$testPath);
    imagedestroy(\$image);
    
    if (\$result && file_exists(\$testPath)) {
        echo 'image_processing_success';
        unlink(\$testPath);
    } else {
        echo 'image_processing_failed';
    }
} catch (Exception \$e) {
    echo 'image_processing_error';
}
" 2>/dev/null | grep -q "image_processing_success"; then
    print_result 0 "Image processing test passed"
else
    print_result 1 "Image processing test failed"
fi

# Test 8: Nginx configuration
echo ""
echo "8. Testing Nginx configuration..."
if docker exec jewelry_nginx nginx -t >/dev/null 2>&1; then
    print_result 0 "Nginx configuration valid"
else
    print_result 1 "Nginx configuration invalid"
fi

# Test 9: Category image serving
echo ""
echo "9. Testing category image serving..."
# Create a test image first
docker exec jewelry_app php -r "
\$image = imagecreate(50, 50);
\$bg = imagecolorallocate(\$image, 255, 0, 0);
imagepng(\$image, '/var/www/storage/app/public/categories/nginx_test.png');
imagedestroy(\$image);
"

# Test if nginx can serve the image
if docker exec jewelry_nginx test -f "/var/www/storage/app/public/categories/nginx_test.png" 2>/dev/null; then
    print_result 0 "Nginx can access category images"
    
    # Clean up test image
    docker exec jewelry_app rm -f "/var/www/storage/app/public/categories/nginx_test.png"
else
    print_result 1 "Nginx cannot access category images"
fi

# Test 10: Backup scripts
echo ""
echo "10. Testing backup scripts..."
BACKUP_SCRIPTS=(
    "/var/www/docker/scripts/backup-categories.sh"
    "/var/www/docker/scripts/restore-categories.sh"
    "/var/www/docker/scripts/init-categories.sh"
)

for script in "${BACKUP_SCRIPTS[@]}"; do
    if docker exec jewelry_app test -f "$script" 2>/dev/null; then
        print_result 0 "Script exists: $(basename $script)"
        
        # Test script syntax
        if docker exec jewelry_app bash -n "$script" 2>/dev/null; then
            print_result 0 "Script syntax valid: $(basename $script)"
        else
            print_result 1 "Script syntax invalid: $(basename $script)"
        fi
    else
        print_result 1 "Script missing: $(basename $script)"
    fi
done

# Test 11: Environment variables
echo ""
echo "11. Testing environment variables..."
ENV_VARS=(
    "CATEGORY_IMAGE_MAX_SIZE"
    "CATEGORY_IMAGE_QUALITY"
    "CATEGORY_THUMBNAIL_SIZE"
)

for var in "${ENV_VARS[@]}"; do
    VALUE=$(docker exec jewelry_app printenv "$var" 2>/dev/null || echo "")
    if [ -n "$VALUE" ]; then
        print_result 0 "Environment variable set: $var=$VALUE"
    else
        print_result 1 "Environment variable missing: $var"
    fi
done

# Summary
echo ""
echo "=== Validation Summary ==="
echo -e "Tests passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Tests failed: ${RED}$TESTS_FAILED${NC}"
echo "Total tests: $((TESTS_PASSED + TESTS_FAILED))"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed! Category management Docker environment is properly configured.${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed. Please review the configuration.${NC}"
    exit 1
fi