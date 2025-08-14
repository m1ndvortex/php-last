import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    setupFiles: ['./src/test-setup.ts'],
    globals: true,
    include: [
      'src/**/*.integration.test.{js,ts}',
      'src/services/__tests__/seamlessTabNavigation.integration.test.ts'
    ],
    testTimeout: 30000, // 30 seconds for integration tests
    hookTimeout: 30000,
    teardownTimeout: 10000,
    // Real API testing configuration
    env: {
      VITE_API_BASE_URL: 'http://localhost/api',
      VITE_APP_URL: 'http://localhost:3000',
      NODE_ENV: 'test'
    },
    // Coverage configuration for integration tests
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      include: [
        'src/services/**/*.ts',
        'src/stores/**/*.ts',
        'src/composables/**/*.ts'
      ],
      exclude: [
        'src/**/*.test.ts',
        'src/**/*.spec.ts',
        'src/test-utils/**',
        'node_modules/**'
      ],
      thresholds: {
        global: {
          branches: 70,
          functions: 70,
          lines: 70,
          statements: 70
        }
      }
    },
    // Retry configuration for flaky network tests
    retry: 2,
    // Concurrent execution for performance
    pool: 'threads',
    poolOptions: {
      threads: {
        singleThread: false,
        maxThreads: 4,
        minThreads: 1
      }
    }
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './src')
    }
  },
  define: {
    __VUE_OPTIONS_API__: true,
    __VUE_PROD_DEVTOOLS__: false
  }
})