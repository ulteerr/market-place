import type { Page, Route } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupRolesCollectionApi } from '../../helpers/crud/roles';
import { readJsonBody } from '../../helpers/http';

const existingUser = {
  id: 'u-1',
  email: 'ivanov@example.com',
  first_name: 'Иван',
  last_name: 'Иванов',
  middle_name: 'Иванович',
  phone: '+79990001122',
  roles: ['admin'],
  can_access_admin_panel: true,
};

const setupUsersForm = async (page: Page) => {
  await setupAdminAuth(page);
  await setupRolesCollectionApi(page);
};

test.describe('Admin users form pages', () => {
  test('shows create form on /admin/users/new', async ({ page }) => {
    await setupUsersForm(page);

    await page.goto('/admin/users/new');
    await expect(page.getByRole('heading', { level: 2, name: 'Новый пользователь' })).toBeVisible();
    await expect(page.getByLabel('Имя')).toBeVisible();
    await expect(page.getByLabel('Фамилия')).toBeVisible();
    await expect(page.getByLabel('Email')).toBeVisible();
    await expect(page.getByLabel('Телефон')).toBeVisible();
    await expect(page.locator('label:has-text("Пароль") input').first()).toBeVisible();
    await expect(
      page.locator('label:has-text("Подтверждение пароля") input').first()
    ).toBeVisible();
    await expect(page.getByLabel('Роли')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();
  });

  test('updates user on /admin/users/[id]/edit', async ({ page }) => {
    await setupUsersForm(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await page.route('**/api/admin/users/u-1', async (route) => {
      const method = route.request().method();

      if (method === 'GET') {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            user: existingUser,
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
              ...existingUser,
              ...capturedUpdatePayload,
            },
          }),
        });
        return;
      }

      await route.fallback();
    });

    await page.goto('/admin/users/u-1/edit');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование пользователя' })
    ).toBeVisible();
    await expect(page.getByLabel('Имя')).toHaveValue('Иван');

    await page.getByLabel('Имя').fill('  Иван ');
    await page.getByLabel('Фамилия').fill(' Петров ');
    await page.getByLabel('Отчество').fill(' ');
    await page.getByLabel('Email').fill('ivan.petrov@example.com');
    await page.getByLabel('Телефон').fill('+79990002233');

    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page).toHaveURL(/\/admin\/users\/u-1$/);
    expect(capturedUpdatePayload).toEqual({
      first_name: 'Иван',
      last_name: 'Петров',
      middle_name: null,
      email: 'ivan.petrov@example.com',
      phone: '+79990002233',
      roles: ['admin'],
    });
  });
});
