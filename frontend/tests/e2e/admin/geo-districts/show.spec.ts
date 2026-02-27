import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupGeoDistrictShowApi } from '../../helpers/crud/geo';

const shownDistrict = {
  id: 'd-1',
  name: 'Арбат',
  city_id: 'ct-1',
};

test.describe('Admin geo districts show page', () => {
  test('redirects unauthenticated user from /admin/geo/districts/[id] to /login', async ({
    page,
  }) => {
    await page.goto('/admin/geo/districts/d-1');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows district data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);
    await setupGeoDistrictShowApi(page, shownDistrict);

    await page.goto('/admin/geo/districts/d-1');

    await expect(page.getByRole('heading', { level: 2, name: 'Район' })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Арбат$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^ct-1$/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/geo/districts/d-1/edit"]')).toBeVisible();
  });
});
