import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export interface AdminGeoRegion {
  id: string;
  name: string;
  country_id: string;
}

interface GeoRegionShowResponse {
  status: string;
  data: AdminGeoRegion;
}

interface GeoRegionMutationResponse {
  status: string;
  message?: string;
  data?: AdminGeoRegion;
}

export interface CreateGeoRegionPayload {
  name: string;
  country_id: string;
}

export interface UpdateGeoRegionPayload {
  name?: string;
  country_id?: string;
}

export interface AdminGeoRegionsListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
  country_id?: string;
}

export const useAdminGeoRegions = () => {
  const api = useApi();

  const list = async (
    params: AdminGeoRegionsListParams = {}
  ): Promise<PaginationPayload<AdminGeoRegion>> => {
    const response = await api<IndexResponse<AdminGeoRegion>>('/api/admin/geo/regions', {
      query: params,
    });

    return response.data;
  };

  const show = async (id: string): Promise<AdminGeoRegion> => {
    const response = await api<GeoRegionShowResponse>(`/api/admin/geo/regions/${id}`);

    return response.data;
  };

  const create = async (payload: CreateGeoRegionPayload): Promise<void> => {
    await api<GeoRegionMutationResponse>('/api/admin/geo/regions', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateGeoRegionPayload): Promise<void> => {
    await api<GeoRegionMutationResponse>(`/api/admin/geo/regions/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/geo/regions/${id}`, {
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
