import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import {
  metroLinesFixture,
  metroStationsFixture,
  setupMetroLinesCollectionApi,
  setupMetroStationEditApi,
  setupMetroStationShowApi,
  setupMetroStationsCollectionApi,
} from '../../helpers/crud/metro';
import { E2E_RESPONSIVE_VIEWPORTS } from '../../helpers/viewports';

const existingStation = metroStationsFixture[1];

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasOverflow).toBeFalsy();
};

test.describe('Admin metro stations responsive pages', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`index page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupMetroLinesCollectionApi(page, metroLinesFixture);
      await setupMetroStationsCollectionApi(page, metroStationsFixture);

      await page.goto('/admin/metro-stations');
      await expect(page.getByRole('heading', { level: 2, name: 'Станции метро' })).toBeVisible();
      await expect(page.getByText('Охотный ряд', { exact: true })).toBeVisible();
      await expect(page.getByText('Арбатская', { exact: true })).toBeVisible();

      if (viewport.width >= 1024) {
        await expect(page.locator('.admin-table:visible')).toHaveCount(1);
        await expect(page.locator('.role-card:visible')).toHaveCount(0);
      } else {
        await assertNoHorizontalOverflow(page);
      }
    });

    test(`new page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupMetroLinesCollectionApi(page, metroLinesFixture);

      await page.goto('/admin/metro-stations/new');
      const form = page.locator('article form').first();

      await expect(
        page.getByRole('heading', { level: 2, name: 'Новая станция метро' })
      ).toBeVisible();
      await expect(form.getByRole('textbox', { name: 'Название' })).toBeVisible();
      await expect(form.getByRole('textbox', { name: 'Внешний ID' })).toBeVisible();
      await expect(form.getByRole('textbox', { name: 'ID линии', exact: true })).toBeVisible();
      await expect(form.getByRole('textbox', { name: 'Широта' })).toBeVisible();
      await expect(form.getByRole('textbox', { name: 'Долгота' })).toBeVisible();
      await expect(form.getByRole('switch', { name: 'Станция закрыта' })).toBeVisible();
      await expect(form.getByRole('textbox', { name: 'Линия метро' })).toBeVisible();
      await expect(form.getByRole('textbox', { name: 'ID города' })).toBeVisible();
      await expect(form.getByRole('textbox', { name: 'Источник' })).toBeVisible();
      await expect(form.getByRole('button', { name: 'Создать' })).toBeVisible();

      if (viewport.width < 1024) {
        await assertNoHorizontalOverflow(page);
      }
    });

    test(`show page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupMetroLinesCollectionApi(page, metroLinesFixture);
      await setupMetroStationShowApi(page, existingStation);

      await page.goto('/admin/metro-stations/ms-2');
      await expect(page.getByRole('heading', { level: 2, name: 'Станция метро' })).toBeVisible();
      await expect(page.locator('dd', { hasText: /^Арбатская$/ })).toBeVisible();
      await expect(page.locator('dd', { hasText: /^3$/ })).toBeVisible();
      await expect(page.locator('a[href="/admin/metro-stations/ms-2/edit"]')).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`edit form page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupMetroLinesCollectionApi(page, metroLinesFixture);
      await setupMetroStationEditApi(page, existingStation);

      await page.goto('/admin/metro-stations/ms-2/edit');
      await expect(
        page.getByRole('heading', { level: 2, name: 'Редактирование станции метро' })
      ).toBeVisible();
      await expect(page.getByLabel('Название')).toHaveValue('Арбатская');
      await expect(page.getByLabel('ID линии', { exact: true })).toHaveValue('3');
      await expect(page.getByLabel('Линия метро')).toBeVisible();
      await expect(page.getByLabel('ID города')).toBeVisible();
      await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });
  }
});
