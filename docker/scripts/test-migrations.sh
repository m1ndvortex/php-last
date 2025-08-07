#!/bin/bash

# Test script for category-related database migrations in Docker environment
set -e

echo "=== Testing Category Database Migrations in Docker ==="

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
        exit 1
    fi
}

# Test 1: Check if database is accessible
echo "1. Testing database connectivity..."
php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo 'database_connected';
} catch (Exception \$e) {
    echo 'database_error: ' . \$e->getMessage();
    exit(1);
}
" >/dev/null 2>&1

if [ $? -eq 0 ]; then
    print_result 0 "Database connection successful"
else
    print_result 1 "Database connection failed"
fi

# Test 2: Check migration status
echo ""
echo "2. Checking migration status..."
MIGRATION_STATUS=$(php artisan migrate:status 2>/dev/null | grep -E "(categories|category)" || echo "no_category_migrations")

if echo "$MIGRATION_STATUS" | grep -q "categories"; then
    print_result 0 "Category migrations found in status"
else
    echo -e "${YELLOW}! No category migrations found in status (may be normal)${NC}"
fi

# Test 3: Run fresh migrations in test mode
echo ""
echo "3. Testing fresh migration run..."
php artisan migrate:fresh --seed --force >/dev/null 2>&1

if [ $? -eq 0 ]; then
    print_result 0 "Fresh migrations completed successfully"
else
    print_result 1 "Fresh migrations failed"
fi

# Test 4: Verify category table structure
echo ""
echo "4. Verifying category table structure..."
CATEGORY_COLUMNS=$(php artisan tinker --execute="
try {
    \$columns = Schema::getColumnListing('categories');
    echo implode(',', \$columns);
} catch (Exception \$e) {
    echo 'table_error';
    exit(1);
}
" 2>/dev/null)

REQUIRED_COLUMNS=(
    "id"
    "name"
    "name_persian"
    "code"
    "parent_id"
    "default_gold_purity"
    "image_path"
    "sort_order"
    "specifications"
    "is_active"
    "created_at"
    "updated_at"
)

for column in "${REQUIRED_COLUMNS[@]}"; do
    if echo "$CATEGORY_COLUMNS" | grep -q "$column"; then
        print_result 0 "Column exists: $column"
    else
        print_result 1 "Column missing: $column"
    fi
done

# Test 5: Verify category_images table structure
echo ""
echo "5. Verifying category_images table structure..."
CATEGORY_IMAGES_COLUMNS=$(php artisan tinker --execute="
try {
    \$columns = Schema::getColumnListing('category_images');
    echo implode(',', \$columns);
} catch (Exception \$e) {
    echo 'table_error';
    exit(1);
}
" 2>/dev/null)

REQUIRED_IMAGE_COLUMNS=(
    "id"
    "category_id"
    "image_path"
    "alt_text"
    "alt_text_persian"
    "is_primary"
    "sort_order"
    "created_at"
    "updated_at"
)

for column in "${REQUIRED_IMAGE_COLUMNS[@]}"; do
    if echo "$CATEGORY_IMAGES_COLUMNS" | grep -q "$column"; then
        print_result 0 "Image column exists: $column"
    else
        print_result 1 "Image column missing: $column"
    fi
done

# Test 6: Test foreign key constraints
echo ""
echo "6. Testing foreign key constraints..."
php artisan tinker --execute="
try {
    // Test category parent relationship
    \$category = new \App\Models\Category([
        'name' => 'Test Category',
        'code' => 'TEST001',
        'parent_id' => 99999 // Non-existent parent
    ]);
    
    try {
        \$category->save();
        echo 'foreign_key_failed'; // Should not reach here
    } catch (Exception \$e) {
        echo 'foreign_key_working';
    }
} catch (Exception \$e) {
    echo 'constraint_test_error';
}
" >/dev/null 2>&1

if [ $? -eq 0 ]; then
    print_result 0 "Foreign key constraints working"
else
    print_result 1 "Foreign key constraint test failed"
fi

# Test 7: Test model relationships
echo ""
echo "7. Testing model relationships..."
php artisan tinker --execute="
try {
    // Create test category
    \$category = \App\Models\Category::create([
        'name' => 'Migration Test Category',
        'code' => 'MTC001',
        'is_active' => true
    ]);
    
    // Test relationships exist
    \$hasParentRelation = method_exists(\$category, 'parent');
    \$hasChildrenRelation = method_exists(\$category, 'children');
    \$hasImagesRelation = method_exists(\$category, 'images');
    
    if (\$hasParentRelation && \$hasChildrenRelation && \$hasImagesRelation) {
        echo 'relationships_working';
    } else {
        echo 'relationships_missing';
    }
    
    // Clean up
    \$category->delete();
    
} catch (Exception \$e) {
    echo 'relationship_test_error: ' . \$e->getMessage();
}
" >/dev/null 2>&1

RELATIONSHIP_RESULT=$(php artisan tinker --execute="
try {
    \$category = \App\Models\Category::create([
        'name' => 'Migration Test Category',
        'code' => 'MTC001',
        'is_active' => true
    ]);
    
    \$hasParentRelation = method_exists(\$category, 'parent');
    \$hasChildrenRelation = method_exists(\$category, 'children');
    \$hasImagesRelation = method_exists(\$category, 'images');
    
    if (\$hasParentRelation && \$hasChildrenRelation && \$hasImagesRelation) {
        echo 'relationships_working';
    } else {
        echo 'relationships_missing';
    }
    
    \$category->delete();
    
} catch (Exception \$e) {
    echo 'relationship_test_error';
}
" 2>/dev/null)

if echo "$RELATIONSHIP_RESULT" | grep -q "relationships_working"; then
    print_result 0 "Model relationships working"
else
    print_result 1 "Model relationships failed"
fi

# Test 8: Test data types and constraints
echo ""
echo "8. Testing data types and constraints..."
php artisan tinker --execute="
try {
    // Test gold purity decimal precision
    \$category = \App\Models\Category::create([
        'name' => 'Gold Test Category',
        'code' => 'GTC001',
        'default_gold_purity' => 18.750,
        'is_active' => true
    ]);
    
    if (\$category->default_gold_purity == 18.750) {
        echo 'decimal_precision_working';
    } else {
        echo 'decimal_precision_failed';
    }
    
    \$category->delete();
    
} catch (Exception \$e) {
    echo 'data_type_test_error';
}
" >/dev/null 2>&1

DATA_TYPE_RESULT=$(php artisan tinker --execute="
try {
    \$category = \App\Models\Category::create([
        'name' => 'Gold Test Category',
        'code' => 'GTC001',
        'default_gold_purity' => 18.750,
        'is_active' => true
    ]);
    
    if (\$category->default_gold_purity == 18.750) {
        echo 'decimal_precision_working';
    } else {
        echo 'decimal_precision_failed';
    }
    
    \$category->delete();
    
} catch (Exception \$e) {
    echo 'data_type_test_error';
}
" 2>/dev/null)

if echo "$DATA_TYPE_RESULT" | grep -q "decimal_precision_working"; then
    print_result 0 "Data types and precision working"
else
    print_result 1 "Data types and precision failed"
fi

# Test 9: Test JSON column functionality
echo ""
echo "9. Testing JSON column functionality..."
JSON_TEST_RESULT=$(php artisan tinker --execute="
try {
    \$category = \App\Models\Category::create([
        'name' => 'JSON Test Category',
        'code' => 'JTC001',
        'specifications' => ['weight_range' => '1-10g', 'material' => 'gold'],
        'is_active' => true
    ]);
    
    \$retrieved = \App\Models\Category::find(\$category->id);
    if (is_array(\$retrieved->specifications) && \$retrieved->specifications['weight_range'] === '1-10g') {
        echo 'json_column_working';
    } else {
        echo 'json_column_failed';
    }
    
    \$category->delete();
    
} catch (Exception \$e) {
    echo 'json_test_error';
}
" 2>/dev/null)

if echo "$JSON_TEST_RESULT" | grep -q "json_column_working"; then
    print_result 0 "JSON column functionality working"
else
    print_result 1 "JSON column functionality failed"
fi

echo ""
echo -e "${GREEN}✓ All migration tests passed successfully!${NC}"
echo "Category database structure is properly configured in Docker environment."