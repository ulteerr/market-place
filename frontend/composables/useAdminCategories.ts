import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export interface AdminCategoryParent {
  id: string;
  name: string;
  slug: string;
}

export interface AdminCategory {
  id: string;
  name: string;
  slug: string;
  parent_id: string | null;
  sort_order: number;
  is_active: boolean;
  children_count?: number | null;
  activities_count?: number | null;
  created_at?: string | null;
  updated_at?: string | null;
  parent?: AdminCategoryParent | null;
  children?: AdminCategory[];
}

interface CategoryMutationResponse {
  status: string;
  message?: string;
  data?: AdminCategory;
}

interface CategoryShowResponse {
  status: string;
  data: AdminCategory;
}

interface CategoryTreeResponse {
  status: string;
  data: AdminCategory[];
}

interface CategorySlugPreviewResponse {
  status: string;
  data: {
    slug: string;
  };
}

export interface CreateCategoryPayload {
  name: string;
  slug?: string | null;
  parent_id?: string | null;
  sort_order?: number;
  is_active?: boolean;
}

export interface UpdateCategoryPayload {
  name?: string;
  slug?: string | null;
  parent_id?: string | null;
  sort_order?: number;
  is_active?: boolean;
}

export interface AdminCategoriesListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
  parent_id?: string;
  is_active?: boolean | '';
}

export const useAdminCategories = () => {
  const api = useApi();

  const list = async (
    params: AdminCategoriesListParams = {}
  ): Promise<PaginationPayload<AdminCategory>> => {
    const response = await api<IndexResponse<AdminCategory>>('/api/admin/categories', {
      query: params,
    });

    return response.data;
  };

  const show = async (id: string): Promise<AdminCategory> => {
    const response = await api<CategoryShowResponse>(`/api/admin/categories/${id}`);

    return response.data;
  };

  const tree = async (onlyActive = false): Promise<AdminCategory[]> => {
    const response = await api<CategoryTreeResponse>('/api/admin/categories/tree', {
      query: {
        only_active: onlyActive,
      },
    });

    return response.data;
  };

  const previewSlug = async (params: {
    name: string;
    parent_id?: string | null;
    ignore_id?: string | null;
  }): Promise<string> => {
    const response = await api<CategorySlugPreviewResponse>('/api/admin/categories/slug-preview', {
      query: {
        name: params.name,
        parent_id: params.parent_id || undefined,
        ignore_id: params.ignore_id || undefined,
      },
    });

    return response.data.slug;
  };

  const create = async (payload: CreateCategoryPayload): Promise<void> => {
    await api<CategoryMutationResponse>('/api/admin/categories', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateCategoryPayload): Promise<void> => {
    await api<CategoryMutationResponse>(`/api/admin/categories/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/categories/${id}`, {
      method: 'DELETE',
    });
  };

  return {
    list,
    show,
    tree,
    previewSlug,
    create,
    update,
    remove,
  };
};
