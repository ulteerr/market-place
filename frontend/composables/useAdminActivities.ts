import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';
import { resolveAssetUrl } from '~/composables/asset-url';

export interface AdminActivityRelation {
  id: string;
  name: string;
}

export interface AdminActivityLocation {
  id: string;
  organization_id: string;
  city_id: string | null;
  address: string;
  city?: AdminActivityRelation | null;
}

export interface AdminActivityCategory extends AdminActivityRelation {
  slug: string;
  parent_id: string | null;
  parent?: AdminActivityRelation | null;
}

export interface AdminActivitySchedule {
  id: string;
  day_of_week: number;
  start_time: string;
  end_time: string;
}

export interface AdminActivityScheduleInput {
  day_of_week: number;
  start_time: string;
  end_time: string;
}

export interface AdminActivityFile {
  id: string;
  url: string;
  original_name: string;
  mime_type: string;
  size: number;
  collection: string;
}

export interface AdminActivity {
  id: string;
  organization_id: string;
  location_id: string;
  name: string;
  slug: string;
  short_description: string;
  description?: string | null;
  min_age?: number | null;
  max_age?: number | null;
  capacity?: number | null;
  price_from?: number | null;
  price_to?: number | null;
  currency?: string | null;
  status: string;
  is_featured: boolean;
  published_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  organization?: AdminActivityRelation | null;
  location?: AdminActivityLocation | null;
  primary_category?: AdminActivityCategory | null;
  schedules?: AdminActivitySchedule[];
  cover?: AdminActivityFile | null;
  gallery?: AdminActivityFile[];
}

interface ActivityMutationResponse {
  status: string;
  message?: string;
  data?: AdminActivity;
}

interface ActivityShowResponse {
  status: string;
  data: AdminActivity;
}

interface ActivitySlugPreviewResponse {
  status: string;
  data: {
    slug: string;
  };
}

export interface AdminActivitiesListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
  status?: string;
  is_featured?: boolean | '';
  organization_id?: string;
  location_id?: string;
  category_id?: string;
}

export interface CreateActivityPayload {
  organization_id: string;
  location_id: string;
  category_id: string;
  name: string;
  slug: string;
  short_description: string;
  description?: string | null;
  min_age?: number | null;
  max_age?: number | null;
  capacity?: number | null;
  price_from?: number | null;
  price_to?: number | null;
  currency?: string | null;
  status?: string | null;
  is_featured?: boolean;
  published_at?: string | null;
  schedules?: AdminActivityScheduleInput[];
  cover?: File | null;
  cover_delete?: boolean;
  gallery?: File[];
  gallery_delete_ids?: string[];
  gallery_order_ids?: string[];
}

export interface UpdateActivityPayload extends Partial<CreateActivityPayload> {}

export const useAdminActivities = () => {
  const api = useApi();
  const config = useRuntimeConfig();

  const normalizeFile = (file: AdminActivityFile): AdminActivityFile => {
    const url = resolveAssetUrl(config.public.apiBase, file.url);

    return {
      ...file,
      url: url || file.url,
    };
  };

  const normalizeActivityAssets = (activity: AdminActivity): AdminActivity => ({
    ...activity,
    cover: activity.cover ? normalizeFile(activity.cover) : null,
    gallery: Array.isArray(activity.gallery) ? activity.gallery.map(normalizeFile) : [],
  });

  const buildMutationBody = (payload: CreateActivityPayload | UpdateActivityPayload): FormData => {
    const body = new FormData();

    Object.entries(payload).forEach(([key, value]) => {
      if (value === undefined || value === null) {
        return;
      }

      if (key === 'gallery' && Array.isArray(value)) {
        value.forEach((file) => {
          if (file instanceof File) {
            body.append('gallery[]', file);
          }
        });
        return;
      }

      if ((key === 'gallery_delete_ids' || key === 'gallery_order_ids') && Array.isArray(value)) {
        value.forEach((item) => {
          body.append(`${key}[]`, String(item));
        });
        return;
      }

      if (key === 'schedules' && Array.isArray(value)) {
        value.forEach((schedule, index) => {
          body.append(`schedules[${index}][day_of_week]`, String(schedule.day_of_week));
          body.append(`schedules[${index}][start_time]`, schedule.start_time);
          body.append(`schedules[${index}][end_time]`, schedule.end_time);
        });
        return;
      }

      if (key === 'cover' && value instanceof File) {
        body.append('cover', value);
        return;
      }

      if (typeof value === 'boolean') {
        body.append(key, value ? '1' : '0');
        return;
      }

      body.append(key, String(value));
    });

    return body;
  };

  const list = async (
    params: AdminActivitiesListParams = {},
    context?: { signal?: AbortSignal }
  ): Promise<PaginationPayload<AdminActivity>> => {
    const response = await api<IndexResponse<AdminActivity>>('/api/admin/activities', {
      query: params,
      signal: context?.signal,
    });

    return {
      ...response.data,
      data: response.data.data.map(normalizeActivityAssets),
    };
  };

  const show = async (id: string): Promise<AdminActivity> => {
    const response = await api<ActivityShowResponse>(`/api/admin/activities/${id}`);

    return normalizeActivityAssets(response.data);
  };

  const previewSlug = async (params: {
    name: string;
    ignore_id?: string | null;
  }): Promise<string> => {
    const response = await api<ActivitySlugPreviewResponse>('/api/admin/activities/slug-preview', {
      query: {
        name: params.name,
        ignore_id: params.ignore_id || undefined,
      },
    });

    return response.data.slug;
  };

  const create = async (payload: CreateActivityPayload): Promise<void> => {
    await api<ActivityMutationResponse>('/api/admin/activities', {
      method: 'POST',
      body: buildMutationBody(payload),
    });
  };

  const update = async (id: string, payload: UpdateActivityPayload): Promise<void> => {
    await api<ActivityMutationResponse>(`/api/admin/activities/${id}`, {
      method: 'PATCH',
      body: buildMutationBody(payload),
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api<ActivityMutationResponse>(`/api/admin/activities/${id}`, {
      method: 'DELETE',
    });
  };

  return {
    list,
    show,
    previewSlug,
    create,
    update,
    remove,
  };
};
