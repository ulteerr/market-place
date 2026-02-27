import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupGeoRegionEditApi } from '../../helpers/crud/geo';
import { readJsonBody } from '../../helpers/http';

const existingRegion = {
  id: 'r-2',
  name: 'Гомельская область',
  country_id: 'c-2',
};

test.describe('Admin geo regions form pages', () => {
  test('shows create form on /admin/geo/regions/new', async ({ page }) => {
    await setupAdminAuth(page);

    await page.goto('/admin/geo/regions/new');
    await expect(page.getByRole('heading', { level: 2, name: 'Новый регион' })).toBeVisible();
    await expect(page.getByLabel('Название')).toBeVisible();
    await expect(page.getByLabel('ID страны')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();
  });

  test('creates region on /admin/geo/regions/new', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route('**/api/admin/geo/regions', async (route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback();
        return;
      }

      capturedCreatePayload = readJsonBody(route);

      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: { id: 'r-new' } }),
      });
    });

    await page.goto('/admin/geo/regions/new');

    await page.getByLabel('Название').fill('  Минская область ');
    await page.getByLabel('ID страны').fill(' c-2 ');
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect(page).toHaveURL(/\/admin\/geo\/regions$/);
    expect(capturedCreatePayload).toEqual({
      name: 'Минская область',
      country_id: 'c-2',
    });
  });

  test('updates region on /admin/geo/regions/[id]/edit', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await setupGeoRegionEditApi(page, existingRegion, (payload) => {
      capturedUpdatePayload = payload;
    });

    await page.goto('/admin/geo/regions/r-2/edit');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование региона' })
    ).toBeVisible();
    await expect(page.getByLabel('Название')).toHaveValue('Гомельская область');

    await page.getByLabel('Название').fill('  Брестская область ');
    await page.getByLabel('ID страны').fill(' c-2 ');
    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page).toHaveURL(/\/admin\/geo\/regions\/r-2$/);
    expect(capturedUpdatePayload).toEqual({
      name: 'Брестская область',
      country_id: 'c-2',
    });
  });
});
