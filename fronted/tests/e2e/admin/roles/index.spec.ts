import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupRolesCollectionApi } from '../../helpers/crud/roles';

const setupRolesPage = async (page: Page) => {
  await setupAdminAuth(page);
  await setupRolesCollectionApi(page, [
    { id: 'r-1', code: 'admin', label: 'Администратор', is_system: true },
    { id: 'r-2', code: 'manager', label: 'Менеджер', is_system: false },
  ]);
};

test.describe('Admin roles page', () => {
  test('redirects unauthenticated user from /admin/roles to /login', async ({ page }) => {
    await page.goto('/admin/roles');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows roles table for authenticated admin', async ({ page }) => {
    await setupRolesPage(page);
    await page.goto('/admin/roles');

    await expect(page.getByRole('heading', { level: 2, name: 'Роли' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'admin', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'manager', exact: true })).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('sorts roles by code', async ({ page }) => {
    await setupRolesPage(page);
    await page.goto('/admin/roles');

    const firstCodeCell = page.locator('tbody tr').nth(0).locator('td').nth(0);
    await expect(firstCodeCell).toHaveText('admin');

    await page.getByRole('button', { name: 'Code ↑' }).click();

    await expect(firstCodeCell).toHaveText('manager');
    await expect(page.getByRole('button', { name: 'Code ↓' })).toBeVisible();
  });
});
