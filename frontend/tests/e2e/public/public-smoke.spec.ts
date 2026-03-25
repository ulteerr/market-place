import { expect, test } from '@playwright/test';

test.describe('Public routes smoke', () => {
  test('renders header sections without transient loading skeleton on public ssr pages', async ({
    page,
  }) => {
    await page.goto('/');

    await expect(page.locator('[data-test="public-header-sections-loading"]')).toHaveCount(0);
    await expect(page.locator('[data-test="public-header-bottom-row"]')).toBeVisible();
    await expect(page.locator('[data-test="home-featured-activities-loading"]')).toHaveCount(0);
    await expect(page.locator('[data-test="home-new-activities-loading"]')).toHaveCount(0);

    await page.goto('/catalog');
    await expect(page.locator('[data-test="public-header-sections-loading"]')).toHaveCount(0);
    await expect(page.locator('[data-test="catalog-category-browser-loading"]')).toHaveCount(0);
    await expect(page.locator('[data-test="catalog-category-browser"]')).toBeVisible();
  });

  test('renders public home and primary public routes', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('[data-test="public-header"]')).toBeVisible();
    await expect(page.locator('[data-test="public-header-bottom-row"]')).toContainText('Главная');
    await expect(page.locator('[data-test="home-featured-activities-grid"]')).toBeVisible();
    await expect(page.locator('[data-test="home-new-activities-grid"]')).toBeVisible();

    await page.goto('/catalog');
    await expect(page.locator('[data-test="catalog-activities-grid"]')).toBeVisible();

    await page.goto('/content');
    await expect(page.locator('[data-test="content-pages-grid"]')).toBeVisible();
  });

  test('navigates to SEO-ready category pages from public header', async ({ page }) => {
    await page.goto('/');

    const firstSectionLink = page
      .locator('[data-test="public-header-bottom-row"] a')
      .filter({ hasNotText: 'Главная' })
      .first();

    await firstSectionLink.click();
    await expect(page).toHaveURL(/\/catalog\/.+/);
    await expect(page.locator('[data-test="catalog-root-category-page"]')).toBeVisible();
    await expect(page.locator('[data-test="catalog-root-category-activities-grid"]')).toBeVisible();

    const firstChildLink = page.locator('[data-test="catalog-root-category-children"] a').first();
    await expect(firstChildLink).toBeVisible();
    await firstChildLink.click();
    await expect(page).toHaveURL(/\/catalog\/[^/]+\/[^/]+$/);
    await expect(page.locator('[data-test="catalog-leaf-category-page"]')).toBeVisible();
    const leafContentLocator = page.locator(
      '[data-test="catalog-leaf-category-activities-grid"], [data-test="catalog-leaf-category-empty"]'
    );
    await expect(leafContentLocator.first()).toBeVisible();
  });

  test('renders public activity card page', async ({ page }) => {
    await page.goto('/');

    const firstActivityLink = page.locator('[data-test="home-featured-activities-grid"] a').first();
    await expect(firstActivityLink).toBeVisible();
    await firstActivityLink.click();

    await expect(page).toHaveURL(/\/[^/]+\/[^/]+\/[^/]+$/);
    await expect(page.locator('[data-test="public-activity-swiper"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-summary"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-details"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-lead-card"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-actions"]')).toBeVisible();
    await expect(page.locator('h1')).not.toHaveText('');
  });

  test('renders skeleton state for public home', async ({ page }) => {
    await page.goto('/?state=loading');

    await expect(page.locator('[data-test="home-new-activities-loading"]')).toBeVisible();
  });

  test('renders skeleton state for public catalog', async ({ page }) => {
    await page.goto('/catalog?state=loading');

    await expect(page.locator('[data-test="catalog-activities-loading"]')).toBeVisible();
  });

  test('renders skeleton state for public activity page', async ({ page }) => {
    await page.goto('/sport/futbol/faz-zenit-activity-uuid?state=loading');

    await expect(page.locator('[data-test="public-activity-loading"]')).toBeVisible();
  });
});
