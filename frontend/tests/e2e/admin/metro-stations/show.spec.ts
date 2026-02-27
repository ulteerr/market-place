import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import {
  metroLinesFixture,
  setupMetroLinesCollectionApi,
  setupMetroStationShowApi,
} from '../../helpers/crud/metro';

const shownStation = {
  id: 'ms-2',
  name: 'Арбатская',
  external_id: 'station-ext-2',
  line_id: '3',
  geo_lat: 55.752,
  geo_lon: 37.604,
  is_closed: false,
  metro_line_id: 'ml-2',
  city_id: 'msk',
  source: 'import',
};

test.describe('Admin metro stations show page', () => {
  test('redirects unauthenticated user from /admin/metro-stations/[id] to /login', async ({
    page,
  }) => {
    await page.goto('/admin/metro-stations/ms-2');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows metro station data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);
    await setupMetroLinesCollectionApi(page, metroLinesFixture);
    await setupMetroStationShowApi(page, shownStation);

    await page.goto('/admin/metro-stations/ms-2');

    await expect(page.getByRole('heading', { level: 2, name: 'Станция метро' })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Арбатская$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^station-ext-2$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^3$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Нет$/ })).toBeVisible();
    await expect(page.getByRole('link', { name: /Арбатско-Покровская/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/metro-lines/ml-2"]')).toBeVisible();
    await expect(page.locator('a[href="/admin/metro-stations?search=msk"]')).toBeVisible();
    await expect(page.locator('a[href="/admin/metro-stations/ms-2/edit"]')).toBeVisible();
  });
});
