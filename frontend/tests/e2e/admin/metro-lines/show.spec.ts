import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupMetroLineShowApi } from '../../helpers/crud/metro';

const shownLine = {
  id: 'ml-2',
  name: 'Арбатско-Покровская',
  external_id: 'line-ext-2',
  line_id: '3',
  color: '#2B4EA2',
  city_id: 'msk',
  source: 'import',
};

test.describe('Admin metro lines show page', () => {
  test('redirects unauthenticated user from /admin/metro-lines/[id] to /login', async ({
    page,
  }) => {
    await page.goto('/admin/metro-lines/ml-2');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows metro line data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);
    await setupMetroLineShowApi(page, shownLine);

    await page.goto('/admin/metro-lines/ml-2');

    await expect(page.getByRole('heading', { level: 2, name: 'Линия метро' })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Арбатско-Покровская$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^line-ext-2$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^3$/ })).toBeVisible();
    const colorField = page
      .locator('dt', { hasText: 'Цвет' })
      .locator('xpath=following-sibling::dd[1]')
      .locator('span[aria-hidden="true"]')
      .first();
    await expect(colorField).toBeVisible();
    await expect(page.locator('dd', { hasText: /^msk$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^import$/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/metro-lines/ml-2/edit"]')).toBeVisible();
  });
});
