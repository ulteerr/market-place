import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAppAuth } from '../helpers/app-auth';
import { E2E_RESPONSIVE_VIEWPORTS } from '../helpers/viewports';

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasOverflow).toBeFalsy();
};

const mockCategories = async (page: Page) => {
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

const mockFeaturedAndFeed = async (page: Page) => {
  await page.route('**/api/activities/featured**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: [
          {
            id: 'act-1',
            public_key: 'zenit-uuid-1',
            name: 'ФАЗ Зенит',
            slug: 'faz-zenit',
            short_description: 'Тренировки для детей.',
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
              public_key: 'zenit-uuid-1',
              name: 'ФАЗ Зенит',
              slug: 'faz-zenit',
              short_description: 'Тренировки для детей.',
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
          ],
          next_cursor: null,
        },
      }),
    });
  });
};

const mockActivityShow = async (page: Page) => {
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
          short_description: 'Тренировки для детей.',
          description: 'Полное описание.',
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
        },
      ]),
    });
  });
};

test.describe('Public activities responsive pages', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`homepage @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await mockCategories(page);
      await mockFeaturedAndFeed(page);

      await page.goto('/');

      await expect(page.locator('[data-test="home-featured-activities-grid"]')).toBeVisible();
      await expect(page.locator('[data-test="home-new-activities-grid"]')).toBeVisible();
      await assertNoHorizontalOverflow(page);
    });

    test(`catalog @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await mockCategories(page);
      await mockFeaturedAndFeed(page);

      await page.goto('/catalog');

      await expect(page.locator('[data-test="catalog-category-browser"]')).toBeVisible();
      await expect(page.locator('[data-test="catalog-activities-grid"]')).toBeVisible();
      await assertNoHorizontalOverflow(page);
    });

    test(`activity page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await mockCategories(page);
      await mockActivityShow(page);

      await page.goto('/activities/zenit-uuid-1');

      await expect(page.locator('[data-test="public-activity-top"]')).toBeVisible();
      await expect(page.locator('[data-test="public-activity-swiper"]')).toBeVisible();
      await expect(page.locator('[data-test="public-activity-summary"]')).toBeVisible();
      await expect(page.locator('[data-test="public-activity-actions"]')).toBeVisible();
      await expect(page.locator('[data-test="public-activity-lead-card"]')).toBeVisible();
      await assertNoHorizontalOverflow(page);
    });

    test(`activity lead form @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAppAuth(page, {
        phone: '+79990001122',
      });
      await mockCategories(page);
      await mockActivityShow(page);
      await mockChildrenList(page);

      await page.goto('/sport/futbol/zenit-uuid-1');

      await expect(page.locator('[data-test="public-activity-lead-card"]')).toBeVisible();
      await expect(page.locator('[data-test="public-activity-lead-request-type"]')).toBeVisible();
      await expect(page.locator('[data-test="public-activity-lead-submit"]')).toBeVisible();
      await assertNoHorizontalOverflow(page);
    });
  }
});
