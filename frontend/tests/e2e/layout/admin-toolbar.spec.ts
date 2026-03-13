import { expect, test } from '@playwright/test';
import { setupAppAuth } from '../helpers/app-auth';

test.describe('Admin toolbar', () => {
  test('is hidden for non-admin user on public route', async ({ page }) => {
    await setupAppAuth(page, {
      permissions: [],
      roles: ['participant'],
    });

    await page.goto('/');

    await expect(page.locator('[data-test="admin-toolbar"]')).toHaveCount(0);
  });

  test('is visible for admin and triggers cache reset', async ({ page }) => {
    let resetRequested = false;

    await page.route('**/api/admin/cache/reset', async (route) => {
      resetRequested = true;
      expect(route.request().postDataJSON()).toMatchObject({
        scopes: ['frontend-ssr', 'backend'],
      });

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
        }),
      });
    });

    await setupAppAuth(page, {
      permissions: ['admin.panel.access'],
      roles: ['participant', 'admin'],
    });

    await page.goto('/');

    await expect(page.locator('[data-test="admin-toolbar"]')).toBeVisible();
    await expect(page.locator('[data-test="admin-toolbar-go-admin"]')).toHaveAttribute(
      'href',
      '/admin'
    );

    await page.locator('[data-test="admin-toolbar-cache-reset"]').click();
    await expect.poll(() => resetRequested).toBeTruthy();
    await expect(page.getByText('Кеш сброшен (SSR + backend).')).toBeVisible();
  });
});
