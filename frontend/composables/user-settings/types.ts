export type ThemeMode = 'light' | 'dark';
export type AdminCrudContentMode = 'table' | 'table-cards' | 'cards';
export type LocaleCode = 'ru' | 'en';
export type PublicCitySource = 'ip_auto' | 'manual';

export interface AdminCrudPreference {
  contentMode?: AdminCrudContentMode;
  tableOnDesktop?: boolean;
}

export interface AdminNavigationSectionPreference {
  open?: boolean;
}

export interface PublicCitySetting {
  city_id: string;
  city_name: string;
  source: PublicCitySource;
  region_id: string | null;
  region_name: string | null;
  country_id: string;
  country_name: string;
}

export interface UserSettings {
  locale: LocaleCode | null;
  theme: ThemeMode;
  collapse_menu: boolean;
  favorites: string[];
  public_city: PublicCitySetting | null;
  admin_crud_preferences: Record<string, AdminCrudPreference>;
  admin_navigation_sections: Record<string, AdminNavigationSectionPreference>;
}
