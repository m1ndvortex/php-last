# Seamless Tab Navigation Integration Tests

This directory contains comprehensive integration tests for the seamless tab navigation feature. These tests validate the complete authentication workflow, cross-tab session management, and performance requirements using real web application instances in the Docker environment.

## Test Structure

### Backend Integration Tests

**File**: `tests/Feature/SeamlessTabNavigationIntegrationTest.php`

Tests the Laravel backend API endpoints and session management:

- Cross-tab authentication flows with real API calls
- Session persistence across browser restarts
- Logout functionality across multiple tabs
- Performance measurement of API response times
- Docker environment compatibility
- Session conflict resolution
- Error recovery scenarios
- Comprehensive authentication workflow

### Frontend Integration Tests

**File**: `frontend/src/services/__tests__/seamlessTabNavigation.integration.test.ts`

Tests the Vue.js frontend components and services:

- Cross-tab session synchronization using real API
- Session persistence with localStorage and sessionStorage
- Logout coordination across multiple tabs
- Performance measurement of tab switching times
- Docker environment CORS and networking
- Error recovery and network resilience
- Comprehensive end-to-end authentication workflow

## Requirements Coverage

### Requirement 4.1 - Docker Environment Compatibility
- ✅ Authentication features work correctly in Docker containers
- ✅ CORS configuration tested for frontend integration
- ✅ Container networking validated (app ↔ mysql ↔ redis)
- ✅ File permissions and storage tested

### Requirement 4.2 - Container Restart Session Handling
- ✅ Session persistence across Docker container restarts
- ✅ Token validation after application refresh
- ✅ Session recovery mechanisms tested

### Requirement 4.3 - Real Database Integration
- ✅ Tests use actual MySQL database in Docker
- ✅ Real user authentication with test credentials
- ✅ Token management with Laravel Sanctum
- ✅ Session storage in database

### Requirement 5.1 - Real Web Application Testing
- ✅ Tests use actual Laravel API endpoints
- ✅ Real HTTP requests instead of mocks
- ✅ Actual Vue.js components and stores
- ✅ Integration with real authentication system

### Requirement 5.2 - Cross-Tab Authentication Flows
- ✅ Multiple tab session simulation
- ✅ Cross-tab communication testing
- ✅ Session synchronization validation
- ✅ Conflict resolution testing

### Requirement 5.3 - Session Persistence Validation
- ✅ localStorage and sessionStorage testing
- ✅ Browser restart simulation
- ✅ Session recovery validation
- ✅ Data integrity checks

### Requirement 5.7 - Performance Requirements
- ✅ Tab switching time measurement (target: <100ms)
- ✅ API response time validation
- ✅ Cache performance testing
- ✅ Performance regression detection

## Test Credentials

All tests use the following test credentials:
- **Email**: test@example.com
- **Password**: password

These credentials are automatically created by the test setup if they don't exist.

## Running Tests

### Prerequisites

1. Docker and Docker Compose installed
2. All containers running: `docker-compose up -d`
3. Database migrations completed
4. Test user created (handled automatically)

### Backend Tests

```bash
# Run all backend integration tests
docker-compose exec app php artisan test tests/Feature/SeamlessTabNavigationIntegrationTest.php

# Run with verbose output
docker-compose exec app php artisan test tests/Feature/SeamlessTabNavigationIntegrationTest.php --verbose

# Run specific test method
docker-compose exec app php artisan test --filter test_cross_tab_authentication_flow_with_real_application
```

### Frontend Tests

```bash
# Run frontend integration tests
docker-compose exec frontend npm run test -- --run src/services/__tests__/seamlessTabNavigation.integration.test.ts

# Run with coverage
docker-compose exec frontend npm run test -- --coverage src/services/__tests__/seamlessTabNavigation.integration.test.ts

# Run with integration config
docker-compose exec frontend npx vitest --config vitest.integration.config.ts
```

### Comprehensive Test Suite

```bash
# Windows
docker\scripts\run-seamless-tab-navigation-tests.bat

# Linux/Mac
bash docker/scripts/run-seamless-tab-navigation-tests.sh
```

## Test Scenarios

### 1. Cross-Tab Authentication Flow
- User logs in on Tab 1
- Tab 2 automatically recognizes authentication
- Tab 3 opens and inherits session
- All tabs maintain synchronized state
- Performance measured and validated

### 2. Session Persistence
- User logs in and closes browser
- Browser reopens and session is restored
- Token validation with backend
- User data recovery from storage
- Session health verification

### 3. Multi-Tab Logout
- User has 5 active tabs
- Logout initiated from Tab 3
- All tabs receive logout broadcast
- Session cleared across all tabs
- Backend tokens invalidated
- Redirect to login page

### 4. Performance Validation
- Tab switching measured (target: <100ms)
- API response times tracked
- Cache hit rates monitored
- Network error recovery tested
- Performance regression detection

### 5. Docker Environment
- Container networking tested
- CORS configuration validated
- File permissions verified
- Volume persistence checked
- Service health monitoring

## Performance Benchmarks

### Target Metrics
- **Tab Switch Time**: <100ms (average)
- **API Response Time**: <500ms (95th percentile)
- **Login Time**: <500ms
- **Logout Time**: <200ms
- **Session Sync**: <50ms

### Actual Results
Results are logged during test execution and saved to:
- `SEAMLESS_TAB_NAVIGATION_TEST_REPORT.txt`
- Console output during test runs
- Coverage reports in `frontend/coverage/`

## Error Scenarios Tested

### Network Errors
- Connection timeout simulation
- Server error responses (500, 503)
- Network disconnection/reconnection
- Retry mechanism validation
- Fallback to cached data

### Authentication Errors
- Invalid token handling
- Expired session recovery
- Concurrent login conflicts
- Session hijacking prevention
- Malformed request handling

### Performance Degradation
- Slow network conditions
- High server load simulation
- Memory pressure testing
- Cache corruption recovery
- Resource exhaustion handling

## Debugging Failed Tests

### Backend Test Failures

1. **Database Connection Issues**
   ```bash
   docker-compose exec app php artisan tinker --execute="DB::connection()->getPdo();"
   ```

2. **Missing Test User**
   ```bash
   docker-compose exec app php artisan tinker --execute="App\Models\User::where('email', 'test@example.com')->first();"
   ```

3. **Token Issues**
   ```bash
   docker-compose exec app php artisan tinker --execute="App\Models\User::find(1)->tokens;"
   ```

### Frontend Test Failures

1. **API Connection Issues**
   - Check Docker container networking
   - Verify CORS configuration
   - Test API endpoints manually

2. **Storage Issues**
   - Clear localStorage/sessionStorage
   - Check browser permissions
   - Verify storage quotas

3. **Performance Issues**
   - Check system resources
   - Monitor network latency
   - Verify cache configuration

## Continuous Integration

These tests are designed to run in CI/CD pipelines:

1. **GitHub Actions**: Use Docker Compose for consistent environment
2. **GitLab CI**: Leverage Docker-in-Docker for testing
3. **Jenkins**: Execute in Docker containers for isolation
4. **Local Development**: Run with Docker Compose for consistency

## Test Data Management

### Setup
- Test user created automatically
- Database seeded with minimal required data
- Cache cleared before each test suite
- Storage cleaned between test runs

### Cleanup
- Test tokens deleted after each test
- Session data cleared
- Cache invalidated
- Temporary files removed

## Security Considerations

### Test Isolation
- Tests run in isolated Docker environment
- Test database separate from production
- Mock external services where appropriate
- Sensitive data excluded from logs

### Authentication Testing
- Use dedicated test credentials
- Token lifecycle properly managed
- Session security validated
- CSRF protection tested

## Monitoring and Alerting

### Performance Monitoring
- Response time tracking
- Error rate monitoring
- Cache hit rate analysis
- Resource utilization tracking

### Test Result Reporting
- Automated test reports generated
- Performance metrics logged
- Failure notifications configured
- Trend analysis available

## Future Enhancements

### Planned Improvements
- Visual regression testing with screenshots
- Load testing with multiple concurrent users
- Mobile browser compatibility testing
- Accessibility testing integration
- Security penetration testing

### Test Coverage Goals
- Increase code coverage to 90%
- Add more edge case scenarios
- Implement chaos engineering tests
- Add performance regression tests
- Enhance error simulation scenarios