#!/bin/bash

# Test script for file permissions in Docker category management system
set -e

echo "=== Testing File Permissions for Category Management ==="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print test results
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2"
        return 1
    fi
}

# Test counters
TESTS_PASSED=0
TESTS_FAILED=0

# Function to run test and count results
run_test() {
    if print_result $1 "$2"; then
        ((TESTS_PASSED++))
    else
        ((TESTS_FAILED++))
    fi
}

# Test 1: Check directory permissions
echo "1. Testing directory permissions..."

DIRECTORIES=(
    "/var/www/storage/app/public/categories:755"
    "/var/www/storage/app/public/categories/thumbnails:755"
    "/var/www/storage/backups/categories:755"
    "/var/www/public/storage:755"
)

for dir_perm in "${DIRECTORIES[@]}"; do
    DIR=$(echo "$dir_perm" | cut -d: -f1)
    EXPECTED_PERM=$(echo "$dir_perm" | cut -d: -f2)
    
    if [ -d "$DIR" ]; then
        ACTUAL_PERM=$(stat -c "%a" "$DIR")
        if [ "$ACTUAL_PERM" = "$EXPECTED_PERM" ] || [ "$ACTUAL_PERM" = "775" ]; then
            run_test 0 "Directory permissions correct: $DIR ($ACTUAL_PERM)"
        else
            run_test 1 "Directory permissions incorrect: $DIR (expected $EXPECTED_PERM, got $ACTUAL_PERM)"
        fi
    else
        run_test 1 "Directory missing: $DIR"
    fi
done

# Test 2: Check directory ownership
echo ""
echo "2. Testing directory ownership..."

for dir_perm in "${DIRECTORIES[@]}"; do
    DIR=$(echo "$dir_perm" | cut -d: -f1)
    
    if [ -d "$DIR" ]; then
        OWNER=$(stat -c "%U:%G" "$DIR")
        if [ "$OWNER" = "www-data:www-data" ] || [ "$OWNER" = "root:root" ]; then
            run_test 0 "Directory ownership correct: $DIR ($OWNER)"
        else
            run_test 1 "Directory ownership incorrect: $DIR ($OWNER)"
        fi
    fi
done

# Test 3: Test file creation permissions
echo ""
echo "3. Testing file creation permissions..."

TEST_FILE="/var/www/storage/app/public/categories/permission_test.txt"
if echo "test content" > "$TEST_FILE" 2>/dev/null; then
    run_test 0 "Can create files in categories directory"
    
    # Check created file permissions
    FILE_PERM=$(stat -c "%a" "$TEST_FILE")
    if [ "$FILE_PERM" = "644" ] || [ "$FILE_PERM" = "664" ]; then
        run_test 0 "Created file has correct permissions ($FILE_PERM)"
    else
        run_test 1 "Created file has incorrect permissions ($FILE_PERM)"
    fi
    
    # Clean up
    rm -f "$TEST_FILE"
else
    run_test 1 "Cannot create files in categories directory"
fi

# Test 4: Test image upload simulation
echo ""
echo "4. Testing image upload simulation..."

php -r "
try {
    // Create a test image
    \$image = imagecreate(100, 100);
    \$bg = imagecolorallocate(\$image, 255, 255, 255);
    \$testPath = '/var/www/storage/app/public/categories/permission_test_image.png';
    
    if (imagepng(\$image, \$testPath)) {
        echo 'image_creation_success';
        
        // Check if file is readable
        if (is_readable(\$testPath)) {
            echo '|image_readable';
        }
        
        // Check file permissions
        \$perms = substr(sprintf('%o', fileperms(\$testPath)), -3);
        echo '|perms:' . \$perms;
        
        // Clean up
        unlink(\$testPath);
        imagedestroy(\$image);
    } else {
        echo 'image_creation_failed';
    }
} catch (Exception \$e) {
    echo 'image_test_error';
}
" 2>/dev/null

IMAGE_RESULT=$(php -r "
try {
    \$image = imagecreate(100, 100);
    \$bg = imagecolorallocate(\$image, 255, 255, 255);
    \$testPath = '/var/www/storage/app/public/categories/permission_test_image.png';
    
    if (imagepng(\$image, \$testPath)) {
        echo 'image_creation_success';
        
        if (is_readable(\$testPath)) {
            echo '|image_readable';
        }
        
        \$perms = substr(sprintf('%o', fileperms(\$testPath)), -3);
        echo '|perms:' . \$perms;
        
        unlink(\$testPath);
        imagedestroy(\$image);
    } else {
        echo 'image_creation_failed';
    }
} catch (Exception \$e) {
    echo 'image_test_error';
}
" 2>/dev/null)

if echo "$IMAGE_RESULT" | grep -q "image_creation_success"; then
    run_test 0 "Image creation successful"
    
    if echo "$IMAGE_RESULT" | grep -q "image_readable"; then
        run_test 0 "Created image is readable"
    else
        run_test 1 "Created image is not readable"
    fi
    
    IMAGE_PERMS=$(echo "$IMAGE_RESULT" | grep -o "perms:[0-9]*" | cut -d: -f2)
    if [ "$IMAGE_PERMS" = "644" ] || [ "$IMAGE_PERMS" = "664" ]; then
        run_test 0 "Image file permissions correct ($IMAGE_PERMS)"
    else
        run_test 1 "Image file permissions incorrect ($IMAGE_PERMS)"
    fi
else
    run_test 1 "Image creation failed"
fi

# Test 5: Test thumbnail directory permissions
echo ""
echo "5. Testing thumbnail directory permissions..."

THUMB_TEST_FILE="/var/www/storage/app/public/categories/thumbnails/thumb_test.png"
php -r "
try {
    \$image = imagecreate(50, 50);
    \$bg = imagecolorallocate(\$image, 255, 0, 0);
    
    if (imagepng(\$image, '/var/www/storage/app/public/categories/thumbnails/thumb_test.png')) {
        echo 'thumbnail_creation_success';
        unlink('/var/www/storage/app/public/categories/thumbnails/thumb_test.png');
    } else {
        echo 'thumbnail_creation_failed';
    }
    imagedestroy(\$image);
} catch (Exception \$e) {
    echo 'thumbnail_test_error';
}
" >/dev/null 2>&1

THUMB_RESULT=$(php -r "
try {
    \$image = imagecreate(50, 50);
    \$bg = imagecolorallocate(\$image, 255, 0, 0);
    
    if (imagepng(\$image, '/var/www/storage/app/public/categories/thumbnails/thumb_test.png')) {
        echo 'thumbnail_creation_success';
        unlink('/var/www/storage/app/public/categories/thumbnails/thumb_test.png');
    } else {
        echo 'thumbnail_creation_failed';
    }
    imagedestroy(\$image);
} catch (Exception \$e) {
    echo 'thumbnail_test_error';
}
" 2>/dev/null)

if echo "$THUMB_RESULT" | grep -q "thumbnail_creation_success"; then
    run_test 0 "Thumbnail creation successful"
else
    run_test 1 "Thumbnail creation failed"
fi

# Test 6: Test backup directory permissions
echo ""
echo "6. Testing backup directory permissions..."

BACKUP_TEST_FILE="/var/www/storage/backups/categories/backup_permission_test.txt"
if echo "backup test" > "$BACKUP_TEST_FILE" 2>/dev/null; then
    run_test 0 "Can create files in backup directory"
    rm -f "$BACKUP_TEST_FILE"
else
    run_test 1 "Cannot create files in backup directory"
fi

# Test 7: Test web server access
echo ""
echo "7. Testing web server access to images..."

# Create a test image for web access
php -r "
\$image = imagecreate(100, 100);
\$bg = imagecolorallocate(\$image, 0, 255, 0);
imagepng(\$image, '/var/www/storage/app/public/categories/web_access_test.png');
imagedestroy(\$image);
"

# Check if nginx can access the file
if [ -f "/var/www/storage/app/public/categories/web_access_test.png" ]; then
    # Test if the file is accessible via the web path
    WEB_ACCESS_PERM=$(stat -c "%a" "/var/www/storage/app/public/categories/web_access_test.png")
    if [ "$WEB_ACCESS_PERM" = "644" ] || [ "$WEB_ACCESS_PERM" = "664" ]; then
        run_test 0 "Web server can access image files (permissions: $WEB_ACCESS_PERM)"
    else
        run_test 1 "Web server may not access image files (permissions: $WEB_ACCESS_PERM)"
    fi
    
    # Clean up
    rm -f "/var/www/storage/app/public/categories/web_access_test.png"
else
    run_test 1 "Failed to create test image for web access"
fi

# Test 8: Test storage link accessibility
echo ""
echo "8. Testing storage link accessibility..."

if [ -L "/var/www/public/storage" ]; then
    LINK_TARGET=$(readlink "/var/www/public/storage")
    if [ "$LINK_TARGET" = "/var/www/storage/app/public" ]; then
        run_test 0 "Storage link points to correct target"
        
        # Test if we can access categories through the link
        if [ -d "/var/www/public/storage/categories" ]; then
            run_test 0 "Categories accessible through storage link"
        else
            run_test 1 "Categories not accessible through storage link"
        fi
    else
        run_test 1 "Storage link points to wrong target: $LINK_TARGET"
    fi
else
    run_test 1 "Storage symbolic link missing"
fi

# Test 9: Test Docker volume permissions
echo ""
echo "9. Testing Docker volume mount permissions..."

# Check if we're running in Docker and volumes are properly mounted
if [ -f "/.dockerenv" ]; then
    run_test 0 "Running inside Docker container"
    
    # Test volume mounts
    VOLUME_MOUNTS=$(mount | grep -E "(categories|thumbnails|backups)" | wc -l)
    if [ "$VOLUME_MOUNTS" -gt 0 ]; then
        run_test 0 "Docker volumes are mounted ($VOLUME_MOUNTS mounts found)"
    else
        run_test 1 "No Docker volume mounts found for categories"
    fi
else
    echo -e "${YELLOW}! Not running in Docker container${NC}"
fi

# Test 10: Test cross-container file access
echo ""
echo "10. Testing cross-container file access simulation..."

# Create a test file and check if it would be accessible by nginx
TEST_CROSS_FILE="/var/www/storage/app/public/categories/cross_container_test.png"
php -r "
\$image = imagecreate(100, 100);
\$bg = imagecolorallocate(\$image, 255, 255, 0);
imagepng(\$image, '/var/www/storage/app/public/categories/cross_container_test.png');
imagedestroy(\$image);
"

if [ -f "$TEST_CROSS_FILE" ]; then
    # Check if the file has proper permissions for cross-container access
    FILE_OWNER=$(stat -c "%U" "$TEST_CROSS_FILE")
    FILE_PERMS=$(stat -c "%a" "$TEST_CROSS_FILE")
    
    if [ "$FILE_PERMS" = "644" ] || [ "$FILE_PERMS" = "664" ]; then
        run_test 0 "File permissions suitable for cross-container access ($FILE_PERMS)"
    else
        run_test 1 "File permissions may prevent cross-container access ($FILE_PERMS)"
    fi
    
    # Clean up
    rm -f "$TEST_CROSS_FILE"
else
    run_test 1 "Failed to create cross-container test file"
fi

# Summary
echo ""
echo "=== File Permissions Test Summary ==="
echo -e "Tests passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Tests failed: ${RED}$TESTS_FAILED${NC}"
echo "Total tests: $((TESTS_PASSED + TESTS_FAILED))"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All file permission tests passed!${NC}"
    echo "Category management file permissions are properly configured."
    exit 0
else
    echo -e "${RED}✗ Some file permission tests failed.${NC}"
    echo "Please review and fix the file permission issues."
    exit 1
fi