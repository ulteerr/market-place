import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import {
  metroLinesFixture,
  setupMetroLineEditApi,
  setupMetroLineShowApi,
  setupMetroLinesCollectionApi,
} from '../../helpers/crud/metro';
import { E2E_RESPONSIVE_VIEWPORTS } from '../../helpers/viewports';

const existingLine = metroLinesFixture[1];

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

test.describe('Admin metro lines responsive pages', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`index page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupMetroLinesCollectionApi(page, metroLinesFixture);

      await page.goto('/admin/metro-lines');
      await ensureMobileSidebarClosed(page);
      await expectPageHeading(page, 'Линии метро', viewport.width);
      await expect(page.getByText('Сокольническая', { exact: true })).toBeVisible();
      await expect(page.getByText('Арбатско-Покровская', { exact: true })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`new page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);

      await page.goto('/admin/metro-lines/new');
      await ensureMobileSidebarClosed(page);
      await expectPageHeading(page, 'Новая линия метро', viewport.width);
      await expect(page.getByLabel('Название')).toBeVisible();
      await expect(page.getByLabel('Внешний ID')).toBeVisible();
      await expect(page.getByLabel('ID линии')).toBeVisible();
      await expect(page.getByRole('textbox', { name: /^Цвет/ })).toBeVisible();
      await expect(page.getByLabel('ID города')).toBeVisible();
      await expect(page.getByLabel('Источник')).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`show page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupMetroLineShowApi(page, existingLine);

      await page.goto('/admin/metro-lines/ml-2');
      await ensureMobileSidebarClosed(page);
      await expectPageHeading(page, 'Линия метро', viewport.width);
      if (viewport.width < 1024) {
        await expect(page.locator('text=/^Арбатско-Покровская$/')).toHaveCount(1);
        await expect(page.locator('text=/^3$/')).toHaveCount(1);
      } else {
        await expect(page.locator('dd', { hasText: /^Арбатско-Покровская$/ })).toBeVisible();
        await expect(page.locator('dd', { hasText: /^3$/ })).toBeVisible();
      }
      await expect(page.locator('a[href="/admin/metro-lines/ml-2/edit"]')).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`edit form page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupMetroLineEditApi(page, existingLine);

      await page.goto('/admin/metro-lines/ml-2/edit');
      await ensureMobileSidebarClosed(page);
      await expectPageHeading(page, 'Редактирование линии метро', viewport.width);
      await expect(page.getByLabel('Название')).toHaveValue('Арбатско-Покровская');
      await expect(page.getByLabel('ID линии')).toHaveValue('3');
      await expect(page.getByRole('textbox', { name: /^Цвет/ })).toHaveValue('#2B4EA2');
      await expect(page.getByLabel('ID города')).toHaveValue('msk');
      await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });
  }
});
