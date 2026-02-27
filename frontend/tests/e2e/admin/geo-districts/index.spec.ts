import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { geoDistrictsFixture, setupGeoDistrictsCollectionApi } from '../../helpers/crud/geo';

const setupGeoDistrictsPage = async (page: Page) => {
  await setupAdminAuth(page);
  await setupGeoDistrictsCollectionApi(page, geoDistrictsFixture);
};

test.describe('Admin geo districts page', () => {
  test('redirects unauthenticated user from /admin/geo/districts to /login', async ({ page }) => {
    await page.goto('/admin/geo/districts');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows districts table for authenticated admin', async ({ page }) => {
    await setupGeoDistrictsPage(page);
    await page.goto('/admin/geo/districts');

    await expect(page.getByRole('heading', { level: 2, name: 'Районы' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Арбат', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Центральный', exact: true })).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('filters districts by search automatically', async ({ page }) => {
    await setupGeoDistrictsPage(page);
    await page.goto('/admin/geo/districts');

    await page.locator('input.admin-input').first().fill('ЦЕНТР');

    await expect(page).toHaveURL(/search=%D0%A6%D0%95%D0%9D%D0%A2%D0%A0/);
    await expect(page.getByText('Арбат', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Центральный', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('deletes district through confirm modal', async ({ page }) => {
    await setupGeoDistrictsPage(page);
    await page.goto('/admin/geo/districts');

    await page.locator('tbody tr').first().getByRole('button', { name: 'Удалить' }).click();

    const dialog = page.locator('[role="dialog"]');
    await expect(dialog).toBeVisible();
    const deleteResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'DELETE' &&
        response.url().includes('/api/admin/geo/districts/')
    );
    await dialog.getByRole('button', { name: 'Удалить' }).click();
    const deleteResponse = await deleteResponsePromise;
    expect(deleteResponse.status()).toBe(200);

    await expect(page.locator('tbody tr')).toHaveCount(1);
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });
});
