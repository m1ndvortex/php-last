<template>
  <!-- Mobile sidebar -->
  <div
    :class="[
      'fixed inset-y-0 z-50 bg-white dark:bg-gray-800 shadow-lg transform transition-all duration-300 ease-in-out w-64',
      open ? 'translate-x-0' : '-translate-x-full',
      isRTL ? 'right-0 left-auto' : 'left-0 right-auto',
    ]"
  >
    <div
      class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700"
    >
      <!-- Logo -->
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ $t("app.name") }}
          </h1>
        </div>
      </div>

      <!-- Close button (mobile only) -->
      <button
        @click="$emit('close')"
        class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
      >
        <XMarkIcon class="h-6 w-6" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="mt-5 px-2 space-y-1">
      <router-link
        v-for="item in navigation"
        :key="item.name"
        :to="item.href"
        @click="$emit('close')"
        :class="[
          'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors duration-150',
          $route.path === item.href
            ? 'bg-primary-100 text-primary-900 dark:bg-primary-900 dark:text-primary-100'
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white',
        ]"
      >
        <component
          :is="item.icon"
          :class="[
            'flex-shrink-0 h-6 w-6 transition-colors duration-150',
            isRTL ? 'ml-3' : 'mr-3',
            $route.path === item.href
              ? 'text-primary-500'
              : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300',
          ]"
        />
        {{ $t(item.name) }}
      </router-link>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import {
  XMarkIcon,
  HomeIcon,
  DocumentTextIcon,
  CubeIcon,
  UsersIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  CogIcon,
} from "@heroicons/vue/24/outline";

interface Props {
  open: boolean;
}

defineProps<Props>();
defineEmits<{
  close: [];
}>();

const { locale } = useI18n();

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
</script>
