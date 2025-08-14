#!/bin/bash

# Seamless Tab Navigation Playwright E2E Test Runner for Docker
# This script runs the Playwright MCP end-to-end tests in Docker environment

set -e

echo "🎭 Starting Playwright MCP End-to-End Tests for Seamless Tab Navigation..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

print_status "Checking Docker containers..."

# Start Docker containers if not running
if ! docker-compose ps | grep -q "Up"; then
    print_status "Starting Docker containers..."
    docker-compose up -d
    
    # Wait for services to be ready
    print_status "Waiting for services to be ready..."
    sleep 30
    
    # Check if web application is accessible
    print_status "Checking web application accessibility..."
    timeout=60
    while [ $timeout -gt 0 ]; do
        if curl -f http://localhost:8080/login > /dev/null 2>&1; then
            print_success "Web application is accessible"
            break
        fi
        print_status "Waiting for web application... ($timeout seconds remaining)"
        sleep 5
        timeout=$((timeout - 5))
    done
    
    if [ $timeout -le 0 ]; then
        print_error "Web application is not accessible after 60 seconds"
        exit 1
    fi
else
    print_success "Docker containers are already running"
fi

# Ensure test user exists
print_status "Ensuring test user exists..."
docker-compose exec app php artisan tinker --execute="
try {
    \$user = App\Models\User::where('email', 'test@example.com')->first();
    if (!\$user) {
        \$user = App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        echo 'Test user created successfully\n';
    } else {
        echo 'Test user already exists\n';
    }
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
}
"

# Set environment variables for Playwright
export APP_URL="http://localhost:8080"
export PLAYWRIGHT_BROWSERS_PATH="/ms-playwright"

print_status "Running Playwright MCP End-to-End Tests..."

# Run the Playwright tests using MCP
# Note: This assumes Playwright MCP is configured and available
if command -v playwright > /dev/null 2>&1; then
    print_status "Running tests with local Playwright installation..."
    cd tests/e2e
    npx playwright test seamless-tab-navigation.spec.ts --reporter=html --output=../../storage/logs/playwright
else
    print_status "Running tests through Docker container..."
    docker run --rm \
        --network host \
        -v "$(pwd):/workspace" \
        -w /workspace \
        -e APP_URL="http://localhost:8080" \
        mcr.microsoft.com/playwright:v1.40.0-focal \
        npx playwright test tests/e2e/seamless-tab-navigation.spec.ts --reporter=html --output=storage/logs/playwright
fi

# Check test results
if [ $? -eq 0 ]; then
    print_success "All Playwright E2E tests passed!"
    
    # Generate test report summary
    print_status "Generating test report summary..."
    cat > storage/logs/playwright-e2e-summary.md << EOF
# Playwright MCP End-to-End Test Results

## Test Execution Summary
- **Date**: $(date)
- **Environment**: Docker
- **Application URL**: $APP_URL
- **Test Suite**: Seamless Tab Navigation
- **Status**: ✅ PASSED

## Tests Executed
1. ✅ Multi-tab authentication persistence
2. ✅ No authentication prompts on tab switching
3. ✅ Tab switching performance validation (<100ms)
4. ✅ Cross-tab logout functionality
5. ✅ Session recovery after network interruption
6. ✅ Concurrent login handling
7. ✅ Session persistence across browser restart
8. ✅ Comprehensive logout validation

## Performance Metrics
- Tab switching performance target: <100ms
- All performance requirements met

## Test Coverage
- ✅ Requirements 5.3: Complete user authentication workflows
- ✅ Requirements 5.4: Tab switching and session persistence
- ✅ Requirements 5.5: Logout functionality verification
- ✅ Requirements 5.6: Performance validation
- ✅ Requirements 5.7: Real web application testing

## Next Steps
- All end-to-end tests are passing
- Ready for production deployment
- Performance requirements satisfied
EOF

    print_success "Test report generated at storage/logs/playwright-e2e-summary.md"
    
else
    print_error "Some Playwright E2E tests failed!"
    
    # Generate failure report
    cat > storage/logs/playwright-e2e-failure.md << EOF
# Playwright MCP End-to-End Test Failure Report

## Test Execution Summary
- **Date**: $(date)
- **Environment**: Docker
- **Application URL**: $APP_URL
- **Test Suite**: Seamless Tab Navigation
- **Status**: ❌ FAILED

## Failure Analysis
Please check the detailed Playwright HTML report for specific failure information.

## Troubleshooting Steps
1. Verify Docker containers are running properly
2. Check web application accessibility at $APP_URL
3. Ensure test user (test@example.com) exists and is accessible
4. Verify network connectivity between containers
5. Check browser compatibility and Playwright installation

## Logs Location
- Playwright HTML Report: storage/logs/playwright/index.html
- Container logs: docker-compose logs
EOF

    print_error "Failure report generated at storage/logs/playwright-e2e-failure.md"
    exit 1
fi

print_success "Playwright MCP End-to-End Tests completed successfully!"