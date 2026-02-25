import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

const childrenPermissions = ['org.children.read', 'org.children.write'];

const childrenFixture = [
  {
    id: 'c-1',
    user_id: 'u-1',
    first_name: 'Виктория',
    last_name: 'Кузнецова',
    middle_name: 'Тимофеевна',
    gender: 'female',
    birth_date: '2018-07-14',
    user: {
      id: 'u-1',
      first_name: 'Павел',
      last_name: 'Кузнецов',
      middle_name: 'Иванович',
      email: 'pavel@example.com',
    },
  },
  {
    id: 'c-2',
    user_id: 'u-2',
    first_name: 'Иван',
    last_name: 'Смирнов',
    middle_name: null,
    gender: 'male',
    birth_date: '2017-03-20',
    user: {
      id: 'u-2',
      first_name: 'Анна',
      last_name: 'Смирнова',
      middle_name: null,
      email: 'anna@example.com',
    },
  },
];

const setupChildrenPage = async (page: Page) => {
  await setupAdminAuth(page, {
    permissions: [...(defaultAdminUser.permissions ?? []), ...childrenPermissions],
  });

  let dataset = [...childrenFixture];

  await page.route('**/api/admin/children**', async (route) => {
    if (route.request().method() === 'DELETE') {
      const childId = route.request().url().split('/').pop();
      dataset = dataset.filter((item) => item.id !== childId);

      await route.fulfill({
        status: 204,
        body: '',
      });
      return;
    }

    const url = new URL(route.request().url());
    const search = (url.searchParams.get('search') ?? '').trim().toLowerCase();
    const perPage = Number(url.searchParams.get('per_page') ?? 10);

    const filtered = dataset.filter((item) => {
      if (!search) {
        return true;
      }

      return [item.first_name, item.last_name, item.middle_name, item.user_id]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(search));
    });

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          data: filtered.slice(0, perPage),
          current_page: 1,
          last_page: 1,
          per_page: perPage,
          total: filtered.length,
        },
      }),
    });
  });
};

test.describe('Admin children page', () => {
  test('redirects unauthenticated user from /admin/children to /login', async ({ page }) => {
    await page.goto('/admin/children');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows children table for authenticated admin', async ({ page }) => {
    await setupChildrenPage(page);
    await page.goto('/admin/children');

    await expect(page.getByRole('heading', { level: 2, name: 'Дети' })).toBeVisible();
    await expect(page.getByText('Кузнецова Виктория Тимофеевна')).toBeVisible();
    await expect(page.getByText('Смирнов Иван')).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('filters children by search automatically', async ({ page }) => {
    await setupChildrenPage(page);
    await page.goto('/admin/children');

    await page.locator('input.admin-input').first().fill('Кузнецова');

    await expect(page).toHaveURL(/search=%D0%9A%D1%83%D0%B7%D0%BD%D0%B5%D1%86%D0%BE%D0%B2%D0%B0/);
    await expect(page.getByText('Кузнецова Виктория Тимофеевна')).toBeVisible();
    await expect(page.getByText('Смирнов Иван')).toHaveCount(0);
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('deletes child through confirm modal', async ({ page }) => {
    await setupChildrenPage(page);
    await page.goto('/admin/children');

    await page.getByRole('button', { name: 'Удалить' }).first().click();

    await expect(
      page.getByText('Удалить запись ребенка Кузнецова Виктория Тимофеевна?')
    ).toBeVisible();
    await page.locator('[role="dialog"]').getByRole('button', { name: 'Удалить' }).click();

    await expect(page.getByText('Кузнецова Виктория Тимофеевна')).toHaveCount(0);
    await expect(page.getByText('Смирнов Иван')).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });
});
