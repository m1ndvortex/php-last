import { defineStore } from "pinia";
import { ref, computed } from "vue";

export interface AppTheme {
  mode: "light" | "dark";
  primaryColor: string;
}

export interface AppSettings {
  sidebarCollapsed: boolean;
  theme: AppTheme;
  notifications: boolean;
  autoSave: boolean;
}

export const useAppStore = defineStore("app", () => {
  // State
  const settings = ref<AppSettings>({
    sidebarCollapsed: false,
    theme: {
      mode: "light",
      primaryColor: "#3b82f6",
    },
    notifications: true,
    autoSave: true,
  });

  const isLoading = ref(false);
  const notifications = ref<
    Array<{
      id: string;
      type: "success" | "error" | "warning" | "info";
      title: string;
      message: string;
      timestamp: Date;
      read: boolean;
    }>
  >([]);

  // Getters
  const isDarkMode = computed(() => settings.value.theme.mode === "dark");
  const unreadNotifications = computed(() =>
    notifications.value.filter((n) => !n.read),
  );
  const notificationCount = computed(() => unreadNotifications.value.length);

  // Actions
  const toggleSidebar = () => {
    settings.value.sidebarCollapsed = !settings.value.sidebarCollapsed;
    saveSettings();
  };

  const toggleTheme = () => {
    settings.value.theme.mode =
      settings.value.theme.mode === "light" ? "dark" : "light";
    applyTheme();
    saveSettings();
  };

  const updateTheme = (theme: Partial<AppTheme>) => {
    settings.value.theme = { ...settings.value.theme, ...theme };
    applyTheme();
    saveSettings();
  };

  const applyTheme = () => {
    const { mode } = settings.value.theme;

    if (mode === "dark") {
      document.documentElement.classList.add("dark");
    } else {
      document.documentElement.classList.remove("dark");
    }
  };

  const addNotification = (
    notification: Omit<
      (typeof notifications.value)[0],
      "id" | "timestamp" | "read"
    >,
  ) => {
    const id = Date.now().toString();
    notifications.value.unshift({
      ...notification,
      id,
      timestamp: new Date(),
      read: false,
    });

    // Auto-remove after 5 seconds for success notifications
    if (notification.type === "success") {
      setTimeout(() => {
        removeNotification(id);
      }, 5000);
    }
  };

  const removeNotification = (id: string) => {
    const index = notifications.value.findIndex((n) => n.id === id);
    if (index > -1) {
      notifications.value.splice(index, 1);
    }
  };

  const markNotificationAsRead = (id: string) => {
    const notification = notifications.value.find((n) => n.id === id);
    if (notification) {
      notification.read = true;
    }
  };

  const markAllNotificationsAsRead = () => {
    notifications.value.forEach((n) => (n.read = true));
  };

  const clearNotifications = () => {
    notifications.value = [];
  };

  const setLoading = (loading: boolean) => {
    isLoading.value = loading;
  };

  const updateSettings = (newSettings: Partial<AppSettings>) => {
    settings.value = { ...settings.value, ...newSettings };
    saveSettings();
  };

  const saveSettings = () => {
    localStorage.setItem("app_settings", JSON.stringify(settings.value));
  };

  const loadSettings = () => {
    const saved = localStorage.getItem("app_settings");
    if (saved) {
      try {
        const parsedSettings = JSON.parse(saved);
        settings.value = { ...settings.value, ...parsedSettings };
        applyTheme();
      } catch (err) {
        console.error("Failed to load app settings:", err);
      }
    }
  };

  // Initialize
  const initialize = () => {
    loadSettings();
    applyTheme();
  };

  return {
    // State
    settings,
    isLoading,
    notifications,

    // Getters
    isDarkMode,
    unreadNotifications,
    notificationCount,

    // Actions
    toggleSidebar,
    toggleTheme,
    updateTheme,
    addNotification,
    removeNotification,
    markNotificationAsRead,
    markAllNotificationsAsRead,
    clearNotifications,
    setLoading,
    updateSettings,
    initialize,
  };
});
