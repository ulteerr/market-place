import type { Page, Route } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';
import { readJsonBody } from '../../helpers/http';

const categoryPermissions = [
  'admin.categories.read',
  'admin.categories.create',
  'admin.categories.update',
];

const treePayload = [
  {
    id: 'cat-root-sport',
    name: 'Спорт',
    slug: 'sport',
    parent_id: null,
    sort_order: 10,
    is_active: true,
    children: [],
  },
  {
    id: 'cat-root-music',
    name: 'Музыка',
    slug: 'muzyka',
    parent_id: null,
    sort_order: 20,
    is_active: true,
    children: [],
  },
];

const setupCategoriesForm = async (page: Page) => {
  await setupAdminAuth(page, {
    permissions: [...(defaultAdminUser.permissions ?? []), ...categoryPermissions],
  });

  await page.route('**/api/admin/categories/tree**', async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: treePayload,
      }),
    });
  });

  await page.route('**/api/admin/categories/slug-preview**', async (route: Route) => {
    const url = new URL(route.request().url());
    const name = (url.searchParams.get('name') ?? '').trim();
    const ignoreId = url.searchParams.get('ignore_id');

    let slug = 'category';
    if (name === 'Футбол') {
      slug = 'futbol';
    }
    if (name === 'Новая музыка') {
      slug = 'novaya-muzyka';
    }

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          slug,
        },
      }),
    });
  });
};

test.describe('Admin categories form pages', () => {
  test('shows create form on /admin/categories/new', async ({ page }) => {
    await setupCategoriesForm(page);
    await page.goto('/admin/categories/new');

    const form = page.locator('article form').first();
    const breadcrumbs = page.locator('nav[aria-label="Хлебные крошки"]');
    await expect(form.getByLabel('Название *')).toHaveCount(1);
    await expect(form.getByLabel('Slug *')).toHaveCount(1);
    await expect(form.getByLabel('Родительская категория')).toHaveCount(1);
    await expect(form.getByLabel('Порядок сортировки')).toHaveCount(1);
    await expect(
      page.locator('.admin-sidebar').getByText('Категории', { exact: true }).first()
    ).toHaveCount(1);
    await expect(breadcrumbs).toContainText('Главная');
    await expect(breadcrumbs).toContainText('Организация');
    await expect(breadcrumbs).toContainText('Категории');
    await expect(breadcrumbs).toContainText('Создать');
  });

  test('creates root category on /admin/categories/new without parent', async ({ page }) => {
    await setupCategoriesForm(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route(/\/api\/admin\/categories(?:\?.*)?$/, async (route: Route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback();
        return;
      }

      capturedCreatePayload = readJsonBody(route);

      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          message: 'Created successfully',
          data: {
            id: 'cat-root-new',
          },
        }),
      });
    });

    await page.goto('/admin/categories/new');
    const form = page.locator('article form').first();

    await form.getByLabel('Название *').fill('Футбол');
    await form.getByRole('button', { name: 'Авто' }).click();
    await expect(form.getByLabel('Slug *')).toHaveValue('futbol');
    await form.getByLabel('Порядок сортировки').fill('10');
    await form.locator('button[type="submit"]').click();

    await expect.poll(() => capturedCreatePayload !== null).toBeTruthy();
    expect(capturedCreatePayload).toEqual({
      name: 'Футбол',
      slug: 'futbol',
      parent_id: null,
      sort_order: 10,
      is_active: true,
    });
  });

  test('creates child category on /admin/categories/new with generated slug', async ({ page }) => {
    await setupCategoriesForm(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route(/\/api\/admin\/categories(?:\?.*)?$/, async (route: Route) => {
      if (route.request().method() !== 'POST') {
        await route.fallback();
        return;
      }

      capturedCreatePayload = readJsonBody(route);

      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          message: 'Created successfully',
          data: {
            id: 'cat-new',
          },
        }),
      });
    });

    await page.goto('/admin/categories/new');
    const form = page.locator('article form').first();

    await form.getByLabel('Название *').fill('Футбол');
    await form.getByRole('button', { name: 'Авто' }).click();
    await expect(form.getByLabel('Slug *')).toHaveValue('futbol');
    await form.getByLabel('Родительская категория').click();
    await page.getByRole('option', { name: 'Спорт' }).click();
    await form.getByLabel('Порядок сортировки').fill('15');
    await form.locator('button[type="submit"]').click();

    await expect.poll(() => capturedCreatePayload !== null).toBeTruthy();
    expect(capturedCreatePayload).toEqual({
      name: 'Футбол',
      slug: 'futbol',
      parent_id: 'cat-root-sport',
      sort_order: 15,
      is_active: true,
    });
  });

  test('updates category on /admin/categories/[id]/edit without overwriting manual slug', async ({
    page,
  }) => {
    await setupCategoriesForm(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await page.route(/\/api\/admin\/categories\/cat-leaf-football(?:\?.*)?$/, async (route) => {
      const method = route.request().method();

      if (method === 'GET') {
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
        return;
      }

      if (method === 'PATCH') {
        capturedUpdatePayload = readJsonBody(route);
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            message: 'Updated successfully',
            data: {
              id: 'cat-leaf-football',
            },
          }),
        });
        return;
      }

      await route.fallback();
    });

    await page.goto('/admin/categories/cat-leaf-football/edit');
    const form = page.locator('article form').first();
    const breadcrumbs = page.locator('nav[aria-label="Хлебные крошки"]');

    await expect(form.getByLabel('Название *')).toHaveValue('Футбол');
    await expect(form.getByLabel('Slug *')).toHaveValue('futbol');
    await expect(
      page.locator('.admin-sidebar').getByText('Категории', { exact: true }).first()
    ).toHaveCount(1);
    await expect(breadcrumbs).toContainText('Главная');
    await expect(breadcrumbs).toContainText('Организация');
    await expect(breadcrumbs).toContainText('Категории');
    await expect(breadcrumbs).toContainText('Редактировать');
    await expect(page.getByRole('heading', { level: 3, name: 'История изменений' })).toBeVisible();
    await expect(page.getByRole('heading', { level: 3, name: 'Журнал изменений' })).toBeVisible();

    await form.getByLabel('Название *').fill('Новая музыка');
    await expect(form.getByLabel('Slug *')).toHaveValue('novaya-muzyka');

    await form.getByLabel('Slug *').fill('custom-slug');
    await form.getByLabel('Название *').fill('Футбол');
    await expect(form.getByLabel('Slug *')).toHaveValue('custom-slug');

    await form.locator('button[type="submit"]').click();

    await expect.poll(() => capturedUpdatePayload !== null).toBeTruthy();
    expect(capturedUpdatePayload).toEqual({
      name: 'Футбол',
      slug: 'custom-slug',
      parent_id: 'cat-root-sport',
      sort_order: 20,
      is_active: true,
    });
  });

  test('shows load error on edit page', async ({ page }) => {
    await setupCategoriesForm(page);

    await page.route(/\/api\/admin\/categories\/cat-leaf-football(?:\?.*)?$/, async (route) => {
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

    await page.goto('/admin/categories/cat-leaf-football/edit');

    await expect(page.getByText('Edit load failed')).toBeVisible();
  });

  test('shows skeleton while edit page is loading', async ({ page }) => {
    await setupCategoriesForm(page);

    await page.route(/\/api\/admin\/categories\/cat-leaf-football(?:\?.*)?$/, async (route) => {
      const method = route.request().method();

      if (method !== 'GET') {
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
            id: 'cat-leaf-football',
            name: 'Футбол',
            slug: 'futbol',
            parent_id: 'cat-root-sport',
            sort_order: 20,
            is_active: true,
            children_count: 0,
            activities_count: 0,
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

    await page.goto('/admin/categories/cat-leaf-football/edit');

    await expect(page.locator('.admin-page-skeleton')).toBeVisible();
    await expect(page.locator('article form').first().getByLabel('Название *')).toHaveValue(
      'Футбол'
    );
  });
});
