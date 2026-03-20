import { expect, test, type Page } from '@playwright/test';

const mockPublicCategoryTree = async (page: Page) => {
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
        ],
      }),
    });
  });
};

test.describe('Public routes smoke', () => {
  test('renders category loading state in public header and catalog', async ({ page }) => {
    await page.route('**/api/categories/tree**', async (route) => {
      await new Promise((resolve) => setTimeout(resolve, 1000));
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: [],
        }),
      });
    });

    await page.goto('/');

    await expect(page.locator('[data-test="public-header-sections-loading"]')).toBeVisible();
    await page.locator('[data-test="public-header-catalog-toggle"]').click();
    await expect(page.locator('[data-test="public-header-catalog-loading"]')).toBeVisible();

    await page.goto('/catalog');
    await expect(page.locator('[data-test="catalog-category-browser-loading"]')).toBeVisible();
  });

  test('renders category error state in public header and catalog', async ({ page }) => {
    await page.route('**/api/categories/tree**', async (route) => {
      await route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'error',
          message: 'Server error',
        }),
      });
    });

    await page.goto('/');

    await expect(page.locator('[data-test="public-header-sections-error"]')).toBeVisible();
    await page.locator('[data-test="public-header-catalog-toggle"]').click();
    await expect(page.locator('[data-test="public-header-catalog-error"]')).toBeVisible();

    await page.goto('/catalog');
    await expect(page.locator('[data-test="catalog-category-browser-error"]')).toBeVisible();
  });

  test('renders public home and primary public routes', async ({ page }) => {
    await mockPublicCategoryTree(page);

    await page.route('**/api/activities/featured**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: [
            {
              id: 'act-1',
              public_key: 'futbol-uuid-1',
              name: 'Футбольная секция',
              slug: 'futbol',
              short_description: 'Тренировки для детей 7-12 лет.',
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
              location: {
                id: 'loc-1',
                address: 'Ленинский, 10',
                city: {
                  id: 'city-1',
                  name: 'Москва',
                },
              },
              organization: {
                id: 'org-1',
                name: 'Спортивный клуб',
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
          ],
        }),
      });
    });

    await page.route('**/api/activities/feed**', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            items: [
              {
                id: 'act-1',
                public_key: 'futbol-uuid-1',
                name: 'Футбольная секция',
                slug: 'futbol',
                short_description: 'Тренировки для детей 7-12 лет.',
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
            ],
            next_cursor: null,
          },
        }),
      });
    });

    await page.goto('/');

    await expect(page.locator('[data-test="public-header"]')).toBeVisible();
    await expect(
      page.locator('[data-test="public-header-bottom-row"]').getByRole('link', { name: 'Спорт' })
    ).toBeVisible();
    await page.locator('[data-test="public-header-catalog-toggle"]').click();
    await expect(page.locator('[data-test="public-header-catalog-menu"]')).toBeVisible();
    await expect(
      page.locator('[data-test="public-header-catalog-menu"]').getByRole('link', { name: 'Футбол' })
    ).toBeVisible();
    await expect(page.locator('[data-test="home-featured-activities-grid"]')).toBeVisible();
    await expect(page.locator('[data-test="home-featured-activity-act-1"]')).toBeVisible();
    await expect(page.locator('[data-test="home-featured-activity-act-1"] img')).toBeVisible();
    await expect(page.locator('[data-test="home-new-activities-grid"]')).toBeVisible();
    await expect(page.locator('[data-test="home-category-activities-grid"]')).toBeVisible();
    await expect(page.locator('[data-test="home-city-activities-grid"]')).toBeVisible();
    await expect(page.locator('[data-test="home-organization-activities-grid"]')).toBeVisible();

    await page.goto('/catalog?root_category_id=cat-root-sport&category_id=cat-leaf-football');
    await expect(page.locator('[data-test="catalog-activities-grid"]')).toBeVisible();
    await expect(page.locator('[data-test="catalog-activity-act-1"]')).toBeVisible();
    await expect(page.locator('[data-test="catalog-activity-act-1"] img')).toBeVisible();

    await page.goto('/content');
    await expect(page.locator('[data-test="content-pages-grid"]')).toBeVisible();
  });

  test('loads next catalog chunk on scroll', async ({ page }) => {
    await mockPublicCategoryTree(page);

    let feedRequests = 0;

    await page.route('**/api/activities/feed**', async (route) => {
      feedRequests += 1;

      const url = new URL(route.request().url());
      const cursor = url.searchParams.get('cursor');

      if (cursor === 'cursor-2') {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: {
              items: [
                {
                  id: 'act-2',
                  public_key: 'volejbol-uuid-2',
                  name: 'Волейбол',
                  slug: 'volejbol',
                  short_description: 'Вторая страница выдачи.',
                  min_age: 10,
                  max_age: 14,
                  price_from: 3000,
                  price_to: 4500,
                  currency: 'RUB',
                  is_featured: false,
                  published_at: '2026-03-16T11:00:00Z',
                  primary_category: {
                    id: 'cat-leaf-volleyball',
                    name: 'Волейбол',
                    slug: 'volejbol',
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
                    id: 'file-cover-2',
                    url: '/storage/activities/cover-2.jpg',
                    original_name: 'cover-2.jpg',
                    mime_type: 'image/jpeg',
                    size: 1300,
                    collection: 'cover',
                  },
                },
              ],
              next_cursor: null,
            },
          }),
        });

        return;
      }

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            items: [
              {
                id: 'act-1',
                public_key: 'futbol-uuid-1',
                name: 'Футбольная секция',
                slug: 'futbol',
                short_description: 'Первая страница выдачи.',
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
            ],
            next_cursor: 'cursor-2',
          },
        }),
      });
    });

    await page.goto('/catalog');

    await expect(page.locator('[data-test="catalog-activity-act-1"]')).toBeVisible();
    await expect(page.locator('[data-test="catalog-activity-act-2"]')).toBeVisible();
    expect(feedRequests).toBeGreaterThanOrEqual(2);
  });

  test('applies public catalog filters and sorting', async ({ page }) => {
    await mockPublicCategoryTree(page);

    const feedRequests: string[] = [];

    await page.route('**/api/activities/feed**', async (route) => {
      const url = route.request().url();
      feedRequests.push(url);
      const searchParams = new URL(url).searchParams;

      const isFilteredRequest =
        searchParams.get('search') === 'зенит' &&
        searchParams.get('category_id') === 'cat-leaf-football' &&
        searchParams.get('is_featured') === 'true' &&
        searchParams.get('sort_dir') === 'asc';

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            items: isFilteredRequest
              ? [
                  {
                    id: 'act-filtered',
                    public_key: 'zenit-uuid-3',
                    name: 'ФАЗ Зенит',
                    slug: 'faz-zenit',
                    short_description: 'Отфильтрованная выдача.',
                    min_age: 7,
                    max_age: 14,
                    price_from: 4500,
                    price_to: 6500,
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
                      name: 'Академия Зенит',
                    },
                    location: {
                      id: 'loc-1',
                      address: 'Крестовский, 1',
                      city: {
                        id: 'city-1',
                        name: 'Санкт-Петербург',
                      },
                    },
                    cover: {
                      id: 'file-cover-3',
                      url: '/storage/activities/cover-3.jpg',
                      original_name: 'cover-3.jpg',
                      mime_type: 'image/jpeg',
                      size: 1600,
                      collection: 'cover',
                    },
                  },
                ]
              : [
                  {
                    id: 'act-1',
                    public_key: 'futbol-uuid-1',
                    name: 'Футбольная секция',
                    slug: 'futbol',
                    short_description: 'Базовая выдача.',
                    min_age: 7,
                    max_age: 12,
                    price_from: 3500,
                    price_to: 5000,
                    currency: 'RUB',
                    is_featured: false,
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
                ],
            next_cursor: null,
          },
        }),
      });
    });

    await page.goto('/catalog');

    await page
      .locator('[data-test="catalog-filters"]')
      .getByPlaceholder('Найти секции, кружки и активности')
      .fill('зенит');
    await expect(page.locator('[data-test="catalog-category-browser"]')).toBeVisible();
    await page.locator('[data-test="catalog-root-category-cat-root-sport"]').click();
    await page.locator('[data-test="catalog-leaf-category-cat-leaf-football"]').click();
    await page.getByLabel('Витрина').click();
    await page.getByRole('option', { name: 'Только рекомендуемые' }).click();
    await page.getByLabel('Сортировка').click();
    await page.getByRole('option', { name: 'Сначала старые' }).click();

    await expect(page.locator('[data-test="catalog-activity-act-filtered"]')).toBeVisible();
    await expect(page).toHaveURL(/search=%D0%B7%D0%B5%D0%BD%D0%B8%D1%82/);
    expect(feedRequests.some((url) => url.includes('category_id=cat-leaf-football'))).toBeTruthy();
    expect(feedRequests.some((url) => url.includes('is_featured=true'))).toBeTruthy();
    expect(feedRequests.some((url) => url.includes('sort_dir=asc'))).toBeTruthy();
  });

  test('navigates to SEO-ready category pages from public header', async ({ page }) => {
    await mockPublicCategoryTree(page);

    await page.route('**/api/activities/feed**', async (route) => {
      const url = new URL(route.request().url());
      const rootCategoryId = url.searchParams.get('root_category_id');
      const categoryId = url.searchParams.get('category_id');

      const isLeafRequest =
        rootCategoryId === 'cat-root-sport' && categoryId === 'cat-leaf-football';

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            items: [
              {
                id: isLeafRequest ? 'act-leaf-1' : 'act-root-1',
                public_key: isLeafRequest ? 'faz-zenit-uuid-1' : 'sport-root-uuid-1',
                name: isLeafRequest ? 'ФАЗ Зенит' : 'Спортивная активность',
                slug: isLeafRequest ? 'faz-zenit' : 'sport-root',
                short_description: 'Категорийная выдача.',
                min_age: 7,
                max_age: 14,
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
            ],
            next_cursor: null,
          },
        }),
      });
    });

    await page.goto('/');

    await page
      .locator('[data-test="public-header-bottom-row"]')
      .getByRole('link', { name: 'Спорт' })
      .click();
    await expect(page).toHaveURL('/catalog/sport');
    await expect(page.locator('[data-test="catalog-root-category-page"]')).toBeVisible();
    await expect(page.locator('[data-test="catalog-root-category-activities-grid"]')).toBeVisible();

    await page.goto('/');
    await page.locator('[data-test="public-header-catalog-toggle"]').click();
    await page
      .locator('[data-test="public-header-catalog-menu"]')
      .getByRole('link', { name: 'Футбол' })
      .click();
    await expect(page).toHaveURL('/catalog/sport/futbol');
    await expect(page.locator('[data-test="catalog-leaf-category-page"]')).toBeVisible();
    await expect(page.locator('[data-test="catalog-leaf-category-activities-grid"]')).toBeVisible();
  });

  test('renders public activity card page', async ({ page }) => {
    await page.route('**/api/activities/futbol-uuid-1', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            id: 'act-1',
            organization_id: 'org-1',
            location_id: 'loc-1',
            name: 'Футбольная секция',
            slug: 'futbol',
            short_description: 'Тренировки для детей 7-12 лет.',
            description: 'Полное описание футбольной активности.',
            min_age: 7,
            max_age: 12,
            capacity: 20,
            price_from: 3500,
            price_to: 5000,
            currency: 'RUB',
            status: 'published',
            is_featured: true,
            published_at: '2026-03-16T10:00:00Z',
            created_at: '2026-03-16T09:00:00Z',
            updated_at: '2026-03-16T09:00:00Z',
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
            schedules: [
              {
                id: 'sch-1',
                day_of_week: 1,
                start_time: '10:00:00',
                end_time: '11:30:00',
              },
            ],
            cover: {
              id: 'file-cover-1',
              url: '/storage/activities/cover-1.jpg',
              original_name: 'cover-1.jpg',
              mime_type: 'image/jpeg',
              size: 1200,
              collection: 'cover',
            },
            gallery: [
              {
                id: 'file-gallery-1',
                url: '/storage/activities/gallery-1.jpg',
                original_name: 'gallery-1.jpg',
                mime_type: 'image/jpeg',
                size: 1100,
                collection: 'gallery',
              },
            ],
          },
        }),
      });
    });

    await page.goto('/activities/futbol-uuid-1');

    await expect(page).toHaveURL(/\/sport\/futbol\/futbol-uuid-1$/);

    await expect(page.locator('[data-test="public-activity-swiper"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-summary"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-details"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-schedules"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-actions"]')).toBeVisible();
    await expect(page.locator('h1')).toHaveText('Футбольная секция');
    await expect(
      page.locator('[data-test="public-activity-details"]').getByText('Спорт / Футбол')
    ).toBeVisible();
  });

  test('renders skeleton state for public home', async ({ page }) => {
    await page.goto('/?state=loading');

    await expect(page.locator('[data-test="home-new-activities-loading"]')).toBeVisible();
  });

  test('renders skeleton state for public catalog', async ({ page }) => {
    await page.goto('/catalog?state=loading');

    await expect(page.locator('[data-test="catalog-activities-loading"]')).toBeVisible();
  });

  test('renders skeleton state for public activity page', async ({ page }) => {
    await page.goto('/sport/futbol/faz-zenit-activity-uuid?state=loading');

    await expect(page.locator('[data-test="public-activity-loading"]')).toBeVisible();
  });
});
