export default defineNuxtRouteMiddleware(async () => {
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
