import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export interface AdminRole {
  id: string;
  code: string;
  label: string | null;
  is_system: boolean;
  permissions?: string[];
}

interface RoleShowResponse {
  status: string;
  role: AdminRole;
}

interface RoleMutationResponse {
  status: string;
  message?: string;
  data?: AdminRole;
}

export interface CreateRolePayload {
  code: string;
  label?: string | null;
  permissions?: string[];
}

export interface UpdateRolePayload {
  code?: string;
  label?: string | null;
  permissions?: string[];
}

export interface AdminRolesListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
}

export const useAdminRoles = () => {
  const api = useApi();

  const list = async (params: AdminRolesListParams = {}): Promise<PaginationPayload<AdminRole>> => {
    const response = await api<IndexResponse<AdminRole>>('/api/admin/roles', {
      query: params,
    });

    return response.data;
  };

  const show = async (id: string): Promise<AdminRole> => {
    const response = await api<RoleShowResponse>(`/api/admin/roles/${id}`);

    return response.role;
  };

  const create = async (payload: CreateRolePayload): Promise<void> => {
    await api<RoleMutationResponse>('/api/admin/roles', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateRolePayload): Promise<void> => {
    await api<RoleMutationResponse>(`/api/admin/roles/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/roles/${id}`, {
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
