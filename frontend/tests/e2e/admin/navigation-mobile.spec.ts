import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../helpers/admin-auth';
import { E2E_VIEWPORTS } from '../helpers/viewports';

test.describe('Admin navigation mobile', () => {
  test.use({ viewport: E2E_VIEWPORTS.mobile390 });

  test('does not use collapsed sidebar mode on mobile', async ({ page }) => {
    await setupAdminAuth(page, {
      ...defaultAdminUser,
      settings: {
        theme: 'dark',
        collapse_menu: true,
        admin_crud_preferences: {},
        admin_navigation_sections: {
          system: { open: true },
        },
      },
    });

    await page.goto('/admin');

    await page.locator('header .admin-icon-button').first().click();

    const sidebar = page.locator('aside.admin-sidebar');
    await expect(sidebar).toHaveClass(/is-open/);
    await expect(sidebar).not.toHaveClass(/is-collapsed/);

    await expect(sidebar.locator('.admin-nav-collapsed-group')).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Система' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Пользователи' })).toBeVisible();
  });
});
