import { expect, test, type Page } from '@playwright/test';
import { setupAppAuth } from '../helpers/app-auth';

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
            ],
          },
        ],
      }),
    });
  });
};

const mockPublicActivitiesFeed = async (page: Page) => {
  let feedRequests = 0;

  await page.route('**/api/activities/feed**', async (route) => {
    feedRequests += 1;

    const cursor = new URL(route.request().url()).searchParams.get('cursor');
    const items =
      cursor === 'cursor-2'
        ? [
            {
              id: 'act-2',
              public_key: 'zenit-uuid-2',
              name: 'ФАЗ Зенит 2',
              slug: 'faz-zenit-2',
              short_description: 'Вторая карточка из следующей пачки.',
              min_age: 8,
              max_age: 13,
              price_from: 4000,
              price_to: 5500,
              currency: 'RUB',
              is_featured: false,
              published_at: '2026-03-16T11:00:00Z',
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
                id: 'file-cover-2',
                url: '/storage/activities/cover-2.jpg',
                original_name: 'cover-2.jpg',
                mime_type: 'image/jpeg',
                size: 1600,
                collection: 'cover',
              },
            },
          ]
        : [
            {
              id: 'act-1',
              public_key: 'zenit-uuid-1',
              name: 'ФАЗ Зенит',
              slug: 'faz-zenit',
              short_description: 'Главная карточка витрины.',
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
                id: 'file-cover-1',
                url: '/storage/activities/cover-1.jpg',
                original_name: 'cover-1.jpg',
                mime_type: 'image/jpeg',
                size: 1500,
                collection: 'cover',
              },
            },
          ];

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          items,
          next_cursor: cursor ? null : 'cursor-2',
        },
      }),
    });
  });

  return {
    getFeedRequests: () => feedRequests,
  };
};

const mockPublicActivityShow = async (page: Page) => {
  await page.route('**/api/activities/zenit-uuid-1', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          id: 'act-1',
          organization_id: 'org-1',
          location_id: 'loc-1',
          name: 'ФАЗ Зенит',
          slug: 'faz-zenit',
          short_description: 'Тренировки для детей 7-14 лет.',
          description: 'Полное описание публичной активности.',
          min_age: 7,
          max_age: 14,
          capacity: 24,
          price_from: 4500,
          price_to: 6500,
          currency: 'RUB',
          status: 'published',
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
            size: 1500,
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
};

const mockChildrenList = async (page: Page) => {
  await page.route('**/children', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify([
        {
          id: 'child-1',
          user_id: '1',
          first_name: 'Иван',
          last_name: 'Иванов',
          middle_name: 'Иванович',
          birth_date: '2016-05-12',
        },
      ]),
    });
  });
};

test.describe('Public activities feed and card', () => {
  test('renders catalog feed cards and loads next chunk on scroll', async ({ page }) => {
    await mockPublicCategoryTree(page);
    const feed = await mockPublicActivitiesFeed(page);

    await page.goto('/catalog');

    await expect(page.locator('[data-test="catalog-activities-grid"]')).toBeVisible();
    await expect(page.locator('[data-test="catalog-activities-grid"] img').first()).toHaveAttribute(
      'loading',
      'lazy'
    );
    await expect(page.locator('[data-test="catalog-activity-act-1"]')).toBeVisible();
    await expect(page.locator('[data-test="catalog-activity-act-2"]')).toBeVisible();
    expect(feed.getFeedRequests()).toBeGreaterThanOrEqual(2);
  });

  test('navigates from public feed card to canonical activity page', async ({ page }) => {
    await mockPublicCategoryTree(page);
    await mockPublicActivitiesFeed(page);
    await mockPublicActivityShow(page);

    await page.goto('/catalog');
    await page.locator('[data-test="catalog-activity-act-1"]').click();

    await expect(page).toHaveURL('/sport/futbol/zenit-uuid-1');
    await expect(page.locator('[data-test="public-activity-swiper"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-summary"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-details"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-actions"]')).toBeVisible();
  });

  test('redirects fallback activity route to canonical nested route', async ({ page }) => {
    await mockPublicActivityShow(page);

    await page.goto('/activities/zenit-uuid-1');

    await expect(page).toHaveURL('/sport/futbol/zenit-uuid-1');
    await expect(page.locator('h1')).toHaveText('ФАЗ Зенит');
    await expect(page.locator('[data-test="public-activity-schedules"]')).toBeVisible();
  });

  test('shows login CTA for guest on public activity lead card', async ({ page }) => {
    await mockPublicActivityShow(page);

    await page.goto('/sport/futbol/zenit-uuid-1');

    await expect(page.locator('[data-test="public-activity-lead-card"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-lead-guest"]')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Войти и отправить заявку' })).toHaveAttribute(
      'href',
      '/login'
    );
  });

  test('submits authenticated public lead request from activity page', async ({ page }) => {
    let submitBody: Record<string, unknown> | null = null;

    await setupAppAuth(page, {
      phone: '+79990001122',
    });
    await mockPublicCategoryTree(page);
    await mockPublicActivityShow(page);
    await mockChildrenList(page);
    await page.route('**/api/activities/act-1/leads', async (route) => {
      submitBody = route.request().postDataJSON() as Record<string, unknown>;

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            id: 'lead-1',
            activity_id: 'act-1',
            user_id: '1',
            request_for_type: 'child',
            status: 'new',
            contact_channels: ['phone', 'telegram'],
            contact_payload: {
              phone: '+79990001122',
              telegram: '@ivan_parent',
            },
          },
        }),
      });
    });

    await page.goto('/sport/futbol/zenit-uuid-1');

    await expect(page.locator('[data-test="public-activity-lead-card"]')).toBeVisible();
    await expect(page.locator('[data-test="public-activity-lead-guest"]')).toHaveCount(0);

    const requestType = page.locator('[data-test="public-activity-lead-request-type"]');
    await requestType.getByRole('combobox').click();
    await page.getByRole('option', { name: 'На ребенка' }).click();

    const childSelect = page.locator('[data-test="public-activity-lead-child"]');
    await childSelect.getByRole('combobox').click();
    await page.getByRole('option', { name: 'Иванов Иван Иванович' }).click();

    await page.getByRole('checkbox', { name: 'Telegram' }).check({ force: true });
    await page.getByRole('textbox', { name: 'Telegram' }).fill('@ivan_parent');
    await page
      .getByRole('textbox', { name: 'Сообщение' })
      .fill('Нужна обратная связь по расписанию.');
    await page.locator('[data-test="public-activity-lead-submit"]').click();

    await expect(page.locator('[data-test="public-activity-lead-success"]')).toBeVisible();
    expect(submitBody).toEqual({
      request_for_type: 'child',
      child_id: 'child-1',
      contact_channels: ['phone', 'telegram'],
      contact_payload: {
        phone: '+79990001122',
        telegram: '@ivan_parent',
      },
      message: 'Нужна обратная связь по расписанию.',
    });
  });
});
