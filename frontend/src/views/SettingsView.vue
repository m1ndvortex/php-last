<template>
  <div class="space-y-6">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ $t("pages.settings") }}
        </h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
          {{ $t("settings.description") }}
        </p>
      </div>
    </div>

    <!-- Settings Tabs -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
          <button
            v-for="tab in settingsTabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2',
            ]"
          >
            <component :is="tab.icon" class="h-5 w-5" />
            <span>{{ $t(`settings.tabs.${tab.id}`) }}</span>
          </button>
        </nav>
      </div>

      <div class="p-6">
        <!-- Business Configuration -->
        <BusinessSettings v-if="activeTab === 'business'" />

        <!-- Role and Permission Management -->
        <RolePermissionSettings v-else-if="activeTab === 'roles'" />

        <!-- Message Templates -->
        <MessageTemplateSettings v-else-if="activeTab === 'templates'" />

        <!-- Theme and Language -->
        <ThemeLanguageSettings v-else-if="activeTab === 'appearance'" />

        <!-- Security Settings -->
        <SecuritySettings v-else-if="activeTab === 'security'" />

        <!-- Backup Settings -->
        <BackupSettings v-else-if="activeTab === 'backup'" />

        <!-- Audit Log Configuration -->
        <AuditLogSettings v-else-if="activeTab === 'audit'" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useSettingsStore } from "@/stores/settings";
import {
  BuildingOfficeIcon,
  UserGroupIcon,
  ChatBubbleLeftRightIcon,
  PaintBrushIcon,
  ShieldCheckIcon,
  CloudArrowUpIcon,
  DocumentTextIcon,
} from "@heroicons/vue/24/outline";

// Components
import BusinessSettings from "@/components/settings/BusinessSettings.vue";
import RolePermissionSettings from "@/components/settings/RolePermissionSettings.vue";
import MessageTemplateSettings from "@/components/settings/MessageTemplateSettings.vue";
import ThemeLanguageSettings from "@/components/settings/ThemeLanguageSettings.vue";
import SecuritySettings from "@/components/settings/SecuritySettings.vue";
import BackupSettings from "@/components/settings/BackupSettings.vue";
import AuditLogSettings from "@/components/settings/AuditLogSettings.vue";

const settingsStore = useSettingsStore();

// State
const activeTab = ref("business");

const settingsTabs = [
  {
    id: "business",
    name: "Business",
    icon: BuildingOfficeIcon,
  },
  {
    id: "roles",
    name: "Roles & Permissions",
    icon: UserGroupIcon,
  },
  {
    id: "templates",
    name: "Message Templates",
    icon: ChatBubbleLeftRightIcon,
  },
  {
    id: "appearance",
    name: "Theme & Language",
    icon: PaintBrushIcon,
  },
  {
    id: "security",
    name: "Security",
    icon: ShieldCheckIcon,
  },
  {
    id: "backup",
    name: "Backup",
    icon: CloudArrowUpIcon,
  },
  {
    id: "audit",
    name: "Audit Logs",
    icon: DocumentTextIcon,
  },
];

onMounted(async () => {
  await settingsStore.fetchSettings();
});
</script>
