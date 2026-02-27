import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export interface AdminGeoCountry {
  id: string;
  name: string;
  iso_code?: string | null;
}

interface GeoCountryShowResponse {
  status: string;
  data: AdminGeoCountry;
}

interface GeoCountryMutationResponse {
  status: string;
  message?: string;
  data?: AdminGeoCountry;
}

export interface CreateGeoCountryPayload {
  name: string;
  iso_code?: string | null;
}

export interface UpdateGeoCountryPayload {
  name?: string;
  iso_code?: string | null;
}

export interface AdminGeoCountriesListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
}

export const useAdminGeoCountries = () => {
  const api = useApi();

  const list = async (
    params: AdminGeoCountriesListParams = {}
  ): Promise<PaginationPayload<AdminGeoCountry>> => {
    const response = await api<IndexResponse<AdminGeoCountry>>('/api/admin/geo/countries', {
      query: params,
    });

    return response.data;
  };

  const show = async (id: string): Promise<AdminGeoCountry> => {
    const response = await api<GeoCountryShowResponse>(`/api/admin/geo/countries/${id}`);

    return response.data;
  };

  const create = async (payload: CreateGeoCountryPayload): Promise<void> => {
    await api<GeoCountryMutationResponse>('/api/admin/geo/countries', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateGeoCountryPayload): Promise<void> => {
    await api<GeoCountryMutationResponse>(`/api/admin/geo/countries/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/geo/countries/${id}`, {
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
