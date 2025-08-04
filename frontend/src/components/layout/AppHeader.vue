<template>
  <header
    class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Mobile menu button -->
        <div class="flex items-center lg:hidden">
          <button
            @click="$emit('toggleSidebar')"
            class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
          >
            <Bars3Icon class="h-6 w-6" />
          </button>
        </div>

        <!-- Page title -->
        <div class="flex-1 lg:flex-none">
          <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ pageTitle }}
          </h1>
        </div>

        <!-- Right side items -->
        <div class="flex items-center space-x-4 rtl:space-x-reverse">
          <!-- Language switcher -->
          <LanguageSwitcher />

          <!-- Notifications -->
          <button
            class="p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 relative"
          >
            <BellIcon class="h-6 w-6" />
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
              @click="userMenuOpen = !userMenuOpen"
              class="flex items-center space-x-2 rtl:space-x-reverse p-2 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <div
                class="h-8 w-8 bg-primary-500 rounded-full flex items-center justify-center"
              >
                <span class="text-sm font-medium text-white">
                  {{ userInitials }}
                </span>
              </div>
              <ChevronDownIcon class="h-4 w-4" />
            </button>

            <!-- User dropdown menu -->
            <div
              v-if="userMenuOpen"
              v-click-outside="() => (userMenuOpen = false)"
              :class="[
                'absolute mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50',
                isRTL ? 'left-0' : 'right-0',
              ]"
            >
              <div class="py-1">
                <a
                  href="#"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                  {{ $t("header.profile") }}
                </a>
                <a
                  href="#"
                  class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                  {{ $t("header.settings") }}
                </a>
                <hr class="my-1 border-gray-200 dark:border-gray-600" />
                <button
                  @click="logout"
                  class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                  {{ $t("header.logout") }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import {
  Bars3Icon,
  BellIcon,
  ChevronDownIcon,
} from "@heroicons/vue/24/outline";
import LanguageSwitcher from "../ui/LanguageSwitcher.vue";

defineEmits<{
  toggleSidebar: [];
}>();

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();

const userMenuOpen = ref(false);
const notificationCount = ref(3); // Mock notification count

const isRTL = computed(() => locale.value === "fa");

const pageTitle = computed(() => {
  const routeName = route.name as string;
  return t(`pages.${routeName}`, routeName);
});

const userInitials = computed(() => {
  // Mock user data - in real app, get from auth store
  return "JU";
});

const logout = () => {
  // TODO: Implement logout logic
  router.push("/login");
};

// Click outside directive
const vClickOutside = {
  beforeMount(
    el: HTMLElement & { clickOutsideEvent?: (event: Event) => void },
    binding: any,
  ) {
    el.clickOutsideEvent = (event: Event) => {
      if (!(el === event.target || el.contains(event.target as Node))) {
        binding.value();
      }
    };
    document.addEventListener("click", el.clickOutsideEvent);
  },
  unmounted(el: HTMLElement & { clickOutsideEvent?: (event: Event) => void }) {
    if (el.clickOutsideEvent) {
      document.removeEventListener("click", el.clickOutsideEvent);
    }
  },
};
</script>
