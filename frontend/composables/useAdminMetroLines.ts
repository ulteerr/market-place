import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export interface AdminMetroLine {
  id: string;
  name: string;
  external_id?: string | null;
  line_id?: string | null;
  color?: string | null;
  city_id: string;
  source: string;
}

interface MetroLineShowResponse {
  status: string;
  data: AdminMetroLine;
}

interface MetroLineMutationResponse {
  status: string;
  message?: string;
  data?: AdminMetroLine;
}

export interface CreateMetroLinePayload {
  name: string;
  external_id?: string | null;
  line_id?: string | null;
  color?: string | null;
  city_id: string;
  source: string;
}

export interface UpdateMetroLinePayload {
  name?: string;
  external_id?: string | null;
  line_id?: string | null;
  color?: string | null;
  city_id?: string;
  source?: string;
}

export interface AdminMetroLinesListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
  city_id?: string;
}

export const useAdminMetroLines = () => {
  const api = useApi();

  const list = async (
    params: AdminMetroLinesListParams = {}
  ): Promise<PaginationPayload<AdminMetroLine>> => {
    const rawSearch =
      typeof params.search === 'string' && params.search.trim().length > 0
        ? params.search.trim()
        : undefined;

    const queryBase = {
      ...params,
      search: rawSearch,
    };

    const response = await api<IndexResponse<AdminMetroLine>>('/api/admin/metro-lines', {
      query: queryBase,
    });

    if (!rawSearch || response.data.total > 0) {
      return response.data;
    }

    // Backend search may be case-sensitive in some environments.
    const titleCaseSearch = rawSearch.charAt(0).toLocaleUpperCase() + rawSearch.slice(1);
    const lowerCaseSearch = rawSearch.toLocaleLowerCase();
    const fallbackCandidates = [titleCaseSearch, lowerCaseSearch].filter(
      (candidate, index, list) => candidate !== rawSearch && list.indexOf(candidate) === index
    );

    for (const fallbackSearch of fallbackCandidates) {
      const fallbackResponse = await api<IndexResponse<AdminMetroLine>>('/api/admin/metro-lines', {
        query: {
          ...params,
          search: fallbackSearch,
        },
      });

      if (fallbackResponse.data.total > 0) {
        return fallbackResponse.data;
      }
    }

    return response.data;
  };

  const show = async (id: string): Promise<AdminMetroLine> => {
    const response = await api<MetroLineShowResponse>(`/api/admin/metro-lines/${id}`);

    return response.data;
  };

  const create = async (payload: CreateMetroLinePayload): Promise<void> => {
    await api<MetroLineMutationResponse>('/api/admin/metro-lines', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateMetroLinePayload): Promise<void> => {
    await api<MetroLineMutationResponse>(`/api/admin/metro-lines/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/metro-lines/${id}`, {
      method: 'DELETE',
    });
  };

  return {
    list,
    show,
    create,
    update,
    remove,
  };
};
