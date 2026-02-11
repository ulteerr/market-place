import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../helpers/admin-auth';

test.describe('Admin settings page', () => {
  test('redirects unauthenticated user from /admin/settings to /login', async ({ page }) => {
    await page.goto('/admin/settings');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows settings switches for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page, {
      ...defaultAdminUser,
      settings: {
        theme: 'light',
        collapse_menu: false,
        admin_crud_preferences: {},
      },
    });

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
