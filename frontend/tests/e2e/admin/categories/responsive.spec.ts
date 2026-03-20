import type { Page, Route } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';
import { E2E_RESPONSIVE_VIEWPORTS } from '../../helpers/viewports';

const categoryPermissions = [
  'admin.categories.read',
  'admin.categories.create',
  'admin.categories.update',
  'admin.categories.delete',
];

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasOverflow).toBeFalsy();
};

const setupCategoriesIndexApi = async (page: Page) => {
  await page.route('**/api/admin/categories**', async (route: Route) => {
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
          data: [
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
          ],
          current_page: 1,
          last_page: 1,
          per_page: 20,
          total: 2,
        },
      }),
    });
  });
};

const setupCategoriesFormApi = async (page: Page) => {
  await page.route('**/api/admin/categories/tree**', async (route: Route) => {
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
            children: [],
          },
          {
            id: 'cat-root-music',
            name: 'Музыка',
            slug: 'muzyka',
            parent_id: null,
            children: [],
          },
        ],
      }),
    });
  });

  await page.route('**/api/admin/categories/slug-preview**', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          slug: 'futbol',
        },
      }),
    });
  });

  await page.route('**/api/admin/categories/cat-leaf-football', async (route: Route) => {
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
          children: [],
        },
      }),
    });
  });
};

const setTableCardsMode = async (page: Page) => {
  const modeSelectInput = page.locator('.mode-select-wrap input').first();
  await modeSelectInput.click();
  await page.getByRole('option', { name: 'Таблица + карточки' }).click();
};

test.describe('Admin categories responsive pages', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`index page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page, {
        permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
      });
      await setupCategoriesIndexApi(page);

      await page.goto('/admin/categories');
      await expect(page.getByRole('heading', { level: 2, name: 'Категории' })).toBeVisible();
      await setTableCardsMode(page);

      if (viewport.width < 768) {
        await expect(page.locator('.admin-table:visible')).toHaveCount(0);
        await expect(
          page.locator('article.admin-card').filter({ hasText: 'Спорт' }).first()
        ).toBeVisible();
        await expect(
          page.locator('article.admin-card').filter({ hasText: 'Футбол' }).first()
        ).toBeVisible();
      } else {
        await expect(page.locator('.admin-table')).toBeVisible();
      }

      await assertNoHorizontalOverflow(page);
    });

    test(`new page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page, {
        permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
      });
      await setupCategoriesFormApi(page);

      await page.goto('/admin/categories/new');
      await expect(page.getByRole('heading', { level: 2, name: 'Новая категория' })).toBeVisible();
      await expect(page.getByLabel('Название *')).toBeVisible();
      await expect(page.getByLabel('Slug *')).toBeVisible();
      await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`edit page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page, {
        permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
      });
      await setupCategoriesFormApi(page);

      await page.goto('/admin/categories/cat-leaf-football/edit');
      await expect(
        page.getByRole('heading', { level: 2, name: 'Редактирование категории' })
      ).toBeVisible();
      await expect(page.getByLabel('Название *')).toHaveValue('Футбол');
      await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });
  }
});
