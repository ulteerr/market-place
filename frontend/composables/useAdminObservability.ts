export interface ObservabilityEventStatusCounters {
  [status: string]: number;
}

export interface ObservabilityDomainSummary {
  events_total: number;
  errors_total: number;
  duration_total_ms: number;
  duration_count: number;
  events: Record<string, ObservabilityEventStatusCounters>;
  last_event_at: string | null;
}

export interface ObservabilitySummaryPayload {
  domains: Record<string, ObservabilityDomainSummary>;
  updated_at: string | null;
}

export interface ObservabilityIncident {
  timestamp: string;
  domain: string;
  component: string;
  event: string;
  severity: string;
  status: string;
  duration_ms: number | null;
  request_id: string | null;
  meta: Record<string, unknown>;
}

export interface ObservabilityDashboardPayload {
  summary: ObservabilitySummaryPayload;
  incidents: ObservabilityIncident[];
  analytics?: Record<
    string,
    {
      events_total: number;
      errors_total: number;
      error_rate: number;
      availability_percent: number;
      avg_duration_ms: number | null;
    }
  >;
  alerts: Array<{
    code: string;
    severity: string;
    domain: string;
    message: string;
    value: number;
    threshold: number;
    events_total: number;
    errors_total: number;
  }>;
}

interface ObservabilityResponse {
  status: string;
  data: ObservabilityDashboardPayload;
}

export const useAdminObservability = () => {
  const api = useApi();

  const getDashboard = async (params?: {
    domain?: string | null;
    limit?: number;
  }): Promise<ObservabilityDashboardPayload> => {
    const query: Record<string, string | number> = {};
    if (params?.domain) {
      query.domain = params.domain;
    }
    if (typeof params?.limit === 'number') {
      query.limit = params.limit;
    }

    const response = await api<ObservabilityResponse>('/api/admin/observability', {
      method: 'GET',
      query,
    });

    return response.data;
  };

  return {
    getDashboard,
  };
};
