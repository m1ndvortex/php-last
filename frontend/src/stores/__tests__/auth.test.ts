import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { createApp } from 'vue';
import { useAuthStore } from '../auth';

// Mock the API service
vi.mock('@/services/api', () => ({
  apiService: {
    auth: {
      login: vi.fn(),
      logout: vi.fn(),
      me: vi.fn(),
      refresh: vi.fn(),
      validateSession: vi.fn(),
      extendSession: vi.fn(),
    }
  }
}));

// Mock the router
vi.mock('@/router', () => ({
  default: {
    push: vi.fn()
  }
}));

describe('Auth Store', () => {
  beforeEach(() => {
    // Create a Vue app instance to provide proper context
    const app = createApp({});
    const pinia = createPinia();
    app.use(pinia);
    setActivePinia(pinia);
    
    // Clear localStorage
    localStorage.clear();
    // Clear all timers
    vi.clearAllTimers();
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it('should initialize with correct default state', () => {
    const authStore = useAuthStore();
    
    expect(authStore.user).toBeNull();
    expect(authStore.token).toBeNull();
    expect(authStore.isLoading).toBe(false);
    expect(authStore.error).toBeNull();
    expect(authStore.initialized).toBe(false);
    expect(authStore.isAuthenticated).toBe(false);
  });

  it('should handle successful login', async () => {
    const { apiService } = await import('@/services/api');
    const authStore = useAuthStore();

    const mockResponse = {
      data: {
        success: true,
        data: {
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            preferred_language: 'en'
          },
          token: 'test-token',
          session_expiry: new Date(Date.now() + 3600000).toISOString()
        }
      }
    };

    vi.mocked(apiService.auth.login).mockResolvedValue(mockResponse);

    const result = await authStore.login({
      email: 'test@example.com',
      password: 'password'
    });

    expect(result.success).toBe(true);
    expect(authStore.user).toEqual(mockResponse.data.data.user);
    expect(authStore.token).toBe('test-token');
    expect(authStore.isAuthenticated).toBe(true);
    expect(localStorage.getItem('auth_token')).toBe('test-token');
  });

  it('should handle login failure', async () => {
    const { apiService } = await import('@/services/api');
    const authStore = useAuthStore();

    const mockError = {
      response: {
        data: {
          error: {
            code: 'INVALID_CREDENTIALS',
            message: 'Invalid credentials'
          }
        }
      }
    };

    vi.mocked(apiService.auth.login).mockRejectedValue(mockError);

    const result = await authStore.login({
      email: 'test@example.com',
      password: 'wrong-password'
    });

    expect(result.success).toBe(false);
    expect(result.error).toBe('Invalid credentials');
    expect(authStore.user).toBeNull();
    expect(authStore.token).toBeNull();
    expect(authStore.isAuthenticated).toBe(false);
  });

  it('should handle logout', async () => {
    const { apiService } = await import('@/services/api');
    const authStore = useAuthStore();

    // Set up authenticated state
    authStore.user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      preferred_language: 'en',
      is_active: true
    };
    authStore.token = 'test-token';
    localStorage.setItem('auth_token', 'test-token');

    vi.mocked(apiService.auth.logout).mockResolvedValue({ data: { success: true } });

    await authStore.logout();

    expect(authStore.user).toBeNull();
    expect(authStore.token).toBeNull();
    expect(authStore.isAuthenticated).toBe(false);
    expect(localStorage.getItem('auth_token')).toBeNull();
  });

  it('should validate session', async () => {
    const { apiService } = await import('@/services/api');
    const authStore = useAuthStore();

    authStore.token = 'test-token';

    const mockResponse = {
      data: {
        success: true,
        data: {
          session_valid: true,
          expires_at: new Date(Date.now() + 3600000).toISOString(),
          time_remaining_minutes: 60
        }
      }
    };

    vi.mocked(apiService.auth.validateSession).mockResolvedValue(mockResponse);

    const isValid = await authStore.validateSession();

    expect(isValid).toBe(true);
    expect(authStore.sessionExpiry).toBeDefined();
  });

  it('should extend session', async () => {
    const { apiService } = await import('@/services/api');
    const authStore = useAuthStore();

    authStore.token = 'test-token';

    const mockResponse = {
      data: {
        success: true,
        data: {
          session_extended: true,
          expires_at: new Date(Date.now() + 3600000).toISOString()
        }
      }
    };

    vi.mocked(apiService.auth.extendSession).mockResolvedValue(mockResponse);

    const extended = await authStore.extendSession();

    expect(extended).toBe(true);
    expect(authStore.sessionExpiry).toBeDefined();
  });

  it('should handle token refresh', async () => {
    const { apiService } = await import('@/services/api');
    const authStore = useAuthStore();

    authStore.token = 'old-token';

    const mockResponse = {
      data: {
        success: true,
        data: {
          token: 'new-token',
          expires_at: new Date(Date.now() + 3600000).toISOString()
        }
      }
    };

    vi.mocked(apiService.auth.refresh).mockResolvedValue(mockResponse);

    const refreshed = await authStore.refreshAuthToken();

    expect(refreshed).toBe(true);
    expect(authStore.token).toBe('new-token');
    expect(localStorage.getItem('auth_token')).toBe('new-token');
  });

  it('should clean up auth state', () => {
    const authStore = useAuthStore();

    // Set up authenticated state
    authStore.user = {
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      preferred_language: 'en',
      is_active: true
    };
    authStore.token = 'test-token';
    authStore.error = 'Some error';
    localStorage.setItem('auth_token', 'test-token');

    authStore.cleanupAuthState();

    expect(authStore.user).toBeNull();
    expect(authStore.token).toBeNull();
    expect(authStore.error).toBeNull();
    expect(authStore.isAuthenticated).toBe(false);
    expect(localStorage.getItem('auth_token')).toBeNull();
  });
});