import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useAdminCategories } from '~/composables/useAdminCategories';

describe('useAdminCategories', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('requests admin categories tree with explicit activity flag', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: [],
    });

    vi.stubGlobal('useApi', () => fetchMock);

    const api = useAdminCategories();
    await api.tree(false);

    expect(fetchMock).toHaveBeenCalledWith('/api/admin/categories/tree', {
      query: {
        only_active: false,
      },
    });
  });

  it('requests slug preview with parent and ignore identifiers', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: {
        slug: 'futbol-2',
      },
    });

    vi.stubGlobal('useApi', () => fetchMock);

    const api = useAdminCategories();
    const slug = await api.previewSlug({
      name: 'Футбол',
      parent_id: 'cat-root-sport',
      ignore_id: 'cat-leaf-football',
    });

    expect(fetchMock).toHaveBeenCalledWith('/api/admin/categories/slug-preview', {
      query: {
        name: 'Футбол',
        parent_id: 'cat-root-sport',
        ignore_id: 'cat-leaf-football',
      },
    });
    expect(slug).toBe('futbol-2');
  });
});
