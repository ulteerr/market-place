import { expect, test, type Page } from '@playwright/test';
import { setGuestPreferencesCookie } from '../helpers/guest-preferences';
import { E2E_VIEWPORTS } from '../helpers/viewports';

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasHorizontalOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasHorizontalOverflow).toBeFalsy();
};

const waitForNuxtHydration = async (page: Page) => {
  await page.waitForFunction(() => {
    const runtimeWindow = window as Window & {
      useNuxtApp?: () => { isHydrating?: boolean };
    };

    if (typeof runtimeWindow.useNuxtApp !== 'function') {
      return false;
    }

    return runtimeWindow.useNuxtApp()?.isHydrating === false;
  });
};

const setManualGuestCity = async (page: Page) => {
  await setGuestPreferencesCookie(page, {
    v: 1,
    settings: {
      locale: null,
      theme: 'light',
      collapse_menu: false,
      favorites: [],
      public_city: {
        city_id: 'city-msk',
        city_name: 'Москва',
        source: 'manual',
        region_id: 'region-msk',
        region_name: 'Москва',
        country_id: 'country-ru',
        country_name: 'Россия',
      },
      admin_crud_preferences: {},
      admin_navigation_sections: {},
    },
  });
};

test.describe('Public header responsive', () => {
  test('renders desktop navigation contract', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.desktop1366);
    await setManualGuestCity(page);
    await page.goto('/');
    await waitForNuxtHydration(page);

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
    await setManualGuestCity(page);
    await page.goto('/');
    await waitForNuxtHydration(page);

    await expect(page.locator('[data-test="public-header"]').last()).toBeVisible();
    await expect(page.locator('[data-test="public-header-bottom-row"]').last()).toBeVisible();
    await expect(
      page.locator('[data-test="public-header-mobile-menu-toggle"]').last()
    ).toBeVisible();

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
    await setManualGuestCity(page);
    await page.goto('/');
    await waitForNuxtHydration(page);

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
