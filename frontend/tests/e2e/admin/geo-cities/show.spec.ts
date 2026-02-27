import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupGeoCityShowApi } from '../../helpers/crud/geo';

const shownCity = {
  id: 'ct-1',
  name: 'Москва',
  country_id: 'c-1',
  region_id: 'r-1',
};

test.describe('Admin geo cities show page', () => {
  test('redirects unauthenticated user from /admin/geo/cities/[id] to /login', async ({ page }) => {
    await page.goto('/admin/geo/cities/ct-1');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows city data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page);
    await setupGeoCityShowApi(page, shownCity);

    await page.goto('/admin/geo/cities/ct-1');

    await expect(page.getByRole('heading', { level: 2, name: 'Город' })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^Москва$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^c-1$/ })).toBeVisible();
    await expect(page.locator('dd', { hasText: /^r-1$/ })).toBeVisible();
    await expect(page.locator('a[href="/admin/geo/cities/ct-1/edit"]')).toBeVisible();
  });
});
