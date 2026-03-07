import type { AdminUser } from '~/composables/useAdminUsers';

interface UsersStatsApiResponse {
  status: string;
  data: {
    total_users: number;
    online_users: number;
    updated_at?: string | null;
  };
}

export interface MainUsersStatsState {
  totalUsers: number;
  onlineUsers: number;
  updatedAt: string | null;
}

const DEFAULT_STATE: MainUsersStatsState = {
  totalUsers: 0,
  onlineUsers: 0,
  updatedAt: null,
};

export const useMainUsersStats = () => {
  const api = useApi();
  const adminUsers = useAdminUsers();
  const auth = useAuth();

  const state = ref<MainUsersStatsState>({ ...DEFAULT_STATE });
  const loading = ref(false);
  const error = ref('');

  const fetchViaStatsEndpoint = async (): Promise<MainUsersStatsState> => {
    const response = await api<UsersStatsApiResponse>('/api/admin/users/stats', {
      method: 'GET',
    });

    return {
      totalUsers: Number(response.data.total_users) || 0,
      onlineUsers: Number(response.data.online_users) || 0,
      updatedAt: response.data.updated_at ?? null,
    };
  };

  const fetchViaUsersListFallback = async (): Promise<MainUsersStatsState> => {
    const perPage = 200;
    const firstPage = await adminUsers.list({ page: 1, per_page: perPage });

    const pages = [firstPage];
    if (firstPage.last_page > 1) {
      const restPages = await Promise.all(
        Array.from({ length: firstPage.last_page - 1 }, (_, index) =>
          adminUsers.list({ page: index + 2, per_page: perPage })
        )
      );
      pages.push(...restPages);
    }

    const users = pages.flatMap((page) => page.data);
    const visibleOnlineUsers = users.reduce((sum, user: AdminUser) => {
      return sum + (user.is_online ? 1 : 0);
    }, 0);

    const authUserId = auth.user.value?.id ?? null;
    const authUserRow = authUserId ? users.find((user) => user.id === authUserId) : null;
    const shouldIncludeCurrentUser =
      Boolean(authUserId) && (!authUserRow || typeof authUserRow.is_online !== 'boolean');
    const onlineUsers = visibleOnlineUsers + (shouldIncludeCurrentUser ? 1 : 0);

    return {
      totalUsers: firstPage.total,
      onlineUsers,
      updatedAt: null,
    };
  };

  const refresh = async (): Promise<void> => {
    loading.value = true;
    error.value = '';

    try {
      state.value = await fetchViaStatsEndpoint();
    } catch {
      try {
        // Fallback keeps UI functional in environments where /users/stats is not available yet.
        state.value = await fetchViaUsersListFallback();
      } catch {
        error.value = 'load_error';
      }
    } finally {
      loading.value = false;
    }
  };

  return {
    state,
    loading,
    error,
    refresh,
  };
};
