#!/bin/bash

# Full System Backup Script
# This script creates a complete backup of the jewelry platform including database, files, and configuration

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="/var/backups/jewelry-platform"
FULL_BACKUP_DIR="$BACKUP_DIR/full"
LOG_FILE="$APP_DIR/storage/logs/full-backup.log"
DATE=$(date +%Y%m%d_%H%M%S)

# Functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}" | tee -a "$LOG_FILE"
}

# Create backup directories
create_backup_dirs() {
    mkdir -p "$FULL_BACKUP_DIR"
    mkdir -p "$FULL_BACKUP_DIR/database"
    mkdir -p "$FULL_BACKUP_DIR/application"
    mkdir -p "$FULL_BACKUP_DIR/docker"
    mkdir -p "$FULL_BACKUP_DIR/system"
    mkdir -p "$FULL_BACKUP_DIR/ssl"
    
    log "Backup directories created"
}

# Load environment variables
load_environment() {
    if [[ ! -f "$APP_DIR/.env" ]]; then
        error ".env file not found"
    fi
    
    source "$APP_DIR/.env"
    
    if [[ -z "$DB_DATABASE" || -z "$DB_USERNAME" || -z "$DB_PASSWORD" ]]; then
        error "Database credentials not found in .env file"
    fi
}

# Backup database with structure and data
backup_database() {
    log "Creating complete database backup..."
    
    cd "$APP_DIR"
    
    DB_BACKUP_FILE="$FULL_BACKUP_DIR/database/complete_db_$DATE.sql"
    
    # Create comprehensive database dump
    docker-compose -f docker-compose.prod.yml exec -T mysql mysqldump \
        -u "$DB_USERNAME" \
        -p"$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-table \
        --add-locks \
        --create-options \
        --disable-keys \
        --extended-insert \
        --quick \
        --lock-tables=false \
        --complete-insert \
        --hex-blob \
        --default-character-set=utf8mb4 \
        "$DB_DATABASE" > "$DB_BACKUP_FILE"
    
    if [[ $? -eq 0 ]]; then
        # Compress backup
        gzip "$DB_BACKUP_FILE"
        SIZE=$(du -h "${DB_BACKUP_FILE}.gz" | cut -f1)
        log "Database backup created: ${DB_BACKUP_FILE}.gz ($SIZE)"
    else
        error "Database backup failed"
    fi
    
    # Also backup database schema only
    SCHEMA_BACKUP_FILE="$FULL_BACKUP_DIR/database/schema_only_$DATE.sql"
    docker-compose -f docker-compose.prod.yml exec -T mysql mysqldump \
        -u "$DB_USERNAME" \
        -p"$DB_PASSWORD" \
        --no-data \
        --routines \
        --triggers \
        --events \
        "$DB_DATABASE" > "$SCHEMA_BACKUP_FILE"
    
    gzip "$SCHEMA_BACKUP_FILE"
    log "Database schema backup created: ${SCHEMA_BACKUP_FILE}.gz"
}

# Backup complete application
backup_application() {
    log "Creating complete application backup..."
    
    APP_BACKUP_FILE="$FULL_BACKUP_DIR/application/application_$DATE.tar.gz"
    
    cd "$(dirname "$APP_DIR")"
    
    # Create comprehensive application backup
    tar -czf "$APP_BACKUP_FILE" \
        --exclude='*/node_modules' \
        --exclude='*/vendor' \
        --exclude='*/storage/logs/*.log' \
        --exclude='*/storage/framework/cache/*' \
        --exclude='*/storage/framework/sessions/*' \
        --exclude='*/storage/framework/views/*' \
        --exclude='*/.git' \
        --exclude='*/frontend/dist' \
        --exclude='*/frontend/node_modules' \
        --exclude='*/bootstrap/cache/*' \
        "$(basename "$APP_DIR")"
    
    if [[ $? -eq 0 ]]; then
        SIZE=$(du -h "$APP_BACKUP_FILE" | cut -f1)
        log "Application backup created: $APP_BACKUP_FILE ($SIZE)"
    else
        error "Application backup failed"
    fi
}

# Backup Docker configuration and volumes
backup_docker() {
    log "Creating Docker configuration backup..."
    
    cd "$APP_DIR"
    
    DOCKER_BACKUP_FILE="$FULL_BACKUP_DIR/docker/docker_config_$DATE.tar.gz"
    
    # Backup Docker compose files and configuration
    tar -czf "$DOCKER_BACKUP_FILE" \
        docker-compose.yml \
        docker-compose.prod.yml \
        docker/ \
        .env
    
    if [[ $? -eq 0 ]]; then
        SIZE=$(du -h "$DOCKER_BACKUP_FILE" | cut -f1)
        log "Docker configuration backup created: $DOCKER_BACKUP_FILE ($SIZE)"
    else
        warning "Docker configuration backup failed"
    fi
    
    # Backup Docker volumes data
    log "Creating Docker volumes backup..."
    
    VOLUMES_BACKUP_FILE="$FULL_BACKUP_DIR/docker/docker_volumes_$DATE.tar.gz"
    
    # Stop containers temporarily for consistent backup
    docker-compose -f docker-compose.prod.yml stop
    
    # Backup volume data
    docker run --rm \
        -v jewelry-platform_mysql_data:/mysql_data:ro \
        -v jewelry-platform_redis_data:/redis_data:ro \
        -v jewelry-platform_app_files:/app_files:ro \
        -v "$FULL_BACKUP_DIR/docker:/backup" \
        alpine:latest \
        tar -czf "/backup/docker_volumes_$DATE.tar.gz" \
        -C / mysql_data redis_data app_files
    
    # Restart containers
    docker-compose -f docker-compose.prod.yml start
    
    if [[ -f "$VOLUMES_BACKUP_FILE" ]]; then
        SIZE=$(du -h "$VOLUMES_BACKUP_FILE" | cut -f1)
        log "Docker volumes backup created: $VOLUMES_BACKUP_FILE ($SIZE)"
    else
        warning "Docker volumes backup failed"
    fi
}

# Backup system configuration
backup_system_config() {
    log "Creating system configuration backup..."
    
    SYSTEM_BACKUP_FILE="$FULL_BACKUP_DIR/system/system_config_$DATE.tar.gz"
    
    # Backup important system files
    sudo tar -czf "$SYSTEM_BACKUP_FILE" \
        --ignore-failed-read \
        /etc/nginx/sites-available/ \
        /etc/nginx/sites-enabled/ \
        /etc/nginx/nginx.conf \
        /etc/crontab \
        /var/spool/cron/crontabs/ \
        /etc/logrotate.d/ \
        /etc/fail2ban/ \
        /etc/ufw/ \
        /etc/hosts \
        /etc/hostname \
        /etc/timezone \
        2>/dev/null || true
    
    if [[ -f "$SYSTEM_BACKUP_FILE" ]]; then
        SIZE=$(du -h "$SYSTEM_BACKUP_FILE" | cut -f1)
        log "System configuration backup created: $SYSTEM_BACKUP_FILE ($SIZE)"
    else
        warning "System configuration backup failed"
    fi
}

# Backup SSL certificates
backup_ssl() {
    log "Creating SSL certificates backup..."
    
    SSL_BACKUP_FILE="$FULL_BACKUP_DIR/ssl/ssl_certificates_$DATE.tar.gz"
    
    if [[ -d "/etc/letsencrypt" ]]; then
        sudo tar -czf "$SSL_BACKUP_FILE" /etc/letsencrypt/
        
        if [[ $? -eq 0 ]]; then
            SIZE=$(du -h "$SSL_BACKUP_FILE" | cut -f1)
            log "SSL certificates backup created: $SSL_BACKUP_FILE ($SIZE)"
        else
            warning "SSL certificates backup failed"
        fi
    else
        warning "SSL certificates directory not found"
    fi
}

# Create backup manifest
create_manifest() {
    log "Creating backup manifest..."
    
    MANIFEST_FILE="$FULL_BACKUP_DIR/backup_manifest_$DATE.json"
    
    cat > "$MANIFEST_FILE" << EOF
{
    "backup_info": {
        "type": "full_system_backup",
        "date": "$(date -Iseconds)",
        "hostname": "$(hostname)",
        "backup_id": "$DATE",
        "app_version": "$(cd $APP_DIR && git describe --tags --always 2>/dev/null || echo 'unknown')"
    },
    "components": {
        "database": {
            "complete_backup": "$FULL_BACKUP_DIR/database/complete_db_$DATE.sql.gz",
            "schema_backup": "$FULL_BACKUP_DIR/database/schema_only_$DATE.sql.gz",
            "size": "$(du -h $FULL_BACKUP_DIR/database/complete_db_$DATE.sql.gz 2>/dev/null | cut -f1 || echo 'N/A')"
        },
        "application": {
            "backup_file": "$FULL_BACKUP_DIR/application/application_$DATE.tar.gz",
            "size": "$(du -h $FULL_BACKUP_DIR/application/application_$DATE.tar.gz 2>/dev/null | cut -f1 || echo 'N/A')"
        },
        "docker": {
            "config_backup": "$FULL_BACKUP_DIR/docker/docker_config_$DATE.tar.gz",
            "volumes_backup": "$FULL_BACKUP_DIR/docker/docker_volumes_$DATE.tar.gz",
            "config_size": "$(du -h $FULL_BACKUP_DIR/docker/docker_config_$DATE.tar.gz 2>/dev/null | cut -f1 || echo 'N/A')",
            "volumes_size": "$(du -h $FULL_BACKUP_DIR/docker/docker_volumes_$DATE.tar.gz 2>/dev/null | cut -f1 || echo 'N/A')"
        },
        "system": {
            "config_backup": "$FULL_BACKUP_DIR/system/system_config_$DATE.tar.gz",
            "size": "$(du -h $FULL_BACKUP_DIR/system/system_config_$DATE.tar.gz 2>/dev/null | cut -f1 || echo 'N/A')"
        },
        "ssl": {
            "certificates_backup": "$FULL_BACKUP_DIR/ssl/ssl_certificates_$DATE.tar.gz",
            "size": "$(du -h $FULL_BACKUP_DIR/ssl/ssl_certificates_$DATE.tar.gz 2>/dev/null | cut -f1 || echo 'N/A')"
        }
    },
    "total_size": "$(du -sh $FULL_BACKUP_DIR | cut -f1)",
    "backup_location": "$FULL_BACKUP_DIR",
    "restoration_notes": "Use restore-full.sh script for complete system restoration"
}
EOF

    log "Backup manifest created: $MANIFEST_FILE"
}

# Verify all backups
verify_backups() {
    log "Verifying backup integrity..."
    
    VERIFICATION_LOG="$FULL_BACKUP_DIR/verification_$DATE.log"
    
    {
        echo "Backup Verification Report - $(date)"
        echo "=================================="
        echo
        
        # Verify database backup
        echo "Database Backup Verification:"
        if [[ -f "$FULL_BACKUP_DIR/database/complete_db_$DATE.sql.gz" ]]; then
            if gzip -t "$FULL_BACKUP_DIR/database/complete_db_$DATE.sql.gz"; then
                echo "✓ Database backup integrity verified"
            else
                echo "✗ Database backup is corrupted"
            fi
        else
            echo "✗ Database backup file not found"
        fi
        
        # Verify application backup
        echo "Application Backup Verification:"
        if [[ -f "$FULL_BACKUP_DIR/application/application_$DATE.tar.gz" ]]; then
            if tar -tzf "$FULL_BACKUP_DIR/application/application_$DATE.tar.gz" > /dev/null 2>&1; then
                echo "✓ Application backup integrity verified"
            else
                echo "✗ Application backup is corrupted"
            fi
        else
            echo "✗ Application backup file not found"
        fi
        
        # Verify Docker backups
        echo "Docker Backup Verification:"
        if [[ -f "$FULL_BACKUP_DIR/docker/docker_config_$DATE.tar.gz" ]]; then
            if tar -tzf "$FULL_BACKUP_DIR/docker/docker_config_$DATE.tar.gz" > /dev/null 2>&1; then
                echo "✓ Docker configuration backup integrity verified"
            else
                echo "✗ Docker configuration backup is corrupted"
            fi
        else
            echo "✗ Docker configuration backup file not found"
        fi
        
        if [[ -f "$FULL_BACKUP_DIR/docker/docker_volumes_$DATE.tar.gz" ]]; then
            if tar -tzf "$FULL_BACKUP_DIR/docker/docker_volumes_$DATE.tar.gz" > /dev/null 2>&1; then
                echo "✓ Docker volumes backup integrity verified"
            else
                echo "✗ Docker volumes backup is corrupted"
            fi
        else
            echo "✗ Docker volumes backup file not found"
        fi
        
        echo
        echo "Total backup size: $(du -sh $FULL_BACKUP_DIR | cut -f1)"
        echo "Verification completed: $(date)"
        
    } > "$VERIFICATION_LOG"
    
    log "Backup verification completed: $VERIFICATION_LOG"
}

# Clean old full backups
cleanup_old_backups() {
    log "Cleaning up old full backups..."
    
    # Keep last 4 weekly full backups (1 month)
    find "$FULL_BACKUP_DIR" -maxdepth 1 -name "backup_manifest_*.json" -mtime +28 | while read manifest; do
        BACKUP_ID=$(basename "$manifest" | sed 's/backup_manifest_\(.*\)\.json/\1/')
        log "Removing old backup: $BACKUP_ID"
        
        # Remove all files for this backup
        find "$FULL_BACKUP_DIR" -name "*_${BACKUP_ID}.*" -delete
    done
    
    info "Old full backups cleaned up (kept last 4 weeks)"
}

# Send notification
send_notification() {
    log "Sending full backup notification..."
    
    TOTAL_SIZE=$(du -sh "$FULL_BACKUP_DIR" | cut -f1)
    
    REPORT="Full system backup completed successfully on $(date)

Backup Components:
- Database: $(du -h $FULL_BACKUP_DIR/database/complete_db_$DATE.sql.gz 2>/dev/null | cut -f1 || echo 'Failed')
- Application: $(du -h $FULL_BACKUP_DIR/application/application_$DATE.tar.gz 2>/dev/null | cut -f1 || echo 'Failed')
- Docker Config: $(du -h $FULL_BACKUP_DIR/docker/docker_config_$DATE.tar.gz 2>/dev/null | cut -f1 || echo 'Failed')
- Docker Volumes: $(du -h $FULL_BACKUP_DIR/docker/docker_volumes_$DATE.tar.gz 2>/dev/null | cut -f1 || echo 'Failed')
- System Config: $(du -h $FULL_BACKUP_DIR/system/system_config_$DATE.tar.gz 2>/dev/null | cut -f1 || echo 'Failed')
- SSL Certificates: $(du -h $FULL_BACKUP_DIR/ssl/ssl_certificates_$DATE.tar.gz 2>/dev/null | cut -f1 || echo 'Failed')

Total backup size: $TOTAL_SIZE
Backup location: $FULL_BACKUP_DIR
Backup ID: $DATE

Next full backup scheduled: $(date -d '+1 week' '+%Y-%m-%d %H:%M:%S')"

    # Save report
    echo "$REPORT" > "$FULL_BACKUP_DIR/full_backup_report_$DATE.txt"
    
    # Send email if configured
    if command -v mail >/dev/null 2>&1 && [[ -n "$BACKUP_EMAIL" ]]; then
        echo "$REPORT" | mail -s "Jewelry Platform Full Backup Report - $(date +%Y-%m-%d)" "$BACKUP_EMAIL"
        info "Full backup notification sent to $BACKUP_EMAIL"
    fi
    
    info "Full backup report saved: $FULL_BACKUP_DIR/full_backup_report_$DATE.txt"
}

# Main backup function
main() {
    log "Starting full system backup process..."
    
    create_backup_dirs
    load_environment
    backup_database
    backup_application
    backup_docker
    backup_system_config
    backup_ssl
    create_manifest
    verify_backups
    cleanup_old_backups
    send_notification
    
    log "Full system backup process completed successfully!"
    
    # Display summary
    echo
    info "Full Backup Summary:"
    info "Backup ID: $DATE"
    info "Total size: $(du -sh $FULL_BACKUP_DIR | cut -f1)"
    info "Location: $FULL_BACKUP_DIR"
    info "Manifest: $FULL_BACKUP_DIR/backup_manifest_$DATE.json"
    info "Verification: $FULL_BACKUP_DIR/verification_$DATE.log"
    echo
    warning "Important: Store this backup in a secure, off-site location"
}

# Run main function
main "$@"