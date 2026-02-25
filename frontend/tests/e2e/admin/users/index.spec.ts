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
    avatar: {
      id: 'file-u-1',
      url: 'https://example.com/users/u-1-avatar.png',
      original_name: 'u-1-avatar.png',
      collection: 'avatar',
    },
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
    const accessGroup = url.searchParams.get('access_group');

    const filtered = dataset.filter((item) => {
      if (!search) {
        return true;
      }

      return [item.first_name, item.last_name, item.middle_name, item.email]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(search));
    });

    const byAccess = filtered.filter((item) => {
      if (accessGroup === 'admin') {
        return item.can_access_admin_panel;
      }

      if (accessGroup === 'basic') {
        return !item.can_access_admin_panel;
      }

      return true;
    });

    const sorted = [...byAccess].sort((left, right) => {
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

const setUsersMode = async (page: Page, label: 'Таблица' | 'Таблица + карточки' | 'Карточки') => {
  await page.locator('.mode-select-wrap input').first().click();
  await page.getByRole('button', { name: label, exact: true }).click();
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
    await expect(page.getByRole('button', { name: 'Найти' })).toHaveCount(0);
    await expect(page.getByText('Иванов', { exact: true })).toBeVisible();
    await expect(page.getByText('Петрова', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
    await expect(page.locator('thead th').first()).toBeVisible();
    await expect(page.locator('img[src="https://example.com/users/u-1-avatar.png"]')).toBeVisible();
  });

  test('filters users by search automatically', async ({ page }) => {
    await setupUsersPage(page);
    await page.goto('/admin/users');

    await page.locator('input.admin-input').first().fill('Петрова');

    await expect(page).toHaveURL(/search=%D0%9F%D0%B5%D1%82%D1%80%D0%BE%D0%B2%D0%B0/);
    await expect(page.getByText('Иванов', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Петрова', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('opens full image from thumbnail in modal', async ({ page }) => {
    await setupUsersPage(page);
    await page.goto('/admin/users');

    await page.getByRole('button', { name: 'Открыть изображение' }).first().click();

    const dialog = page.locator('[role="dialog"]');
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('img', { name: 'Иванов Иван Иванович' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(dialog).toHaveCount(0);
  });

  test('renders image preview controls in table/cards/table-cards modes', async ({ page }) => {
    await setupUsersPage(page);
    await page.goto('/admin/users');

    await setUsersMode(page, 'Таблица');
    await expect(page.locator('table.admin-table')).toBeVisible();
    await expect(page.locator('.user-card')).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Открыть изображение' })).toHaveCount(1);

    await setUsersMode(page, 'Карточки');
    await expect(page.locator('table.admin-table:visible')).toHaveCount(0);
    await expect(page.locator('.user-card')).toHaveCount(2);
    await expect(page.getByRole('button', { name: 'Открыть изображение' })).toHaveCount(1);

    await setUsersMode(page, 'Таблица + карточки');
    await expect(page.locator('table.admin-table')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Desktop: таблица' })).toBeVisible();
    await page.getByRole('button', { name: 'Desktop: таблица' }).click();
    await expect(page.getByRole('button', { name: 'Desktop: карточки' })).toBeVisible();
    await expect(page.locator('.user-card')).toHaveCount(2);
  });

  test('shows fallback thumbnail for users without avatar', async ({ page }) => {
    await setupUsersPage(page);
    await page.goto('/admin/users');

    const secondRowThumbnailCell = page.locator('tbody tr').nth(1).locator('td').nth(1);
    await expect(secondRowThumbnailCell).toContainText('—');
  });

  test('sorts users by last name', async ({ page }) => {
    await setupUsersPage(page);
    await page.goto('/admin/users');

    const firstLastNameCell = page.locator('tbody tr').nth(0).locator('td').nth(2);
    await expect(firstLastNameCell).toContainText('Иванов');

    await page.getByRole('button', { name: 'Фамилия ↑' }).click();

    await expect(firstLastNameCell).toContainText('Петрова');
    await expect(page.getByRole('button', { name: 'Фамилия ↓' })).toBeVisible();
  });

  test('deletes user through confirm modal', async ({ page }) => {
    await setupUsersPage(page);
    await page.goto('/admin/users');

    await page.getByRole('button', { name: 'Удалить' }).first().click();

    await expect(page.getByText('Удалить пользователя Иванов Иван Иванович?')).toBeVisible();
    await page.locator('[role="dialog"]').getByRole('button', { name: 'Удалить' }).click();

    await expect(page.getByText('Иванов', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Петрова', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('filters users by access groups via tag buttons', async ({ page }) => {
    await setupUsersPage(page);
    await page.goto('/admin/users');

    await page.getByTestId('admin-tag-admin').click();
    await expect(page).toHaveURL(/access_group=admin/);
    await expect(page.getByText('Иванов', { exact: true })).toBeVisible();
    await expect(page.getByText('Петрова', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();

    await page.reload();
    await expect(page).toHaveURL(/access_group=admin/);
    await expect(page.getByText('Иванов', { exact: true })).toBeVisible();
    await expect(page.getByText('Петрова', { exact: true })).toHaveCount(0);

    await page.getByTestId('admin-tag-basic').click();
    await expect(page).toHaveURL(/access_group=basic/);
    await expect(page.getByText('Иванов', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Петрова', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('shows pagination navigation buttons only when available', async ({ page }) => {
    await setupAdminAuth(page);

    const paginatedUsers = Array.from({ length: 51 }, (_, index) => ({
      id: `u-p-${index + 1}`,
      email: `user${index + 1}@example.com`,
      first_name: `Имя${index + 1}`,
      last_name: `Фамилия${index + 1}`,
      middle_name: null,
      can_access_admin_panel: index % 2 === 0,
    }));

    await page.route('**/api/admin/users**', async (route) => {
      const url = new URL(route.request().url());
      const pageNumber = Math.max(1, Number(url.searchParams.get('page') ?? 1));
      const perPage = Math.max(1, Number(url.searchParams.get('per_page') ?? 10));

      const total = paginatedUsers.length;
      const lastPage = Math.max(1, Math.ceil(total / perPage));
      const currentPage = Math.min(pageNumber, lastPage);
      const offset = (currentPage - 1) * perPage;
      const pageItems = paginatedUsers.slice(offset, offset + perPage);

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            data: pageItems,
            current_page: currentPage,
            last_page: lastPage,
            per_page: perPage,
            total,
          },
        }),
      });
    });

    await page.goto('/admin/users');

    await expect(page.getByRole('button', { name: 'Назад' })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Вперед' })).toHaveCount(1);

    await page.getByRole('button', { name: '2', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Назад' })).toHaveCount(1);
    await expect(page.getByRole('button', { name: 'Вперед' })).toHaveCount(1);

    await page.getByRole('button', { name: '6', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Назад' })).toHaveCount(1);
    await expect(page.getByRole('button', { name: 'Вперед' })).toHaveCount(0);
  });

  test('changes list when pagination buttons are clicked', async ({ page }) => {
    await setupAdminAuth(page);

    const paginatedUsers = Array.from({ length: 25 }, (_, index) => ({
      id: `u-c-${index + 1}`,
      email: `click${index + 1}@example.com`,
      first_name: `Имя${index + 1}`,
      last_name: `Фамилия${index + 1}`,
      middle_name: null,
      can_access_admin_panel: true,
    }));

    await page.route('**/api/admin/users**', async (route) => {
      const url = new URL(route.request().url());
      const pageNumber = Math.max(1, Number(url.searchParams.get('page') ?? 1));
      const perPage = Math.max(1, Number(url.searchParams.get('per_page') ?? 10));

      const total = paginatedUsers.length;
      const lastPage = Math.max(1, Math.ceil(total / perPage));
      const currentPage = Math.min(pageNumber, lastPage);
      const offset = (currentPage - 1) * perPage;
      const pageItems = paginatedUsers.slice(offset, offset + perPage);

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            data: pageItems,
            current_page: currentPage,
            last_page: lastPage,
            per_page: perPage,
            total,
          },
        }),
      });
    });

    await page.goto('/admin/users');
    await expect(page.locator('tbody tr').first()).toContainText('Фамилия1');

    await page.getByRole('button', { name: '2', exact: true }).click();
    await expect(page).toHaveURL(/page=2/);
    await expect(page.locator('tbody tr').first()).toContainText('Фамилия11');

    await page.getByRole('button', { name: 'Вперед' }).click();
    await expect(page).toHaveURL(/page=3/);
    await expect(page.locator('tbody tr').first()).toContainText('Фамилия21');

    await page.getByRole('button', { name: 'Назад' }).click();
    await expect(page).toHaveURL(/page=2/);
    await expect(page.locator('tbody tr').first()).toContainText('Фамилия11');
  });
});
