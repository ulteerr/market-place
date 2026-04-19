import type { PublicCitySetting } from '~/composables/user-settings/types';

export const usePublicCity = () => {
  const { settings, updateSettings } = useUserSettings();

  const selectedCity = computed<PublicCitySetting | null>(() => settings.value.public_city ?? null);
  const selectedCityId = computed(() => selectedCity.value?.city_id ?? '');
  const selectedCityLabel = computed(() => selectedCity.value?.city_name ?? '');
  const hasSelectedCity = computed(() => selectedCity.value !== null);
  const isManualCity = computed(() => selectedCity.value?.source === 'manual');

  const setSelectedCity = (city: PublicCitySetting) => {
    updateSettings({
      public_city: {
        ...city,
        source: 'manual',
      },
    });
  };

  const applyDetectedCity = (city: PublicCitySetting) => {
    if (isManualCity.value) {
      return;
    }

    updateSettings({
      public_city: {
        ...city,
        source: 'ip_auto',
      },
    });
  };

  const clearSelectedCity = () => {
    updateSettings({
      public_city: null,
    });
  };

  return {
    selectedCity,
    selectedCityId,
    selectedCityLabel,
    hasSelectedCity,
    isManualCity,
    setSelectedCity,
    applyDetectedCity,
    clearSelectedCity,
  };
};
