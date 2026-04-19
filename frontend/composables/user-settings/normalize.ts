import { DEFAULT_COLLAPSE_MENU, DEFAULT_THEME } from '~/composables/user-settings/constants';
import type {
  AdminCrudContentMode,
  AdminCrudPreference,
  AdminNavigationSectionPreference,
  LocaleCode,
  PublicCitySetting,
  PublicCitySource,
  ThemeMode,
  UserSettings,
} from '~/composables/user-settings/types';

export const isThemeMode = (value: unknown): value is ThemeMode =>
  value === 'light' || value === 'dark';
export const isLocaleCode = (value: unknown): value is LocaleCode =>
  value === 'ru' || value === 'en';
export const isPublicCitySource = (value: unknown): value is PublicCitySource =>
  value === 'ip_auto' || value === 'manual';

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

export const normalizeAdminNavigationSections = (
  value: unknown
): Record<string, AdminNavigationSectionPreference> => {
  if (typeof value !== 'object' || value === null) {
    return {};
  }

  const source = value as Record<string, unknown>;
  const normalized: Record<string, AdminNavigationSectionPreference> = {};

  for (const [key, section] of Object.entries(source)) {
    if (typeof section !== 'object' || section === null) {
      continue;
    }

    const candidate = section as Record<string, unknown>;
    const next: AdminNavigationSectionPreference = {};

    if (typeof candidate.open === 'boolean') {
      next.open = candidate.open;
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

export const normalizeFavoriteKeys = (value: unknown): string[] => {
  if (!Array.isArray(value)) {
    return [];
  }

  return Array.from(
    new Set(
      value
        .map((item) => (typeof item === 'string' ? item.trim() : ''))
        .filter((item) => item !== '')
    )
  );
};

export const normalizePublicCity = (value: unknown): PublicCitySetting | null => {
  if (value === null || value === undefined) {
    return null;
  }

  if (typeof value !== 'object' || Array.isArray(value)) {
    return null;
  }

  const source = value as Record<string, unknown>;
  const cityId = typeof source.city_id === 'string' ? source.city_id.trim() : '';
  const cityName = typeof source.city_name === 'string' ? source.city_name.trim() : '';
  const countryId = typeof source.country_id === 'string' ? source.country_id.trim() : '';
  const countryName = typeof source.country_name === 'string' ? source.country_name.trim() : '';
  const regionIdRaw = typeof source.region_id === 'string' ? source.region_id.trim() : '';
  const regionNameRaw = typeof source.region_name === 'string' ? source.region_name.trim() : '';

  if (
    cityId === '' ||
    cityName === '' ||
    countryId === '' ||
    countryName === '' ||
    !isPublicCitySource(source.source)
  ) {
    return null;
  }

  return {
    city_id: cityId,
    city_name: cityName,
    source: source.source,
    region_id: regionIdRaw || null,
    region_name: regionNameRaw || null,
    country_id: countryId,
    country_name: countryName,
  };
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
  favorites: normalizeFavoriteKeys(remoteSettings?.favorites),
  public_city: normalizePublicCity(remoteSettings?.public_city),
  admin_crud_preferences: normalizeAdminCrudPreferences(remoteSettings?.admin_crud_preferences),
  admin_navigation_sections: normalizeAdminNavigationSections(
    remoteSettings?.admin_navigation_sections
  ),
});

export const mergeIncomingSettings = (current: UserSettings, remote: unknown): UserSettings => {
  const payload = (remote ?? {}) as Partial<UserSettings>;

  return {
    locale: isLocaleCode(payload.locale) ? payload.locale : current.locale,
    theme: isThemeMode(payload.theme) ? payload.theme : current.theme,
    collapse_menu: resolveCollapseMenu(payload.collapse_menu) ?? current.collapse_menu,
    favorites: payload.favorites ? normalizeFavoriteKeys(payload.favorites) : current.favorites,
    public_city:
      payload.public_city === null
        ? null
        : payload.public_city !== undefined
          ? (normalizePublicCity(payload.public_city) ?? current.public_city)
          : current.public_city,
    admin_crud_preferences: {
      ...current.admin_crud_preferences,
      ...normalizeAdminCrudPreferences(payload.admin_crud_preferences),
    },
    admin_navigation_sections: {
      ...current.admin_navigation_sections,
      ...normalizeAdminNavigationSections(payload.admin_navigation_sections),
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
  const nextFavorites = patch.favorites
    ? normalizeFavoriteKeys(patch.favorites)
    : current.favorites;
  const nextPublicCity =
    patch.public_city === null
      ? null
      : patch.public_city !== undefined
        ? (normalizePublicCity(patch.public_city) ?? current.public_city)
        : current.public_city;

  const nextCrud = patch.admin_crud_preferences
    ? {
        ...current.admin_crud_preferences,
        ...normalizeAdminCrudPreferences(patch.admin_crud_preferences),
      }
    : current.admin_crud_preferences;
  const nextNavigationSections = patch.admin_navigation_sections
    ? {
        ...current.admin_navigation_sections,
        ...normalizeAdminNavigationSections(patch.admin_navigation_sections),
      }
    : current.admin_navigation_sections;

  return {
    locale: nextLocale,
    theme: nextTheme,
    collapse_menu: nextCollapseMenu,
    favorites: nextFavorites,
    public_city: nextPublicCity,
    admin_crud_preferences: nextCrud,
    admin_navigation_sections: nextNavigationSections,
  };
};
