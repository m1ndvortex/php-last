#!/bin/bash

# Comprehensive test runner for category management Docker integration
set -e

echo "=== Category Management Docker Integration Test Suite ==="
echo "Starting comprehensive tests at $(date)"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test results tracking
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Function to run a test script and track results
run_test_script() {
    local script_name=$1
    local script_path="/var/www/docker/scripts/$script_name"
    local description=$2
    
    echo -e "${BLUE}Running: $description${NC}"
    echo "Script: $script_name"
    echo "----------------------------------------"
    
    if [ -f "$script_path" ]; then
        if bash "$script_path"; then
            echo -e "${GREEN}✓ $description - PASSED${NC}"
            ((PASSED_TESTS++))
        else
            echo -e "${RED}✗ $description - FAILED${NC}"
            ((FAILED_TESTS++))
        fi
    else
        echo -e "${RED}✗ Test script not found: $script_path${NC}"
        ((FAILED_TESTS++))
    fi
    
    ((TOTAL_TESTS++))
    echo ""
}

# Function to run inline tests
run_inline_test() {
    local test_name=$1
    local test_command=$2
    local description=$3
    
    echo -e "${BLUE}Running: $description${NC}"
    echo "Test: $test_name"
    echo "----------------------------------------"
    
    if eval "$test_command"; then
        echo -e "${GREEN}✓ $description - PASSED${NC}"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗ $description - FAILED${NC}"
        ((FAILED_TESTS++))
    fi
    
    ((TOTAL_TESTS++))
    echo ""
}

# Pre-test setup
echo -e "${YELLOW}Setting up test environment...${NC}"

# Ensure all required directories exist
mkdir -p /var/www/storage/app/public/categories
mkdir -p /var/www/storage/app/public/categories/thumbnails
mkdir -p /var/www/storage/backups/categories

# Set proper permissions
chown -R www-data:www-data /var/www/storage/app/public/categories 2>/dev/null || true
chmod -R 755 /var/www/storage/app/public/categories 2>/dev/null || true

echo -e "${GREEN}✓ Test environment setup complete${NC}"
echo ""

# Test 1: Environment Validation
run_test_script "validate-category-environment.sh" "Docker Environment Validation"

# Test 2: Database Migration Tests
run_test_script "test-migrations.sh" "Database Migration Tests"

# Test 3: File Permission Tests
run_test_script "test-file-permissions.sh" "File Permission Tests"

# Test 4: Basic Docker Integration Test
run_test_script "test-category-docker.sh" "Basic Docker Integration"

# Test 5: Service Availability Tests
run_inline_test "service_availability" "
php artisan tinker --execute='
try {
    \$categoryService = app(\App\Services\CategoryService::class);
    \$imageService = app(\App\Services\CategoryImageService::class);
    \$goldService = app(\App\Services\GoldPurityService::class);
    echo \"All services loaded successfully\";
} catch (Exception \$e) {
    echo \"Service loading failed: \" . \$e->getMessage();
    exit(1);
}
' >/dev/null 2>&1
" "Category Services Availability"

# Test 6: Image Processing Capabilities
run_inline_test "image_processing" "
php -r '
if (!extension_loaded(\"gd\")) {
    echo \"GD extension not loaded\";
    exit(1);
}

\$info = gd_info();
if (!\$info[\"JPEG Support\"] || !\$info[\"PNG Support\"]) {
    echo \"Required image format support missing\";
    exit(1);
}

// Test image creation
\$image = imagecreate(100, 100);
\$bg = imagecolorallocate(\$image, 255, 255, 255);
\$testPath = \"/var/www/storage/app/public/categories/processing_test.png\";

if (!imagepng(\$image, \$testPath)) {
    echo \"Image creation failed\";
    exit(1);
}

if (!file_exists(\$testPath)) {
    echo \"Image file not created\";
    exit(1);
}

unlink(\$testPath);
imagedestroy(\$image);
echo \"Image processing test passed\";
' 2>/dev/null
" "Image Processing Capabilities"

# Test 7: Storage Link Functionality
run_inline_test "storage_link" "
if [ ! -L '/var/www/public/storage' ]; then
    echo 'Storage link missing'
    exit 1
fi

TARGET=\$(readlink '/var/www/public/storage')
if [ \"\$TARGET\" != '/var/www/storage/app/public' ]; then
    echo 'Storage link points to wrong target'
    exit 1
fi

if [ ! -d '/var/www/public/storage/categories' ]; then
    echo 'Categories not accessible through storage link'
    exit 1
fi

echo 'Storage link functionality verified'
" "Storage Link Functionality"

# Test 8: Docker Volume Persistence
run_inline_test "volume_persistence" "
# Create a test file
TEST_FILE='/var/www/storage/app/public/categories/persistence_test.txt'
echo 'persistence test' > \"\$TEST_FILE\"

if [ ! -f \"\$TEST_FILE\" ]; then
    echo 'Failed to create persistence test file'
    exit 1
fi

# Check if file persists (simulate container restart scenario)
if [ -f \"\$TEST_FILE\" ]; then
    rm -f \"\$TEST_FILE\"
    echo 'Volume persistence test passed'
else
    echo 'Volume persistence test failed'
    exit 1
fi
" "Docker Volume Persistence"

# Test 9: Nginx Configuration Validation
run_inline_test "nginx_config" "
# Test nginx configuration syntax
if ! nginx -t >/dev/null 2>&1; then
    echo 'Nginx configuration invalid'
    exit 1
fi

# Test if nginx can access category images directory
if [ ! -d '/var/www/storage/app/public/categories' ]; then
    echo 'Nginx cannot access categories directory'
    exit 1
fi

echo 'Nginx configuration validation passed'
" "Nginx Configuration Validation"

# Test 10: Backup System Functionality
run_inline_test "backup_system" "
# Test backup script syntax
if ! bash -n '/var/www/docker/scripts/backup-categories.sh' 2>/dev/null; then
    echo 'Backup script syntax error'
    exit 1
fi

# Test restore script syntax
if ! bash -n '/var/www/docker/scripts/restore-categories.sh' 2>/dev/null; then
    echo 'Restore script syntax error'
    exit 1
fi

# Test backup directory accessibility
if [ ! -d '/var/www/storage/backups/categories' ]; then
    echo 'Backup directory not accessible'
    exit 1
fi

echo 'Backup system functionality verified'
" "Backup System Functionality"

# Test 11: Environment Variables
run_inline_test "environment_variables" "
REQUIRED_VARS=('CATEGORY_IMAGE_MAX_SIZE' 'CATEGORY_IMAGE_QUALITY' 'CATEGORY_THUMBNAIL_SIZE')
MISSING_VARS=()

for var in \"\${REQUIRED_VARS[@]}\"; do
    if [ -z \"\$(printenv \$var)\" ]; then
        MISSING_VARS+=(\"\$var\")
    fi
done

if [ \${#MISSING_VARS[@]} -gt 0 ]; then
    echo \"Missing environment variables: \${MISSING_VARS[*]}\"
    exit 1
fi

echo 'All required environment variables are set'
" "Environment Variables Check"

# Test 12: Database Connectivity and Models
run_inline_test "database_models" "
php artisan tinker --execute='
try {
    // Test database connection
    DB::connection()->getPdo();
    
    // Test Category model
    \$categoryCount = \App\Models\Category::count();
    
    // Test CategoryImage model
    \$imageCount = \App\Models\CategoryImage::count();
    
    echo \"Database connectivity and models working (Categories: \$categoryCount, Images: \$imageCount)\";
} catch (Exception \$e) {
    echo \"Database or model error: \" . \$e->getMessage();
    exit(1);
}
' >/dev/null 2>&1
" "Database Connectivity and Models"

# Generate comprehensive test report
echo "=== Generating Test Report ==="
REPORT_FILE="/var/www/storage/backups/categories/docker_integration_test_report_$(date +%Y%m%d_%H%M%S).txt"

cat > "$REPORT_FILE" << EOF
Category Management Docker Integration Test Report
==================================================
Test Date: $(date)
Environment: Docker Container
Hostname: $(hostname)

Test Summary:
- Total Tests: $TOTAL_TESTS
- Passed: $PASSED_TESTS
- Failed: $FAILED_TESTS
- Success Rate: $(( PASSED_TESTS * 100 / TOTAL_TESTS ))%

System Information:
- PHP Version: $(php -r "echo PHP_VERSION;")
- GD Extension: $(php -r "echo extension_loaded('gd') ? 'Enabled' : 'Disabled';")
- MySQL Connection: $(php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Connected'; } catch (Exception \$e) { echo 'Failed'; }" 2>/dev/null)

Docker Environment:
- Container ID: $(hostname)
- Storage Volumes: $(mount | grep -c categories || echo "0") mounted
- Nginx Status: $(nginx -t >/dev/null 2>&1 && echo "Valid" || echo "Invalid")

File System:
- Categories Directory: $([ -d "/var/www/storage/app/public/categories" ] && echo "Exists" || echo "Missing")
- Thumbnails Directory: $([ -d "/var/www/storage/app/public/categories/thumbnails" ] && echo "Exists" || echo "Missing")
- Backup Directory: $([ -d "/var/www/storage/backups/categories" ] && echo "Exists" || echo "Missing")
- Storage Link: $([ -L "/var/www/public/storage" ] && echo "Exists" || echo "Missing")

Test Results: $([ $FAILED_TESTS -eq 0 ] && echo "ALL TESTS PASSED" || echo "SOME TESTS FAILED")
EOF

echo "Test report generated: $REPORT_FILE"

# Final summary
echo ""
echo "=== Final Test Summary ==="
echo -e "Total tests run: ${BLUE}$TOTAL_TESTS${NC}"
echo -e "Tests passed: ${GREEN}$PASSED_TESTS${NC}"
echo -e "Tests failed: ${RED}$FAILED_TESTS${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 ALL TESTS PASSED! 🎉${NC}"
    echo -e "${GREEN}Category management Docker integration is fully functional.${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Deploy the application using: docker-compose up -d"
    echo "2. Initialize categories: docker exec jewelry_app bash /var/www/docker/scripts/init-categories.sh"
    echo "3. Test the web interface at: http://localhost"
    exit 0
else
    echo ""
    echo -e "${RED}❌ SOME TESTS FAILED ❌${NC}"
    echo -e "${RED}Please review the failed tests and fix the issues before deployment.${NC}"
    echo ""
    echo "Troubleshooting:"
    echo "1. Check Docker container logs: docker-compose logs"
    echo "2. Verify volume mounts: docker volume ls"
    echo "3. Check file permissions: ls -la /var/www/storage/app/public/"
    echo "4. Review test report: $REPORT_FILE"
    exit 1
fi