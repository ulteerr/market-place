import type { Page, Route } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

const activityPermissions = ['admin.activities.read', 'admin.activities.delete'];

const activitiesFixture = [
  {
    id: 'act-football',
    organization_id: 'org-1',
    location_id: 'loc-1',
    name: 'Футбольная секция',
    slug: 'futbolnaya-sektsiya',
    short_description: 'Тренировки для детей',
    status: 'published',
    is_featured: true,
    published_at: '2026-03-10T12:00:00Z',
    organization: { id: 'org-1', name: 'Чемпионы' },
    location: {
      id: 'loc-1',
      organization_id: 'org-1',
      city_id: 'city-1',
      address: 'ул. Ленина, 1',
      city: { id: 'city-1', name: 'Москва' },
    },
    primary_category: {
      id: 'cat-leaf-football',
      name: 'Футбол',
      slug: 'futbol',
      parent_id: 'cat-root-sport',
      parent: { id: 'cat-root-sport', name: 'Спорт' },
    },
  },
  {
    id: 'act-vocal',
    organization_id: 'org-2',
    location_id: 'loc-2',
    name: 'Вокальная студия',
    slug: 'vokalnaya-studiya',
    short_description: 'Занятия по вокалу',
    status: 'draft',
    is_featured: false,
    published_at: null,
    organization: { id: 'org-2', name: 'Голос' },
    location: {
      id: 'loc-2',
      organization_id: 'org-2',
      city_id: 'city-2',
      address: 'пр-т Мира, 10',
      city: { id: 'city-2', name: 'Казань' },
    },
    primary_category: {
      id: 'cat-leaf-vocal',
      name: 'Вокал',
      slug: 'vokal',
      parent_id: 'cat-root-music',
      parent: { id: 'cat-root-music', name: 'Музыка' },
    },
  },
];

const setupActivitiesIndexPage = async (page: Page) => {
  await setupAdminAuth(page, {
    permissions: [...(defaultAdminUser.permissions ?? []), ...activityPermissions],
    settings: {
      theme: 'dark',
      collapse_menu: false,
      admin_crud_preferences: {},
      admin_navigation_sections: {
        organization: { open: true },
      },
    },
  });

  let dataset = [...activitiesFixture];

  await page.route('**/api/admin/activities**', async (route: Route) => {
    if (route.request().method() === 'DELETE') {
      const activityId = route.request().url().split('/').pop();
      dataset = dataset.filter((item) => item.id !== activityId);

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
    const status = (url.searchParams.get('status') ?? '').trim();
    const isFeatured = url.searchParams.get('is_featured');

    const filtered = dataset.filter((item) => {
      if (status && item.status !== status) {
        return false;
      }

      if (isFeatured === 'true' && !item.is_featured) {
        return false;
      }

      if (isFeatured === 'false' && item.is_featured) {
        return false;
      }

      if (!search) {
        return true;
      }

      return [item.name, item.slug, item.organization.name]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(search));
    });

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          data: filtered,
          current_page: 1,
          last_page: 1,
          per_page: 20,
          total: filtered.length,
        },
      }),
    });
  });
};

test.describe('Admin activities list page', () => {
  test('shows activities table for authenticated admin', async ({ page }) => {
    await setupActivitiesIndexPage(page);
    await page.goto('/admin/activities');

    const table = page.locator('table.admin-table');
    const breadcrumbs = page.locator('nav[aria-label="Хлебные крошки"]');

    await expect(page.getByRole('heading', { level: 2, name: 'Активности' })).toBeVisible();
    await expect(
      table.locator('tbody tr').filter({ hasText: 'Футбольная секция' }).first()
    ).toBeVisible();
    await expect(
      table.locator('tbody tr').filter({ hasText: 'Вокальная студия' }).first()
    ).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
    await expect(breadcrumbs).toContainText('Главная');
    await expect(breadcrumbs).toContainText('Организация');
    await expect(breadcrumbs).toContainText('Активности');
  });

  test('deletes activity through confirm modal', async ({ page }) => {
    await setupActivitiesIndexPage(page);
    await page.goto('/admin/activities');

    const table = page.locator('table.admin-table');
    const footballRow = table.locator('tbody tr').filter({ hasText: 'Футбольная секция' }).first();

    await footballRow.getByRole('button', { name: 'Удалить' }).click();
    await expect(page.getByText('Удалить активность «Футбольная секция»?')).toBeVisible();
    await page.locator('[role="dialog"]').getByRole('button', { name: 'Удалить' }).click();

    await expect(table.locator('tbody tr')).toHaveCount(1);
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('shows empty state when activities list is empty', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...activityPermissions],
    });

    await page.route('**/api/admin/activities**', async (route: Route) => {
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

    await page.goto('/admin/activities');

    await expect(page.getByText('Активности пока не добавлены.')).toBeVisible();
  });

  test('shows load error state when activities request fails', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...activityPermissions],
    });

    await page.route('**/api/admin/activities**', async (route: Route) => {
      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'error',
          message: 'Activities index failed',
        }),
      });
    });

    await page.goto('/admin/activities');

    await expect(page.getByText('Activities index failed')).toBeVisible();
  });

  test('shows skeleton while activities list is loading', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...activityPermissions],
    });

    await page.route('**/api/admin/activities**', async (route: Route) => {
      await new Promise((resolve) => setTimeout(resolve, 1200));
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            data: activitiesFixture,
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: activitiesFixture.length,
          },
        }),
      });
    });

    await page.goto('/admin/activities');

    await expect(page.locator('.crud-skeleton')).toBeVisible();
    await expect(
      page.locator('table.admin-table tbody tr').filter({ hasText: 'Футбольная секция' }).first()
    ).toBeVisible();
  });
});
