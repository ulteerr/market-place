import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export type OrganizationStatus = 'draft' | 'active' | 'suspended' | 'archived';
export type OrganizationSourceType = 'manual' | 'import' | 'parsed' | 'self_registered';
export type OrganizationOwnershipStatus = 'unclaimed' | 'pending_claim' | 'claimed';

export interface AdminOrganization {
  id: string;
  name: string;
  description?: string | null;
  address?: string | null;
  phone?: string | null;
  email?: string | null;
  status?: OrganizationStatus | null;
  source_type?: OrganizationSourceType | null;
  ownership_status?: OrganizationOwnershipStatus | null;
  owner_user_id?: string | null;
  created_by_user_id?: string | null;
  claimed_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  owner?: {
    id: string;
    first_name?: string | null;
    last_name?: string | null;
    middle_name?: string | null;
    email?: string | null;
  } | null;
}

interface OrganizationMutationResponse {
  status: string;
  message?: string;
  data?: AdminOrganization;
}

interface OrganizationShowResponse {
  status: string;
  data: AdminOrganization;
}

export interface AdminOrganizationsListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
}

export interface CreateOrganizationPayload {
  name: string;
  description?: string | null;
  address?: string | null;
  phone?: string | null;
  email?: string | null;
  status?: OrganizationStatus | null;
  source_type?: OrganizationSourceType | null;
  ownership_status?: OrganizationOwnershipStatus | null;
  owner_user_id?: string | null;
}

export interface UpdateOrganizationPayload {
  name?: string;
  description?: string | null;
  address?: string | null;
  phone?: string | null;
  email?: string | null;
  status?: OrganizationStatus | null;
  source_type?: OrganizationSourceType | null;
  ownership_status?: OrganizationOwnershipStatus | null;
  owner_user_id?: string | null;
}

export const getAdminOrganizationOwnerName = (organization: AdminOrganization): string => {
  const owner = organization.owner;

  if (!owner) {
    return organization.owner_user_id || '';
  }

  const fullName = [owner.last_name, owner.first_name, owner.middle_name]
    .filter((part): part is string => typeof part === 'string' && part.trim().length > 0)
    .join(' ');

  return fullName || owner.email || owner.id;
};

export const useAdminOrganizations = () => {
  const api = useApi();

  const list = async (
    params: AdminOrganizationsListParams = {}
  ): Promise<PaginationPayload<AdminOrganization>> => {
    const response = await api<IndexResponse<AdminOrganization>>('/api/admin/organizations', {
      query: params,
    });

    return response.data;
  };

  const show = async (id: string): Promise<AdminOrganization> => {
    const response = await api<OrganizationShowResponse>(`/api/admin/organizations/${id}`);

    return response.data;
  };

  const create = async (payload: CreateOrganizationPayload): Promise<void> => {
    await api<OrganizationMutationResponse>('/api/admin/organizations', {
      method: 'POST',
      body: payload,
    });
  };

  const update = async (id: string, payload: UpdateOrganizationPayload): Promise<void> => {
    await api<OrganizationMutationResponse>(`/api/admin/organizations/${id}`, {
      method: 'PATCH',
      body: payload,
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/organizations/${id}`, {
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
