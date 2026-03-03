export type AdminUserMenuAction = 'profile' | 'settings' | 'logout';

interface UseAdminUserMenuActionsOptions {
  logout: () => Promise<void>;
}

export const useAdminUserMenuActions = ({ logout }: UseAdminUserMenuActionsOptions) => {
  const handleLogout = async () => {
    await logout();
    await navigateTo('/login');
  };

  const onUserMenuSelect = async (action: AdminUserMenuAction) => {
    if (action === 'profile') {
      await navigateTo('/admin/profile');
      return;
    }

    if (action === 'settings') {
      await navigateTo('/admin/settings');
      return;
    }

    await handleLogout();
  };

  return {
    onUserMenuSelect,
  };
};
