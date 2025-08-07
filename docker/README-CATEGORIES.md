# Category Management Docker Integration

This document describes the Docker integration for the jewelry category management system, including setup, configuration, testing, and maintenance procedures.

## Overview

The category management system is fully integrated with Docker, providing:
- Persistent storage for category images and thumbnails
- Automated backup and restore functionality
- Comprehensive testing suite
- Production-ready configuration
- Cross-container file sharing

## Architecture

### Docker Services

The category management system integrates with the following Docker services:

- **app**: Laravel application container with category services
- **nginx**: Web server with optimized category image serving
- **mysql**: Database with category tables and relationships
- **category_init**: One-time initialization service

### Docker Volumes

```yaml
volumes:
  category_images:        # Main category images storage
  category_thumbnails:    # Optimized thumbnails
  category_backups:       # Backup storage
```

### Volume Mounts

```yaml
# Application container
- category_images:/var/www/storage/app/public/categories
- category_thumbnails:/var/www/storage/app/public/categories/thumbnails
- category_backups:/var/www/storage/backups/categories

# Nginx container
- category_images:/var/www/storage/app/public/categories
- category_thumbnails:/var/www/storage/app/public/categories/thumbnails
```

## Setup and Configuration

### Initial Setup

1. **Start the Docker environment:**
   ```bash
   make up
   ```

2. **Initialize the category system:**
   ```bash
   make init-categories
   ```

3. **Verify the installation:**
   ```bash
   make test-categories
   ```

### Environment Variables

The following environment variables are configured in docker-compose.yml:

```yaml
environment:
  - CATEGORY_IMAGE_MAX_SIZE=5120      # Max upload size in KB
  - CATEGORY_IMAGE_QUALITY=85         # JPEG quality (1-100)
  - CATEGORY_THUMBNAIL_SIZE=150       # Thumbnail size in pixels
  - GD_EXTENSION_ENABLED=1            # Enable GD extension
  - CATEGORY_BACKUP_RETENTION_DAYS=30 # Backup retention period
```

### Nginx Configuration

Category images are served directly by Nginx with optimized settings:

```nginx
# Direct image serving
location /storage/categories/ {
    alias /var/www/storage/app/public/categories/;
    expires 1y;
    add_header Cache-Control "public, immutable";
    
    # Security headers
    add_header X-Content-Type-Options "nosniff";
    add_header X-Frame-Options "DENY";
    
    # GZIP compression
    gzip on;
    gzip_types image/jpeg image/png image/webp;
}

# Upload endpoint optimization
location /api/categories/*/image {
    client_max_body_size 10M;
    client_body_timeout 120s;
    proxy_read_timeout 120s;
}
```

## Testing

### Comprehensive Test Suite

Run all category management tests:
```bash
make test-categories
```

### Individual Test Components

1. **Environment Validation:**
   ```bash
   make test-category-env
   ```

2. **Database Migration Tests:**
   ```bash
   make test-category-migrations
   ```

3. **File Permission Tests:**
   ```bash
   make test-category-permissions
   ```

### Test Coverage

The test suite covers:
- ✅ Docker volume mounts and permissions
- ✅ Directory structure and ownership
- ✅ PHP GD extension and image processing
- ✅ Database connectivity and migrations
- ✅ Category services and models
- ✅ Nginx configuration and image serving
- ✅ Backup and restore functionality
- ✅ Cross-container file access
- ✅ Environment variables
- ✅ Storage symbolic links

## Backup and Restore

### Creating Backups

**Automatic backup:**
```bash
make backup-categories
```

**Manual backup with custom retention:**
```bash
docker-compose exec app bash -c "
CATEGORY_BACKUP_RETENTION_DAYS=60 \
/var/www/docker/scripts/backup-categories.sh
"
```

### Restoring from Backup

**List available backups:**
```bash
make restore-categories
```

**Restore specific backup:**
```bash
make restore-categories TIMESTAMP=20250107_143022
```

**Database-only restore:**
```bash
docker-compose exec app bash /var/www/docker/scripts/restore-categories.sh 20250107_143022 --database-only
```

### Backup Contents

Each backup includes:
- **Images**: Compressed tar.gz of all category images
- **Database**: JSON export of category and image records
- **Manifest**: Backup metadata and verification info
- **Log**: Detailed backup process log

## File Structure

```
docker/
├── scripts/
│   ├── backup-categories.sh           # Enhanced backup script
│   ├── restore-categories.sh          # Enhanced restore script
│   ├── init-categories.sh             # System initialization
│   ├── validate-category-environment.sh # Environment validation
│   ├── test-migrations.sh             # Migration testing
│   ├── test-file-permissions.sh       # Permission testing
│   ├── test-category-docker.sh        # Basic integration test
│   └── run-all-category-tests.sh      # Comprehensive test runner
├── nginx/
│   ├── nginx.conf                     # Main nginx config
│   └── sites/default.conf             # Site-specific config
└── README-CATEGORIES.md               # This documentation
```

## Storage Paths

### Inside Containers

```
/var/www/storage/app/public/categories/          # Main images
/var/www/storage/app/public/categories/thumbnails/ # Thumbnails
/var/www/storage/backups/categories/             # Backups
/var/www/public/storage/categories/              # Web-accessible path
```

### Docker Volumes

```bash
# Inspect volume locations
docker volume inspect category_images
docker volume inspect category_thumbnails
docker volume inspect category_backups
```

## Troubleshooting

### Common Issues

1. **Permission Denied Errors:**
   ```bash
   # Fix permissions
   docker-compose exec app chown -R www-data:www-data /var/www/storage/app/public/categories
   docker-compose exec app chmod -R 755 /var/www/storage/app/public/categories
   ```

2. **Missing Storage Link:**
   ```bash
   # Recreate storage link
   docker-compose exec app php artisan storage:link
   ```

3. **Image Processing Errors:**
   ```bash
   # Check GD extension
   docker-compose exec app php -m | grep gd
   
   # Test image processing
   docker-compose exec app php -r "
   \$info = gd_info();
   print_r(\$info);
   "
   ```

4. **Volume Mount Issues:**
   ```bash
   # Recreate volumes
   docker-compose down
   docker volume rm category_images category_thumbnails category_backups
   docker-compose up -d
   make init-categories
   ```

### Log Files

- **Application logs**: `/var/www/storage/logs/laravel.log`
- **Nginx logs**: `/var/log/nginx/access.log`, `/var/log/nginx/error.log`
- **Backup logs**: `/var/www/storage/backups/categories/backup_*.log`
- **Test reports**: `/var/www/storage/backups/categories/docker_integration_test_report_*.txt`

### Health Checks

The Docker services include health checks:

```yaml
# Application health check
healthcheck:
  test: ["CMD", "php", "artisan", "tinker", "--execute=echo 'healthy';"]
  interval: 30s
  timeout: 10s
  retries: 3

# MySQL health check
healthcheck:
  test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
  interval: 30s
  timeout: 10s
  retries: 5
```

## Performance Optimization

### Image Serving

- **Direct Nginx serving**: Images served directly by Nginx, bypassing PHP
- **Compression**: GZIP compression for all image types
- **Caching**: Long-term browser caching with immutable headers
- **CDN Ready**: Headers configured for CDN integration

### Upload Optimization

- **Chunked uploads**: Support for large file uploads
- **Timeout handling**: Extended timeouts for image processing
- **Memory management**: Optimized PHP memory settings

### Database Optimization

- **Indexed relationships**: Proper indexing on foreign keys
- **Connection pooling**: Efficient database connection management
- **Query optimization**: Eager loading for category hierarchies

## Security

### File Security

- **Type validation**: Only image files allowed
- **Size limits**: Configurable upload size limits
- **Path traversal protection**: Secure file path handling
- **Access control**: Proper file permissions and ownership

### Web Security

- **Content-Type headers**: Prevent MIME type confusion
- **Frame protection**: X-Frame-Options headers
- **CORS configuration**: Controlled cross-origin access
- **Input sanitization**: All uploads validated and sanitized

## Monitoring

### Metrics

Monitor these key metrics:
- Image upload success rate
- Storage volume usage
- Backup completion status
- Image serving response times
- Database query performance

### Alerts

Set up alerts for:
- Storage volume > 80% full
- Failed backup operations
- Image processing errors
- Database connection failures
- High error rates in logs

## Production Deployment

### Pre-deployment Checklist

- [ ] Run comprehensive tests: `make test-categories`
- [ ] Verify backup system: `make backup-categories`
- [ ] Check environment variables
- [ ] Validate SSL certificates for image serving
- [ ] Configure monitoring and alerts
- [ ] Set up log rotation
- [ ] Test disaster recovery procedures

### Production Configuration

```yaml
# Production docker-compose.yml additions
services:
  app:
    environment:
      - CATEGORY_IMAGE_MAX_SIZE=10240  # 10MB for production
      - CATEGORY_BACKUP_RETENTION_DAYS=90
      - BACKUP_WEBHOOK_URL=https://monitoring.example.com/webhook
    
  nginx:
    # Add SSL configuration
    # Configure rate limiting
    # Set up monitoring endpoints
```

## Support

For issues related to category management Docker integration:

1. Check the troubleshooting section above
2. Run the diagnostic tests: `make test-categories`
3. Review log files in `/var/www/storage/logs/`
4. Check Docker container status: `docker-compose ps`
5. Verify volume mounts: `docker volume ls`

## Version History

- **v1.0**: Initial Docker integration
- **v1.1**: Added comprehensive testing suite
- **v1.2**: Enhanced backup and restore functionality
- **v1.3**: Added health checks and monitoring
- **v1.4**: Production optimization and security hardening