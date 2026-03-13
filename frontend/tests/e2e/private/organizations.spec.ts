import { expect, test } from '@playwright/test';
import { setupAppAuth } from '../helpers/app-auth';

test.describe('Organizations routes and permissions', () => {
  test('renders organizations overview and join requests happy-path for allowed user', async ({
    page,
  }) => {
    await setupAppAuth(page, {
      permissions: ['org.company.profile.read', 'org.members.read', 'org.children.read'],
      roles: ['participant', 'manager'],
    });

    await page.goto('/organizations');
    await expect(page.locator('[data-test="organizations-overview-page"]')).toBeVisible();
    await expect(page.locator('[data-test="organizations-join-requests-section"]')).toBeVisible();

    await page.getByRole('link', { name: 'Все заявки' }).click();
    await expect(page).toHaveURL(/\/organizations\/join-requests$/);
    await expect(page.locator('[data-test="organizations-join-requests-inbox"]')).toBeVisible();
    await expect(page.locator('[data-test="organizations-join-requests-history"]')).toBeVisible();
  });

  test('redirects user without members permission from members page to overview', async ({
    page,
  }) => {
    await setupAppAuth(page, {
      permissions: ['org.company.profile.read'],
      roles: ['participant', 'member'],
    });

    await page.goto('/organizations/members');

    await expect(page).toHaveURL(/\/organizations$/);
    await expect(page.locator('[data-test="organizations-overview-page"]')).toBeVisible();
  });

  test('renders skeleton state for organizations members page', async ({ page }) => {
    await setupAppAuth(page, {
      permissions: ['org.company.profile.read', 'org.members.read'],
      roles: ['participant', 'manager'],
    });

    await page.goto('/organizations/members?state=loading');

    await expect(page.locator('[data-test="organizations-members-loading"]')).toBeVisible();
  });
});
