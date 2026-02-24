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
    await expect(page.getByRole('heading', { level: 2, name: 'Новый ребенок' })).toBeVisible();
    await expect(page.getByLabel('Фамилия')).toBeVisible();
    await expect(page.getByLabel('Имя')).toBeVisible();
    await expect(page.getByLabel('Отчество')).toBeVisible();
    await expect(page.getByLabel('ID пользователя')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();
  });

  test('creates child on /admin/children/new', async ({ page }) => {
    await setupChildrenForm(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route('**/api/admin/children', async (route) => {
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

    await page.goto('/admin/children/new');

    await page.getByLabel('Фамилия').fill('  Петров ');
    await page.getByLabel('Имя').fill(' Анна ');
    await page.getByLabel('ID пользователя').click();
    await page.getByRole('button', { name: /u-1$/ }).first().click();
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect(page).toHaveURL(/\/admin\/children$/);
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

    await page.route('**/api/admin/children/c-1', async (route) => {
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

    await page.goto('/admin/children/c-1/edit');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование ребенка' })
    ).toBeVisible();

    await page.getByLabel('Фамилия').fill('  Петрова ');
    await page.getByLabel('Имя').fill(' Мария ');
    await page.getByLabel('Отчество').fill(' ');
    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page).toHaveURL(/\/admin\/children\/c-1$/);
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
