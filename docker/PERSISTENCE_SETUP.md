# Docker Data Persistence Setup

This document describes the comprehensive data persistence configuration implemented for the Bilingual Jewelry Platform.

## Overview

The Docker setup now includes persistent storage for all critical data components:
- MySQL database with optimization settings
- Redis cache with appendonly persistence
- Application files and logs
- Automated backup system
- Proper volume management

## Volume Configuration

### Database Volumes
- `mysql_data`: MySQL database files (`/var/lib/mysql`)
- `mysql_logs`: MySQL log files (`/var/log/mysql`)
- `redis_data`: Redis persistent data (`/data`)
- `redis_logs`: Redis log files (`/var/log/redis`)

### Application Volumes
- `app_storage`: Laravel storage directory (`/var/www/storage`)
- `app_logs`: Application log files (`/var/www/storage/logs`)
- `app_cache`: Bootstrap cache files (`/var/www/bootstrap/cache`)

### File Storage Volumes
- `category_images`: Category image files
- `category_thumbnails`: Category thumbnail files
- `category_backups`: Category backup files
- `invoice_files`: Invoice PDF and related files

### System Volumes
- `backup_data`: Automated backup storage
- `nginx_logs`: Nginx access and error logs

## Persistence Features

### MySQL Optimization
- Buffer pool size: 256MB
- Log file size: 64MB
- Query cache: 32MB
- Max connections: 100
- Slow query logging enabled
- Binary logging for replication

### Redis Configuration
- Appendonly persistence enabled
- Memory limit: 256MB
- LRU eviction policy
- Automatic snapshots (RDB)
- Save points: 900s/1change, 300s/10changes, 60s/10000changes

### Backup System
- Automated daily backups at 2 AM
- 30-day retention policy
- MySQL dump with compression
- Redis RDB backup
- Application files backup
- Backup integrity verification
- Backup manifest generation

## Scripts

### Backup Scripts
- `docker/scripts/backup.sh`: Automated backup script
- `docker/scripts/restore.sh`: Restore from backup script
- `docker/scripts/test-persistence.sh`: Data persistence testing
- `docker/scripts/init-volumes.sh`: Volume initialization

### Usage

#### Initialize Volumes
```bash
# On Linux/Mac
bash docker/scripts/init-volumes.sh

# On Windows (PowerShell)
# Volumes are automatically created when containers start
```

#### Test Data Persistence
```bash
# Test that data persists across container restarts
bash docker/scripts/test-persistence.sh
```

#### Manual Backup
```bash
# Run backup manually
docker-compose exec backup /var/www/docker/scripts/backup.sh
```

#### Restore from Backup
```bash
# List available backups
docker-compose exec backup ls -la /var/www/backups/

# Restore from specific backup
docker-compose exec backup /var/www/docker/scripts/restore.sh TIMESTAMP
```

## Container Restart Policies

All containers are configured with `restart: unless-stopped` to ensure:
- Automatic restart after system reboot
- Recovery from container crashes
- Maintained service availability

## Health Checks

### MySQL Health Check
- Command: `mysqladmin ping`
- Interval: 30 seconds
- Timeout: 10 seconds
- Retries: 5

### Redis Health Check
- Command: `redis-cli ping`
- Interval: 30 seconds
- Timeout: 10 seconds
- Retries: 5

### Application Health Check
- Command: `php artisan tinker --execute=echo 'healthy';`
- Interval: 30 seconds
- Timeout: 10 seconds
- Retries: 3

## Volume Locations

All persistent data is stored in `./docker/volumes/` with the following structure:

```
docker/volumes/
├── mysql_data/          # MySQL database files
├── mysql_logs/          # MySQL log files
├── redis_data/          # Redis persistent data
├── redis_logs/          # Redis log files
├── app_storage/         # Laravel storage
├── app_logs/            # Application logs
├── app_cache/           # Bootstrap cache
├── category_images/     # Category images
├── category_thumbnails/ # Category thumbnails
├── category_backups/    # Category backups
├── invoice_files/       # Invoice files
├── backups/             # System backups
└── nginx_logs/          # Nginx logs
```

## Environment Configuration

The `.env` file has been updated to use Redis for:
- Cache driver: `CACHE_DRIVER=redis`
- Session driver: `SESSION_DRIVER=redis`
- Queue driver: `QUEUE_CONNECTION=redis`

## Verification

To verify that persistence is working correctly:

1. Start the containers: `docker-compose up -d`
2. Create some test data in the application
3. Restart containers: `docker-compose restart`
4. Verify data is still present
5. Run the persistence test: `bash docker/scripts/test-persistence.sh`

## Troubleshooting

### Volume Permission Issues
On Linux/Mac systems, you may need to set proper permissions:
```bash
sudo chown -R 999:999 docker/volumes/mysql_data docker/volumes/mysql_logs
sudo chown -R 999:999 docker/volumes/redis_data docker/volumes/redis_logs
sudo chown -R 33:33 docker/volumes/app_storage docker/volumes/app_logs
```

### Backup Issues
Check backup logs:
```bash
docker-compose logs backup
```

### Volume Mount Issues
Ensure volume directories exist:
```bash
ls -la docker/volumes/
```

## Security Considerations

- Database volumes are mounted read-only in backup container
- Backup retention prevents unlimited disk usage
- Log rotation should be configured for production
- Regular backup testing is recommended

## Performance Impact

The persistence configuration includes optimizations to minimize performance impact:
- MySQL buffer pool optimization
- Redis memory limits and eviction policies
- Efficient volume mounting
- Optimized backup scheduling (off-peak hours)

This setup ensures complete data persistence while maintaining optimal performance for the jewelry platform.