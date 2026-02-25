import type { Page } from '@playwright/test';
import { expect, test } from '@playwright/test';
import { defaultAdminUser, setupAdminAuth } from '../../helpers/admin-auth';

const organizationPermissions = [
  'org.company.profile.read',
  'org.company.profile.update',
  'org.company.profile.delete',
];

const organizationsFixture = [
  {
    id: 'org-1',
    name: 'Организация 1',
    status: 'active',
    ownership_status: 'unclaimed',
    owner_user_id: 'u-1',
    owner: {
      id: 'u-1',
      first_name: 'Павел',
      last_name: 'Васильев',
      middle_name: 'Павлович',
    },
    created_at: '2026-02-20T11:30:00.000000Z',
  },
  {
    id: 'org-2',
    name: 'Альфа Центр',
    status: 'draft',
    ownership_status: 'claimed',
    owner_user_id: 'u-2',
    owner: {
      id: 'u-2',
      first_name: 'Анна',
      last_name: 'Смирнова',
      middle_name: null,
    },
    created_at: '2026-02-21T09:00:00.000000Z',
  },
];

const setupOrganizationsPage = async (page: Page) => {
  await setupAdminAuth(page, {
    permissions: [...(defaultAdminUser.permissions ?? []), ...organizationPermissions],
  });

  let dataset = [...organizationsFixture];

  await page.route('**/api/admin/organizations**', async (route) => {
    if (route.request().method() === 'DELETE') {
      const orgId = route.request().url().split('/').pop();
      dataset = dataset.filter((item) => item.id !== orgId);

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

      return [item.name, item.owner_user_id]
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

test.describe('Admin organizations page', () => {
  test('redirects unauthenticated user from /admin/organizations to /login', async ({ page }) => {
    await page.goto('/admin/organizations');

    await expect(page).toHaveURL(/\/login$/);
    await expect(
      page.getByRole('heading', { level: 1, name: 'Вход в админ-панель' })
    ).toBeVisible();
  });

  test('shows organizations table for authenticated admin', async ({ page }) => {
    await setupOrganizationsPage(page);
    await page.goto('/admin/organizations');

    await expect(page.getByRole('heading', { level: 2, name: 'Организации' })).toBeVisible();
    await expect(page.getByText('Организация 1', { exact: true })).toBeVisible();
    await expect(page.getByText('Альфа Центр', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 2 из 2.')).toBeVisible();
  });

  test('filters organizations by search automatically', async ({ page }) => {
    await setupOrganizationsPage(page);
    await page.goto('/admin/organizations');

    await page.locator('input.admin-input').first().fill('Альфа');

    await expect(page).toHaveURL(/search=%D0%90%D0%BB%D1%8C%D1%84%D0%B0/);
    await expect(page.getByText('Организация 1', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Альфа Центр', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });

  test('deletes organization through confirm modal', async ({ page }) => {
    await setupOrganizationsPage(page);
    await page.goto('/admin/organizations');

    await page.getByRole('button', { name: 'Удалить' }).first().click();

    await expect(page.getByText('Удалить организацию Организация 1?')).toBeVisible();
    await page.locator('[role="dialog"]').getByRole('button', { name: 'Удалить' }).click();

    await expect(page.getByText('Организация 1', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Альфа Центр', { exact: true })).toBeVisible();
    await expect(page.getByText('Показано 1 из 1.')).toBeVisible();
  });
});
