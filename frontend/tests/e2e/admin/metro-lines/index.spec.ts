import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { metroLinesFixture, setupMetroLinesCollectionApi } from '../../helpers/crud/metro';

const setupMetroLinesPage = async (page: Page) => {
  await setupAdminAuth(page);
  await setupMetroLinesCollectionApi(page, metroLinesFixture);
};

test.describe('Admin metro lines page', () => {
  test('redirects unauthenticated user from /admin/metro-lines to /login', async ({ page }) => {
    await page.goto('/admin/metro-lines');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows metro lines table for authenticated admin', async ({ page }) => {
    await setupMetroLinesPage(page);
    await page.goto('/admin/metro-lines');

    await expect(page.getByRole('heading', { level: 2, name: 'Линии метро' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Сокольническая', exact: true })).toBeVisible();
    await expect(
      page.getByRole('cell', { name: 'Арбатско-Покровская', exact: true })
    ).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('filters metro lines by search automatically', async ({ page }) => {
    await setupMetroLinesPage(page);
    await page.goto('/admin/metro-lines');

    await page.locator('input.admin-input').first().fill('АРБАТ');

    await expect(page).toHaveURL(/search=%D0%90%D0%A0%D0%91%D0%90%D0%A2/);
    await expect(page.getByText('Сокольническая', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Арбатско-Покровская', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('deletes metro line through confirm modal', async ({ page }) => {
    await setupMetroLinesPage(page);
    await page.goto('/admin/metro-lines');

    await page.locator('tbody tr').first().getByRole('button', { name: 'Удалить' }).click();

    const dialog = page.locator('[role="dialog"]');
    await expect(dialog).toBeVisible();
    const deleteResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'DELETE' &&
        response.url().includes('/api/admin/geo/metro-lines/')
    );
    await dialog.getByRole('button', { name: 'Удалить' }).click();
    const deleteResponse = await deleteResponsePromise;
    expect(deleteResponse.status()).toBe(200);

    await expect(page.locator('tbody tr')).toHaveCount(1);
    await expect(page.locator('tbody tr').first()).toContainText(
      /Сокольническая|Арбатско-Покровская/
    );
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });
});
