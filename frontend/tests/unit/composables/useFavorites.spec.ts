import { computed, ref } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const isAuthenticated = ref(false);
const settings = ref({
  locale: null,
  theme: 'light' as const,
  collapse_menu: false,
  favorites: [] as string[],
  admin_crud_preferences: {},
  admin_navigation_sections: {},
});
const guestPreferences = ref<{ favorites?: string[]; settings?: Record<string, unknown> }>({
  favorites: [],
});

vi.mock('~/composables/guest-preferences', () => ({
  readGuestPreferences: () => guestPreferences.value,
  writeGuestPreferences: (next: typeof guestPreferences.value) => {
    guestPreferences.value = next ?? { favorites: [] };
  },
}));

import { useFavorites } from '~/composables/useFavorites';

describe('useFavorites', () => {
  beforeEach(() => {
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('useAuth', () => ({
      isAuthenticated,
    }));
    vi.stubGlobal('useUserSettings', () => ({
      settings,
      updateSettings: (patch: { favorites?: string[] }) => {
        settings.value = {
          ...settings.value,
          favorites: patch.favorites ?? settings.value.favorites,
        };
      },
    }));

    isAuthenticated.value = false;
    settings.value = {
      locale: null,
      theme: 'light',
      collapse_menu: false,
      favorites: [],
      admin_crud_preferences: {},
      admin_navigation_sections: {},
    };
    guestPreferences.value = { favorites: [] };
  });

  afterEach(() => {
    vi.clearAllMocks();
    vi.unstubAllGlobals();
  });

  it('stores favorites in guest preferences for guests', () => {
    const favorites = useFavorites();
    favorites.toggleFavorite('activity-1');
    favorites.toggleFavorite('activity-2');
    favorites.toggleFavorite('activity-1');

    expect(guestPreferences.value.favorites).toEqual(['activity-2']);
    expect(favorites.favoriteCount.value).toBe(1);
  });

  it('stores favorites in user settings for authenticated users', () => {
    isAuthenticated.value = true;

    const favorites = useFavorites();
    favorites.toggleFavorite('activity-1');
    favorites.toggleFavorite('activity-2');

    expect(settings.value.favorites).toEqual(['activity-1', 'activity-2']);
    expect(favorites.isFavorite('activity-2')).toBe(true);
  });
});
