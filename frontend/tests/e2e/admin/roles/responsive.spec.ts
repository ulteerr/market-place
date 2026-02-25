import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import {
  setupRoleEditApi,
  setupRoleShowApi,
  setupRolesCollectionApi,
} from '../../helpers/crud/roles';
import { E2E_RESPONSIVE_VIEWPORTS } from '../../helpers/viewports';

const existingRole = {
  id: 'r-2',
  code: 'manager',
  label: 'Менеджер',
  is_system: false,
};

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasOverflow).toBeFalsy();
};

test.describe('Admin roles responsive pages', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`index page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupRolesCollectionApi(page, [
        { id: 'r-1', code: 'admin', label: 'Администратор', is_system: true },
        existingRole,
      ]);

      await page.goto('/admin/roles');
      await expect(page.getByRole('heading', { level: 2, name: 'Роли' })).toBeVisible();
      await expect(page.getByText('admin').first()).toBeVisible();
      await expect(page.getByText('manager').first()).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`new page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);

      await page.goto('/admin/roles/new');
      await expect(page.getByRole('heading', { level: 2, name: 'Новая роль' })).toBeVisible();
      await expect(page.getByLabel('Code')).toBeVisible();
      await expect(page.getByLabel('Label')).toBeVisible();
      await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`show page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupRoleShowApi(page, existingRole);

      await page.goto('/admin/roles/r-2');
      await expect(page.getByRole('heading', { level: 2, name: 'Роль' })).toBeVisible();
      await expect(page.locator('dd', { hasText: /^manager$/ })).toBeVisible();
      await expect(page.locator('dd', { hasText: /^Менеджер$/ })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`edit form page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupRoleEditApi(page, existingRole);

      await page.goto('/admin/roles/r-2/edit');
      await expect(
        page.getByRole('heading', { level: 2, name: 'Редактирование роли' })
      ).toBeVisible();
      await expect(page.getByLabel('Code')).toHaveValue('manager');
      await expect(page.getByLabel('Label')).toHaveValue('Менеджер');
      await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });
  }
});
