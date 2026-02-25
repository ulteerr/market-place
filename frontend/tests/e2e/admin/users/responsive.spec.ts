import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupRolesCollectionApi } from '../../helpers/crud/roles';
import { E2E_RESPONSIVE_VIEWPORTS } from '../../helpers/viewports';

const users = [
  {
    id: 'u-1',
    email: 'ivanov@example.com',
    first_name: 'Иван',
    last_name: 'Иванов',
    middle_name: 'Иванович',
    phone: '+79990001122',
    roles: ['admin'],
    can_access_admin_panel: true,
  },
  {
    id: 'u-2',
    email: 'petrova@example.com',
    first_name: 'Анна',
    last_name: 'Петрова',
    middle_name: null,
    phone: null,
    roles: ['participant'],
    can_access_admin_panel: false,
  },
];

const assertNoHorizontalOverflow = async (page: Page) => {
  const hasOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > window.innerWidth
  );
  expect(hasOverflow).toBeFalsy();
};

const setupUsersIndexApi = async (page: Page) => {
  await page.route('**/api/admin/users**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: {
          data: users,
          current_page: 1,
          last_page: 1,
          per_page: 10,
          total: users.length,
        },
      }),
    });
  });
};

const setupUserShowApi = async (page: Page) => {
  await page.route('**/api/admin/users/u-1', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        user: users[0],
      }),
    });
  });
};

const setTableCardsMode = async (page: Page) => {
  const modeSelectInput = page.locator('.mode-select-wrap input').first();
  await modeSelectInput.click();
  await page.getByRole('button', { name: 'Таблица + карточки' }).click();
};

test.describe('Admin users responsive pages', () => {
  for (const viewport of E2E_RESPONSIVE_VIEWPORTS) {
    test(`index page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupUsersIndexApi(page);

      await page.goto('/admin/users');
      await expect(page.getByRole('heading', { level: 2, name: 'Пользователи' })).toBeVisible();
      await setTableCardsMode(page);

      if (viewport.width < 768) {
        await expect(page.locator('.user-card')).toHaveCount(2);
        await expect(page.locator('.admin-table:visible')).toHaveCount(0);
      } else {
        await expect(page.locator('.admin-table')).toBeVisible();
      }

      await assertNoHorizontalOverflow(page);
    });

    test(`new page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupRolesCollectionApi(page);

      await page.goto('/admin/users/new');
      await expect(
        page.getByRole('heading', { level: 2, name: 'Новый пользователь' })
      ).toBeVisible();
      await expect(page.getByLabel('Имя')).toBeVisible();
      await expect(page.getByLabel('Фамилия')).toBeVisible();
      await expect(page.getByLabel('Email')).toBeVisible();
      await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`show page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupUserShowApi(page);

      await page.goto('/admin/users/u-1');
      await expect(
        page.getByRole('heading', { level: 2, name: 'Профиль пользователя' })
      ).toBeVisible();
      await expect(page.locator('dd', { hasText: /^Иван$/ })).toBeVisible();
      await expect(page.locator('dd', { hasText: /^Иванов$/ })).toBeVisible();
      await expect(page.locator('a[href="/admin/users/u-1/edit"]')).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });

    test(`edit form page @ ${viewport.name}`, async ({ page }) => {
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await setupAdminAuth(page);
      await setupUserShowApi(page);
      await setupRolesCollectionApi(page);

      await page.goto('/admin/users/u-1/edit');
      await expect(
        page.getByRole('heading', { level: 2, name: 'Редактирование пользователя' })
      ).toBeVisible();
      await expect(page.getByLabel('Имя')).toHaveValue('Иван');
      await expect(page.getByLabel('Фамилия')).toHaveValue('Иванов');
      await expect(page.getByRole('button', { name: 'Сохранить' })).toBeVisible();

      await assertNoHorizontalOverflow(page);
    });
  }
});
