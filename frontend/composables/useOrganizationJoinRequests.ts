import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export type OrganizationJoinRequestSubjectType = 'user' | 'child';
export type OrganizationJoinRequestStatus = 'pending' | 'approved' | 'rejected';

export interface OrganizationJoinRequestUserShort {
  id: string;
  first_name?: string | null;
  last_name?: string | null;
  middle_name?: string | null;
  email?: string | null;
  label?: string | null;
}

export interface OrganizationJoinRequestSubject {
  type: OrganizationJoinRequestSubjectType;
  id: string;
  first_name?: string | null;
  last_name?: string | null;
  middle_name?: string | null;
  email?: string | null;
  user_id?: string | null;
  label?: string | null;
}

export interface OrganizationJoinRequest {
  id: string;
  organization_id: string;
  subject_type: OrganizationJoinRequestSubjectType;
  subject_id: string;
  requested_by_user_id?: string | null;
  status: OrganizationJoinRequestStatus;
  message?: string | null;
  review_note?: string | null;
  reviewed_by_user_id?: string | null;
  reviewed_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  subject?: OrganizationJoinRequestSubject | null;
  requested_by?: OrganizationJoinRequestUserShort | null;
  reviewed_by?: OrganizationJoinRequestUserShort | null;
}

export interface OrganizationJoinRequestsListParams {
  page?: number;
  per_page?: number;
  status?: OrganizationJoinRequestStatus | '';
  subject_type?: OrganizationJoinRequestSubjectType | '';
  search?: string;
  sort_by?: 'created_at' | 'reviewed_at' | 'status' | 'id';
  sort_dir?: SortDirection;
}

export interface CreateOrganizationJoinRequestPayload {
  subject_type: OrganizationJoinRequestSubjectType;
  subject_id: string;
  message?: string | null;
}

export interface ReviewOrganizationJoinRequestPayload {
  review_note?: string | null;
}

interface JoinRequestMutationResponse {
  status: string;
  message?: string;
  data: OrganizationJoinRequest;
}

export const resolveOrganizationJoinRequestSubjectLabel = (
  request: OrganizationJoinRequest
): string => {
  return request.subject?.label || request.requested_by?.label || request.subject_id || request.id;
};

export const useOrganizationJoinRequests = () => {
  const api = useApi();

  const submit = async (
    organizationId: string,
    payload: CreateOrganizationJoinRequestPayload
  ): Promise<OrganizationJoinRequest> => {
    const response = await api<JoinRequestMutationResponse>(
      `/api/organizations/${organizationId}/join-requests`,
      {
        method: 'POST',
        body: payload,
      }
    );

    return response.data;
  };

  const list = async (
    organizationId: string,
    params: OrganizationJoinRequestsListParams = {}
  ): Promise<PaginationPayload<OrganizationJoinRequest>> => {
    const response = await api<IndexResponse<OrganizationJoinRequest>>(
      `/api/organizations/${organizationId}/join-requests`,
      {
        query: params,
      }
    );

    return response.data;
  };

  const my = async (
    organizationId: string,
    params: OrganizationJoinRequestsListParams = {}
  ): Promise<PaginationPayload<OrganizationJoinRequest>> => {
    const response = await api<IndexResponse<OrganizationJoinRequest>>(
      `/api/organizations/${organizationId}/join-requests/my`,
      {
        query: params,
      }
    );

    return response.data;
  };

  const approve = async (
    organizationId: string,
    requestId: string,
    payload: ReviewOrganizationJoinRequestPayload = {}
  ): Promise<OrganizationJoinRequest> => {
    const response = await api<JoinRequestMutationResponse>(
      `/api/organizations/${organizationId}/join-requests/${requestId}/approve`,
      {
        method: 'PATCH',
        body: payload,
      }
    );

    return response.data;
  };

  const reject = async (
    organizationId: string,
    requestId: string,
    payload: ReviewOrganizationJoinRequestPayload = {}
  ): Promise<OrganizationJoinRequest> => {
    const response = await api<JoinRequestMutationResponse>(
      `/api/organizations/${organizationId}/join-requests/${requestId}/reject`,
      {
        method: 'PATCH',
        body: payload,
      }
    );

    return response.data;
  };

  return {
    submit,
    list,
    my,
    approve,
    reject,
  };
};
