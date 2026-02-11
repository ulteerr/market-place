import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';

const authOrigin = 'http://127.0.0.1:3000';

const adminUser = {
  id: '1',
  email: 'admin@example.com',
  first_name: 'Админ',
  last_name: 'Системный',
  middle_name: 'Тестовый',
  can_access_admin_panel: true,
};

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

const setupAdminAuth = async (page: Page) => {
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

  await page.route('**/api/admin/users**', async (route) => {
    const url = new URL(route.request().url());
    const search = (url.searchParams.get('search') ?? '').trim().toLowerCase();
    const sortBy = url.searchParams.get('sort_by') ?? 'last_name';
    const sortDir = (url.searchParams.get('sort_dir') ?? 'asc').toLowerCase();
    const perPage = Number(url.searchParams.get('per_page') ?? 10);

    const filtered = users.filter((item) => {
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
    await setupAdminAuth(page);
    await page.goto('/admin/users');

    await expect(page.getByRole('heading', { level: 2, name: 'Пользователи' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Иванов', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Петрова', exact: true })).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('sorts users by last name', async ({ page }) => {
    await setupAdminAuth(page);
    await page.goto('/admin/users');

    const firstLastNameCell = page.locator('tbody tr').nth(0).locator('td').nth(0);
    await expect(firstLastNameCell).toHaveText('Иванов');

    await page.getByRole('button', { name: 'Фамилия ↑' }).click();

    await expect(firstLastNameCell).toHaveText('Петрова');
    await expect(page.getByRole('button', { name: 'Фамилия ↓' })).toBeVisible();
  });
});
