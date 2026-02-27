import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

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

interface MetroStationShowResponse {
  status: string;
  data: AdminMetroStation;
}

interface MetroStationMutationResponse {
  status: string;
  message?: string;
  data?: AdminMetroStation;
}

export interface CreateMetroStationPayload {
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

export interface UpdateMetroStationPayload {
  name?: string;
  external_id?: string | null;
  line_id?: string | null;
  geo_lat?: number | null;
  geo_lon?: number | null;
  is_closed?: boolean | null;
  metro_line_id?: string;
  city_id?: string;
  source?: string;
}

export interface AdminMetroStationsListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
  city_id?: string;
  metro_line_id?: string;
}

export const useAdminMetroStations = () => {
  const api = useApi();

  const list = async (
    params: AdminMetroStationsListParams = {}
  ): Promise<PaginationPayload<AdminMetroStation>> => {
    const rawSearch =
      typeof params.search === 'string' && params.search.trim().length > 0
        ? params.search.trim()
        : undefined;

    const queryBase = {
      ...params,
      search: rawSearch,
    };

    const response = await api<IndexResponse<AdminMetroStation>>('/api/admin/geo/metro-stations', {
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
      const fallbackResponse = await api<IndexResponse<AdminMetroStation>>(
        '/api/admin/geo/metro-stations',
        {
          query: {
            ...params,
            search: fallbackSearch,
          },
        }
      );

      if (fallbackResponse.data.total > 0) {
        return fallbackResponse.data;
      }
    }

    return response.data;
  };

  const show = async (id: string): Promise<AdminMetroStation> => {
    const response = await api<MetroStationShowResponse>(`/api/admin/geo/metro-stations/${id}`);

    return response.data;
  };

  const create = async (payload: CreateMetroStationPayload): Promise<void> => {
    await api<MetroStationMutationResponse>('/api/admin/geo/metro-stations', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateMetroStationPayload): Promise<void> => {
    await api<MetroStationMutationResponse>(`/api/admin/geo/metro-stations/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/geo/metro-stations/${id}`, {
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
