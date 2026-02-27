import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupGeoCountryShowApi } from '../../helpers/crud/geo';

const shownCountry = {
  id: 'c-1',
  name: 'Россия',
  iso_code: 'RU',
};

test.describe('Admin geo countries show page', () => {
  test('redirects unauthenticated user from /admin/geo/countries/[id] to /login', async ({
    page,
  }) => {
    await page.goto('/admin/geo/countries/c-1');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows country data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);
    await setupGeoCountryShowApi(page, shownCountry);

    await page.goto('/admin/geo/countries/c-1');

    await expect(page.getByRole('heading', { level: 2, name: 'Страна' })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Россия$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^RU$/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/geo/countries/c-1/edit"]')).toBeVisible();
  });
});
