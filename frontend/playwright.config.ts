import { defineConfig, devices } from '@playwright/test';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';

const envPath = resolve(process.cwd(), '.env');
if (existsSync(envPath)) {
  process.loadEnvFile(envPath);
}

const port = Number(process.env.PORT ?? 3000);
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? `http://127.0.0.1:${port}`;
const ciWorkers = Number(process.env.PLAYWRIGHT_WORKERS ?? 2);

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? ciWorkers : undefined,
  reporter: 'html',
  use: {
    baseURL,
    trace: 'on-first-retry',
  },
  webServer: {
    command: `NUXT_PUBLIC_API_BASE=${baseURL} npm run dev`,
    url: baseURL,
    reuseExistingServer: !process.env.CI,
  },
  projects: [
    {
      name: 'chrome',
      use: {
        ...devices['Desktop Chrome'],
        channel: 'chrome',
      },
    },
    {
      name: 'mozilla-firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'safari-webkit',
      use: { ...devices['Desktop Safari'] },
    },
  ],
});
