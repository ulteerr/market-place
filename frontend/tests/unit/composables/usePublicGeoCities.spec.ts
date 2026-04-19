import { beforeEach, describe, expect, it, vi } from 'vitest';
import { toPublicCitySetting, usePublicGeoCities } from '~/composables/usePublicGeoCities';

describe('usePublicGeoCities', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('requests public city options list', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: [],
    });

    vi.stubGlobal('useApi', () => fetchMock);

    const api = usePublicGeoCities();
    await api.list({
      search: 'Екат',
      limit: 12,
    });

    expect(fetchMock).toHaveBeenCalledWith('/api/public/geo/cities', {
      method: 'GET',
      query: {
        search: 'Екат',
        limit: 12,
      },
      signal: undefined,
    });
  });

  it('requests detected public city', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: {
        resolved_by: 'ip',
        city: {
          id: 'city-1',
          name: 'Москва',
          country_id: 'country-1',
          region_id: 'region-1',
          country: {
            name: 'Россия',
          },
          region: {
            name: 'Москва',
          },
        },
      },
    });

    vi.stubGlobal('useApi', () => fetchMock);

    const api = usePublicGeoCities();
    await api.detect();

    expect(fetchMock).toHaveBeenCalledWith('/api/public/geo/detect-city', {
      method: 'GET',
      signal: undefined,
    });
  });

  it('maps public geo city payload to public city settings contract', () => {
    expect(
      toPublicCitySetting(
        {
          id: 'city-1',
          name: 'Москва',
          country_id: 'country-1',
          region_id: 'region-1',
          country: {
            name: 'Россия',
          },
          region: {
            name: 'Москва',
          },
        },
        'manual'
      )
    ).toEqual({
      city_id: 'city-1',
      city_name: 'Москва',
      source: 'manual',
      region_id: 'region-1',
      region_name: 'Москва',
      country_id: 'country-1',
      country_name: 'Россия',
    });
  });
});
