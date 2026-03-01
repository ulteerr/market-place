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

const ensureMobileSidebarClosed = async (page: Page) => {
  const sidebar = page.locator('aside.admin-sidebar');
  const className = (await sidebar.getAttribute('class')) ?? '';

  if (!className.includes('is-open')) {
    return;
  }

  await page.getByRole('button', { name: 'Закрыть меню' }).first().click();
  await expect(sidebar).not.toHaveClass(/is-open/);
};

const expectPageHeading = async (page: Page, text: string, viewportWidth: number) => {
  const heading = page.locator('h2', { hasText: text }).first();

  if (viewportWidth < 1024) {
    await expect(heading).toHaveCount(1);
    return;
  }

  await expect(heading).toBeVisible();
};

const stabilizeLayout = async (page: Page) => {
  await ensureMobileSidebarClosed(page);

  try {
    await page.waitForLoadState('networkidle', { timeout: 3000 });
  } catch {
    // Some pages still have non-critical async work; continue with frame-based settling.
  }

  await page.evaluate(async () => {
    if ('fonts' in document) {
      await document.fonts.ready;
    }

    await new Promise<void>((resolve) => {
      requestAnimationFrame(() => requestAnimationFrame(() => resolve()));
    });
  });
};

const assertNoHorizontalOverflow = async (page: Page) => {
  await stabilizeLayout(page);
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
      await ensureMobileSidebarClosed(page);
      await expectPageHeading(page, 'Роли', viewport.width);
      if (viewport.width < 1024) {
        await expect(page.locator('text=/^admin$/').last()).toHaveCount(1);
        await expect(page.locator('text=/^manager$/').last()).toHaveCount(1);
      } else {
        await expect(page.locator('text=/^admin$/').last()).toBeVisible();
        await expect(page.locator('text=/^manager$/').last()).toBeVisible();
      }

      await assertNoHorizontalOverflow(page);
    });

    test(`new page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);

      await page.goto('/admin/roles/new');
      await ensureMobileSidebarClosed(page);
      await expectPageHeading(page, 'Новая роль', viewport.width);
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
      await ensureMobileSidebarClosed(page);
      await expectPageHeading(page, 'Роль', viewport.width);
      await expect(page.locator('dd', { hasText: /^manager$/ })).toBeVisible();
      await expect(page.locator('dd', { hasText: /^Менеджер$/ })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`edit form page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupRoleEditApi(page, existingRole);

      await page.goto('/admin/roles/r-2/edit');
      await ensureMobileSidebarClosed(page);
      await expectPageHeading(page, 'Редактирование роли', viewport.width);
      await expect(page.getByLabel('Code')).toHaveValue('manager');
      await expect(page.getByLabel('Label')).toHaveValue('Менеджер');
      await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });
  }
});
