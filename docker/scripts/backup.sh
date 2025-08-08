#!/bin/bash

# Automated Backup Script for Jewelry Platform
# This script creates backups of MySQL database, Redis data, and application files

set -e

# Configuration
BACKUP_DIR="/var/www/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=${BACKUP_RETENTION_DAYS:-30}

# Database configuration
DB_HOST=${DB_HOST:-mysql}
DB_DATABASE=${DB_DATABASE:-jewelry_platform}
DB_USERNAME=${DB_USERNAME:-jewelry_user}
DB_PASSWORD=${DB_PASSWORD:-jewelry_password}

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo "Starting backup process at $(date)"

# 1. MySQL Database Backup
echo "Backing up MySQL database..."
mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/mysql_backup_$TIMESTAMP.sql"
gzip "$BACKUP_DIR/mysql_backup_$TIMESTAMP.sql"
echo "MySQL backup completed: mysql_backup_$TIMESTAMP.sql.gz"

# 2. Redis Data Backup
echo "Backing up Redis data..."
if [ -f /data/dump.rdb ]; then
    cp /data/dump.rdb "$BACKUP_DIR/redis_backup_$TIMESTAMP.rdb"
    gzip "$BACKUP_DIR/redis_backup_$TIMESTAMP.rdb"
    echo "Redis backup completed: redis_backup_$TIMESTAMP.rdb.gz"
else
    echo "Redis dump file not found, skipping Redis backup"
fi

# 3. Application Files Backup
echo "Backing up application files..."
tar -czf "$BACKUP_DIR/app_files_backup_$TIMESTAMP.tar.gz" \
    -C /var/www/storage \
    app/public/categories \
    app/invoices \
    logs \
    2>/dev/null || echo "Some application files may not exist yet"

echo "Application files backup completed: app_files_backup_$TIMESTAMP.tar.gz"

# 4. Create backup manifest
echo "Creating backup manifest..."
cat > "$BACKUP_DIR/backup_manifest_$TIMESTAMP.json" << EOF
{
    "timestamp": "$TIMESTAMP",
    "date": "$(date -Iseconds)",
    "files": {
        "mysql": "mysql_backup_$TIMESTAMP.sql.gz",
        "redis": "redis_backup_$TIMESTAMP.rdb.gz",
        "app_files": "app_files_backup_$TIMESTAMP.tar.gz"
    },
    "database": {
        "host": "$DB_HOST",
        "database": "$DB_DATABASE",
        "username": "$DB_USERNAME"
    },
    "retention_days": $RETENTION_DAYS
}
EOF

# 5. Clean up old backups
echo "Cleaning up old backups (older than $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "*.rdb.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "backup_manifest_*.json" -mtime +$RETENTION_DAYS -delete

# 6. Display backup summary
echo "Backup process completed at $(date)"
echo "Backup files created:"
ls -la "$BACKUP_DIR"/*_$TIMESTAMP.*

# 7. Verify backup integrity
echo "Verifying backup integrity..."
if [ -f "$BACKUP_DIR/mysql_backup_$TIMESTAMP.sql.gz" ]; then
    if gzip -t "$BACKUP_DIR/mysql_backup_$TIMESTAMP.sql.gz"; then
        echo "MySQL backup integrity: OK"
    else
        echo "MySQL backup integrity: FAILED"
        exit 1
    fi
fi

if [ -f "$BACKUP_DIR/redis_backup_$TIMESTAMP.rdb.gz" ]; then
    if gzip -t "$BACKUP_DIR/redis_backup_$TIMESTAMP.rdb.gz"; then
        echo "Redis backup integrity: OK"
    else
        echo "Redis backup integrity: FAILED"
        exit 1
    fi
fi

if [ -f "$BACKUP_DIR/app_files_backup_$TIMESTAMP.tar.gz" ]; then
    if tar -tzf "$BACKUP_DIR/app_files_backup_$TIMESTAMP.tar.gz" >/dev/null 2>&1; then
        echo "Application files backup integrity: OK"
    else
        echo "Application files backup integrity: FAILED"
        exit 1
    fi
fi

echo "All backups verified successfully!"