# Ubuntu VPS Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying the bilingual jewelry platform on an Ubuntu VPS. The deployment includes Docker containerization, SSL/HTTPS configuration, database optimization, automated backups, monitoring, and performance optimization.

## Prerequisites

- Ubuntu 20.04 LTS or 22.04 LTS VPS
- Minimum 2GB RAM, 2 CPU cores, 20GB storage
- Root or sudo access
- Domain name pointing to your VPS IP address

## Quick Start

For automated installation, run:

```bash
wget https://raw.githubusercontent.com/your-repo/jewelry-platform/main/scripts/deploy-ubuntu.sh
chmod +x deploy-ubuntu.sh
sudo ./deploy-ubuntu.sh
```

## Manual Installation Steps

### Step 1: System Preparation

Update the system and install basic dependencies:

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release

# Install fail2ban for security
sudo apt install -y fail2ban

# Configure timezone
sudo timedatectl set-timezone UTC
```

### Step 2: Install Docker and Docker Compose

```bash
# Add Docker's official GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Add Docker repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Add current user to docker group
sudo usermod -aG docker $USER

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker
```

### Step 3: Install Nginx (Reverse Proxy)

```bash
# Install Nginx
sudo apt install -y nginx

# Start and enable Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Configure firewall
sudo ufw allow 'Nginx Full'
sudo ufw allow OpenSSH
sudo ufw --force enable
```

### Step 4: Clone and Configure Application

```bash
# Clone the repository
git clone https://github.com/your-repo/jewelry-platform.git /var/www/jewelry-platform
cd /var/www/jewelry-platform

# Copy environment file
cp .env.example .env

# Edit environment variables
sudo nano .env
```

Configure the following variables in `.env`:
```env
APP_NAME="Jewelry Platform"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=jewelry_platform
DB_USERNAME=jewelry_user
DB_PASSWORD=your-secure-password

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

FRONTEND_URL=https://yourdomain.com
SANCTUM_STATEFUL_DOMAINS=yourdomain.com

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 5: SSL/HTTPS Configuration with Let's Encrypt

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test automatic renewal
sudo certbot renew --dry-run
```

### Step 6: Configure Nginx Reverse Proxy

Create Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/jewelry-platform
```

Add the following configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    
    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security Headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # Frontend (Vue.js)
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }

    # API Routes
    location /api {
        proxy_pass http://localhost:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # API specific settings
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
        proxy_send_timeout 300;
    }

    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        proxy_pass http://localhost:3000;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # File uploads
    client_max_body_size 50M;
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/jewelry-platform /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 7: Deploy Application

```bash
# Set proper permissions
sudo chown -R $USER:$USER /var/www/jewelry-platform
chmod -R 755 /var/www/jewelry-platform

# Run deployment script
cd /var/www/jewelry-platform
./scripts/deploy-production.sh
```

### Step 8: Configure Monitoring and Logging

```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Configure log rotation
sudo nano /etc/logrotate.d/jewelry-platform
```

Add log rotation configuration:

```
/var/www/jewelry-platform/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        docker-compose -f /var/www/jewelry-platform/docker-compose.prod.yml restart app
    endscript
}
```

### Step 9: Setup Automated Backups

```bash
# Create backup directory
sudo mkdir -p /var/backups/jewelry-platform

# Set up backup cron job
sudo crontab -e
```

Add the following cron jobs:

```cron
# Daily database backup at 2 AM
0 2 * * * /var/www/jewelry-platform/scripts/backup-database.sh

# Weekly full backup at 3 AM on Sundays
0 3 * * 0 /var/www/jewelry-platform/scripts/backup-full.sh

# SSL certificate renewal check (twice daily)
0 0,12 * * * /usr/bin/certbot renew --quiet
```

### Step 10: Performance Optimization

```bash
# Run database optimization
cd /var/www/jewelry-platform
./scripts/optimize-database.sh

# Configure system limits
sudo nano /etc/security/limits.conf
```

Add the following limits:

```
* soft nofile 65536
* hard nofile 65536
* soft nproc 32768
* hard nproc 32768
```

Update sysctl settings:

```bash
sudo nano /etc/sysctl.conf
```

Add:

```
# Network optimizations
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.ipv4.tcp_rmem = 4096 65536 16777216
net.ipv4.tcp_wmem = 4096 65536 16777216
net.ipv4.tcp_congestion_control = bbr

# File system optimizations
fs.file-max = 2097152
vm.swappiness = 10
```

Apply changes:

```bash
sudo sysctl -p
```

## Post-Deployment Verification

### 1. Check Services Status

```bash
# Check Docker containers
docker-compose -f docker-compose.prod.yml ps

# Check Nginx status
sudo systemctl status nginx

# Check SSL certificate
sudo certbot certificates
```

### 2. Test Application

```bash
# Test API endpoints
curl -k https://yourdomain.com/api/health

# Test frontend
curl -k https://yourdomain.com

# Check database connection
docker-compose -f docker-compose.prod.yml exec app php artisan migrate:status
```

### 3. Performance Testing

```bash
# Run performance tests
./scripts/performance-test.sh

# Check resource usage
htop
docker stats
```

## Maintenance Tasks

### Daily Tasks
- Monitor application logs
- Check disk space usage
- Verify backup completion

### Weekly Tasks
- Review security logs
- Update system packages
- Check SSL certificate status

### Monthly Tasks
- Analyze performance metrics
- Review and rotate logs
- Update application dependencies

## Troubleshooting

### Common Issues

1. **Docker containers not starting**
   ```bash
   docker-compose -f docker-compose.prod.yml logs
   ```

2. **SSL certificate issues**
   ```bash
   sudo certbot renew --force-renewal
   ```

3. **Database connection problems**
   ```bash
   docker-compose -f docker-compose.prod.yml exec mysql mysql -u root -p
   ```

4. **High memory usage**
   ```bash
   docker stats
   free -h
   ```

### Log Locations

- Application logs: `/var/www/jewelry-platform/storage/logs/`
- Nginx logs: `/var/log/nginx/`
- Docker logs: `docker-compose logs [service]`
- System logs: `/var/log/syslog`

## Security Considerations

1. **Firewall Configuration**
   - Only allow necessary ports (80, 443, 22)
   - Use fail2ban for intrusion prevention

2. **Regular Updates**
   - Keep system packages updated
   - Update Docker images regularly
   - Monitor security advisories

3. **Access Control**
   - Use SSH keys instead of passwords
   - Implement proper user permissions
   - Regular security audits

## Support and Documentation

- Application documentation: `/docs/`
- API documentation: `https://yourdomain.com/api/documentation`
- Troubleshooting guide: `/docs/troubleshooting/`

For additional support, refer to the project repository or contact the development team.