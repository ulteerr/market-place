import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';

const authOrigin = 'http://127.0.0.1:3000';

const adminUser = {
  id: '1',
  email: 'admin@example.com',
  first_name: 'Админ',
  last_name: 'Системный',
  middle_name: 'Тестовый',
  can_access_admin_panel: true,
};

const shownUser = {
  id: 'u-1',
  email: 'ivanov@example.com',
  first_name: 'Иван',
  last_name: 'Иванов',
  middle_name: 'Иванович',
  phone: '+79990001122',
  roles: ['admin', 'manager'],
};

const setupAdminAuth = async (page: Page) => {
  await page.context().addCookies([
    {
      name: 'auth_token',
      value: 'test-admin-token',
      url: authOrigin,
    },
    {
      name: 'auth_user',
      value: encodeURIComponent(JSON.stringify(adminUser)),
      url: authOrigin,
    },
  ]);

  await page.route('**/api/me', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        user: adminUser,
      }),
    });
  });
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
    await expect(page.getByRole('link', { name: 'Edit' })).toHaveAttribute(
      'href',
      '/admin/users/u-1/edit'
    );
  });
});
