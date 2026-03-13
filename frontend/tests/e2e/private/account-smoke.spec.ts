import { expect, test } from '@playwright/test';
import { setupAppAuth } from '../helpers/app-auth';

test.describe('Account routes smoke', () => {
  test('renders dashboard and profile/activity routes for authenticated user', async ({ page }) => {
    await setupAppAuth(page, {
      id: '42',
      email: 'member@example.com',
      first_name: 'Ирина',
      last_name: 'Тестова',
    });

    await page.goto('/account');
    await expect(page.locator('[data-test="account-dashboard-page"]')).toBeVisible();
    await expect(page.locator('[data-test="account-dashboard-metrics"]')).toBeVisible();

    await page.goto('/account/profile');
    await expect(page.locator('[data-test="account-profile-page"]')).toBeVisible();
    await expect(page.locator('[data-test="account-profile-preferences"]')).toBeVisible();

    await page.goto('/account/activity');
    await expect(page.locator('[data-test="account-activity-page"]')).toBeVisible();
    await expect(page.locator('[data-test="account-activity-requests"]')).toBeVisible();
  });

  test('navigates from dashboard to activity through account UI action', async ({ page }) => {
    await setupAppAuth(page, {
      settings: {
        theme: 'light',
      },
    });

    await page.goto('/account');
    await expect(page.locator('[data-test="account-dashboard-page"]')).toBeVisible();

    await page.getByRole('link', { name: 'Все активности' }).click();

    await expect(page).toHaveURL(/\/account\/activity$/);
    await expect(page.locator('[data-test="account-activity-page"]')).toBeVisible();
    await expect(page.locator('[data-test="account-activity-timeline"]')).toBeVisible();
  });

  test('renders skeleton state for account dashboard', async ({ page }) => {
    await setupAppAuth(page);

    await page.goto('/account?state=loading');

    await expect(page.locator('[data-test="account-dashboard-loading"]')).toBeVisible();
  });
});
