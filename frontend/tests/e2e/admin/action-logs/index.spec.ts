import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';

test.describe('Admin action logs page', () => {
  test('redirects unauthenticated user from /admin/action-logs to /login', async ({ page }) => {
    await page.goto('/admin/action-logs');

    await expect(page).toHaveURL(/\/login$/);
  });

  test('shows action logs list for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);

    await page.route('**/api/admin/action-logs**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 2,
            data: [
              {
                id: 'a-1',
                user_id: '1',
                user: { id: '1', full_name: 'Системный Админ', email: 'admin@example.com' },
                event: 'update',
                model_type: 'Modules\\\\Users\\\\Models\\\\User',
                model_id: 'u-1',
                ip_address: '127.0.0.1',
                before: { first_name: 'Old' },
                after: { first_name: 'New' },
                changed_fields: ['first_name'],
                created_at: '2026-02-15T23:10:00.000000Z',
              },
              {
                id: 'a-2',
                user_id: '1',
                user: { id: '1', full_name: 'Системный Админ', email: 'admin@example.com' },
                event: 'delete',
                model_type: 'Modules\\\\Users\\\\Models\\\\Role',
                model_id: 'r-1',
                ip_address: '127.0.0.1',
                before: { code: 'moderator' },
                after: null,
                changed_fields: ['code'],
                created_at: '2026-02-15T23:11:00.000000Z',
              },
            ],
          },
        }),
      });
    });

    await page.goto('/admin/action-logs');

    await expect(page.getByRole('heading', { level: 2, name: 'Журнал изменений' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Найти' })).toHaveCount(0);
    await expect(page.getByText('Пользователь #u-1')).toBeVisible();
    await expect(page.getByText('Роль: moderator')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Системный Админ' }).first()).toHaveAttribute(
      'href',
      '/admin/profile'
    );
    await expect(page.getByText('first_name')).toBeVisible();
    await expect(page.locator('.event-chip', { hasText: 'Обновление' })).toBeVisible();
    await expect(page.locator('.event-chip', { hasText: 'Удаление' })).toBeVisible();
  });

  test('applies event filter immediately after select change', async ({ page }) => {
    await setupAdminAuth(page);

    await page.route('**/api/admin/action-logs**', async (route) => {
      const url = new URL(route.request().url());
      const event = url.searchParams.get('event');

      const baseItem = {
        user_id: '1',
        user: { id: '1', full_name: 'Системный Админ', email: 'admin@example.com' },
        ip_address: '127.0.0.1',
        before: null,
        after: null,
        changed_fields: null,
        created_at: '2026-02-15T23:10:00.000000Z',
      };

      const allItems = [
        {
          ...baseItem,
          id: 'a-1',
          event: 'update',
          model_type: 'Modules\\\\Users\\\\Models\\\\User',
          model_id: 'u-1',
          changed_fields: ['first_name'],
        },
        {
          ...baseItem,
          id: 'a-2',
          event: 'delete',
          model_type: 'Modules\\\\Users\\\\Models\\\\Role',
          model_id: 'r-1',
          changed_fields: ['code'],
        },
      ];

      const filtered = event ? allItems.filter((item) => item.event === event) : allItems;

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: filtered.length,
            data: filtered,
          },
        }),
      });
    });

    await page.goto('/admin/action-logs');

    await expect(page.locator('.event-chip:visible', { hasText: 'Удаление' })).toHaveCount(1);

    const filteredResponsePromise = page.waitForResponse((response) => {
      if (
        response.request().method() !== 'GET' ||
        !response.url().includes('/api/admin/action-logs')
      ) {
        return false;
      }
      const url = new URL(response.url());
      return response.status() === 200 && url.searchParams.get('event') === 'update';
    });

    await page.getByLabel('Событие').click();
    await page.getByRole('listbox').getByRole('button', { name: 'Обновление' }).click();

    await filteredResponsePromise;
    await expect(page).toHaveURL(/event=update/);
    await expect(page.locator('.event-chip:visible', { hasText: 'Удаление' })).toHaveCount(0);
    await expect(page.locator('.event-chip:visible', { hasText: 'Обновление' })).toHaveCount(1);
  });

  test('normalizes invalid date range from query', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedFrom = '';
    let capturedTo = '';

    await page.route('**/api/admin/action-logs**', async (route) => {
      const url = new URL(route.request().url());
      capturedFrom = url.searchParams.get('date_from') ?? '';
      capturedTo = url.searchParams.get('date_to') ?? '';

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            data: [],
          },
        }),
      });
    });

    await page.goto('/admin/action-logs?date_from=2026-02-12&date_to=2026-02-09');

    await expect.poll(() => `${capturedFrom}|${capturedTo}`).toBe('2026-02-09|2026-02-12');
    await expect(page).toHaveURL(/date_from=2026-02-09/);
    await expect(page).toHaveURL(/date_to=2026-02-12/);
  });

  test('keeps default per_page and sort params in query', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedSortBy = '';
    let capturedSortDir = '';

    await page.route('**/api/admin/action-logs**', async (route) => {
      const url = new URL(route.request().url());
      capturedSortBy = url.searchParams.get('sort_by') ?? '';
      capturedSortDir = url.searchParams.get('sort_dir') ?? '';

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            data: [],
          },
        }),
      });
    });

    await page.goto('/admin/action-logs');

    await expect.poll(() => `${capturedSortBy}|${capturedSortDir}`).toBe('created_at|desc');
    await expect(page).toHaveURL(/per_page=20/);
    await expect(page).toHaveURL(/sort_by=created_at/);
    await expect(page).toHaveURL(/sort_dir=desc/);
  });
});
