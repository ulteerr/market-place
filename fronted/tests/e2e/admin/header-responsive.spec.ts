import type { Locator } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../helpers/admin-auth';
import { E2E_VIEWPORTS } from '../helpers/viewports';

const isElementInsideViewport = async (locator: Locator) =>
  locator.evaluate((element) => {
    const rect = element.getBoundingClientRect();

    return rect.left >= 0 && rect.right <= window.innerWidth;
  });

const assertNoHorizontalOverflow = async (page: import('@playwright/test').Page) => {
  const hasHorizontalOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasHorizontalOverflow).toBeFalsy();
};

const assertHeaderControlsVisibleAndInsideViewport = async (
  page: import('@playwright/test').Page
) => {
  const localeSelect = page.locator('.admin-topbar .admin-locale-select');
  const themeSwitcher = page.locator('.admin-topbar .theme-switcher-btn');

  await expect(localeSelect).toBeVisible();
  await expect(themeSwitcher).toBeVisible();
  await expect(await isElementInsideViewport(localeSelect)).toBeTruthy();
  await expect(await isElementInsideViewport(themeSwitcher)).toBeTruthy();
};

test.describe('Admin header responsive', () => {
  test('renders correctly on mobile viewport', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.mobile390);
    await setupAdminAuth(page);

    await page.goto('/admin');
    await expect(page.getByRole('heading', { level: 2, name: 'Панель управления' })).toBeVisible();
    await assertNoHorizontalOverflow(page);
    await assertHeaderControlsVisibleAndInsideViewport(page);

    await expect(page.locator('header .admin-icon-button').first()).toBeVisible();
    await expect(page.locator('.admin-sidebar-toggle')).toBeHidden();
  });

  test('renders correctly on tablet viewport', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.tablet768);
    await setupAdminAuth(page);

    await page.goto('/admin');
    await expect(page.getByRole('heading', { level: 2, name: 'Панель управления' })).toBeVisible();
    await assertNoHorizontalOverflow(page);
    await assertHeaderControlsVisibleAndInsideViewport(page);

    await expect(page.locator('header .admin-icon-button').first()).toBeVisible();
    await expect(page.locator('.admin-sidebar-toggle')).toBeHidden();
  });

  test('renders correctly on desktop viewport', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.desktop1366);
    await setupAdminAuth(page);

    await page.goto('/admin');
    await expect(page.getByRole('heading', { level: 2, name: 'Панель управления' })).toBeVisible();
    await assertNoHorizontalOverflow(page);
    await assertHeaderControlsVisibleAndInsideViewport(page);

    await expect(page.locator('header .admin-icon-button').first()).toBeHidden();
    await expect(page.locator('.admin-sidebar-toggle')).toBeVisible();
  });

  test('keeps header controls inside 280px viewport without horizontal overflow', async ({
    page,
  }) => {
    await page.setViewportSize(E2E_VIEWPORTS.ultraNarrow280);
    await setupAdminAuth(page);

    await page.goto('/admin');
    await expect(page.getByRole('heading', { level: 2, name: 'Панель управления' })).toBeVisible();
    await assertNoHorizontalOverflow(page);
    await assertHeaderControlsVisibleAndInsideViewport(page);
  });
});
