import type { PaginationPayload } from '~/composables/useAdminCrudCommon';

export interface AdminActionLogItem {
  id: string;
  user_id: string | null;
  user: {
    id: string;
    full_name: string | null;
    email: string | null;
  } | null;
  event: 'create' | 'update' | 'delete' | string;
  model_type: string;
  model_id: string;
  ip_address: string | null;
  before: Record<string, unknown> | null;
  after: Record<string, unknown> | null;
  changed_fields: string[] | null;
  created_at: string;
}

interface ActionLogsIndexResponse {
  status: string;
  data: PaginationPayload<AdminActionLogItem>;
}

export interface AdminActionLogsListParams {
  page?: number;
  per_page?: number;
  search?: string;
  event?: string;
  model?: string;
  model_id?: string;
  user?: string;
  date_from?: string;
  date_to?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}

export const useAdminActionLogs = () => {
  const api = useApi();

  const list = async (
    params: AdminActionLogsListParams = {}
  ): Promise<PaginationPayload<AdminActionLogItem>> => {
    const response = await api<ActionLogsIndexResponse>('/api/admin/action-logs', {
      query: params,
    });

    return response.data;
  };

  return {
    list,
  };
};
