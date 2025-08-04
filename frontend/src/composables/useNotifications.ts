import { useAppStore } from "@/stores/app";

export function useNotifications() {
  const appStore = useAppStore();

  const showSuccess = (title: string, message: string) => {
    appStore.addNotification({
      type: "success",
      title,
      message,
    });
  };

  const showError = (title: string, message: string) => {
    appStore.addNotification({
      type: "error",
      title,
      message,
    });
  };

  const showWarning = (title: string, message: string) => {
    appStore.addNotification({
      type: "warning",
      title,
      message,
    });
  };

  const showInfo = (title: string, message: string) => {
    appStore.addNotification({
      type: "info",
      title,
      message,
    });
  };

  const removeNotification = (id: string) => {
    appStore.removeNotification(id);
  };

  const clearAll = () => {
    appStore.clearNotifications();
  };

  return {
    showSuccess,
    showError,
    showWarning,
    showInfo,
    removeNotification,
    clearAll,
    notifications: appStore.notifications,
    unreadCount: appStore.notificationCount,
  };
}
