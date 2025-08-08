#!/bin/bash

# Volume Initialization Script
# This script initializes all Docker volumes with proper permissions and structure

set -e

echo "=== Initializing Docker Volumes ==="

# Volume directories
VOLUME_BASE="./docker/volumes"
VOLUMES=(
    "mysql_data"
    "mysql_logs"
    "redis_data"
    "redis_logs"
    "app_storage"
    "app_logs"
    "app_cache"
    "category_images"
    "category_thumbnails"
    "category_backups"
    "invoice_files"
    "backups"
    "nginx_logs"
)

# Create volume directories if they don't exist
echo "Creating volume directories..."
for volume in "${VOLUMES[@]}"; do
    mkdir -p "$VOLUME_BASE/$volume"
    echo "  ✓ Created: $VOLUME_BASE/$volume"
done

# Set proper permissions (if on Linux/Unix)
if [[ "$OSTYPE" == "linux-gnu"* ]] || [[ "$OSTYPE" == "darwin"* ]]; then
    echo "Setting proper permissions..."
    
    # MySQL volumes need mysql user permissions (999:999)
    sudo chown -R 999:999 "$VOLUME_BASE/mysql_data" "$VOLUME_BASE/mysql_logs" 2>/dev/null || echo "  Note: Could not set MySQL permissions (may need sudo)"
    
    # Redis volumes need redis user permissions (999:999)
    sudo chown -R 999:999 "$VOLUME_BASE/redis_data" "$VOLUME_BASE/redis_logs" 2>/dev/null || echo "  Note: Could not set Redis permissions (may need sudo)"
    
    # Application volumes need www-data permissions (33:33)
    sudo chown -R 33:33 "$VOLUME_BASE/app_storage" "$VOLUME_BASE/app_logs" "$VOLUME_BASE/app_cache" 2>/dev/null || echo "  Note: Could not set app permissions (may need sudo)"
    
    # File storage volumes
    sudo chown -R 33:33 "$VOLUME_BASE/category_images" "$VOLUME_BASE/category_thumbnails" "$VOLUME_BASE/category_backups" "$VOLUME_BASE/invoice_files" 2>/dev/null || echo "  Note: Could not set file storage permissions (may need sudo)"
    
    # Backup volume
    sudo chown -R 33:33 "$VOLUME_BASE/backups" 2>/dev/null || echo "  Note: Could not set backup permissions (may need sudo)"
    
    # Nginx logs
    sudo chown -R 101:101 "$VOLUME_BASE/nginx_logs" 2>/dev/null || echo "  Note: Could not set nginx permissions (may need sudo)"
    
    echo "  ✓ Permissions set (where possible)"
else
    echo "  Note: Skipping permission setup on Windows"
fi

# Create subdirectories for application storage
echo "Creating application storage structure..."
mkdir -p "$VOLUME_BASE/app_storage/app/public/categories"
mkdir -p "$VOLUME_BASE/app_storage/app/invoices"
mkdir -p "$VOLUME_BASE/app_storage/logs"
mkdir -p "$VOLUME_BASE/app_storage/framework/cache"
mkdir -p "$VOLUME_BASE/app_storage/framework/sessions"
mkdir -p "$VOLUME_BASE/app_storage/framework/views"
echo "  ✓ Application storage structure created"

# Create log files
echo "Creating initial log files..."
touch "$VOLUME_BASE/mysql_logs/error.log"
touch "$VOLUME_BASE/mysql_logs/slow.log"
touch "$VOLUME_BASE/redis_logs/redis.log"
touch "$VOLUME_BASE/app_logs/laravel.log"
touch "$VOLUME_BASE/nginx_logs/access.log"
touch "$VOLUME_BASE/nginx_logs/error.log"
echo "  ✓ Initial log files created"

# Create backup directory structure
echo "Creating backup directory structure..."
mkdir -p "$VOLUME_BASE/backups/mysql"
mkdir -p "$VOLUME_BASE/backups/redis"
mkdir -p "$VOLUME_BASE/backups/files"
mkdir -p "$VOLUME_BASE/backups/manifests"
echo "  ✓ Backup directory structure created"

# Create .gitkeep files to preserve empty directories in git
echo "Creating .gitkeep files..."
for volume in "${VOLUMES[@]}"; do
    if [ ! -f "$VOLUME_BASE/$volume/.gitkeep" ]; then
        touch "$VOLUME_BASE/$volume/.gitkeep"
    fi
done
echo "  ✓ .gitkeep files created"

echo
echo "=== Volume Initialization Complete ==="
echo "All Docker volumes have been initialized successfully!"
echo
echo "Volume locations:"
for volume in "${VOLUMES[@]}"; do
    echo "  $volume: $VOLUME_BASE/$volume"
done
echo
echo "You can now run 'docker-compose up' to start the services with persistent storage."