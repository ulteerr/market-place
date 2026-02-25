import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';
import { readJsonBody } from '../../helpers/http';

const organizationPermissions = [
  'org.company.profile.read',
  'org.company.profile.update',
  'org.company.profile.delete',
];

const setupOrganizationsForm = async (page: Page) => {
  await setupAdminAuth(page, {
    permissions: [...(defaultAdminUser.permissions ?? []), ...organizationPermissions],
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
              last_name: 'Васильев',
              middle_name: 'Павлович',
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

test.describe('Admin organizations form pages', () => {
  test('shows create form on /admin/organizations/new', async ({ page }) => {
    await setupOrganizationsForm(page);

    await page.goto('/admin/organizations/new');
    await expect(page.getByRole('heading', { level: 2, name: 'Новая организация' })).toBeVisible();
    await expect(page.getByLabel('Название')).toBeVisible();
    await expect(page.getByLabel('Email')).toBeVisible();
    await expect(page.getByLabel('Телефон')).toBeVisible();
    await expect(page.getByLabel('ID владельца')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Создать' })).toBeVisible();
  });

  test('creates organization on /admin/organizations/new', async ({ page }) => {
    await setupOrganizationsForm(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route('**/api/admin/organizations', async (route) => {
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
            id: 'org-new',
          },
        }),
      });
    });

    await page.goto('/admin/organizations/new');

    await page.getByLabel('Название').fill('  Детский клуб ');
    await page.getByLabel('Email').fill('club@example.com');
    await page.getByLabel('Телефон').fill('+79990001122');
    await page.getByLabel('ID владельца').click();
    await page.getByRole('button', { name: /u-1$/ }).first().click();
    await page.getByRole('button', { name: 'Создать' }).click();

    await expect(page).toHaveURL(/\/admin\/organizations$/);
    expect(capturedCreatePayload).toEqual({
      name: 'Детский клуб',
      description: null,
      address: null,
      phone: '+79990001122',
      email: 'club@example.com',
      status: null,
      source_type: null,
      ownership_status: null,
      owner_user_id: 'u-1',
    });
  });

  test('updates organization on /admin/organizations/[id]/edit', async ({ page }) => {
    await setupOrganizationsForm(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await page.route('**/api/admin/organizations/org-1', async (route) => {
      const method = route.request().method();

      if (method === 'GET') {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: {
              id: 'org-1',
              name: 'Организация 1',
              description: 'Тестовое описание',
              address: 'г. Москва, ул. Тестовая, д. 1',
              phone: '+79990000001',
              email: 'org1@example.com',
              status: 'active',
              source_type: 'manual',
              ownership_status: 'unclaimed',
              owner_user_id: 'u-1',
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
              id: 'org-1',
            },
          }),
        });
        return;
      }

      await route.fallback();
    });

    await page.goto('/admin/organizations/org-1/edit');
    await expect(
      page.getByRole('heading', { level: 2, name: 'Редактирование организации' })
    ).toBeVisible();

    await page.getByLabel('Название').fill('  Новое название ');
    await page.getByLabel('Email').fill('new-org@example.com');
    await page.getByLabel('Телефон').fill('+79990009999');
    await page.getByLabel('Адрес').fill(' ');
    await page.getByRole('button', { name: 'Сохранить' }).click();

    await expect(page).toHaveURL(/\/admin\/organizations\/org-1$/);
    expect(capturedUpdatePayload).toEqual({
      name: 'Новое название',
      description: 'Тестовое описание',
      address: null,
      phone: '+79990009999',
      email: 'new-org@example.com',
      status: 'active',
      source_type: 'manual',
      ownership_status: 'unclaimed',
      owner_user_id: 'u-1',
    });
  });
});
