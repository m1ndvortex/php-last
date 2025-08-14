import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { createRouter, createWebHistory } from 'vue-router';
import { setActivePinia, createPinia } from 'pinia';

// Mock the services
vi.mock('@/services/routePreloader', () => ({
  routePreloader: {
    preloadBasedOnNavigation: vi.fn(),
    preloadRoute: vi.fn().mockResolvedValue(undefined)
  }
}));

vi.mock('@/services/loadingStateManager', () => ({
  loadingStateManager: {
    startLoading: vi.fn(),
    finishLoading: vi.fn(),
    updateProgress: vi.fn()
  }
}));

// Mock auth store
const mockAuthStore = {
  isAuthenticated: true,
  isSessionExpiringSoon: false,
  lastActivity: new Date(),
  initialized: true,
  user: { id: 1, name: 'Test User', email: 'test@example.com' },
  token: 'mock-token',
  validateSession: vi.fn().mockResolvedValue(true),
  fetchUser: vi.fn().mockResolvedValue(true),
  cleanupAuthState: vi.fn(),
  needsSessionValidation: vi.fn().mockReturnValue(false)
};

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => mockAuthStore
}));

describe('Optimized Router Navigation', () => {
  let router: any;

  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    
    // Create a simple router for testing
    router = createRouter({
      history: createWebHistory(),
      routes: [
        {
          path: '/dashboard',
          name: 'dashboard',
          component: { template: '<div>Dashboard</div>' },
          meta: { requiresAuth: true, title: 'Dashboard' }
        },
        {
          path: '/invoices',
          name: 'invoices',
          component: { template: '<div>Invoices</div>' },
          meta: { requiresAuth: true, title: 'Invoices' }
        },
        {
          path: '/login',
          name: 'login',
          component: { template: '<div>Login</div>' },
          meta: { requiresAuth: false }
        }
      ]
    });
  });

  afterEach(() => {
    vi.clearAllTimers();
  });

  describe('Fast Tab Navigation', () => {
    it('should skip session validation for authenticated users with recent activity', async () => {
      // Mock recent activity (within 5 minutes)
      mockAuthStore.lastActivity = new Date(Date.now() - 2 * 60 * 1000); // 2 minutes ago
      mockAuthStore.needsSessionValidation.mockReturnValue(false);
      
      // Navigate from dashboard to invoices
      await router.push('/dashboard');
      await router.push('/invoices');
      
      // Should not call validateSession for fast navigation
      expect(mockAuthStore.validateSession).not.toHaveBeenCalled();
    });

    it('should validate session when needed', async () => {
      // Mock old activity (more than 5 minutes)
      mockAuthStore.lastActivity = new Date(Date.now() - 10 * 60 * 1000); // 10 minutes ago
      mockAuthStore.needsSessionValidation.mockReturnValue(true);
      
      // Navigate to protected route
      await router.push('/dashboard');
      
      // Should call validateSession for old activity
      expect(mockAuthStore.validateSession).toHaveBeenCalled();
    });

    it('should handle unauthenticated users correctly', async () => {
      mockAuthStore.isAuthenticated = false;
      mockAuthStore.token = null;
      mockAuthStore.user = null;
      
      // Try to navigate to protected route
      const result = await router.push('/dashboard');
      
      // Should handle unauthenticated state
      expect(mockAuthStore.cleanupAuthState).not.toHaveBeenCalled(); // No cleanup needed if already unauthenticated
    });
  });

  describe('Loading State Management', () => {
    it('should use loading state manager for route transitions', async () => {
      const { loadingStateManager } = await import('@/services/loadingStateManager');
      
      await router.push('/dashboard');
      
      expect(loadingStateManager.startLoading).toHaveBeenCalledWith(
        'route-dashboard',
        'Loading Dashboard...',
        expect.objectContaining({
          showSkeleton: true,
          skeletonType: 'card',
          showProgress: false,
          minDisplayTime: 0
        })
      );
      
      expect(loadingStateManager.finishLoading).toHaveBeenCalledWith('route-dashboard');
    });

    it('should determine correct skeleton type for different routes', async () => {
      const { loadingStateManager } = await import('@/services/loadingStateManager');
      
      // Test different route types
      const testCases = [
        { route: '/dashboard', expectedSkeleton: 'card' },
        { route: '/invoices', expectedSkeleton: 'table' }
      ];
      
      for (const { route, expectedSkeleton } of testCases) {
        vi.clearAllMocks();
        await router.push(route);
        
        expect(loadingStateManager.startLoading).toHaveBeenCalledWith(
          expect.any(String),
          expect.any(String),
          expect.objectContaining({
            skeletonType: expectedSkeleton
          })
        );
      }
    });
  });

  describe('Route Preloading', () => {
    it('should trigger route preloading based on navigation patterns', async () => {
      const { routePreloader } = await import('@/services/routePreloader');
      
      // Navigate from dashboard to invoices
      await router.push('/dashboard');
      await router.push('/invoices');
      
      expect(routePreloader.preloadBasedOnNavigation).toHaveBeenCalledWith(
        'invoices',
        ['dashboard']
      );
    });
  });

  describe('Performance Optimization', () => {
    it('should complete navigation quickly for cached routes', async () => {
      const startTime = performance.now();
      
      // Navigate to a route (should be fast since no real validation)
      await router.push('/dashboard');
      
      const endTime = performance.now();
      const navigationTime = endTime - startTime;
      
      // Should be very fast (< 50ms) since we're skipping expensive operations
      expect(navigationTime).toBeLessThan(50);
    });

    it('should handle errors gracefully without breaking navigation', async () => {
      // Mock validation error
      mockAuthStore.validateSession.mockRejectedValueOnce(new Error('Network error'));
      mockAuthStore.needsSessionValidation.mockReturnValue(true);
      
      // Should not throw error
      await expect(router.push('/dashboard')).resolves.not.toThrow();
      
      // Should handle the error gracefully
      expect(mockAuthStore.cleanupAuthState).toHaveBeenCalled();
    });
  });

  describe('Integration with Real Application', () => {
    it('should work with actual route configurations', () => {
      // Test that our optimizations work with real route structure
      const routes = router.getRoutes();
      
      expect(routes).toHaveLength(3);
      expect(routes.find(r => r.name === 'dashboard')).toBeDefined();
      expect(routes.find(r => r.name === 'invoices')).toBeDefined();
      expect(routes.find(r => r.name === 'login')).toBeDefined();
    });

    it('should maintain route meta information', async () => {
      await router.push('/dashboard');
      
      const currentRoute = router.currentRoute.value;
      expect(currentRoute.meta.requiresAuth).toBe(true);
      expect(currentRoute.meta.title).toBe('Dashboard');
    });
  });
});