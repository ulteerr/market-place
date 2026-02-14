import type { Page, Route } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { setupAdminAuth } from '../../helpers/admin-auth';
import { setupRolesCollectionApi } from '../../helpers/crud/roles';
import { readRawBody } from '../../helpers/http';

const existingUser = {
  id: 'u-1',
  email: 'ivanov@example.com',
  first_name: 'Иван',
  last_name: 'Иванов',
  middle_name: 'Иванович',
  phone: '+79990001122',
  roles: ['admin'],
  can_access_admin_panel: true,
  avatar: {
    id: 'file-u-1',
    url: 'https://example.com/users/u-1-avatar.png',
    original_name: 'u-1-avatar.png',
    collection: 'avatar',
  },
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

    let capturedUpdatePayload = '';

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
        capturedUpdatePayload = readRawBody(route);
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: existingUser,
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
    expect(capturedUpdatePayload).toContain('name="first_name"');
    expect(capturedUpdatePayload).toContain('\r\nИван\r\n');
    expect(capturedUpdatePayload).toContain('name="last_name"');
    expect(capturedUpdatePayload).toContain('\r\nПетров\r\n');
    expect(capturedUpdatePayload).toContain('name="email"');
    expect(capturedUpdatePayload).toContain('\r\nivan.petrov@example.com\r\n');
    expect(capturedUpdatePayload).toContain('name="phone"');
    expect(capturedUpdatePayload).toContain('\r\n+79990002233\r\n');
    expect(capturedUpdatePayload).toContain('name="roles[]"');
    expect(capturedUpdatePayload).toContain('\r\nadmin\r\n');
  });

  test('creates single changelog entry when user and roles change together', async ({ page }) => {
    await setupUsersForm(page);

    let currentUser = { ...existingUser };
    let changeLogPage = {
      status: 'ok',
      data: {
        list_mode: 'latest',
        current_page: 1,
        data: [
          {
            id: 'log-1',
            auditable_type: 'Modules\\\\Users\\\\Models\\\\User',
            auditable_id: 'u-1',
            event: 'update',
            version: 1,
            before: { first_name: 'Иван' },
            after: { first_name: 'Иван' },
            changed_fields: ['first_name'],
            actor_type: 'Modules\\\\Users\\\\Models\\\\User',
            actor_id: '1',
            batch_id: 'b-1',
            rolled_back_from_id: null,
            meta: null,
            created_at: '2026-02-14T18:00:00.000000Z',
          },
        ],
        last_page: 1,
        per_page: 20,
        total: 1,
      },
    };

    await page.route('**/api/admin/users/u-1', async (route) => {
      const method = route.request().method();

      if (method === 'GET') {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            user: currentUser,
          }),
        });
        return;
      }

      if (method === 'PATCH') {
        currentUser = {
          ...currentUser,
          last_name: 'Петров',
          roles: ['admin', 'manager'],
        };

        changeLogPage = {
          status: 'ok',
          data: {
            ...changeLogPage.data,
            data: [
              {
                id: 'log-2',
                auditable_type: 'Modules\\\\Users\\\\Models\\\\User',
                auditable_id: 'u-1',
                event: 'update',
                version: 2,
                before: { last_name: 'Иванов', roles: ['admin'] },
                after: { last_name: 'Петров', roles: ['admin', 'manager'] },
                changed_fields: ['last_name', 'roles'],
                actor_type: 'Modules\\\\Users\\\\Models\\\\User',
                actor_id: '1',
                batch_id: 'b-2',
                rolled_back_from_id: null,
                meta: null,
                created_at: '2026-02-14T18:05:00.000000Z',
              },
              ...changeLogPage.data.data,
            ],
            total: 2,
          },
        };

        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: currentUser,
          }),
        });
        return;
      }

      await route.fallback();
    });

    await page.route('**/api/admin/changelog**', async (route) => {
      const url = route.request().url();
      if (!url.includes('model=user') || !url.includes('entity_id=u-1')) {
        await route.fallback();
        return;
      }

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify(changeLogPage),
      });
    });

    await page.goto('/admin/users/u-1/edit');

    await page.getByLabel('Фамилия').fill('Петров');

    await page.getByLabel('Роли').click();
    await page
      .getByText(/manager\s*\(Менеджер\)/i)
      .first()
      .click();
    await page.keyboard.press('Escape');

    await page.getByRole('button', { name: 'Сохранить' }).click();
    await expect(page).toHaveURL(/\/admin\/users\/u-1$/);

    await expect(page.locator('text=Версия #2')).toBeVisible();
    await expect(page.locator('text=Изменённые поля: Фамилия, Роли')).toBeVisible();
    await expect(page.locator('text=Фамилия:')).toHaveCount(1);
    await expect(page.locator('text=Роли:')).toHaveCount(1);
    await expect(page.locator('text=Версия #3')).toHaveCount(0);
  });

  test('creates user with avatar on /admin/users/new', async ({ page }) => {
    await setupUsersForm(page);

    let capturedCreatePayload = '';

    await page.route('**/api/admin/users', async (route) => {
      if (route.request().method() === 'POST') {
        capturedCreatePayload = readRawBody(route);
        await route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: {
              id: 'u-new',
            },
          }),
        });
        return;
      }

      await route.fallback();
    });

    await page.goto('/admin/users/new');
    await page.getByLabel('Имя').fill('Новый');
    await page.getByLabel('Фамилия').fill('Пользователь');
    await page.getByLabel('Email').fill('new.user@example.com');
    await page.locator('label:has-text("Пароль") input').first().fill('password123');
    await page.locator('label:has-text("Подтверждение пароля") input').first().fill('password123');

    await page.setInputFiles('input[type="file"]', {
      name: 'avatar.png',
      mimeType: 'image/png',
      buffer: Buffer.from(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO6W1k8AAAAASUVORK5CYII=',
        'base64'
      ),
    });

    await expect(page.getByRole('button', { name: 'Удалить' }).first()).toBeVisible();
    await expect(page.locator('img[alt="Аватар пользователя"]').first()).toBeVisible();

    await page.getByRole('button', { name: 'Создать' }).click();

    await expect(page).toHaveURL(/\/admin\/users$/);
    expect(capturedCreatePayload).toContain('name="first_name"');
    expect(capturedCreatePayload).toContain('\r\nНовый\r\n');
    expect(capturedCreatePayload).toContain('name="last_name"');
    expect(capturedCreatePayload).toContain('\r\nПользователь\r\n');
    expect(capturedCreatePayload).toContain('name="email"');
    expect(capturedCreatePayload).toContain('\r\nnew.user@example.com\r\n');
    expect(capturedCreatePayload).toContain('name="avatar"; filename="avatar.png"');
  });

  test('marks avatar for deletion on edit form', async ({ page }) => {
    await setupUsersForm(page);

    let capturedUpdatePayload = '';

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
        capturedUpdatePayload = readRawBody(route);
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: existingUser,
          }),
        });
        return;
      }

      await route.fallback();
    });

    await page.goto('/admin/users/u-1/edit');
    await expect(page.locator('img[src="https://example.com/users/u-1-avatar.png"]')).toBeVisible();

    await page.getByRole('button', { name: 'Удалить' }).first().click();
    await expect(page.locator('img[src="https://example.com/users/u-1-avatar.png"]')).toHaveCount(
      0
    );

    await page.getByRole('button', { name: 'Сохранить' }).click();
    await expect(page).toHaveURL(/\/admin\/users\/u-1$/);
    expect(capturedUpdatePayload).toContain('name="avatar_delete"');
    expect(capturedUpdatePayload).toContain('\r\n1\r\n');
  });
});
