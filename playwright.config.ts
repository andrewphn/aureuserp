import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright Configuration for AureusERP PDF Viewer Testing
 *
 * Tests the Nutrient Web SDK PDF viewer integration across multiple browsers
 * with focus on annotation functionality and real-time synchronization.
 */
export default defineConfig({
  // Test directory
  testDir: './tests/Browser',

  // Maximum time one test can run for
  timeout: 60 * 1000,

  // Test execution settings
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,

  // Reporter configuration
  reporter: [
    ['html', { outputFolder: 'tests/Browser/reports/html' }],
    ['json', { outputFile: 'tests/Browser/reports/results.json' }],
    ['list'],
  ],

  // Shared settings for all projects
  use: {
    // Base URL for tests
    baseURL: process.env.APP_URL || 'http://localhost',

    // Collect trace on failure for debugging
    trace: 'on-first-retry',

    // Screenshot on failure
    screenshot: 'only-on-failure',

    // Video recording
    video: 'retain-on-failure',

    // Viewport settings
    viewport: { width: 1280, height: 720 },

    // Timeout for navigation actions
    actionTimeout: 15 * 1000,

    // Ignore HTTPS errors in development
    ignoreHTTPSErrors: true,

    // Accept downloads during tests
    acceptDownloads: true,
  },

  // Configure projects for major browsers
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        // Chromium-specific settings
        launchOptions: {
          args: ['--disable-web-security'], // Allow cross-origin for PDF tests
        },
      },
    },

    {
      name: 'firefox',
      use: {
        ...devices['Desktop Firefox'],
        // Firefox-specific settings
        launchOptions: {
          firefoxUserPrefs: {
            // Enable PDF viewer
            'pdfjs.disabled': false,
          },
        },
      },
    },

    {
      name: 'webkit',
      use: {
        ...devices['Desktop Safari'],
        // WebKit-specific settings
      },
    },

    // Mobile browsers for responsive testing
    {
      name: 'mobile-chrome',
      use: {
        ...devices['Pixel 5'],
      },
    },

    {
      name: 'mobile-safari',
      use: {
        ...devices['iPhone 12'],
      },
    },
  ],

  // Web server configuration (optional, for local testing)
  webServer: process.env.CI ? undefined : {
    command: 'php artisan serve --port=8000',
    port: 8000,
    timeout: 120 * 1000,
    reuseExistingServer: !process.env.CI,
  },
});
