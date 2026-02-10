import { expect, test } from '@playwright/test';

test.describe('Admin authorization', () => {
  test('redirects unauthenticated user from /admin to /login', async ({ page }) => {
    await page.goto('/admin');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('allows authenticated admin to open /admin', async ({ page }) => {
    const authUser = encodeURIComponent(
      JSON.stringify({
        id: '1',
        email: 'admin@example.com',
        can_access_admin_panel: true,
      })
    );

    await page.context().addCookies([
      {
        name: 'auth_token',
        value: 'test-admin-token',
        url: 'http://127.0.0.1:3000',
      },
      {
        name: 'auth_user',
        value: authUser,
        url: 'http://127.0.0.1:3000',
      },
    ]);

    await page.goto('/admin');
    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.getByRole('heading', { level: 2, name: 'Панель управления' })).toBeVisible();
  });
});
