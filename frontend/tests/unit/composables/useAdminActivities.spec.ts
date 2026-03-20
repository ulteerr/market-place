import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useAdminActivities } from '~/composables/useAdminActivities';

describe('useAdminActivities', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('lists activities and normalizes cover/gallery asset urls', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: {
        data: [
          {
            id: 'act-1',
            organization_id: 'org-1',
            location_id: 'loc-1',
            name: 'Футбол',
            slug: 'futbol',
            short_description: 'Короткое описание',
            status: 'draft',
            is_featured: false,
            cover: {
              id: 'cover-1',
              url: '/storage/activities/cover.jpg',
              original_name: 'cover.jpg',
              mime_type: 'image/jpeg',
              size: 123,
              collection: 'cover',
            },
            gallery: [
              {
                id: 'gallery-1',
                url: '/storage/activities/gallery.jpg',
                original_name: 'gallery.jpg',
                mime_type: 'image/jpeg',
                size: 456,
                collection: 'gallery',
              },
            ],
          },
        ],
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 1,
      },
    });

    vi.stubGlobal('useApi', () => fetchMock);
    vi.stubGlobal('useRuntimeConfig', () => ({
      public: { apiBase: 'http://api.localhost:8080' },
    }));

    const api = useAdminActivities();
    const payload = await api.list({ search: 'футбол', status: 'draft' });

    expect(fetchMock).toHaveBeenCalledWith('/api/admin/activities', {
      query: {
        search: 'футбол',
        status: 'draft',
      },
      signal: undefined,
    });
    expect(payload.data[0]?.cover?.url).toBe(
      'http://api.localhost:8080/storage/activities/cover.jpg'
    );
    expect(payload.data[0]?.gallery?.[0]?.url).toBe(
      'http://api.localhost:8080/storage/activities/gallery.jpg'
    );
  });

  it('requests slug preview with ignore_id', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: { slug: 'futbol-2' },
    });

    vi.stubGlobal('useApi', () => fetchMock);
    vi.stubGlobal('useRuntimeConfig', () => ({
      public: { apiBase: 'http://api.localhost:8080' },
    }));

    const api = useAdminActivities();
    const slug = await api.previewSlug({
      name: 'Футбол',
      ignore_id: 'act-1',
    });

    expect(fetchMock).toHaveBeenCalledWith('/api/admin/activities/slug-preview', {
      query: {
        name: 'Футбол',
        ignore_id: 'act-1',
      },
    });
    expect(slug).toBe('futbol-2');
  });

  it('creates multipart payload with normalized booleans, schedules and files', async () => {
    const fetchMock = vi.fn().mockResolvedValue({ status: 'ok', data: { id: 'act-1' } });

    vi.stubGlobal('useApi', () => fetchMock);
    vi.stubGlobal('useRuntimeConfig', () => ({
      public: { apiBase: 'http://api.localhost:8080' },
    }));

    const api = useAdminActivities();
    const cover = new File(['cover'], 'cover.jpg', { type: 'image/jpeg' });
    const galleryA = new File(['gallery-a'], 'gallery-a.jpg', { type: 'image/jpeg' });
    const galleryB = new File(['gallery-b'], 'gallery-b.webp', { type: 'image/webp' });

    await api.create({
      organization_id: 'org-1',
      location_id: 'loc-1',
      category_id: 'cat-leaf-football',
      name: 'Футбол',
      slug: 'futbol',
      short_description: 'Короткое описание',
      status: 'published',
      is_featured: true,
      schedules: [
        {
          day_of_week: 1,
          start_time: '17:00:00',
          end_time: '18:30:00',
        },
      ],
      cover,
      gallery: [galleryA, galleryB],
    });

    const [, options] = fetchMock.mock.calls[0] as [string, { method: string; body: FormData }];

    expect(fetchMock).toHaveBeenCalledWith(
      '/api/admin/activities',
      expect.objectContaining({
        method: 'POST',
        body: expect.any(FormData),
      })
    );
    expect(options.body.get('organization_id')).toBe('org-1');
    expect(options.body.get('location_id')).toBe('loc-1');
    expect(options.body.get('category_id')).toBe('cat-leaf-football');
    expect(options.body.get('name')).toBe('Футбол');
    expect(options.body.get('slug')).toBe('futbol');
    expect(options.body.get('short_description')).toBe('Короткое описание');
    expect(options.body.get('status')).toBe('published');
    expect(options.body.get('is_featured')).toBe('1');
    expect(options.body.get('schedules[0][day_of_week]')).toBe('1');
    expect(options.body.get('schedules[0][start_time]')).toBe('17:00:00');
    expect(options.body.get('schedules[0][end_time]')).toBe('18:30:00');
    expect(options.body.get('cover')).toBe(cover);
    expect(options.body.getAll('gallery[]')).toEqual([galleryA, galleryB]);
  });

  it('updates multipart payload with delete and order arrays', async () => {
    const fetchMock = vi.fn().mockResolvedValue({ status: 'ok', data: { id: 'act-1' } });

    vi.stubGlobal('useApi', () => fetchMock);
    vi.stubGlobal('useRuntimeConfig', () => ({
      public: { apiBase: 'http://api.localhost:8080' },
    }));

    const api = useAdminActivities();

    await api.update('act-1', {
      slug: 'custom-slug',
      cover_delete: true,
      gallery_delete_ids: ['gallery-1', 'gallery-2'],
      gallery_order_ids: ['gallery-2', 'gallery-1'],
      is_featured: false,
    });

    const [, options] = fetchMock.mock.calls[0] as [string, { method: string; body: FormData }];

    expect(fetchMock).toHaveBeenCalledWith(
      '/api/admin/activities/act-1',
      expect.objectContaining({
        method: 'PATCH',
        body: expect.any(FormData),
      })
    );
    expect(options.body.get('slug')).toBe('custom-slug');
    expect(options.body.get('cover_delete')).toBe('1');
    expect(options.body.get('is_featured')).toBe('0');
    expect(options.body.getAll('gallery_delete_ids[]')).toEqual(['gallery-1', 'gallery-2']);
    expect(options.body.getAll('gallery_order_ids[]')).toEqual(['gallery-2', 'gallery-1']);
  });
});
