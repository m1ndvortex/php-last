<template>
  <div class="relative" ref="dropdownRef">
    <button
      @click="toggleDropdown"
      class="flex items-center space-x-2 rtl:space-x-reverse px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors duration-150"
    >
      <span class="text-lg">{{ currentLanguage.flag }}</span>
      <span>{{ currentLanguage.name }}</span>
      <svg
        class="h-4 w-4 transition-transform duration-200"
        :class="{ 'rotate-180': dropdownOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Language dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="dropdownOpen"
        class="absolute top-full mt-2 w-40 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50"
        :class="[
          isRTL ? 'right-0' : 'left-0',
        ]"
        :style="dropdownStyle"
      >
        <div class="py-1">
          <button
            v-for="language in languages"
            :key="language.code"
            @click="switchLanguage(language.code)"
            :class="[
              'w-full text-left px-4 py-2 text-sm transition-colors duration-150',
              locale === language.code
                ? 'bg-blue-100 text-blue-900 dark:bg-blue-900 dark:text-blue-100'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
            ]"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <span class="text-lg">{{ language.flag }}</span>
                <span>{{ language.name }}</span>
              </div>
              <svg
                v-if="locale === language.code"
                class="h-4 w-4 text-blue-600"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </div>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from "vue";
import { useI18n } from "vue-i18n";

const { locale } = useI18n();
const dropdownOpen = ref(false);
const dropdownRef = ref<HTMLElement>();

const languages = [
  { code: "en", name: "English", flag: "🇺🇸", dir: "ltr" },
  { code: "fa", name: "فارسی", flag: "🇮🇷", dir: "rtl" },
];

const isRTL = computed(() => locale.value === "fa");

const currentLanguage = computed(() => {
  return languages.find((lang) => lang.code === locale.value) || languages[0];
});

// Calculate dropdown position to prevent overflow
const dropdownStyle = computed(() => {
  if (!dropdownRef.value || !dropdownOpen.value) return {};
  
  const rect = dropdownRef.value.getBoundingClientRect();
  const viewportWidth = window.innerWidth;
  const dropdownWidth = 160; // w-40 = 10rem = 160px
  
  // Check if dropdown would overflow on the right
  if (rect.right + dropdownWidth > viewportWidth) {
    return { right: '0px', left: 'auto' };
  }
  
  // Default positioning
  return isRTL.value ? { right: '0px', left: 'auto' } : { left: '0px', right: 'auto' };
});

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
  dropdownOpen.value = false;
};

const switchLanguage = (langCode: string) => {
  locale.value = langCode;
  dropdownOpen.value = false;

  // Update document direction and language
  const selectedLang = languages.find((lang) => lang.code === langCode);
  if (selectedLang) {
    document.documentElement.dir = selectedLang.dir;
    document.documentElement.lang = langCode;

    // Update body classes for global RTL/LTR styling
    document.body.classList.toggle("rtl", selectedLang.dir === "rtl");
    document.body.classList.toggle("ltr", selectedLang.dir === "ltr");

    // Store preference in localStorage
    localStorage.setItem("preferred-language", langCode);

    // Trigger a custom event for other components to listen to
    window.dispatchEvent(
      new CustomEvent("language-changed", {
        detail: { locale: langCode, direction: selectedLang.dir },
      }),
    );
  }
};

// Handle click outside
const handleClickOutside = (event: Event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>
