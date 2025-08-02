@echo off
echo 🚀 Setting up Bilingual Jewelry Platform...

REM Check if Docker is installed
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not installed. Please install Docker first.
    exit /b 1
)

REM Check if Docker Compose is installed
docker-compose --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker Compose is not installed. Please install Docker Compose first.
    exit /b 1
)

REM Create .env file if it doesn't exist
if not exist .env (
    echo 📝 Creating .env file from .env.example...
    copy .env.example .env
    echo ✅ .env file created. Please configure it with your settings.
) else (
    echo ✅ .env file already exists.
)

REM Create necessary directories
echo 📁 Creating necessary directories...
if not exist storage\app mkdir storage\app
if not exist storage\logs mkdir storage\logs
if not exist bootstrap\cache mkdir bootstrap\cache
if not exist backups mkdir backups
if not exist docker\nginx\ssl mkdir docker\nginx\ssl
if not exist docker\nginx\letsencrypt mkdir docker\nginx\letsencrypt

REM Build Docker containers
echo 🔨 Building Docker containers...
docker-compose build

REM Start services
echo 🚀 Starting services...
docker-compose up -d

REM Wait for services to be ready
echo ⏳ Waiting for services to be ready...
timeout /t 10 /nobreak >nul

REM Install Composer dependencies
echo 📦 Installing Composer dependencies...
docker-compose exec -T app composer install --no-dev --optimize-autoloader

REM Install NPM dependencies
echo 📦 Installing NPM dependencies...
docker-compose exec -T frontend npm install

REM Generate application key
echo 🔑 Generating application key...
docker-compose exec -T app php artisan key:generate

REM Run database migrations
echo 🗄️ Running database migrations...
docker-compose exec -T app php artisan migrate --force

REM Clear caches
echo 🧹 Clearing caches...
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan route:clear

echo.
echo 🎉 Setup completed successfully!
echo.
echo 📍 Access points:
echo    - Frontend: http://localhost:3000
echo    - Backend API: http://localhost/api
echo    - Full application: http://localhost
echo.
echo 🔧 Useful commands:
echo    - View logs: docker-compose logs -f
echo    - Stop services: docker-compose down
echo    - Restart services: docker-compose restart
echo    - Access app shell: docker-compose exec app bash
echo.
echo 📚 Next steps:
echo    1. Configure your .env file with proper database and API credentials
echo    2. Set up your business information in the admin panel
echo    3. Configure WhatsApp and SMS API settings
echo.
pause