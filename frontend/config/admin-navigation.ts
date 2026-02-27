export type AdminNavIcon =
  | 'home'
  | 'system'
  | 'settings'
  | 'users'
  | 'roles'
  | 'activity'
  | 'organization'
  | 'organizations'
  | 'children'
  | 'metro'
  | 'geo';

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
      {
        key: 'action-logs',
        to: '/admin/action-logs',
        labelKey: 'admin.layout.menu.actionLogs',
        icon: 'activity',
      },
      {
        key: 'metro-lines',
        to: '/admin/metro-lines',
        labelKey: 'admin.layout.menu.metroLines',
        icon: 'metro',
      },
      {
        key: 'metro-stations',
        to: '/admin/metro-stations',
        labelKey: 'admin.layout.menu.metroStations',
        icon: 'metro',
      },
      {
        key: 'geo-countries',
        to: '/admin/geo/countries',
        labelKey: 'admin.layout.menu.geoCountries',
        icon: 'geo',
      },
      {
        key: 'geo-regions',
        to: '/admin/geo/regions',
        labelKey: 'admin.layout.menu.geoRegions',
        icon: 'geo',
      },
      {
        key: 'geo-cities',
        to: '/admin/geo/cities',
        labelKey: 'admin.layout.menu.geoCities',
        icon: 'geo',
      },
      {
        key: 'geo-districts',
        to: '/admin/geo/districts',
        labelKey: 'admin.layout.menu.geoDistricts',
        icon: 'geo',
      },
    ],
  },
  {
    key: 'organization',
    labelKey: 'admin.layout.sections.organization',
    icon: 'organization',
    items: [
      {
        key: 'organizations',
        to: '/admin/organizations',
        labelKey: 'admin.layout.menu.organizations',
        icon: 'organizations',
      },
      {
        key: 'children',
        to: '/admin/children',
        labelKey: 'admin.layout.menu.children',
        icon: 'children',
      },
    ],
  },
];
