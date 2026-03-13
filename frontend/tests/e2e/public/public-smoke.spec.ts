import { expect, test } from '@playwright/test';

test.describe('Public routes smoke', () => {
  test('renders public home and primary public routes', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('[data-test="public-header"]')).toBeVisible();
    await expect(page.locator('[data-test="home-public-routes-grid"]')).toBeVisible();

    await page.locator('[data-test="home-route-catalog"]').click();
    await expect(page).toHaveURL(/\/catalog$/);
    await expect(page.locator('[data-test="catalog-popular-categories-grid"]')).toBeVisible();

    await page.goto('/content');
    await expect(page.locator('[data-test="content-pages-grid"]')).toBeVisible();
  });

  test('renders skeleton state for public home', async ({ page }) => {
    await page.goto('/?state=loading');

    await expect(page.locator('[data-test="home-public-routes-loading"]')).toBeVisible();
  });
});
