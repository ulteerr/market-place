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
  {
    id: 'cat-root-art',
    name: 'Творчество',
    slug: 'tvorchestvo',
    parent_id: null,
    children: [
      {
        id: 'cat-leaf-drawing',
        name: 'Рисование',
        slug: 'risovanie',
        parent_id: 'cat-root-art',
      },
    ],
  },
];

const setupActivitiesCreatePage = async (page: Page) => {
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
    const url = new URL(route.request().url());
    const name = (url.searchParams.get('name') ?? '').trim();

    let slug = 'activity';
    if (name === 'Футбольная секция') {
      slug = 'futbolnaya-sektsiya';
    }
    if (name === 'Новая футбольная секция') {
      slug = 'novaya-futbolnaya-sektsiya';
    }

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: { slug },
      }),
    });
  });
};

const expectMultipartField = (body: string, field: string, value: string) => {
  expect(body).toContain(`name="${field}"`);
  expect(body).toContain(value);
};

test.describe('Admin activities create page', () => {
  test('shows create form on /admin/activities/new', async ({ page }) => {
    await setupActivitiesCreatePage(page);
    await page.goto('/admin/activities/new');

    const breadcrumbs = page.locator('nav[aria-label="Хлебные крошки"]');

    await expect(page.getByRole('heading', { level: 2, name: 'Новая активность' })).toBeVisible();
    await expect(page.getByLabel('Организация')).toHaveCount(1);
    await expect(page.getByLabel('Локация')).toHaveCount(1);
    await expect(page.getByLabel('Основная категория')).toHaveCount(1);
    await expect(page.getByLabel('Подкатегория')).toHaveCount(1);
    await expect(page.getByLabel('Название')).toHaveCount(1);
    await expect(page.getByLabel('Slug')).toHaveCount(1);
    await expect(page.getByRole('heading', { level: 3, name: 'Расписание' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Добавить слот' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Авто' })).toBeVisible();
    await expect(breadcrumbs).toContainText('Главная');
    await expect(breadcrumbs).toContainText('Организация');
    await expect(breadcrumbs).toContainText('Активности');
    await expect(breadcrumbs).toContainText('Создать');
  });

  test('creates activity on /admin/activities/new', async ({ page }) => {
    await setupActivitiesCreatePage(page);

    let capturedBody = '';

    await page.route('**/api/admin/activities', async (route: Route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback();
        return;
      }

      capturedBody = readRawBody(route);
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: { id: 'act-1' },
        }),
      });
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
    await expect(page.getByLabel('Slug')).toHaveValue('futbolnaya-sektsiya');
    await page.getByRole('textbox', { name: 'Короткое описание *' }).fill('Тренировки для детей');
    await page
      .getByRole('textbox', { name: 'Описание', exact: true })
      .fill('Подробное описание активности');
    await page.getByLabel('Мин. возраст').fill('7');
    await page.getByLabel('Макс. возраст').fill('12');
    await page.getByLabel('Цена от').fill('1500');
    await page.getByLabel('Цена до').fill('2500');
    await page.getByRole('button', { name: 'Добавить слот' }).click();
    await page.getByLabel('День недели').click();
    await page.getByRole('option', { name: 'Понедельник' }).click();
    await page.getByLabel('Начало').fill('17:00');
    await page.getByLabel('Окончание').fill('18:30');
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
        {
          name: 'gallery-2.webp',
          mimeType: 'image/webp',
          buffer: Buffer.from('gallery-file-2'),
        },
      ]);
    await page.getByLabel('Статус').click();
    await page.getByRole('option', { name: 'Опубликована' }).click();
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect.poll(() => capturedBody.length > 0).toBeTruthy();
    expectMultipartField(capturedBody, 'organization_id', 'org-1');
    expectMultipartField(capturedBody, 'location_id', 'loc-1');
    expectMultipartField(capturedBody, 'category_id', 'cat-leaf-football');
    expectMultipartField(capturedBody, 'name', 'Футбольная секция');
    expectMultipartField(capturedBody, 'slug', 'futbolnaya-sektsiya');
    expectMultipartField(capturedBody, 'short_description', 'Тренировки для детей');
    expectMultipartField(capturedBody, 'description', 'Подробное описание активности');
    expectMultipartField(capturedBody, 'min_age', '7');
    expectMultipartField(capturedBody, 'max_age', '12');
    expectMultipartField(capturedBody, 'price_from', '1500');
    expectMultipartField(capturedBody, 'price_to', '2500');
    expectMultipartField(capturedBody, 'status', 'published');
    expectMultipartField(capturedBody, 'currency', 'RUB');
    expectMultipartField(capturedBody, 'is_featured', '0');
    expectMultipartField(capturedBody, 'schedules[0][day_of_week]', '1');
    expectMultipartField(capturedBody, 'schedules[0][start_time]', '17:00:00');
    expectMultipartField(capturedBody, 'schedules[0][end_time]', '18:30:00');
    expect(capturedBody).toContain('filename="cover.jpg"');
    expect(capturedBody).toContain('filename="gallery-1.jpg"');
    expect(capturedBody).toContain('filename="gallery-2.webp"');
  });

  test('requires full category pair on /admin/activities/new before submit', async ({ page }) => {
    await setupActivitiesCreatePage(page);

    let submitRequests = 0;

    await page.route('**/api/admin/activities', async (route: Route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback();
        return;
      }

      submitRequests += 1;
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: { id: 'act-1' },
        }),
      });
    });

    await page.goto('/admin/activities/new');

    await page.getByLabel('Организация').click();
    await page.getByRole('option', { name: 'Чемпионы' }).click();
    await page.getByLabel('Локация').click();
    await page.getByRole('option', { name: 'ул. Ленина, 1' }).click();
    await page.getByLabel('Название').fill('Футбольная секция');
    await page.getByRole('textbox', { name: 'Короткое описание *' }).fill('Тренировки для детей');
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect(page.getByText('Выберите основную категорию.')).toBeVisible();
    await expect(page.getByText('Выберите подкатегорию.')).toBeVisible();
    expect(submitRequests).toBe(0);
  });

  test('updates activity on /admin/activities/[id]/edit without overwriting manual slug', async ({
    page,
  }) => {
    await setupActivitiesCreatePage(page);

    let capturedBody = '';

    await page.route(/\/api\/admin\/activities\/act-football(?:\?.*)?$/, async (route) => {
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
              schedules: [
                {
                  id: 'schedule-1',
                  day_of_week: 1,
                  start_time: '17:00:00',
                  end_time: '18:30:00',
                },
              ],
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
            },
          }),
        });
        return;
      }

      if (method === 'PATCH') {
        capturedBody = readRawBody(route);
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: { id: 'act-football' },
          }),
        });
        return;
      }

      await route.fallback();
    });

    await page.goto('/admin/activities/act-football/edit');

    const form = page.locator('article form').first();
    const breadcrumbs = page.locator('nav[aria-label="Хлебные крошки"]');

    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование активности' })
    ).toBeVisible();
    await expect(form.getByLabel('Основная категория')).toHaveValue('Спорт');
    await expect(form.getByLabel('Подкатегория')).toHaveValue('Футбол');
    await expect(form.getByLabel('Название')).toHaveValue('Футбольная секция');
    await expect(form.getByLabel('Slug')).toHaveValue('futbolnaya-sektsiya');
    await expect(form.getByLabel('День недели')).toHaveValue('Понедельник');
    await expect(form.getByLabel('Начало')).toHaveValue('17:00');
    await expect(form.getByLabel('Окончание')).toHaveValue('18:30');
    await expect(form.getByText('Текущая обложка')).toBeVisible();
    await expect(form.getByText('Текущее фото')).toBeVisible();
    await expect(form.getByRole('button', { name: 'Удалить' }).first()).toBeVisible();
    await expect(breadcrumbs).toContainText('Редактировать');
    await expect(page.getByRole('heading', { level: 3, name: 'История изменений' })).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'Журнал изменений' })).toBeVisible();

    await form.getByLabel('Название').fill('Новая футбольная секция');
    await expect(form.getByLabel('Slug')).toHaveValue('novaya-futbolnaya-sektsiya');

    await form.getByLabel('Slug').fill('custom-activity-slug');
    await form.getByLabel('Название').fill('Футбольная секция');
    await expect(form.getByLabel('Slug')).toHaveValue('custom-activity-slug');
    await form.getByLabel('Начало').fill('18:00');

    await form.getByRole('button', { name: 'Сохранить' }).click();

    await expect.poll(() => capturedBody.length > 0).toBeTruthy();
    expectMultipartField(capturedBody, 'organization_id', 'org-1');
    expectMultipartField(capturedBody, 'location_id', 'loc-1');
    expectMultipartField(capturedBody, 'category_id', 'cat-leaf-football');
    expectMultipartField(capturedBody, 'name', 'Футбольная секция');
    expectMultipartField(capturedBody, 'slug', 'custom-activity-slug');
    expectMultipartField(capturedBody, 'short_description', 'Тренировки для детей');
    expectMultipartField(capturedBody, 'description', 'Подробное описание активности');
    expectMultipartField(capturedBody, 'min_age', '7');
    expectMultipartField(capturedBody, 'max_age', '12');
    expectMultipartField(capturedBody, 'capacity', '20');
    expectMultipartField(capturedBody, 'price_from', '1500');
    expectMultipartField(capturedBody, 'price_to', '2500');
    expectMultipartField(capturedBody, 'currency', 'RUB');
    expectMultipartField(capturedBody, 'status', 'draft');
    expectMultipartField(capturedBody, 'is_featured', '0');
    expectMultipartField(capturedBody, 'schedules[0][day_of_week]', '1');
    expectMultipartField(capturedBody, 'schedules[0][start_time]', '18:00:00');
    expectMultipartField(capturedBody, 'schedules[0][end_time]', '18:30:00');
  });

  test('requires subcategory after root change on /admin/activities/[id]/edit', async ({
    page,
  }) => {
    await setupActivitiesCreatePage(page);

    let submitRequests = 0;

    await page.route(/\/api\/admin\/activities\/act-football(?:\?.*)?$/, async (route) => {
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
              schedules: [
                {
                  id: 'schedule-1',
                  day_of_week: 1,
                  start_time: '17:00:00',
                  end_time: '18:30:00',
                },
              ],
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
            },
          }),
        });
        return;
      }

      if (method === 'PATCH') {
        submitRequests += 1;
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: { id: 'act-football' },
          }),
        });
        return;
      }

      await route.fallback();
    });

    await page.goto('/admin/activities/act-football/edit');

    const form = page.locator('article form').first();

    await form.getByLabel('Основная категория').click();
    await page.getByRole('option', { name: 'Творчество' }).click();
    await expect(form.getByLabel('Подкатегория')).toHaveValue('');
    await form.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page.getByText('Выберите подкатегорию.')).toBeVisible();
    expect(submitRequests).toBe(0);
  });

  test('shows load error on /admin/activities/[id]/edit', async ({ page }) => {
    await setupActivitiesCreatePage(page);

    await page.route(/\/api\/admin\/activities\/act-football(?:\?.*)?$/, async (route) => {
      if (route.request().method() !== 'GET') {
        await route.fallback();
        return;
      }

      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'error',
          message: 'Edit load failed',
        }),
      });
    });

    await page.goto('/admin/activities/act-football/edit');

    await expect(page.getByText('Edit load failed')).toBeVisible();
  });

  test('shows skeleton while /admin/activities/[id]/edit is loading', async ({ page }) => {
    await setupActivitiesCreatePage(page);

    await page.route(/\/api\/admin\/activities\/act-football(?:\?.*)?$/, async (route) => {
      if (route.request().method() !== 'GET') {
        await route.fallback();
        return;
      }

      await new Promise((resolve) => setTimeout(resolve, 1200));
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
            schedules: [
              {
                id: 'schedule-1',
                day_of_week: 1,
                start_time: '17:00:00',
                end_time: '18:30:00',
              },
            ],
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
          },
        }),
      });
    });

    await page.goto('/admin/activities/act-football/edit');

    await expect(page.locator('.admin-page-skeleton')).toBeVisible();
    await expect(page.locator('article form').first().getByLabel('Название')).toHaveValue(
      'Футбольная секция'
    );
  });
});
