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

test.describe('Admin profile responsive', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`profile page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);

      await page.goto('/admin/profile');
      const form = page.locator('article form').first();

      await expect(page.getByRole('heading', { level: 2, name: 'Мой профиль' })).toBeVisible();
      await expect(form.getByRole('textbox', { name: 'Имя' })).toHaveValue(
        defaultAdminUser.first_name ?? ''
      );
      await expect(form.getByRole('textbox', { name: 'Фамилия' })).toHaveValue(
        defaultAdminUser.last_name ?? ''
      );
      await expect(form.getByRole('textbox', { name: 'Отчество' })).toHaveValue(
        defaultAdminUser.middle_name ?? ''
      );
      await expect(form.getByRole('textbox', { name: 'Email' })).toHaveValue(
        defaultAdminUser.email
      );
      await expect(form.getByRole('button', { name: 'Сохранить' })).toBeVisible();

      if (viewport.width === 390) {
        const sidebar = page.locator('aside.admin-sidebar');
        const mobileMenuButton = page.locator('header .admin-icon-button').first();

        await expect(mobileMenuButton).toBeVisible();
        await expect(page.locator('.admin-sidebar-toggle')).toBeHidden();
        await expect(sidebar).not.toHaveClass(/is-open/);

        await mobileMenuButton.click();
        await expect(sidebar).toHaveClass(/is-open/);
      }

      await assertNoHorizontalOverflow(page);
    });
  }
});
