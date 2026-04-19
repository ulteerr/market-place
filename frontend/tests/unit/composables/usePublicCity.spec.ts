import { computed, ref } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const settings = ref({
  locale: null as 'ru' | 'en' | null,
  theme: 'light' as const,
  collapse_menu: false,
  favorites: [] as string[],
  public_city: null as null | {
    city_id: string;
    city_name: string;
    source: 'ip_auto' | 'manual';
    region_id: string | null;
    region_name: string | null;
    country_id: string;
    country_name: string;
  },
  admin_crud_preferences: {},
  admin_navigation_sections: {},
});

import { usePublicCity } from '~/composables/usePublicCity';

describe('usePublicCity', () => {
  beforeEach(() => {
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('useUserSettings', () => ({
      settings,
      updateSettings: (patch: { public_city?: typeof settings.value.public_city }) => {
        settings.value = {
          ...settings.value,
          public_city:
            patch.public_city === undefined ? settings.value.public_city : patch.public_city,
        };
      },
    }));

    settings.value = {
      locale: null,
      theme: 'light',
      collapse_menu: false,
      favorites: [],
      public_city: null,
      admin_crud_preferences: {},
      admin_navigation_sections: {},
    };
  });

  afterEach(() => {
    vi.clearAllMocks();
    vi.unstubAllGlobals();
  });

  it('stores selected city as manual choice', () => {
    const publicCity = usePublicCity();

    publicCity.setSelectedCity({
      city_id: 'city-1',
      city_name: 'Moscow',
      source: 'ip_auto',
      region_id: 'region-1',
      region_name: 'Moscow',
      country_id: 'country-1',
      country_name: 'Russia',
    });

    expect(publicCity.selectedCityId.value).toBe('city-1');
    expect(publicCity.selectedCityLabel.value).toBe('Moscow');
    expect(settings.value.public_city?.source).toBe('manual');
    expect(publicCity.isManualCity.value).toBe(true);
  });

  it('applies detected city only when there is no manual override', () => {
    const publicCity = usePublicCity();

    publicCity.applyDetectedCity({
      city_id: 'city-2',
      city_name: 'Yekaterinburg',
      source: 'manual',
      region_id: 'region-2',
      region_name: 'Sverdlovsk Oblast',
      country_id: 'country-1',
      country_name: 'Russia',
    });

    expect(settings.value.public_city?.city_id).toBe('city-2');
    expect(settings.value.public_city?.source).toBe('ip_auto');

    publicCity.setSelectedCity({
      city_id: 'city-3',
      city_name: 'Kazan',
      source: 'manual',
      region_id: null,
      region_name: null,
      country_id: 'country-1',
      country_name: 'Russia',
    });

    publicCity.applyDetectedCity({
      city_id: 'city-4',
      city_name: 'Sochi',
      source: 'ip_auto',
      region_id: null,
      region_name: null,
      country_id: 'country-1',
      country_name: 'Russia',
    });

    expect(settings.value.public_city?.city_id).toBe('city-3');
    expect(settings.value.public_city?.source).toBe('manual');
  });

  it('clears selected city', () => {
    const publicCity = usePublicCity();

    publicCity.setSelectedCity({
      city_id: 'city-1',
      city_name: 'Moscow',
      source: 'manual',
      region_id: 'region-1',
      region_name: 'Moscow',
      country_id: 'country-1',
      country_name: 'Russia',
    });

    publicCity.clearSelectedCity();

    expect(publicCity.selectedCity.value).toBeNull();
    expect(publicCity.hasSelectedCity.value).toBe(false);
  });
});
