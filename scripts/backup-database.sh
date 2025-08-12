#!/bin/bash

# Database Backup Script
# This script creates automated backups of the MySQL database

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
LOG_FILE="$APP_DIR/storage/logs/backup.log"
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

# Create backup directory
create_backup_dir() {
    if [[ ! -d "$BACKUP_DIR" ]]; then
        mkdir -p "$BACKUP_DIR"
        log "Created backup directory: $BACKUP_DIR"
    fi
    
    # Create subdirectories
    mkdir -p "$BACKUP_DIR/database"
    mkdir -p "$BACKUP_DIR/files"
    mkdir -p "$BACKUP_DIR/logs"
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

# Check database connectivity
check_database() {
    cd "$APP_DIR"
    
    if ! docker-compose -f docker-compose.prod.yml exec -T mysql mysqladmin ping -h localhost --silent; then
        error "Cannot connect to database"
    fi
    
    info "Database connectivity verified"
}

# Create database backup
backup_database() {
    log "Creating database backup..."
    
    cd "$APP_DIR"
    
    BACKUP_FILE="$BACKUP_DIR/database/db_backup_$DATE.sql"
    
    # Create database dump
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
        "$DB_DATABASE" > "$BACKUP_FILE"
    
    if [[ $? -eq 0 ]]; then
        # Compress backup
        gzip "$BACKUP_FILE"
        BACKUP_FILE="${BACKUP_FILE}.gz"
        
        # Get file size
        SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        
        log "Database backup created: $BACKUP_FILE ($SIZE)"
    else
        error "Database backup failed"
    fi
}

# Backup application files
backup_files() {
    log "Creating application files backup..."
    
    cd "$APP_DIR"
    
    FILES_BACKUP="$BACKUP_DIR/files/files_backup_$DATE.tar.gz"
    
    # Create tar archive of important files
    tar -czf "$FILES_BACKUP" \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='storage/logs' \
        --exclude='storage/framework/cache' \
        --exclude='storage/framework/sessions' \
        --exclude='storage/framework/views' \
        --exclude='.git' \
        --exclude='frontend/dist' \
        --exclude='frontend/node_modules' \
        .env \
        storage/app \
        public \
        resources \
        config \
        database/migrations \
        database/seeders \
        app \
        routes \
        composer.json \
        composer.lock \
        frontend/src \
        frontend/public \
        frontend/package.json \
        docker-compose.prod.yml \
        scripts
    
    if [[ $? -eq 0 ]]; then
        SIZE=$(du -h "$FILES_BACKUP" | cut -f1)
        log "Files backup created: $FILES_BACKUP ($SIZE)"
    else
        warning "Files backup failed"
    fi
}

# Backup logs
backup_logs() {
    log "Creating logs backup..."
    
    LOGS_BACKUP="$BACKUP_DIR/logs/logs_backup_$DATE.tar.gz"
    
    if [[ -d "$APP_DIR/storage/logs" ]]; then
        tar -czf "$LOGS_BACKUP" -C "$APP_DIR" storage/logs
        
        if [[ $? -eq 0 ]]; then
            SIZE=$(du -h "$LOGS_BACKUP" | cut -f1)
            log "Logs backup created: $LOGS_BACKUP ($SIZE)"
        else
            warning "Logs backup failed"
        fi
    else
        warning "Logs directory not found"
    fi
}

# Clean old backups
cleanup_old_backups() {
    log "Cleaning up old backups..."
    
    # Keep last 7 daily backups
    find "$BACKUP_DIR/database" -name "db_backup_*.sql.gz" -mtime +7 -delete
    find "$BACKUP_DIR/files" -name "files_backup_*.tar.gz" -mtime +7 -delete
    find "$BACKUP_DIR/logs" -name "logs_backup_*.tar.gz" -mtime +7 -delete
    
    info "Old backups cleaned up (kept last 7 days)"
}

# Verify backup integrity
verify_backup() {
    log "Verifying backup integrity..."
    
    DB_BACKUP="$BACKUP_DIR/database/db_backup_$DATE.sql.gz"
    FILES_BACKUP="$BACKUP_DIR/files/files_backup_$DATE.tar.gz"
    
    # Test database backup
    if [[ -f "$DB_BACKUP" ]]; then
        if gzip -t "$DB_BACKUP"; then
            info "Database backup integrity verified"
        else
            error "Database backup is corrupted"
        fi
    fi
    
    # Test files backup
    if [[ -f "$FILES_BACKUP" ]]; then
        if tar -tzf "$FILES_BACKUP" > /dev/null; then
            info "Files backup integrity verified"
        else
            warning "Files backup may be corrupted"
        fi
    fi
}

# Send backup notification
send_notification() {
    log "Sending backup notification..."
    
    # Calculate total backup size
    TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
    
    # Create backup report
    REPORT="Backup completed successfully on $(date)

Database backup: $(ls -lh $BACKUP_DIR/database/db_backup_$DATE.sql.gz 2>/dev/null | awk '{print $5}' || echo 'Failed')
Files backup: $(ls -lh $BACKUP_DIR/files/files_backup_$DATE.tar.gz 2>/dev/null | awk '{print $5}' || echo 'Failed')
Logs backup: $(ls -lh $BACKUP_DIR/logs/logs_backup_$DATE.tar.gz 2>/dev/null | awk '{print $5}' || echo 'Failed')

Total backup directory size: $TOTAL_SIZE
Backup location: $BACKUP_DIR

Next backup scheduled: $(date -d '+1 day' '+%Y-%m-%d %H:%M:%S')"

    # Save report
    echo "$REPORT" > "$BACKUP_DIR/backup_report_$DATE.txt"
    
    # If mail is configured, send email notification
    if command -v mail >/dev/null 2>&1 && [[ -n "$BACKUP_EMAIL" ]]; then
        echo "$REPORT" | mail -s "Jewelry Platform Backup Report - $(date +%Y-%m-%d)" "$BACKUP_EMAIL"
        info "Backup notification sent to $BACKUP_EMAIL"
    fi
    
    info "Backup report saved: $BACKUP_DIR/backup_report_$DATE.txt"
}

# Create backup summary
create_summary() {
    log "Creating backup summary..."
    
    SUMMARY_FILE="$BACKUP_DIR/backup_summary.json"
    
    cat > "$SUMMARY_FILE" << EOF
{
    "last_backup": {
        "date": "$(date -Iseconds)",
        "database_backup": "$BACKUP_DIR/database/db_backup_$DATE.sql.gz",
        "files_backup": "$BACKUP_DIR/files/files_backup_$DATE.tar.gz",
        "logs_backup": "$BACKUP_DIR/logs/logs_backup_$DATE.tar.gz",
        "status": "completed"
    },
    "backup_retention": "7 days",
    "next_backup": "$(date -d '+1 day' -Iseconds)"
}
EOF

    info "Backup summary updated: $SUMMARY_FILE"
}

# Main backup function
main() {
    log "Starting database backup process..."
    
    create_backup_dir
    load_environment
    check_database
    backup_database
    backup_files
    backup_logs
    cleanup_old_backups
    verify_backup
    send_notification
    create_summary
    
    log "Database backup process completed successfully!"
    
    # Display backup information
    echo
    info "Backup Summary:"
    info "Date: $(date)"
    info "Database backup: $BACKUP_DIR/database/db_backup_$DATE.sql.gz"
    info "Files backup: $BACKUP_DIR/files/files_backup_$DATE.tar.gz"
    info "Logs backup: $BACKUP_DIR/logs/logs_backup_$DATE.tar.gz"
    info "Total backup size: $(du -sh $BACKUP_DIR | cut -f1)"
}

# Run main function
main "$@"