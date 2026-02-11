import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';

const users = [
  {
    id: 'u-1',
    email: 'ivanov@example.com',
    first_name: 'Иван',
    last_name: 'Иванов',
    middle_name: 'Иванович',
    can_access_admin_panel: true,
  },
  {
    id: 'u-2',
    email: 'petrova@example.com',
    first_name: 'Анна',
    last_name: 'Петрова',
    middle_name: null,
    can_access_admin_panel: false,
  },
];

const setupUsersPage = async (page: Page) => {
  await setupAdminAuth(page);
  let dataset = [...users];

  await page.route('**/api/admin/users**', async (route) => {
    if (route.request().method() === 'DELETE') {
      const userId = route.request().url().split('/').pop();
      dataset = dataset.filter((item) => item.id !== userId);

      await route.fulfill({
        status: 204,
        body: '',
      });
      return;
    }

    const url = new URL(route.request().url());
    const search = (url.searchParams.get('search') ?? '').trim().toLowerCase();
    const sortBy = url.searchParams.get('sort_by') ?? 'last_name';
    const sortDir = (url.searchParams.get('sort_dir') ?? 'asc').toLowerCase();
    const perPage = Number(url.searchParams.get('per_page') ?? 10);

    const filtered = dataset.filter((item) => {
      if (!search) {
        return true;
      }

      return [item.first_name, item.last_name, item.middle_name, item.email]
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

test.describe('Admin users page', () => {
  test('redirects unauthenticated user from /admin/users to /login', async ({ page }) => {
    await page.goto('/admin/users');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows users table for authenticated admin', async ({ page }) => {
    await setupUsersPage(page);
    await page.goto('/admin/users');

    await expect(page.getByRole('heading', { level: 2, name: 'Пользователи' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Иванов', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Петрова', exact: true })).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('sorts users by last name', async ({ page }) => {
    await setupUsersPage(page);
    await page.goto('/admin/users');

    const firstLastNameCell = page.locator('tbody tr').nth(0).locator('td').nth(0);
    await expect(firstLastNameCell).toHaveText('Иванов');

    await page.getByRole('button', { name: 'Фамилия ↑' }).click();

    await expect(firstLastNameCell).toHaveText('Петрова');
    await expect(page.getByRole('button', { name: 'Фамилия ↓' })).toBeVisible();
  });

  test('deletes user through confirm modal', async ({ page }) => {
    await setupUsersPage(page);
    await page.goto('/admin/users');

    await page.getByRole('button', { name: 'Удалить' }).first().click();

    await expect(page.getByText('Удалить пользователя Иванов Иван Иванович?')).toBeVisible();
    await page.locator('[role="dialog"]').getByRole('button', { name: 'Удалить' }).click();

    await expect(page.getByRole('cell', { name: 'Иванов', exact: true })).toHaveCount(0);
    await expect(page.getByRole('cell', { name: 'Петрова', exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });
});
