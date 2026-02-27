import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export interface AdminGeoCity {
  id: string;
  name: string;
  country_id?: string | null;
  region_id?: string | null;
}

interface GeoCityShowResponse {
  status: string;
  data: AdminGeoCity;
}

interface GeoCityMutationResponse {
  status: string;
  message?: string;
  data?: AdminGeoCity;
}

export interface CreateGeoCityPayload {
  name: string;
  country_id?: string | null;
  region_id?: string | null;
}

export interface UpdateGeoCityPayload {
  name?: string;
  country_id?: string | null;
  region_id?: string | null;
}

export interface AdminGeoCitiesListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
  country_id?: string;
  region_id?: string;
}

export const useAdminGeoCities = () => {
  const api = useApi();

  const list = async (
    params: AdminGeoCitiesListParams = {}
  ): Promise<PaginationPayload<AdminGeoCity>> => {
    const response = await api<IndexResponse<AdminGeoCity>>('/api/admin/geo/cities', {
      query: params,
    });

    return response.data;
  };

  const show = async (id: string): Promise<AdminGeoCity> => {
    const response = await api<GeoCityShowResponse>(`/api/admin/geo/cities/${id}`);

    return response.data;
  };

  const create = async (payload: CreateGeoCityPayload): Promise<void> => {
    await api<GeoCityMutationResponse>('/api/admin/geo/cities', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateGeoCityPayload): Promise<void> => {
    await api<GeoCityMutationResponse>(`/api/admin/geo/cities/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/geo/cities/${id}`, {
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
