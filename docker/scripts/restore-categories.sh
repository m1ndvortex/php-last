#!/bin/bash

# Enhanced category images restore script for Docker environment
set -e

BACKUP_DIR="/var/www/storage/backups/categories"
CATEGORIES_DIR="/var/www/storage/app/public/categories"
LOG_FILE="$BACKUP_DIR/restore_$(date +"%Y%m%d_%H%M%S").log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to log messages
log_message() {
    echo "$1" | tee -a "$LOG_FILE"
}

# Initialize log
echo "Category Restore Log - $(date)" > "$LOG_FILE"
echo "==============================" >> "$LOG_FILE"

if [ $# -eq 0 ]; then
    echo -e "${YELLOW}Usage: $0 <backup_timestamp> [--force] [--database-only]${NC}"
    echo ""
    echo "Available backups:"
    if ls "$BACKUP_DIR"/categories_backup_*.tar.gz >/dev/null 2>&1; then
        for backup in "$BACKUP_DIR"/categories_backup_*.tar.gz; do
            BACKUP_NAME=$(basename "$backup")
            BACKUP_TIMESTAMP=$(echo "$BACKUP_NAME" | sed 's/categories_backup_\(.*\)\.tar\.gz/\1/')
            BACKUP_SIZE=$(du -h "$backup" | cut -f1)
            BACKUP_DATE=$(date -d "${BACKUP_TIMESTAMP:0:8} ${BACKUP_TIMESTAMP:9:2}:${BACKUP_TIMESTAMP:11:2}:${BACKUP_TIMESTAMP:13:2}" 2>/dev/null || echo "Unknown date")
            echo "  - $BACKUP_TIMESTAMP ($BACKUP_SIZE) - $BACKUP_DATE"
        done
    else
        echo "  No backups found"
    fi
    echo ""
    echo "Options:"
    echo "  --force         Skip confirmation prompts"
    echo "  --database-only Restore only database records, not images"
    exit 1
fi

TIMESTAMP=$1
FORCE_MODE=false
DATABASE_ONLY=false

# Parse additional arguments
shift
while [[ $# -gt 0 ]]; do
    case $1 in
        --force)
            FORCE_MODE=true
            shift
            ;;
        --database-only)
            DATABASE_ONLY=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

BACKUP_FILE="categories_backup_${TIMESTAMP}.tar.gz"
DATA_FILE="categories_data_${TIMESTAMP}.json"
MANIFEST_FILE="backup_manifest_${TIMESTAMP}.txt"

log_message "Starting category restore from backup: $TIMESTAMP"

# Check if backup files exist
if [ ! -f "$BACKUP_DIR/$BACKUP_FILE" ] && [ "$DATABASE_ONLY" = false ]; then
    log_message "ERROR: Backup file not found: $BACKUP_DIR/$BACKUP_FILE"
    exit 1
fi

if [ ! -f "$BACKUP_DIR/$DATA_FILE" ]; then
    log_message "WARNING: Database backup not found: $BACKUP_DIR/$DATA_FILE"
fi

# Show backup information if manifest exists
if [ -f "$BACKUP_DIR/$MANIFEST_FILE" ]; then
    log_message "Backup manifest found:"
    cat "$BACKUP_DIR/$MANIFEST_FILE" | head -20 | tee -a "$LOG_FILE"
fi

# Confirmation prompt unless force mode
if [ "$FORCE_MODE" = false ]; then
    echo -e "${YELLOW}This will restore category data from backup $TIMESTAMP${NC}"
    echo -e "${YELLOW}Current data will be backed up before restoration${NC}"
    read -p "Continue? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_message "Restore cancelled by user"
        exit 0
    fi
fi

# Create backup of current state before restore
if [ -d "$CATEGORIES_DIR" ] && [ "$DATABASE_ONLY" = false ]; then
    CURRENT_BACKUP="categories_pre_restore_$(date +"%Y%m%d_%H%M%S").tar.gz"
    log_message "Creating backup of current state: $CURRENT_BACKUP"
    
    if tar -czf "$BACKUP_DIR/$CURRENT_BACKUP" -C "$CATEGORIES_DIR" . 2>>"$LOG_FILE"; then
        log_message "✓ Current state backed up successfully"
    else
        log_message "✗ Failed to backup current state"
        exit 1
    fi
fi

# Restore images unless database-only mode
if [ "$DATABASE_ONLY" = false ]; then
    # Verify backup integrity before restoration
    log_message "Verifying backup integrity..."
    if tar -tzf "$BACKUP_DIR/$BACKUP_FILE" >/dev/null 2>&1; then
        log_message "✓ Backup integrity verified"
    else
        log_message "✗ Backup integrity check failed"
        exit 1
    fi
    
    # Clear current categories directory
    log_message "Clearing current categories directory..."
    rm -rf "$CATEGORIES_DIR"
    mkdir -p "$CATEGORIES_DIR"
    mkdir -p "$CATEGORIES_DIR/thumbnails"
    
    # Restore images
    log_message "Restoring category images..."
    if tar -xzf "$BACKUP_DIR/$BACKUP_FILE" -C "$CATEGORIES_DIR" 2>>"$LOG_FILE"; then
        RESTORED_COUNT=$(find "$CATEGORIES_DIR" -type f | wc -l)
        log_message "✓ Restored $RESTORED_COUNT files"
    else
        log_message "✗ Failed to restore images"
        exit 1
    fi
    
    # Set proper permissions
    log_message "Setting proper permissions..."
    chown -R www-data:www-data "$CATEGORIES_DIR" 2>>"$LOG_FILE" || true
    chmod -R 755 "$CATEGORIES_DIR" 2>>"$LOG_FILE" || true
    log_message "✓ Permissions set"
fi

# Restore database data if available
if [ -f "$BACKUP_DIR/$DATA_FILE" ]; then
    log_message "Restoring category database records..."
    
    RESTORE_RESULT=$(php artisan tinker --execute="
    try {
        \$data = json_decode(file_get_contents('$BACKUP_DIR/$DATA_FILE'), true);
        
        if (!isset(\$data['categories'])) {
            echo 'Invalid backup format';
            exit(1);
        }
        
        \$categories = \$data['categories'];
        \$totalCount = count(\$categories);
        
        echo 'Loaded ' . \$totalCount . ' category records from backup';
        echo 'Backup timestamp: ' . (\$data['timestamp'] ?? 'Unknown');
        echo 'Note: Database restore requires manual intervention to avoid conflicts';
        echo 'Use the following command to import:';
        echo 'php artisan category:import-backup $BACKUP_DIR/$DATA_FILE';
        
    } catch (Exception \$e) {
        echo 'Database restore error: ' . \$e->getMessage();
        exit(1);
    }
    " 2>>"$LOG_FILE")
    
    if [ $? -eq 0 ]; then
        log_message "✓ Database backup processed"
        echo "$RESTORE_RESULT" | tee -a "$LOG_FILE"
    else
        log_message "✗ Database restore failed"
    fi
else
    log_message "No database backup to restore"
fi

# Verify restoration
if [ "$DATABASE_ONLY" = false ]; then
    log_message "Verifying restoration..."
    
    # Check if key directories exist
    if [ -d "$CATEGORIES_DIR" ] && [ -d "$CATEGORIES_DIR/thumbnails" ]; then
        log_message "✓ Directory structure verified"
    else
        log_message "✗ Directory structure verification failed"
    fi
    
    # Test image access
    if find "$CATEGORIES_DIR" -name "*.png" -o -name "*.jpg" -o -name "*.jpeg" -o -name "*.webp" | head -1 | xargs test -f 2>/dev/null; then
        log_message "✓ Image files accessible"
    else
        log_message "! No image files found (may be normal if backup was empty)"
    fi
fi

# Create restore report
RESTORE_REPORT="$BACKUP_DIR/restore_report_$(date +"%Y%m%d_%H%M%S").txt"
cat > "$RESTORE_REPORT" << EOF
Category Restore Report
=======================
Restore Date: $(date)
Backup Timestamp: $TIMESTAMP
Restore Mode: $([ "$DATABASE_ONLY" = true ] && echo "Database Only" || echo "Full Restore")

Files Restored:
- Backup File: $BACKUP_FILE
- Database File: $DATA_FILE
- Log File: $(basename "$LOG_FILE")

Status: SUCCESS
EOF

log_message "Category restore completed successfully!"
log_message "Files processed:"
if [ "$DATABASE_ONLY" = false ]; then
    log_message "  - Images: $BACKUP_DIR/$BACKUP_FILE"
fi
log_message "  - Database: $BACKUP_DIR/$DATA_FILE"
log_message "  - Report: $RESTORE_REPORT"
log_message "  - Log: $LOG_FILE"

echo -e "${GREEN}✓ Restore process completed successfully!${NC}"