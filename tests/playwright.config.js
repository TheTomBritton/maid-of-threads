// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Playwright configuration for Maid of Threads e2e tests.
 *
 * Tests run against the local Docker dev environment at http://localhost:8080.
 * Ensure Docker is running before executing tests.
 */
module.exports = defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: 1,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'list',
  timeout: 30000,

  use: {
    baseURL: 'http://localhost:8080',
    screenshot: 'only-on-failure',
    trace: 'on-first-retry',
  },

  projects: [
    {
      name: 'desktop',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1280, height: 720 },
      },
    },
    {
      name: 'mobile',
      use: {
        ...devices['iPhone 13'],
      },
    },
  ],
});
