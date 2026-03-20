import type { Page, Route } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';
import { E2E_RESPONSIVE_VIEWPORTS } from '../../helpers/viewports';

const activityPermissions = [
  'admin.activities.read',
  'admin.activities.create',
  'admin.activities.update',
];

const organizationsPayload = {
  status: 'ok',
  data: {
    data: [
      {
        id: 'org-1',
        name: 'Чемпионы',
        locations: [
          {
            id: 'loc-1',
            city_id: 'city-1',
            address: 'ул. Ленина, 1',
          },
        ],
      },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 100,
    total: 1,
  },
};

const categoriesTree = [
  {
    id: 'cat-root-sport',
    name: 'Спорт',
    slug: 'sport',
    parent_id: null,
    children: [
      {
        id: 'cat-leaf-football',
        name: 'Футбол',
        slug: 'futbol',
        parent_id: 'cat-root-sport',
      },
    ],
  },
];

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

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasOverflow).toBeFalsy();
};

const setupAdminActivityAuth = async (page: Page) => {
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
};

const setupActivitiesIndexApi = async (page: Page) => {
  await page.route('**/api/admin/activities**', async (route: Route) => {
    if (route.request().method() !== 'GET') {
      await route.fallback();
      return;
    }

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          data: [activityPayload],
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 1,
        },
      }),
    });
  });
};

const setupOrganizationsAndCategoriesApi = async (page: Page) => {
  await page.route('**/api/admin/organizations**', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(organizationsPayload),
    });
  });

  await page.route('**/api/admin/categories/tree**', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: categoriesTree,
      }),
    });
  });

  await page.route('**/api/admin/activities/slug-preview**', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: { slug: 'futbolnaya-sektsiya' },
      }),
    });
  });
};

const setupActivityShowApi = async (page: Page) => {
  await page.route('**/api/admin/activities/act-football', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: activityPayload,
      }),
    });
  });
};

test.describe('Admin activities responsive pages', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`index page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminActivityAuth(page);
      await setupActivitiesIndexApi(page);

      await page.goto('/admin/activities');

      await expect(page.getByRole('heading', { level: 2, name: 'Активности' })).toBeVisible();
      await expect(page.getByText('Футбольная секция', { exact: true })).toBeVisible();
      await assertNoHorizontalOverflow(page);
    });

    test(`new page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminActivityAuth(page);
      await setupOrganizationsAndCategoriesApi(page);

      await page.goto('/admin/activities/new');

      await expect(page.getByRole('heading', { level: 2, name: 'Новая активность' })).toBeVisible();
      await expect(page.getByLabel('Организация')).toBeVisible();
      await expect(page.getByLabel('Локация')).toBeVisible();
      await expect(page.getByLabel('Основная категория')).toBeVisible();
      await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();
      await assertNoHorizontalOverflow(page);
    });

    test(`show page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminActivityAuth(page);
      await setupActivityShowApi(page);

      await page.goto('/admin/activities/act-football');

      await expect(page.getByRole('heading', { level: 2, name: 'Активность' })).toBeVisible();
      await expect(page.getByText('Футбольная секция', { exact: true })).toBeVisible();
      await expect(page.getByRole('heading', { level: 3, name: 'Расписание' })).toBeVisible();
      await assertNoHorizontalOverflow(page);
    });

    test(`edit page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminActivityAuth(page);
      await setupActivityShowApi(page);
      await setupOrganizationsAndCategoriesApi(page);

      await page.goto('/admin/activities/act-football/edit');

      await expect(
        page.getByRole('heading', { level: 2, name: 'Редактирование активности' })
      ).toBeVisible();
      await expect(page.getByLabel('Название')).toHaveValue('Футбольная секция');
      await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();
      await assertNoHorizontalOverflow(page);
    });
  }
});
