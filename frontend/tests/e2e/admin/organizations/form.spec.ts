import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';
import {
  geoCitiesFixture,
  geoCountriesFixture,
  geoDistrictsFixture,
  geoRegionsFixture,
  setupGeoCitiesCollectionApi,
  setupGeoCountriesCollectionApi,
  setupGeoDistrictsCollectionApi,
  setupGeoRegionsCollectionApi,
} from '../../helpers/crud/geo';
import { metroStationsFixture, setupMetroStationsCollectionApi } from '../../helpers/crud/metro';
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

  await setupGeoCountriesCollectionApi(page, geoCountriesFixture);
  await setupGeoRegionsCollectionApi(page, geoRegionsFixture);
  await setupGeoCitiesCollectionApi(page, geoCitiesFixture);
  await setupGeoDistrictsCollectionApi(page, geoDistrictsFixture);
  await setupMetroStationsCollectionApi(page, metroStationsFixture);

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
    const form = page.locator('article form').first();
    await expect(form.getByLabel('Название *')).toHaveCount(1);
    await expect(form.getByLabel('Email')).toHaveCount(1);
    await expect(form.getByLabel('Телефон')).toHaveCount(1);
    await expect(form.getByLabel('ID владельца')).toHaveCount(1);
    await expect(form.locator('button[type="submit"]')).toHaveCount(1);
  });

  test('creates organization on /admin/organizations/new', async ({ page }) => {
    await setupOrganizationsForm(page);

    let capturedCreatePayload: Record<string, unknown> | null = null;

    await page.route(/\/api\/admin\/organizations(?:\?.*)?$/, async (route) => {
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
    const form = page.locator('article form').first();

    await form.getByLabel('Название *').fill('  Детский клуб ');
    await form.getByLabel('Email').fill('club@example.com');
    await form.getByLabel('Телефон').fill('+79990001122');
    await form.locator('button[type="submit"]').click();

    await expect.poll(() => capturedCreatePayload !== null).toBeTruthy();
    expect(capturedCreatePayload).toEqual({
      name: 'Детский клуб',
      description: null,
      locations: [],
      phone: '+79990001122',
      email: 'club@example.com',
      status: null,
      source_type: null,
      ownership_status: null,
      owner_user_id: null,
    });
  });

  test('updates organization on /admin/organizations/[id]/edit', async ({ page }) => {
    await setupOrganizationsForm(page);

    let capturedUpdatePayload: Record<string, unknown> | null = null;

    await page.route(/\/api\/admin\/organizations\/org-1(?:\?.*)?$/, async (route) => {
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
    const form = page.locator('article form').first();
    await expect(form.getByLabel('Название *')).toHaveValue('Организация 1', { timeout: 15000 });
    await form.getByLabel('Название *').fill('  Новое название ');
    await form.getByLabel('Email').fill('new-org@example.com');
    await form.getByLabel('Телефон').fill('+79990009999');
    await form.getByLabel('Адрес').fill(' ');
    await form.locator('button[type="submit"]').click();

    await expect.poll(() => capturedUpdatePayload !== null).toBeTruthy();
    expect(capturedUpdatePayload).toEqual({
      name: 'Новое название',
      description: 'Тестовое описание',
      locations: [],
      phone: '+79990009999',
      email: 'new-org@example.com',
      status: 'active',
      source_type: 'manual',
      ownership_status: 'unclaimed',
      owner_user_id: 'u-1',
    });
  });
});
