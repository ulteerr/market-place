import type { PaginationPayload, SortDirection } from '~/composables/useAdminCrudCommon';
import { resolveAssetUrl } from '~/composables/asset-url';

export interface PublicActivityRelation {
  id: string;
  name: string;
}

export interface PublicActivityCategory extends PublicActivityRelation {
  slug: string;
  parent?:
    | (PublicActivityRelation & {
        slug: string;
      })
    | null;
}

export interface PublicActivityLocation {
  id: string;
  address?: string | null;
  city?: PublicActivityRelation | null;
}

export interface PublicActivityOrganization extends PublicActivityRelation {}

export interface PublicActivityFile {
  id: string;
  url: string;
  original_name: string;
  mime_type: string;
  size: number;
  collection: string;
}

export interface PublicActivitySchedule {
  id: string;
  day_of_week: number;
  start_time: string;
  end_time: string;
}

export interface PublicActivityFeedItem {
  id: string;
  public_key: string;
  name: string;
  slug: string;
  short_description: string;
  min_age?: number | null;
  max_age?: number | null;
  price_from?: number | null;
  price_to?: number | null;
  currency?: string | null;
  is_featured: boolean;
  published_at?: string | null;
  organization?: PublicActivityOrganization | null;
  location?: PublicActivityLocation | null;
  primary_category?: PublicActivityCategory | null;
  cover?: PublicActivityFile | null;
}

export interface PublicActivity extends Omit<PublicActivityFeedItem, 'public_key'> {
  organization_id: string;
  location_id: string;
  description?: string | null;
  capacity?: number | null;
  status: string;
  created_at?: string | null;
  updated_at?: string | null;
  schedules?: PublicActivitySchedule[];
  gallery?: PublicActivityFile[];
}

export interface PublicActivitiesListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: 'created_at' | 'updated_at' | 'published_at' | 'price_from' | 'price_to' | 'name';
  sort_dir?: SortDirection;
  root_category_id?: string;
  category_id?: string;
  city_id?: string;
  organization_id?: string;
  location_id?: string;
  status?: string;
  is_featured?: boolean | '';
}

export interface PublicActivitiesFeedParams {
  limit?: number;
  cursor?: string | null;
  search?: string;
  root_category_id?: string;
  category_id?: string;
  city_id?: string;
  organization_id?: string;
  location_id?: string;
  is_featured?: boolean | '';
  sort_by?: 'created_at';
  sort_dir?: SortDirection;
}

interface PublicActivitiesResponse<T> {
  status: string;
  data: T;
}

interface PublicActivitiesFeedResponse {
  items: PublicActivityFeedItem[];
  next_cursor: string | null;
}

export const buildPublicActivityPath = (activity: {
  public_key?: string;
  id?: string;
  slug?: string;
  primary_category?: PublicActivityCategory | null;
}): string => {
  const publicKey = activity.public_key
    ? activity.public_key
    : activity.slug && activity.id
      ? `${activity.slug}-${activity.id}`
      : '';

  if (!publicKey) {
    return '/activities';
  }

  const rootSlug = activity.primary_category?.parent?.slug;
  const leafSlug = activity.primary_category?.slug;

  if (rootSlug && leafSlug) {
    return `/${rootSlug}/${leafSlug}/${publicKey}`;
  }

  if (leafSlug) {
    return `/${leafSlug}/${publicKey}`;
  }

  return `/activities/${publicKey}`;
};

export const usePublicActivities = () => {
  const api = useApi();
  const config = useRuntimeConfig();

  const normalizeFile = (file: PublicActivityFile): PublicActivityFile => {
    const url = resolveAssetUrl(config.public.apiBase, file.url);

    return {
      ...file,
      url: url || file.url,
    };
  };

  const normalizeFeedItem = (activity: PublicActivityFeedItem): PublicActivityFeedItem => ({
    ...activity,
    cover: activity.cover ? normalizeFile(activity.cover) : null,
  });

  const normalizeActivity = (activity: PublicActivity): PublicActivity => ({
    ...normalizeFeedItem(activity),
    gallery: Array.isArray(activity.gallery) ? activity.gallery.map(normalizeFile) : [],
  });

  const featured = async (limit = 12): Promise<PublicActivityFeedItem[]> => {
    const response = await api<PublicActivitiesResponse<PublicActivityFeedItem[]>>(
      '/api/activities/featured',
      {
        method: 'GET',
        query: { limit },
      }
    );

    return response.data.map(normalizeFeedItem);
  };

  const list = async (
    params: PublicActivitiesListParams = {},
    context?: { signal?: AbortSignal }
  ): Promise<PaginationPayload<PublicActivity>> => {
    const response = await api<PublicActivitiesResponse<PaginationPayload<PublicActivity>>>(
      '/api/activities',
      {
        method: 'GET',
        query: params,
        signal: context?.signal,
      }
    );

    return {
      ...response.data,
      data: response.data.data.map(normalizeActivity),
    };
  };

  const feed = async (
    params: PublicActivitiesFeedParams = {},
    context?: { signal?: AbortSignal }
  ): Promise<PublicActivitiesFeedResponse> => {
    const response = await api<PublicActivitiesResponse<PublicActivitiesFeedResponse>>(
      '/api/activities/feed',
      {
        method: 'GET',
        query: params,
        signal: context?.signal,
      }
    );

    return {
      ...response.data,
      items: response.data.items.map(normalizeFeedItem),
    };
  };

  const show = async (publicKey: string): Promise<PublicActivity> => {
    const response = await api<PublicActivitiesResponse<PublicActivity>>(
      `/api/activities/${publicKey}`,
      {
        method: 'GET',
      }
    );

    return normalizeActivity(response.data);
  };

  return {
    featured,
    list,
    feed,
    show,
  };
};
