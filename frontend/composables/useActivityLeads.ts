import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';

export type ActivityLeadStatus = 'new' | 'in_progress' | 'contacted' | 'registered' | 'cancelled';

export type ActivityLeadRequestType = 'self' | 'child';
export type ActivityLeadContactChannel = 'chat' | 'phone' | 'telegram' | 'whatsapp' | 'max';

export interface ActivityLeadChild {
  id: string;
  user_id?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  middle_name?: string | null;
  birth_date?: string | null;
}

export interface ActivityLeadSubject {
  type: ActivityLeadRequestType;
  id: string;
  first_name?: string | null;
  last_name?: string | null;
  middle_name?: string | null;
  email?: string | null;
  phone?: string | null;
  birth_date?: string | null;
  user_id?: string | null;
  label?: string | null;
}

export interface ActivityLeadActivity {
  id: string;
  name: string;
  slug: string;
  organization?: {
    id: string;
    name: string;
  } | null;
}

export interface ActivityLead {
  id: string;
  activity_id: string;
  user_id: string;
  child_id?: string | null;
  request_for_type: ActivityLeadRequestType;
  status: ActivityLeadStatus;
  contact_channels: string[];
  contact_payload?: Record<string, string> | null;
  message?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  activity?: ActivityLeadActivity | null;
  subject?: ActivityLeadSubject | null;
}

export interface ActivityLeadsListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: 'created_at' | 'updated_at' | 'status';
  sort_dir?: SortDirection;
  status?: ActivityLeadStatus | '';
  request_for_type?: ActivityLeadRequestType | '';
  activity_id?: string;
}

interface ActivityLeadMutationResponse {
  status: string;
  message?: string;
  data: ActivityLead;
}

export interface PublicActivityLeadSubmitPayload {
  request_for_type: ActivityLeadRequestType;
  child_id?: string | null;
  contact_channels: ActivityLeadContactChannel[];
  contact_payload?: Partial<Record<'phone' | 'telegram' | 'whatsapp' | 'max', string>> | null;
  message?: string | null;
}

export const resolveActivityLeadSubjectLabel = (lead: ActivityLead): string => {
  return (
    lead.subject?.label ||
    [lead.subject?.last_name, lead.subject?.first_name, lead.subject?.middle_name]
      .filter((value): value is string => typeof value === 'string' && value.length > 0)
      .join(' ') ||
    lead.subject?.email ||
    lead.subject?.phone ||
    lead.id
  );
};

export const resolveActivityLeadChannelsLabel = (lead: ActivityLead): string => {
  return Array.isArray(lead.contact_channels) && lead.contact_channels.length > 0
    ? lead.contact_channels.join(', ')
    : 'chat';
};

export const useActivityLeads = () => {
  const api = useApi();

  const listChildren = async (): Promise<ActivityLeadChild[]> => {
    return await api<ActivityLeadChild[]>('/children', {
      method: 'GET',
    });
  };

  const submit = async (
    activityId: string,
    payload: PublicActivityLeadSubmitPayload
  ): Promise<ActivityLead> => {
    const response = await api<ActivityLeadMutationResponse>(
      `/api/activities/${activityId}/leads`,
      {
        method: 'POST',
        body: payload,
      }
    );

    return response.data;
  };

  const adminList = async (
    params: ActivityLeadsListParams = {},
    context?: { signal?: AbortSignal }
  ): Promise<PaginationPayload<ActivityLead>> => {
    const response = await api<IndexResponse<ActivityLead>>('/api/admin/activity-leads', {
      query: params,
      signal: context?.signal,
    });

    return response.data;
  };

  const adminUpdateStatus = async (
    leadId: string,
    status: ActivityLeadStatus
  ): Promise<ActivityLead> => {
    const response = await api<ActivityLeadMutationResponse>(
      `/api/admin/activity-leads/${leadId}/status`,
      {
        method: 'PATCH',
        body: { status },
      }
    );

    return response.data;
  };

  const organizationList = async (
    organizationId: string,
    params: ActivityLeadsListParams = {}
  ): Promise<PaginationPayload<ActivityLead>> => {
    const response = await api<IndexResponse<ActivityLead>>(
      `/api/organizations/${organizationId}/activity-leads`,
      {
        query: params,
      }
    );

    return response.data;
  };

  const organizationUpdateStatus = async (
    organizationId: string,
    leadId: string,
    status: ActivityLeadStatus
  ): Promise<ActivityLead> => {
    const response = await api<ActivityLeadMutationResponse>(
      `/api/organizations/${organizationId}/activity-leads/${leadId}/status`,
      {
        method: 'PATCH',
        body: { status },
      }
    );

    return response.data;
  };

  return {
    listChildren,
    submit,
    adminList,
    adminUpdateStatus,
    organizationList,
    organizationUpdateStatus,
  };
};
