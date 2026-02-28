import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

const childrenPermissions = ['org.children.read', 'org.children.write'];

test.describe('Admin children show page', () => {
  const profileValueByLabel = (page: Page, label: string) => {
    return page
      .locator('dl > div', { has: page.locator('dt', { hasText: label }) })
      .locator('dd')
      .first();
  };

  test('redirects unauthenticated user from /admin/children/[id] to /login', async ({ page }) => {
    await page.goto('/admin/children/c-1');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows child profile data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...childrenPermissions],
    });

    await page.route('**/api/admin/action-logs**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
            data: [],
          },
        }),
      });
    });

    await page.route('**/api/admin/children/c-1', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            id: 'c-1',
            user_id: 'u-1',
            first_name: 'Виктория',
            last_name: 'Кузнецова',
            middle_name: 'Тимофеевна',
            gender: 'female',
            birth_date: '2018-07-14',
            user: {
              id: 'u-1',
              first_name: 'Павел',
              last_name: 'Кузнецов',
              middle_name: 'Иванович',
              email: 'pavel@example.com',
            },
          },
        }),
      });
    });

    const childResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        response.url().includes('/api/admin/children/c-1') &&
        response.status() === 200
    );

    await page.goto('/admin/children/c-1');
    await childResponsePromise;

    await expect(page.getByRole('heading', { level: 2, name: 'Профиль ребенка' })).toBeVisible();
    await expect(profileValueByLabel(page, 'ФИО')).toHaveText('Кузнецова Виктория Тимофеевна');
    await expect(profileValueByLabel(page, 'Дата рождения')).toHaveText('2018-07-14');
    await expect(profileValueByLabel(page, 'Пол')).toHaveText('Женский');
    await expect(profileValueByLabel(page, 'Пользователь')).toContainText(
      'Кузнецов Павел Иванович'
    );
    await expect(page.locator('a[href="/admin/users/u-1"]')).toBeVisible();
    await expect(page.locator('a[href="/admin/children/c-1/edit"]')).toBeVisible();
  });
});
