import { defineConfig, devices } from '@playwright/test';

/**
 * Tests E2E Accommity.
 *
 * Le stack local doit être démarré avant :
 *   scripts/deploy-local.ps1   (ou ./scripts/deploy-local.sh)
 *
 * Puis :
 *   npm run test:e2e
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  forbidOnly: !!process.env['CI'],
  retries: process.env['CI'] ? 1 : 0,
  workers: 1,
  timeout: 45_000,
  expect: {
    timeout: 10_000,
  },
  reporter: process.env['CI']
    ? [['line'], ['html', { open: 'never' }]]
    : [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env['E2E_BASE_URL'] ?? 'http://localhost:8080',
    locale: 'fr-FR',
    timezoneId: 'Africa/Casablanca',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
