import { vi } from 'vitest';
import { config } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { createPinia } from 'pinia';

// Mock API service
vi.mock('@/services/api', () => ({
  apiService: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    inventory: {
      getCategories: vi.fn().mockResolvedValue({
        data: {
          success: true,
          data: [],
        },
      }),
      getGoldPurityOptions: vi.fn().mockResolvedValue({
        data: {
          success: true,
          data: {
            standard_purities: [
              { value: 10, label: '10K Gold' },
              { value: 14, label: '14K Gold' },
              { value: 18, label: '18K Gold' },
              { value: 22, label: '22K Gold' },
              { value: 24, label: '24K Gold' },
            ],
          },
        },
      }),
      createCategory: vi.fn(),
      updateCategory: vi.fn(),
      deleteCategory: vi.fn(),
    },
  },
}));

// Mock composables
vi.mock('@/composables/useApi', () => ({
  useApi: () => ({
    execute: vi.fn().mockResolvedValue(null),
    loading: false,
    error: null,
  }),
}));

vi.mock('@/composables/useLocale', () => ({
  useLocale: () => ({
    isRTL: false,
    formatGoldPurity: vi.fn((value: number) => `${value}K Gold`),
  }),
}));

vi.mock('@/composables/useNumberFormatter', () => ({
  useNumberFormatter: () => ({
    getGoldPurityOptions: vi.fn(() => [
      { value: 10, label: '10K Gold' },
      { value: 14, label: '14K Gold' },
      { value: 18, label: '18K Gold' },
      { value: 22, label: '22K Gold' },
      { value: 24, label: '24K Gold' },
    ]),
    toPersianNumerals: vi.fn((value: string) => value),
    formatNumber: vi.fn((value: number) => value.toString()),
  }),
}));

// Create i18n instance for tests
const i18n = createI18n({
  legacy: false,
  locale: 'en',
  messages: {
    en: {
      inventory: {
        categories: {
          select_parent: 'Select parent category',
          gold_purity_placeholder: 'Select gold purity',
          no_categories_found: 'No categories found',
          description: 'Get started by creating your first category',
        },
        gold_purity_help: 'Select the gold purity for this item',
      },
      common: {
        custom: 'Custom',
      },
    },
  },
});

// Global test configuration
config.global.plugins = [i18n, createPinia()];
config.global.mocks = {
  $t: (key: string) => key,
};