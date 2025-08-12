#!/bin/bash

# Production Deployment Script
# This script handles the application deployment in production environment

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
LOG_FILE="$APP_DIR/storage/logs/deployment.log"

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

# Check if .env file exists
check_environment() {
    if [[ ! -f "$APP_DIR/.env" ]]; then
        error ".env file not found. Please create it from .env.example"
    fi
    
    # Check required environment variables
    source "$APP_DIR/.env"
    
    if [[ -z "$APP_KEY" ]]; then
        error "APP_KEY is not set in .env file"
    fi
    
    if [[ -z "$DB_PASSWORD" ]]; then
        error "DB_PASSWORD is not set in .env file"
    fi
    
    info "Environment configuration validated"
}

# Create necessary directories
create_directories() {
    log "Creating necessary directories..."
    
    mkdir -p "$APP_DIR/storage/logs"
    mkdir -p "$APP_DIR/storage/app/public"
    mkdir -p "$APP_DIR/storage/framework/cache"
    mkdir -p "$APP_DIR/storage/framework/sessions"
    mkdir -p "$APP_DIR/storage/framework/views"
    mkdir -p "$APP_DIR/bootstrap/cache"
    mkdir -p "$APP_DIR/backups"
    
    # Set permissions
    chmod -R 755 "$APP_DIR/storage"
    chmod -R 755 "$APP_DIR/bootstrap/cache"
    
    info "Directories created successfully"
}

# Build and start containers
deploy_containers() {
    log "Building and starting Docker containers..."
    
    cd "$APP_DIR"
    
    # Stop existing containers
    docker-compose -f docker-compose.prod.yml down || true
    
    # Build containers
    docker-compose -f docker-compose.prod.yml build --no-cache
    
    # Start containers
    docker-compose -f docker-compose.prod.yml up -d
    
    # Wait for containers to be ready
    log "Waiting for containers to be ready..."
    sleep 30
    
    # Check container status
    if ! docker-compose -f docker-compose.prod.yml ps | grep -q "Up"; then
        error "Some containers failed to start"
    fi
    
    info "Containers started successfully"
}

# Install PHP dependencies
install_php_dependencies() {
    log "Installing PHP dependencies..."
    
    cd "$APP_DIR"
    
    # Install Composer dependencies
    docker-compose -f docker-compose.prod.yml exec -T app composer install --no-dev --optimize-autoloader --no-interaction
    
    info "PHP dependencies installed"
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."
    
    cd "$APP_DIR"
    
    # Wait for database to be ready
    log "Waiting for database to be ready..."
    sleep 10
    
    # Run migrations
    docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
    
    # Seed database if needed
    if [[ "$1" == "--seed" ]]; then
        log "Seeding database..."
        docker-compose -f docker-compose.prod.yml exec -T app php artisan db:seed --force
    fi
    
    info "Database migrations completed"
}

# Install and build frontend
build_frontend() {
    log "Building frontend application..."
    
    cd "$APP_DIR/frontend"
    
    # Install Node.js dependencies
    docker-compose -f ../docker-compose.prod.yml exec -T frontend npm ci
    
    # Build for production
    docker-compose -f ../docker-compose.prod.yml exec -T frontend npm run build
    
    info "Frontend built successfully"
}

# Configure Laravel
configure_laravel() {
    log "Configuring Laravel application..."
    
    cd "$APP_DIR"
    
    # Clear and cache configuration
    docker-compose -f docker-compose.prod.yml exec -T app php artisan config:clear
    docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
    
    # Clear and cache routes
    docker-compose -f docker-compose.prod.yml exec -T app php artisan route:clear
    docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
    
    # Clear and cache views
    docker-compose -f docker-compose.prod.yml exec -T app php artisan view:clear
    docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache
    
    # Create storage link
    docker-compose -f docker-compose.prod.yml exec -T app php artisan storage:link
    
    # Clear application cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan cache:clear
    
    info "Laravel configuration completed"
}

# Setup queue workers
setup_queue_workers() {
    log "Setting up queue workers..."
    
    cd "$APP_DIR"
    
    # Start queue workers
    docker-compose -f docker-compose.prod.yml exec -d app php artisan queue:work --daemon --tries=3 --timeout=300
    
    info "Queue workers started"
}

# Setup scheduled tasks
setup_scheduler() {
    log "Setting up task scheduler..."
    
    cd "$APP_DIR"
    
    # Add Laravel scheduler to cron
    (crontab -l 2>/dev/null; echo "* * * * * cd $APP_DIR && docker-compose -f docker-compose.prod.yml exec -T app php artisan schedule:run >> /dev/null 2>&1") | crontab -
    
    info "Task scheduler configured"
}

# Optimize application
optimize_application() {
    log "Optimizing application performance..."
    
    cd "$APP_DIR"
    
    # Optimize Composer autoloader
    docker-compose -f docker-compose.prod.yml exec -T app composer dump-autoload --optimize
    
    # Cache configuration
    docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache
    
    # Optimize database
    ./scripts/optimize-database.sh
    
    info "Application optimization completed"
}

# Setup monitoring
setup_monitoring() {
    log "Setting up application monitoring..."
    
    cd "$APP_DIR"
    
    # Create monitoring script
    cat > "$APP_DIR/scripts/monitor-app.sh" << 'EOF'
#!/bin/bash

# Application monitoring script
APP_DIR="/var/www/jewelry-platform"
LOG_FILE="$APP_DIR/storage/logs/monitoring.log"

# Check container health
check_containers() {
    cd "$APP_DIR"
    if ! docker-compose -f docker-compose.prod.yml ps | grep -q "Up"; then
        echo "[$(date)] Some containers are down" >> "$LOG_FILE"
        docker-compose -f docker-compose.prod.yml up -d
    fi
}

# Check disk space
check_disk_space() {
    USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$USAGE" -gt 80 ]; then
        echo "[$(date)] Disk usage is at ${USAGE}%" >> "$LOG_FILE"
    fi
}

# Check memory usage
check_memory() {
    USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    if [ "$USAGE" -gt 80 ]; then
        echo "[$(date)] Memory usage is at ${USAGE}%" >> "$LOG_FILE"
    fi
}

# Run checks
check_containers
check_disk_space
check_memory
EOF

    chmod +x "$APP_DIR/scripts/monitor-app.sh"
    
    # Add monitoring to cron
    (crontab -l 2>/dev/null; echo "*/5 * * * * $APP_DIR/scripts/monitor-app.sh") | crontab -
    
    info "Application monitoring configured"
}

# Verify deployment
verify_deployment() {
    log "Verifying deployment..."
    
    cd "$APP_DIR"
    
    # Check container status
    docker-compose -f docker-compose.prod.yml ps
    
    # Check application health
    sleep 10
    
    # Test database connection
    if docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate:status > /dev/null 2>&1; then
        info "Database connection successful"
    else
        warning "Database connection failed"
    fi
    
    # Test API endpoint
    if docker-compose -f docker-compose.prod.yml exec -T app php artisan route:list | grep -q "api/health"; then
        info "API routes loaded successfully"
    else
        warning "API routes not loaded properly"
    fi
    
    # Check logs for errors
    if docker-compose -f docker-compose.prod.yml logs app | grep -i error | tail -5; then
        warning "Found errors in application logs"
    fi
    
    info "Deployment verification completed"
}

# Cleanup
cleanup() {
    log "Cleaning up temporary files..."
    
    cd "$APP_DIR"
    
    # Remove unused Docker images
    docker image prune -f
    
    # Clean up old logs
    find "$APP_DIR/storage/logs" -name "*.log" -mtime +30 -delete
    
    info "Cleanup completed"
}

# Main deployment function
main() {
    log "Starting production deployment..."
    
    check_environment
    create_directories
    deploy_containers
    install_php_dependencies
    run_migrations "$@"
    build_frontend
    configure_laravel
    setup_queue_workers
    setup_scheduler
    optimize_application
    setup_monitoring
    verify_deployment
    cleanup
    
    log "Production deployment completed successfully!"
    echo
    info "Application Status:"
    docker-compose -f docker-compose.prod.yml ps
    echo
    info "Next Steps:"
    info "1. Configure your domain DNS to point to this server"
    info "2. Update email settings in .env file"
    info "3. Test all application features"
    info "4. Set up regular backups"
    info "5. Monitor application logs"
}

# Run main function
main "$@"