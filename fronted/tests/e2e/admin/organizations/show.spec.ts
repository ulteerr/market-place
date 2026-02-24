import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

const organizationPermissions = [
  'org.company.profile.read',
  'org.company.profile.update',
  'org.company.profile.delete',
];

test.describe('Admin organizations show page', () => {
  test('redirects unauthenticated user from /admin/organizations/[id] to /login', async ({
    page,
  }) => {
    await page.goto('/admin/organizations/org-1');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows organization data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...organizationPermissions],
    });

    await page.route('**/api/admin/organizations/org-1', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            id: 'org-1',
            name: 'Организация 1',
            description: 'Описание организации',
            address: 'г. Москва, ул. Тестовая, д. 1',
            phone: '+79990001122',
            email: 'org1@example.com',
            status: 'active',
            source_type: 'manual',
            ownership_status: 'claimed',
            owner_user_id: 'u-1',
            claimed_at: '2026-02-20T10:00:00.000000Z',
            created_at: '2026-02-19T08:00:00.000000Z',
            owner: {
              id: 'u-1',
              first_name: 'Павел',
              last_name: 'Васильев',
              middle_name: 'Павлович',
            },
          },
        }),
      });
    });

    await page.goto('/admin/organizations/org-1');

    await expect(page.getByRole('heading', { level: 2, name: 'Организация' })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Организация 1$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Активна$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Подтверждена$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Вручную$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^org1@example\.com$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^\+79990001122$/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/users/u-1"]')).toContainText(
      'Васильев Павел Павлович'
    );
    await expect(page.locator('a[href="/admin/organizations/org-1/edit"]')).toBeVisible();
  });
});
