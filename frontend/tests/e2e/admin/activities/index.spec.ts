import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

const activityPermissions = ['admin.activities.read'];

test.describe('Admin activities entry page', () => {
  test('shows /admin/activities entry page and navigation item', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...activityPermissions],
      settings: {
        theme: 'dark',
        collapse_menu: false,
        admin_crud_preferences: {},
        admin_navigation_sections: {
          organization: { open: true },
        },
      },
    });

    await page.goto('/admin/activities');

    const breadcrumbs = page.locator('nav[aria-label="Хлебные крошки"]');

    await expect(page.getByRole('heading', { level: 2, name: 'Активности' })).toBeVisible();
    await expect(
      page.locator('.admin-sidebar').getByText('Активности', { exact: true }).first()
    ).toHaveCount(1);
    await expect(breadcrumbs).toContainText('Главная');
    await expect(breadcrumbs).toContainText('Организация');
    await expect(breadcrumbs).toContainText('Активности');
  });
});
