import type {
  IndexResponse,
  PaginationPayload,
  SortDirection,
} from '~/composables/useAdminCrudCommon';
import { resolveAssetUrl } from '~/composables/asset-url';

export interface AdminUser {
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
  first_name: string;
  last_name: string;
  middle_name?: string | null;
  gender?: 'male' | 'female' | null;
  phone?: string | null;
  roles?: Array<string | { code?: string | null }>;
  permission_overrides?: {
    allow: string[];
    deny: string[];
  };
  can_access_admin_panel?: boolean;
}

const ROLE_LEVELS: Record<string, number> = {
  participant: 0,
  moderator: 1,
  admin: 2,
  super_admin: 3,
};

export const getRoleLevel = (roleCode: string): number => {
  return ROLE_LEVELS[roleCode] ?? 0;
};

interface UserShowResponse {
  status: string;
  user: AdminUser;
}

interface UserMutationResponse {
  status: string;
  message?: string;
  data?: AdminUser;
}

export interface CreateUserPayload {
  email: string;
  password: string;
  password_confirmation: string;
  first_name: string;
  last_name: string;
  middle_name?: string | null;
  gender?: 'male' | 'female' | null;
  phone?: string | null;
  roles?: string[];
  permission_overrides?: {
    allow: string[];
    deny: string[];
  };
  avatar?: File | null;
}

export interface UpdateUserPayload {
  email?: string;
  password?: string;
  password_confirmation?: string;
  first_name?: string;
  last_name?: string;
  middle_name?: string | null;
  gender?: 'male' | 'female' | null;
  phone?: string | null;
  roles?: string[];
  permission_overrides?: {
    allow: string[];
    deny: string[];
  };
  avatar?: File | null;
  avatar_delete?: boolean;
}

export interface AdminUsersListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: SortDirection;
  access_group?: 'admin' | 'basic';
}

export const getAdminUserFullName = (user: AdminUser): string => {
  return (
    [user.last_name, user.first_name, user.middle_name].filter(Boolean).join(' ') || user.email
  );
};

export const resolveAdminUserPanelAccess = (user: AdminUser): boolean | null => {
  if (typeof user.can_access_admin_panel === 'boolean') {
    return user.can_access_admin_panel;
  }

  if (Array.isArray(user.roles)) {
    return user.roles.some((role) => {
      if (typeof role === 'string') {
        return role !== 'participant';
      }

      return role?.code !== 'participant';
    });
  }

  return null;
};

const extractRoleCode = (role: string | { code?: string | null }): string => {
  if (typeof role === 'string') {
    return role;
  }

  return role?.code ?? '';
};

export const adminUserHasRole = (user: AdminUser, roleCode: string): boolean => {
  if (!Array.isArray(user.roles) || !roleCode) {
    return false;
  }

  return user.roles.some((role) => extractRoleCode(role) === roleCode);
};

export const getHighestRoleLevelFromCodes = (roles: string[] = []): number => {
  if (!Array.isArray(roles) || roles.length === 0) {
    return 0;
  }

  return roles.reduce((max, roleCode) => Math.max(max, getRoleLevel(roleCode)), 0);
};

export const getHighestRoleLevelForUser = (user: AdminUser): number => {
  if (!Array.isArray(user.roles) || user.roles.length === 0) {
    return 0;
  }

  const roleCodes = user.roles
    .map((role) => extractRoleCode(role))
    .filter((role): role is string => typeof role === 'string' && role.length > 0);

  return getHighestRoleLevelFromCodes(roleCodes);
};

export const useAdminUsers = () => {
  const api = useApi();
  const config = useRuntimeConfig();

  const normalizeUserAssets = (user: AdminUser): AdminUser => {
    const avatarUrl = resolveAssetUrl(config.public.apiBase, user.avatar?.url ?? null);

    if (!user.avatar || !avatarUrl) {
      return user;
    }

    return {
      ...user,
      avatar: {
        ...user.avatar,
        url: avatarUrl,
      },
    };
  };

  const list = async (params: AdminUsersListParams = {}): Promise<PaginationPayload<AdminUser>> => {
    const response = await api<IndexResponse<AdminUser>>('/api/admin/users', {
      query: params,
    });

    return {
      ...response.data,
      data: response.data.data.map(normalizeUserAssets),
    };
  };

  const show = async (id: string): Promise<AdminUser> => {
    const response = await api<UserShowResponse>(`/api/admin/users/${id}`);

    return normalizeUserAssets(response.user);
  };

  const buildMutationBody = (payload: CreateUserPayload | UpdateUserPayload): FormData => {
    const body = new FormData();

    Object.entries(payload).forEach(([key, value]) => {
      if (value === undefined || value === null) {
        return;
      }

      if (key === 'roles' && Array.isArray(value)) {
        value.forEach((role) => {
          body.append('roles[]', role);
        });
        return;
      }

      if (
        key === 'permission_overrides' &&
        value &&
        typeof value === 'object' &&
        !Array.isArray(value)
      ) {
        body.append('permission_overrides_present', '1');

        const allow: string[] = Array.isArray(value.allow)
          ? value.allow.filter((code: unknown): code is string => typeof code === 'string')
          : [];
        const deny: string[] = Array.isArray(value.deny)
          ? value.deny.filter((code: unknown): code is string => typeof code === 'string')
          : [];

        allow.forEach((code) => body.append('permission_overrides[allow][]', code));
        deny.forEach((code) => body.append('permission_overrides[deny][]', code));
        return;
      }

      if (key === 'avatar' && value instanceof File) {
        body.append('avatar', value);
        return;
      }

      if (typeof value === 'boolean') {
        body.append(key, value ? '1' : '0');
        return;
      }

      body.append(key, String(value));
    });

    return body;
  };

  const create = async (payload: CreateUserPayload): Promise<void> => {
    await api<UserMutationResponse>('/api/admin/users', {
      method: 'POST',
      body: buildMutationBody(payload),
    });
  };

  const update = async (id: string, payload: UpdateUserPayload): Promise<void> => {
    await api<UserMutationResponse>(`/api/admin/users/${id}`, {
      method: 'PATCH',
      body: buildMutationBody(payload),
    });
  };

  const remove = async (id: string): Promise<void> => {
    await api(`/api/admin/users/${id}`, {
      method: 'DELETE',
    });
  };

  return {
    list,
    show,
    create,
    update,
    remove,
  };
};
