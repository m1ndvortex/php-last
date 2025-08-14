import { defineConfig, devices } from '@playwright/test'

/**
 * Playwright Configuration for Seamless Tab Navigation E2E Tests
 * Configured for Docker environment with real web application testing
 */
export default defineConfig({
  testDir: './tests/e2e',
  
  /* Run tests in files in parallel */
  fullyParallel: false, // Disabled for cross-tab testing
  
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  
  /* Retry on CI only */
  retries: process.env.CI ? 2 : 1,
  
  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : 1, // Single worker for cross-tab coordination
  
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['html', { outputFolder: 'storage/logs/playwright-report' }],
    ['json', { outputFile: 'storage/logs/playwright-results.json' }],
    ['junit', { outputFile: 'storage/logs/playwright-junit.xml' }]
  ],
  
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: process.env.APP_URL || 'http://localhost:8080',
    
    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',
    
    /* Take screenshot on failure */
    screenshot: 'only-on-failure',
    
    /* Record video on failure */
    video: 'retain-on-failure',
    
    /* Timeout for each action */
    actionTimeout: 10000,
    
    /* Timeout for navigation */
    navigationTimeout: 30000,
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'chromium',
      use: { 
        ...devices['Desktop Chrome'],
        // Enable cross-tab testing features
        contextOptions: {
          // Allow multiple tabs in same context
          permissions: ['notifications'],
        }
      },
    },

    {
      name: 'firefox',
      use: { 
        ...devices['Desktop Firefox'],
        contextOptions: {
          permissions: ['notifications'],
        }
      },
    },

    {
      name: 'webkit',
      use: { 
        ...devices['Desktop Safari'],
        contextOptions: {
          permissions: ['notifications'],
        }
      },
    },

    /* Test against mobile viewports. */
    {
      name: 'Mobile Chrome',
      use: { 
        ...devices['Pixel 5'],
        contextOptions: {
          permissions: ['notifications'],
        }
      },
    },
    {
      name: 'Mobile Safari',
      use: { 
        ...devices['iPhone 12'],
        contextOptions: {
          permissions: ['notifications'],
        }
      },
    },
  ],

  /* Run your local dev server before starting the tests */
  webServer: {
    command: 'docker-compose up -d && sleep 30',
    url: process.env.APP_URL || 'http://localhost:8080',
    reuseExistingServer: !process.env.CI,
    timeout: 120000,
  },

  /* Global setup and teardown */
  globalSetup: require.resolve('./tests/e2e/global-setup.ts'),
  globalTeardown: require.resolve('./tests/e2e/global-teardown.ts'),

  /* Test timeout */
  timeout: 60000,

  /* Expect timeout */
  expect: {
    timeout: 10000,
  },

  /* Output directory */
  outputDir: 'storage/logs/playwright-artifacts',
})