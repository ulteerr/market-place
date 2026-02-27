import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupGeoRegionShowApi } from '../../helpers/crud/geo';

const shownRegion = {
  id: 'r-1',
  name: 'Московская область',
  country_id: 'c-1',
};

test.describe('Admin geo regions show page', () => {
  test('redirects unauthenticated user from /admin/geo/regions/[id] to /login', async ({
    page,
  }) => {
    await page.goto('/admin/geo/regions/r-1');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows region data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);
    await setupGeoRegionShowApi(page, shownRegion);

    await page.goto('/admin/geo/regions/r-1');

    await expect(page.getByRole('heading', { level: 2, name: 'Регион' })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Московская область$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^c-1$/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/geo/regions/r-1/edit"]')).toBeVisible();
  });
});
