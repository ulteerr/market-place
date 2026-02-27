import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupGeoDistrictEditApi } from '../../helpers/crud/geo';
import { readJsonBody } from '../../helpers/http';

const existingDistrict = {
  id: 'd-2',
  name: 'Центральный',
  city_id: 'ct-2',
};

test.describe('Admin geo districts form pages', () => {
  test('shows create form on /admin/geo/districts/new', async ({ page }) => {
    await setupAdminAuth(page);

    await page.goto('/admin/geo/districts/new');
    await expect(page.getByRole('heading', { level: 2, name: 'Новый район' })).toBeVisible();
    await expect(page.getByLabel('Название')).toBeVisible();
    await expect(page.getByLabel('ID города')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();
  });

  test('creates district on /admin/geo/districts/new', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route('**/api/admin/geo/districts', async (route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback();
        return;
      }

      capturedCreatePayload = readJsonBody(route);

      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: { id: 'd-new' } }),
      });
    });

    await page.goto('/admin/geo/districts/new');

    await page.getByLabel('Название').fill('  Фрунзенский ');
    await page.getByLabel('ID города').fill(' ct-9 ');
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect(page).toHaveURL(/\/admin\/geo\/districts$/);
    expect(capturedCreatePayload).toEqual({
      name: 'Фрунзенский',
      city_id: 'ct-9',
    });
  });

  test('updates district on /admin/geo/districts/[id]/edit', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await setupGeoDistrictEditApi(page, existingDistrict, (payload) => {
      capturedUpdatePayload = payload;
    });

    await page.goto('/admin/geo/districts/d-2/edit');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование района' })
    ).toBeVisible();
    await expect(page.getByLabel('Название')).toHaveValue('Центральный');

    await page.getByLabel('Название').fill('  Ленинский ');
    await page.getByLabel('ID города').fill(' ct-3 ');
    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page).toHaveURL(/\/admin\/geo\/districts\/d-2$/);
    expect(capturedUpdatePayload).toEqual({
      name: 'Ленинский',
      city_id: 'ct-3',
    });
  });
});
