import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { routePreloader } from "@/services/routePreloader";
import { loadingStateManager } from "@/services/loadingStateManager";

// Lazy load the main layout
const AppLayout = () => import("@/components/layout/AppLayout.vue");

// Preload critical routes for better UX
const preloadRoute = (routeImport: () => Promise<any>) => {
  // Preload on idle or after a short delay
  if ("requestIdleCallback" in window) {
    requestIdleCallback(() => routeImport());
  } else {
    setTimeout(() => routeImport(), 100);
  }
  return routeImport;
};

// Critical routes that should be preloaded
const DashboardView = preloadRoute(() => import("@/views/DashboardView.vue"));
const LoginView = preloadRoute(() => import("@/views/LoginView.vue"));

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/login",
      name: "login",
      component: LoginView,
      meta: {
        requiresAuth: false,
        layout: "auth",
        preload: true,
      },
    },
    {
      path: "/forgot-password",
      name: "forgot-password",
      component: () => import("@/views/ForgotPasswordView.vue"),
      meta: {
        requiresAuth: false,
        layout: "auth",
      },
    },
    {
      path: "/",
      component: AppLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: "",
          redirect: "/dashboard",
        },
        {
          path: "dashboard",
          name: "dashboard",
          component: DashboardView,
          meta: {
            requiresAuth: true,
            title: "Dashboard",
            preload: true,
            roles: ["admin", "manager", "user"], // All authenticated users can access dashboard
          },
        },
        {
          path: "invoices",
          name: "invoices",
          component: () => import("@/views/InvoicesView.vue"),
          meta: {
            requiresAuth: true,
            title: "Invoices",
            roles: ["admin", "manager", "user"], // All authenticated users can access invoices
          },
        },
        {
          path: "inventory",
          name: "inventory",
          component: () => import("@/views/InventoryView.vue"),
          meta: {
            requiresAuth: true,
            title: "Inventory",
            roles: ["admin", "manager", "user"], // All authenticated users can access inventory
          },
        },
        {
          path: "customers",
          name: "customers",
          component: () => import("@/views/CustomersView.vue"),
          meta: {
            requiresAuth: true,
            title: "Customers",
            roles: ["admin", "manager", "user"], // All authenticated users can access customers
          },
        },
        {
          path: "accounting",
          name: "accounting",
          component: () => import("@/views/AccountingView.vue"),
          meta: {
            requiresAuth: true,
            title: "Accounting",
            roles: ["admin", "manager"], // Only admin and manager can access accounting
          },
        },
        {
          path: "reports",
          name: "reports",
          component: () => import("@/views/ReportsView.vue"),
          meta: {
            requiresAuth: true,
            title: "Reports",
            roles: ["admin", "manager"], // Only admin and manager can access reports
          },
        },
        {
          path: "profile",
          name: "profile",
          component: () => import("@/views/ProfileView.vue"),
          meta: {
            requiresAuth: true,
            title: "Profile",
            roles: ["admin", "manager", "user"], // All authenticated users can access profile
          },
        },
        {
          path: "settings",
          name: "settings",
          component: () => import("@/views/SettingsView.vue"),
          meta: {
            requiresAuth: true,
            title: "Settings",
            roles: ["admin"], // Only admin can access settings
          },
        },
      ],
    },
    {
      path: "/:pathMatch(.*)*",
      name: "not-found",
      component: () => import("@/views/NotFoundView.vue"),
    },
  ],
});

// Optimized navigation guard with minimal authentication checks for seamless tab navigation
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);

  // Start loading state for the route
  const loadingContext = `route-${to.name}`;
  loadingStateManager.startLoading(
    loadingContext,
    `Loading ${to.meta?.title || to.name}...`,
    {
      showSkeleton: true,
      skeletonType: getSkeletonTypeForRoute(to.name as string),
      showProgress: false, // Don't show progress for fast tab switches
      minDisplayTime: 0 // Allow instant loading for cached routes
    }
  );

  try {
    // Initialize auth store if not already done (only on first load)
    if (!authStore.initialized) {
      await authStore.initialize();
    }

    // Optimized authentication check for protected routes
    if (requiresAuth) {
      // Fast path: If user is authenticated and session is not expired, skip validation
      if (authStore.isAuthenticated && !authStore.isSessionExpiringSoon) {
        // Skip expensive session validation for fast tab switching
        // Only validate if session is expiring soon or if it's been a while since last validation
        const lastValidation = authStore.lastActivity;
        const timeSinceLastValidation = Date.now() - lastValidation.getTime();
        const shouldValidate = timeSinceLastValidation > 5 * 60 * 1000; // 5 minutes
        
        if (shouldValidate) {
          try {
            const sessionValid = await authStore.validateSession();
            if (!sessionValid) {
              await authStore.cleanupAuthState();
              const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
              loadingStateManager.finishLoading(loadingContext);
              next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
              return;
            }
          } catch (error) {
            console.error("Session validation failed:", error);
            await authStore.cleanupAuthState();
            const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
            loadingStateManager.finishLoading(loadingContext);
            next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
            return;
          }
        }
      } else if (!authStore.isAuthenticated) {

        // If we have a token but no user data, try to fetch user
        if (authStore.token && !authStore.user) {
          try {
            const userFetched = await authStore.fetchUser();
            if (!userFetched || !authStore.isAuthenticated) {
              // Failed to fetch user, redirect to login
              const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
              loadingStateManager.finishLoading(loadingContext);
              next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
              return;
            }
          } catch (error) {
            console.error("Failed to fetch user during route guard:", error);
            // Clear invalid token and redirect
            await authStore.cleanupAuthState();
            const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
            loadingStateManager.finishLoading(loadingContext);
            next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
            return;
          }
        } else {
          // No token or user, redirect to login with return URL
          const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
          loadingStateManager.finishLoading(loadingContext);
          next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
          return;
        }
      }

      // Role-based access control
      if (to.meta.roles && authStore.user) {
        const userRole = authStore.user.role;
        const allowedRoles = to.meta.roles as string[];

        // Owner role has access to everything
        if (userRole === 'owner') {
          // Owner can access all routes
        } else if (!userRole || !allowedRoles.includes(userRole)) {
          console.warn(`Access denied: User role '${userRole}' not in allowed roles:`, allowedRoles);
          // Redirect to dashboard with error message
          next("/dashboard?error=access_denied");
          return;
        }
      }

      // Permission-based access control (if specified)
      if (to.meta.permissions && authStore.user) {
        const requiredPermissions = to.meta.permissions as string[];
        // TODO: Implement permission checking when user permissions are available
        // For now, we'll skip this check
      }
    }

    // If user is authenticated and trying to access auth pages (login, forgot-password)
    if ((to.name === "login" || to.name === "forgot-password") && authStore.isAuthenticated) {
      // Check if there's a return URL to redirect to
      const returnUrl = (to.query.returnUrl as string) || "/dashboard";
      loadingStateManager.finishLoading(loadingContext);
      next(returnUrl);
      return;
    }

    // Handle return URL redirect after successful login
    if (to.name === "login" && to.query.returnUrl && authStore.isAuthenticated) {
      const returnUrl = decodeURIComponent(to.query.returnUrl as string);
      // Validate the return URL to prevent open redirects
      if (returnUrl.startsWith("/") && !returnUrl.startsWith("//")) {
        loadingStateManager.finishLoading(loadingContext);
        next(returnUrl);
        return;
      } else {
        loadingStateManager.finishLoading(loadingContext);
        next("/dashboard");
        return;
      }
    }

    // Preload next likely routes for faster navigation
    if (from.name && to.name) {
      routePreloader.preloadBasedOnNavigation(to.name as string, [from.name as string]);
    }

    // All checks passed, proceed to route
    loadingStateManager.finishLoading(loadingContext);
    next();

  } catch (error) {
    console.error("Router guard error:", error);
    loadingStateManager.finishLoading(loadingContext, error as Error);
    
    // On any unexpected error, redirect to login for protected routes
    if (requiresAuth) {
      await authStore.cleanupAuthState();
      const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
      next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
    } else {
      next();
    }
  }
});

// Helper function to determine skeleton type for routes
function getSkeletonTypeForRoute(routeName: string): 'card' | 'table' | 'list' | 'chart' {
  const skeletonMap: Record<string, 'card' | 'table' | 'list' | 'chart'> = {
    'dashboard': 'card',
    'invoices': 'table',
    'inventory': 'table',
    'customers': 'table',
    'reports': 'chart',
    'accounting': 'table',
    'settings': 'list'
  };
  return skeletonMap[routeName] || 'card';
}

// Set page title and track navigation performance
router.afterEach((to, from) => {
  const title = to.meta.title as string;
  if (title) {
    document.title = `${title} - Jewelry Platform`;
  } else {
    document.title = "Jewelry Platform";
  }

  // Track navigation performance for optimization
  if (from.name && to.name) {
    console.log(`🚀 Navigation: ${from.name} → ${to.name}`);
  }
});

export default router;
