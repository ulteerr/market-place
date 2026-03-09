import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';
import type { OrganizationJoinRequestSubjectType } from '~/composables/useOrganizationJoinRequests';

export type OrganizationClientStatus = 'active' | 'left' | 'blocked';

export interface OrganizationClientSubject {
  type: OrganizationJoinRequestSubjectType;
  id: string;
  first_name?: string | null;
  last_name?: string | null;
  middle_name?: string | null;
  email?: string | null;
  user_id?: string | null;
  label?: string | null;
}

export interface OrganizationClient {
  id: string;
  organization_id: string;
  subject_type: OrganizationJoinRequestSubjectType;
  subject_id: string;
  status: OrganizationClientStatus;
  added_by_user_id?: string | null;
  joined_at?: string | null;
  subject?: OrganizationClientSubject | null;
  added_by?: {
    id: string;
    first_name?: string | null;
    last_name?: string | null;
    middle_name?: string | null;
    email?: string | null;
    label?: string | null;
  } | null;
}

export interface OrganizationClientsListParams {
  page?: number;
  per_page?: number;
  status?: OrganizationClientStatus | '';
  subject_type?: OrganizationJoinRequestSubjectType | '';
  search?: string;
  sort_by?: 'created_at' | 'joined_at' | 'status' | 'id' | 'subject_type';
  sort_dir?: SortDirection;
}

export const resolveOrganizationClientLabel = (client: OrganizationClient): string => {
  return client.subject?.label || client.subject_id || client.id;
};

export const useOrganizationClients = () => {
  const api = useApi();

  const list = async (
    organizationId: string,
    params: OrganizationClientsListParams = {}
  ): Promise<PaginationPayload<OrganizationClient>> => {
    const response = await api<IndexResponse<OrganizationClient>>(
      `/api/organizations/${organizationId}/clients`,
      {
        query: params,
      }
    );

    return response.data;
  };

  return { list };
};
