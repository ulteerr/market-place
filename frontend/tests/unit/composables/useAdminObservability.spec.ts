import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useAdminObservability } from '~/composables/useAdminObservability';

describe('useAdminObservability', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('requests dashboard data with domain and limit filters', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: {
        summary: { domains: {}, updated_at: null },
        incidents: [],
      },
    });

    // Nuxt auto-import shim for unit test context.
    (globalThis as unknown as { useApi: () => typeof fetchMock }).useApi = () => fetchMock;

    const api = useAdminObservability();
    await api.getDashboard({ domain: 'presence', limit: 25 });

    expect(fetchMock).toHaveBeenCalledWith('/api/admin/observability', {
      method: 'GET',
      query: {
        domain: 'presence',
        limit: 25,
      },
    });
  });
});
