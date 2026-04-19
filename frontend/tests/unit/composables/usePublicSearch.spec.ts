import { computed, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePublicSearch } from '~/composables/usePublicSearch';

describe('usePublicSearch', () => {
  const selectedCityId = ref('');

  beforeEach(() => {
    vi.restoreAllMocks();
    selectedCityId.value = '';
    vi.stubGlobal('usePublicCity', () => ({
      selectedCityId: computed(() => selectedCityId.value),
    }));
  });

  it('injects selected city into suggest requests', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: {
        queries: [],
        entities: [],
        recent: [],
      },
    });

    vi.stubGlobal('useApi', () => fetchMock);
    selectedCityId.value = 'city-1';

    const api = usePublicSearch();
    await api.suggest('футбол', {
      limit: 6,
    });

    expect(fetchMock).toHaveBeenCalledWith('/api/search/suggest', {
      method: 'GET',
      query: {
        q: 'футбол',
        limit: 6,
        city_id: 'city-1',
      },
      signal: undefined,
    });
  });

  it('injects selected city into search requests and respects explicit override', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: {
        query: 'футбол',
        items: [],
        pagination: {
          total: 0,
          per_page: 12,
          current_page: 1,
          last_page: 1,
        },
        facets: {
          categories: [],
          organizations: [],
          cities: [],
        },
      },
    });

    vi.stubGlobal('useApi', () => fetchMock);
    selectedCityId.value = 'city-selected';

    const api = usePublicSearch();
    await api.search({
      q: 'футбол',
      page: 2,
      city_id: 'city-explicit',
    });

    expect(fetchMock).toHaveBeenCalledWith('/api/search', {
      method: 'GET',
      query: {
        q: 'футбол',
        page: 2,
        city_id: 'city-explicit',
      },
      signal: undefined,
    });
  });
});
