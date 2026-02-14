import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';

const shownUser = {
  id: 'u-1',
  email: 'ivanov@example.com',
  first_name: 'Иван',
  last_name: 'Иванов',
  middle_name: 'Иванович',
  phone: '+79990001122',
  roles: ['admin', 'manager'],
  avatar: {
    id: 'file-u-1',
    url: 'https://example.com/users/u-1-avatar.png',
    original_name: 'u-1-avatar.png',
    collection: 'avatar',
  },
};

test.describe('Admin users show page', () => {
  test('redirects unauthenticated user from /admin/users/[id] to /login', async ({ page }) => {
    await page.goto('/admin/users/u-1');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows user profile data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);

    await page.route('**/api/admin/users/u-1', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          user: shownUser,
        }),
      });
    });

    await page.goto('/admin/users/u-1');

    await expect(
      page.getByRole('heading', { level: 2, name: 'Профиль пользователя' })
    ).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Иван$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Иванов$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Иванович$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^ivanov@example\.com$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^\+79990001122$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^admin, manager$/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/users/u-1/edit"]')).toBeVisible();
    await expect(page.locator('img[src="https://example.com/users/u-1-avatar.png"]')).toBeVisible();
  });
});
