import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../helpers/admin-auth';
import { E2E_VIEWPORTS } from '../helpers/viewports';

interface NavigationSettings {
  collapse_menu: boolean;
  admin_navigation_sections?: Record<string, { open?: boolean }>;
}

const openMobileSidebar = async (page: import('@playwright/test').Page) => {
  await page.locator('header .admin-icon-button').first().click();
  await expect(page.locator('aside.admin-sidebar')).toHaveClass(/is-open/);
};

const setupNavigation = async (
  page: import('@playwright/test').Page,
  settings: NavigationSettings,
  path = '/admin'
) => {
  await setupAdminAuth(page, {
    ...defaultAdminUser,
    settings: {
      theme: 'dark',
      collapse_menu: settings.collapse_menu,
      admin_crud_preferences: {},
      admin_navigation_sections: settings.admin_navigation_sections ?? {},
    },
  });

  await page.goto(path);
};

test.describe('Admin navigation responsive', () => {
  test('mobile 390: navigation stays expanded mode', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.mobile390);
    await setupNavigation(page, {
      collapse_menu: true,
      admin_navigation_sections: { system: { open: true } },
    });

    await openMobileSidebar(page);

    const sidebar = page.locator('aside.admin-sidebar');
    await expect(sidebar).not.toHaveClass(/is-collapsed/);
    await expect(sidebar.locator('.admin-nav-collapsed-group')).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Система' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Пользователи' })).toBeVisible();
  });

  test('tablet 768: navigation stays expanded mode', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.tablet768);
    await setupNavigation(page, {
      collapse_menu: true,
      admin_navigation_sections: { system: { open: true } },
    });

    await openMobileSidebar(page);

    const sidebar = page.locator('aside.admin-sidebar');
    await expect(sidebar).not.toHaveClass(/is-collapsed/);
    await expect(sidebar.locator('.admin-nav-collapsed-group')).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Система' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Роли' })).toBeVisible();
  });

  test('desktop 1366: renders regular navigation when collapse disabled', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.desktop1366);
    await setupNavigation(page, {
      collapse_menu: false,
      admin_navigation_sections: { system: { open: false } },
    });

    const sidebar = page.locator('aside.admin-sidebar');
    await expect(sidebar).not.toHaveClass(/is-collapsed/);
    await expect(page.locator('.admin-sidebar-toggle')).toBeVisible();

    await page.getByRole('button', { name: 'Система' }).click();
    await expect(page.locator('.admin-nav-submenu')).toBeVisible();
  });

  test('ultra narrow 280: navigation stays expanded mode', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.ultraNarrow280);
    await setupNavigation(page, {
      collapse_menu: true,
      admin_navigation_sections: { system: { open: true } },
    });

    await openMobileSidebar(page);

    const sidebar = page.locator('aside.admin-sidebar');
    await expect(sidebar).not.toHaveClass(/is-collapsed/);
    await expect(sidebar.locator('.admin-nav-collapsed-group')).toHaveCount(0);
    await expect(page.getByRole('link', { name: 'Главная' })).toBeVisible();
  });

  test('desktop collapse mode: renders collapsed section group', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.desktop1366);
    await setupNavigation(page, {
      collapse_menu: true,
      admin_navigation_sections: { system: { open: false } },
    });

    const sidebar = page.locator('aside.admin-sidebar');
    await expect(sidebar).toHaveClass(/is-collapsed/);
    await expect(sidebar.locator('.admin-nav-collapsed-group')).toHaveCount(1);

    await page.locator('.admin-nav-section-collapsed-toggle').first().click();
    await expect(page.locator('.admin-nav-collapsed-panel')).toBeVisible();
  });

  test('desktop collapse mode: sidebar toggle switches collapsed state', async ({ page }) => {
    await page.setViewportSize(E2E_VIEWPORTS.desktop1366);
    await setupNavigation(page, {
      collapse_menu: true,
      admin_navigation_sections: { system: { open: false } },
    });

    const sidebar = page.locator('aside.admin-sidebar');
    const sidebarToggle = page.locator('.admin-sidebar-toggle');

    await expect(sidebar).toHaveClass(/is-collapsed/);
    await sidebarToggle.click();
    await expect(sidebar).not.toHaveClass(/is-collapsed/);

    await sidebarToggle.click();
    await expect(sidebar).toHaveClass(/is-collapsed/);
  });
});
