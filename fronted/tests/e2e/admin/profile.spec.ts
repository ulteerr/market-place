import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../helpers/admin-auth';

test.describe('Admin profile page', () => {
  test('redirects unauthenticated user from /admin/profile to /login', async ({ page }) => {
    await page.goto('/admin/profile');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows profile form for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);

    await page.goto('/admin/profile');

    await expect(page.getByRole('heading', { level: 2, name: 'Мой профиль' })).toBeVisible();
    await expect(page.getByLabel('Имя')).toHaveValue(defaultAdminUser.first_name ?? '');
    await expect(page.getByLabel('Фамилия')).toHaveValue(defaultAdminUser.last_name ?? '');
    await expect(page.getByLabel('Отчество')).toHaveValue(defaultAdminUser.middle_name ?? '');
    await expect(page.getByLabel('Email')).toHaveValue(defaultAdminUser.email);
    await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();
  });
});
