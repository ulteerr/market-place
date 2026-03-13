import { expect, test } from '@playwright/test';

test.describe('Private route-groups auth guard', () => {
  test('redirects unauthenticated user from /account to /login', async ({ page }) => {
    await page.goto('/account');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('redirects unauthenticated user from /organizations to /login', async ({ page }) => {
    await page.goto('/organizations');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });
});
