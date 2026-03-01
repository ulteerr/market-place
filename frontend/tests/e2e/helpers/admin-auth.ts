import type { Page, Route } from '@playwright/test';

const fallbackPort = process.env.PORT ?? '3000';
export const authOrigin = process.env.PLAYWRIGHT_BASE_URL ?? `http://127.0.0.1:${fallbackPort}`;

export interface AdminAuthUser {
  id: string;
  email: string;
  first_name?: string;
  last_name?: string;
  middle_name?: string;
  avatar?: {
    id: string;
    url: string;
    original_name: string;
    collection: string;
    mime_type?: string | null;
    size?: number;
  } | null;
  can_access_admin_panel: boolean;
  roles?: string[];
  permissions?: string[];
  settings?: {
    locale?: 'ru' | 'en' | null;
    theme: 'light' | 'dark';
    collapse_menu: boolean;
    admin_crud_preferences: Record<string, unknown>;
    admin_navigation_sections?: Record<string, { open?: boolean }>;
  };
}

export const defaultAdminUser: AdminAuthUser = {
  id: '1',
  email: 'admin@example.com',
  first_name: 'Админ',
  last_name: 'Системный',
  middle_name: 'Тестовый',
  can_access_admin_panel: true,
  roles: ['participant', 'admin'],
  permissions: [
    'admin.panel.access',
    'admin.users.read',
    'admin.users.create',
    'admin.users.update',
    'admin.users.delete',
    'admin.roles.read',
    'admin.roles.create',
    'admin.roles.update',
    'admin.roles.delete',
    'admin.changelog.read',
    'admin.changelog.rollback',
    'admin.action-log.read',
  ],
};

export const setAdminAuthCookies = async (page: Page, user: AdminAuthUser = defaultAdminUser) => {
  const mergedUser: AdminAuthUser = { ...defaultAdminUser, ...user };

  await page.context().addCookies([
    {
      name: 'auth_token',
      value: 'test-admin-token',
      url: authOrigin,
    },
    {
      name: 'auth_user',
      value: encodeURIComponent(JSON.stringify(mergedUser)),
      url: authOrigin,
    },
    {
      name: 'i18n_redirected',
      value: 'ru',
      url: authOrigin,
    },
  ]);
};

export const mockMeEndpoint = async (page: Page, user: AdminAuthUser = defaultAdminUser) => {
  const mergedUser: AdminAuthUser = { ...defaultAdminUser, ...user };

  await page.route('**/api/me', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        user: mergedUser,
      }),
    });
  });
};

export const setupAdminAuth = async (page: Page, user: AdminAuthUser = defaultAdminUser) => {
  await setAdminAuthCookies(page, user);
  await mockMeEndpoint(page, user);
  await page.route('**/api/admin/changelog**', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          list_mode: 'latest',
          current_page: 1,
          data: [],
          last_page: 1,
          per_page: 20,
          total: 0,
        },
      }),
    });
  });
  await page.route('**/api/admin/action-logs**', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          current_page: 1,
          last_page: 1,
          per_page: 20,
          total: 0,
          data: [],
        },
      }),
    });
  });
};
