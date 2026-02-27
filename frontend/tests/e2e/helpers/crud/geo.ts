import type { Page, Route } from '@playwright/test';
import { readJsonBody } from '../http';

export interface AdminGeoCountry {
  id: string;
  name: string;
  iso_code?: string | null;
}

export interface AdminGeoRegion {
  id: string;
  name: string;
  country_id: string;
}

export interface AdminGeoCity {
  id: string;
  name: string;
  country_id?: string | null;
  region_id?: string | null;
}

export interface AdminGeoDistrict {
  id: string;
  name: string;
  city_id: string;
}

export const geoCountriesFixture: AdminGeoCountry[] = [
  { id: 'c-1', name: 'Россия', iso_code: 'RU' },
  { id: 'c-2', name: 'Беларусь', iso_code: 'BY' },
];

export const geoRegionsFixture: AdminGeoRegion[] = [
  { id: 'r-1', name: 'Московская область', country_id: 'c-1' },
  { id: 'r-2', name: 'Гомельская область', country_id: 'c-2' },
];

export const geoCitiesFixture: AdminGeoCity[] = [
  { id: 'ct-1', name: 'Москва', country_id: 'c-1', region_id: 'r-1' },
  { id: 'ct-2', name: 'Гомель', country_id: 'c-2', region_id: 'r-2' },
];

export const geoDistrictsFixture: AdminGeoDistrict[] = [
  { id: 'd-1', name: 'Арбат', city_id: 'ct-1' },
  { id: 'd-2', name: 'Центральный', city_id: 'ct-2' },
];

const buildCollectionResponse = <T>(items: T[], perPage: number) => ({
  status: 'ok',
  data: {
    data: items.slice(0, perPage),
    current_page: 1,
    last_page: 1,
    per_page: perPage,
    total: items.length,
  },
});

const setupCollectionRoute = async <T extends { id: string }>(
  page: Page,
  pattern: RegExp,
  seedItems: T[],
  options: {
    defaultSortBy: keyof T;
    sortableFields: Array<keyof T>;
    searchableFields: Array<keyof T>;
  }
) => {
  let dataset = [...seedItems];

  await page.route(pattern, async (route: Route) => {
    const url = new URL(route.request().url());
    const pathSegments = url.pathname.split('/');
    const maybeId = pathSegments[pathSegments.length - 1];
    const isItemPath =
      maybeId &&
      ![
        pathSegments[pathSegments.length - 2],
        'countries',
        'regions',
        'cities',
        'districts',
      ].includes(maybeId);

    if (route.request().method() === 'GET' && isItemPath) {
      const item = dataset.find((entry) => entry.id === maybeId);
      if (!item) {
        await route.fulfill({
          status: 404,
          contentType: 'application/json',
          body: JSON.stringify({ status: 'error', message: 'Not found' }),
        });
        return;
      }

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: item }),
      });
      return;
    }

    if (route.request().method() === 'DELETE') {
      dataset = dataset.filter((entry) => entry.id !== maybeId);
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok' }),
      });
      return;
    }

    const search = (url.searchParams.get('search') ?? '').trim().toLowerCase();
    const sortByParam = (url.searchParams.get('sort_by') ??
      String(options.defaultSortBy)) as keyof T;
    const sortDir = (url.searchParams.get('sort_dir') ?? 'asc').toLowerCase();
    const perPage = Number(url.searchParams.get('per_page') ?? 10);

    const resolvedSortBy: keyof T = options.sortableFields.includes(sortByParam)
      ? sortByParam
      : options.defaultSortBy;

    const filtered = dataset.filter((item) => {
      if (!search) {
        return true;
      }

      return options.searchableFields
        .map((field) => item[field])
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
      body: JSON.stringify(buildCollectionResponse(sorted, perPage)),
    });
  });
};

export const setupGeoCountriesCollectionApi = async (
  page: Page,
  seedCountries: AdminGeoCountry[] = geoCountriesFixture
) => {
  await setupCollectionRoute(
    page,
    /\/api\/admin\/geo\/countries(?:\/[^/?#]+)?(?:\?.*)?$/,
    seedCountries,
    {
      defaultSortBy: 'name',
      sortableFields: ['id', 'name', 'iso_code'],
      searchableFields: ['name', 'iso_code'],
    }
  );
};

export const setupGeoRegionsCollectionApi = async (
  page: Page,
  seedRegions: AdminGeoRegion[] = geoRegionsFixture
) => {
  await setupCollectionRoute(
    page,
    /\/api\/admin\/geo\/regions(?:\/[^/?#]+)?(?:\?.*)?$/,
    seedRegions,
    {
      defaultSortBy: 'name',
      sortableFields: ['id', 'name', 'country_id'],
      searchableFields: ['name', 'country_id'],
    }
  );
};

export const setupGeoCitiesCollectionApi = async (
  page: Page,
  seedCities: AdminGeoCity[] = geoCitiesFixture
) => {
  await setupCollectionRoute(
    page,
    /\/api\/admin\/geo\/cities(?:\/[^/?#]+)?(?:\?.*)?$/,
    seedCities,
    {
      defaultSortBy: 'name',
      sortableFields: ['id', 'name', 'country_id', 'region_id'],
      searchableFields: ['name', 'country_id', 'region_id'],
    }
  );
};

export const setupGeoDistrictsCollectionApi = async (
  page: Page,
  seedDistricts: AdminGeoDistrict[] = geoDistrictsFixture
) => {
  await setupCollectionRoute(
    page,
    /\/api\/admin\/geo\/districts(?:\/[^/?#]+)?(?:\?.*)?$/,
    seedDistricts,
    {
      defaultSortBy: 'name',
      sortableFields: ['id', 'name', 'city_id'],
      searchableFields: ['name', 'city_id'],
    }
  );
};

export const setupGeoCountryShowApi = async (page: Page, country: AdminGeoCountry) => {
  await page.route(`**/api/admin/geo/countries/${country.id}`, async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ status: 'ok', data: country }),
    });
  });
};

export const setupGeoRegionShowApi = async (page: Page, region: AdminGeoRegion) => {
  await page.route(`**/api/admin/geo/regions/${region.id}`, async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ status: 'ok', data: region }),
    });
  });
};

export const setupGeoCityShowApi = async (page: Page, city: AdminGeoCity) => {
  await page.route(`**/api/admin/geo/cities/${city.id}`, async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ status: 'ok', data: city }),
    });
  });
};

export const setupGeoDistrictShowApi = async (page: Page, district: AdminGeoDistrict) => {
  await page.route(`**/api/admin/geo/districts/${district.id}`, async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ status: 'ok', data: district }),
    });
  });
};

export const setupGeoCountryEditApi = async (
  page: Page,
  country: AdminGeoCountry,
  onPatch?: (payload: Record<string, unknown>) => void
) => {
  await page.route(`**/api/admin/geo/countries/${country.id}`, async (route: Route) => {
    const method = route.request().method();

    if (method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: country }),
      });
      return;
    }

    if (method === 'PATCH') {
      const payload = readJsonBody(route);
      onPatch?.(payload);

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: { ...country, ...payload } }),
      });
      return;
    }

    await route.fallback();
  });
};

export const setupGeoRegionEditApi = async (
  page: Page,
  region: AdminGeoRegion,
  onPatch?: (payload: Record<string, unknown>) => void
) => {
  await page.route(`**/api/admin/geo/regions/${region.id}`, async (route: Route) => {
    const method = route.request().method();

    if (method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: region }),
      });
      return;
    }

    if (method === 'PATCH') {
      const payload = readJsonBody(route);
      onPatch?.(payload);

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: { ...region, ...payload } }),
      });
      return;
    }

    await route.fallback();
  });
};

export const setupGeoCityEditApi = async (
  page: Page,
  city: AdminGeoCity,
  onPatch?: (payload: Record<string, unknown>) => void
) => {
  await page.route(`**/api/admin/geo/cities/${city.id}`, async (route: Route) => {
    const method = route.request().method();

    if (method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: city }),
      });
      return;
    }

    if (method === 'PATCH') {
      const payload = readJsonBody(route);
      onPatch?.(payload);

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: { ...city, ...payload } }),
      });
      return;
    }

    await route.fallback();
  });
};

export const setupGeoDistrictEditApi = async (
  page: Page,
  district: AdminGeoDistrict,
  onPatch?: (payload: Record<string, unknown>) => void
) => {
  await page.route(`**/api/admin/geo/districts/${district.id}`, async (route: Route) => {
    const method = route.request().method();

    if (method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: district }),
      });
      return;
    }

    if (method === 'PATCH') {
      const payload = readJsonBody(route);
      onPatch?.(payload);

      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'ok', data: { ...district, ...payload } }),
      });
      return;
    }

    await route.fallback();
  });
};
