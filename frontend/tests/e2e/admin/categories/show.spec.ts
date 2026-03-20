import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

const categoryPermissions = ['admin.categories.read', 'admin.categories.update'];

test.describe('Admin categories show page', () => {
  test('shows category data for authenticated admin', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
    });

    await page.route('**/api/admin/categories/cat-root-sport', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            id: 'cat-root-sport',
            name: 'Спорт',
            slug: 'sport',
            parent_id: null,
            sort_order: 10,
            is_active: true,
            children_count: 2,
            activities_count: 4,
            parent: null,
            children: [
              {
                id: 'cat-leaf-football',
                name: 'Футбол',
                slug: 'futbol',
                parent_id: 'cat-root-sport',
                sort_order: 20,
                is_active: true,
              },
              {
                id: 'cat-leaf-volleyball',
                name: 'Волейбол',
                slug: 'volejbol',
                parent_id: 'cat-root-sport',
                sort_order: 30,
                is_active: true,
              },
            ],
          },
        }),
      });
    });

    await page.goto('/admin/categories/cat-root-sport');

    const details = page.locator('dl');
    const values = details.locator('dd');
    const breadcrumbs = page.locator('nav[aria-label="Хлебные крошки"]');

    await expect(page.getByRole('heading', { level: 2, name: 'Категория' })).toBeVisible();
    await expect(
      page.locator('.admin-sidebar').getByText('Категории', { exact: true }).first()
    ).toHaveCount(1);
    await expect(breadcrumbs).toContainText('Главная');
    await expect(breadcrumbs).toContainText('Организация');
    await expect(breadcrumbs).toContainText('Категории');
    await expect(breadcrumbs).toContainText('cat-root-sport');
    await expect(values.nth(0)).toHaveText('Спорт');
    await expect(values.nth(1)).toHaveText('sport');
    await expect(values.nth(6)).toHaveText('4');
    await expect(page.getByText('Футбол', { exact: true })).toBeVisible();
    await expect(page.getByText('Волейбол', { exact: true })).toBeVisible();
    await expect(page.locator('a[href="/admin/categories/cat-root-sport/edit"]')).toBeVisible();
    await expect(
      page.locator('a[href="/admin/categories?parent_id=cat-leaf-football"]')
    ).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'История изменений' })).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'Журнал изменений' })).toBeVisible();
  });

  test('hides change log panel without changelog permission and keeps action log panel', async ({
    page,
  }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions].filter(
        (permission) => permission !== 'admin.changelog.read'
      ),
    });

    await page.route('**/api/admin/categories/cat-root-sport', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            id: 'cat-root-sport',
            name: 'Спорт',
            slug: 'sport',
            parent_id: null,
            sort_order: 10,
            is_active: true,
            children_count: 0,
            activities_count: 0,
            parent: null,
            children: [],
          },
        }),
      });
    });

    await page.goto('/admin/categories/cat-root-sport');

    await expect(page.getByRole('heading', { level: 3, name: 'История изменений' })).toHaveCount(0);
    await expect(page.getByRole('heading', { level: 3, name: 'Журнал изменений' })).toBeVisible();
  });

  test('shows load error on show page', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
    });

    await page.route('**/api/admin/categories/cat-root-sport', async (route) => {
      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'error',
          message: 'Show failed',
        }),
      });
    });

    await page.goto('/admin/categories/cat-root-sport');

    await expect(page.getByText('Show failed')).toBeVisible();
  });

  test('shows skeleton while show page is loading', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
    });

    await page.route('**/api/admin/categories/cat-root-sport', async (route) => {
      await new Promise((resolve) => setTimeout(resolve, 1200));
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            id: 'cat-root-sport',
            name: 'Спорт',
            slug: 'sport',
            parent_id: null,
            sort_order: 10,
            is_active: true,
            children_count: 0,
            activities_count: 0,
            parent: null,
            children: [],
          },
        }),
      });
    });

    await page.goto('/admin/categories/cat-root-sport');

    await expect(page.locator('.admin-page-skeleton')).toBeVisible();
    await expect(page.getByText('Спорт', { exact: true })).toBeVisible();
  });
});
