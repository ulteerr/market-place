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

const assertNoHorizontalOverflow = async (page: Page) => {
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
      await expect(page.getByRole('heading', { level: 2, name: 'Линии метро' })).toBeVisible();
      await expect(page.getByText('Сокольническая', { exact: true })).toBeVisible();
      await expect(page.getByText('Арбатско-Покровская', { exact: true })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`new page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);

      await page.goto('/admin/metro-lines/new');
      await expect(
        page.getByRole('heading', { level: 2, name: 'Новая линия метро' })
      ).toBeVisible();
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
      await expect(page.getByRole('heading', { level: 2, name: 'Линия метро' })).toBeVisible();
      await expect(page.locator('dd', { hasText: /^Арбатско-Покровская$/ })).toBeVisible();
      await expect(page.locator('dd', { hasText: /^3$/ })).toBeVisible();
      await expect(page.locator('a[href="/admin/metro-lines/ml-2/edit"]')).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`edit form page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupMetroLineEditApi(page, existingLine);

      await page.goto('/admin/metro-lines/ml-2/edit');
      await expect(
        page.getByRole('heading', { level: 2, name: 'Редактирование линии метро' })
      ).toBeVisible();
      await expect(page.getByLabel('Название')).toHaveValue('Арбатско-Покровская');
      await expect(page.getByLabel('ID линии')).toHaveValue('3');
      await expect(page.getByRole('textbox', { name: /^Цвет/ })).toHaveValue('#2B4EA2');
      await expect(page.getByLabel('ID города')).toHaveValue('msk');
      await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });
  }
});
