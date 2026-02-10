import { expect, test } from '@playwright/test';

const adminUser = {
  id: '1',
  email: 'admin@example.com',
  first_name: 'Админ',
  last_name: 'Системный',
  middle_name: 'Тестовый',
  can_access_admin_panel: true,
};

const authOrigin = 'http://127.0.0.1:3000';

test.describe('Admin profile page', () => {
  test('redirects unauthenticated user from /admin/profile to /login', async ({ page }) => {
    await page.goto('/admin/profile');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows profile form for authenticated admin', async ({ page }) => {
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

    await page.goto('/admin/profile');

    await expect(page.getByRole('heading', { level: 2, name: 'Мой профиль' })).toBeVisible();
    await expect(page.getByLabel('Имя')).toHaveValue('Админ');
    await expect(page.getByLabel('Фамилия')).toHaveValue('Системный');
    await expect(page.getByLabel('Отчество')).toHaveValue('Тестовый');
    await expect(page.getByLabel('Email')).toHaveValue('admin@example.com');
    await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();
  });
});
