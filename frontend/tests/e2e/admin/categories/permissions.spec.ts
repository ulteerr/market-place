import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

test.describe('Admin categories permissions', () => {
  test('hides categories navigation item without read permission', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: (defaultAdminUser.permissions ?? []).filter(
        (permission) => permission !== 'admin.categories.read'
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

    await expect(page.getByRole('link', { name: 'Категории' })).toHaveCount(0);
  });

  test('redirects from categories index without read permission', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: (defaultAdminUser.permissions ?? []).filter(
        (permission) => permission !== 'admin.categories.read'
      ),
    });

    await page.goto('/admin/categories');

    await expect(page).toHaveURL(/\/admin$/);
  });

  test('redirects from category create page without create permission', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), 'admin.categories.read'].filter(
        (permission) => permission !== 'admin.categories.create'
      ),
    });

    await page.goto('/admin/categories/new');

    await expect(page).toHaveURL(/\/admin$/);
  });

  test('redirects from category edit page without update permission', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), 'admin.categories.read'].filter(
        (permission) => permission !== 'admin.categories.update'
      ),
    });

    await page.goto('/admin/categories/cat-root-sport/edit');

    await expect(page).toHaveURL(/\/admin$/);
  });
});
