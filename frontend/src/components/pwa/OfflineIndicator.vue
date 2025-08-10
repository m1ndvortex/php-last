<template>
  <div class="fixed top-4 right-4 z-50">
    <!-- Offline Indicator -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 transform translate-y-2"
      enter-to-class="opacity-100 transform translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 transform translate-y-0"
      leave-to-class="opacity-0 transform translate-y-2"
    >
      <div
        v-if="!isOnline"
        class="bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2"
        :class="{ 'rtl:space-x-reverse': isRTL }"
      >
        <WifiIcon class="h-5 w-5" />
        <span class="text-sm font-medium">{{ t("offline.indicator") }}</span>
      </div>
    </Transition>

    <!-- Sync Status -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 transform translate-y-2"
      enter-to-class="opacity-100 transform translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 transform translate-y-0"
      leave-to-class="opacity-0 transform translate-y-2"
    >
      <div
        v-if="syncStatus.isVisible"
        class="mt-2 px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2"
        :class="[syncStatusClasses, { 'rtl:space-x-reverse': isRTL }]"
      >
        <component
          :is="syncStatusIcon"
          class="h-5 w-5"
          :class="syncIconClasses"
        />
        <div class="flex-1">
          <div class="text-sm font-medium">{{ syncStatus.message }}</div>
          <div
            v-if="syncStatus.progress !== null"
            class="text-xs opacity-75 mt-1"
          >
            {{
              t("sync.progress", {
                current: syncStatus.progress.current,
                total: syncStatus.progress.total,
              })
            }}
          </div>
        </div>
        <button
          v-if="syncStatus.type === 'error'"
          @click="retrySyncOperation"
          class="text-xs underline hover:no-underline"
        >
          {{ t("sync.retry") }}
        </button>
      </div>
    </Transition>

    <!-- Storage Usage Warning -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 transform translate-y-2"
      enter-to-class="opacity-100 transform translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 transform translate-y-0"
      leave-to-class="opacity-0 transform translate-y-2"
    >
      <div
        v-if="storageWarning.show"
        class="mt-2 bg-yellow-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2"
        :class="{ 'rtl:space-x-reverse': isRTL }"
      >
        <ExclamationTriangleIcon class="h-5 w-5" />
        <div class="flex-1">
          <div class="text-sm font-medium">{{ t("storage.warning") }}</div>
          <div class="text-xs opacity-75">
            {{ t("storage.usage", { percentage: storageWarning.percentage }) }}
          </div>
        </div>
        <button
          @click="clearOldData"
          class="text-xs underline hover:no-underline"
        >
          {{ t("storage.clear") }}
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import { useLocale } from "@/composables/useLocale";
import { usePWA } from "@/composables/usePWA";
import {
  WifiIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  ClockIcon,
} from "@heroicons/vue/24/outline";

const { t } = useI18n();
const { isRTL } = useLocale();
const { isOnline, syncStatus, storageInfo, retrySyncOperation, clearOldData } =
  usePWA();

const storageWarning = computed(() => ({
  show: storageInfo.percentage > 80,
  percentage: Math.round(storageInfo.percentage),
}));

const syncStatusClasses = computed(() => {
  switch (syncStatus.type) {
    case "syncing":
      return "bg-blue-500 text-white";
    case "success":
      return "bg-green-500 text-white";
    case "error":
      return "bg-red-500 text-white";
    case "pending":
      return "bg-yellow-500 text-white";
    default:
      return "bg-gray-500 text-white";
  }
});

const syncStatusIcon = computed(() => {
  switch (syncStatus.type) {
    case "syncing":
      return ArrowPathIcon;
    case "success":
      return CheckCircleIcon;
    case "error":
      return ExclamationCircleIcon;
    case "pending":
      return ClockIcon;
    default:
      return ArrowPathIcon;
  }
});

const syncIconClasses = computed(() => ({
  "animate-spin": syncStatus.type === "syncing",
}));
</script>
