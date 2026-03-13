import type { Page } from '@playwright/test';
import {
  defaultAdminUser,
  setupAdminAuth,
  type AdminAuthUser,
  type AdminAuthUserOverrides,
} from './admin-auth';

const defaultAppUser: AdminAuthUser = {
  ...defaultAdminUser,
  roles: ['participant'],
  permissions: [],
  settings: {
    locale: 'ru',
    theme: 'light',
    collapse_menu: false,
    admin_crud_preferences: {},
    admin_navigation_sections: {},
  },
};

const mergeUser = (overrides: AdminAuthUserOverrides = {}): AdminAuthUserOverrides => ({
  ...defaultAppUser,
  ...overrides,
  settings: {
    ...(defaultAppUser.settings ?? {}),
    ...(overrides.settings ?? {}),
  },
});

export const setupAppAuth = async (page: Page, overrides: AdminAuthUserOverrides = {}) => {
  await page.route('**/api/me/settings', async (route) => {
    await route.fulfill({
      status: 204,
      body: '',
    });
  });

  await setupAdminAuth(page, mergeUser(overrides));
};
