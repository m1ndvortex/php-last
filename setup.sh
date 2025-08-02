#!/bin/bash

# Bilingual Jewelry Platform Setup Script

set -e

echo "🚀 Setting up Bilingual Jewelry Platform..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✅ .env file created. Please configure it with your settings."
else
    echo "✅ .env file already exists."
fi

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p storage/app storage/logs bootstrap/cache backups
mkdir -p docker/nginx/ssl docker/nginx/letsencrypt

# Build Docker containers
echo "🔨 Building Docker containers..."
docker-compose build

# Start services
echo "🚀 Starting services..."
docker-compose up -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 10

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
docker-compose exec -T app composer install --no-dev --optimize-autoloader

# Install NPM dependencies
echo "📦 Installing NPM dependencies..."
docker-compose exec -T frontend npm install

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run database migrations
echo "🗄️ Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Clear caches
echo "🧹 Clearing caches..."
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan route:clear

echo ""
echo "🎉 Setup completed successfully!"
echo ""
echo "📍 Access points:"
echo "   - Frontend: http://localhost:3000"
echo "   - Backend API: http://localhost/api"
echo "   - Full application: http://localhost"
echo ""
echo "🔧 Useful commands:"
echo "   - View logs: docker-compose logs -f"
echo "   - Stop services: docker-compose down"
echo "   - Restart services: docker-compose restart"
echo "   - Access app shell: docker-compose exec app bash"
echo ""
echo "📚 Next steps:"
echo "   1. Configure your .env file with proper database and API credentials"
echo "   2. Set up your business information in the admin panel"
echo "   3. Configure WhatsApp and SMS API settings"
echo ""