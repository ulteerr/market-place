import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../helpers/admin-auth';
import { E2E_RESPONSIVE_VIEWPORTS } from '../helpers/viewports';

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasOverflow).toBeFalsy();
};

test.describe('Admin settings responsive', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`settings page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page, {
        ...defaultAdminUser,
        settings: {
          theme: 'light',
          collapse_menu: false,
          admin_crud_preferences: {},
          admin_navigation_sections: {},
        },
      });

      await page.goto('/admin/settings');

      await expect(
        page.getByRole('heading', { level: 2, name: 'Настройки пользователя' })
      ).toBeVisible();

      const themeSwitch = page.getByRole('switch', { name: 'Тёмная тема' });
      const menuSwitch = page.getByRole('switch', { name: 'Collapse menu' });

      await expect(themeSwitch).toHaveAttribute('aria-checked', 'false');
      await expect(menuSwitch).toHaveAttribute('aria-checked', 'false');

      await assertNoHorizontalOverflow(page);
    });
  }
});
