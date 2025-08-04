<template>
  <div class="relative">
    <button
      @click="dropdownOpen = !dropdownOpen"
      class="flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors duration-150"
    >
      <LanguageIcon class="h-5 w-5" />
      <span>{{ currentLanguage.name }}</span>
      <ChevronDownIcon class="h-4 w-4" />
    </button>

    <!-- Language dropdown -->
    <div
      v-if="dropdownOpen"
      v-click-outside="() => (dropdownOpen = false)"
      :class="[
        'absolute mt-2 w-40 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50',
        isRTL ? 'left-0' : 'right-0',
      ]"
    >
      <div class="py-1">
        <button
          v-for="language in languages"
          :key="language.code"
          @click="switchLanguage(language.code)"
          :class="[
            'w-full text-left px-4 py-2 text-sm transition-colors duration-150',
            locale === language.code
              ? 'bg-primary-100 text-primary-900 dark:bg-primary-900 dark:text-primary-100'
              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
          ]"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3 rtl:space-x-reverse">
              <span class="text-lg">{{ language.flag }}</span>
              <span>{{ language.name }}</span>
            </div>
            <CheckIcon
              v-if="locale === language.code"
              class="h-4 w-4 text-primary-600"
            />
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import {
  LanguageIcon,
  ChevronDownIcon,
  CheckIcon,
} from "@heroicons/vue/24/outline";

const { locale } = useI18n();
const dropdownOpen = ref(false);

const languages = [
  { code: "en", name: "English", flag: "🇺🇸", dir: "ltr" },
  { code: "fa", name: "فارسی", flag: "🇮🇷", dir: "rtl" },
];

const isRTL = computed(() => locale.value === "fa");

const currentLanguage = computed(() => {
  return languages.find((lang) => lang.code === locale.value) || languages[0];
});

const switchLanguage = (langCode: string) => {
  locale.value = langCode;
  dropdownOpen.value = false;

  // Update document direction and language
  const selectedLang = languages.find((lang) => lang.code === langCode);
  if (selectedLang) {
    document.documentElement.dir = selectedLang.dir;
    document.documentElement.lang = langCode;

    // Store preference in localStorage
    localStorage.setItem("preferred-language", langCode);
  }
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
