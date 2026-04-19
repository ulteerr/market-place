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

const detectMock = vi.fn();
const listMock = vi.fn();
const localStorageState = new Map<string, string>();

import { usePublicCitySelection } from '~/composables/usePublicCitySelection';

describe('usePublicCitySelection', () => {
  beforeEach(() => {
    vi.stubGlobal('ref', ref);
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('watch', (source: { value?: unknown } | (() => unknown), callback: Function) => {
      const value = typeof source === 'function' ? source() : source?.value;
      callback(value, undefined);
      return () => {};
    });
    vi.stubGlobal('onMounted', (callback: () => void) => callback());
    vi.stubGlobal('onBeforeUnmount', () => {});
    vi.stubGlobal('window', {
      localStorage: {
        getItem: (key: string) => localStorageState.get(key) ?? null,
        setItem: (key: string, value: string) => {
          localStorageState.set(key, value);
        },
        removeItem: (key: string) => {
          localStorageState.delete(key);
        },
        clear: () => {
          localStorageState.clear();
        },
      },
    });
    vi.stubGlobal('usePublicCity', () => ({
      selectedCity: computed(() => settings.value.public_city),
      selectedCityId: computed(() => settings.value.public_city?.city_id ?? ''),
      selectedCityLabel: computed(() => settings.value.public_city?.city_name ?? ''),
      hasSelectedCity: computed(() => settings.value.public_city !== null),
      isManualCity: computed(() => settings.value.public_city?.source === 'manual'),
      setSelectedCity: (city: NonNullable<typeof settings.value.public_city>) => {
        settings.value = {
          ...settings.value,
          public_city: {
            ...city,
            source: 'manual',
          },
        };
      },
      applyDetectedCity: (city: NonNullable<typeof settings.value.public_city>) => {
        if (settings.value.public_city?.source === 'manual') {
          return;
        }

        settings.value = {
          ...settings.value,
          public_city: {
            ...city,
            source: 'ip_auto',
          },
        };
      },
      clearSelectedCity: () => {
        settings.value = {
          ...settings.value,
          public_city: null,
        };
      },
    }));
    vi.stubGlobal('usePublicGeoCities', () => ({
      detect: detectMock,
      list: listMock,
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

    detectMock.mockReset();
    listMock.mockReset();
    localStorageState.clear();
  });

  afterEach(() => {
    vi.clearAllMocks();
    vi.unstubAllGlobals();
  });

  it('applies detected city and shows confirmation prompt for ip match', async () => {
    detectMock.mockResolvedValue({
      resolved_by: 'ip',
      city: {
        id: 'city-1',
        name: 'Москва',
        country_id: 'country-1',
        region_id: 'region-1',
        country: {
          name: 'Россия',
        },
        region: {
          name: 'Москва',
        },
      },
    });
    listMock.mockResolvedValue([]);

    const selection = usePublicCitySelection();
    await selection.detectAutoCity();

    expect(settings.value.public_city?.city_id).toBe('city-1');
    expect(settings.value.public_city?.source).toBe('ip_auto');
    expect(selection.showDetectedBanner.value).toBe(true);

    selection.dismissDetectedBanner();

    expect(localStorageState.get('marketplace-public-city-banner-dismissed')).toBe('city-1');
    expect(selection.showDetectedBanner.value).toBe(false);
  });

  it('shows confirmation prompt for fallback city default', async () => {
    detectMock.mockResolvedValue({
      resolved_by: 'fallback',
      city: {
        id: 'city-1',
        name: 'Москва',
        country_id: 'country-1',
        region_id: 'region-1',
        country: {
          name: 'Россия',
        },
        region: {
          name: 'Москва',
        },
      },
    });
    listMock.mockResolvedValue([]);

    const selection = usePublicCitySelection();
    await selection.detectAutoCity();

    expect(settings.value.public_city?.city_id).toBe('city-1');
    expect(settings.value.public_city?.source).toBe('ip_auto');
    expect(selection.showDetectedBanner.value).toBe(true);
  });

  it('does not overwrite manual city with detected value', async () => {
    settings.value = {
      ...settings.value,
      public_city: {
        city_id: 'city-manual',
        city_name: 'Казань',
        source: 'manual',
        region_id: 'region-1',
        region_name: 'Татарстан',
        country_id: 'country-1',
        country_name: 'Россия',
      },
    };

    const selection = usePublicCitySelection();
    await selection.detectAutoCity();

    expect(detectMock).not.toHaveBeenCalled();
    expect(settings.value.public_city?.city_id).toBe('city-manual');
    expect(settings.value.public_city?.source).toBe('manual');
  });

  it('loads picker options and stores manual city selection', async () => {
    listMock.mockResolvedValue([
      {
        id: 'city-2',
        name: 'Екатеринбург',
        country_id: 'country-1',
        region_id: 'region-2',
        country: {
          name: 'Россия',
        },
        region: {
          name: 'Свердловская область',
        },
      },
    ]);
    detectMock.mockResolvedValue({
      resolved_by: 'fallback',
      city: {
        id: 'city-1',
        name: 'Москва',
        country_id: 'country-1',
        region_id: 'region-1',
        country: {
          name: 'Россия',
        },
        region: {
          name: 'Москва',
        },
      },
    });

    const selection = usePublicCitySelection();
    await selection.loadPickerOptions('Екат');
    selection.selectManualCityByValue('city-2');

    expect(listMock).toHaveBeenCalledWith({
      search: 'Екат',
      limit: 20,
    });
    expect(settings.value.public_city?.city_id).toBe('city-2');
    expect(settings.value.public_city?.city_name).toBe('Екатеринбург');
    expect(settings.value.public_city?.source).toBe('manual');
    expect(selection.pickerOpen.value).toBe(false);
  });
});
