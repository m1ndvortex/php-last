// Optimized login composable with performance monitoring
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { loginPerformanceService } from '@/services/loginPerformanceService';
import { loginAssetOptimizer } from '@/services/loginAssetOptimizer';
import type { LoginCredentials } from '@/stores/auth';

export interface LoginFormData {
  email: string;
  password: string;
  remember: boolean;
}

export interface ValidationErrors {
  email?: string;
  password?: string;
}

export function useOptimizedLogin() {
  const router = useRouter();
  const route = useRoute();
  const authStore = useAuthStore();

  // Form state
  const form = ref<LoginFormData>({
    email: '',
    password: '',
    remember: false,
  });

  const showPassword = ref(false);
  const validationErrors = ref<ValidationErrors>({});
  const isSubmitting = ref(false);

  // Performance tracking
  const performanceStartTime = ref<number>(0);
  const authStartTime = ref<number>(0);

  // Computed properties
  const isFormValid = computed(() => {
    return (
      form.value.email.trim() !== '' &&
      form.value.password.trim() !== '' &&
      Object.keys(validationErrors.value).length === 0
    );
  });

  const isLoading = computed(() => {
    return authStore.isLoading || isSubmitting.value;
  });

  // Validation functions
  const validateField = (field: keyof LoginFormData): void => {
    switch (field) {
      case 'email':
        if (!form.value.email.trim()) {
          validationErrors.value.email = 'Email is required';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
          validationErrors.value.email = 'Please enter a valid email address';
        } else {
          delete validationErrors.value.email;
        }
        break;
      case 'password':
        if (!form.value.password.trim()) {
          validationErrors.value.password = 'Password is required';
        } else if (form.value.password.length < 6) {
          validationErrors.value.password = 'Password must be at least 6 characters';
        } else {
          delete validationErrors.value.password;
        }
        break;
    }
  };

  const validateForm = (): boolean => {
    validateField('email');
    validateField('password');
    return Object.keys(validationErrors.value).length === 0;
  };

  // Optimized form submission with performance tracking
  const handleLogin = async (): Promise<void> => {
    if (!validateForm() || isSubmitting.value) return;

    try {
      isSubmitting.value = true;
      performanceStartTime.value = performance.now();
      
      // Start authentication performance tracking
      loginPerformanceService.startAuthentication();
      authStartTime.value = performance.now();

      console.log('[OptimizedLogin] Starting login process');

      const credentials: LoginCredentials = {
        email: form.value.email,
        password: form.value.password,
        remember: form.value.remember,
      };

      const result = await authStore.login(credentials);

      // End authentication tracking
      const authTime = loginPerformanceService.endAuthentication();
      
      if (result.success) {
        console.log('[OptimizedLogin] Login successful');
        
        // Track redirect performance
        const redirectStartTime = performance.now();
        const redirectTo = (route.query.redirect as string) || '/dashboard';
        
        await router.push(redirectTo);
        
        const redirectTime = performance.now() - redirectStartTime;
        
        // Complete login performance tracking
        const metrics = loginPerformanceService.completeLoginTracking(redirectTime);
        
        console.log('[OptimizedLogin] Login completed with metrics:', {
          totalTime: `${metrics.totalLoginTime.toFixed(2)}ms`,
          authTime: `${metrics.authenticationTime.toFixed(2)}ms`,
          redirectTime: `${redirectTime.toFixed(2)}ms`,
        });

        // Show success feedback if login was slow
        if (metrics.totalLoginTime > 2000) {
          console.warn('[OptimizedLogin] Login exceeded 2-second target');
        }
      } else {
        console.error('[OptimizedLogin] Login failed:', result.error);
        
        // Track failed login performance
        loginPerformanceService.completeLoginTracking(0);
      }
    } catch (error) {
      console.error('[OptimizedLogin] Login error:', error);
      
      // Track error performance
      loginPerformanceService.completeLoginTracking(0);
    } finally {
      isSubmitting.value = false;
    }
  };

  // Optimized input handlers with debouncing
  let validationTimeout: number | null = null;
  
  const handleFieldBlur = (field: keyof LoginFormData): void => {
    // Clear existing timeout
    if (validationTimeout) {
      clearTimeout(validationTimeout);
    }
    
    // Debounce validation to avoid excessive calls
    validationTimeout = window.setTimeout(() => {
      validateField(field);
    }, 150);
  };

  // Preload dashboard resources on form interaction
  const preloadDashboardResources = (): void => {
    const link = document.createElement('link');
    link.rel = 'prefetch';
    link.href = '/dashboard';
    document.head.appendChild(link);
    
    console.log('[OptimizedLogin] Dashboard resources preloaded');
  };

  // Handle form focus to start preloading
  const handleFormFocus = (): void => {
    // Only preload once
    if (!document.querySelector('link[href="/dashboard"]')) {
      preloadDashboardResources();
    }
  };

  // Optimize password visibility toggle
  const togglePasswordVisibility = (): void => {
    showPassword.value = !showPassword.value;
  };

  // Auto-fill detection and optimization
  const handleAutoFill = (): void => {
    // Check for autofilled values after a short delay
    setTimeout(() => {
      if (form.value.email && form.value.password) {
        console.log('[OptimizedLogin] Auto-fill detected, validating form');
        validateForm();
      }
    }, 100);
  };

  // Initialize performance tracking and optimizations
  const initializeOptimizations = async (): Promise<void> => {
    // Start login page performance tracking
    loginPerformanceService.startLoginPageTracking();
    
    // Initialize asset optimizations
    await loginAssetOptimizer.initialize();
    
    // Optimize fonts
    loginAssetOptimizer.optimizeFonts();
    
    // Preload critical resources (fallback)
    loginPerformanceService.preloadCriticalResources();
    
    // Optimize images
    setTimeout(() => {
      loginPerformanceService.optimizeImages();
      loginAssetOptimizer.optimizeImages();
    }, 100);

    // Add auto-fill detection
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    if (emailInput && passwordInput) {
      emailInput.addEventListener('input', handleAutoFill);
      passwordInput.addEventListener('input', handleAutoFill);
    }

    console.log('[OptimizedLogin] All optimizations initialized');
  };

  // Cleanup function
  const cleanup = (): void => {
    if (validationTimeout) {
      clearTimeout(validationTimeout);
    }
    
    // Remove auto-fill listeners
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    if (emailInput && passwordInput) {
      emailInput.removeEventListener('input', handleAutoFill);
      passwordInput.removeEventListener('input', handleAutoFill);
    }
  };

  // Lifecycle hooks
  onMounted(() => {
    // Clear any existing errors
    authStore.error = null;
    validationErrors.value = {};
    
    // Initialize optimizations
    initializeOptimizations();
    
    // Focus email field for better UX
    setTimeout(() => {
      const emailInput = document.getElementById('email');
      if (emailInput) {
        emailInput.focus();
      }
    }, 100);
  });

  onUnmounted(() => {
    cleanup();
  });

  return {
    // Form state
    form,
    showPassword,
    validationErrors,
    isSubmitting,
    
    // Computed
    isFormValid,
    isLoading,
    
    // Methods
    handleLogin,
    validateField,
    validateForm,
    handleFieldBlur,
    handleFormFocus,
    togglePasswordVisibility,
    
    // Auth store
    authError: computed(() => authStore.error),
  };
}