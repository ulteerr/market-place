import type { Page, Route } from '@playwright/test';
import { readJsonBody } from '../http';

export interface AdminMetroLine {
  id: string;
  name: string;
  external_id?: string | null;
  line_id?: string | null;
  color?: string | null;
  city_id: string;
  source: string;
}

export interface AdminMetroStation {
  id: string;
  name: string;
  external_id?: string | null;
  line_id?: string | null;
  geo_lat?: number | null;
  geo_lon?: number | null;
  is_closed?: boolean | null;
  metro_line_id: string;
  city_id: string;
  source: string;
}

export const metroLinesFixture: AdminMetroLine[] = [
  {
    id: 'ml-1',
    name: 'Сокольническая',
    external_id: 'line-ext-1',
    line_id: '1',
    color: '#D12D2D',
    city_id: 'msk',
    source: 'manual',
  },
  {
    id: 'ml-2',
    name: 'Арбатско-Покровская',
    external_id: 'line-ext-2',
    line_id: '3',
    color: '#2B4EA2',
    city_id: 'msk',
    source: 'import',
  },
];

export const metroStationsFixture: AdminMetroStation[] = [
  {
    id: 'ms-1',
    name: 'Охотный ряд',
    external_id: 'station-ext-1',
    line_id: '1',
    geo_lat: 55.757,
    geo_lon: 37.615,
    is_closed: false,
    metro_line_id: 'ml-1',
    city_id: 'msk',
    source: 'manual',
  },
  {
    id: 'ms-2',
    name: 'Арбатская',
    external_id: 'station-ext-2',
    line_id: '3',
    geo_lat: 55.752,
    geo_lon: 37.604,
    is_closed: false,
    metro_line_id: 'ml-2',
    city_id: 'msk',
    source: 'import',
  },
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

export const setupMetroLinesCollectionApi = async (
  page: Page,
  seedLines: AdminMetroLine[] = metroLinesFixture
) => {
  let dataset = [...seedLines];

  await page.route(
    /\/api\/admin\/geo\/metro-lines(?:\/[^/?#]+)?(?:\?.*)?$/,
    async (route: Route) => {
      const url = new URL(route.request().url());
      const pathMatch = url.pathname.match(/\/api\/admin\/geo\/metro-lines\/([^/?#]+)/);
      const requestedId = pathMatch?.[1];

      if (route.request().method() === 'GET' && requestedId) {
        const line = dataset.find((item) => item.id === requestedId);
        if (!line) {
          await route.fulfill({
            status: 404,
            contentType: 'application/json',
            body: JSON.stringify({
              status: 'error',
              message: 'Not found',
            }),
          });
          return;
        }

        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
            data: line,
          }),
        });
        return;
      }

      if (route.request().method() === 'DELETE') {
        const id = route.request().url().split('/').pop();
        dataset = dataset.filter((item) => item.id !== id);

        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
          }),
        });
        return;
      }

      const search = (url.searchParams.get('search') ?? '').trim().toLowerCase();
      const sortBy = url.searchParams.get('sort_by') ?? 'name';
      const sortDir = (url.searchParams.get('sort_dir') ?? 'asc').toLowerCase();
      const perPage = Number(url.searchParams.get('per_page') ?? 10);
      const sortableFields: Array<keyof AdminMetroLine> = [
        'id',
        'name',
        'line_id',
        'color',
        'city_id',
        'source',
      ];
      const resolvedSortBy: keyof AdminMetroLine = sortableFields.includes(
        sortBy as keyof AdminMetroLine
      )
        ? (sortBy as keyof AdminMetroLine)
        : 'name';

      const filtered = dataset.filter((item) => {
        if (!search) {
          return true;
        }

        return [item.name, item.line_id, item.color, item.city_id, item.source]
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
    }
  );
};

export const setupMetroStationsCollectionApi = async (
  page: Page,
  seedStations: AdminMetroStation[] = metroStationsFixture
) => {
  let dataset = [...seedStations];

  await page.route(
    /\/api\/admin\/geo\/metro-stations(?:\/[^/?#]+)?(?:\?.*)?$/,
    async (route: Route) => {
      if (route.request().method() === 'DELETE') {
        const id = route.request().url().split('/').pop();
        dataset = dataset.filter((item) => item.id !== id);

        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({
            status: 'ok',
          }),
        });
        return;
      }

      const url = new URL(route.request().url());
      const search = (url.searchParams.get('search') ?? '').trim().toLowerCase();
      const sortBy = url.searchParams.get('sort_by') ?? 'name';
      const sortDir = (url.searchParams.get('sort_dir') ?? 'asc').toLowerCase();
      const perPage = Number(url.searchParams.get('per_page') ?? 10);
      const sortableFields: Array<keyof AdminMetroStation> = [
        'id',
        'name',
        'line_id',
        'metro_line_id',
        'city_id',
        'source',
      ];
      const resolvedSortBy: keyof AdminMetroStation = sortableFields.includes(
        sortBy as keyof AdminMetroStation
      )
        ? (sortBy as keyof AdminMetroStation)
        : 'name';

      const filtered = dataset.filter((item) => {
        if (!search) {
          return true;
        }

        return [item.name, item.line_id, item.metro_line_id, item.city_id, item.source]
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
    }
  );
};

export const setupMetroLineShowApi = async (page: Page, line: AdminMetroLine) => {
  await page.route(`**/api/admin/geo/metro-lines/${line.id}`, async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: line,
      }),
    });
  });
};

export const setupMetroStationShowApi = async (page: Page, station: AdminMetroStation) => {
  await page.route(`**/api/admin/geo/metro-stations/${station.id}`, async (route: Route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status: 'ok',
        data: station,
      }),
    });
  });
};

export const setupMetroLineEditApi = async (
  page: Page,
  line: AdminMetroLine,
  onPatch?: (payload: Record<string, unknown>) => void
) => {
  await page.route(`**/api/admin/geo/metro-lines/${line.id}`, async (route: Route) => {
    const method = route.request().method();

    if (method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: line,
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
            ...line,
            ...payload,
          },
        }),
      });
      return;
    }

    await route.fallback();
  });
};

export const setupMetroStationEditApi = async (
  page: Page,
  station: AdminMetroStation,
  onPatch?: (payload: Record<string, unknown>) => void
) => {
  await page.route(`**/api/admin/geo/metro-stations/${station.id}`, async (route: Route) => {
    const method = route.request().method();

    if (method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          status: 'ok',
          data: station,
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
            ...station,
            ...payload,
          },
        }),
      });
      return;
    }

    await route.fallback();
  });
};
