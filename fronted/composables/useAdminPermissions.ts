export interface AdminAccessPermission {
  id: string;
  code: string;
  scope: string;
  label: string | null;
}

interface AdminAccessPermissionsResponse {
  status: string;
  data: {
    data: AdminAccessPermission[];
  };
}

export const useAdminPermissions = () => {
  const api = useApi();
  const { t, te } = useI18n();

  const list = async (): Promise<AdminAccessPermission[]> => {
    const response = await api<AdminAccessPermissionsResponse>('/api/admin/permissions');
    return response.data?.data ?? [];
  };

  const normalizeCodeToI18nPath = (code: string): string => {
    return code.replaceAll('-', '_');
  };

  const resolvePermissionLabel = (permission: AdminAccessPermission): string => {
    const path = `admin.permissions.codes.${normalizeCodeToI18nPath(permission.code)}`;

    if (te(path)) {
      return t(path);
    }

    return permission.label || permission.code;
  };

  const resolvePermissionScopeLabel = (scope: string): string => {
    const path = `admin.permissions.scopes.${scope}`;

    if (te(path)) {
      return t(path);
    }

    return scope.toUpperCase();
  };

  return {
    list,
    resolvePermissionLabel,
    resolvePermissionScopeLabel,
  };
};
