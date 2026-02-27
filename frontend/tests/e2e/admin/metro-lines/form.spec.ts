import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupMetroLineEditApi } from '../../helpers/crud/metro';
import { readJsonBody } from '../../helpers/http';

const existingLine = {
  id: 'ml-2',
  name: 'Арбатско-Покровская',
  external_id: 'line-ext-2',
  line_id: '3',
  color: '#2B4EA2',
  city_id: 'msk',
  source: 'import',
};

test.describe('Admin metro lines form pages', () => {
  test('shows create form on /admin/metro-lines/new', async ({ page }) => {
    await setupAdminAuth(page);

    await page.goto('/admin/metro-lines/new');
    await expect(page.getByRole('heading', { level: 2, name: 'Новая линия метро' })).toBeVisible();
    await expect(page.getByLabel('Название')).toBeVisible();
    await expect(page.getByLabel('Внешний ID')).toBeVisible();
    await expect(page.getByLabel('ID линии')).toBeVisible();
    await expect(page.getByRole('textbox', { name: /^Цвет/ })).toBeVisible();
    await expect(page.getByLabel('ID города')).toBeVisible();
    await expect(page.getByLabel('Источник')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();
  });

  test('creates metro line on /admin/metro-lines/new', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route('**/api/admin/geo/metro-lines', async (route) => {
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
          data: { id: 'ml-new' },
        }),
      });
    });

    await page.goto('/admin/metro-lines/new');

    await page.getByLabel('Название').fill('  Кольцевая ');
    await page.getByLabel('Внешний ID').fill(' ext-10 ');
    await page.getByLabel('ID линии').fill(' 5 ');
    await page.getByRole('textbox', { name: /^Цвет/ }).fill(' #915133 ');
    await page.getByLabel('ID города').fill(' msk ');
    await page.getByLabel('Источник').fill(' manual ');
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect(page).toHaveURL(/\/admin\/metro-lines$/);
    expect(capturedCreatePayload).toEqual({
      name: 'Кольцевая',
      external_id: 'ext-10',
      line_id: '5',
      color: '#915133',
      city_id: 'msk',
      source: 'manual',
    });
  });

  test('updates metro line on /admin/metro-lines/[id]/edit', async ({ page }) => {
    await setupAdminAuth(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await setupMetroLineEditApi(page, existingLine, (payload) => {
      capturedUpdatePayload = payload;
    });

    await page.goto('/admin/metro-lines/ml-2/edit');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование линии метро' })
    ).toBeVisible();
    await expect(page.getByLabel('Название')).toHaveValue('Арбатско-Покровская');

    await page.getByLabel('Название').fill('  Замоскворецкая ');
    await page.getByLabel('Внешний ID').fill(' ');
    await page.getByLabel('ID линии').fill(' 2 ');
    await page.getByRole('textbox', { name: /^Цвет/ }).fill(' #008A49 ');
    await page.getByLabel('ID города').fill(' msk ');
    await page.getByLabel('Источник').fill(' parsed ');
    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page).toHaveURL(/\/admin\/metro-lines\/ml-2$/);
    expect(capturedUpdatePayload).toEqual({
      name: 'Замоскворецкая',
      external_id: null,
      line_id: '2',
      color: '#008A49',
      city_id: 'msk',
      source: 'parsed',
    });
  });
});
