import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export interface AdminGeoDistrict {
  id: string;
  name: string;
  city_id: string;
}

interface GeoDistrictShowResponse {
  status: string;
  data: AdminGeoDistrict;
}

interface GeoDistrictMutationResponse {
  status: string;
  message?: string;
  data?: AdminGeoDistrict;
}

export interface CreateGeoDistrictPayload {
  name: string;
  city_id: string;
}

export interface UpdateGeoDistrictPayload {
  name?: string;
  city_id?: string;
}

export interface AdminGeoDistrictsListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
  city_id?: string;
}

export const useAdminGeoDistricts = () => {
  const api = useApi();

  const list = async (
    params: AdminGeoDistrictsListParams = {}
  ): Promise<PaginationPayload<AdminGeoDistrict>> => {
    const response = await api<IndexResponse<AdminGeoDistrict>>('/api/admin/geo/districts', {
      query: params,
    });

    return response.data;
  };

  const show = async (id: string): Promise<AdminGeoDistrict> => {
    const response = await api<GeoDistrictShowResponse>(`/api/admin/geo/districts/${id}`);

    return response.data;
  };

  const create = async (payload: CreateGeoDistrictPayload): Promise<void> => {
    await api<GeoDistrictMutationResponse>('/api/admin/geo/districts', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateGeoDistrictPayload): Promise<void> => {
    await api<GeoDistrictMutationResponse>(`/api/admin/geo/districts/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/geo/districts/${id}`, {
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
