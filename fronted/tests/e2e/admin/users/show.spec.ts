import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';

const shownUser = {
  id: 'u-1',
  email: 'ivanov@example.com',
  first_name: 'Иван',
  last_name: 'Иванов',
  middle_name: 'Иванович',
  phone: '+79990001122',
  roles: ['admin', 'manager'],
  avatar: {
    id: 'file-u-1',
    url: 'https://example.com/users/u-1-avatar.png',
    original_name: 'u-1-avatar.png',
    collection: 'avatar',
  },
};

test.describe('Admin users show page', () => {
  test('redirects unauthenticated user from /admin/users/[id] to /login', async ({ page }) => {
    await page.goto('/admin/users/u-1');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows user profile data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);

    await page.route('**/api/admin/users/u-1', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          user: shownUser,
        }),
      });
    });

    await page.goto('/admin/users/u-1');

    await expect(
      page.getByRole('heading', { level: 2, name: 'Профиль пользователя' })
    ).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Иван$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Иванов$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Иванович$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^ivanov@example\.com$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^\+79990001122$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^admin, manager$/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/users/u-1/edit"]')).toBeVisible();
    await expect(page.locator('img[src="https://example.com/users/u-1-avatar.png"]')).toBeVisible();
  });

  test('applies rollback and refreshes shown user data', async ({ page }) => {
    await setupAdminAuth(page);

    let currentLastName = 'Moderator1';
    let rollbackCalled = false;

    await page.route('**/api/admin/users/u-1', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          user: {
            ...shownUser,
            last_name: currentLastName,
          },
        }),
      });
    });

    await page.route('**/api/admin/changelog**', async (route) => {
      const url = route.request().url();
      if (!url.includes('model=user') || !url.includes('entity_id=u-1')) {
        await route.fallback();
        return;
      }

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            list_mode: 'latest',
            current_page: 1,
            data: [
              {
                id: 'log-1',
                auditable_type: 'Modules\\\\Users\\\\Models\\\\User',
                auditable_id: 'u-1',
                event: 'update',
                version: 1,
                before: { last_name: 'Moderator' },
                after: { last_name: 'Moderator1' },
                changed_fields: ['last_name'],
                actor_type: 'Modules\\\\Users\\\\Models\\\\User',
                actor_id: 'admin-1',
                batch_id: null,
                rolled_back_from_id: null,
                meta: null,
                created_at: '2026-02-14T18:56:29.000000Z',
              },
            ],
            last_page: 1,
            per_page: 20,
            total: 1,
          },
        }),
      });
    });

    await page.route('**/api/admin/changelog/log-1/rollback', async (route) => {
      rollbackCalled = true;
      currentLastName = 'Moderator';

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          message: 'Rollback completed.',
          data: {
            model_type: 'Modules\\\\Users\\\\Models\\\\User',
            model_id: 'u-1',
            rolled_back_from_id: 'log-1',
          },
        }),
      });
    });

    await page.goto('/admin/users/u-1');

    await expect(page.locator('dd', { hasText: /^Moderator1$/ })).toBeVisible();

    await page.getByRole('button', { name: 'Откатить' }).first().click();
    await page.locator('[role="dialog"]').getByRole('button', { name: 'Откатить' }).click();

    await expect.poll(() => rollbackCalled).toBeTruthy();
    await expect(page.locator('dd', { hasText: /^Moderator$/ })).toBeVisible();
  });

  test('applies rollback from create entry and restores initial snapshot', async ({ page }) => {
    await setupAdminAuth(page);

    let currentLastName = 'AfterCreateUpdate';
    let rollbackCalled = false;

    await page.route('**/api/admin/users/u-1', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          user: {
            ...shownUser,
            last_name: currentLastName,
          },
        }),
      });
    });

    await page.route('**/api/admin/changelog**', async (route) => {
      const url = route.request().url();
      if (!url.includes('model=user') || !url.includes('entity_id=u-1')) {
        await route.fallback();
        return;
      }

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            list_mode: 'latest',
            current_page: 1,
            data: [
              {
                id: 'log-create-1',
                auditable_type: 'Modules\\\\Users\\\\Models\\\\User',
                auditable_id: 'u-1',
                event: 'create',
                version: 1,
                before: null,
                after: {
                  id: 'u-1',
                  first_name: shownUser.first_name,
                  last_name: 'InitialLastName',
                  middle_name: shownUser.middle_name,
                  email: shownUser.email,
                },
                changed_fields: null,
                actor_type: 'Modules\\\\Users\\\\Models\\\\User',
                actor_id: 'admin-1',
                batch_id: null,
                rolled_back_from_id: null,
                meta: null,
                created_at: '2026-02-14T18:00:00.000000Z',
              },
            ],
            last_page: 1,
            per_page: 20,
            total: 1,
          },
        }),
      });
    });

    await page.route('**/api/admin/changelog/log-create-1/rollback', async (route) => {
      rollbackCalled = true;
      currentLastName = 'InitialLastName';

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          message: 'Rollback completed.',
          data: {
            model_type: 'Modules\\\\Users\\\\Models\\\\User',
            model_id: 'u-1',
            rolled_back_from_id: 'log-create-1',
          },
        }),
      });
    });

    await page.goto('/admin/users/u-1');
    await expect(page.locator('dd', { hasText: /^AfterCreateUpdate$/ })).toBeVisible();

    await page.getByRole('button', { name: 'Откатить' }).first().click();
    await page.locator('[role="dialog"]').getByRole('button', { name: 'Откатить' }).click();

    await expect.poll(() => rollbackCalled).toBeTruthy();
    await expect(page.locator('dd', { hasText: /^InitialLastName$/ })).toBeVisible();
  });

  test('shows actor name links in changelog', async ({ page }) => {
    await setupAdminAuth(page);

    await page.route('**/api/admin/users/u-1', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          user: shownUser,
        }),
      });
    });

    await page.route('**/api/admin/changelog**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            list_mode: 'latest',
            current_page: 1,
            data: [
              {
                id: 'log-self',
                auditable_type: 'Modules\\\\Users\\\\Models\\\\User',
                auditable_id: 'u-1',
                event: 'update',
                version: 2,
                before: { first_name: 'Иван' },
                after: { first_name: 'Иван2' },
                changed_fields: ['first_name'],
                actor_type: 'Modules\\\\Users\\\\Models\\\\User',
                actor_id: '1',
                actor: { id: '1', full_name: 'Системный Админ' },
                batch_id: null,
                rolled_back_from_id: null,
                meta: null,
                created_at: '2026-02-14T18:56:29.000000Z',
              },
              {
                id: 'log-other',
                auditable_type: 'Modules\\\\Users\\\\Models\\\\User',
                auditable_id: 'u-1',
                event: 'update',
                version: 1,
                before: { last_name: 'Иванов' },
                after: { last_name: 'Петров' },
                changed_fields: ['last_name'],
                actor_type: 'Modules\\\\Users\\\\Models\\\\User',
                actor_id: 'u-2',
                actor: { id: 'u-2', full_name: 'Петров Петр' },
                batch_id: null,
                rolled_back_from_id: null,
                meta: null,
                created_at: '2026-02-14T18:50:00.000000Z',
              },
            ],
            last_page: 1,
            per_page: 20,
            total: 2,
          },
        }),
      });
    });

    await page.goto('/admin/users/u-1');

    await expect(page.locator('a[href="/admin/profile"]', { hasText: 'Я' }).first()).toBeVisible();
    await expect(
      page.locator('a[href="/admin/users/u-2"]', { hasText: 'Петров Петр' }).first()
    ).toBeVisible();
  });
});
