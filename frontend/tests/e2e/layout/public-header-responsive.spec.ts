import { expect, test, type Page } from '@playwright/test';
import { E2E_VIEWPORTS } from '../helpers/viewports';

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasHorizontalOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasHorizontalOverflow).toBeFalsy();
};

test.describe('Public header responsive', () => {
  test('renders desktop navigation contract', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.desktop1366);
    await page.goto('/');

    await expect(page.locator('[data-test="public-header"]').last()).toBeVisible();
    await expect(page.locator('[data-test="public-header-search"]').last()).toBeVisible();
    await expect(page.locator('[data-test="public-header-bottom-row"]').last()).toBeVisible();

    const catalogToggle = page.locator('[data-test="public-header-catalog-toggle"]').last();
    await catalogToggle.click();
    await expect(page.locator('[data-test="public-header-catalog-menu"]').last()).toBeVisible();
    await expect(catalogToggle).toHaveAttribute('aria-expanded', 'true');

    await page.keyboard.press('Escape');
    await expect(page.locator('[data-test="public-header-catalog-menu"]')).toHaveCount(0);
    await expect(catalogToggle).toHaveAttribute('aria-expanded', 'false');
    await assertNoHorizontalOverflow(page);
  });

  test('renders tablet navigation contract', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.tablet768);
    await page.goto('/');

    await expect(page.locator('[data-test="public-header"]').last()).toBeVisible();
    await expect(page.locator('[data-test="public-header-bottom-row"]').last()).toBeVisible();
    await expect(
      page.locator('[data-test="public-header-mobile-menu-toggle"]').last()
    ).toBeVisible();
    await expect(page.locator('[data-test="public-header-search"]').last()).toBeVisible();

    const mobileToggle = page.locator('[data-test="public-header-mobile-menu-toggle"]').last();
    await mobileToggle.click();
    await expect(page.locator('[data-test="public-header-mobile-menu"]').last()).toBeVisible();
    await expect(page.locator('[data-test="public-header-mobile-search"]').last()).toBeVisible();

    await mobileToggle.click();
    await expect(page.locator('[data-test="public-header-mobile-menu"]')).toHaveCount(0);
    await assertNoHorizontalOverflow(page);
  });

  test('renders mobile navigation contract', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.mobile390);
    await page.goto('/');

    await expect(page.locator('[data-test="public-header"]').last()).toBeVisible();
    await expect(
      page.locator('[data-test="public-header-mobile-menu-toggle"]').last()
    ).toBeVisible();

    const catalogToggle = page.locator('[data-test="public-header-catalog-toggle"]').last();
    await catalogToggle.click();
    await expect(page.locator('[data-test="public-header-catalog-menu"]').last()).toBeVisible();

    const mobileToggle = page.locator('[data-test="public-header-mobile-menu-toggle"]').last();
    await mobileToggle.click();
    await expect(page.locator('[data-test="public-header-mobile-menu"]').last()).toBeVisible();
    await expect(page.locator('[data-test="public-header-mobile-search"]').last()).toBeVisible();
    await assertNoHorizontalOverflow(page);
  });
});
