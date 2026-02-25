import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export interface AdminChild {
  id: string;
  user_id: string;
  first_name: string;
  last_name: string;
  middle_name: string | null;
  gender: 'male' | 'female' | null;
  user?: {
    id: string;
    full_name?: string | null;
    email?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    middle_name?: string | null;
  } | null;
  birth_date: string | null;
  created_at?: string;
  updated_at?: string;
}

interface ChildMutationResponse {
  status: string;
  message?: string;
  data?: AdminChild;
}

interface ChildShowResponse {
  status: string;
  data: AdminChild;
}

export interface CreateChildPayload {
  user_id: string;
  first_name: string;
  last_name: string;
  middle_name?: string | null;
  gender?: 'male' | 'female' | null;
  birth_date?: string | null;
}

export interface UpdateChildPayload {
  user_id?: string;
  first_name?: string;
  last_name?: string;
  middle_name?: string | null;
  gender?: 'male' | 'female' | null;
  birth_date?: string | null;
}

export interface AdminChildrenListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
}

export const useAdminChildren = () => {
  const api = useApi();

  const list = async (
    params: AdminChildrenListParams = {}
  ): Promise<PaginationPayload<AdminChild>> => {
    const response = await api<IndexResponse<AdminChild>>('/api/admin/children', {
      query: params,
    });

    return response.data;
  };

  const show = async (id: string): Promise<AdminChild> => {
    const response = await api<ChildShowResponse>(`/api/admin/children/${id}`);

    return response.data;
  };

  const create = async (payload: CreateChildPayload): Promise<void> => {
    await api<ChildMutationResponse>('/api/admin/children', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateChildPayload): Promise<void> => {
    await api<ChildMutationResponse>(`/api/admin/children/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/children/${id}`, {
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
