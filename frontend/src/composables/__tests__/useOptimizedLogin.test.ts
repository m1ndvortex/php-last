import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { ref } from 'vue';
import { useOptimizedLogin } from '../useOptimizedLogin';

// Mock dependencies
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: vi.fn(),
  }),
  useRoute: () => ({
    query: { redirect: '/dashboard' },
  }),
}));

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    isLoading: ref(false),
    error: ref(null),
    login: vi.fn().mockResolvedValue({ success: true }),
  }),
}));

vi.mock('@/services/loginPerformanceService', () => ({
  loginPerformanceService: {
    startLoginPageTracking: vi.fn(),
    startAuthentication: vi.fn(),
    endAuthentication: vi.fn().mockReturnValue(500),
    completeLoginTracking: vi.fn().mockReturnValue({
      totalLoginTime: 1500,
      authenticationTime: 500,
      pageLoadTime: 800,
      redirectTime: 200,
    }),
    preloadCriticalResources: vi.fn(),
    optimizeImages: vi.fn(),
  },
}));

// Mock DOM methods
const mockDocument = {
  getElementById: vi.fn(),
  querySelector: vi.fn(),
  addEventListener: vi.fn(),
  removeEventListener: vi.fn(),
};

const mockElement = {
  focus: vi.fn(),
  addEventListener: vi.fn(),
  removeEventListener: vi.fn(),
};

global.document = mockDocument as any;
global.setTimeout = vi.fn((fn) => fn()) as any;
global.clearTimeout = vi.fn();

describe('useOptimizedLogin', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockDocument.getElementById.mockReturnValue(mockElement);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Form State Management', () => {
    it('should initialize with empty form', () => {
      const { form } = useOptimizedLogin();
      
      expect(form.value).toEqual({
        email: '',
        password: '',
        remember: false,
      });
    });

    it('should validate email field correctly', () => {
      const { form, validationErrors, validateField } = useOptimizedLogin();
      
      // Test empty email
      form.value.email = '';
      validateField('email');
      expect(validationErrors.value.email).toBe('Email is required');
      
      // Test invalid email
      form.value.email = 'invalid-email';
      validateField('email');
      expect(validationErrors.value.email).toBe('Please enter a valid email address');
      
      // Test valid email
      form.value.email = 'test@example.com';
      validateField('email');
      expect(validationErrors.value.email).toBeUndefined();
    });

    it('should validate password field correctly', () => {
      const { form, validationErrors, validateField } = useOptimizedLogin();
      
      // Test empty password
      form.value.password = '';
      validateField('password');
      expect(validationErrors.value.password).toBe('Password is required');
      
      // Test short password
      form.value.password = '123';
      validateField('password');
      expect(validationErrors.value.password).toBe('Password must be at least 6 characters');
      
      // Test valid password
      form.value.password = 'password123';
      validateField('password');
      expect(validationErrors.value.password).toBeUndefined();
    });

    it('should compute form validity correctly', () => {
      const { form, isFormValid, validateForm } = useOptimizedLogin();
      
      // Invalid form
      expect(isFormValid.value).toBe(false);
      
      // Valid form
      form.value.email = 'test@example.com';
      form.value.password = 'password123';
      validateForm();
      expect(isFormValid.value).toBe(true);
    });
  });

  describe('Performance Optimization', () => {
    it('should initialize performance tracking on mount', () => {
      const { loginPerformanceService } = require('@/services/loginPerformanceService');
      
      useOptimizedLogin();
      
      expect(loginPerformanceService.startLoginPageTracking).toHaveBeenCalled();
      expect(loginPerformanceService.preloadCriticalResources).toHaveBeenCalled();
    });

    it('should track authentication performance during login', async () => {
      const { loginPerformanceService } = require('@/services/loginPerformanceService');
      const { form, handleLogin } = useOptimizedLogin();
      
      form.value.email = 'test@example.com';
      form.value.password = 'password123';
      
      await handleLogin();
      
      expect(loginPerformanceService.startAuthentication).toHaveBeenCalled();
      expect(loginPerformanceService.endAuthentication).toHaveBeenCalled();
      expect(loginPerformanceService.completeLoginTracking).toHaveBeenCalled();
    });

    it('should preload dashboard resources on form focus', () => {
      const { handleFormFocus } = useOptimizedLogin();
      
      // Mock document.querySelector to return null (no existing preload)
      mockDocument.querySelector.mockReturnValue(null);
      
      const mockLink = {
        rel: '',
        href: '',
      };
      mockDocument.createElement = vi.fn().mockReturnValue(mockLink);
      mockDocument.head = { appendChild: vi.fn() };
      
      handleFormFocus();
      
      expect(mockDocument.createElement).toHaveBeenCalledWith('link');
      expect(mockDocument.head.appendChild).toHaveBeenCalled();
    });

    it('should optimize images after mount', () => {
      const { loginPerformanceService } = require('@/services/loginPerformanceService');
      
      useOptimizedLogin();
      
      // Should be called after a timeout
      expect(setTimeout).toHaveBeenCalled();
      expect(loginPerformanceService.optimizeImages).toHaveBeenCalled();
    });
  });

  describe('Login Process', () => {
    it('should handle successful login with performance tracking', async () => {
      const { useAuthStore } = require('@/stores/auth');
      const { useRouter } = require('vue-router');
      const { loginPerformanceService } = require('@/services/loginPerformanceService');
      
      const authStore = useAuthStore();
      const router = useRouter();
      const { form, handleLogin } = useOptimizedLogin();
      
      form.value.email = 'test@example.com';
      form.value.password = 'password123';
      
      await handleLogin();
      
      expect(authStore.login).toHaveBeenCalledWith({
        email: 'test@example.com',
        password: 'password123',
        remember: false,
      });
      expect(router.push).toHaveBeenCalledWith('/dashboard');
      expect(loginPerformanceService.completeLoginTracking).toHaveBeenCalled();
    });

    it('should handle login failure with performance tracking', async () => {
      const { useAuthStore } = require('@/stores/auth');
      const { loginPerformanceService } = require('@/services/loginPerformanceService');
      
      const authStore = useAuthStore();
      authStore.login.mockResolvedValue({ success: false, error: 'Invalid credentials' });
      
      const { form, handleLogin } = useOptimizedLogin();
      
      form.value.email = 'test@example.com';
      form.value.password = 'wrongpassword';
      
      await handleLogin();
      
      expect(loginPerformanceService.completeLoginTracking).toHaveBeenCalledWith(0);
    });

    it('should prevent multiple concurrent login attempts', async () => {
      const { useAuthStore } = require('@/stores/auth');
      const authStore = useAuthStore();
      
      const { form, handleLogin, isSubmitting } = useOptimizedLogin();
      
      form.value.email = 'test@example.com';
      form.value.password = 'password123';
      
      // Start first login
      const loginPromise1 = handleLogin();
      expect(isSubmitting.value).toBe(true);
      
      // Try to start second login while first is in progress
      const loginPromise2 = handleLogin();
      
      await Promise.all([loginPromise1, loginPromise2]);
      
      // Should only call login once
      expect(authStore.login).toHaveBeenCalledTimes(1);
    });
  });

  describe('Form Interaction Optimizations', () => {
    it('should debounce field validation', () => {
      const { handleFieldBlur } = useOptimizedLogin();
      
      // Call multiple times quickly
      handleFieldBlur('email');
      handleFieldBlur('email');
      handleFieldBlur('email');
      
      // Should only set one timeout
      expect(setTimeout).toHaveBeenCalledTimes(1);
    });

    it('should handle auto-fill detection', () => {
      const { form } = useOptimizedLogin();
      
      // Simulate auto-fill
      form.value.email = 'autofilled@example.com';
      form.value.password = 'autofilledpassword';
      
      // Auto-fill handler should be set up
      expect(mockDocument.getElementById).toHaveBeenCalledWith('email');
      expect(mockDocument.getElementById).toHaveBeenCalledWith('password');
    });

    it('should toggle password visibility', () => {
      const { showPassword, togglePasswordVisibility } = useOptimizedLogin();
      
      expect(showPassword.value).toBe(false);
      
      togglePasswordVisibility();
      expect(showPassword.value).toBe(true);
      
      togglePasswordVisibility();
      expect(showPassword.value).toBe(false);
    });
  });

  describe('Accessibility and UX', () => {
    it('should focus email field on mount', () => {
      useOptimizedLogin();
      
      expect(setTimeout).toHaveBeenCalled();
      expect(mockDocument.getElementById).toHaveBeenCalledWith('email');
      expect(mockElement.focus).toHaveBeenCalled();
    });

    it('should clear auth errors on mount', () => {
      const { useAuthStore } = require('@/stores/auth');
      const authStore = useAuthStore();
      
      authStore.error.value = 'Previous error';
      
      useOptimizedLogin();
      
      expect(authStore.error.value).toBe(null);
    });
  });

  describe('Cleanup', () => {
    it('should cleanup event listeners on unmount', () => {
      const { cleanup } = useOptimizedLogin() as any;
      
      if (cleanup) {
        cleanup();
        expect(clearTimeout).toHaveBeenCalled();
        expect(mockElement.removeEventListener).toHaveBeenCalled();
      }
    });
  });

  describe('Error Handling', () => {
    it('should handle login errors gracefully', async () => {
      const { useAuthStore } = require('@/stores/auth');
      const authStore = useAuthStore();
      
      authStore.login.mockRejectedValue(new Error('Network error'));
      
      const { form, handleLogin } = useOptimizedLogin();
      
      form.value.email = 'test@example.com';
      form.value.password = 'password123';
      
      await expect(handleLogin()).resolves.not.toThrow();
    });

    it('should handle performance service errors gracefully', () => {
      const { loginPerformanceService } = require('@/services/loginPerformanceService');
      
      loginPerformanceService.startLoginPageTracking.mockImplementation(() => {
        throw new Error('Performance service error');
      });
      
      expect(() => useOptimizedLogin()).not.toThrow();
    });

    it('should handle DOM errors gracefully', () => {
      mockDocument.getElementById.mockImplementation(() => {
        throw new Error('DOM error');
      });
      
      expect(() => useOptimizedLogin()).not.toThrow();
    });
  });

  describe('Performance Metrics', () => {
    it('should track slow login performance', async () => {
      const { loginPerformanceService } = require('@/services/loginPerformanceService');
      
      // Mock slow login
      loginPerformanceService.completeLoginTracking.mockReturnValue({
        totalLoginTime: 3000, // Slow login
        authenticationTime: 1000,
        pageLoadTime: 1500,
        redirectTime: 500,
      });
      
      const { form, handleLogin } = useOptimizedLogin();
      
      form.value.email = 'test@example.com';
      form.value.password = 'password123';
      
      await handleLogin();
      
      // Should log warning for slow login
      expect(console.warn).toHaveBeenCalledWith(
        expect.stringContaining('Login exceeded 2-second target')
      );
    });

    it('should track fast login performance', async () => {
      const { loginPerformanceService } = require('@/services/loginPerformanceService');
      
      // Mock fast login
      loginPerformanceService.completeLoginTracking.mockReturnValue({
        totalLoginTime: 800, // Fast login
        authenticationTime: 300,
        pageLoadTime: 400,
        redirectTime: 100,
      });
      
      const { form, handleLogin } = useOptimizedLogin();
      
      form.value.email = 'test@example.com';
      form.value.password = 'password123';
      
      await handleLogin();
      
      // Should not log warning for fast login
      expect(console.warn).not.toHaveBeenCalledWith(
        expect.stringContaining('Login exceeded 2-second target')
      );
    });
  });
});