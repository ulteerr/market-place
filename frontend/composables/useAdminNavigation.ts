import type {
  AdminNavigationItemDefinition,
  AdminNavigationSectionDefinition,
} from '~/config/admin-navigation';
import type { Ref } from 'vue';
import {
  adminDashboardItemDefinition,
  adminNavigationSectionDefinitions,
} from '~/config/admin-navigation';

export type AdminNavigationItemView = AdminNavigationItemDefinition & {
  label: string;
};

export type AdminNavigationSectionView = Omit<AdminNavigationSectionDefinition, 'items'> & {
  label: string;
  items: AdminNavigationItemView[];
};

interface UseAdminNavigationOptions {
  t: (key: string) => string;
  route: ReturnType<typeof useRoute>;
  settings: Ref<{
    admin_navigation_sections?: Record<string, { open?: boolean }>;
  }>;
  updateSettings: (payload: Record<string, unknown>) => void;
}

const navigationPermissions: Record<string, string> = {
  users: 'admin.users.read',
  roles: 'admin.roles.read',
  organizations: 'org.company.profile.read',
  children: 'org.children.read',
  'action-logs': 'admin.action-log.read',
  monitoring: 'admin.monitoring.read',
  'metro-lines': 'admin.metro.read',
  'metro-stations': 'admin.metro.read',
  'geo-countries': 'admin.geo.read',
  'geo-regions': 'admin.geo.read',
  'geo-cities': 'admin.geo.read',
  'geo-districts': 'admin.geo.read',
};

export const useAdminNavigation = ({
  t,
  route,
  settings,
  updateSettings,
}: UseAdminNavigationOptions) => {
  const { hasPermission } = usePermissions();

  const dashboardItem = computed<AdminNavigationItemView>(() => ({
    ...adminDashboardItemDefinition,
    label: t(adminDashboardItemDefinition.labelKey),
  }));

  const isNavigationItemVisible = (item: AdminNavigationItemDefinition): boolean => {
    const permission = navigationPermissions[item.key];

    if (!permission) {
      return true;
    }

    return hasPermission(permission);
  };

  const navigationSections = computed<AdminNavigationSectionView[]>(() =>
    adminNavigationSectionDefinitions
      .map((section) => ({
        ...section,
        label: t(section.labelKey),
        items: section.items
          .filter((item) => isNavigationItemVisible(item))
          .map((item) => ({
            ...item,
            label: t(item.labelKey),
          })),
      }))
      .filter((section) => section.items.length > 0)
  );

  const isSectionOpen = (key: string) =>
    settings.value.admin_navigation_sections?.[key]?.open === true;

  const setSectionOpen = (key: string, open: boolean) => {
    updateSettings({
      admin_navigation_sections: {
        [key]: { open },
      },
    });
  };

  const toggleSection = (key: string) => {
    setSectionOpen(key, !isSectionOpen(key));
  };

  const isActive = (path: string) => route.path === path || route.path.startsWith(`${path}/`);
  const isSectionItemActive = (items: Array<{ to: string }>) =>
    items.some((item) => isActive(item.to));
  const isDashboardActive = computed(() => route.path === dashboardItem.value.to);

  return {
    dashboardItem,
    navigationSections,
    isSectionOpen,
    toggleSection,
    isActive,
    isSectionItemActive,
    isDashboardActive,
  };
};
