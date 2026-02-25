import type { AdminUser } from '~/composables/useAdminUsers';

export interface AdminUserSelectOption {
  label: string;
  value: string;
}

interface UseAdminUserSelectOptionsParams {
  perPage?: number;
  debounceMs?: number;
}

const resolveAdminUserSelectLabel = (user: AdminUser): string => {
  const fullName = [user.first_name, user.last_name, user.middle_name]
    .filter((part): part is string => typeof part === 'string' && part.trim().length > 0)
    .join(' ');

  const title = fullName || user.email || user.id;
  return `${title} - ${user.id}`;
};

export const useAdminUserSelectOptions = (params: UseAdminUserSelectOptionsParams = {}) => {
  const usersApi = useAdminUsers();
  const loadingUsers = ref(false);
  const userOptions = ref<AdminUserSelectOption[]>([]);
  let userSearchTimer: ReturnType<typeof setTimeout> | null = null;

  const perPage = params.perPage ?? 20;
  const debounceMs = params.debounceMs ?? 250;

  const loadUserOptions = async (search = '') => {
    loadingUsers.value = true;

    try {
      const payload = await usersApi.list({
        per_page: perPage,
        search: search.trim() || undefined,
        sort_by: 'last_name',
        sort_dir: 'asc',
      });

      userOptions.value = payload.data.map((user) => ({
        value: user.id,
        label: resolveAdminUserSelectLabel(user),
      }));
    } finally {
      loadingUsers.value = false;
    }
  };

  const ensureSelectedUserOption = async (userId: string) => {
    if (!userId || userOptions.value.some((option) => option.value === userId)) {
      return;
    }

    await loadUserOptions(userId);
  };

  const onUserSearch = (query: string) => {
    if (userSearchTimer) {
      clearTimeout(userSearchTimer);
    }

    userSearchTimer = setTimeout(() => {
      loadUserOptions(query);
    }, debounceMs);
  };

  onBeforeUnmount(() => {
    if (userSearchTimer) {
      clearTimeout(userSearchTimer);
      userSearchTimer = null;
    }
  });

  return {
    loadingUsers,
    userOptions,
    loadUserOptions,
    onUserSearch,
    ensureSelectedUserOption,
  };
};
