# Monitoring and Logging Setup Guide

## Overview

This guide covers the setup of comprehensive monitoring and logging for the jewelry platform in production. It includes system monitoring, application monitoring, log management, and alerting.

## System Monitoring

### 1. Install Monitoring Tools

```bash
# Install system monitoring tools
sudo apt update
sudo apt install -y htop iotop nethogs sysstat

# Install log analysis tools
sudo apt install -y logwatch fail2ban

# Install disk monitoring
sudo apt install -y smartmontools
```

### 2. Configure System Monitoring Script

Create a comprehensive monitoring script:

```bash
sudo nano /usr/local/bin/system-monitor.sh
```

Add the following content:

```bash
#!/bin/bash

# System Monitoring Script
LOG_FILE="/var/log/system-monitor.log"
ALERT_EMAIL="admin@yourdomain.com"
APP_DIR="/var/www/jewelry-platform"

# Thresholds
CPU_THRESHOLD=80
MEMORY_THRESHOLD=80
DISK_THRESHOLD=85
LOAD_THRESHOLD=2.0

# Functions
log_message() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

send_alert() {
    local subject="$1"
    local message="$2"
    
    echo "$message" | mail -s "$subject" "$ALERT_EMAIL" 2>/dev/null || true
    log_message "ALERT: $subject"
}

# Check CPU usage
check_cpu() {
    CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | sed 's/%us,//')
    CPU_USAGE=${CPU_USAGE%.*}
    
    if [ "$CPU_USAGE" -gt "$CPU_THRESHOLD" ]; then
        send_alert "High CPU Usage" "CPU usage is at ${CPU_USAGE}% (threshold: ${CPU_THRESHOLD}%)"
    fi
}

# Check memory usage
check_memory() {
    MEMORY_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    
    if [ "$MEMORY_USAGE" -gt "$MEMORY_THRESHOLD" ]; then
        send_alert "High Memory Usage" "Memory usage is at ${MEMORY_USAGE}% (threshold: ${MEMORY_THRESHOLD}%)"
    fi
}

# Check disk usage
check_disk() {
    DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$DISK_USAGE" -gt "$DISK_THRESHOLD" ]; then
        send_alert "High Disk Usage" "Disk usage is at ${DISK_USAGE}% (threshold: ${DISK_THRESHOLD}%)"
    fi
}

# Check load average
check_load() {
    LOAD_AVG=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    
    if (( $(echo "$LOAD_AVG > $LOAD_THRESHOLD" | bc -l) )); then
        send_alert "High Load Average" "Load average is $LOAD_AVG (threshold: $LOAD_THRESHOLD)"
    fi
}

# Check Docker containers
check_containers() {
    cd "$APP_DIR"
    
    if ! docker-compose -f docker-compose.prod.yml ps | grep -q "Up"; then
        send_alert "Container Down" "One or more Docker containers are not running"
        docker-compose -f docker-compose.prod.yml up -d
    fi
}

# Check application health
check_app_health() {
    if ! curl -f -s http://localhost:8000/api/health > /dev/null; then
        send_alert "Application Health Check Failed" "Application health endpoint is not responding"
    fi
}

# Check SSL certificate expiration
check_ssl() {
    DOMAIN=$(grep APP_URL "$APP_DIR/.env" | cut -d'=' -f2 | sed 's|https://||' | sed 's|http://||')
    
    if [ -n "$DOMAIN" ]; then
        EXPIRY_DATE=$(echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN:443" 2>/dev/null | openssl x509 -noout -dates | grep notAfter | cut -d= -f2)
        EXPIRY_TIMESTAMP=$(date -d "$EXPIRY_DATE" +%s)
        CURRENT_TIMESTAMP=$(date +%s)
        DAYS_UNTIL_EXPIRY=$(( (EXPIRY_TIMESTAMP - CURRENT_TIMESTAMP) / 86400 ))
        
        if [ "$DAYS_UNTIL_EXPIRY" -lt 30 ]; then
            send_alert "SSL Certificate Expiring" "SSL certificate for $DOMAIN expires in $DAYS_UNTIL_EXPIRY days"
        fi
    fi
}

# Main monitoring function
main() {
    log_message "Starting system monitoring check"
    
    check_cpu
    check_memory
    check_disk
    check_load
    check_containers
    check_app_health
    check_ssl
    
    log_message "System monitoring check completed"
}

main "$@"
```

Make the script executable and add to cron:

```bash
sudo chmod +x /usr/local/bin/system-monitor.sh

# Add to cron for every 5 minutes
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/local/bin/system-monitor.sh") | crontab -
```

## Application Monitoring

### 1. Laravel Application Monitoring

Create application monitoring script:

```bash
nano /var/www/jewelry-platform/scripts/app-monitor.sh
```

```bash
#!/bin/bash

# Application Monitoring Script
APP_DIR="/var/www/jewelry-platform"
LOG_FILE="$APP_DIR/storage/logs/app-monitoring.log"

log_message() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Check queue workers
check_queue_workers() {
    cd "$APP_DIR"
    
    QUEUE_WORKERS=$(docker-compose -f docker-compose.prod.yml exec -T app php artisan queue:work --stop-when-empty --timeout=1 2>&1 | grep -c "Processing" || echo "0")
    
    if [ "$QUEUE_WORKERS" -eq 0 ]; then
        log_message "WARNING: No queue workers are processing jobs"
        # Restart queue workers
        docker-compose -f docker-compose.prod.yml exec -d app php artisan queue:restart
    fi
}

# Check database connectivity
check_database() {
    cd "$APP_DIR"
    
    if ! docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate:status > /dev/null 2>&1; then
        log_message "ERROR: Database connectivity check failed"
    else
        log_message "INFO: Database connectivity OK"
    fi
}

# Check storage permissions
check_storage_permissions() {
    if [ ! -w "$APP_DIR/storage/logs" ]; then
        log_message "ERROR: Storage logs directory is not writable"
        chmod -R 755 "$APP_DIR/storage"
    fi
}

# Check application errors
check_app_errors() {
    ERROR_COUNT=$(tail -n 100 "$APP_DIR/storage/logs/laravel.log" 2>/dev/null | grep -c "ERROR" || echo "0")
    
    if [ "$ERROR_COUNT" -gt 10 ]; then
        log_message "WARNING: High number of application errors detected ($ERROR_COUNT in last 100 log entries)"
    fi
}

# Main function
main() {
    log_message "Starting application monitoring check"
    
    check_queue_workers
    check_database
    check_storage_permissions
    check_app_errors
    
    log_message "Application monitoring check completed"
}

main "$@"
```

Make executable and add to cron:

```bash
chmod +x /var/www/jewelry-platform/scripts/app-monitor.sh
(crontab -l 2>/dev/null; echo "*/10 * * * * /var/www/jewelry-platform/scripts/app-monitor.sh") | crontab -
```

## Log Management

### 1. Configure Log Rotation

Create comprehensive log rotation configuration:

```bash
sudo nano /etc/logrotate.d/jewelry-platform
```

```
# Application logs
/var/www/jewelry-platform/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        docker-compose -f /var/www/jewelry-platform/docker-compose.prod.yml exec app php artisan cache:clear > /dev/null 2>&1 || true
    endscript
}

# System monitoring logs
/var/log/system-monitor.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 root root
}

# Nginx logs
/var/log/nginx/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data adm
    sharedscripts
    prerotate
        if [ -d /etc/logrotate.d/httpd-prerotate ]; then \
            run-parts /etc/logrotate.d/httpd-prerotate; \
        fi
    endscript
    postrotate
        invoke-rc.d nginx rotate >/dev/null 2>&1
    endscript
}
```

### 2. Configure Centralized Logging

Create log aggregation script:

```bash
sudo nano /usr/local/bin/log-aggregator.sh
```

```bash
#!/bin/bash

# Log Aggregation Script
CENTRAL_LOG_DIR="/var/log/jewelry-platform"
APP_DIR="/var/www/jewelry-platform"
DATE=$(date +%Y%m%d)

# Create central log directory
mkdir -p "$CENTRAL_LOG_DIR"

# Aggregate application logs
{
    echo "=== Application Logs - $(date) ==="
    tail -n 100 "$APP_DIR/storage/logs/laravel.log" 2>/dev/null || echo "Laravel log not found"
    echo
    
    echo "=== System Monitor Logs - $(date) ==="
    tail -n 50 "/var/log/system-monitor.log" 2>/dev/null || echo "System monitor log not found"
    echo
    
    echo "=== Nginx Access Logs - $(date) ==="
    tail -n 50 "/var/log/nginx/access.log" 2>/dev/null || echo "Nginx access log not found"
    echo
    
    echo "=== Nginx Error Logs - $(date) ==="
    tail -n 50 "/var/log/nginx/error.log" 2>/dev/null || echo "Nginx error log not found"
    echo
    
    echo "=== Docker Container Logs - $(date) ==="
    cd "$APP_DIR"
    docker-compose -f docker-compose.prod.yml logs --tail=50 2>/dev/null || echo "Docker logs not available"
    
} > "$CENTRAL_LOG_DIR/aggregated_$DATE.log"

# Compress old aggregated logs
find "$CENTRAL_LOG_DIR" -name "aggregated_*.log" -mtime +7 -exec gzip {} \;

# Remove old compressed logs
find "$CENTRAL_LOG_DIR" -name "aggregated_*.log.gz" -mtime +30 -delete
```

Make executable and schedule:

```bash
sudo chmod +x /usr/local/bin/log-aggregator.sh
(crontab -l 2>/dev/null; echo "0 1 * * * /usr/local/bin/log-aggregator.sh") | crontab -
```

## Performance Monitoring

### 1. Database Performance Monitoring

Create database performance monitoring:

```bash
nano /var/www/jewelry-platform/scripts/db-performance-monitor.sh
```

```bash
#!/bin/bash

# Database Performance Monitoring Script
APP_DIR="/var/www/jewelry-platform"
LOG_FILE="$APP_DIR/storage/logs/db-performance.log"

cd "$APP_DIR"
source .env

log_message() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Check slow queries
check_slow_queries() {
    SLOW_QUERIES=$(docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SHOW STATUS LIKE 'Slow_queries';" | grep -v Variable_name | awk '{print $2}')
    
    log_message "Slow queries count: $SLOW_QUERIES"
    
    if [ "$SLOW_QUERIES" -gt 100 ]; then
        log_message "WARNING: High number of slow queries detected"
    fi
}

# Check connection count
check_connections() {
    CONNECTIONS=$(docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SHOW STATUS LIKE 'Threads_connected';" | grep -v Variable_name | awk '{print $2}')
    
    log_message "Active connections: $CONNECTIONS"
    
    if [ "$CONNECTIONS" -gt 50 ]; then
        log_message "WARNING: High number of database connections"
    fi
}

# Check table sizes
check_table_sizes() {
    log_message "Checking table sizes..."
    
    docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "
        SELECT 
            table_name as 'Table',
            ROUND(((data_length + index_length) / 1024 / 1024), 2) as 'Size (MB)'
        FROM information_schema.tables 
        WHERE table_schema = '$DB_DATABASE'
        AND ((data_length + index_length) / 1024 / 1024) > 10
        ORDER BY (data_length + index_length) DESC;" >> "$LOG_FILE"
}

# Main function
main() {
    log_message "Starting database performance monitoring"
    
    check_slow_queries
    check_connections
    check_table_sizes
    
    log_message "Database performance monitoring completed"
}

main "$@"
```

### 2. Application Performance Monitoring

Create application performance monitoring:

```bash
nano /var/www/jewelry-platform/scripts/app-performance-monitor.sh
```

```bash
#!/bin/bash

# Application Performance Monitoring Script
APP_DIR="/var/www/jewelry-platform"
LOG_FILE="$APP_DIR/storage/logs/app-performance.log"

log_message() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Check response times
check_response_times() {
    ENDPOINTS=(
        "http://localhost:8000/api/health"
        "http://localhost:8000/api/dashboard/kpis"
        "http://localhost:3000"
    )
    
    for endpoint in "${ENDPOINTS[@]}"; do
        RESPONSE_TIME=$(curl -o /dev/null -s -w '%{time_total}' "$endpoint" 2>/dev/null || echo "999")
        log_message "Response time for $endpoint: ${RESPONSE_TIME}s"
        
        if (( $(echo "$RESPONSE_TIME > 2.0" | bc -l) )); then
            log_message "WARNING: Slow response time for $endpoint"
        fi
    done
}

# Check memory usage
check_memory_usage() {
    MEMORY_USAGE=$(docker stats --no-stream --format "table {{.Container}}\t{{.MemUsage}}" | grep jewelry-platform)
    log_message "Container memory usage: $MEMORY_USAGE"
}

# Check disk I/O
check_disk_io() {
    DISK_IO=$(iostat -x 1 1 | grep -E "(Device|sda|nvme)" | tail -2)
    log_message "Disk I/O: $DISK_IO"
}

# Main function
main() {
    log_message "Starting application performance monitoring"
    
    check_response_times
    check_memory_usage
    check_disk_io
    
    log_message "Application performance monitoring completed"
}

main "$@"
```

Make scripts executable and schedule:

```bash
chmod +x /var/www/jewelry-platform/scripts/db-performance-monitor.sh
chmod +x /var/www/jewelry-platform/scripts/app-performance-monitor.sh

# Schedule performance monitoring
(crontab -l 2>/dev/null; echo "*/15 * * * * /var/www/jewelry-platform/scripts/db-performance-monitor.sh") | crontab -
(crontab -l 2>/dev/null; echo "*/15 * * * * /var/www/jewelry-platform/scripts/app-performance-monitor.sh") | crontab -
```

## Alerting System

### 1. Configure Email Alerts

Install and configure mail system:

```bash
# Install mail system
sudo apt install -y postfix mailutils

# Configure postfix (choose "Internet Site" when prompted)
sudo dpkg-reconfigure postfix
```

### 2. Create Alert Management Script

```bash
sudo nano /usr/local/bin/alert-manager.sh
```

```bash
#!/bin/bash

# Alert Management Script
ALERT_LOG="/var/log/alerts.log"
ALERT_EMAIL="admin@yourdomain.com"
ALERT_THRESHOLD_FILE="/tmp/alert_thresholds"

# Initialize threshold tracking
touch "$ALERT_THRESHOLD_FILE"

log_alert() {
    local level="$1"
    local message="$2"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] [$level] $message" >> "$ALERT_LOG"
}

send_alert() {
    local subject="$1"
    local message="$2"
    local alert_key="$3"
    
    # Check if we've already sent this alert recently (within 1 hour)
    if grep -q "$alert_key" "$ALERT_THRESHOLD_FILE"; then
        LAST_ALERT=$(grep "$alert_key" "$ALERT_THRESHOLD_FILE" | cut -d: -f2)
        CURRENT_TIME=$(date +%s)
        
        if [ $((CURRENT_TIME - LAST_ALERT)) -lt 3600 ]; then
            return 0  # Skip sending alert
        fi
    fi
    
    # Send alert
    echo "$message" | mail -s "$subject - Jewelry Platform Alert" "$ALERT_EMAIL"
    log_alert "ALERT" "$subject: $message"
    
    # Update threshold file
    grep -v "$alert_key" "$ALERT_THRESHOLD_FILE" > "${ALERT_THRESHOLD_FILE}.tmp" 2>/dev/null || true
    echo "$alert_key:$(date +%s)" >> "${ALERT_THRESHOLD_FILE}.tmp"
    mv "${ALERT_THRESHOLD_FILE}.tmp" "$ALERT_THRESHOLD_FILE"
}

# Export functions for use by other scripts
export -f log_alert send_alert
```

## Dashboard and Reporting

### 1. Create Monitoring Dashboard Script

```bash
nano /var/www/jewelry-platform/scripts/monitoring-dashboard.sh
```

```bash
#!/bin/bash

# Monitoring Dashboard Generator
APP_DIR="/var/www/jewelry-platform"
DASHBOARD_FILE="$APP_DIR/storage/app/public/monitoring-dashboard.html"

generate_dashboard() {
    cat > "$DASHBOARD_FILE" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Jewelry Platform Monitoring Dashboard</title>
    <meta http-equiv="refresh" content="60">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .metric { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .timestamp { color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>Jewelry Platform Monitoring Dashboard</h1>
    <p class="timestamp">Last updated: $(date)</p>
    
    <div class="metric success">
        <h3>System Status</h3>
        <p>Uptime: $(uptime -p)</p>
        <p>Load Average: $(uptime | awk -F'load average:' '{print $2}')</p>
    </div>
    
    <div class="metric">
        <h3>Resource Usage</h3>
        <p>CPU: $(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | sed 's/%us,//')%</p>
        <p>Memory: $(free | awk 'NR==2{printf "%.1f%%", $3*100/$2}') used</p>
        <p>Disk: $(df / | awk 'NR==2 {print $5}') used</p>
    </div>
    
    <div class="metric">
        <h3>Docker Containers</h3>
        <pre>$(cd $APP_DIR && docker-compose -f docker-compose.prod.yml ps)</pre>
    </div>
    
    <div class="metric">
        <h3>Recent Logs</h3>
        <pre>$(tail -n 10 $APP_DIR/storage/logs/laravel.log 2>/dev/null | head -20)</pre>
    </div>
    
    <div class="metric">
        <h3>Database Status</h3>
        <p>Connection: $(cd $APP_DIR && docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate:status > /dev/null 2>&1 && echo "OK" || echo "ERROR")</p>
    </div>
</body>
</html>
EOF
}

generate_dashboard
```

Schedule dashboard updates:

```bash
chmod +x /var/www/jewelry-platform/scripts/monitoring-dashboard.sh
(crontab -l 2>/dev/null; echo "*/5 * * * * /var/www/jewelry-platform/scripts/monitoring-dashboard.sh") | crontab -
```

## Log Analysis and Reporting

### 1. Create Weekly Report Generator

```bash
nano /var/www/jewelry-platform/scripts/weekly-report.sh
```

```bash
#!/bin/bash

# Weekly Monitoring Report Generator
APP_DIR="/var/www/jewelry-platform"
REPORT_FILE="$APP_DIR/storage/logs/weekly-report-$(date +%Y%m%d).txt"

{
    echo "Jewelry Platform Weekly Report - $(date)"
    echo "========================================"
    echo
    
    echo "System Overview:"
    echo "- Uptime: $(uptime -p)"
    echo "- Average Load: $(uptime | awk -F'load average:' '{print $2}')"
    echo "- Disk Usage: $(df -h / | awk 'NR==2 {print $5}')"
    echo
    
    echo "Application Metrics:"
    echo "- Total Errors (last 7 days): $(grep -c "ERROR" $APP_DIR/storage/logs/laravel.log 2>/dev/null || echo "0")"
    echo "- Database Size: $(cd $APP_DIR && docker-compose -f docker-compose.prod.yml exec -T mysql mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size (MB)' FROM information_schema.tables WHERE table_schema = '$DB_DATABASE';" 2>/dev/null | tail -1 || echo "N/A")"
    echo
    
    echo "Security Events:"
    echo "- Failed Login Attempts: $(grep -c "authentication failed" /var/log/auth.log 2>/dev/null || echo "0")"
    echo "- Fail2ban Actions: $(grep -c "Ban" /var/log/fail2ban.log 2>/dev/null || echo "0")"
    echo
    
    echo "Performance Summary:"
    echo "- Average Response Time: $(grep "Response time" $APP_DIR/storage/logs/app-performance.log 2>/dev/null | tail -10 | awk '{sum+=$NF} END {print sum/NR "s"}' || echo "N/A")"
    echo "- Slow Queries: $(grep "Slow queries count" $APP_DIR/storage/logs/db-performance.log 2>/dev/null | tail -1 | awk '{print $NF}' || echo "N/A")"
    
} > "$REPORT_FILE"

# Email the report
if command -v mail >/dev/null 2>&1; then
    mail -s "Jewelry Platform Weekly Report - $(date +%Y-%m-%d)" admin@yourdomain.com < "$REPORT_FILE"
fi
```

Schedule weekly reports:

```bash
chmod +x /var/www/jewelry-platform/scripts/weekly-report.sh
(crontab -l 2>/dev/null; echo "0 9 * * 1 /var/www/jewelry-platform/scripts/weekly-report.sh") | crontab -
```

## Troubleshooting

### Common Issues

1. **High CPU Usage**
   - Check for runaway processes
   - Optimize database queries
   - Scale application resources

2. **Memory Leaks**
   - Monitor container memory usage
   - Restart containers if needed
   - Check for memory-intensive operations

3. **Disk Space Issues**
   - Clean up old logs
   - Optimize database
   - Move backups to external storage

4. **Network Issues**
   - Check firewall settings
   - Monitor network connections
   - Verify DNS resolution

### Monitoring Commands

```bash
# Real-time system monitoring
htop

# Network monitoring
nethogs

# Disk I/O monitoring
iotop

# Docker container monitoring
docker stats

# Application logs
tail -f /var/www/jewelry-platform/storage/logs/laravel.log

# System logs
tail -f /var/log/syslog
```

This comprehensive monitoring and logging setup provides complete visibility into your jewelry platform's health and performance.