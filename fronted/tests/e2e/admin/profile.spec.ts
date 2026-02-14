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

  test('shows user avatar in profile and sidebar menu when present', async ({ page }) => {
    await setupAdminAuth(page, {
      ...defaultAdminUser,
      avatar: {
        id: 'file-1',
        url: 'https://example.com/avatar.png',
        original_name: 'avatar.png',
        collection: 'avatar',
      },
    });

    await page.goto('/admin/profile');

    await expect(page.locator('img[src="https://example.com/avatar.png"]').first()).toBeVisible();
    await expect(page.locator('.admin-user-menu .admin-avatar-image')).toBeVisible();
  });

  test('rolls back profile update and changes form value', async ({ page }) => {
    const meUser = {
      ...defaultAdminUser,
      id: 'u-me-1',
      first_name: 'System1',
      last_name: 'Admin',
      email: 'admin@example.com',
    };

    await setupAdminAuth(page, meUser);

    let currentFirstName = 'System1';
    let rollbackCalled = false;

    await page.unroute('**/api/me');
    await page.route('**/api/me', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          user: {
            ...meUser,
            first_name: currentFirstName,
          },
        }),
      });
    });

    await page.unroute('**/api/admin/changelog**');
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
                id: 'log-profile-1',
                auditable_type: 'Modules\\\\Users\\\\Models\\\\User',
                auditable_id: 'u-me-1',
                event: 'update',
                version: 3,
                before: { first_name: 'System' },
                after: { first_name: 'System1' },
                changed_fields: ['first_name'],
                actor_type: 'Modules\\\\Users\\\\Models\\\\User',
                actor_id: 'admin-1',
                batch_id: null,
                rolled_back_from_id: null,
                meta: { scope: 'profile' },
                created_at: '2026-02-14T19:03:50.000000Z',
              },
            ],
            last_page: 1,
            per_page: 20,
            total: 1,
          },
        }),
      });
    });

    await page.route('**/api/admin/changelog/log-profile-1/rollback', async (route) => {
      rollbackCalled = true;
      currentFirstName = 'System';

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          message: 'Rollback completed.',
          data: {
            model_type: 'Modules\\\\Users\\\\Models\\\\User',
            model_id: 'u-me-1',
            rolled_back_from_id: 'log-profile-1',
          },
        }),
      });
    });

    await page.goto('/admin/profile');
    await expect(page.getByLabel('Имя')).toHaveValue('System1');

    await page.getByRole('button', { name: 'Откатить' }).first().click();
    await page.locator('[role="dialog"]').getByRole('button', { name: 'Откатить' }).click();

    await expect.poll(() => rollbackCalled).toBeTruthy();
    await expect(page.getByLabel('Имя')).toHaveValue('System');
  });
});
