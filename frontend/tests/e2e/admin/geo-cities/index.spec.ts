import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { geoCitiesFixture, setupGeoCitiesCollectionApi } from '../../helpers/crud/geo';

const setupGeoCitiesPage = async (page: Page) => {
  await setupAdminAuth(page);
  await setupGeoCitiesCollectionApi(page, geoCitiesFixture);
};

test.describe('Admin geo cities page', () => {
  test('redirects unauthenticated user from /admin/geo/cities to /login', async ({ page }) => {
    await page.goto('/admin/geo/cities');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows cities table for authenticated admin', async ({ page }) => {
    await setupGeoCitiesPage(page);
    await page.goto('/admin/geo/cities');

    await expect(page.getByRole('heading', { level: 2, name: 'Города' })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Москва', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Гомель', exact: true })).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('filters cities by search automatically', async ({ page }) => {
    await setupGeoCitiesPage(page);
    await page.goto('/admin/geo/cities');

    await page.locator('input.admin-input').first().fill('ГОМЕЛ');

    await expect(page).toHaveURL(/search=%D0%93%D0%9E%D0%9C%D0%95%D0%9B/);
    await expect(page.getByText('Москва', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Гомель', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('deletes city through confirm modal', async ({ page }) => {
    await setupGeoCitiesPage(page);
    await page.goto('/admin/geo/cities');

    await page.locator('tbody tr').first().getByRole('button', { name: 'Удалить' }).click();

    const dialog = page.locator('[role="dialog"]');
    await expect(dialog).toBeVisible();
    const deleteResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'DELETE' &&
        response.url().includes('/api/admin/geo/cities/')
    );
    await dialog.getByRole('button', { name: 'Удалить' }).click();
    const deleteResponse = await deleteResponsePromise;
    expect(deleteResponse.status()).toBe(200);

    await expect(page.locator('tbody tr')).toHaveCount(1);
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });
});
