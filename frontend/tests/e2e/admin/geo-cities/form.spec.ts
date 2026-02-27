import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupGeoCityEditApi } from '../../helpers/crud/geo';
import { readJsonBody } from '../../helpers/http';

const existingCity = {
  id: 'ct-2',
  name: 'Гомель',
  country_id: 'c-2',
  region_id: 'r-2',
};

test.describe('Admin geo cities form pages', () => {
  test('shows create form on /admin/geo/cities/new', async ({ page }) => {
    await setupAdminAuth(page);

    await page.goto('/admin/geo/cities/new');
    await expect(page.getByRole('heading', { level: 2, name: 'Новый город' })).toBeVisible();
    await expect(page.getByLabel('Название')).toBeVisible();
    await expect(page.getByLabel('ID страны')).toBeVisible();
    await expect(page.getByLabel('ID региона')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();
  });

  test('creates city on /admin/geo/cities/new', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route('**/api/admin/geo/cities', async (route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback();
        return;
      }

      capturedCreatePayload = readJsonBody(route);

      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: { id: 'ct-new' } }),
      });
    });

    await page.goto('/admin/geo/cities/new');

    await page.getByLabel('Название').fill('  Витебск ');
    await page.getByLabel('ID страны').fill(' c-2 ');
    await page.getByLabel('ID региона').fill(' r-3 ');
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect(page).toHaveURL(/\/admin\/geo\/cities$/);
    expect(capturedCreatePayload).toEqual({
      name: 'Витебск',
      country_id: 'c-2',
      region_id: 'r-3',
    });
  });

  test('updates city on /admin/geo/cities/[id]/edit', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await setupGeoCityEditApi(page, existingCity, (payload) => {
      capturedUpdatePayload = payload;
    });

    await page.goto('/admin/geo/cities/ct-2/edit');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование города' })
    ).toBeVisible();
    await expect(page.getByLabel('Название')).toHaveValue('Гомель');

    await page.getByLabel('Название').fill('  Брест ');
    await page.getByLabel('ID страны').fill(' ');
    await page.getByLabel('ID региона').fill(' ');
    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page).toHaveURL(/\/admin\/geo\/cities\/ct-2$/);
    expect(capturedUpdatePayload).toEqual({
      name: 'Брест',
      country_id: null,
      region_id: null,
    });
  });
});
