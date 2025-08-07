@echo off
echo === Category Management Docker Integration Validation ===
echo.

echo Checking Docker Compose configuration...
docker-compose config > nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Docker Compose configuration is invalid
    pause
    exit /b 1
)
echo ✓ Docker Compose configuration is valid

echo.
echo Checking if Docker services are running...
docker-compose ps | findstr "Up" > nul
if %errorlevel% neq 0 (
    echo WARNING: Docker services may not be running
    echo Starting services...
    docker-compose up -d
    timeout /t 10 > nul
)

echo.
echo Checking Docker volumes...
docker volume ls | findstr "category_images" > nul
if %errorlevel% neq 0 (
    echo WARNING: Category volumes not found, they will be created on first run
) else (
    echo ✓ Category Docker volumes exist
)

echo.
echo Checking if application container is accessible...
docker-compose exec -T app php --version > nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Cannot access application container
    echo Please ensure Docker services are running: docker-compose up -d
    pause
    exit /b 1
)
echo ✓ Application container is accessible

echo.
echo Checking category management scripts...
docker-compose exec -T app test -f /var/www/docker/scripts/run-all-category-tests.sh
if %errorlevel% neq 0 (
    echo ERROR: Category test scripts not found
    pause
    exit /b 1
)
echo ✓ Category management scripts are present

echo.
echo Running basic category system validation...
docker-compose exec -T app php -r "
try {
    if (!extension_loaded('gd')) {
        echo 'ERROR: GD extension not loaded';
        exit(1);
    }
    
    if (!class_exists('\App\Services\CategoryService')) {
        echo 'ERROR: CategoryService not found';
        exit(1);
    }
    
    echo 'SUCCESS: Basic validation passed';
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
    exit(1);
}
"

if %errorlevel% neq 0 (
    echo Basic validation failed
    pause
    exit /b 1
)

echo.
echo === Validation Summary ===
echo ✓ Docker Compose configuration valid
echo ✓ Docker volumes configured
echo ✓ Application container accessible
echo ✓ Category scripts present
echo ✓ Basic system validation passed
echo.
echo Category Management Docker Integration is ready!
echo.
echo Next steps:
echo 1. Initialize categories: make init-categories
echo 2. Run comprehensive tests: make test-categories
echo 3. Access the application at: http://localhost
echo.
pause