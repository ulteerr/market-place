import type { Page, Route } from '@playwright/test';

export const authOrigin = 'http://127.0.0.1:3000';

export interface AdminAuthUser {
  id: string;
  email: string;
  first_name?: string;
  last_name?: string;
  middle_name?: string;
  can_access_admin_panel: boolean;
  settings?: {
    theme: 'light' | 'dark';
    collapse_menu: boolean;
    admin_crud_preferences: Record<string, unknown>;
  };
}

export const defaultAdminUser: AdminAuthUser = {
  id: '1',
  email: 'admin@example.com',
  first_name: 'Админ',
  last_name: 'Системный',
  middle_name: 'Тестовый',
  can_access_admin_panel: true,
};

export const setAdminAuthCookies = async (page: Page, user: AdminAuthUser = defaultAdminUser) => {
  await page.context().addCookies([
    {
      name: 'auth_token',
      value: 'test-admin-token',
      url: authOrigin,
    },
    {
      name: 'auth_user',
      value: encodeURIComponent(JSON.stringify(user)),
      url: authOrigin,
    },
  ]);
};

export const mockMeEndpoint = async (page: Page, user: AdminAuthUser = defaultAdminUser) => {
  await page.route('**/api/me', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        user,
      }),
    });
  });
};

export const setupAdminAuth = async (page: Page, user: AdminAuthUser = defaultAdminUser) => {
  await setAdminAuthCookies(page, user);
  await mockMeEndpoint(page, user);
};
