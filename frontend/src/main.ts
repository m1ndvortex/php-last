import { createApp } from "vue";
import { createPinia } from "pinia";
import { createI18n } from "vue-i18n";
import App from "./App.vue";
import router from "./router";
import "./style.css";

// Import translations
import en from "./locales/en.json";
import fa from "./locales/fa.json";

// Import stores
import { useAuthStore } from "./stores/auth";
import { useAppStore } from "./stores/app";

// Get saved language preference
const savedLanguage = localStorage.getItem("preferred-language") || "en";

// Create i18n instance
const i18n = createI18n({
  legacy: false,
  locale: savedLanguage,
  fallbackLocale: "en",
  messages: {
    en,
    fa,
  },
});

// Create app instance
const app = createApp(App);

// Create pinia instance
const pinia = createPinia();

// Use plugins
app.use(pinia);
app.use(router);
app.use(i18n);

// Initialize stores
const authStore = useAuthStore();
const appStore = useAppStore();

// Initialize app
const initializeApp = async () => {
  // Initialize app settings
  appStore.initialize();

  // Initialize auth if token exists
  await authStore.initialize();

  // Set document direction based on language
  const isRTL = savedLanguage === "fa";
  document.documentElement.dir = isRTL ? "rtl" : "ltr";
  document.documentElement.lang = savedLanguage;
};

// Initialize and mount app
initializeApp().then(() => {
  app.mount("#app");
});
