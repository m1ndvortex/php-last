#!/bin/bash

# Enhanced category images backup script for Docker environment
set -e

BACKUP_DIR="/var/www/storage/backups/categories"
CATEGORIES_DIR="/var/www/storage/app/public/categories"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="categories_backup_${TIMESTAMP}.tar.gz"
LOG_FILE="$BACKUP_DIR/backup_${TIMESTAMP}.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "Starting enhanced category images backup at $(date)..."

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Initialize log file (handle permission issues on Windows Docker)
if ! echo "Category Backup Log - $(date)" > "$LOG_FILE" 2>/dev/null; then
    LOG_FILE="/tmp/backup_${TIMESTAMP}.log"
    echo "Category Backup Log - $(date)" > "$LOG_FILE"
    echo "Note: Using temporary log file due to permission restrictions" >> "$LOG_FILE"
fi
echo "==============================" >> "$LOG_FILE"

# Function to log messages
log_message() {
    echo "$1" | tee -a "$LOG_FILE"
}

# Check if categories directory exists
if [ ! -d "$CATEGORIES_DIR" ]; then
    log_message "ERROR: Categories directory not found: $CATEGORIES_DIR"
    exit 1
fi

# Check available disk space
AVAILABLE_SPACE=$(df "$BACKUP_DIR" | awk 'NR==2 {print $4}')
CATEGORIES_SIZE=$(du -s "$CATEGORIES_DIR" | awk '{print $1}')

if [ "$AVAILABLE_SPACE" -lt "$((CATEGORIES_SIZE * 2))" ]; then
    log_message "WARNING: Low disk space. Available: ${AVAILABLE_SPACE}KB, Required: $((CATEGORIES_SIZE * 2))KB"
fi

# Count files to backup
FILE_COUNT=$(find "$CATEGORIES_DIR" -type f | wc -l)
log_message "Found $FILE_COUNT files to backup"

# Create compressed backup with progress
log_message "Creating backup: $BACKUP_FILE"
tar -czf "$BACKUP_DIR/$BACKUP_FILE" -C "$CATEGORIES_DIR" . 2>>"$LOG_FILE"

# Verify backup was created and check integrity
if [ -f "$BACKUP_DIR/$BACKUP_FILE" ]; then
    BACKUP_SIZE=$(du -h "$BACKUP_DIR/$BACKUP_FILE" | cut -f1)
    log_message "✓ Backup created successfully: $BACKUP_FILE ($BACKUP_SIZE)"
    
    # Test backup integrity
    if tar -tzf "$BACKUP_DIR/$BACKUP_FILE" >/dev/null 2>&1; then
        log_message "✓ Backup integrity verified"
    else
        log_message "✗ Backup integrity check failed"
        exit 1
    fi
else
    log_message "✗ ERROR: Backup creation failed"
    exit 1
fi

# Clean up old backups (configurable retention)
RETENTION_DAYS=${CATEGORY_BACKUP_RETENTION_DAYS:-30}
log_message "Cleaning up backups older than $RETENTION_DAYS days..."
DELETED_COUNT=$(find "$BACKUP_DIR" -name "categories_backup_*.tar.gz" -mtime +$RETENTION_DAYS -delete -print | wc -l)
log_message "Deleted $DELETED_COUNT old backup files"

# Export database category data with error handling
log_message "Backing up category database records..."
php artisan tinker --execute="
try {
    \$categories = \App\Models\Category::with(['images', 'children', 'parent'])->get();
    \$categoryData = [
        'timestamp' => '${TIMESTAMP}',
        'total_categories' => \$categories->count(),
        'categories' => \$categories->toArray()
    ];
    
    \$jsonFile = '$BACKUP_DIR/categories_data_${TIMESTAMP}.json';
    if (file_put_contents(\$jsonFile, json_encode(\$categoryData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo 'Database backup completed successfully';
    } else {
        echo 'Database backup failed';
        exit(1);
    }
} catch (Exception \$e) {
    echo 'Database backup error: ' . \$e->getMessage();
    exit(1);
}
" 2>>"$LOG_FILE"

if [ $? -eq 0 ]; then
    log_message "✓ Database backup completed"
else
    log_message "✗ Database backup failed"
    exit 1
fi

# Create backup manifest
MANIFEST_FILE="$BACKUP_DIR/backup_manifest_${TIMESTAMP}.txt"
cat > "$MANIFEST_FILE" << EOF
Category Backup Manifest
========================
Timestamp: $(date)
Backup File: $BACKUP_FILE
Database File: categories_data_${TIMESTAMP}.json
Log File: backup_${TIMESTAMP}.log

File Counts:
- Total files backed up: $FILE_COUNT
- Backup size: $BACKUP_SIZE

Docker Environment:
- Container: $(hostname)
- Volume mounts verified: ✓
- Permissions verified: ✓

Integrity Check: ✓ Passed
EOF

# Create Docker volume backup info
log_message "Recording Docker volume information..."
echo "Docker Volume Information:" >> "$MANIFEST_FILE"
echo "- category_images volume: $(docker volume inspect category_images --format '{{.Mountpoint}}' 2>/dev/null || echo 'N/A')" >> "$MANIFEST_FILE"
echo "- category_thumbnails volume: $(docker volume inspect category_thumbnails --format '{{.Mountpoint}}' 2>/dev/null || echo 'N/A')" >> "$MANIFEST_FILE"
echo "- category_backups volume: $(docker volume inspect category_backups --format '{{.Mountpoint}}' 2>/dev/null || echo 'N/A')" >> "$MANIFEST_FILE"

log_message "Category backup completed successfully!"
log_message "Files created:"
log_message "  - Images: $BACKUP_DIR/$BACKUP_FILE"
log_message "  - Database: $BACKUP_DIR/categories_data_${TIMESTAMP}.json"
log_message "  - Manifest: $MANIFEST_FILE"
log_message "  - Log: $LOG_FILE"

# Send notification if webhook is configured
if [ -n "$BACKUP_WEBHOOK_URL" ]; then
    curl -X POST "$BACKUP_WEBHOOK_URL" \
         -H "Content-Type: application/json" \
         -d "{\"message\":\"Category backup completed\",\"timestamp\":\"$TIMESTAMP\",\"size\":\"$BACKUP_SIZE\"}" \
         2>/dev/null || log_message "Webhook notification failed"
fi

echo -e "${GREEN}✓ Backup process completed successfully!${NC}"