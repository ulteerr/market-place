import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export type OrganizationMemberStatus = 'active' | 'invited' | 'blocked';

export interface OrganizationMember {
  id: string;
  organization_id: string;
  user_id: string;
  position?: string | null;
  status: OrganizationMemberStatus;
  joined_at?: string | null;
  user?: {
    id: string;
    first_name?: string | null;
    last_name?: string | null;
    middle_name?: string | null;
    email?: string | null;
  } | null;
}

export interface OrganizationMembersListParams {
  page?: number;
  per_page?: number;
  status?: OrganizationMemberStatus | '';
  search?: string;
  sort_by?: 'created_at' | 'joined_at' | 'position' | 'status' | 'id';
  sort_dir?: SortDirection;
}

export const resolveOrganizationMemberLabel = (member: OrganizationMember): string => {
  const user = member.user;
  if (!user) {
    return member.user_id;
  }

  const fullName = [user.last_name, user.first_name, user.middle_name]
    .filter((part): part is string => typeof part === 'string' && part.trim().length > 0)
    .join(' ');

  return fullName || user.email || user.id;
};

export const useOrganizationMembers = () => {
  const api = useApi();

  const list = async (
    organizationId: string,
    params: OrganizationMembersListParams = {}
  ): Promise<PaginationPayload<OrganizationMember>> => {
    const response = await api<IndexResponse<OrganizationMember>>(
      `/api/organizations/${organizationId}/users`,
      {
        query: params,
      }
    );

    return response.data;
  };

  return { list };
};
