import type { Ref } from 'vue';

type AdminLocale = 'ru' | 'en';

interface UseAdminLocaleSyncOptions {
  locale: Ref<string>;
  setLocale: (locale: AdminLocale) => Promise<void> | void;
  isAuthenticated: Ref<boolean>;
  settings: Ref<{ locale?: string | null }>;
  updateSettings: (payload: Record<string, unknown>) => void;
}

const localeStorageKey = 'preferred_locale';

const isAdminLocale = (value: unknown): value is AdminLocale => value === 'ru' || value === 'en';

export const useAdminLocaleSync = ({
  locale,
  setLocale,
  isAuthenticated,
  settings,
  updateSettings,
}: UseAdminLocaleSyncOptions) => {
  const isApplyingLocaleFromSettings = ref(false);

  const localeSelectOptions = [
    { value: 'ru', label: 'RU' },
    { value: 'en', label: 'EN' },
  ] as const;

  const onLocaleChange = async (value: string | number | (string | number)[]) => {
    const nextLocale = Array.isArray(value) ? value[0] : value;

    if (isAdminLocale(nextLocale)) {
      await setLocale(nextLocale);
    }
  };

  const syncLocaleFromSource = async () => {
    if (!process.client) {
      return;
    }

    if (isAuthenticated.value) {
      window.localStorage.removeItem(localeStorageKey);
      const savedLocale = settings.value.locale;

      if (isAdminLocale(savedLocale)) {
        if (locale.value !== savedLocale) {
          await setLocale(savedLocale);
        }
        return;
      }

      if (isAdminLocale(locale.value)) {
        updateSettings({ locale: locale.value });
      }
      return;
    }

    const storedLocale = window.localStorage.getItem(localeStorageKey);
    if (isAdminLocale(storedLocale)) {
      if (locale.value !== storedLocale) {
        await setLocale(storedLocale);
      }
      return;
    }

    if (isAdminLocale(locale.value)) {
      window.localStorage.setItem(localeStorageKey, locale.value);
    }
  };

  onMounted(() => {
    void syncLocaleFromSource();
  });

  watch(
    () => locale.value,
    (nextLocale) => {
      if (!isAdminLocale(nextLocale)) {
        return;
      }

      if (isAuthenticated.value) {
        if (process.client) {
          window.localStorage.removeItem(localeStorageKey);
        }

        if (isApplyingLocaleFromSettings.value) {
          return;
        }

        if (settings.value.locale !== nextLocale) {
          updateSettings({ locale: nextLocale });
        }

        return;
      }

      if (process.client) {
        window.localStorage.setItem(localeStorageKey, nextLocale);
      }
    }
  );

  watch(
    () => settings.value.locale,
    async (nextLocale) => {
      if (!isAuthenticated.value || !isAdminLocale(nextLocale) || locale.value === nextLocale) {
        return;
      }

      isApplyingLocaleFromSettings.value = true;

      try {
        await setLocale(nextLocale);
      } finally {
        isApplyingLocaleFromSettings.value = false;
      }
    }
  );

  watch(
    () => isAuthenticated.value,
    () => {
      void syncLocaleFromSource();
    }
  );

  return {
    localeSelectOptions,
    onLocaleChange,
  };
};
