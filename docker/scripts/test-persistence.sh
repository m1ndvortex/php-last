#!/bin/bash

# Data Persistence Test Script
# This script tests that data persists across container restarts

set -e

echo "=== Docker Data Persistence Test ==="
echo "This script will test data persistence across container restarts"
echo

# Test configuration
TEST_DB_TABLE="persistence_test"
TEST_REDIS_KEY="persistence_test_key"
TEST_FILE_PATH="/var/www/storage/app/persistence_test.txt"
TEST_DATA="Test data created at $(date)"

echo "1. Testing MySQL data persistence..."

# Create test data in MySQL
docker-compose exec mysql mysql -u jewelry_user -pjewelry_password jewelry_platform -e "
CREATE TABLE IF NOT EXISTS $TEST_DB_TABLE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_data VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO $TEST_DB_TABLE (test_data) VALUES ('$TEST_DATA');
"

echo "   Test data inserted into MySQL"

# Get the inserted data ID
TEST_ID=$(docker-compose exec mysql mysql -u jewelry_user -pjewelry_password jewelry_platform -se "SELECT id FROM $TEST_DB_TABLE ORDER BY id DESC LIMIT 1;")
echo "   Test record ID: $TEST_ID"

echo "2. Testing Redis data persistence..."

# Create test data in Redis
docker-compose exec redis redis-cli SET "$TEST_REDIS_KEY" "$TEST_DATA"
echo "   Test data stored in Redis"

echo "3. Testing file storage persistence..."

# Create test file
docker-compose exec app bash -c "echo '$TEST_DATA' > $TEST_FILE_PATH"
echo "   Test file created"

echo "4. Restarting containers to test persistence..."

# Restart containers
docker-compose restart mysql redis app

echo "   Waiting for containers to be ready..."
sleep 30

echo "5. Verifying data persistence after restart..."

# Check MySQL data
echo "   Checking MySQL data..."
MYSQL_DATA=$(docker-compose exec mysql mysql -u jewelry_user -pjewelry_password jewelry_platform -se "SELECT test_data FROM $TEST_DB_TABLE WHERE id = $TEST_ID;")
if [ "$MYSQL_DATA" = "$TEST_DATA" ]; then
    echo "   ✓ MySQL data persisted successfully"
else
    echo "   ✗ MySQL data persistence FAILED"
    echo "   Expected: $TEST_DATA"
    echo "   Got: $MYSQL_DATA"
    exit 1
fi

# Check Redis data
echo "   Checking Redis data..."
REDIS_DATA=$(docker-compose exec redis redis-cli GET "$TEST_REDIS_KEY")
if [ "$REDIS_DATA" = "$TEST_DATA" ]; then
    echo "   ✓ Redis data persisted successfully"
else
    echo "   ✗ Redis data persistence FAILED"
    echo "   Expected: $TEST_DATA"
    echo "   Got: $REDIS_DATA"
    exit 1
fi

# Check file data
echo "   Checking file storage..."
FILE_DATA=$(docker-compose exec app cat "$TEST_FILE_PATH")
if [ "$FILE_DATA" = "$TEST_DATA" ]; then
    echo "   ✓ File storage persisted successfully"
else
    echo "   ✗ File storage persistence FAILED"
    echo "   Expected: $TEST_DATA"
    echo "   Got: $FILE_DATA"
    exit 1
fi

echo "6. Cleaning up test data..."

# Clean up test data
docker-compose exec mysql mysql -u jewelry_user -pjewelry_password jewelry_platform -e "DROP TABLE IF EXISTS $TEST_DB_TABLE;"
docker-compose exec redis redis-cli DEL "$TEST_REDIS_KEY"
docker-compose exec app rm -f "$TEST_FILE_PATH"

echo "   Test data cleaned up"

echo
echo "=== DATA PERSISTENCE TEST PASSED ==="
echo "All data persisted successfully across container restarts!"
echo "✓ MySQL data persistence: WORKING"
echo "✓ Redis data persistence: WORKING"
echo "✓ File storage persistence: WORKING"
echo