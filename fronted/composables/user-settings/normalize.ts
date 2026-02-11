import { DEFAULT_COLLAPSE_MENU, DEFAULT_THEME } from '~/composables/user-settings/constants';
import type {
  AdminCrudContentMode,
  AdminCrudPreference,
  LocaleCode,
  ThemeMode,
  UserSettings,
} from '~/composables/user-settings/types';

export const isThemeMode = (value: unknown): value is ThemeMode =>
  value === 'light' || value === 'dark';
export const isLocaleCode = (value: unknown): value is LocaleCode =>
  value === 'ru' || value === 'en';

export const isContentMode = (value: unknown): value is AdminCrudContentMode => {
  return value === 'table' || value === 'table-cards' || value === 'cards';
};

export const normalizeAdminCrudPreferences = (
  value: unknown
): Record<string, AdminCrudPreference> => {
  if (typeof value !== 'object' || value === null) {
    return {};
  }

  const source = value as Record<string, unknown>;
  const normalized: Record<string, AdminCrudPreference> = {};

  for (const [key, preference] of Object.entries(source)) {
    if (typeof preference !== 'object' || preference === null) {
      continue;
    }

    const candidate = preference as Record<string, unknown>;
    const next: AdminCrudPreference = {};

    if (isContentMode(candidate.contentMode)) {
      next.contentMode = candidate.contentMode;
    }

    if (typeof candidate.tableOnDesktop === 'boolean') {
      next.tableOnDesktop = candidate.tableOnDesktop;
    }

    if (Object.keys(next).length > 0) {
      normalized[key] = next;
    }
  }

  return normalized;
};

export const resolveCollapseMenu = (value: unknown): boolean | undefined => {
  return typeof value === 'boolean' ? value : undefined;
};

export const getSystemTheme = (): ThemeMode => {
  if (!process.client || !window.matchMedia) {
    return DEFAULT_THEME;
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

export const mergeSettings = (remoteSettings: Partial<UserSettings> | null): UserSettings => ({
  locale: isLocaleCode(remoteSettings?.locale) ? remoteSettings.locale : null,
  theme: isThemeMode(remoteSettings?.theme) ? remoteSettings.theme : getSystemTheme(),
  collapse_menu: resolveCollapseMenu(remoteSettings?.collapse_menu) ?? DEFAULT_COLLAPSE_MENU,
  admin_crud_preferences: normalizeAdminCrudPreferences(remoteSettings?.admin_crud_preferences),
});

export const mergeIncomingSettings = (current: UserSettings, remote: unknown): UserSettings => {
  const payload = (remote ?? {}) as Partial<UserSettings>;

  return {
    locale: isLocaleCode(payload.locale) ? payload.locale : current.locale,
    theme: isThemeMode(payload.theme) ? payload.theme : current.theme,
    collapse_menu: resolveCollapseMenu(payload.collapse_menu) ?? current.collapse_menu,
    admin_crud_preferences: {
      ...current.admin_crud_preferences,
      ...normalizeAdminCrudPreferences(payload.admin_crud_preferences),
    },
  };
};

export const mergePatchWithSettings = (
  current: UserSettings,
  patch: Partial<UserSettings>
): UserSettings => {
  const nextLocale =
    patch.locale === null ? null : isLocaleCode(patch.locale) ? patch.locale : current.locale;
  const nextTheme = isThemeMode(patch.theme) ? patch.theme : current.theme;
  const nextCollapseMenu = resolveCollapseMenu(patch.collapse_menu) ?? current.collapse_menu;

  const nextCrud = patch.admin_crud_preferences
    ? {
        ...current.admin_crud_preferences,
        ...normalizeAdminCrudPreferences(patch.admin_crud_preferences),
      }
    : current.admin_crud_preferences;

  return {
    locale: nextLocale,
    theme: nextTheme,
    collapse_menu: nextCollapseMenu,
    admin_crud_preferences: nextCrud,
  };
};
