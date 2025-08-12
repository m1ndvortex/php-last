#!/bin/bash

# Ubuntu VPS Deployment Script for Jewelry Platform
# This script automates the deployment process on Ubuntu 20.04/22.04 LTS

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="jewelry-platform"
APP_DIR="/var/www/$APP_NAME"
BACKUP_DIR="/var/backups/$APP_NAME"
LOG_FILE="/var/log/${APP_NAME}-deployment.log"

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

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root. Please run as a regular user with sudo privileges."
    fi
}

# Check Ubuntu version
check_ubuntu_version() {
    if ! grep -q "Ubuntu" /etc/os-release; then
        error "This script is designed for Ubuntu. Detected: $(cat /etc/os-release | grep PRETTY_NAME)"
    fi
    
    VERSION=$(lsb_release -rs)
    if [[ "$VERSION" != "20.04" && "$VERSION" != "22.04" ]]; then
        warning "This script is tested on Ubuntu 20.04 and 22.04. You're running $VERSION"
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

# Collect deployment information
collect_info() {
    log "Collecting deployment information..."
    
    # Domain name
    read -p "Enter your domain name (e.g., example.com): " DOMAIN
    if [[ -z "$DOMAIN" ]]; then
        error "Domain name is required"
    fi
    
    # Email for SSL certificate
    read -p "Enter email for SSL certificate: " SSL_EMAIL
    if [[ -z "$SSL_EMAIL" ]]; then
        error "Email is required for SSL certificate"
    fi
    
    # Database password
    read -s -p "Enter database password: " DB_PASSWORD
    echo
    if [[ -z "$DB_PASSWORD" ]]; then
        error "Database password is required"
    fi
    
    # Confirm installation
    echo
    info "Deployment Configuration:"
    info "Domain: $DOMAIN"
    info "SSL Email: $SSL_EMAIL"
    info "App Directory: $APP_DIR"
    echo
    read -p "Proceed with installation? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
}

# Update system
update_system() {
    log "Updating system packages..."
    sudo apt update && sudo apt upgrade -y
    sudo apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release
}

# Install Docker
install_docker() {
    log "Installing Docker..."
    
    # Remove old versions
    sudo apt remove -y docker docker-engine docker.io containerd runc 2>/dev/null || true
    
    # Add Docker's official GPG key
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    
    # Add Docker repository
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    
    # Install Docker
    sudo apt update
    sudo apt install -y docker-ce docker-ce-cli containerd.io
    
    # Install Docker Compose
    DOCKER_COMPOSE_VERSION=$(curl -s https://api.github.com/repos/docker/compose/releases/latest | grep 'tag_name' | cut -d\" -f4)
    sudo curl -L "https://github.com/docker/compose/releases/download/${DOCKER_COMPOSE_VERSION}/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    
    # Add user to docker group
    sudo usermod -aG docker $USER
    
    # Start and enable Docker
    sudo systemctl start docker
    sudo systemctl enable docker
    
    info "Docker installed successfully"
}

# Install Nginx
install_nginx() {
    log "Installing and configuring Nginx..."
    
    sudo apt install -y nginx
    sudo systemctl start nginx
    sudo systemctl enable nginx
    
    # Configure firewall
    sudo ufw allow 'Nginx Full'
    sudo ufw allow OpenSSH
    sudo ufw --force enable
    
    info "Nginx installed and configured"
}

# Install additional tools
install_tools() {
    log "Installing additional tools..."
    
    # Monitoring tools
    sudo apt install -y htop iotop nethogs fail2ban
    
    # Certbot for SSL
    sudo apt install -y certbot python3-certbot-nginx
    
    info "Additional tools installed"
}

# Clone application
clone_application() {
    log "Cloning application..."
    
    if [[ -d "$APP_DIR" ]]; then
        warning "Application directory already exists. Backing up..."
        sudo mv "$APP_DIR" "${APP_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
    fi
    
    # Clone repository (replace with actual repository URL)
    git clone https://github.com/your-repo/jewelry-platform.git "$APP_DIR"
    
    # Set permissions
    sudo chown -R $USER:$USER "$APP_DIR"
    chmod -R 755 "$APP_DIR"
    
    info "Application cloned successfully"
}

# Configure environment
configure_environment() {
    log "Configuring environment..."
    
    cd "$APP_DIR"
    
    # Copy environment file
    cp .env.example .env
    
    # Generate application key
    APP_KEY=$(openssl rand -base64 32)
    
    # Configure .env file
    sed -i "s|APP_NAME=.*|APP_NAME=\"Jewelry Platform\"|" .env
    sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
    sed -i "s|APP_KEY=.*|APP_KEY=base64:$APP_KEY|" .env
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env
    sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|" .env
    
    sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
    sed -i "s|DB_HOST=.*|DB_HOST=mysql|" .env
    sed -i "s|DB_PORT=.*|DB_PORT=3306|" .env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=jewelry_platform|" .env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=jewelry_user|" .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env
    
    sed -i "s|FRONTEND_URL=.*|FRONTEND_URL=https://$DOMAIN|" .env
    sed -i "s|SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=$DOMAIN|" .env
    
    info "Environment configured"
}

# Configure Nginx
configure_nginx() {
    log "Configuring Nginx reverse proxy..."
    
    # Create Nginx configuration
    sudo tee /etc/nginx/sites-available/$APP_NAME > /dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name $DOMAIN www.$DOMAIN;

    # SSL Configuration (will be updated by Certbot)
    ssl_certificate /etc/ssl/certs/ssl-cert-snakeoil.pem;
    ssl_certificate_key /etc/ssl/private/ssl-cert-snakeoil.key;
    
    # SSL Security
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
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
    }

    # API Routes
    location /api {
        proxy_pass http://localhost:8000;
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
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

    client_max_body_size 50M;
}
EOF

    # Enable site
    sudo ln -sf /etc/nginx/sites-available/$APP_NAME /etc/nginx/sites-enabled/
    sudo rm -f /etc/nginx/sites-enabled/default
    
    # Test configuration
    sudo nginx -t
    sudo systemctl reload nginx
    
    info "Nginx configured successfully"
}

# Setup SSL
setup_ssl() {
    log "Setting up SSL certificate..."
    
    # Obtain SSL certificate
    sudo certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" --email "$SSL_EMAIL" --agree-tos --non-interactive
    
    # Test automatic renewal
    sudo certbot renew --dry-run
    
    info "SSL certificate configured successfully"
}

# Deploy application
deploy_application() {
    log "Deploying application..."
    
    cd "$APP_DIR"
    
    # Make scripts executable
    chmod +x scripts/*.sh
    
    # Run production deployment
    ./scripts/deploy-production.sh
    
    info "Application deployed successfully"
}

# Setup monitoring
setup_monitoring() {
    log "Setting up monitoring and logging..."
    
    # Create log rotation configuration
    sudo tee /etc/logrotate.d/$APP_NAME > /dev/null <<EOF
$APP_DIR/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        docker-compose -f $APP_DIR/docker-compose.prod.yml restart app
    endscript
}
EOF

    info "Monitoring and logging configured"
}

# Setup backups
setup_backups() {
    log "Setting up automated backups..."
    
    # Create backup directory
    sudo mkdir -p "$BACKUP_DIR"
    sudo chown $USER:$USER "$BACKUP_DIR"
    
    # Add cron jobs
    (crontab -l 2>/dev/null; echo "0 2 * * * $APP_DIR/scripts/backup-database.sh") | crontab -
    (crontab -l 2>/dev/null; echo "0 3 * * 0 $APP_DIR/scripts/backup-full.sh") | crontab -
    (crontab -l 2>/dev/null; echo "0 0,12 * * * /usr/bin/certbot renew --quiet") | crontab -
    
    info "Automated backups configured"
}

# Optimize system
optimize_system() {
    log "Optimizing system performance..."
    
    # Configure system limits
    sudo tee -a /etc/security/limits.conf > /dev/null <<EOF
* soft nofile 65536
* hard nofile 65536
* soft nproc 32768
* hard nproc 32768
EOF

    # Configure sysctl
    sudo tee -a /etc/sysctl.conf > /dev/null <<EOF
# Network optimizations
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.ipv4.tcp_rmem = 4096 65536 16777216
net.ipv4.tcp_wmem = 4096 65536 16777216
net.ipv4.tcp_congestion_control = bbr

# File system optimizations
fs.file-max = 2097152
vm.swappiness = 10
EOF

    sudo sysctl -p
    
    # Run database optimization
    cd "$APP_DIR"
    ./scripts/optimize-database.sh
    
    info "System optimization completed"
}

# Verify deployment
verify_deployment() {
    log "Verifying deployment..."
    
    # Check Docker containers
    cd "$APP_DIR"
    docker-compose -f docker-compose.prod.yml ps
    
    # Check Nginx
    sudo systemctl status nginx --no-pager
    
    # Check SSL certificate
    sudo certbot certificates
    
    # Test endpoints
    sleep 10
    if curl -f -s "https://$DOMAIN/api/health" > /dev/null; then
        info "API endpoint is responding"
    else
        warning "API endpoint is not responding yet"
    fi
    
    if curl -f -s "https://$DOMAIN" > /dev/null; then
        info "Frontend is responding"
    else
        warning "Frontend is not responding yet"
    fi
    
    info "Deployment verification completed"
}

# Main deployment function
main() {
    log "Starting Ubuntu VPS deployment for Jewelry Platform"
    
    check_root
    check_ubuntu_version
    collect_info
    
    update_system
    install_docker
    install_nginx
    install_tools
    clone_application
    configure_environment
    configure_nginx
    setup_ssl
    deploy_application
    setup_monitoring
    setup_backups
    optimize_system
    verify_deployment
    
    log "Deployment completed successfully!"
    echo
    info "Your Jewelry Platform is now available at: https://$DOMAIN"
    info "API documentation: https://$DOMAIN/api/documentation"
    info "Application logs: $APP_DIR/storage/logs/"
    info "Deployment log: $LOG_FILE"
    echo
    warning "Please note:"
    warning "1. You may need to log out and back in for Docker group changes to take effect"
    warning "2. Configure your email settings in $APP_DIR/.env"
    warning "3. Review and customize the application settings as needed"
    warning "4. Set up regular monitoring of your application"
}

# Run main function
main "$@"