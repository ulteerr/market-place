import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
  buildPublicLeafCategoryPath,
  buildPublicRootCategoryPath,
  findPublicCategoryBySlugs,
  usePublicCategories,
} from '~/composables/usePublicCategories';

describe('usePublicCategories', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('requests public categories tree with active filter by default', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: [],
    });

    vi.stubGlobal('useApi', () => fetchMock);

    const api = usePublicCategories();
    await api.tree();

    expect(fetchMock).toHaveBeenCalledWith('/api/categories/tree', {
      method: 'GET',
      query: {
        only_active: true,
      },
    });
  });

  it('builds public category paths', () => {
    expect(buildPublicRootCategoryPath({ slug: 'sport' })).toBe('/catalog/sport');
    expect(buildPublicLeafCategoryPath({ slug: 'sport' }, { slug: 'futbol' })).toBe(
      '/catalog/sport/futbol'
    );
  });

  it('finds root and leaf categories by slugs', () => {
    const tree = [
      {
        id: 'cat-root-sport',
        name: 'Спорт',
        slug: 'sport',
        children: [
          {
            id: 'cat-leaf-football',
            name: 'Футбол',
            slug: 'futbol',
            children: [],
          },
        ],
      },
    ];

    expect(findPublicCategoryBySlugs(tree, 'sport')).toEqual({
      root: tree[0],
      leaf: null,
    });
    expect(findPublicCategoryBySlugs(tree, 'sport', 'futbol')).toEqual({
      root: tree[0],
      leaf: tree[0].children?.[0] ?? null,
    });
    expect(findPublicCategoryBySlugs(tree, 'music')).toBeNull();
    expect(findPublicCategoryBySlugs(tree, 'sport', 'vokal')).toBeNull();
  });
});
