import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useMainUsersStats } from '~/composables/useMainUsersStats';

describe('useMainUsersStats', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.unstubAllGlobals();
    vi.stubGlobal('ref', ref);
  });

  it('counts current user as online when admin users list hides is_online for self', async () => {
    const apiMock = vi.fn().mockRejectedValue(new Error('not implemented'));
    const listMock = vi
      .fn()
      .mockResolvedValueOnce({
        data: [
          { id: 'self-user', is_online: undefined },
          { id: 'other-online', is_online: true },
        ],
        current_page: 1,
        last_page: 2,
        per_page: 200,
        total: 3,
      })
      .mockResolvedValueOnce({
        data: [{ id: 'other-offline', is_online: false }],
        current_page: 2,
        last_page: 2,
        per_page: 200,
        total: 3,
      });

    vi.stubGlobal('useApi', () => apiMock);
    vi.stubGlobal('useAdminUsers', () => ({
      list: listMock,
    }));
    vi.stubGlobal('useAuth', () => ({
      user: ref({ id: 'self-user' }),
    }));

    const stats = useMainUsersStats();
    await stats.refresh();

    expect(stats.error.value).toBe('');
    expect(stats.state.value.totalUsers).toBe(3);
    expect(stats.state.value.onlineUsers).toBe(2);
    expect(listMock).toHaveBeenCalledTimes(2);
  });
});
