type OrganizationAccessLevel = 'owner' | 'admin' | 'manager' | 'member' | 'none';

const organizationRoleOrder: OrganizationAccessLevel[] = ['owner', 'admin', 'manager', 'member'];

export const useOrganizationAccess = () => {
  const { t } = useI18n();
  const { user } = useAuth();
  const { hasPermission, hasAllPermissions, hasAnyPermission } = usePermissions();

  const rawRoles = computed<string[]>(() => {
    const roles = user.value?.roles;

    if (!Array.isArray(roles)) {
      return [];
    }

    return roles.filter((role): role is string => typeof role === 'string' && role.length > 0);
  });

  const explicitRole = computed<OrganizationAccessLevel | null>(() => {
    const normalized = rawRoles.value.map((role) => role.toLowerCase());

    return organizationRoleOrder.find((role) => normalized.includes(role)) ?? null;
  });

  const canReadProfile = computed(() => hasPermission('org.company.profile.read'));
  const canManageProfile = computed(() => hasPermission('org.company.profile.update'));
  const canReadMembers = computed(() => hasPermission('org.members.read'));
  const canManageMembers = computed(() => hasPermission('org.members.write'));
  const canReadClients = computed(() => hasPermission('org.children.read'));
  const canManageClients = computed(() => hasPermission('org.children.write'));
  const canViewJoinRequests = computed(() => hasPermission('org.members.read'));
  const canReviewJoinRequests = computed(() => hasPermission('org.members.write'));

  const inferredRole = computed<OrganizationAccessLevel>(() => {
    if (explicitRole.value) {
      return explicitRole.value;
    }

    if (
      hasAllPermissions(['org.company.profile.update', 'org.members.write', 'org.children.write'])
    ) {
      return 'admin';
    }

    if (hasAnyPermission(['org.members.write', 'org.children.write'])) {
      return 'manager';
    }

    if (hasAnyPermission(['org.company.profile.read', 'org.members.read', 'org.children.read'])) {
      return 'member';
    }

    return 'none';
  });

  const accessLabel = computed(() => t(`app.organizations.access.${inferredRole.value}`));

  return {
    accessLabel,
    inferredRole,
    canReadProfile,
    canManageProfile,
    canReadMembers,
    canManageMembers,
    canReadClients,
    canManageClients,
    canViewJoinRequests,
    canReviewJoinRequests,
  };
};
