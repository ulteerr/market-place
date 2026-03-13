export default defineNuxtRouteMiddleware((to) => {
  const isAccountArea = to.path.startsWith('/account');
  const isOrganizationsArea = to.path.startsWith('/organizations');

  if (!isAccountArea && !isOrganizationsArea) {
    return;
  }

  setPageLayout(isAccountArea ? 'account' : 'organizations');

  const { isAuthenticated } = useAuth();
  if (!isAuthenticated.value) {
    return navigateTo('/login');
  }
});
