import { expect, test } from '@playwright/test';
import { setAdminAuthCookies } from '../helpers/admin-auth';

test.describe('Admin authorization', () => {
  test('redirects unauthenticated user from /admin to /login', async ({ page }) => {
    await page.goto('/admin');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('allows authenticated admin to open /admin', async ({ page }) => {
    await setAdminAuthCookies(page, {
      id: '1',
      email: 'admin@example.com',
      can_access_admin_panel: true,
    });

    await page.goto('/admin');
    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.getByRole('heading', { level: 2, name: 'Панель управления' })).toBeVisible();
  });
});
