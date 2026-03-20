import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useActivityLeads } from '~/composables/useActivityLeads';

describe('useActivityLeads', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('loads current user children from private children endpoint', async () => {
    const fetchMock = vi.fn().mockResolvedValue([
      {
        id: 'child-1',
        first_name: 'Иван',
        last_name: 'Иванов',
      },
    ]);

    vi.stubGlobal('useApi', () => fetchMock);

    const api = useActivityLeads();
    const result = await api.listChildren();

    expect(fetchMock).toHaveBeenCalledWith('/children', {
      method: 'GET',
    });
    expect(result[0]?.id).toBe('child-1');
  });

  it('submits public activity lead payload', async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      status: 'ok',
      data: {
        id: 'lead-1',
        activity_id: 'act-1',
        user_id: 'user-1',
        request_for_type: 'child',
        status: 'new',
        contact_channels: ['phone', 'telegram'],
        contact_payload: {
          phone: '+79990001122',
          telegram: '@tester',
        },
      },
    });

    vi.stubGlobal('useApi', () => fetchMock);

    const api = useActivityLeads();
    const result = await api.submit('act-1', {
      request_for_type: 'child',
      child_id: 'child-1',
      contact_channels: ['phone', 'telegram'],
      contact_payload: {
        phone: '+79990001122',
        telegram: '@tester',
      },
      message: 'Хочу уточнить расписание.',
    });

    expect(fetchMock).toHaveBeenCalledWith('/api/activities/act-1/leads', {
      method: 'POST',
      body: {
        request_for_type: 'child',
        child_id: 'child-1',
        contact_channels: ['phone', 'telegram'],
        contact_payload: {
          phone: '+79990001122',
          telegram: '@tester',
        },
        message: 'Хочу уточнить расписание.',
      },
    });
    expect(result.id).toBe('lead-1');
  });
});
