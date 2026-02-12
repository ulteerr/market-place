import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupRoleShowApi } from '../../helpers/crud/roles';

const shownRole = {
  id: 'r-2',
  code: 'manager',
  label: 'Менеджер',
  is_system: false,
};

test.describe('Admin roles show page', () => {
  test('redirects unauthenticated user from /admin/roles/[id] to /login', async ({ page }) => {
    await page.goto('/admin/roles/r-2');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows role data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);
    await setupRoleShowApi(page, shownRole);

    await page.goto('/admin/roles/r-2');

    await expect(page.getByRole('heading', { level: 2, name: 'Роль' })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^manager$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Менеджер$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Пользовательская$/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/roles/r-2/edit"]')).toBeVisible();
  });
});
