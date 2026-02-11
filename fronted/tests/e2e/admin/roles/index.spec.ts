import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';

const roles = [
  {
    id: 'r-1',
    code: 'admin',
    label: 'Администратор',
    is_system: true,
  },
  {
    id: 'r-2',
    code: 'manager',
    label: 'Менеджер',
    is_system: false,
  },
];

const setupRolesPage = async (page: Page) => {
  await setupAdminAuth(page);
  await page.route('**/api/admin/roles**', async (route) => {
    const url = new URL(route.request().url());
    const search = (url.searchParams.get('search') ?? '').trim().toLowerCase();
    const sortBy = url.searchParams.get('sort_by') ?? 'code';
    const sortDir = (url.searchParams.get('sort_dir') ?? 'asc').toLowerCase();
    const perPage = Number(url.searchParams.get('per_page') ?? 10);

    const filtered = roles.filter((item) => {
      if (!search) {
        return true;
      }

      return [item.code, item.label]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(search));
    });

    const sorted = [...filtered].sort((left, right) => {
      const leftValue = String((left as Record<string, unknown>)[sortBy] ?? '').toLowerCase();
      const rightValue = String((right as Record<string, unknown>)[sortBy] ?? '').toLowerCase();
      const compare = leftValue.localeCompare(rightValue, 'ru');

      return sortDir === 'desc' ? -compare : compare;
    });

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          data: sorted.slice(0, perPage),
          current_page: 1,
          last_page: 1,
          per_page: perPage,
          total: sorted.length,
        },
      }),
    });
  });
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
