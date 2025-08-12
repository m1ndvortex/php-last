# Security Features Documentation

## Overview

This document outlines the minimal security features implemented in the jewelry platform to protect against common security threats while maintaining simplicity and avoiding application errors.

## Implemented Security Features

### 1. CORS Configuration

**Location**: `config/cors.php`

- Configured to allow specific origins only (frontend and backend URLs)
- Supports credentials for authenticated requests
- Blocks unauthorized cross-origin requests

**Configuration**:
```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:3000'),
    env('APP_URL', 'http://localhost:8000'),
    'http://localhost:3000',
    'http://localhost:8000',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:8000',
],
```

### 2. CSRF Protection

**Location**: `app/Http/Middleware/SimpleCSRFProtection.php`

- Simple CSRF token validation that works with frontend
- Skips validation for GET, HEAD, OPTIONS requests
- Can be disabled via environment variable for development
- Provides clear error messages

**Features**:
- Validates X-CSRF-TOKEN header
- Validates _token form field
- Validates X-XSRF-TOKEN header
- Returns 419 status code with descriptive error

### 3. Rate Limiting

**Location**: `app/Http/Middleware/SecurityMiddleware.php`

- Basic rate limiting: 100 requests per minute per IP
- Uses Laravel's cache system for tracking
- Logs rate limit violations
- Returns 429 status code when exceeded

**Configuration**:
- Limit: 100 requests per minute
- Tracking: By IP address
- Storage: Laravel cache (file-based)

### 4. Input Sanitization

**Location**: `app/Http/Middleware/SecurityMiddleware.php` and `app/Services/InputValidationService.php`

**Middleware Sanitization**:
- Removes script tags completely
- Strips HTML tags
- Removes javascript: protocol
- Removes event handlers (onclick, onload, etc.)

**Service Sanitization**:
- HTML entity encoding
- XSS pattern detection
- SQL injection pattern detection
- Filename sanitization for uploads

### 5. Session Security

**Location**: `app/Http/Middleware/SessionSecurityMiddleware.php`

**Features**:
- Session timeout handling (60 minutes default)
- Automatic session regeneration every 30 minutes
- Last activity tracking
- Secure logout on timeout

**Configuration**:
- Session lifetime: 60 minutes
- Session encryption: Enabled
- Same-site policy: Strict
- Regeneration interval: 30 minutes

### 6. Security Audit Logging

**Location**: `app/Http/Middleware/SecurityAuditMiddleware.php`

**Logged Events**:
- Login attempts (success/failure)
- Logout events
- Password changes
- Unauthorized access attempts
- Suspicious requests (based on user agent and content)

**Log Storage**: `storage/logs/security.log` (90-day retention)

## Environment Configuration

Add these variables to your `.env` file:

```env
# Security Configuration
CORS_DISABLED=false
CSRF_DISABLED=false
FRONTEND_URL=http://localhost:3000

# Session Security
SESSION_LIFETIME=60
SESSION_ENCRYPT=true
```

## Testing

Run the security tests to verify all features work correctly:

```bash
docker-compose exec app php artisan test tests/Feature/SecurityMiddlewareTest.php
```

## Security Best Practices

1. **Regular Updates**: Keep all dependencies updated
2. **Environment Variables**: Never commit sensitive data to version control
3. **HTTPS**: Use HTTPS in production
4. **Strong Passwords**: Enforce strong password policies
5. **Regular Monitoring**: Monitor security logs regularly

## Troubleshooting

### CORS Issues
- Verify FRONTEND_URL matches your frontend domain
- Check browser developer tools for CORS errors
- Ensure credentials are properly configured

### CSRF Issues
- Ensure frontend sends CSRF token in headers
- Check that sessions are working properly
- Verify middleware order in Kernel.php

### Rate Limiting Issues
- Clear cache if testing: `php artisan cache:clear`
- Adjust rate limits in SecurityMiddleware if needed
- Check IP address detection in load balancer setups

### Session Issues
- Verify session driver is properly configured
- Check session table exists if using database driver
- Ensure proper session cookie configuration

## Security Considerations

This implementation provides **minimal security** suitable for development and small-scale production environments. For enterprise deployments, consider:

- Web Application Firewall (WAF)
- DDoS protection
- Advanced threat detection
- Security scanning tools
- Regular security audits
- Multi-factor authentication
- Advanced session management
- Database encryption
- API rate limiting per user
- Advanced input validation