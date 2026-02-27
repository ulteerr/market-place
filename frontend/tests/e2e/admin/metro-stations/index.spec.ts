import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import {
  metroLinesFixture,
  metroStationsFixture,
  setupMetroLinesCollectionApi,
  setupMetroStationsCollectionApi,
} from '../../helpers/crud/metro';

const setupMetroStationsPage = async (page: Page) => {
  await setupAdminAuth(page);
  await setupMetroLinesCollectionApi(page, metroLinesFixture);
  await setupMetroStationsCollectionApi(page, metroStationsFixture);
};

test.describe('Admin metro stations page', () => {
  test('redirects unauthenticated user from /admin/metro-stations to /login', async ({ page }) => {
    await page.goto('/admin/metro-stations');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows metro stations table for authenticated admin', async ({ page }) => {
    await setupMetroStationsPage(page);
    await page.goto('/admin/metro-stations');

    await expect(page.getByRole('heading', { level: 2, name: 'Станции метро' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Охотный ряд', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Арбатская', exact: true })).toBeVisible();
    await expect(page.getByText('Сокольническая', { exact: true })).toBeVisible();
    await expect(page.locator('a[href="/admin/metro-lines/ml-1"]')).toBeVisible();
    await expect(page.locator('a[href="/admin/metro-stations?search=msk"]').first()).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('filters metro stations by search automatically', async ({ page }) => {
    await setupMetroStationsPage(page);
    await page.goto('/admin/metro-stations');

    await page.locator('input.admin-input').first().fill('АРБАТ');

    await expect(page).toHaveURL(/search=%D0%90%D0%A0%D0%91%D0%90%D0%A2/);
    await expect(page.getByText('Охотный ряд', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Арбатская', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('deletes metro station through confirm modal', async ({ page }) => {
    await setupMetroStationsPage(page);
    await page.goto('/admin/metro-stations');

    await page.locator('tbody tr').first().getByRole('button', { name: 'Удалить' }).click();

    const dialog = page.locator('[role="dialog"]');
    await expect(dialog).toBeVisible();
    const deleteResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'DELETE' &&
        response.url().includes('/api/admin/metro-stations/')
    );
    await dialog.getByRole('button', { name: 'Удалить' }).click();
    const deleteResponse = await deleteResponsePromise;
    expect(deleteResponse.status()).toBe(200);

    await expect(page.locator('tbody tr')).toHaveCount(1);
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });
});
