import type { Page, Route } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

const categoryPermissions = [
  'admin.categories.read',
  'admin.categories.create',
  'admin.categories.update',
  'admin.categories.delete',
];

const categoriesFixture = [
  {
    id: 'cat-root-sport',
    name: 'Спорт',
    slug: 'sport',
    parent_id: null,
    sort_order: 10,
    is_active: true,
    children_count: 2,
    activities_count: 4,
    parent: null,
  },
  {
    id: 'cat-leaf-football',
    name: 'Футбол',
    slug: 'futbol',
    parent_id: 'cat-root-sport',
    sort_order: 20,
    is_active: true,
    children_count: 0,
    activities_count: 3,
    parent: {
      id: 'cat-root-sport',
      name: 'Спорт',
      slug: 'sport',
    },
  },
  {
    id: 'cat-leaf-volleyball',
    name: 'Волейбол',
    slug: 'volejbol',
    parent_id: 'cat-root-sport',
    sort_order: 30,
    is_active: true,
    children_count: 0,
    activities_count: 1,
    parent: {
      id: 'cat-root-sport',
      name: 'Спорт',
      slug: 'sport',
    },
  },
];

const setupCategoriesIndexPage = async (page: Page) => {
  await setupAdminAuth(page, {
    permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
  });

  let dataset = [...categoriesFixture];

  await page.route('**/api/admin/categories**', async (route: Route) => {
    if (route.request().method() === 'DELETE') {
      const categoryId = route.request().url().split('/').pop();
      dataset = dataset.filter((item) => item.id !== categoryId);

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          message: 'Deleted successfully',
        }),
      });
      return;
    }

    const url = new URL(route.request().url());
    const search = (url.searchParams.get('search') ?? '').trim().toLowerCase();
    const parentId = (url.searchParams.get('parent_id') ?? '').trim();
    const perPage = Number(url.searchParams.get('per_page') ?? 20);

    const filtered = dataset.filter((item) => {
      if (parentId && item.parent_id !== parentId) {
        return false;
      }

      if (!search) {
        return true;
      }

      return [item.name, item.slug, item.parent?.name]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(search));
    });

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          data: filtered.slice(0, perPage),
          current_page: 1,
          last_page: 1,
          per_page: perPage,
          total: filtered.length,
        },
      }),
    });
  });
};

test.describe('Admin categories page', () => {
  test('shows categories table for authenticated admin', async ({ page }) => {
    await setupCategoriesIndexPage(page);
    await page.goto('/admin/categories');

    const table = page.locator('table.admin-table');
    const sportRow = table.locator('tbody tr').filter({ hasText: 'Спорт' }).first();
    const footballRow = table.locator('tbody tr').filter({ hasText: 'Футбол' }).first();
    const breadcrumbs = page.locator('nav[aria-label="Хлебные крошки"]');

    await expect(page.getByRole('heading', { level: 2, name: 'Категории' })).toBeVisible();
    await expect(
      page.locator('.admin-sidebar').getByText('Категории', { exact: true }).first()
    ).toHaveCount(1);
    await expect(breadcrumbs).toContainText('Главная');
    await expect(breadcrumbs).toContainText('Организация');
    await expect(breadcrumbs).toContainText('Категории');
    await expect(sportRow).toBeVisible();
    await expect(footballRow).toBeVisible();
    await expect(page.getByText('Показано 3 из 3.')).toBeVisible();
    await expect(sportRow.getByRole('button', { name: '2' })).toBeVisible();
    await expect(sportRow.locator('td').nth(6)).toHaveText('4');
  });

  test('filters categories by search automatically', async ({ page }) => {
    await setupCategoriesIndexPage(page);
    await page.goto('/admin/categories');

    const searchInput = page.locator('main input.admin-input').first();
    await searchInput.fill('футбол');

    await expect(page.getByText('Футбол')).toBeVisible();
    await expect(page.getByText('Волейбол')).toHaveCount(0);
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('opens child categories via children count shortcut', async ({ page }) => {
    await setupCategoriesIndexPage(page);
    await page.goto('/admin/categories');

    const table = page.locator('table.admin-table');
    const sportRow = table.locator('tbody tr').filter({ hasText: 'Спорт' }).first();

    await sportRow.getByRole('button', { name: '2' }).click();

    await expect(page).toHaveURL(/parent_id=cat-root-sport/);
    await expect(page.getByText('Показаны дочерние категории для: Спорт')).toBeVisible();
    await expect(table.locator('tbody tr').filter({ hasText: 'Футбол' }).first()).toBeVisible();
    await expect(table.locator('tbody tr').filter({ hasText: 'Волейбол' }).first()).toBeVisible();
    await expect(table.locator('tbody tr')).toHaveCount(2);
  });

  test('deletes category through confirm modal', async ({ page }) => {
    await setupCategoriesIndexPage(page);
    await page.goto('/admin/categories');

    const table = page.locator('table.admin-table');
    const sportRow = table.locator('tbody tr').filter({ hasText: 'Спорт' }).first();

    await sportRow.getByRole('button', { name: 'Удалить' }).click();
    await expect(page.getByText('Удалить категорию «Спорт»?')).toBeVisible();
    await page.locator('[role="dialog"]').getByRole('button', { name: 'Удалить' }).click();

    await expect(table.locator('tbody tr')).toHaveCount(2);
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('shows empty state when categories list is empty', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
    });

    await page.route('**/api/admin/categories**', async (route: Route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            data: [],
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
          },
        }),
      });
    });

    await page.goto('/admin/categories');

    await expect(page.getByText('Категории пока не добавлены.')).toBeVisible();
    await expect(page.getByText('Показано 0 из 0.')).toBeVisible();
  });

  test('shows load error state when categories request fails', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
    });

    await page.route('**/api/admin/categories**', async (route: Route) => {
      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'error',
          message: 'Index failed',
        }),
      });
    });

    await page.goto('/admin/categories');

    await expect(page.getByText('Index failed')).toBeVisible();
  });

  test('shows skeleton while categories list is loading', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
    });

    await page.route('**/api/admin/categories**', async (route: Route) => {
      await new Promise((resolve) => setTimeout(resolve, 1200));
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            data: categoriesFixture,
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: categoriesFixture.length,
          },
        }),
      });
    });

    await page.goto('/admin/categories');

    await expect(page.locator('.crud-skeleton')).toBeVisible();
    await expect(
      page.locator('table.admin-table tbody tr').filter({ hasText: 'Спорт' }).first()
    ).toBeVisible();
  });
});
