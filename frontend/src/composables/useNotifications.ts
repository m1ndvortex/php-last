import { useAppStore } from "@/stores/app";

export interface NotificationAction {
  label: string;
  handler: () => void | Promise<void>;
}

export interface NotificationOptions {
  duration?: number;
  action?: NotificationAction;
  persistent?: boolean;
}

export function useNotifications() {
  const appStore = useAppStore();

  const showSuccess = (title: string, message: string, options?: NotificationOptions) => {
    appStore.addNotification({
      type: "success",
      title,
      message,
      duration: options?.duration,
      action: options?.action,
      persistent: options?.persistent,
    });
  };

  const showError = (title: string, message: string, options?: NotificationOptions) => {
    appStore.addNotification({
      type: "error",
      title,
      message,
      duration: options?.duration,
      action: options?.action,
      persistent: options?.persistent,
    });
  };

  const showWarning = (title: string, message: string, options?: NotificationOptions) => {
    appStore.addNotification({
      type: "warning",
      title,
      message,
      duration: options?.duration,
      action: options?.action,
      persistent: options?.persistent,
    });
  };

  const showInfo = (title: string, message: string, options?: NotificationOptions) => {
    appStore.addNotification({
      type: "info",
      title,
      message,
      duration: options?.duration,
      action: options?.action,
      persistent: options?.persistent,
    });
  };

  const removeNotification = (id: string) => {
    appStore.removeNotification(id);
  };

  const clearAll = () => {
    appStore.clearNotifications();
  };

  const showNotification = (notification: {
    type: "success" | "error" | "warning" | "info";
    title: string;
    message?: string;
    duration?: number;
    action?: NotificationAction;
    persistent?: boolean;
  }) => {
    appStore.addNotification({
      ...notification,
      message: notification.message || "",
    });
  };

  return {
    showSuccess,
    showError,
    showWarning,
    showInfo,
    showNotification,
    removeNotification,
    clearAll,
    notifications: appStore.notifications,
    unreadCount: appStore.notificationCount,
  };
}
