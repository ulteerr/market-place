export type AdminNavIcon = 'home' | 'system' | 'settings' | 'users' | 'roles';

export interface AdminNavigationItemDefinition {
  key: string;
  to: string;
  labelKey: string;
  icon: AdminNavIcon;
}

export interface AdminNavigationSectionDefinition {
  key: string;
  labelKey: string;
  icon: AdminNavIcon;
  items: AdminNavigationItemDefinition[];
}

export const adminDashboardItemDefinition: AdminNavigationItemDefinition = {
  key: 'dashboard',
  to: '/admin',
  labelKey: 'admin.layout.menu.dashboard',
  icon: 'home',
};

export const adminNavigationSectionDefinitions: AdminNavigationSectionDefinition[] = [
  {
    key: 'system',
    labelKey: 'admin.layout.sections.system',
    icon: 'system',
    items: [
      {
        key: 'users',
        to: '/admin/users',
        labelKey: 'admin.layout.menu.users',
        icon: 'users',
      },
      {
        key: 'roles',
        to: '/admin/roles',
        labelKey: 'admin.layout.menu.roles',
        icon: 'roles',
      },
    ],
  },
];
