export const usePermissions = () => {
  const { user } = useAuth();

  const codes = computed<string[]>(() => {
    const items = user.value?.permissions;
    if (!Array.isArray(items)) {
      return [];
    }

    return items.filter((item): item is string => typeof item === 'string' && item.length > 0);
  });

  const hasPermission = (code: string): boolean => {
    if (!code) {
      return false;
    }

    return codes.value.includes(code);
  };

  const hasAnyPermission = (required: string[]): boolean => {
    if (!Array.isArray(required) || required.length === 0) {
      return false;
    }

    return required.some((code) => hasPermission(code));
  };

  const hasAllPermissions = (required: string[]): boolean => {
    if (!Array.isArray(required) || required.length === 0) {
      return false;
    }

    return required.every((code) => hasPermission(code));
  };

  return {
    codes,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
  };
};
