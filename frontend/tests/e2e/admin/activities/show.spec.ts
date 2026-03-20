import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

const activityPermissions = ['admin.activities.read', 'admin.activities.update'];

const activityPayload = {
  id: 'act-football',
  organization_id: 'org-1',
  location_id: 'loc-1',
  name: 'Футбольная секция',
  slug: 'futbolnaya-sektsiya',
  short_description: 'Тренировки для детей 7-12 лет.',
  description: 'Подробное описание активности.',
  min_age: 7,
  max_age: 12,
  capacity: 18,
  price_from: 1500,
  price_to: 2500,
  currency: 'RUB',
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
    parent: {
      id: 'cat-root-sport',
      name: 'Спорт',
      slug: 'sport',
    },
  },
  schedules: [
    {
      id: 'sch-1',
      day_of_week: 2,
      start_time: '16:00',
      end_time: '17:30',
    },
  ],
  cover: {
    id: 'file-cover',
    url: 'https://example.com/cover.jpg',
    original_name: 'cover.jpg',
    mime_type: 'image/jpeg',
    size: 12345,
    collection: 'cover',
  },
  gallery: [
    {
      id: 'file-gallery-1',
      url: 'https://example.com/gallery-1.jpg',
      original_name: 'gallery-1.jpg',
      mime_type: 'image/jpeg',
      size: 23456,
      collection: 'gallery',
    },
  ],
};

test.describe('Admin activities show page', () => {
  test('shows activity data for authenticated admin', async ({ page }) => {
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

    await page.route('**/api/admin/activities/act-football', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: activityPayload,
        }),
      });
    });

    await page.goto('/admin/activities/act-football');

    const breadcrumbs = page.locator('nav[aria-label="Хлебные крошки"]');
    const values = page.locator('dl').locator('dd');

    await expect(page.getByRole('heading', { level: 2, name: 'Активность' })).toBeVisible();
    await expect(
      page.locator('.admin-sidebar').getByText('Активности', { exact: true }).first()
    ).toHaveCount(1);
    await expect(breadcrumbs).toContainText('Главная');
    await expect(breadcrumbs).toContainText('Организация');
    await expect(breadcrumbs).toContainText('Активности');
    await expect(breadcrumbs).toContainText('act-football');
    await expect(values.nth(0)).toHaveText('Футбольная секция');
    await expect(values.nth(1)).toHaveText('futbolnaya-sektsiya');
    await expect(page.getByText('Спорт / Футбол')).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'Расписание' })).toBeVisible();
    await expect(page.getByText('Вторник', { exact: true })).toBeVisible();
    await expect(page.getByText('16:00 - 17:30')).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'Обложка' })).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'Галерея' })).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'История изменений' })).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'Журнал изменений' })).toBeVisible();
    await expect(page.locator('a[href="/admin/activities/act-football/edit"]')).toBeVisible();
  });

  test('hides change log panel without changelog permission and keeps action log panel', async ({
    page,
  }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...activityPermissions].filter(
        (permission) => permission !== 'admin.changelog.read'
      ),
    });

    await page.route('**/api/admin/activities/act-football', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: activityPayload,
        }),
      });
    });

    await page.goto('/admin/activities/act-football');

    await expect(page.getByRole('heading', { level: 3, name: 'История изменений' })).toHaveCount(0);
    await expect(page.getByRole('heading', { level: 3, name: 'Журнал изменений' })).toBeVisible();
  });

  test('shows load error on activity show page', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...activityPermissions],
    });

    await page.route('**/api/admin/activities/act-football', async (route) => {
      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'error',
          message: 'Show failed',
        }),
      });
    });

    await page.goto('/admin/activities/act-football');

    await expect(page.getByText('Show failed')).toBeVisible();
  });

  test('shows skeleton while activity is loading', async ({ page }) => {
    await setupAdminAuth(page, {
      permissions: [...(defaultAdminUser.permissions ?? []), ...activityPermissions],
    });

    await page.route('**/api/admin/activities/act-football', async (route) => {
      await new Promise((resolve) => setTimeout(resolve, 1200));
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: activityPayload,
        }),
      });
    });

    await page.goto('/admin/activities/act-football');

    await expect(page.locator('.admin-page-skeleton')).toBeVisible();
    await expect(page.getByText('Футбольная секция', { exact: true })).toBeVisible();
  });
});
