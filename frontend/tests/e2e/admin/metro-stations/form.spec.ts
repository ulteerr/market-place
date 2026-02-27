import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import {
  metroLinesFixture,
  setupMetroLinesCollectionApi,
  setupMetroStationEditApi,
} from '../../helpers/crud/metro';
import { readJsonBody } from '../../helpers/http';

const existingStation = {
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

test.describe('Admin metro stations form pages', () => {
  test('shows create form on /admin/metro-stations/new', async ({ page }) => {
    await setupAdminAuth(page);
    await setupMetroLinesCollectionApi(page, metroLinesFixture);

    await page.goto('/admin/metro-stations/new');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Новая станция метро' })
    ).toBeVisible();
    await expect(page.getByLabel('Название')).toBeVisible();
    await expect(page.getByLabel('Внешний ID')).toBeVisible();
    await expect(page.getByLabel('ID линии', { exact: true })).toBeVisible();
    await expect(page.getByLabel('Широта')).toBeVisible();
    await expect(page.getByLabel('Долгота')).toBeVisible();
    await expect(page.getByRole('switch', { name: 'Станция закрыта' })).toBeVisible();
    await expect(page.getByLabel('Линия метро')).toBeVisible();
    await expect(page.getByLabel('ID города')).toBeVisible();
    await expect(page.getByLabel('Источник')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();
  });

  test('creates metro station on /admin/metro-stations/new', async ({ page }) => {
    await setupAdminAuth(page);
    await setupMetroLinesCollectionApi(page, metroLinesFixture);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route('**/api/admin/metro-stations', async (route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback();
        return;
      }

      capturedCreatePayload = readJsonBody(route);

      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: { id: 'ms-new' },
        }),
      });
    });

    await page.goto('/admin/metro-stations/new');

    await page.getByLabel('Название').fill('  Тверская ');
    await page.getByLabel('Внешний ID').fill(' st-ext-10 ');
    await page.getByLabel('ID линии', { exact: true }).fill(' 2 ');
    await page.getByLabel('Широта').fill('55.766');
    await page.getByLabel('Долгота').fill('37.605');
    await page.getByLabel('Линия метро').fill('Соколь');
    await page.getByRole('button', { name: /Сокольническая/ }).click();
    await page.getByLabel('ID города').fill('msk');
    await page.getByRole('button', { name: /^msk$/ }).click();
    await page.getByLabel('Источник').fill(' manual ');
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect(page).toHaveURL(/\/admin\/metro-stations$/);
    expect(capturedCreatePayload).toEqual({
      name: 'Тверская',
      external_id: 'st-ext-10',
      line_id: '2',
      geo_lat: 55.766,
      geo_lon: 37.605,
      is_closed: false,
      metro_line_id: 'ml-1',
      city_id: 'msk',
      source: 'manual',
    });
  });

  test('updates metro station on /admin/metro-stations/[id]/edit', async ({ page }) => {
    await setupAdminAuth(page);
    await setupMetroLinesCollectionApi(page, metroLinesFixture);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await setupMetroStationEditApi(page, existingStation, (payload) => {
      capturedUpdatePayload = payload;
    });

    await page.goto('/admin/metro-stations/ms-2/edit');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование станции метро' })
    ).toBeVisible();
    await expect(page.getByLabel('Название')).toHaveValue('Арбатская');

    await page.getByLabel('Название').fill('  Парк культуры ');
    await page.getByLabel('Внешний ID').fill(' ');
    await page.getByLabel('ID линии', { exact: true }).fill(' 5 ');
    await page.getByLabel('Широта').fill('55.735');
    await page.getByLabel('Долгота').fill('37.594');
    await page.getByRole('switch', { name: 'Станция закрыта' }).click();
    await page.getByLabel('Линия метро').fill('Соколь');
    await page.getByRole('button', { name: /Сокольническая/ }).click();
    await page.getByLabel('ID города').fill('msk');
    await page.getByRole('button', { name: /^msk$/ }).click();
    await page.getByLabel('Источник').fill(' parsed ');
    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page).toHaveURL(/\/admin\/metro-stations\/ms-2$/);
    expect(capturedUpdatePayload).toEqual({
      name: 'Парк культуры',
      external_id: null,
      line_id: '5',
      geo_lat: 55.735,
      geo_lon: 37.594,
      is_closed: true,
      metro_line_id: 'ml-1',
      city_id: 'msk',
      source: 'parsed',
    });
  });
});
