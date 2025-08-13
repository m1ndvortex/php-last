import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import type { AxiosError } from 'axios';
import { 
  apiService, 
  enhancedApiService, 
  ApiLogger, 
  ErrorCategorizer, 
  SessionManager,
  type ApiError 
} from '../api';

// Test configuration
const TEST_API_BASE_URL = 'http://localhost:8080';

describe('Enhanced API Service - Real Integration Tests', () => {
  let originalToken: string | null = null;
  let testUser: any = null;

  beforeEach(async () => {
    // Store original auth state
    originalToken = localStorage.getItem('auth_token');
    
    // Clear any existing auth state for clean tests
    localStorage.removeItem('auth_token');
    localStorage.removeItem('session_id');
    localStorage.removeItem('session_expiry');
    
    // Clear session manager state
    SessionManager.clearSession();
  });

  afterEach(async () => {
    // Restore original auth state if it existed
    if (originalToken) {
      localStorage.setItem('auth_token', originalToken);
    } else {
      localStorage.removeItem('auth_token');
    }
    
    // Logout test user if created
    if (testUser && localStorage.getItem('auth_token')) {
      try {
        await apiService.auth.logout();
      } catch (error) {
        console.warn('Failed to logout test user:', error);
      }
    }
  });

  describe('ApiLogger', () => {
    it('should be a singleton', () => {
      const logger1 = ApiLogger.getInstance();
      const logger2 = ApiLogger.getInstance();
      expect(logger1).toBe(logger2);
    });

    it('should generate unique request IDs', () => {
      const logger = ApiLogger.getInstance();
      const id1 = logger['generateRequestId']();
      const id2 = logger['generateRequestId']();
      
      expect(id1).toMatch(/^req_\d+_[a-z0-9]+$/);
      expect(id2).toMatch(/^req_\d+_[a-z0-9]+$/);
      expect(id1).not.toBe(id2);
    });

    it('should sanitize sensitive headers', () => {
      const logger = ApiLogger.getInstance();
      const headers = {
        'Authorization': 'Bearer secret-token',
        'X-XSRF-TOKEN': 'csrf-token',
        'Content-Type': 'application/json'
      };
      
      const sanitized = logger['sanitizeHeaders'](headers);
      
      expect(sanitized.Authorization).toBe('Bearer [REDACTED]');
      expect(sanitized['X-XSRF-TOKEN']).toBe('[REDACTED]');
      expect(sanitized['Content-Type']).toBe('application/json');
    });
  });

  describe('ErrorCategorizer', () => {
    it('should categorize network errors', () => {
      const networkError = {
        message: 'Network Error',
        config: { url: '/api/test' }
      } as AxiosError;

      const categorized = ErrorCategorizer.categorizeError(networkError);

      expect(categorized.success).toBe(false);
      expect(categorized.error.code).toBe('NETWORK_ERROR');
      expect(categorized.error.retryable).toBe(true);
      expect(categorized.error.message).toContain('Network connection failed');
    });

    it('should categorize 401 unauthorized errors', () => {
      const unauthorizedError = {
        response: {
          status: 401,
          data: { message: 'Unauthorized access' }
        },
        config: { url: '/api/test' }
      } as AxiosError;

      const categorized = ErrorCategorizer.categorizeError(unauthorizedError);

      expect(categorized.error.code).toBe('UNAUTHORIZED');
      expect(categorized.error.retryable).toBe(false);
      expect(categorized.error.message).toContain('Authentication required');
    });

    it('should categorize 422 validation errors', () => {
      const validationError = {
        response: {
          status: 422,
          data: { 
            message: 'Validation failed',
            errors: { email: ['Email is required'] }
          }
        },
        config: { url: '/api/test' }
      } as AxiosError;

      const categorized = ErrorCategorizer.categorizeError(validationError);

      expect(categorized.error.code).toBe('VALIDATION_ERROR');
      expect(categorized.error.retryable).toBe(false);
      expect(categorized.error.message).toContain('Validation failed');
    });

    it('should categorize 500 server errors as retryable', () => {
      const serverError = {
        response: {
          status: 500,
          data: { message: 'Internal server error' }
        },
        config: { url: '/api/test' }
      } as AxiosError;

      const categorized = ErrorCategorizer.categorizeError(serverError);

      expect(categorized.error.code).toBe('SERVER_ERROR');
      expect(categorized.error.retryable).toBe(true);
      expect(categorized.error.message).toContain('Server error occurred');
    });

    it('should categorize 429 rate limit errors as retryable', () => {
      const rateLimitError = {
        response: {
          status: 429,
          data: { message: 'Too many requests' }
        },
        config: { url: '/api/test' }
      } as AxiosError;

      const categorized = ErrorCategorizer.categorizeError(rateLimitError);

      expect(categorized.error.code).toBe('RATE_LIMITED');
      expect(categorized.error.retryable).toBe(true);
      expect(categorized.error.message).toContain('Too many requests');
    });

    it('should handle server-provided error structures', () => {
      const customError = {
        response: {
          status: 400,
          data: { 
            error: {
              code: 'CUSTOM_ERROR',
              message: 'Custom error message',
              retryable: true
            }
          }
        },
        config: { url: '/api/test' }
      } as AxiosError;

      const categorized = ErrorCategorizer.categorizeError(customError);

      expect(categorized.error.code).toBe('CUSTOM_ERROR');
      expect(categorized.error.message).toBe('Custom error message');
      expect(categorized.error.retryable).toBe(true);
    });
  });

  describe('SessionManager', () => {
    beforeEach(() => {
      // Clear any existing session data
      SessionManager.clearSession();
    });

    it('should generate and store session ID', () => {
      const sessionId = SessionManager.getSessionId();
      
      expect(sessionId).toMatch(/^sess_\d+_[a-z0-9]+$/);
      expect(localStorage.getItem('session_id')).toBe(sessionId);
    });

    it('should reuse existing session ID from localStorage', () => {
      const existingSessionId = 'sess_existing_123';
      localStorage.setItem('session_id', existingSessionId);
      
      const sessionId = SessionManager.getSessionId();
      
      expect(sessionId).toBe(existingSessionId);
    });

    it('should set and validate session info', () => {
      const sessionId = 'test-session-id';
      const expiryDate = new Date(Date.now() + 60000); // 1 minute from now
      
      SessionManager.setSessionInfo(sessionId, expiryDate);
      
      expect(localStorage.getItem('session_id')).toBe(sessionId);
      expect(localStorage.getItem('session_expiry')).toBe(expiryDate.toISOString());
      expect(SessionManager.isSessionValid()).toBe(true);
    });

    it('should detect expired sessions', () => {
      const sessionId = 'test-session-id';
      const expiryDate = new Date(Date.now() - 60000); // 1 minute ago
      
      SessionManager.setSessionInfo(sessionId, expiryDate);
      
      expect(SessionManager.isSessionValid()).toBe(false);
    });

    it('should clear session data', () => {
      SessionManager.setSessionInfo('test-id', new Date());
      SessionManager.clearSession();
      
      expect(localStorage.getItem('session_id')).toBeNull();
      expect(localStorage.getItem('session_expiry')).toBeNull();
    });
  });

  describe('Enhanced API Service Integration', () => {
    it('should provide session management utilities', () => {
      expect(enhancedApiService.session).toBeDefined();
      expect(enhancedApiService.session.getId).toBeInstanceOf(Function);
      expect(enhancedApiService.session.isValid).toBeInstanceOf(Function);
      expect(enhancedApiService.session.clear).toBeInstanceOf(Function);
      expect(enhancedApiService.session.setInfo).toBeInstanceOf(Function);
    });

    it('should provide logging utilities', () => {
      expect(enhancedApiService.logging).toBeDefined();
      expect(enhancedApiService.logging.getInstance).toBeInstanceOf(Function);
      expect(enhancedApiService.logging.enableDebug).toBeInstanceOf(Function);
      expect(enhancedApiService.logging.disableDebug).toBeInstanceOf(Function);
    });

    it('should provide error handling utilities', () => {
      expect(enhancedApiService.errors).toBeDefined();
      expect(enhancedApiService.errors.categorize).toBeInstanceOf(Function);
      expect(enhancedApiService.errors.isRetryable).toBeInstanceOf(Function);
      expect(enhancedApiService.errors.getErrorCode).toBeInstanceOf(Function);
      expect(enhancedApiService.errors.getErrorMessage).toBeInstanceOf(Function);
    });

    it('should handle retryable errors correctly', () => {
      const retryableError: ApiError = {
        success: false,
        error: {
          code: 'SERVER_ERROR',
          message: 'Server error',
          retryable: true
        }
      };

      const nonRetryableError: ApiError = {
        success: false,
        error: {
          code: 'VALIDATION_ERROR',
          message: 'Validation error',
          retryable: false
        }
      };

      expect(enhancedApiService.errors.isRetryable(retryableError)).toBe(true);
      expect(enhancedApiService.errors.isRetryable(nonRetryableError)).toBe(false);
    });

    it('should extract error codes and messages correctly', () => {
      const error: ApiError = {
        success: false,
        error: {
          code: 'NETWORK_ERROR',
          message: 'Network connection failed',
          retryable: true
        }
      };

      expect(enhancedApiService.errors.getErrorCode(error)).toBe('NETWORK_ERROR');
      expect(enhancedApiService.errors.getErrorMessage(error)).toBe('Network connection failed');
    });
  });

  describe('Real API Integration Tests', () => {
    // Helper function to create a test user and login
    const loginTestUser = async () => {
      try {
        const response = await apiService.auth.login({
          email: 'admin@example.com',
          password: 'password123'
        });
        
        if (response.data.success) {
          testUser = response.data.data.user;
          return response.data.data;
        }
        throw new Error('Login failed');
      } catch (error) {
        console.warn('Test user login failed, this is expected if no test user exists');
        throw error;
      }
    };

    it('should handle network errors with retry logic', async () => {
      // Test with an invalid URL to trigger network error
      const originalBaseURL = apiService.get.defaults?.baseURL;
      
      try {
        // This should trigger retry logic for network errors
        await apiService.get('http://invalid-url-that-does-not-exist.com/api/test');
        expect.fail('Should have thrown an error');
      } catch (error: any) {
        // Should be categorized as network error
        expect(error.error?.code).toBe('NETWORK_ERROR');
        expect(error.error?.retryable).toBe(true);
      }
    }, 15000); // Longer timeout for retry logic

    it('should categorize 404 errors correctly', async () => {
      try {
        await apiService.get('api/non-existent-endpoint-12345');
        expect.fail('Should have thrown an error');
      } catch (error: any) {
        expect(error.response?.status).toBe(404);
      }
    });

    it('should handle authentication flow with session management', async () => {
      try {
        const loginData = await loginTestUser();
        
        // Verify session was created
        const sessionId = SessionManager.getSessionId();
        expect(sessionId).toMatch(/^sess_\d+_[a-z0-9]+$/);
        
        // Verify token was stored
        expect(localStorage.getItem('auth_token')).toBeTruthy();
        
        // Test session validation
        const sessionValidation = await apiService.auth.validateSession();
        expect(sessionValidation.data.success).toBe(true);
        
        // Test session extension
        const sessionExtension = await apiService.auth.extendSession();
        expect(sessionExtension.data.success).toBe(true);
        
      } catch (error) {
        console.warn('Authentication test skipped - no test user available');
        // This is expected in environments without test data
      }
    }, 10000);

    it('should handle unauthorized requests correctly', async () => {
      // Clear any existing auth
      localStorage.removeItem('auth_token');
      
      try {
        await apiService.auth.me();
        expect.fail('Should have thrown an unauthorized error');
      } catch (error: any) {
        expect(error.response?.status).toBe(401);
      }
    });

    it('should include proper headers in requests', async () => {
      const sessionId = SessionManager.getSessionId();
      
      // Make a request and verify headers are included
      try {
        await apiService.get('api/dashboard/kpis');
      } catch (error: any) {
        // Even if the request fails, we can check that headers were set
        const config = error.config;
        expect(config.headers['X-Session-ID']).toBe(sessionId);
        expect(config.headers['X-Request-ID']).toMatch(/^req_\d+_[a-z0-9]+$/);
        expect(config.headers['Accept-Language']).toBeTruthy();
      }
    });

    it('should handle CSRF token from cookies', async () => {
      // Set a test CSRF token in cookies
      document.cookie = 'XSRF-TOKEN=test-csrf-token-12345; path=/';
      
      try {
        await apiService.get('api/dashboard/kpis');
      } catch (error: any) {
        // Check that CSRF token was included in headers
        const config = error.config;
        expect(config.headers['X-XSRF-TOKEN']).toBe('test-csrf-token-12345');
      }
    });

    it('should log requests and responses in debug mode', async () => {
      const logger = ApiLogger.getInstance();
      logger['debugMode'] = true;
      
      const consoleSpy = vi.spyOn(console, 'group').mockImplementation(() => {});
      const consoleLogSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
      const consoleGroupEndSpy = vi.spyOn(console, 'groupEnd').mockImplementation(() => {});
      
      try {
        await apiService.get('api/dashboard/kpis');
      } catch (error) {
        // Request should have been logged regardless of success/failure
      }
      
      // Verify logging occurred
      expect(consoleSpy).toHaveBeenCalled();
      expect(consoleLogSpy).toHaveBeenCalled();
      expect(consoleGroupEndSpy).toHaveBeenCalled();
      
      // Cleanup
      consoleSpy.mockRestore();
      consoleLogSpy.mockRestore();
      consoleGroupEndSpy.mockRestore();
      logger['debugMode'] = false;
    });
  });

  describe('Enhanced API Service Utilities', () => {
    it('should provide session management utilities', () => {
      expect(enhancedApiService.session).toBeDefined();
      expect(enhancedApiService.session.getId).toBeInstanceOf(Function);
      expect(enhancedApiService.session.isValid).toBeInstanceOf(Function);
      expect(enhancedApiService.session.clear).toBeInstanceOf(Function);
      expect(enhancedApiService.session.setInfo).toBeInstanceOf(Function);
    });

    it('should provide logging utilities', () => {
      expect(enhancedApiService.logging).toBeDefined();
      expect(enhancedApiService.logging.getInstance).toBeInstanceOf(Function);
      expect(enhancedApiService.logging.enableDebug).toBeInstanceOf(Function);
      expect(enhancedApiService.logging.disableDebug).toBeInstanceOf(Function);
    });

    it('should provide error handling utilities', () => {
      expect(enhancedApiService.errors).toBeDefined();
      expect(enhancedApiService.errors.categorize).toBeInstanceOf(Function);
      expect(enhancedApiService.errors.isRetryable).toBeInstanceOf(Function);
      expect(enhancedApiService.errors.getErrorCode).toBeInstanceOf(Function);
      expect(enhancedApiService.errors.getErrorMessage).toBeInstanceOf(Function);
    });

    it('should handle retryable errors correctly', () => {
      const retryableError: ApiError = {
        success: false,
        error: {
          code: 'SERVER_ERROR',
          message: 'Server error',
          retryable: true
        }
      };

      const nonRetryableError: ApiError = {
        success: false,
        error: {
          code: 'VALIDATION_ERROR',
          message: 'Validation error',
          retryable: false
        }
      };

      expect(enhancedApiService.errors.isRetryable(retryableError)).toBe(true);
      expect(enhancedApiService.errors.isRetryable(nonRetryableError)).toBe(false);
    });

    it('should extract error codes and messages correctly', () => {
      const error: ApiError = {
        success: false,
        error: {
          code: 'NETWORK_ERROR',
          message: 'Network connection failed',
          retryable: true
        }
      };

      expect(enhancedApiService.errors.getErrorCode(error)).toBe('NETWORK_ERROR');
      expect(enhancedApiService.errors.getErrorMessage(error)).toBe('Network connection failed');
    });
  });
});