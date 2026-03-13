import { expect, test, type Page } from '@playwright/test';
import { setupAppAuth } from '../helpers/app-auth';
import { E2E_RESPONSIVE_VIEWPORTS, E2E_VIEWPORTS } from '../helpers/viewports';

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasHorizontalOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasHorizontalOverflow).toBeFalsy();
};

test.describe('Public and private responsive smoke', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`renders public/account/organizations shells on ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      const enforceNoOverflow = viewport.name !== E2E_VIEWPORTS.ultraNarrow280.name;

      await setupAppAuth(page, {
        permissions: ['org.company.profile.read', 'org.members.read', 'org.children.read'],
        roles: ['participant', 'manager'],
      });

      await page.goto('/');
      await expect(page.locator('[data-test="public-header"]')).toBeVisible();
      await expect(page.locator('[data-test="home-public-routes"]').last()).toBeVisible();
      if (enforceNoOverflow) {
        await assertNoHorizontalOverflow(page);
      }

      await page.goto('/account');
      await expect(page.locator('[data-test="account-dashboard-page"]')).toBeVisible();
      await expect(page.locator('[data-test="account-dashboard-metrics"]')).toBeVisible();
      if (enforceNoOverflow) {
        await assertNoHorizontalOverflow(page);
      }

      await page.goto('/organizations');
      await expect(page.locator('[data-test="organizations-overview-page"]')).toBeVisible();
      await expect(page.locator('[data-test="organizations-overview-metrics"]')).toBeVisible();
      if (enforceNoOverflow) {
        await assertNoHorizontalOverflow(page);
      }
    });
  }
});
