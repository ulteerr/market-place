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

      await expect(page.getByRole('heading', { level: 2, name: 'Мой профиль' })).toBeVisible();
      await expect(page.getByLabel('Имя')).toHaveValue(defaultAdminUser.first_name ?? '');
      await expect(page.getByLabel('Фамилия')).toHaveValue(defaultAdminUser.last_name ?? '');
      await expect(page.getByLabel('Отчество')).toHaveValue(defaultAdminUser.middle_name ?? '');
      await expect(page.getByLabel('Email')).toHaveValue(defaultAdminUser.email);
      await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });
  }
});
