import type { Page, Route } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';
import { readRawBody } from '../../helpers/http';

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

const setupActivitiesPage = async (page: Page) => {
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

test.describe('Admin activities image upload flow', () => {
  test('renders image dropzones with correct upload contract on create form', async ({ page }) => {
    await setupActivitiesPage(page);
    await page.goto('/admin/activities/new');

    const fileInputs = page.locator('input[type="file"]');

    await expect(page.getByRole('heading', { name: 'Обложка' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Галерея' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Выбрать обложку' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Выбрать фотографии' })).toBeVisible();
    await expect(fileInputs).toHaveCount(2);
    await expect(fileInputs.nth(0)).toHaveAttribute('accept', 'image/png,image/jpeg,image/webp');
    await expect(fileInputs.nth(1)).toHaveAttribute('accept', 'image/png,image/jpeg,image/webp');
    await expect(fileInputs.nth(0)).not.toHaveAttribute('multiple', '');
    await expect(fileInputs.nth(1)).toHaveAttribute('multiple', '');
  });

  test('sends cover/gallery files on create and delete flags on edit', async ({ page }) => {
    await setupActivitiesPage(page);

    let createBody = '';
    let updateBody = '';

    await page.route('**/api/admin/activities', async (route: Route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback();
        return;
      }

      createBody = readRawBody(route);
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: { id: 'act-1' } }),
      });
    });

    await page.route(/\/api\/admin\/activities\/act-football(?:\?.*)?$/, async (route: Route) => {
      const method = route.request().method();

      if (method === 'GET') {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: {
              id: 'act-football',
              organization_id: 'org-1',
              location_id: 'loc-1',
              name: 'Футбольная секция',
              slug: 'futbolnaya-sektsiya',
              short_description: 'Тренировки для детей',
              description: 'Подробное описание активности',
              min_age: 7,
              max_age: 12,
              capacity: 20,
              price_from: 1500,
              price_to: 2500,
              currency: 'RUB',
              status: 'draft',
              is_featured: false,
              published_at: null,
              organization: { id: 'org-1', name: 'Чемпионы' },
              location: {
                id: 'loc-1',
                organization_id: 'org-1',
                city_id: 'city-1',
                address: 'ул. Ленина, 1',
              },
              primary_category: {
                id: 'cat-leaf-football',
                name: 'Футбол',
                slug: 'futbol',
                parent_id: 'cat-root-sport',
                parent: { id: 'cat-root-sport', name: 'Спорт' },
              },
              cover: {
                id: 'cover-1',
                url: 'https://example.com/cover.jpg',
                original_name: 'cover.jpg',
                mime_type: 'image/jpeg',
                size: 11111,
                collection: 'cover',
              },
              gallery: [
                {
                  id: 'gallery-1',
                  url: 'https://example.com/gallery-1.jpg',
                  original_name: 'gallery-1.jpg',
                  mime_type: 'image/jpeg',
                  size: 22222,
                  collection: 'gallery',
                },
              ],
              schedules: [],
            },
          }),
        });
        return;
      }

      if (method === 'PATCH') {
        updateBody = readRawBody(route);
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ status: 'ok', data: { id: 'act-football' } }),
        });
        return;
      }

      await route.fallback();
    });

    await page.goto('/admin/activities/new');

    await page.getByLabel('Организация').click();
    await page.getByRole('option', { name: 'Чемпионы' }).click();
    await page.getByLabel('Локация').click();
    await page.getByRole('option', { name: 'ул. Ленина, 1' }).click();
    await page.getByLabel('Основная категория').click();
    await page.getByRole('option', { name: 'Спорт' }).click();
    await page.getByLabel('Подкатегория').click();
    await page.getByRole('option', { name: 'Футбол' }).click();
    await page.getByLabel('Название').fill('Футбольная секция');
    await page.getByRole('textbox', { name: 'Короткое описание *' }).fill('Тренировки для детей');
    await page
      .locator('input[type="file"]')
      .nth(0)
      .setInputFiles({
        name: 'cover.jpg',
        mimeType: 'image/jpeg',
        buffer: Buffer.from('cover-file'),
      });
    await page
      .locator('input[type="file"]')
      .nth(1)
      .setInputFiles([
        {
          name: 'gallery-1.jpg',
          mimeType: 'image/jpeg',
          buffer: Buffer.from('gallery-file-1'),
        },
      ]);
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect.poll(() => createBody.length > 0).toBeTruthy();
    expect(createBody).toContain('filename="cover.jpg"');
    expect(createBody).toContain('filename="gallery-1.jpg"');

    await page.goto('/admin/activities/act-football/edit');

    await expect(page.getByText('Текущая обложка')).toBeVisible();
    await expect(page.getByText('Текущее фото')).toBeVisible();
    await page.getByRole('button', { name: 'Удалить' }).nth(0).click();
    await page.getByRole('button', { name: 'Удалить' }).nth(0).click();
    await page
      .locator('input[type="file"]')
      .nth(0)
      .setInputFiles({
        name: 'new-cover.jpg',
        mimeType: 'image/jpeg',
        buffer: Buffer.from('new-cover-file'),
      });
    await page
      .locator('input[type="file"]')
      .nth(1)
      .setInputFiles([
        {
          name: 'new-gallery.jpg',
          mimeType: 'image/jpeg',
          buffer: Buffer.from('new-gallery-file'),
        },
      ]);
    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect.poll(() => updateBody.length > 0).toBeTruthy();
    expect(updateBody).toContain('name="cover_delete"');
    expect(updateBody).toContain('1');
    expect(updateBody).toContain('name="gallery_delete_ids[]"');
    expect(updateBody).toContain('gallery-1');
    expect(updateBody).toContain('filename="new-cover.jpg"');
    expect(updateBody).toContain('filename="new-gallery.jpg"');
  });
});
