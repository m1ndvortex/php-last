<template>
  <Teleport to="body">
    <!-- Install Banner -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 transform translate-y-full"
      enter-to-class="opacity-100 transform translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 transform translate-y-0"
      leave-to-class="opacity-0 transform translate-y-full"
    >
      <div
        v-if="showInstallBanner && !isInstalled"
        class="fixed bottom-0 left-0 right-0 z-50 bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg"
      >
        <div class="max-w-7xl mx-auto px-4 py-3">
          <div class="flex items-center justify-between">
            <div
              class="flex items-center space-x-3"
              :class="{ 'rtl:space-x-reverse': isRTL }"
            >
              <div class="flex-shrink-0">
                <DevicePhoneMobileIcon class="h-8 w-8" />
              </div>
              <div class="flex-1">
                <h3 class="text-sm font-semibold">
                  {{ t("pwa.install.title") }}
                </h3>
                <p class="text-xs opacity-90 mt-1">
                  {{ t("pwa.install.description") }}
                </p>
              </div>
            </div>

            <div
              class="flex items-center space-x-2"
              :class="{ 'rtl:space-x-reverse': isRTL }"
            >
              <button
                @click="installApp"
                class="bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors"
              >
                {{ t("pwa.install.install") }}
              </button>
              <button
                @click="dismissInstallBanner"
                class="text-white hover:text-gray-200 p-1"
              >
                <XMarkIcon class="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Install Modal -->
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showInstallModal"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
      >
        <div
          class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
        >
          <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="closeInstallModal"
          ></div>

          <span
            class="hidden sm:inline-block sm:align-middle sm:h-screen"
            aria-hidden="true"
            >&#8203;</span
          >

          <div
            class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
          >
            <div>
              <div
                class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900"
              >
                <DevicePhoneMobileIcon
                  class="h-6 w-6 text-blue-600 dark:text-blue-400"
                />
              </div>
              <div class="mt-3 text-center sm:mt-5">
                <h3
                  class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
                  id="modal-title"
                >
                  {{ t("pwa.install.modalTitle") }}
                </h3>
                <div class="mt-2">
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ t("pwa.install.modalDescription") }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Features List -->
            <div class="mt-6">
              <h4
                class="text-sm font-medium text-gray-900 dark:text-white mb-3"
              >
                {{ t("pwa.install.features") }}
              </h4>
              <ul class="space-y-2">
                <li
                  class="flex items-center text-sm text-gray-600 dark:text-gray-300"
                >
                  <CheckIcon
                    class="h-4 w-4 text-green-500 mr-2"
                    :class="{ 'rtl:mr-0 rtl:ml-2': isRTL }"
                  />
                  {{ t("pwa.install.feature1") }}
                </li>
                <li
                  class="flex items-center text-sm text-gray-600 dark:text-gray-300"
                >
                  <CheckIcon
                    class="h-4 w-4 text-green-500 mr-2"
                    :class="{ 'rtl:mr-0 rtl:ml-2': isRTL }"
                  />
                  {{ t("pwa.install.feature2") }}
                </li>
                <li
                  class="flex items-center text-sm text-gray-600 dark:text-gray-300"
                >
                  <CheckIcon
                    class="h-4 w-4 text-green-500 mr-2"
                    :class="{ 'rtl:mr-0 rtl:ml-2': isRTL }"
                  />
                  {{ t("pwa.install.feature3") }}
                </li>
                <li
                  class="flex items-center text-sm text-gray-600 dark:text-gray-300"
                >
                  <CheckIcon
                    class="h-4 w-4 text-green-500 mr-2"
                    :class="{ 'rtl:mr-0 rtl:ml-2': isRTL }"
                  />
                  {{ t("pwa.install.feature4") }}
                </li>
              </ul>
            </div>

            <!-- Installation Instructions -->
            <div
              v-if="installInstructions"
              class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
            >
              <h4
                class="text-sm font-medium text-gray-900 dark:text-white mb-2"
              >
                {{ t("pwa.install.instructions") }}
              </h4>
              <div
                class="text-sm text-gray-600 dark:text-gray-300"
                v-html="installInstructions"
              ></div>
            </div>

            <div
              class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense"
            >
              <button
                @click="installApp"
                :disabled="!canInstall"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:col-start-2 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ t("pwa.install.install") }}
              </button>
              <button
                @click="closeInstallModal"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm"
              >
                {{ t("pwa.install.later") }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";
import { useLocale } from "@/composables/useLocale";
import {
  DevicePhoneMobileIcon,
  XMarkIcon,
  CheckIcon,
} from "@heroicons/vue/24/outline";

const { t } = useI18n();
const { isRTL } = useLocale();

const showInstallBanner = ref(false);
const showInstallModal = ref(false);
const isInstalled = ref(false);
const canInstall = ref(false);
let deferredPrompt: any = null;

const installInstructions = computed(() => {
  const userAgent = navigator.userAgent.toLowerCase();

  if (userAgent.includes("chrome") && !userAgent.includes("edg")) {
    return t("pwa.install.chromeInstructions");
  } else if (userAgent.includes("firefox")) {
    return t("pwa.install.firefoxInstructions");
  } else if (userAgent.includes("safari") && !userAgent.includes("chrome")) {
    return t("pwa.install.safariInstructions");
  } else if (userAgent.includes("edg")) {
    return t("pwa.install.edgeInstructions");
  }

  return t("pwa.install.genericInstructions");
});

const checkIfInstalled = () => {
  // Check if app is running in standalone mode
  if (window.matchMedia("(display-mode: standalone)").matches) {
    isInstalled.value = true;
    return;
  }

  // Check if app is installed on iOS
  if ((navigator as any).standalone === true) {
    isInstalled.value = true;
    return;
  }

  // Check if app is installed on Android
  if (document.referrer.includes("android-app://")) {
    isInstalled.value = true;
    return;
  }
};

const handleBeforeInstallPrompt = (e: Event) => {
  // Prevent the mini-infobar from appearing on mobile
  e.preventDefault();

  // Stash the event so it can be triggered later
  deferredPrompt = e;
  canInstall.value = true;

  // Show install banner after a delay
  setTimeout(() => {
    if (!isInstalled.value && !localStorage.getItem("installBannerDismissed")) {
      showInstallBanner.value = true;
    }
  }, 10000); // Show after 10 seconds
};

const handleAppInstalled = () => {
  console.log("PWA was installed");
  isInstalled.value = true;
  showInstallBanner.value = false;
  showInstallModal.value = false;
  deferredPrompt = null;
  canInstall.value = false;
};

const installApp = async () => {
  if (!deferredPrompt) {
    // If no deferred prompt, show manual installation instructions
    showInstallModal.value = true;
    return;
  }

  try {
    // Show the install prompt
    deferredPrompt.prompt();

    // Wait for the user to respond to the prompt
    const { outcome } = await deferredPrompt.userChoice;

    if (outcome === "accepted") {
      console.log("User accepted the install prompt");
    } else {
      console.log("User dismissed the install prompt");
    }

    // Clear the deferred prompt
    deferredPrompt = null;
    canInstall.value = false;
    showInstallBanner.value = false;
  } catch (error) {
    console.error("Error during app installation:", error);
    // Fallback to manual instructions
    showInstallModal.value = true;
  }
};

const dismissInstallBanner = () => {
  showInstallBanner.value = false;
  localStorage.setItem("installBannerDismissed", "true");

  // Show again after 7 days
  setTimeout(
    () => {
      localStorage.removeItem("installBannerDismissed");
    },
    7 * 24 * 60 * 60 * 1000,
  );
};

const closeInstallModal = () => {
  showInstallModal.value = false;
};

const showInstallPrompt = () => {
  if (isInstalled.value) return;

  if (canInstall.value) {
    installApp();
  } else {
    showInstallModal.value = true;
  }
};

// Expose method for external use
defineExpose({
  showInstallPrompt,
});

onMounted(() => {
  checkIfInstalled();

  // Listen for the beforeinstallprompt event
  window.addEventListener("beforeinstallprompt", handleBeforeInstallPrompt);

  // Listen for the appinstalled event
  window.addEventListener("appinstalled", handleAppInstalled);

  // Check for display mode changes
  const mediaQuery = window.matchMedia("(display-mode: standalone)");
  mediaQuery.addEventListener("change", checkIfInstalled);
});

onUnmounted(() => {
  window.removeEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
  window.removeEventListener("appinstalled", handleAppInstalled);
});
</script>
