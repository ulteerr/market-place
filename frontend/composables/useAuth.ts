import { resolveAssetUrl } from '~/composables/asset-url';
import {
  clearGuestPreferences,
  mergeGuestPreferencesIntoAccountSettings,
  readGuestPreferences,
  writeGuestPreferences,
} from '~/composables/guest-preferences';

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
  gender?: 'male' | 'female' | null;
  phone?: string | null;
  birth_date?: string | null;
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
    favorites?: string[];
  } | null;
  roles?: string[];
  permissions?: string[];
  is_admin?: boolean;
}

interface LoginResponse {
  token: string;
  user: AuthUser;
}

interface MeResponse {
  status: string;
  user: AuthUser;
}

interface RegisterPayload {
  email: string;
  password: string;
  password_confirmation: string;
  first_name?: string;
  last_name?: string;
  middle_name?: string;
  phone?: string | null;
}

export const useAuth = () => {
  const config = useRuntimeConfig();
  const authCookieMaxAge = 60 * 60 * 24 * 30;
  const authCookieOptions = {
    sameSite: 'lax' as const,
    maxAge: authCookieMaxAge,
    secure: process.env.NODE_ENV === 'production',
  };

  const token = useCookie<string | null>('auth_token', {
    ...authCookieOptions,
  });

  const user = useCookie<AuthUser | null>('auth_user', {
    ...authCookieOptions,
  });

  const isAuthenticated = computed(() => Boolean(token.value));
  const canAccessAdminPanel = computed(
    () =>
      Array.isArray(user.value?.permissions) &&
      user.value.permissions.includes('admin.panel.access')
  );

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

  const syncGuestPreferencesAfterAuth = async (): Promise<void> => {
    const guestPreferences = readGuestPreferences();
    const hasFavorites = (guestPreferences.favorites?.length ?? 0) > 0;
    const hasSettings = Boolean(guestPreferences.settings);

    if (!hasSettings && !hasFavorites) {
      clearGuestPreferences();
      return;
    }

    const api = useApi();
    const mergedSettings = mergeGuestPreferencesIntoAccountSettings(
      user.value?.settings ?? null,
      guestPreferences
    );

    await api('/api/me/settings', {
      method: 'PATCH',
      body: {
        settings: mergedSettings,
      },
    });

    await refreshUser();
    clearGuestPreferences();
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
    const guestPreferences = readGuestPreferences();
    setUser({
      ...response.user,
      settings: mergeGuestPreferencesIntoAccountSettings(
        response.user.settings ?? null,
        guestPreferences
      ),
    });
    await syncGuestPreferencesAfterAuth();
  };

  const register = async (payload: RegisterPayload): Promise<void> => {
    const api = useApi();

    const response = await api<LoginResponse>('/api/auth/register', {
      method: 'POST',
      body: payload,
    });

    token.value = response.token;
    const guestPreferences = readGuestPreferences();
    setUser({
      ...response.user,
      settings: mergeGuestPreferencesIntoAccountSettings(
        response.user.settings ?? null,
        guestPreferences
      ),
    });
    await syncGuestPreferencesAfterAuth();
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

    if (user.value) {
      const currentSettings =
        user.value.settings && typeof user.value.settings === 'object' ? user.value.settings : {};
      setUser({
        ...user.value,
        settings: {
          ...currentSettings,
          ...settings,
        },
      });
    }
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
    if (user.value?.settings) {
      writeGuestPreferences({
        ...readGuestPreferences(),
        settings: user.value.settings,
        favorites: user.value.settings.favorites ?? [],
      });
    }

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
    register,
    refreshUser,
    updateProfile,
    updatePassword,
    updateSettings,
    uploadAvatar,
    deleteAvatar,
    logout,
  };
};
