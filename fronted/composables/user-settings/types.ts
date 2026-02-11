export type ThemeMode = 'light' | 'dark';
export type AdminCrudContentMode = 'table' | 'table-cards' | 'cards';
export type LocaleCode = 'ru' | 'en';

export interface AdminCrudPreference {
  contentMode?: AdminCrudContentMode;
  tableOnDesktop?: boolean;
}

export interface UserSettings {
  locale: LocaleCode | null;
  theme: ThemeMode;
  collapse_menu: boolean;
  admin_crud_preferences: Record<string, AdminCrudPreference>;
}
