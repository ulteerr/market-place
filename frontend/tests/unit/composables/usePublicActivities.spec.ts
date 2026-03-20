import { beforeEach, describe, expect, it, vi } from 'vitest';
import { buildPublicActivityPath, usePublicActivities } from '~/composables/usePublicActivities';

describe('usePublicActivities', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('requests featured activities and normalizes cover urls', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: [
        {
          id: 'act-1',
          public_key: 'futbol-uuid-1',
          name: 'Футбол',
          slug: 'futbol',
          short_description: 'Короткое описание',
          is_featured: true,
          published_at: '2026-03-15T12:00:00Z',
          cover: {
            id: 'file-1',
            url: '/storage/activities/cover.jpg',
            original_name: 'cover.jpg',
            mime_type: 'image/jpeg',
            size: 123,
            collection: 'cover',
          },
        },
      ],
    });

    vi.stubGlobal('useApi', () => fetchMock);
    vi.stubGlobal('useRuntimeConfig', () => ({
      public: { apiBase: 'http://api.localhost:8080' },
    }));

    const api = usePublicActivities();
    const items = await api.featured(8);

    expect(fetchMock).toHaveBeenCalledWith('/api/activities/featured', {
      method: 'GET',
      query: { limit: 8 },
    });
    expect(items[0]?.cover?.url).toBe('http://api.localhost:8080/storage/activities/cover.jpg');
  });

  it('requests feed with cursor params', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: {
        items: [],
        next_cursor: 'cursor-2',
      },
    });

    vi.stubGlobal('useApi', () => fetchMock);
    vi.stubGlobal('useRuntimeConfig', () => ({
      public: { apiBase: 'http://api.localhost:8080' },
    }));

    const api = usePublicActivities();
    await api.feed({
      cursor: 'cursor-1',
      limit: 20,
      category_id: 'cat-1',
      search: 'футбол',
      sort_by: 'created_at',
      sort_dir: 'asc',
    });

    expect(fetchMock).toHaveBeenCalledWith('/api/activities/feed', {
      method: 'GET',
      query: {
        cursor: 'cursor-1',
        limit: 20,
        category_id: 'cat-1',
        search: 'футбол',
        sort_by: 'created_at',
        sort_dir: 'asc',
      },
      signal: undefined,
    });
  });
});

describe('buildPublicActivityPath', () => {
  it('builds nested category path from public route contract', () => {
    expect(
      buildPublicActivityPath({
        id: 'uuid-1',
        slug: 'futbol',
        primary_category: {
          id: 'leaf-1',
          name: 'Футбол',
          slug: 'futbol',
          parent: {
            id: 'root-1',
            name: 'Спорт',
            slug: 'sport',
          },
        },
      })
    ).toBe('/sport/futbol/futbol-uuid-1');
  });

  it('uses provided public_key when it is already available', () => {
    expect(
      buildPublicActivityPath({
        public_key: 'sekciya-uuid-2',
      })
    ).toBe('/activities/sekciya-uuid-2');
  });

  it('prefers nested canonical path when public_key and category tree are available', () => {
    expect(
      buildPublicActivityPath({
        public_key: 'futbol-uuid-1',
        primary_category: {
          id: 'leaf-1',
          name: 'Футбол',
          slug: 'futbol',
          parent: {
            id: 'root-1',
            name: 'Спорт',
            slug: 'sport',
          },
        },
      })
    ).toBe('/sport/futbol/futbol-uuid-1');
  });
});
