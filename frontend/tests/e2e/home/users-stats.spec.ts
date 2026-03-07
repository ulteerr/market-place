import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../helpers/admin-auth';
import { E2E_VIEWPORTS } from '../helpers/viewports';

const setupHomeStats = async (page: import('@playwright/test').Page) => {
  await setupAdminAuth(page, {
    permissions: ['admin.panel.access'],
  });

  await page.route('**/api/admin/users/stats', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          total_users: 1200,
          online_users: 87,
          updated_at: '2026-03-07T12:00:00.000Z',
        },
      }),
    });
  });

  await page.goto('/admin');
};

test.describe('Admin home users stats', () => {
  test('shows users stats on desktop', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.desktop1366);
    await setupHomeStats(page);

    await expect(page.locator('[data-test="home-users-stats"]')).toBeVisible();
    await expect(page.locator('[data-test="home-users-total"]')).toContainText('1,200');
    await expect(page.locator('[data-test="home-users-online"]')).toContainText('87');
  });

  test('shows users stats on mobile', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.mobile390);
    await setupHomeStats(page);

    await expect(page.locator('[data-test="home-users-stats"]')).toBeVisible();
    await expect(page.locator('[data-test="home-users-total"]')).toContainText('1,200');
    await expect(page.locator('[data-test="home-users-online"]')).toContainText('87');

    const hasOverflow = await page.evaluate(
      () => document.documentElement.scrollWidth > window.innerWidth
    );
    expect(hasOverflow).toBeFalsy();
  });
});
