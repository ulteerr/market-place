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
      permissions: ['admin.panel.access'],
    });

    await page.goto('/admin');
    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.locator('[data-test="home-users-stats"]')).toBeVisible();
  });

  test('allows admin login via form and redirects to /admin', async ({ page }) => {
    let loginRequestBody: Record<string, unknown> | null = null;

    await page.route('**/api/auth/login', async (route) => {
      loginRequestBody = route.request().postDataJSON();

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          token: 'playwright-admin-token',
          user: {
            id: '1',
            email: 'admin@example.com',
            first_name: 'Системный',
            last_name: 'Админ',
            permissions: ['admin.panel.access'],
          },
        }),
      });
    });

    await page.goto('/login');
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('password123');
    await page.getByRole('button', { name: 'Войти' }).click();

    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.locator('[data-test="home-users-stats"]')).toBeVisible();
    expect(loginRequestBody).toEqual({
      email: 'admin@example.com',
      password: 'password123',
    });
  });

  test('shows login error on invalid credentials', async ({ page }) => {
    await page.route('**/api/auth/login', async (route) => {
      await route.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'error',
          message: 'Invalid credentials',
        }),
      });
    });

    await page.goto('/login');
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('wrong-password');
    await page.getByRole('button', { name: 'Войти' }).click();

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByText('Не удалось авторизоваться. Проверьте email и пароль.')
    ).toBeVisible();
  });
});
