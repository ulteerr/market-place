import {
  upsertAsyncSelectOptions,
  useAsyncSelectOptionCache,
  useDebouncedSearch,
  type AsyncSelectOption,
} from '~/composables/useAsyncSelectOptions';
import { toPublicCitySetting, type PublicGeoCity } from '~/composables/usePublicGeoCities';

export interface PublicCitySelectOption extends AsyncSelectOption {
  city: ReturnType<typeof toPublicCitySetting>;
}

const CITY_BANNER_DISMISSED_STORAGE_KEY = 'marketplace-public-city-banner-dismissed';

export const usePublicCitySelection = () => {
  const publicGeoCitiesApi = usePublicGeoCities();
  const { selectedCity, selectedCityId, isManualCity, setSelectedCity, applyDetectedCity } =
    usePublicCity();
  const optionCache = useAsyncSelectOptionCache<PublicCitySelectOption>();
  const searchDebounce = useDebouncedSearch(180);
  const pickerOpen = ref(false);
  const pickerOptions = ref<PublicCitySelectOption[]>([]);
  const pickerPending = ref(false);
  const pickerLoadFailed = ref(false);
  const detectionPending = ref(false);
  const detectionAttempted = ref(false);
  const detectionResolvedBy = ref<'ip' | 'fallback' | null>(null);
  const dismissedBannerCityId = ref('');
  let lastOptionsRequestId = 0;
  const canUseBrowserStorage = () =>
    typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';

  const mapCityToOption = (city: PublicGeoCity): PublicCitySelectOption => ({
    value: city.id,
    label: city.name,
    city: toPublicCitySetting(city, 'manual'),
  });

  const createOptionFromSelectedCity = (): PublicCitySelectOption | null => {
    if (!selectedCity.value) {
      return null;
    }

    return {
      value: selectedCity.value.city_id,
      label: selectedCity.value.city_name,
      city: {
        ...selectedCity.value,
        source: 'manual',
      },
    };
  };

  const persistDismissedBannerCityId = (cityId: string) => {
    if (!canUseBrowserStorage()) {
      return;
    }

    try {
      if (cityId) {
        window.localStorage.setItem(CITY_BANNER_DISMISSED_STORAGE_KEY, cityId);
        return;
      }

      window.localStorage.removeItem(CITY_BANNER_DISMISSED_STORAGE_KEY);
    } catch {
      // ignore storage failures for non-critical UI state
    }
  };

  const syncSelectedCityOption = () => {
    const option = createOptionFromSelectedCity();
    if (!option) {
      return;
    }

    optionCache.putMany([option]);
    pickerOptions.value = upsertAsyncSelectOptions(pickerOptions.value, [option]);
  };

  const loadPickerOptions = async (search = '') => {
    const requestId = ++lastOptionsRequestId;

    pickerPending.value = true;
    pickerLoadFailed.value = false;

    try {
      const items = await publicGeoCitiesApi.list({
        search,
        limit: 20,
      });

      if (requestId !== lastOptionsRequestId) {
        return;
      }

      const options = items.map(mapCityToOption);
      optionCache.putMany(options);
      pickerOptions.value = options;
      syncSelectedCityOption();
    } catch {
      if (requestId !== lastOptionsRequestId) {
        return;
      }

      pickerLoadFailed.value = true;
      syncSelectedCityOption();
    } finally {
      if (requestId === lastOptionsRequestId) {
        pickerPending.value = false;
      }
    }
  };

  const schedulePickerSearch = (search: string) => {
    searchDebounce.schedule(() => {
      void loadPickerOptions(search);
    });
  };

  const openPicker = () => {
    pickerOpen.value = true;
    syncSelectedCityOption();
    void loadPickerOptions('');
  };

  const closePicker = () => {
    pickerOpen.value = false;
  };

  const selectManualCityByValue = (value: string | number | string[] | number[] | null) => {
    const rawValue = Array.isArray(value) ? value[0] : value;
    const normalizedValue =
      typeof rawValue === 'number' ? String(rawValue) : (rawValue ?? '').trim();
    if (!normalizedValue) {
      return;
    }

    const option =
      pickerOptions.value.find((item) => item.value === normalizedValue) ??
      optionCache.get(normalizedValue);

    if (!option) {
      return;
    }

    setSelectedCity(option.city);
    dismissedBannerCityId.value = '';
    persistDismissedBannerCityId('');
    syncSelectedCityOption();
    closePicker();
  };

  const dismissDetectedBanner = () => {
    if (!selectedCityId.value) {
      return;
    }

    dismissedBannerCityId.value = selectedCityId.value;
    persistDismissedBannerCityId(selectedCityId.value);
  };

  const detectAutoCity = async () => {
    if (typeof window === 'undefined' || detectionPending.value || isManualCity.value) {
      return;
    }

    detectionPending.value = true;

    try {
      const result = await publicGeoCitiesApi.detect();
      detectionResolvedBy.value = result.resolved_by;
      applyDetectedCity(toPublicCitySetting(result.city, 'ip_auto'));
      optionCache.putMany([mapCityToOption(result.city)]);
      syncSelectedCityOption();
    } catch {
      detectionResolvedBy.value = null;
    } finally {
      detectionAttempted.value = true;
      detectionPending.value = false;
    }
  };

  const showDetectedBanner = computed(
    () =>
      detectionAttempted.value &&
      detectionResolvedBy.value !== null &&
      selectedCity.value?.source === 'ip_auto' &&
      dismissedBannerCityId.value !== selectedCityId.value
  );

  watch(
    selectedCityId,
    () => {
      syncSelectedCityOption();
    },
    { immediate: true }
  );

  onMounted(() => {
    if (!canUseBrowserStorage()) {
      return;
    }

    try {
      dismissedBannerCityId.value =
        window.localStorage.getItem(CITY_BANNER_DISMISSED_STORAGE_KEY) ?? '';
    } catch {
      dismissedBannerCityId.value = '';
    }

    syncSelectedCityOption();
  });

  onBeforeUnmount(() => {
    searchDebounce.clear();
  });

  return {
    pickerOpen,
    pickerOptions,
    pickerPending,
    pickerLoadFailed,
    detectionPending,
    showDetectedBanner,
    openPicker,
    closePicker,
    loadPickerOptions,
    schedulePickerSearch,
    selectManualCityByValue,
    dismissDetectedBanner,
    detectAutoCity,
  };
};
