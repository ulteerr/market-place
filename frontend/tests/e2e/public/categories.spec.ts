import { expect, test, type Page } from '@playwright/test';

const mockCategoryTree = async (page: Page) => {
  await page.route('**/api/categories/tree**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: [
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
                children: [],
              },
              {
                id: 'cat-leaf-volleyball',
                name: 'Волейбол',
                slug: 'volejbol',
                parent_id: 'cat-root-sport',
                children: [],
              },
            ],
          },
          {
            id: 'cat-root-music',
            name: 'Музыка',
            slug: 'muzyka',
            parent_id: null,
            children: [
              {
                id: 'cat-leaf-vocal',
                name: 'Вокал',
                slug: 'vokal',
                parent_id: 'cat-root-music',
                children: [],
              },
            ],
          },
        ],
      }),
    });
  });
};

const mockActivitiesFeed = async (page: Page) => {
  await page.route('**/api/activities/feed**', async (route) => {
    const url = new URL(route.request().url());
    const rootSlug = url.pathname;
    const rootCategoryId = url.searchParams.get('root_category_id');
    const categoryId = url.searchParams.get('category_id');

    let items = [
      {
        id: 'act-football',
        public_key: 'futbol-uuid-1',
        name: 'Футбольная секция',
        slug: 'futbol',
        short_description: 'Тренировки по футболу.',
        min_age: 7,
        max_age: 12,
        price_from: 3500,
        price_to: 5000,
        currency: 'RUB',
        is_featured: true,
        published_at: '2026-03-16T10:00:00Z',
        primary_category: {
          id: 'cat-leaf-football',
          name: 'Футбол',
          slug: 'futbol',
          parent: {
            id: 'cat-root-sport',
            name: 'Спорт',
            slug: 'sport',
          },
        },
        organization: {
          id: 'org-1',
          name: 'Спортивный клуб',
        },
        location: {
          id: 'loc-1',
          address: 'Ленинский, 10',
          city: {
            id: 'city-1',
            name: 'Москва',
          },
        },
        cover: {
          id: 'file-cover-1',
          url: '/storage/activities/cover-1.jpg',
          original_name: 'cover-1.jpg',
          mime_type: 'image/jpeg',
          size: 1200,
          collection: 'cover',
        },
      },
      {
        id: 'act-vocal',
        public_key: 'vokal-uuid-2',
        name: 'Вокал',
        slug: 'vokal',
        short_description: 'Занятия вокалом.',
        min_age: 8,
        max_age: 15,
        price_from: 3000,
        price_to: 4500,
        currency: 'RUB',
        is_featured: false,
        published_at: '2026-03-16T11:00:00Z',
        primary_category: {
          id: 'cat-leaf-vocal',
          name: 'Вокал',
          slug: 'vokal',
          parent: {
            id: 'cat-root-music',
            name: 'Музыка',
            slug: 'muzyka',
          },
        },
        organization: {
          id: 'org-2',
          name: 'Музыкальная школа',
        },
        location: {
          id: 'loc-2',
          address: 'Тверская, 5',
          city: {
            id: 'city-1',
            name: 'Москва',
          },
        },
        cover: {
          id: 'file-cover-2',
          url: '/storage/activities/cover-2.jpg',
          original_name: 'cover-2.jpg',
          mime_type: 'image/jpeg',
          size: 1300,
          collection: 'cover',
        },
      },
    ];

    if (rootCategoryId === 'cat-root-sport') {
      items = items.filter((item) => item.primary_category.parent?.id === 'cat-root-sport');
    }

    if (categoryId === 'cat-leaf-football') {
      items = items.filter((item) => item.primary_category.id === 'cat-leaf-football');
    }

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          items,
          next_cursor: null,
        },
      }),
    });
  });
};

test.describe('Public categories rendering', () => {
  test('renders category navigation in header and catalog browser', async ({ page }) => {
    await mockCategoryTree(page);
    await mockActivitiesFeed(page);

    await page.goto('/');

    await expect(
      page.locator('[data-test="public-header-bottom-row"]').getByRole('link', { name: 'Спорт' })
    ).toBeVisible();
    await expect(
      page.locator('[data-test="public-header-bottom-row"]').getByRole('link', { name: 'Музыка' })
    ).toBeVisible();

    await page.locator('[data-test="public-header-catalog-toggle"]').click();
    await expect(page.locator('[data-test="public-header-catalog-menu"]')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Футбол' })).toBeVisible();
    await page.getByRole('menuitemradio', { name: 'Музыка' }).click();
    await expect(page.getByRole('link', { name: 'Вокал' })).toBeVisible();

    await page.goto('/catalog');
    await expect(page.locator('[data-test="catalog-root-category-cat-root-sport"]')).toBeVisible();
    await expect(page.locator('[data-test="catalog-root-category-cat-root-music"]')).toBeVisible();
  });

  test('renders root and leaf category landing pages with filtered activities', async ({
    page,
  }) => {
    await mockCategoryTree(page);
    await mockActivitiesFeed(page);

    await page.goto('/');
    await page
      .locator('[data-test="public-header-bottom-row"]')
      .getByRole('link', { name: 'Спорт' })
      .click();
    await expect(page.locator('[data-test="catalog-root-category-page"]')).toBeVisible();
    await expect(page.getByText('Футбольная секция')).toBeVisible();
    await expect(page.getByText('Вокал', { exact: true })).toHaveCount(0);

    await page.getByRole('link', { name: 'Футбол' }).first().click();
    await expect(page.locator('[data-test="catalog-leaf-category-page"]')).toBeVisible();
    await expect(page.getByText('Футбольная секция')).toBeVisible();
    await expect(page.getByText('Вокал', { exact: true })).toHaveCount(0);
  });
});
