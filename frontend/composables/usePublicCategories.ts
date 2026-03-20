export interface PublicCategoryNode {
  id: string;
  name: string;
  slug: string;
  parent_id?: string | null;
  sort_order?: number;
  is_active?: boolean;
  children?: PublicCategoryNode[];
}

interface PublicCategoriesResponse {
  status: string;
  data: PublicCategoryNode[];
}

export interface PublicResolvedCategory {
  root: PublicCategoryNode;
  leaf: PublicCategoryNode | null;
}

export const buildPublicRootCategoryPath = (root: Pick<PublicCategoryNode, 'slug'>): string =>
  `/catalog/${root.slug}`;

export const buildPublicLeafCategoryPath = (
  root: Pick<PublicCategoryNode, 'slug'>,
  leaf: Pick<PublicCategoryNode, 'slug'>
): string => `/catalog/${root.slug}/${leaf.slug}`;

export const findPublicCategoryBySlugs = (
  tree: PublicCategoryNode[],
  rootSlug: string,
  leafSlug?: string
): PublicResolvedCategory | null => {
  const root = tree.find((category) => category.slug === rootSlug) ?? null;
  if (!root) {
    return null;
  }

  if (!leafSlug) {
    return {
      root,
      leaf: null,
    };
  }

  const leaf = (root.children ?? []).find((category) => category.slug === leafSlug) ?? null;
  if (!leaf) {
    return null;
  }

  return {
    root,
    leaf,
  };
};

export const usePublicCategories = () => {
  const api = useApi();

  const tree = async (onlyActive = true): Promise<PublicCategoryNode[]> => {
    const response = await api<PublicCategoriesResponse>('/api/categories/tree', {
      method: 'GET',
      query: {
        only_active: onlyActive,
      },
    });

    return response.data;
  };

  return {
    tree,
  };
};
