import { readGuestPreferences, writeGuestPreferences } from '~/composables/guest-preferences';
import { normalizeFavoriteKeys } from '~/composables/user-settings/normalize';

export const useFavorites = () => {
  const { isAuthenticated } = useAuth();
  const { settings, updateSettings } = useUserSettings();

  const favoriteKeys = computed(() => {
    if (isAuthenticated.value) {
      return settings.value.favorites;
    }

    return readGuestPreferences().favorites ?? [];
  });

  const favoriteKeySet = computed(() => new Set(favoriteKeys.value));
  const favoriteCount = computed(() => favoriteKeys.value.length);

  const replaceFavorites = (nextFavorites: string[]) => {
    const normalized = normalizeFavoriteKeys(nextFavorites);

    if (isAuthenticated.value) {
      updateSettings({ favorites: normalized });
      return;
    }

    writeGuestPreferences({
      ...readGuestPreferences(),
      favorites: normalized,
    });
  };

  const isFavorite = (favoriteKey: string | null | undefined): boolean => {
    if (!favoriteKey) {
      return false;
    }

    return favoriteKeySet.value.has(favoriteKey);
  };

  const addFavorite = (favoriteKey: string) => {
    replaceFavorites([...favoriteKeys.value, favoriteKey]);
  };

  const removeFavorite = (favoriteKey: string) => {
    replaceFavorites(favoriteKeys.value.filter((item) => item !== favoriteKey));
  };

  const toggleFavorite = (favoriteKey: string) => {
    if (isFavorite(favoriteKey)) {
      removeFavorite(favoriteKey);
      return false;
    }

    addFavorite(favoriteKey);
    return true;
  };

  return {
    favoriteKeys,
    favoriteCount,
    isFavorite,
    addFavorite,
    removeFavorite,
    replaceFavorites,
    toggleFavorite,
  };
};
