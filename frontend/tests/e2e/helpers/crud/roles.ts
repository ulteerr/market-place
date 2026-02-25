import type { Page, Route } from '@playwright/test';
import { readJsonBody } from '../http';

export interface AdminRole {
  id: string;
  code: string;
  label: string;
  is_system: boolean;
}

export const adminRolesFixture: AdminRole[] = [
  { id: 'r-1', code: 'admin', label: 'Администратор', is_system: true },
  { id: 'r-2', code: 'manager', label: 'Менеджер', is_system: false },
  { id: 'r-3', code: 'participant', label: 'Участник', is_system: true },
];

export const buildRolesCollectionResponse = (roles: AdminRole[], perPage = 100) => ({
  status: 'ok',
  data: {
    data: roles.slice(0, perPage),
    current_page: 1,
    last_page: 1,
    per_page: perPage,
    total: roles.length,
  },
});

export const setupRolesCollectionApi = async (
  page: Page,
  seedRoles: AdminRole[] = adminRolesFixture
) => {
  const dataset = [...seedRoles];

  await page.route('**/api/admin/roles**', async (route) => {
    const url = new URL(route.request().url());
    const search = (url.searchParams.get('search') ?? '').trim().toLowerCase();
    const sortBy = url.searchParams.get('sort_by') ?? 'code';
    const sortDir = (url.searchParams.get('sort_dir') ?? 'asc').toLowerCase();
    const perPage = Number(url.searchParams.get('per_page') ?? 10);
    const sortableFields: Array<keyof AdminRole> = ['id', 'code', 'label', 'is_system'];
    const resolvedSortBy: keyof AdminRole = sortableFields.includes(sortBy as keyof AdminRole)
      ? (sortBy as keyof AdminRole)
      : 'code';

    const filtered = dataset.filter((item) => {
      if (!search) {
        return true;
      }

      return [item.code, item.label]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(search));
    });

    const sorted = [...filtered].sort((left, right) => {
      const leftValue = String(left[resolvedSortBy] ?? '').toLowerCase();
      const rightValue = String(right[resolvedSortBy] ?? '').toLowerCase();
      const compare = leftValue.localeCompare(rightValue, 'ru');

      return sortDir === 'desc' ? -compare : compare;
    });

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(buildRolesCollectionResponse(sorted, perPage)),
    });
  });
};

export const setupRoleShowApi = async (page: Page, role: AdminRole) => {
  await page.route(`**/api/admin/roles/${role.id}`, async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        role,
      }),
    });
  });
};

export const setupRoleEditApi = async (
  page: Page,
  role: AdminRole,
  onPatch?: (payload: Record<string, unknown>) => void
) => {
  await page.route(`**/api/admin/roles/${role.id}`, async (route: Route) => {
    const method = route.request().method();

    if (method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          role,
        }),
      });
      return;
    }

    if (method === 'PATCH') {
      const payload = readJsonBody(route);
      onPatch?.(payload);

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: {
            ...role,
            ...payload,
          },
        }),
      });
      return;
    }

    await route.fallback();
  });
};
