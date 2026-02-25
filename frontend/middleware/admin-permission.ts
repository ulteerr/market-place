export default defineNuxtRouteMiddleware((to) => {
  if (!to.path.startsWith('/admin')) {
    return;
  }

  const requiredRaw = to.meta.permission;
  if (!requiredRaw) {
    return;
  }

  const required = Array.isArray(requiredRaw)
    ? requiredRaw.filter((item): item is string => typeof item === 'string' && item.length > 0)
    : typeof requiredRaw === 'string' && requiredRaw.length > 0
      ? [requiredRaw]
      : [];

  if (required.length === 0) {
    return;
  }

  const mode = to.meta.permissionMode === 'all' ? 'all' : 'any';
  const { hasAnyPermission, hasAllPermissions } = usePermissions();
  const allowed = mode === 'all' ? hasAllPermissions(required) : hasAnyPermission(required);

  if (!allowed) {
    return navigateTo('/admin');
  }
});
