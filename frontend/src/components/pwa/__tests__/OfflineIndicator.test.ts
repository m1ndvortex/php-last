import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { createI18n } from "vue-i18n";
import OfflineIndicator from "../OfflineIndicator.vue";

// Mock the composables
vi.mock("@/composables/useLocale", () => ({
  useLocale: () => ({
    isRTL: false,
    formatDate: (date: number) => new Date(date).toLocaleDateString(),
  }),
}));

vi.mock("@/composables/usePWA", () => ({
  usePWA: () => ({
    isOnline: true,
    syncStatus: {
      isVisible: false,
      type: null,
      message: "",
      progress: null,
    },
    storageInfo: {
      used: 0,
      quota: 0,
      percentage: 0,
    },
    retrySyncOperation: vi.fn(),
    clearOldData: vi.fn(),
  }),
}));

const i18n = createI18n({
  legacy: false,
  locale: "en",
  messages: {
    en: {
      offline: {
        indicator: "You are offline",
        online: "Online",
        offline: "Offline",
      },
      sync: {
        retry: "Retry",
        syncing: "Syncing...",
        completed: "Sync completed",
        failed: "Sync failed",
      },
      storage: {
        warning: "Storage space is running low",
        usage: "Using {percentage}% of available storage",
        clear: "Clear old data",
      },
    },
  },
});

describe("OfflineIndicator", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders without errors", () => {
    const wrapper = mount(OfflineIndicator, {
      global: {
        plugins: [i18n],
      },
    });

    expect(wrapper.exists()).toBe(true);
  });

  it("does not show offline indicator when online", () => {
    const wrapper = mount(OfflineIndicator, {
      global: {
        plugins: [i18n],
      },
    });

    // Should not show offline indicator when online
    expect(wrapper.find(".bg-red-500").exists()).toBe(false);
  });
});
