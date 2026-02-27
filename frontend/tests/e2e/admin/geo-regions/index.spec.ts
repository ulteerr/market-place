import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { geoRegionsFixture, setupGeoRegionsCollectionApi } from '../../helpers/crud/geo';

const setupGeoRegionsPage = async (page: Page) => {
  await setupAdminAuth(page);
  await setupGeoRegionsCollectionApi(page, geoRegionsFixture);
};

test.describe('Admin geo regions page', () => {
  test('redirects unauthenticated user from /admin/geo/regions to /login', async ({ page }) => {
    await page.goto('/admin/geo/regions');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows regions table for authenticated admin', async ({ page }) => {
    await setupGeoRegionsPage(page);
    await page.goto('/admin/geo/regions');

    await expect(page.getByRole('heading', { level: 2, name: 'Регионы' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Московская область', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Гомельская область', exact: true })).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('filters regions by search automatically', async ({ page }) => {
    await setupGeoRegionsPage(page);
    await page.goto('/admin/geo/regions');

    await page.locator('input.admin-input').first().fill('ГОМЕЛ');

    await expect(page).toHaveURL(/search=%D0%93%D0%9E%D0%9C%D0%95%D0%9B/);
    await expect(page.getByText('Московская область', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Гомельская область', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('deletes region through confirm modal', async ({ page }) => {
    await setupGeoRegionsPage(page);
    await page.goto('/admin/geo/regions');

    await page.locator('tbody tr').first().getByRole('button', { name: 'Удалить' }).click();

    const dialog = page.locator('[role="dialog"]');
    await expect(dialog).toBeVisible();
    const deleteResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'DELETE' &&
        response.url().includes('/api/admin/geo/regions/')
    );
    await dialog.getByRole('button', { name: 'Удалить' }).click();
    const deleteResponse = await deleteResponsePromise;
    expect(deleteResponse.status()).toBe(200);

    await expect(page.locator('tbody tr')).toHaveCount(1);
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });
});
