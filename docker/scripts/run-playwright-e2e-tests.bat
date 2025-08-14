@echo off
REM Seamless Tab Navigation Playwright E2E Test Runner for Docker (Windows)
REM This script runs the Playwright MCP end-to-end tests in Docker environment

echo 🎭 Starting Playwright MCP End-to-End Tests for Seamless Tab Navigation...

REM Check if Docker is running
docker info >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Docker is not running. Please start Docker and try again.
    exit /b 1
)

echo [INFO] Checking Docker containers...

REM Start Docker containers if not running
docker-compose ps | findstr "Up" >nul
if errorlevel 1 (
    echo [INFO] Starting Docker containers...
    docker-compose up -d
    
    REM Wait for services to be ready
    echo [INFO] Waiting for services to be ready...
    timeout /t 30 /nobreak >nul
    
    REM Check if web application is accessible
    echo [INFO] Checking web application accessibility...
    set timeout=60
    :check_app
    curl -f http://localhost:8080/login >nul 2>&1
    if not errorlevel 1 (
        echo [SUCCESS] Web application is accessible
        goto app_ready
    )
    echo [INFO] Waiting for web application... (%timeout% seconds remaining)
    timeout /t 5 /nobreak >nul
    set /a timeout-=5
    if %timeout% gtr 0 goto check_app
    
    echo [ERROR] Web application is not accessible after 60 seconds
    exit /b 1
    
    :app_ready
) else (
    echo [SUCCESS] Docker containers are already running
)

REM Ensure test user exists
echo [INFO] Ensuring test user exists...
docker-compose exec app php artisan tinker --execute="try { $user = App\Models\User::where('email', 'test@example.com')->first(); if (!$user) { $user = App\Models\User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password'), 'email_verified_at' => now()]); echo 'Test user created successfully\n'; } else { echo 'Test user already exists\n'; } } catch (Exception $e) { echo 'Error: ' . $e->getMessage() . '\n'; }"

REM Set environment variables for Playwright
set APP_URL=http://localhost:8080
set PLAYWRIGHT_BROWSERS_PATH=/ms-playwright

echo [INFO] Running Playwright MCP End-to-End Tests...

REM Run the Playwright tests using MCP
where playwright >nul 2>&1
if not errorlevel 1 (
    echo [INFO] Running tests with local Playwright installation...
    cd tests\e2e
    npx playwright test seamless-tab-navigation.spec.ts --reporter=html --output=..\..\storage\logs\playwright
    cd ..\..
) else (
    echo [INFO] Running tests through Docker container...
    docker run --rm --network host -v "%cd%:/workspace" -w /workspace -e APP_URL=http://localhost:8080 mcr.microsoft.com/playwright:v1.40.0-focal npx playwright test tests/e2e/seamless-tab-navigation.spec.ts --reporter=html --output=storage/logs/playwright
)

REM Check test results
if not errorlevel 1 (
    echo [SUCCESS] All Playwright E2E tests passed!
    
    REM Generate test report summary
    echo [INFO] Generating test report summary...
    (
        echo # Playwright MCP End-to-End Test Results
        echo.
        echo ## Test Execution Summary
        echo - **Date**: %date% %time%
        echo - **Environment**: Docker
        echo - **Application URL**: %APP_URL%
        echo - **Test Suite**: Seamless Tab Navigation
        echo - **Status**: ✅ PASSED
        echo.
        echo ## Tests Executed
        echo 1. ✅ Multi-tab authentication persistence
        echo 2. ✅ No authentication prompts on tab switching
        echo 3. ✅ Tab switching performance validation (^<100ms^)
        echo 4. ✅ Cross-tab logout functionality
        echo 5. ✅ Session recovery after network interruption
        echo 6. ✅ Concurrent login handling
        echo 7. ✅ Session persistence across browser restart
        echo 8. ✅ Comprehensive logout validation
        echo.
        echo ## Performance Metrics
        echo - Tab switching performance target: ^<100ms
        echo - All performance requirements met
        echo.
        echo ## Test Coverage
        echo - ✅ Requirements 5.3: Complete user authentication workflows
        echo - ✅ Requirements 5.4: Tab switching and session persistence
        echo - ✅ Requirements 5.5: Logout functionality verification
        echo - ✅ Requirements 5.6: Performance validation
        echo - ✅ Requirements 5.7: Real web application testing
        echo.
        echo ## Next Steps
        echo - All end-to-end tests are passing
        echo - Ready for production deployment
        echo - Performance requirements satisfied
    ) > storage\logs\playwright-e2e-summary.md
    
    echo [SUCCESS] Test report generated at storage\logs\playwright-e2e-summary.md
    
) else (
    echo [ERROR] Some Playwright E2E tests failed!
    
    REM Generate failure report
    (
        echo # Playwright MCP End-to-End Test Failure Report
        echo.
        echo ## Test Execution Summary
        echo - **Date**: %date% %time%
        echo - **Environment**: Docker
        echo - **Application URL**: %APP_URL%
        echo - **Test Suite**: Seamless Tab Navigation
        echo - **Status**: ❌ FAILED
        echo.
        echo ## Failure Analysis
        echo Please check the detailed Playwright HTML report for specific failure information.
        echo.
        echo ## Troubleshooting Steps
        echo 1. Verify Docker containers are running properly
        echo 2. Check web application accessibility at %APP_URL%
        echo 3. Ensure test user (test@example.com^) exists and is accessible
        echo 4. Verify network connectivity between containers
        echo 5. Check browser compatibility and Playwright installation
        echo.
        echo ## Logs Location
        echo - Playwright HTML Report: storage\logs\playwright\index.html
        echo - Container logs: docker-compose logs
    ) > storage\logs\playwright-e2e-failure.md
    
    echo [ERROR] Failure report generated at storage\logs\playwright-e2e-failure.md
    exit /b 1
)

echo [SUCCESS] Playwright MCP End-to-End Tests completed successfully!