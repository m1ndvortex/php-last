# Image Storage Setup for Category Management

## Overview

The Category Management system requires proper image storage configuration to handle category images, thumbnails, and optimization. This guide covers deployment setup for both Docker and traditional server environments.

## Docker Environment Setup

### 1. Docker Compose Configuration

Update your `docker-compose.yml` to include proper volume mounts for image storage:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/app/Dockerfile
    volumes:
      # Category image storage
      - ./storage/app/public/categories:/var/www/html/storage/app/public/categories
      - ./storage/app/public/thumbnails:/var/www/html/storage/app/public/thumbnails
      # Ensure proper permissions
      - ./storage/logs:/var/www/html/storage/logs
    environment:
      - FILESYSTEM_DISK=public
      - CATEGORY_IMAGE_DISK=public
    depends_on:
      - mysql
      - nginx

  nginx:
    build:
      context: docker/nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      # Serve static files directly
      - ./storage/app/public:/var/www/html/storage/app/public:ro
      # Category images specifically
      - ./storage/app/public/categories:/var/www/html/storage/app/public/categories:ro
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```

### 2. Nginx Configuration

Create or update `docker/nginx/sites/default.conf`:

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Category image serving
    location ~* ^/storage/categories/.*\.(jpg|jpeg|png|gif|webp|svg)$ {
        root /var/www/html/storage/app/public;
        try_files $uri =404;
        
        # Cache images for 30 days
        expires 30d;
        add_header Cache-Control "public, no-transform";
        
        # Security: prevent execution of scripts
        location ~ \.(php|pl|py|jsp|asp|sh|cgi)$ {
            return 403;
        }
    }

    # General storage serving
    location ~* ^/storage/.*\.(jpg|jpeg|png|gif|webp|svg|pdf|doc|docx)$ {
        root /var/www/html/storage/app/public;
        try_files $uri =404;
        expires 7d;
        add_header Cache-Control "public, no-transform";
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Increase upload limits for images
        client_max_body_size 10M;
        fastcgi_read_timeout 300;
    }

    # Frontend routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
}
```

### 3. Application Dockerfile Updates

Update `docker/app/Dockerfile` to ensure proper image processing libraries:

```dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions for image processing
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage
RUN chmod -R 775 /var/www/html/storage

# Create category image directories
RUN mkdir -p /var/www/html/storage/app/public/categories
RUN mkdir -p /var/www/html/storage/app/public/thumbnails
RUN chown -R www-data:www-data /var/www/html/storage/app/public
RUN chmod -R 775 /var/www/html/storage/app/public

EXPOSE 9000
CMD ["php-fpm"]
```

## Traditional Server Setup

### 1. Directory Structure

Create the necessary directory structure:

```bash
# Create storage directories
mkdir -p storage/app/public/categories
mkdir -p storage/app/public/thumbnails
mkdir -p storage/logs

# Set proper permissions
chown -R www-data:www-data storage/
chmod -R 775 storage/

# Create symbolic link for public access
php artisan storage:link
```

### 2. Web Server Configuration

#### Apache Configuration

Add to your virtual host or `.htaccess`:

```apache
# Category image serving
<LocationMatch "^/storage/categories/.*\.(jpg|jpeg|png|gif|webp|svg)$">
    # Cache for 30 days
    ExpiresActive On
    ExpiresDefault "access plus 30 days"
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    
    # Prevent script execution
    <FilesMatch "\.(php|pl|py|jsp|asp|sh|cgi)$">
        Require all denied
    </FilesMatch>
</LocationMatch>

# Upload size limits
LimitRequestBody 10485760  # 10MB
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/public;

    # Category images
    location ~* ^/storage/categories/.*\.(jpg|jpeg|png|gif|webp|svg)$ {
        root /var/www/html/storage/app/public;
        expires 30d;
        add_header Cache-Control "public, no-transform";
        
        # Security
        location ~ \.(php|pl|py|jsp|asp|sh|cgi)$ {
            return 403;
        }
    }

    # PHP configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        client_max_body_size 10M;
    }
}
```

## Environment Configuration

### 1. Laravel Configuration

Update your `.env` file:

```env
# Filesystem configuration
FILESYSTEM_DISK=public
CATEGORY_IMAGE_DISK=public

# Image processing
IMAGE_DRIVER=gd
IMAGE_QUALITY=85
IMAGE_MAX_WIDTH=800
IMAGE_MAX_HEIGHT=800
THUMBNAIL_WIDTH=150
THUMBNAIL_HEIGHT=150

# Upload limits
UPLOAD_MAX_FILESIZE=2M
POST_MAX_SIZE=10M

# Cache configuration
CACHE_DRIVER=file
SESSION_DRIVER=file
```

### 2. Filesystem Configuration

Update `config/filesystems.php`:

```php
<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'categories' => [
            'driver' => 'local',
            'root' => storage_path('app/public/categories'),
            'url' => env('APP_URL').'/storage/categories',
            'visibility' => 'public',
            'throw' => false,
        ],

        // For cloud storage (optional)
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
```

## Image Processing Setup

### 1. PHP Extensions

Ensure required PHP extensions are installed:

```bash
# Ubuntu/Debian
sudo apt-get install php8.2-gd php8.2-imagick

# CentOS/RHEL
sudo yum install php-gd php-imagick

# Or using Docker
RUN docker-php-ext-install gd
```

### 2. Image Processing Service

The `CategoryImageService` handles image processing:

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class CategoryImageService
{
    private const MAX_WIDTH = 800;
    private const MAX_HEIGHT = 800;
    private const THUMBNAIL_SIZE = 150;
    private const QUALITY = 85;

    public function processAndStore(UploadedFile $file, string $directory = 'categories'): string
    {
        // Generate unique filename
        $filename = uniqid() . '.webp';
        $path = $directory . '/' . $filename;

        // Process main image
        $image = Image::make($file)
            ->resize(self::MAX_WIDTH, self::MAX_HEIGHT, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('webp', self::QUALITY);

        // Store main image
        Storage::disk('public')->put($path, $image);

        // Generate thumbnail
        $thumbnailPath = $directory . '/thumbnails/' . $filename;
        $thumbnail = Image::make($file)
            ->fit(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE)
            ->encode('webp', self::QUALITY);

        Storage::disk('public')->put($thumbnailPath, $thumbnail);

        return $path;
    }
}
```

## Security Configuration

### 1. File Upload Security

```php
// In CategoryImageUploadRequest
public function rules(): array
{
    return [
        'image' => [
            'required',
            'image',
            'mimes:jpeg,png,jpg,webp',
            'max:2048', // 2MB
            'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
        ],
    ];
}
```

### 2. Security Headers

Add to your web server configuration:

```nginx
# Prevent hotlinking
location ~* ^/storage/categories/.*\.(jpg|jpeg|png|gif|webp)$ {
    valid_referers none blocked server_names *.yourdomain.com;
    if ($invalid_referer) {
        return 403;
    }
}

# Content Security Policy
add_header Content-Security-Policy "img-src 'self' data: https:";
```

## Backup and Maintenance

### 1. Backup Script

Create `scripts/backup-images.sh`:

```bash
#!/bin/bash

# Configuration
BACKUP_DIR="/backups/category-images"
SOURCE_DIR="/var/www/html/storage/app/public/categories"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Create compressed backup
tar -czf "$BACKUP_DIR/categories_$DATE.tar.gz" -C "$SOURCE_DIR" .

# Keep only last 30 days of backups
find "$BACKUP_DIR" -name "categories_*.tar.gz" -mtime +30 -delete

echo "Backup completed: categories_$DATE.tar.gz"
```

### 2. Cleanup Script

Create `scripts/cleanup-images.sh`:

```bash
#!/bin/bash

# Remove orphaned images (not referenced in database)
php artisan category:cleanup-images

# Optimize existing images
php artisan category:optimize-images

# Generate missing thumbnails
php artisan category:generate-thumbnails
```

### 3. Artisan Commands

Create maintenance commands:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class CategoryImageCleanup extends Command
{
    protected $signature = 'category:cleanup-images';
    protected $description = 'Remove orphaned category images';

    public function handle()
    {
        $disk = Storage::disk('public');
        $categoryImages = $disk->files('categories');
        $referencedImages = Category::whereNotNull('image_path')
            ->pluck('image_path')
            ->toArray();

        $orphanedImages = array_diff($categoryImages, $referencedImages);

        foreach ($orphanedImages as $image) {
            $disk->delete($image);
            $this->info("Deleted orphaned image: $image");
        }

        $this->info("Cleanup completed. Removed " . count($orphanedImages) . " orphaned images.");
    }
}
```

## Monitoring and Troubleshooting

### 1. Health Check Script

```bash
#!/bin/bash

echo "=== Category Image Storage Health Check ==="

# Check directory permissions
echo "Checking directory permissions..."
ls -la storage/app/public/categories/

# Check disk space
echo "Checking disk space..."
df -h storage/

# Check web server access
echo "Checking web server access..."
curl -I http://localhost/storage/categories/test.jpg

# Check image processing
echo "Checking image processing capabilities..."
php -m | grep -E "(gd|imagick)"

echo "Health check completed."
```

### 2. Common Issues and Solutions

#### Issue: Images not displaying

**Solution:**
```bash
# Check symbolic link
ls -la public/storage

# Recreate if missing
php artisan storage:link

# Check permissions
chmod -R 775 storage/app/public/categories
chown -R www-data:www-data storage/app/public/categories
```

#### Issue: Upload fails with 413 error

**Solution:**
```nginx
# Increase nginx upload limits
client_max_body_size 10M;
```

```php
# Check PHP limits
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
```

#### Issue: Images corrupted or not processing

**Solution:**
```bash
# Check GD extension
php -m | grep gd

# Install if missing
sudo apt-get install php8.2-gd

# Restart web server
sudo systemctl restart nginx php8.2-fpm
```

## Performance Optimization

### 1. CDN Integration

For high-traffic sites, consider CDN integration:

```php
// In config/filesystems.php
'categories_cdn' => [
    'driver' => 's3',
    'key' => env('CDN_ACCESS_KEY_ID'),
    'secret' => env('CDN_SECRET_ACCESS_KEY'),
    'region' => env('CDN_DEFAULT_REGION'),
    'bucket' => env('CDN_BUCKET'),
    'url' => env('CDN_URL'),
],
```

### 2. Image Optimization

```bash
# Install optimization tools
sudo apt-get install jpegoptim optipng pngquant

# Optimize existing images
find storage/app/public/categories -name "*.jpg" -exec jpegoptim --max=85 {} \;
find storage/app/public/categories -name "*.png" -exec optipng -o2 {} \;
```

### 3. Caching Strategy

```nginx
# Browser caching
location ~* \.(jpg|jpeg|png|gif|webp)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# Gzip compression
gzip_types image/svg+xml;
```

This comprehensive deployment guide ensures proper image storage setup for the category management system across different environments.