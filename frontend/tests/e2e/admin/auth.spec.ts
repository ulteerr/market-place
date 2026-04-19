import { expect, test } from '@playwright/test';
import { setAdminAuthCookies } from '../helpers/admin-auth';
import {
  readGuestPreferencesCookie,
  setGuestPreferencesCookie,
} from '../helpers/guest-preferences';

const waitForNuxtHydration = async (page: import('@playwright/test').Page) => {
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

test.describe('Admin authorization', () => {
  test('redirects unauthenticated user from /admin to /login', async ({ page }) => {
    await page.goto('/admin');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('allows authenticated admin to open /admin', async ({ page }) => {
    await setAdminAuthCookies(page, {
      id: '1',
      email: 'admin@example.com',
      permissions: ['admin.panel.access'],
    });

    await page.goto('/admin');
    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.locator('[data-test="home-users-stats"]')).toBeVisible();
  });

  test('allows admin login via form and redirects to /admin', async ({ page }) => {
    let loginRequestBody: Record<string, unknown> | null = null;

    await page.route('**/api/auth/login', async (route) => {
      loginRequestBody = route.request().postDataJSON();

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          token: 'playwright-admin-token',
          user: {
            id: '1',
            email: 'admin@example.com',
            first_name: 'Системный',
            last_name: 'Админ',
            permissions: ['admin.panel.access'],
          },
        }),
      });
    });

    await page.goto('/login');
    await waitForNuxtHydration(page);
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('password123');
    await page.getByRole('button', { name: 'Войти' }).click();

    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.locator('[data-test="home-users-stats"]')).toBeVisible();
    expect(loginRequestBody).toEqual({
      email: 'admin@example.com',
      password: 'password123',
    });
  });

  test('shows login error on invalid credentials', async ({ page }) => {
    await page.route('**/api/auth/login', async (route) => {
      await route.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'error',
          message: 'Invalid credentials',
        }),
      });
    });

    await page.goto('/login');
    await waitForNuxtHydration(page);
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('wrong-password');
    await page.getByRole('button', { name: 'Войти' }).click();

    await expect(page).toHaveURL(/\/login(?:\?|$)/);
    await expect(
      page.getByText('Не удалось авторизоваться. Проверьте email и пароль.')
    ).toBeVisible();
  });

  test('merges guest city settings into account settings after login', async ({ page }) => {
    let loginRequestBody: Record<string, unknown> | null = null;
    const settingsPatchPayloads: Array<Record<string, unknown>> = [];

    await setGuestPreferencesCookie(page, {
      v: 1,
      settings: {
        locale: null,
        theme: 'light',
        collapse_menu: false,
        favorites: ['guest-favorite'],
        public_city: {
          city_id: 'city-spb',
          city_name: 'Санкт-Петербург',
          source: 'manual',
          region_id: null,
          region_name: null,
          country_id: 'country-ru',
          country_name: 'Россия',
        },
        admin_crud_preferences: {},
        admin_navigation_sections: {},
      },
      favorites: ['guest-favorite'],
    });

    const mergedSettings = {
      locale: 'en',
      theme: 'dark',
      collapse_menu: true,
      favorites: ['guest-favorite', 'account-favorite'],
      public_city: {
        city_id: 'city-spb',
        city_name: 'Санкт-Петербург',
        source: 'manual',
        region_id: null,
        region_name: null,
        country_id: 'country-ru',
        country_name: 'Россия',
      },
      admin_crud_preferences: {},
      admin_navigation_sections: {
        system: {
          open: true,
        },
      },
    };

    await page.route('**/api/auth/login', async (route) => {
      loginRequestBody = route.request().postDataJSON();

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          token: 'playwright-admin-token',
          user: {
            id: '1',
            email: 'admin@example.com',
            first_name: 'Системный',
            last_name: 'Админ',
            permissions: ['admin.panel.access'],
            settings: {
              locale: 'en',
              theme: 'dark',
              collapse_menu: true,
              favorites: ['account-favorite'],
              public_city: null,
              admin_crud_preferences: {},
              admin_navigation_sections: {
                system: {
                  open: true,
                },
              },
            },
          },
        }),
      });
    });

    await page.route('**/api/me/settings', async (route) => {
      const rawBody = route.request().postData() ?? '{}';
      settingsPatchPayloads.push(JSON.parse(rawBody) as Record<string, unknown>);

      await route.fulfill({
        status: 204,
        body: '',
      });
    });

    await page.route('**/api/me', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          user: {
            id: '1',
            email: 'admin@example.com',
            first_name: 'Системный',
            last_name: 'Админ',
            permissions: ['admin.panel.access'],
            settings: mergedSettings,
          },
        }),
      });
    });

    await page.route('**/api/admin/users/stats', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            total_users: 1200,
            online_users: 87,
            updated_at: '2026-03-07T12:00:00.000Z',
          },
        }),
      });
    });

    await page.goto('/login');
    await waitForNuxtHydration(page);
    await page.locator('input[type="email"]').fill('admin@example.com');
    await page.locator('input[type="password"]').fill('password123');
    await page.getByRole('button', { name: 'Войти' }).click();

    await expect(page).toHaveURL(/\/admin$/);
    await expect(page.locator('[data-test="home-users-stats"]')).toBeVisible();

    expect(loginRequestBody).toEqual({
      email: 'admin@example.com',
      password: 'password123',
    });
    expect(settingsPatchPayloads).toHaveLength(1);
    expect(settingsPatchPayloads[0]?.settings).toEqual(mergedSettings);
    expect(await readGuestPreferencesCookie(page)).toBeNull();
  });
});
