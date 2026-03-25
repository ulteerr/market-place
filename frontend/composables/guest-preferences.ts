import { mergeIncomingSettings, mergeSettings } from '~/composables/user-settings/normalize';
import type { UserSettings } from '~/composables/user-settings/types';

export const GUEST_PREFERENCES_COOKIE_KEY = 'guest_preferences';

export interface GuestPreferences {
  v: 1;
  settings?: Partial<UserSettings>;
  favorites?: string[];
}

const guestCookieMaxAge = 60 * 60 * 24 * 365;

const guestCookieOptions = {
  sameSite: 'lax' as const,
  maxAge: guestCookieMaxAge,
  secure: process.env.NODE_ENV === 'production',
};

const encodeBase64Url = (value: string): string => {
  if (typeof Buffer !== 'undefined') {
    return Buffer.from(value, 'utf8').toString('base64url');
  }

  const encoded = btoa(unescape(encodeURIComponent(value)));

  return encoded.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
};

const decodeBase64Url = (value: string): string => {
  if (typeof Buffer !== 'undefined') {
    return Buffer.from(value, 'base64url').toString('utf8');
  }

  const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
  const padded = normalized.padEnd(normalized.length + ((4 - (normalized.length % 4)) % 4), '=');

  return decodeURIComponent(escape(atob(padded)));
};

const normalizeFavorites = (value: unknown): string[] => {
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

export const normalizeGuestPreferences = (value: unknown): GuestPreferences => {
  if (typeof value !== 'object' || value === null) {
    return { v: 1, favorites: [] };
  }

  const source = value as Record<string, unknown>;
  const settings = source.settings
    ? mergeSettings(source.settings as Partial<UserSettings>)
    : undefined;
  const favorites = normalizeFavorites(source.favorites);

  return {
    v: 1,
    ...(settings ? { settings } : {}),
    ...(favorites.length > 0 ? { favorites } : {}),
  };
};

export const serializeGuestPreferences = (value: GuestPreferences): string => {
  return encodeBase64Url(JSON.stringify(normalizeGuestPreferences(value)));
};

export const deserializeGuestPreferences = (value: string | null | undefined): GuestPreferences => {
  if (!value) {
    return { v: 1, favorites: [] };
  }

  try {
    return normalizeGuestPreferences(JSON.parse(decodeBase64Url(value)));
  } catch {
    return { v: 1, favorites: [] };
  }
};

export const mergeGuestSettingsIntoAccountSettings = (
  accountSettings: Partial<UserSettings> | null | undefined,
  guestSettings: Partial<UserSettings> | null | undefined
): UserSettings => {
  const normalizedAccount = mergeSettings(accountSettings ?? null);

  if (!guestSettings) {
    return normalizedAccount;
  }

  return mergeIncomingSettings(normalizedAccount, guestSettings);
};

export const useGuestPreferencesCookie = () =>
  useCookie<string | null>(GUEST_PREFERENCES_COOKIE_KEY, guestCookieOptions);

export const readGuestPreferences = (): GuestPreferences => {
  return deserializeGuestPreferences(useGuestPreferencesCookie().value);
};

export const writeGuestPreferences = (value: GuestPreferences | null): void => {
  const cookie = useGuestPreferencesCookie();

  if (!value) {
    cookie.value = null;
    return;
  }

  const normalized = normalizeGuestPreferences(value);
  const hasSettings = Boolean(normalized.settings);
  const hasFavorites = (normalized.favorites?.length ?? 0) > 0;

  cookie.value = hasSettings || hasFavorites ? serializeGuestPreferences(normalized) : null;
};

export const clearGuestPreferences = (): void => {
  writeGuestPreferences(null);
};
