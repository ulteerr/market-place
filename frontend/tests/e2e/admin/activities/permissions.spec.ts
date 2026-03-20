import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

test.describe('Admin activities permissions', () => {
  test('hides activities navigation item without read permission', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: (defaultAdminUser.permissions ?? []).filter(
        (permission) => permission !== 'admin.activities.read'
      ),
      settings: {
        theme: 'dark',
        collapse_menu: false,
        admin_crud_preferences: {},
        admin_navigation_sections: {
          organization: { open: true },
        },
      },
    });

    await page.goto('/admin');

    await expect(page.getByRole('link', { name: 'Активности' })).toHaveCount(0);
  });

  test('redirects from activities index without read permission', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: (defaultAdminUser.permissions ?? []).filter(
        (permission) => permission !== 'admin.activities.read'
      ),
    });

    await page.goto('/admin/activities');

    await expect(page).toHaveURL(/\/admin$/);
  });

  test('redirects from activity create page without create permission', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), 'admin.activities.read'].filter(
        (permission) => permission !== 'admin.activities.create'
      ),
    });

    await page.goto('/admin/activities/new');

    await expect(page).toHaveURL(/\/admin$/);
  });

  test('redirects from activity edit page without update permission', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), 'admin.activities.read'].filter(
        (permission) => permission !== 'admin.activities.update'
      ),
    });

    await page.goto('/admin/activities/act-football/edit');

    await expect(page).toHaveURL(/\/admin$/);
  });
});
