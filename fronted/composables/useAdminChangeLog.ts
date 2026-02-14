import type { PaginationPayload } from '~/composables/useAdminCrudCommon';

export type ChangeLogEvent = 'create' | 'update' | 'delete' | 'restore';

export interface AdminChangeLogEntry {
  id: string;
  auditable_type: string;
  auditable_id: string;
  event: ChangeLogEvent;
  version: number;
  before: Record<string, unknown> | null;
  after: Record<string, unknown> | null;
  changed_fields: string[] | null;
  actor_type: string | null;
  actor_id: string | null;
  actor?: {
    id: string;
    full_name: string | null;
  } | null;
  batch_id: string | null;
  rolled_back_from_id: string | null;
  meta: Record<string, unknown> | null;
  created_at: string;
}

interface ChangeLogIndexResponse {
  status: string;
  data: PaginationPayload<AdminChangeLogEntry> & {
    list_mode?: ChangeLogListMode;
  };
}

interface ChangeLogRollbackResponse {
  status: string;
  message?: string;
  data: {
    model_type: string;
    model_id: string;
    rolled_back_from_id: string;
  };
}

export interface AdminChangeLogListParams {
  model?: string;
  entity_id?: string;
  event?: ChangeLogEvent;
  page?: number;
  per_page?: number;
}

export type ChangeLogListMode = 'latest' | 'paginated';

export const useAdminChangeLog = () => {
  const api = useApi();

  const list = async (
    params: AdminChangeLogListParams = {}
  ): Promise<
    PaginationPayload<AdminChangeLogEntry> & {
      list_mode?: ChangeLogListMode;
    }
  > => {
    const response = await api<ChangeLogIndexResponse>('/api/admin/changelog', {
      query: params,
    });

    return response.data;
  };

  const rollback = async (entryId: string): Promise<ChangeLogRollbackResponse['data']> => {
    const response = await api<ChangeLogRollbackResponse>(
      `/api/admin/changelog/${entryId}/rollback`,
      {
        method: 'POST',
      }
    );

    return response.data;
  };

  return {
    list,
    rollback,
  };
};
