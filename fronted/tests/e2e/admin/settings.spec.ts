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
  settings: {
    theme: 'light',
    collapse_menu: false,
    admin_crud_preferences: {},
  },
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

test.describe('Admin settings page', () => {
  test('redirects unauthenticated user from /admin/settings to /login', async ({ page }) => {
    await page.goto('/admin/settings');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows settings switches for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);

    await page.goto('/admin/settings');

    await expect(
      page.getByRole('heading', { level: 2, name: 'Настройки пользователя' })
    ).toBeVisible();

    const themeSwitch = page.getByRole('switch', { name: 'Тёмная тема' });
    const menuSwitch = page.getByRole('switch', { name: 'Collapse menu' });

    await expect(themeSwitch).toHaveAttribute('aria-checked', 'false');
    await expect(menuSwitch).toHaveAttribute('aria-checked', 'false');
  });
});
