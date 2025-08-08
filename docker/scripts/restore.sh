#!/bin/bash

# Restore Script for Jewelry Platform
# This script restores backups of MySQL database, Redis data, and application files

set -e

# Configuration
BACKUP_DIR="/var/www/backups"
RESTORE_TIMESTAMP="$1"

# Database configuration
DB_HOST=${DB_HOST:-mysql}
DB_DATABASE=${DB_DATABASE:-jewelry_platform}
DB_USERNAME=${DB_USERNAME:-jewelry_user}
DB_PASSWORD=${DB_PASSWORD:-jewelry_password}

if [ -z "$RESTORE_TIMESTAMP" ]; then
    echo "Usage: $0 <timestamp>"
    echo "Available backups:"
    ls -la "$BACKUP_DIR"/backup_manifest_*.json | sed 's/.*backup_manifest_\(.*\)\.json/\1/'
    exit 1
fi

echo "Starting restore process for backup: $RESTORE_TIMESTAMP"

# Check if backup files exist
MYSQL_BACKUP="$BACKUP_DIR/mysql_backup_$RESTORE_TIMESTAMP.sql.gz"
REDIS_BACKUP="$BACKUP_DIR/redis_backup_$RESTORE_TIMESTAMP.rdb.gz"
APP_FILES_BACKUP="$BACKUP_DIR/app_files_backup_$RESTORE_TIMESTAMP.tar.gz"
MANIFEST="$BACKUP_DIR/backup_manifest_$RESTORE_TIMESTAMP.json"

if [ ! -f "$MANIFEST" ]; then
    echo "Error: Backup manifest not found: $MANIFEST"
    exit 1
fi

echo "Found backup manifest: $MANIFEST"
cat "$MANIFEST"

# 1. Restore MySQL Database
if [ -f "$MYSQL_BACKUP" ]; then
    echo "Restoring MySQL database..."
    gunzip -c "$MYSQL_BACKUP" | mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
    echo "MySQL database restored successfully"
else
    echo "Warning: MySQL backup file not found: $MYSQL_BACKUP"
fi

# 2. Restore Redis Data
if [ -f "$REDIS_BACKUP" ]; then
    echo "Restoring Redis data..."
    # Stop Redis temporarily for restore
    redis-cli FLUSHALL
    gunzip -c "$REDIS_BACKUP" > /data/dump.rdb
    # Redis will automatically load the dump file on next restart
    echo "Redis data restored successfully (will be loaded on next Redis restart)"
else
    echo "Warning: Redis backup file not found: $REDIS_BACKUP"
fi

# 3. Restore Application Files
if [ -f "$APP_FILES_BACKUP" ]; then
    echo "Restoring application files..."
    tar -xzf "$APP_FILES_BACKUP" -C /var/www/storage/
    echo "Application files restored successfully"
else
    echo "Warning: Application files backup not found: $APP_FILES_BACKUP"
fi

echo "Restore process completed successfully!"
echo "Note: You may need to restart the application containers for all changes to take effect."