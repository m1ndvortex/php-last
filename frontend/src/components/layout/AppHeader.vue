<template>
  <header
    class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700"
  >
    <!-- Top bar with logo and user controls -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Left side: Logo and mobile menu -->
          <div class="flex items-center">
            <!-- Mobile menu button -->
            <button
              @click="$emit('toggleSidebar')"
              class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 mr-3 rtl:mr-0 rtl:ml-3"
            >
              <Bars3Icon class="h-6 w-6" />
            </button>

            <!-- Logo -->
            <div class="flex-shrink-0">
              <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ $t("app.name") }}
              </h1>
            </div>
          </div>

          <!-- Right side items -->
          <div class="flex items-center space-x-4 rtl:space-x-reverse">
            <!-- Language switcher -->
            <LanguageSwitcher />

            <!-- Theme toggle -->
            <ThemeToggle />

            <!-- Notifications -->
            <button
              class="p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 relative"
            >
              <BellIcon class="h-5 w-5" />
              <span
                v-if="notificationCount > 0"
                class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
              >
                {{ notificationCount > 9 ? "9+" : notificationCount }}
              </span>
            </button>

            <!-- User menu -->
            <div class="relative">
              <button
                @click="toggleUserMenu"
                class="flex items-center space-x-2 rtl:space-x-reverse p-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors"
              >
                <div
                  class="h-8 w-8 bg-primary-500 rounded-full flex items-center justify-center"
                >
                  <span class="text-sm font-medium text-white">
                    {{ userInitials }}
                  </span>
                </div>
                <ChevronDownIcon
                  :class="[
                    'h-4 w-4 transition-transform duration-200',
                    userMenuOpen ? 'rotate-180' : '',
                  ]"
                />
              </button>

              <!-- User dropdown menu -->
              <Transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <div
                  v-if="userMenuOpen"
                  @click.stop
                  :class="[
                    'absolute top-full mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 origin-top-right',
                    isRTL ? 'left-0' : 'right-0',
                  ]"
                  style="z-index: 9999"
                >
                  <div class="py-1">
                    <router-link
                      to="/profile"
                      @click="userMenuOpen = false"
                      class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                      {{ $t("header.profile") }}
                    </router-link>
                    <router-link
                      to="/settings"
                      @click="userMenuOpen = false"
                      class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                      {{ $t("header.settings") }}
                    </router-link>
                    <hr class="my-1 border-gray-200 dark:border-gray-600" />
                    <button
                      @click="handleLogout"
                      class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                      {{ $t("header.logout") }}
                    </button>
                  </div>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation bar -->
    <div class="hidden lg:block bg-gray-50 dark:bg-gray-700/50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex space-x-8 rtl:space-x-reverse">
          <router-link
            v-for="item in navigation"
            :key="item.name"
            :to="item.href"
            :class="[
              'flex items-center px-3 py-3 text-sm font-medium border-b-2 transition-all duration-200 hover:bg-white/50 dark:hover:bg-gray-600/50 rounded-t-md',
              $route.path === item.href
                ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-white dark:bg-gray-800'
                : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300 dark:text-gray-300 dark:hover:text-gray-100',
            ]"
          >
            <component
              :is="item.icon"
              :class="[
                'flex-shrink-0 h-5 w-5 transition-colors duration-200',
                isRTL ? 'ml-2' : 'mr-2',
                $route.path === item.href
                  ? 'text-primary-500'
                  : 'text-gray-400 group-hover:text-gray-500',
              ]"
            />
            {{ $t(item.name) }}
          </router-link>
        </nav>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import {
  Bars3Icon,
  BellIcon,
  ChevronDownIcon,
  HomeIcon,
  DocumentTextIcon,
  CubeIcon,
  UsersIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  CogIcon,
} from "@heroicons/vue/24/outline";
import LanguageSwitcher from "../ui/LanguageSwitcher.vue";
import ThemeToggle from "../ui/ThemeToggle.vue";
import { useAuthStore } from "@/stores/auth";

defineEmits<{
  toggleSidebar: [];
}>();

const { locale } = useI18n();
// const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const userMenuOpen = ref(false);
const notificationCount = ref(3); // Mock notification count

const isRTL = computed(() => locale.value === "fa");

const navigation = [
  { name: "nav.dashboard", href: "/dashboard", icon: HomeIcon },
  { name: "nav.invoices", href: "/invoices", icon: DocumentTextIcon },
  { name: "nav.inventory", href: "/inventory", icon: CubeIcon },
  { name: "nav.customers", href: "/customers", icon: UsersIcon },
  { name: "nav.accounting", href: "/accounting", icon: CurrencyDollarIcon },
  { name: "nav.reports", href: "/reports", icon: ChartBarIcon },
  { name: "nav.settings", href: "/settings", icon: CogIcon },
];

const userInitials = computed(() => {
  return authStore.userInitials || "JU";
});

const toggleUserMenu = (event: Event) => {
  event.stopPropagation();
  userMenuOpen.value = !userMenuOpen.value;
  console.log("User menu toggled:", userMenuOpen.value);
};

const closeUserMenu = () => {
  userMenuOpen.value = false;
  console.log("User menu closed");
};

const handleLogout = async () => {
  userMenuOpen.value = false;
  console.log("🚀 LOGOUT CLICKED - Starting logout process");
  
  try {
    // Immediate local cleanup for instant logout
    console.log("🧹 Clearing local storage and session storage");
    localStorage.clear();
    sessionStorage.clear();
    
    // Clear cookies
    console.log("🍪 Clearing cookies");
    document.cookie.split(";").forEach(function(c) { 
      document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
    });
    
    // Clear auth store state
    console.log("🔐 Clearing auth store state");
    authStore.cleanupAuthState();
    
    // Broadcast logout to other tabs
    console.log("📡 Broadcasting logout to other tabs");
    window.dispatchEvent(new CustomEvent('cross-tab-logout', {
      detail: { 
        initiatingTab: 'current',
        reason: 'user_initiated',
        timestamp: new Date().toISOString()
      }
    }));
    
    console.log("✅ Logout completed - redirecting to login");
    
    // Force redirect to login
    window.location.href = '/login';
    
  } catch (error) {
    console.error("❌ Logout error:", error);
    // Force redirect even on error
    window.location.href = '/login';
  }
};

// Handle clicks outside the dropdown
const handleClickOutside = () => {
  if (userMenuOpen.value) {
    closeUserMenu();
  }
};

// Add global click listener when component mounts
onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

// Remove global click listener when component unmounts
onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>
