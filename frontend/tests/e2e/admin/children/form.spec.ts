import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';
import { readJsonBody } from '../../helpers/http';

const childrenPermissions = ['org.children.read', 'org.children.write'];

const setupChildrenForm = async (page: Page) => {
  await setupAdminAuth(page, {
    permissions: [...(defaultAdminUser.permissions ?? []), ...childrenPermissions],
  });

  await page.route('**/api/admin/users**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          data: [
            {
              id: 'u-1',
              email: 'pavel@example.com',
              first_name: 'Павел',
              last_name: 'Кузнецов',
              middle_name: 'Иванович',
            },
          ],
          current_page: 1,
          last_page: 1,
          per_page: 20,
          total: 1,
        },
      }),
    });
  });
};

test.describe('Admin children form pages', () => {
  test('shows create form on /admin/children/new', async ({ page }) => {
    await setupChildrenForm(page);

    await page.goto('/admin/children/new');
    const form = page.locator('article form').first();
    await expect(form.getByLabel('Фамилия *')).toHaveCount(1);
    await expect(form.getByLabel('Имя *')).toHaveCount(1);
    await expect(form.getByLabel('Отчество')).toHaveCount(1);
    await expect(form.getByLabel('ID пользователя *')).toHaveCount(1);
    await expect(form.locator('button[type="submit"]')).toHaveCount(1);
  });

  test('creates child on /admin/children/new', async ({ page }) => {
    await setupChildrenForm(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route(/\/api\/admin\/children(?:\?.*)?$/, async (route) => {
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
          data: {
            id: 'c-new',
          },
        }),
      });
    });

    const usersLoadedPromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        response.url().includes('/api/admin/users') &&
        response.status() === 200
    );

    await page.goto('/admin/children/new');
    await usersLoadedPromise;
    const form = page.locator('article form').first();

    await form.getByLabel('Фамилия *').fill('  Петров ');
    await form.getByLabel('Имя *').fill(' Анна ');
    await form.getByLabel('ID пользователя *').click();
    await form.getByLabel('ID пользователя *').press('Enter');
    await form.locator('button[type="submit"]').click();

    await expect.poll(() => capturedCreatePayload !== null).toBeTruthy();
    expect(capturedCreatePayload).toEqual({
      user_id: 'u-1',
      first_name: 'Анна',
      last_name: 'Петров',
      middle_name: null,
      gender: null,
      birth_date: null,
    });
  });

  test('updates child on /admin/children/[id]/edit', async ({ page }) => {
    await setupChildrenForm(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await page.route(/\/api\/admin\/children\/c-1(?:\?.*)?$/, async (route) => {
      const method = route.request().method();

      if (method === 'GET') {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: {
              id: 'c-1',
              user_id: 'u-1',
              first_name: 'Виктория',
              last_name: 'Кузнецова',
              middle_name: 'Тимофеевна',
              gender: 'female',
              birth_date: '2018-07-14',
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
            data: {
              id: 'c-1',
            },
          }),
        });
        return;
      }

      await route.fallback();
    });

    const childLoadResponsePromise = page.waitForResponse(
      (response) =>
        response.request().method() === 'GET' &&
        response.url().includes('/api/admin/children/c-1') &&
        response.status() === 200
    );

    await page.goto('/admin/children/c-1/edit');
    await childLoadResponsePromise;
    const form = page.locator('article form').first();
    await expect(form.getByLabel('Фамилия *')).toHaveValue('Кузнецова');

    await form.getByLabel('Фамилия *').fill('  Петрова ');
    await form.getByLabel('Имя *').fill(' Мария ');
    await form.getByLabel('Отчество').fill(' ');
    await form.locator('button[type="submit"]').click();

    await expect.poll(() => capturedUpdatePayload !== null).toBeTruthy();
    expect(capturedUpdatePayload).toEqual({
      user_id: 'u-1',
      first_name: 'Мария',
      last_name: 'Петрова',
      middle_name: null,
      gender: 'female',
      birth_date: '2018-07-14',
    });
  });
});
