# Playwright MCP End-to-End Tests for Seamless Tab Navigation

This directory contains comprehensive end-to-end tests for the seamless tab navigation feature using Playwright MCP.

## Overview

The test suite validates the following requirements:
- **Requirement 5.3**: Complete user authentication workflows
- **Requirement 5.4**: Tab switching and session persistence  
- **Requirement 5.5**: Logout functionality verification
- **Requirement 5.6**: Performance validation (tab switching <100ms)
- **Requirement 5.7**: Real web application testing with test credentials

## Test Structure

### Main Test File
- `seamless-tab-navigation.spec.ts` - Main test suite with 8 comprehensive test scenarios

### Helper Files
- `helpers/test-helpers.ts` - Utility functions for authentication, performance, multi-tab management
- `global-setup.ts` - Global test environment setup
- `global-teardown.ts` - Global test environment cleanup

### Configuration
- `../../playwright.config.ts` - Playwright configuration optimized for Docker environment

## Test Scenarios

### 1. Multi-Tab Authentication Persistence
- Verifies login in one tab persists across all tabs
- Tests automatic authentication without prompts
- Validates session data integrity

### 2. No Authentication Prompts on Tab Switching
- Ensures seamless navigation between authenticated tabs
- Verifies no login forms appear when switching tabs
- Tests user menu visibility across all tabs

### 3. Tab Switching Performance Validation
- Measures tab switching times (target: <100ms)
- Collects performance metrics and statistics
- Validates performance requirements are met

### 4. Cross-Tab Logout Functionality
- Tests logout propagation across all open tabs
- Verifies session cleanup in all tabs
- Ensures login forms appear after logout

### 5. Session Recovery After Network Interruption
- Simulates network disconnection and recovery
- Tests session persistence through network issues
- Validates automatic session restoration

### 6. Concurrent Login Handling
- Tests simultaneous login attempts across multiple tabs
- Verifies conflict resolution and session coordination
- Ensures all tabs reach authenticated state

### 7. Session Persistence Across Browser Restart
- Simulates browser restart by closing/reopening tabs
- Tests session storage and recovery mechanisms
- Validates persistent authentication state

### 8. Comprehensive Logout Validation
- Tests logout functionality in various scenarios
- Validates logout with network issues
- Ensures reliable logout completion

## Running Tests

### Prerequisites
- Docker containers must be running
- Test user (test@example.com/password) must exist
- Playwright MCP must be installed and configured

### Docker Environment
```bash
# Linux/Mac
./docker/scripts/run-playwright-e2e-tests.sh

# Windows
docker/scripts/run-playwright-e2e-tests.bat
```

### Manual Execution
```bash
# Ensure Docker is running
docker-compose up -d

# Run tests with Playwright
npx playwright test tests/e2e/seamless-tab-navigation.spec.ts --reporter=html

# View results
npx playwright show-report storage/logs/playwright-report
```

## Test Configuration

### Environment Variables
- `APP_URL`: Application URL (default: http://localhost:8080)
- `PLAYWRIGHT_BROWSERS_PATH`: Browser installation path

### Test Credentials
- Email: test@example.com
- Password: password

### Performance Targets
- Tab switching time: <100ms average
- Authentication response: <5s
- Network recovery: <10s

## Test Reports

### Generated Reports
- HTML Report: `storage/logs/playwright-report/index.html`
- JSON Results: `storage/logs/playwright-results.json`
- JUnit XML: `storage/logs/playwright-junit.xml`
- Summary Report: `storage/logs/playwright-e2e-summary.md`

### Performance Metrics
Tests collect and report:
- Individual tab switching times
- Average, minimum, and maximum performance
- Network recovery times
- Authentication completion times

## Troubleshooting

### Common Issues

1. **Docker containers not running**
   ```bash
   docker-compose up -d
   docker-compose ps
   ```

2. **Test user doesn't exist**
   ```bash
   docker-compose exec app php artisan tinker
   # Create user manually in tinker
   ```

3. **Application not accessible**
   ```bash
   curl http://localhost:8080/login
   # Check nginx and app container logs
   ```

4. **Playwright browser issues**
   ```bash
   npx playwright install
   # Or use Docker container approach
   ```

### Debug Mode
Run tests with debug output:
```bash
DEBUG=pw:api npx playwright test tests/e2e/seamless-tab-navigation.spec.ts
```

### Headed Mode
Run tests with visible browser:
```bash
npx playwright test tests/e2e/seamless-tab-navigation.spec.ts --headed
```

## Test Maintenance

### Adding New Tests
1. Create test function in `seamless-tab-navigation.spec.ts`
2. Use helper functions from `helpers/test-helpers.ts`
3. Follow existing patterns for error handling and reporting
4. Update this README with new test description

### Updating Helpers
1. Modify functions in `helpers/test-helpers.ts`
2. Ensure backward compatibility
3. Update JSDoc documentation
4. Test changes across all test scenarios

### Performance Tuning
1. Adjust timeouts in `playwright.config.ts`
2. Modify performance thresholds in test helpers
3. Update network simulation parameters
4. Optimize Docker container resources

## Integration with CI/CD

### GitHub Actions Example
```yaml
- name: Run Playwright E2E Tests
  run: |
    docker-compose up -d
    npx playwright test tests/e2e/seamless-tab-navigation.spec.ts
    npx playwright show-report --host=0.0.0.0
```

### Test Results Processing
- JUnit XML for CI integration
- HTML reports for detailed analysis
- JSON results for automated processing
- Performance metrics for monitoring

## Security Considerations

### Test Data
- Uses dedicated test credentials
- No production data in tests
- Isolated test environment

### Network Security
- Tests run in Docker network
- No external network dependencies
- Simulated network conditions only

### Session Security
- Tests session cleanup thoroughly
- Validates token management
- Ensures no session leakage

## Performance Monitoring

### Metrics Collected
- Tab switching response times
- Authentication completion times
- Network recovery performance
- Memory and CPU usage patterns

### Performance Thresholds
- Tab switching: <100ms (requirement)
- Authentication: <5s (best practice)
- Network recovery: <10s (acceptable)
- Memory usage: <500MB per tab (monitoring)

## Future Enhancements

### Planned Improvements
1. Visual regression testing
2. Accessibility testing integration
3. Mobile device testing
4. Load testing scenarios
5. Cross-browser compatibility matrix

### Test Coverage Expansion
1. Edge case scenarios
2. Error condition testing
3. Performance stress testing
4. Security vulnerability testing
5. Internationalization testing