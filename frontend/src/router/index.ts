import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";

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

// Enhanced navigation guard for authentication with comprehensive session validation
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);

  // Set loading state for authentication checks
  authStore.isLoading = true;

  try {
    // Initialize auth store if not already done
    if (!authStore.initialized) {
      await authStore.initialize();
    }

    // Pre-route session validation for all protected routes
    if (requiresAuth) {
      // If user appears authenticated, validate session first
      if (authStore.isAuthenticated) {
        try {
          const sessionValid = await authStore.validateSession();
          if (!sessionValid) {
            // Session expired, clear auth state and redirect to login
            await authStore.cleanupAuthState();
            const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
            next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
            return;
          }
        } catch (error) {
          console.error("Pre-route session validation failed:", error);
          // Clear auth state on validation error
          await authStore.cleanupAuthState();
          const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
          next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
          return;
        }
      }

      // If not authenticated after session validation
      if (!authStore.isAuthenticated) {
        // If we have a token but no user data, try to fetch user
        if (authStore.token && !authStore.user) {
          try {
            const userFetched = await authStore.fetchUser();
            if (userFetched && authStore.isAuthenticated) {
              // Validate the newly fetched session
              const sessionValid = await authStore.validateSession();
              if (!sessionValid) {
                await authStore.cleanupAuthState();
                const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
                next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
                return;
              }
            } else {
              // Failed to fetch user, redirect to login
              const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
              next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
              return;
            }
          } catch (error) {
            console.error("Failed to fetch user during route guard:", error);
            // Clear invalid token and redirect
            await authStore.cleanupAuthState();
            const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
            next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
            return;
          }
        } else {
          // No token or user, redirect to login with return URL
          const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
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
      next(returnUrl);
      return;
    }

    // Handle return URL redirect after successful login
    if (to.name === "login" && to.query.returnUrl && authStore.isAuthenticated) {
      const returnUrl = decodeURIComponent(to.query.returnUrl as string);
      // Validate the return URL to prevent open redirects
      if (returnUrl.startsWith("/") && !returnUrl.startsWith("//")) {
        next(returnUrl);
        return;
      } else {
        next("/dashboard");
        return;
      }
    }

    // All checks passed, proceed to route
    next();

  } catch (error) {
    console.error("Router guard error:", error);
    // On any unexpected error, redirect to login for protected routes
    if (requiresAuth) {
      await authStore.cleanupAuthState();
      const returnUrl = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
      next(`/login?returnUrl=${encodeURIComponent(returnUrl)}`);
    } else {
      next();
    }
  } finally {
    // Clear loading state
    authStore.isLoading = false;
  }
});

// Set page title
router.afterEach((to) => {
  const title = to.meta.title as string;
  if (title) {
    document.title = `${title} - Jewelry Platform`;
  } else {
    document.title = "Jewelry Platform";
  }
});

export default router;
