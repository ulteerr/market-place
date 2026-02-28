import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupGeoCountryEditApi } from '../../helpers/crud/geo';
import { readJsonBody } from '../../helpers/http';

const existingCountry = {
  id: 'c-2',
  name: 'Беларусь',
  iso_code: 'BY',
};

test.describe('Admin geo countries form pages', () => {
  test('shows create form on /admin/geo/countries/new', async ({ page }) => {
    await setupAdminAuth(page);

    await page.goto('/admin/geo/countries/new');
    const form = page.locator('article form').first();

    await expect(page.getByRole('heading', { level: 2, name: 'Новая страна' })).toBeVisible();
    await expect(form.getByRole('textbox', { name: 'Название' })).toBeVisible();
    await expect(form.getByRole('textbox', { name: 'ISO код' })).toBeVisible();
    await expect(form.getByRole('button', { name: 'Создать' })).toBeVisible();
  });

  test('creates country on /admin/geo/countries/new', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route('**/api/admin/geo/countries', async (route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback();
        return;
      }

      capturedCreatePayload = readJsonBody(route);

      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: { id: 'c-new' } }),
      });
    });

    await page.goto('/admin/geo/countries/new');

    await page.getByLabel('Название').fill('  Казахстан ');
    await page.getByLabel('ISO код').fill(' kz ');
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect(page).toHaveURL(/\/admin\/geo\/countries$/);
    expect(capturedCreatePayload).toEqual({
      name: 'Казахстан',
      iso_code: 'kz',
    });
  });

  test('updates country on /admin/geo/countries/[id]/edit', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await setupGeoCountryEditApi(page, existingCountry, (payload) => {
      capturedUpdatePayload = payload;
    });

    await page.goto('/admin/geo/countries/c-2/edit');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование страны' })
    ).toBeVisible();
    await expect(page.getByLabel('Название')).toHaveValue('Беларусь');

    await page.getByLabel('Название').fill('  Республика Беларусь ');
    await page.getByLabel('ISO код').fill(' ');
    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page).toHaveURL(/\/admin\/geo\/countries\/c-2$/);
    expect(capturedUpdatePayload).toEqual({
      name: 'Республика Беларусь',
      iso_code: null,
    });
  });
});
