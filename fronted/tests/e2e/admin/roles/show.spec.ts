import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';

const shownRole = {
  id: 'r-2',
  code: 'manager',
  label: 'Менеджер',
  is_system: false,
};

test.describe('Admin roles show page', () => {
  test('redirects unauthenticated user from /admin/roles/[id] to /login', async ({ page }) => {
    await page.goto('/admin/roles/r-2');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows role data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);

    await page.route('**/api/admin/roles/r-2', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          role: shownRole,
        }),
      });
    });

    await page.goto('/admin/roles/r-2');

    await expect(page.getByRole('heading', { level: 2, name: 'Роль' })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^manager$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Менеджер$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Пользовательская$/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/roles/r-2/edit"]')).toBeVisible();
  });
});
