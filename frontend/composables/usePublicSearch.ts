export interface PublicSearchSuggestEntity {
  id: string;
  type: 'category' | 'organization' | 'activity';
  label: string;
  subtitle?: string | null;
  url: string;
}

export interface PublicSearchSuggestResponse {
  queries: string[];
  entities: PublicSearchSuggestEntity[];
  recent: string[];
}

export interface PublicSearchResultFacetItem {
  id: string;
  name: string;
  slug?: string;
  hits: number;
  parent?: {
    id: string;
    name: string;
    slug: string;
  } | null;
}

export interface PublicSearchResultItem {
  id: string;
  public_key: string;
  name: string;
  slug: string;
  short_description: string;
  is_featured: boolean;
  organization?: {
    id: string;
    name: string;
  } | null;
  location?: {
    id: string;
    address?: string | null;
    city?: {
      id: string;
      name: string;
    } | null;
  } | null;
  primary_category?: {
    id: string;
    name: string;
    slug: string;
    parent?: {
      id: string;
      name: string;
      slug: string;
    } | null;
  } | null;
  cover?: {
    id: string;
    url: string;
    original_name: string;
    mime_type: string;
    size: number;
    collection: string;
  } | null;
}

interface PublicSearchApiResponse<T> {
  status: string;
  data: T;
}

interface PublicSearchResultsResponse {
  query: string;
  items: PublicSearchResultItem[];
  pagination: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  facets: {
    categories: PublicSearchResultFacetItem[];
    organizations: PublicSearchResultFacetItem[];
    cities: PublicSearchResultFacetItem[];
  };
}

export const usePublicSearch = () => {
  const api = useApi();
  const { selectedCityId } = usePublicCity();

  const resolveCityId = (cityId?: string) => {
    if (cityId !== undefined) {
      return cityId;
    }

    return selectedCityId.value || undefined;
  };

  const suggest = async (
    q: string,
    context?: { signal?: AbortSignal; limit?: number; city_id?: string }
  ): Promise<PublicSearchSuggestResponse> => {
    const response = await api<PublicSearchApiResponse<PublicSearchSuggestResponse>>(
      '/api/search/suggest',
      {
        method: 'GET',
        query: {
          q,
          limit: context?.limit ?? 8,
          city_id: resolveCityId(context?.city_id),
        },
        signal: context?.signal,
      }
    );

    return response.data;
  };

  const search = async (
    query: Record<string, string | number | boolean | undefined>,
    context?: { signal?: AbortSignal }
  ): Promise<PublicSearchResultsResponse> => {
    const response = await api<PublicSearchApiResponse<PublicSearchResultsResponse>>(
      '/api/search',
      {
        method: 'GET',
        query: {
          ...query,
          city_id: resolveCityId(typeof query.city_id === 'string' ? query.city_id : undefined),
        },
        signal: context?.signal,
      }
    );

    return response.data;
  };

  return {
    suggest,
    search,
  };
};
