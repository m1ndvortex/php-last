<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <h1 class="text-3xl font-bold text-gray-900">
            {{ $t("dashboard.title") }}
          </h1>

          <div class="flex items-center space-x-4">
            <!-- Language Switcher -->
            <button @click="toggleLanguage" class="btn btn-secondary text-sm">
              {{ $t("language.current_language") }}
            </button>

            <!-- Logout Button -->
            <button @click="handleLogout" class="btn btn-secondary text-sm">
              {{ $t("auth.logout") }}
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Welcome Message -->
      <div class="px-4 py-6 sm:px-0">
        <div class="card mb-8">
          <h2 class="text-xl font-semibold text-gray-900 mb-2">
            {{ $t("dashboard.welcome") }}
          </h2>
          <p class="text-gray-600">
            {{ $t("app.description") }}
          </p>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <div v-for="kpi in kpis" :key="kpi.key" class="card">
            <div class="flex items-center">
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-600">
                  {{ $t(`dashboard.kpis.${kpi.key}`) }}
                </p>
                <p class="text-2xl font-bold text-gray-900">
                  {{ kpi.value }}
                </p>
              </div>
              <div class="flex-shrink-0">
                <div
                  class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center"
                >
                  <div class="w-4 h-4 bg-primary-600 rounded-full"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="nav in navigation"
            :key="nav.key"
            class="card hover:shadow-md transition-shadow cursor-pointer"
            @click="navigateTo(nav.route)"
          >
            <h3 class="text-lg font-medium text-gray-900 mb-2">
              {{ $t(`navigation.${nav.key}`) }}
            </h3>
            <p class="text-gray-600 text-sm">
              {{ nav.description }}
            </p>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";

const { locale } = useI18n();
const router = useRouter();

const kpis = ref([
  { key: "gold_sold", value: "12.5 kg" },
  { key: "total_profit", value: "$45,230" },
  { key: "average_price", value: "$1,850" },
  { key: "returns", value: "2.3%" },
  { key: "gross_margin", value: "35.2%" },
  { key: "net_margin", value: "28.7%" },
]);

const navigation = ref([
  {
    key: "customers",
    route: "/customers",
    description: "Manage customer relationships",
  },
  {
    key: "inventory",
    route: "/inventory",
    description: "Track jewelry inventory",
  },
  {
    key: "invoices",
    route: "/invoices",
    description: "Create and manage invoices",
  },
  {
    key: "accounting",
    route: "/accounting",
    description: "Financial management",
  },
  { key: "settings", route: "/settings", description: "System configuration" },
]);

const toggleLanguage = () => {
  locale.value = locale.value === "en" ? "fa" : "en";
};

const handleLogout = () => {
  // TODO: Implement actual logout logic
  router.push("/login");
};

const navigateTo = (route: string) => {
  // TODO: Implement navigation when routes are created
  console.log("Navigate to:", route);
};
</script>
