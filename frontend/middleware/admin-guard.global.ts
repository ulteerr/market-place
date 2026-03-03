export default defineNuxtRouteMiddleware((to) => {
  if (!to.path.startsWith('/admin')) {
    return;
  }

  // Keep admin routes on the admin shell even if page meta fails to resolve.
  setPageLayout('admin');

  const { isAuthenticated, canAccessAdminPanel } = useAuth();

  if (!isAuthenticated.value) {
    return navigateTo('/login');
  }

  if (!canAccessAdminPanel.value) {
    return abortNavigation(
      createError({
        statusCode: 403,
        statusMessage: 'Доступ запрещен',
        message: 'У вас нет доступа к этому разделу.',
      })
    );
  }
});
