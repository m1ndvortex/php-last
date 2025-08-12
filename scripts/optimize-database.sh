#!/bin/bash

# Database Optimization Script for Production
# This script optimizes MySQL database performance for the jewelry platform

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
LOG_FILE="$APP_DIR/storage/logs/database-optimization.log"

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

# Check if database is accessible
check_database() {
    log "Checking database connectivity..."
    
    cd "$APP_DIR"
    
    if ! docker-compose -f docker-compose.prod.yml exec -T mysql mysqladmin ping -h localhost --silent; then
        error "Cannot connect to database"
    fi
    
    info "Database is accessible"
}

# Create optimized MySQL configuration
create_mysql_config() {
    log "Creating optimized MySQL configuration..."
    
    # Create MySQL configuration directory if it doesn't exist
    mkdir -p "$APP_DIR/docker/mysql/conf.d"
    
    # Create optimization configuration
    cat > "$APP_DIR/docker/mysql/conf.d/optimization.cnf" << 'EOF'
[mysqld]
# Basic Settings
default-storage-engine = InnoDB
sql_mode = STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION

# Connection Settings
max_connections = 100
max_connect_errors = 1000000
max_allowed_packet = 64M
interactive_timeout = 3600
wait_timeout = 3600

# Buffer Pool Settings
innodb_buffer_pool_size = 256M
innodb_buffer_pool_instances = 1
innodb_buffer_pool_chunk_size = 128M

# Log Settings
innodb_log_file_size = 64M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Performance Settings
innodb_read_io_threads = 4
innodb_write_io_threads = 4
innodb_io_capacity = 200
innodb_io_capacity_max = 400

# Query Cache (for MySQL 5.7 and below)
query_cache_type = 1
query_cache_size = 32M
query_cache_limit = 2M

# Table Cache
table_open_cache = 2000
table_definition_cache = 1400

# Temporary Tables
tmp_table_size = 32M
max_heap_table_size = 32M

# MyISAM Settings
key_buffer_size = 32M
myisam_sort_buffer_size = 8M

# Binary Logging
binlog_cache_size = 1M
max_binlog_cache_size = 128M
max_binlog_size = 100M
expire_logs_days = 7

# Slow Query Log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1

# Error Log
log_error = /var/log/mysql/error.log

# General Log (disable in production)
general_log = 0

# Character Set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Time Zone
default-time-zone = '+00:00'

# Security
local_infile = 0
skip_show_database = 1

# InnoDB Settings for SSD
innodb_flush_neighbors = 0
innodb_random_read_ahead = 0
innodb_read_ahead_threshold = 0

# Performance Schema (disable if not needed)
performance_schema = OFF
EOF

    info "MySQL configuration created"
}

# Apply database indexes
apply_indexes() {
    log "Applying database performance indexes..."
    
    cd "$APP_DIR"
    
    # Run the index migration
    if docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --path=database/migrations/2025_08_08_000001_add_performance_indexes.php --force; then
        info "Performance indexes applied successfully"
    else
        warning "Performance indexes may already exist"
    fi
}

# Optimize existing tables
optimize_tables() {
    log "Optimizing database tables..."
    
    cd "$APP_DIR"
    
    # Get database credentials from .env
    source .env
    
    # List of tables to optimize
    TABLES=(
        "users"
        "customers" 
        "inventory_items"
        "invoices"
        "invoice_items"
        "inventory_movements"
        "communications"
        "transactions"
        "accounts"
        "categories"
        "locations"
    )
    
    for table in "${TABLES[@]}"; do
        log "Optimizing table: $table"
        docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "OPTIMIZE TABLE $table;" || warning "Failed to optimize $table"
    done
    
    info "Table optimization completed"
}

# Analyze table statistics
analyze_tables() {
    log "Analyzing table statistics..."
    
    cd "$APP_DIR"
    source .env
    
    TABLES=(
        "users"
        "customers" 
        "inventory_items"
        "invoices"
        "invoice_items"
        "inventory_movements"
        "communications"
        "transactions"
        "accounts"
        "categories"
        "locations"
    )
    
    for table in "${TABLES[@]}"; do
        log "Analyzing table: $table"
        docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "ANALYZE TABLE $table;" || warning "Failed to analyze $table"
    done
    
    info "Table analysis completed"
}

# Check and repair tables if needed
check_repair_tables() {
    log "Checking and repairing tables if needed..."
    
    cd "$APP_DIR"
    source .env
    
    TABLES=(
        "users"
        "customers" 
        "inventory_items"
        "invoices"
        "invoice_items"
        "inventory_movements"
        "communications"
        "transactions"
        "accounts"
        "categories"
        "locations"
    )
    
    for table in "${TABLES[@]}"; do
        log "Checking table: $table"
        
        # Check table
        CHECK_RESULT=$(docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "CHECK TABLE $table;" | grep -v "Table" | awk '{print $4}')
        
        if [[ "$CHECK_RESULT" != "OK" ]]; then
            warning "Table $table needs repair"
            docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "REPAIR TABLE $table;"
        fi
    done
    
    info "Table check and repair completed"
}

# Update table statistics
update_statistics() {
    log "Updating table statistics..."
    
    cd "$APP_DIR"
    source .env
    
    # Update statistics for InnoDB tables
    docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "
        SELECT CONCAT('ANALYZE TABLE ', table_schema, '.', table_name, ';') 
        FROM information_schema.tables 
        WHERE table_schema = '$DB_DATABASE' 
        AND engine = 'InnoDB';" | grep -v CONCAT > /tmp/analyze_queries.sql
    
    if [[ -s /tmp/analyze_queries.sql ]]; then
        docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < /tmp/analyze_queries.sql
        rm /tmp/analyze_queries.sql
    fi
    
    info "Table statistics updated"
}

# Configure query cache
configure_query_cache() {
    log "Configuring query cache..."
    
    cd "$APP_DIR"
    source .env
    
    # Reset query cache
    docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "RESET QUERY CACHE;" || warning "Query cache reset failed (may not be available in MySQL 8.0+)"
    
    info "Query cache configured"
}

# Monitor slow queries
setup_slow_query_monitoring() {
    log "Setting up slow query monitoring..."
    
    cd "$APP_DIR"
    
    # Create slow query analysis script
    cat > "$APP_DIR/scripts/analyze-slow-queries.sh" << 'EOF'
#!/bin/bash

# Slow Query Analysis Script
APP_DIR="/var/www/jewelry-platform"
LOG_FILE="$APP_DIR/storage/logs/slow-query-analysis.log"

echo "[$(date)] Analyzing slow queries..." >> "$LOG_FILE"

# Check if slow query log exists
if docker-compose -f "$APP_DIR/docker-compose.prod.yml" exec -T mysql test -f /var/log/mysql/slow.log; then
    # Get slow queries from the last 24 hours
    docker-compose -f "$APP_DIR/docker-compose.prod.yml" exec -T mysql tail -n 1000 /var/log/mysql/slow.log | grep -A 10 -B 2 "Query_time" >> "$LOG_FILE"
else
    echo "[$(date)] Slow query log not found" >> "$LOG_FILE"
fi

# Check for queries taking longer than 5 seconds
docker-compose -f "$APP_DIR/docker-compose.prod.yml" exec -T mysql mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "
    SELECT 
        ROUND(SUM(timer_end-timer_start)/1000000000000,6) as query_time_seconds,
        sql_text,
        count_star as exec_count
    FROM performance_schema.events_statements_summary_by_digest 
    WHERE avg_timer_wait > 5000000000000
    ORDER BY query_time_seconds DESC 
    LIMIT 10;
" 2>/dev/null >> "$LOG_FILE" || echo "[$(date)] Performance schema not available" >> "$LOG_FILE"
EOF

    chmod +x "$APP_DIR/scripts/analyze-slow-queries.sh"
    
    # Add to cron for daily analysis
    (crontab -l 2>/dev/null; echo "0 6 * * * $APP_DIR/scripts/analyze-slow-queries.sh") | crontab -
    
    info "Slow query monitoring configured"
}

# Create database maintenance script
create_maintenance_script() {
    log "Creating database maintenance script..."
    
    cat > "$APP_DIR/scripts/database-maintenance.sh" << 'EOF'
#!/bin/bash

# Database Maintenance Script
# Run this script weekly for database maintenance

set -e

APP_DIR="/var/www/jewelry-platform"
LOG_FILE="$APP_DIR/storage/logs/database-maintenance.log"

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

cd "$APP_DIR"
source .env

log "Starting database maintenance..."

# Optimize tables
log "Optimizing tables..."
TABLES=("users" "customers" "inventory_items" "invoices" "invoice_items" "inventory_movements" "communications" "transactions" "accounts" "categories" "locations")

for table in "${TABLES[@]}"; do
    log "Optimizing $table..."
    docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "OPTIMIZE TABLE $table;" || log "Warning: Failed to optimize $table"
done

# Update statistics
log "Updating table statistics..."
docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "
    SELECT CONCAT('ANALYZE TABLE ', table_schema, '.', table_name, ';') 
    FROM information_schema.tables 
    WHERE table_schema = '$DB_DATABASE' 
    AND engine = 'InnoDB';" | grep -v CONCAT | while read query; do
    docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "$query"
done

# Clean up old logs
log "Cleaning up old binary logs..."
docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "PURGE BINARY LOGS BEFORE DATE(NOW() - INTERVAL 7 DAY);" || log "Warning: Failed to purge binary logs"

# Check database size
log "Database size information:"
docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "
    SELECT 
        table_schema as 'Database',
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as 'Size (MB)'
    FROM information_schema.tables 
    WHERE table_schema = '$DB_DATABASE'
    GROUP BY table_schema;" | tee -a "$LOG_FILE"

log "Database maintenance completed"
EOF

    chmod +x "$APP_DIR/scripts/database-maintenance.sh"
    
    # Add to cron for weekly maintenance
    (crontab -l 2>/dev/null; echo "0 3 * * 0 $APP_DIR/scripts/database-maintenance.sh") | crontab -
    
    info "Database maintenance script created"
}

# Generate database performance report
generate_performance_report() {
    log "Generating database performance report..."
    
    cd "$APP_DIR"
    source .env
    
    REPORT_FILE="$APP_DIR/storage/logs/database-performance-$(date +%Y%m%d).log"
    
    {
        echo "Database Performance Report - $(date)"
        echo "========================================"
        echo
        
        echo "Database Size:"
        docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "
            SELECT 
                table_name as 'Table',
                ROUND(((data_length + index_length) / 1024 / 1024), 2) as 'Size (MB)',
                table_rows as 'Rows'
            FROM information_schema.tables 
            WHERE table_schema = '$DB_DATABASE'
            ORDER BY (data_length + index_length) DESC;"
        
        echo
        echo "Index Usage:"
        docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "
            SELECT 
                table_name as 'Table',
                index_name as 'Index',
                column_name as 'Column',
                cardinality as 'Cardinality'
            FROM information_schema.statistics 
            WHERE table_schema = '$DB_DATABASE'
            ORDER BY table_name, index_name;"
        
        echo
        echo "Connection Status:"
        docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SHOW STATUS LIKE 'Connections';"
        docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SHOW STATUS LIKE 'Max_used_connections';"
        docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SHOW STATUS LIKE 'Threads_connected';"
        
    } > "$REPORT_FILE"
    
    info "Performance report generated: $REPORT_FILE"
}

# Main optimization function
main() {
    log "Starting database optimization..."
    
    check_database
    create_mysql_config
    apply_indexes
    optimize_tables
    analyze_tables
    check_repair_tables
    update_statistics
    configure_query_cache
    setup_slow_query_monitoring
    create_maintenance_script
    generate_performance_report
    
    log "Database optimization completed successfully!"
    echo
    info "Optimization Summary:"
    info "- MySQL configuration optimized"
    info "- Performance indexes applied"
    info "- Tables optimized and analyzed"
    info "- Slow query monitoring configured"
    info "- Maintenance scripts created"
    info "- Performance report generated"
    echo
    warning "Note: You may need to restart MySQL container for configuration changes to take effect:"
    warning "docker-compose -f docker-compose.prod.yml restart mysql"
}

# Run main function
main "$@"