import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { geoCountriesFixture, setupGeoCountriesCollectionApi } from '../../helpers/crud/geo';

const setupGeoCountriesPage = async (page: Page) => {
  await setupAdminAuth(page);
  await setupGeoCountriesCollectionApi(page, geoCountriesFixture);
};

test.describe('Admin geo countries page', () => {
  test('redirects unauthenticated user from /admin/geo/countries to /login', async ({ page }) => {
    await page.goto('/admin/geo/countries');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows countries table for authenticated admin', async ({ page }) => {
    await setupGeoCountriesPage(page);
    await page.goto('/admin/geo/countries');

    await expect(page.getByRole('heading', { level: 2, name: 'Страны' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Россия', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Беларусь', exact: true })).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('filters countries by search automatically', async ({ page }) => {
    await setupGeoCountriesPage(page);
    await page.goto('/admin/geo/countries');

    await page.locator('input.admin-input').first().fill('БЕЛ');

    await expect(page).toHaveURL(/search=%D0%91%D0%95%D0%9B/);
    await expect(page.getByText('Россия', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Беларусь', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('deletes country through confirm modal', async ({ page }) => {
    await setupGeoCountriesPage(page);
    await page.goto('/admin/geo/countries');

    await page.locator('tbody tr').first().getByRole('button', { name: 'Удалить' }).click();

    const dialog = page.locator('[role="dialog"]');
    await expect(dialog).toBeVisible();
    const deleteResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'DELETE' &&
        response.url().includes('/api/admin/geo/countries/')
    );
    await dialog.getByRole('button', { name: 'Удалить' }).click();
    const deleteResponse = await deleteResponsePromise;
    expect(deleteResponse.status()).toBe(200);

    await expect(page.locator('tbody tr')).toHaveCount(1);
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });
});
