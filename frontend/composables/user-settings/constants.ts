import type { UserSettings } from '~/composables/user-settings/types';

export const DEFAULT_THEME = 'light' as const;
export const DEFAULT_COLLAPSE_MENU = false;
export const SETTINGS_SYNC_DEBOUNCE_MS = 900;
export const SETTINGS_FALLBACK_POLL_INTERVAL_MS = 15_000;
export const SETTINGS_FALLBACK_POLL_MAX_BACKOFF_MS = 120_000;

export const createDefaultSettings = (): UserSettings => ({
  locale: null,
  theme: DEFAULT_THEME,
  collapse_menu: DEFAULT_COLLAPSE_MENU,
  admin_crud_preferences: {},
  admin_navigation_sections: {},
});
