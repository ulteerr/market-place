import type { PublicCitySetting, PublicCitySource } from '~/composables/user-settings/types';

export interface PublicGeoCity {
  id: string;
  name: string;
  country_id: string;
  region_id?: string | null;
  country?: {
    name?: string | null;
  } | null;
  region?: {
    name?: string | null;
  } | null;
}

export interface PublicGeoCityListParams {
  search?: string;
  limit?: number;
}

export interface PublicGeoCityDetectionPayload {
  city: PublicGeoCity;
  resolved_by: 'ip' | 'fallback';
}

interface PublicApiResponse<T> {
  status: string;
  data: T;
}

export const toPublicCitySetting = (
  city: PublicGeoCity,
  source: PublicCitySource
): PublicCitySetting => ({
  city_id: city.id,
  city_name: city.name,
  source,
  region_id: city.region_id ?? null,
  region_name: city.region?.name ?? null,
  country_id: city.country_id,
  country_name: city.country?.name ?? '',
});

export const usePublicGeoCities = () => {
  const api = useApi();

  const list = async (
    params: PublicGeoCityListParams = {},
    context?: { signal?: AbortSignal }
  ): Promise<PublicGeoCity[]> => {
    const response = await api<PublicApiResponse<PublicGeoCity[]>>('/api/public/geo/cities', {
      method: 'GET',
      query: {
        search: params.search?.trim() || undefined,
        limit: params.limit,
      },
      signal: context?.signal,
    });

    return response.data;
  };

  const detect = async (context?: {
    signal?: AbortSignal;
  }): Promise<PublicGeoCityDetectionPayload> => {
    const response = await api<PublicApiResponse<PublicGeoCityDetectionPayload>>(
      '/api/public/geo/detect-city',
      {
        method: 'GET',
        signal: context?.signal,
      }
    );

    return response.data;
  };

  return {
    list,
    detect,
  };
};
