import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import AppLayout from "@/components/layout/AppLayout.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/login",
      name: "login",
      component: () => import("../views/LoginView.vue"),
      meta: {
        requiresAuth: false,
        layout: "auth",
      },
    },
    {
      path: "/forgot-password",
      name: "forgot-password",
      component: () => import("../views/ForgotPasswordView.vue"),
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
          component: () => import("../views/DashboardView.vue"),
          meta: {
            requiresAuth: true,
            title: "Dashboard",
          },
        },
        {
          path: "invoices",
          name: "invoices",
          component: () => import("../views/InvoicesView.vue"),
          meta: {
            requiresAuth: true,
            title: "Invoices",
          },
        },
        {
          path: "inventory",
          name: "inventory",
          component: () => import("../views/InventoryView.vue"),
          meta: {
            requiresAuth: true,
            title: "Inventory",
          },
        },
        {
          path: "customers",
          name: "customers",
          component: () => import("../views/CustomersView.vue"),
          meta: {
            requiresAuth: true,
            title: "Customers",
          },
        },
        {
          path: "accounting",
          name: "accounting",
          component: () => import("../views/AccountingView.vue"),
          meta: {
            requiresAuth: true,
            title: "Accounting",
          },
        },
        {
          path: "reports",
          name: "reports",
          component: () => import("../views/ReportsView.vue"),
          meta: {
            requiresAuth: true,
            title: "Reports",
          },
        },
        {
          path: "profile",
          name: "profile",
          component: () => import("../views/ProfileView.vue"),
          meta: {
            requiresAuth: true,
            title: "Profile",
          },
        },
        {
          path: "settings",
          name: "settings",
          component: () => import("../views/SettingsView.vue"),
          meta: {
            requiresAuth: true,
            title: "Settings",
          },
        },
      ],
    },
    {
      path: "/:pathMatch(.*)*",
      name: "not-found",
      component: () => import("../views/NotFoundView.vue"),
    },
  ],
});

// Navigation guard for authentication
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);

  // Initialize auth store if not already done
  if (!authStore.initialized) {
    await authStore.initialize();
  }

  // If route requires auth and user is not authenticated
  if (requiresAuth && !authStore.isAuthenticated) {
    // If we have a token but no user data, try to fetch user
    if (authStore.token && !authStore.user) {
      try {
        await authStore.fetchUser();
        if (authStore.isAuthenticated) {
          next();
          return;
        }
      } catch (error) {
        console.error("Failed to fetch user:", error);
        // Clear invalid token
        authStore.logout();
      }
    }

    // Store the intended route for redirect after login
    const redirectPath = to.fullPath !== "/login" ? to.fullPath : "/dashboard";
    next(`/login?redirect=${encodeURIComponent(redirectPath)}`);
    return;
  }

  // If user is authenticated and trying to access login page
  if (to.name === "login" && authStore.isAuthenticated) {
    next("/dashboard");
    return;
  }

  // Check for role-based access if specified
  if (to.meta.roles && authStore.user) {
    const userRole = authStore.user.role;
    const allowedRoles = to.meta.roles as string[];

    if (!allowedRoles.includes(userRole || "")) {
      // Redirect to unauthorized page or dashboard
      next("/dashboard");
      return;
    }
  }

  next();
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
