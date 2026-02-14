import { resolveAssetUrl } from '~/composables/asset-url';

interface AuthUser {
  avatar?: {
    id: string;
    url: string;
    original_name: string;
    mime_type?: string | null;
    size?: number;
    collection: string;
  } | null;
  id: string;
  email: string;
  first_name?: string;
  last_name?: string;
  middle_name?: string;
  settings?: {
    locale?: 'ru' | 'en' | null;
    theme?: 'light' | 'dark';
    collapse_menu?: boolean;
    admin_crud_preferences?: Record<
      string,
      {
        contentMode?: 'table' | 'table-cards' | 'cards';
        tableOnDesktop?: boolean;
      }
    >;
    admin_navigation_sections?: Record<
      string,
      {
        open?: boolean;
      }
    >;
  } | null;
  roles?: string[];
  is_admin?: boolean;
  can_access_admin_panel?: boolean;
}

interface LoginResponse {
  token: string;
  user: AuthUser;
}

interface MeResponse {
  status: string;
  user: AuthUser;
}

export const useAuth = () => {
  const config = useRuntimeConfig();

  const token = useCookie<string | null>('auth_token', {
    sameSite: 'lax',
  });

  const user = useCookie<AuthUser | null>('auth_user', {
    sameSite: 'lax',
  });

  const isAuthenticated = computed(() => Boolean(token.value));
  const canAccessAdminPanel = computed(() => Boolean(user.value?.can_access_admin_panel));

  const normalizeUserAssets = (nextUser: AuthUser | null): AuthUser | null => {
    if (!nextUser) {
      return null;
    }

    const avatarUrl = resolveAssetUrl(config.public.apiBase, nextUser.avatar?.url ?? null);

    if (!nextUser.avatar || !avatarUrl) {
      return nextUser;
    }

    return {
      ...nextUser,
      avatar: {
        ...nextUser.avatar,
        url: avatarUrl,
      },
    };
  };

  const setUser = (nextUser: AuthUser | null) => {
    user.value = normalizeUserAssets(nextUser);
  };

  const login = async (email: string, password: string): Promise<void> => {
    const api = useApi();

    const response = await api<LoginResponse>('/api/auth/login', {
      method: 'POST',
      body: {
        email,
        password,
      },
    });

    token.value = response.token;
    setUser(response.user);
  };

  const refreshUser = async (): Promise<AuthUser | null> => {
    if (!token.value) {
      setUser(null);
      return null;
    }

    const api = useApi();
    const response = await api<MeResponse>('/api/me');
    setUser(response.user);

    return response.user;
  };

  const updateProfile = async (payload: Record<string, unknown>): Promise<AuthUser> => {
    const api = useApi();
    const response = await api<MeResponse>('/api/me', {
      method: 'PATCH',
      body: payload,
    });

    setUser(response.user);
    return response.user;
  };

  const updatePassword = async (payload: Record<string, unknown>): Promise<AuthUser> => {
    const api = useApi();
    const response = await api<MeResponse>('/api/me/password', {
      method: 'PATCH',
      body: payload,
    });

    setUser(response.user);
    return response.user;
  };

  const updateSettings = async (settings: Record<string, unknown>): Promise<void> => {
    const api = useApi();
    await api('/api/me/settings', {
      method: 'PATCH',
      body: {
        settings,
      },
    });
  };

  const uploadAvatar = async (avatar: File): Promise<AuthUser> => {
    const api = useApi();
    const body = new FormData();
    body.append('avatar', avatar);

    const response = await api<MeResponse>('/api/me/avatar', {
      method: 'POST',
      body,
    });

    setUser(response.user);
    return response.user;
  };

  const deleteAvatar = async (): Promise<AuthUser> => {
    const api = useApi();
    const response = await api<MeResponse>('/api/me/avatar', {
      method: 'DELETE',
    });

    setUser(response.user);
    return response.user;
  };

  const logout = async (): Promise<void> => {
    try {
      const api = useApi();
      await api('/api/auth/logout', { method: 'POST' });
    } catch {
      // ignore network/API logout failures and clear local auth state anyway
    }

    token.value = null;
    setUser(null);
  };

  return {
    token,
    user,
    isAuthenticated,
    canAccessAdminPanel,
    login,
    refreshUser,
    updateProfile,
    updatePassword,
    updateSettings,
    uploadAvatar,
    deleteAvatar,
    logout,
  };
};
