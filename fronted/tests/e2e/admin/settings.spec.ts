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

  test('coalesces rapid settings updates into a single patch request', async ({ page }) => {
    const settingsPatchPayloads: Array<Record<string, unknown>> = [];

    await page.route('**/api/me/settings', async (route) => {
      const rawBody = route.request().postData() ?? '{}';
      settingsPatchPayloads.push(JSON.parse(rawBody) as Record<string, unknown>);

      await route.fulfill({
        status: 204,
        body: '',
      });
    });

    await setupAdminAuth(page, {
      ...defaultAdminUser,
      settings: {
        locale: 'ru',
        theme: 'light',
        collapse_menu: false,
        admin_crud_preferences: {},
        admin_navigation_sections: {},
      },
    });

    await page.goto('/admin/settings');

    const themeSwitch = page.getByRole('switch', { name: 'Тёмная тема' });
    await expect(themeSwitch).toHaveAttribute('aria-checked', 'false');

    for (let index = 0; index < 5; index += 1) {
      await themeSwitch.click();
    }

    await page.waitForTimeout(1200);

    await expect.poll(() => settingsPatchPayloads.length).toBe(1);
    expect(settingsPatchPayloads[0]?.settings).toMatchObject({ theme: 'dark' });
  });
});
