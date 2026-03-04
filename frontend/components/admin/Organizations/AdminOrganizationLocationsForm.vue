<template>
  <section class="space-y-4">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h3 class="text-base font-semibold">{{ t('admin.organizations.locations.title') }}</h3>
        <p class="admin-muted text-sm">{{ t('admin.organizations.locations.subtitle') }}</p>
      </div>
      <button
        type="button"
        class="admin-button-secondary rounded-lg px-3 py-2 text-sm"
        :disabled="disabled"
        @click="addLocation"
      >
        {{ t('admin.organizations.locations.addLocation') }}
      </button>
    </div>

    <div v-if="!model.length" class="admin-muted rounded-xl border border-dashed px-4 py-3 text-sm">
      {{ t('admin.organizations.locations.empty') }}
    </div>

    <article
      v-for="(location, locationIndex) in model"
      :key="location._local_key || location.id || `location-${locationIndex}`"
      class="rounded-2xl border border-[color:var(--admin-border)] p-4"
    >
      <div class="mb-4 flex items-center justify-between gap-3">
        <h4 class="text-sm font-semibold">
          {{ t('admin.organizations.locations.locationTitle', { index: locationIndex + 1 }) }}
        </h4>
        <button
          type="button"
          class="admin-button-secondary rounded-lg px-3 py-2 text-xs"
          :disabled="disabled"
          @click="removeLocation(locationIndex)"
        >
          {{ t('admin.organizations.locations.removeLocation') }}
        </button>
      </div>

      <div class="space-y-3">
        <UiSelect
          v-model="location.country_id"
          :label="t('admin.organizations.fields.country')"
          :options="countryOptions"
          :placeholder="t('admin.organizations.locations.countryPlaceholder')"
          searchable
          :disabled="disabled"
          :error="resolveError(`locations.${locationIndex}.country_id`)"
          @search="onCountrySearch"
          @update:model-value="() => onCountryChanged(locationIndex)"
        />

        <UiSelect
          v-model="location.region_id"
          :label="t('admin.organizations.fields.region')"
          :options="regionOptions[locationIndex] || []"
          :placeholder="t('admin.organizations.locations.regionPlaceholder')"
          searchable
          :disabled="disabled || !location.country_id"
          :error="resolveError(`locations.${locationIndex}.region_id`)"
          @search="(query) => onRegionSearch(locationIndex, query)"
          @update:model-value="() => onRegionChanged(locationIndex)"
        />

        <UiSelect
          v-model="location.city_id"
          :label="t('admin.organizations.fields.city')"
          :options="cityOptions[locationIndex] || []"
          :placeholder="t('admin.organizations.locations.cityPlaceholder')"
          searchable
          :disabled="disabled || !location.region_id"
          :error="resolveError(`locations.${locationIndex}.city_id`)"
          @search="(query) => onCitySearch(locationIndex, query)"
          @update:model-value="() => onCityChanged(locationIndex)"
        />

        <UiSelect
          v-model="location.district_id"
          :label="t('admin.organizations.fields.district')"
          :options="districtOptions[locationIndex] || []"
          :placeholder="t('admin.organizations.locations.districtPlaceholder')"
          searchable
          :disabled="disabled || !location.city_id"
          :error="resolveError(`locations.${locationIndex}.district_id`)"
          @search="(query) => onDistrictSearch(locationIndex, query)"
        />

        <UiInput
          v-model="location.address"
          :label="t('admin.organizations.fields.address')"
          :disabled="disabled"
          :error="resolveError(`locations.${locationIndex}.address`)"
        />

        <div class="grid gap-3 sm:grid-cols-2">
          <UiInput
            :model-value="location.lat ?? ''"
            :label="t('admin.organizations.fields.lat')"
            preset="number"
            :disabled="disabled"
            :error="resolveError(`locations.${locationIndex}.lat`)"
            @update:model-value="(value) => updateNumericField(locationIndex, 'lat', value)"
          />
          <UiInput
            :model-value="location.lng ?? ''"
            :label="t('admin.organizations.fields.lng')"
            preset="number"
            :disabled="disabled"
            :error="resolveError(`locations.${locationIndex}.lng`)"
            @update:model-value="(value) => updateNumericField(locationIndex, 'lng', value)"
          />
        </div>
      </div>

      <div class="mt-5 space-y-3">
        <div class="flex items-center justify-between gap-3">
          <h5 class="text-sm font-semibold">{{ t('admin.organizations.locations.metroTitle') }}</h5>
          <button
            type="button"
            class="admin-button-secondary rounded-lg px-3 py-2 text-xs"
            :disabled="disabled || !location.city_id"
            @click="addMetroConnection(locationIndex)"
          >
            {{ t('admin.organizations.locations.addMetro') }}
          </button>
        </div>

        <p v-if="!location.city_id" class="admin-muted text-sm">
          {{ t('admin.organizations.locations.metroRequiresCity') }}
        </p>

        <div
          v-for="group in getMetroConnectionGroups(locationIndex)"
          :key="group.key"
          class="rounded-xl border border-[color:var(--admin-border)] p-3"
        >
          <div class="mb-3 flex items-center justify-between gap-3">
            <span class="text-xs font-semibold uppercase tracking-[0.08em]">
              {{ t('admin.organizations.locations.metroItemTitle', { index: group.order }) }}
            </span>
            <button
              type="button"
              class="admin-button-secondary rounded-lg px-3 py-2 text-xs"
              :disabled="disabled"
              @click="removeMetroGroup(locationIndex, group.key)"
            >
              {{ t('admin.organizations.locations.removeMetro') }}
            </button>
          </div>

          <div class="space-y-3">
            <UiSelect
              :model-value="group.metro_station_id"
              :label="t('admin.organizations.fields.metroStation')"
              :options="metroStationOptions[locationIndex] || []"
              :placeholder="t('admin.organizations.locations.metroPlaceholder')"
              searchable
              :disabled="disabled || !location.city_id"
              :error="resolveMetroGroupError(locationIndex, group)"
              @update:model-value="
                (value) => updateMetroStationForGroup(locationIndex, group.key, String(value || ''))
              "
              @search="(query) => onMetroSearch(locationIndex, query)"
            />

            <div class="grid gap-3 sm:grid-cols-2">
              <UiInput
                :model-value="group.walk_duration"
                :label="t('admin.organizations.fields.walkDurationMinutes')"
                preset="number"
                :disabled="disabled"
                :error="resolveMetroGroupDurationError(locationIndex, group, 'walk')"
                @update:model-value="
                  (value) => updateMetroDurationForGroup(locationIndex, group.key, 'walk', value)
                "
              />
              <UiInput
                :model-value="group.drive_duration"
                :label="t('admin.organizations.fields.driveDurationMinutes')"
                preset="number"
                :disabled="disabled"
                :error="resolveMetroGroupDurationError(locationIndex, group, 'drive')"
                @update:model-value="
                  (value) => updateMetroDurationForGroup(locationIndex, group.key, 'drive', value)
                "
              />
            </div>
          </div>
        </div>

        <p v-if="(location.metro_connections || []).length === 0" class="admin-muted text-sm">
          {{ t('admin.organizations.locations.metroEmpty') }}
        </p>
      </div>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import type {
  OrganizationLocationFormValue,
  OrganizationLocationMetroConnectionFormValue,
  OrganizationLocationTravelMode,
} from '~/composables/useAdminOrganizations';
import {
  sortAsyncSelectOptions,
  upsertAsyncSelectOptions,
  useAsyncSelectOptionCache,
  useDebouncedSearch,
  useIndexedDebouncedSearch,
  type AsyncSelectOption,
} from '~/composables/useAsyncSelectOptions';

type SelectOption = AsyncSelectOption;

type MutableLocation = OrganizationLocationFormValue & { _local_key?: string };
type LocalMetroConnection = OrganizationLocationMetroConnectionFormValue & { _group_key?: string };
type LocationHydrationState = {
  country_id: string;
  region_id: string;
  city_id: string;
  district_id: string;
  metro_station_ids: string[];
};
type MetroConnectionGroup = {
  key: string;
  order: number;
  metro_station_id: string;
  walk_duration: number | null | '';
  drive_duration: number | null | '';
  walk_index: number;
  drive_index: number;
};

const model = defineModel<MutableLocation[]>({ required: true });

const props = withDefaults(
  defineProps<{
    disabled?: boolean;
    getError?: (path: string) => string;
  }>(),
  {
    disabled: false,
    getError: undefined,
  }
);

const { t } = useI18n();

const countriesApi = useAdminGeoCountries();
const regionsApi = useAdminGeoRegions();
const citiesApi = useAdminGeoCities();
const districtsApi = useAdminGeoDistricts();
const metroStationsApi = useAdminMetroStations();

const countryOptions = ref<SelectOption[]>([]);
const regionOptions = ref<SelectOption[][]>([]);
const cityOptions = ref<SelectOption[][]>([]);
const districtOptions = ref<SelectOption[][]>([]);
const metroStationOptions = ref<SelectOption[][]>([]);
const countrySearch = useDebouncedSearch(250);
const regionSearch = useIndexedDebouncedSearch(250);
const citySearch = useIndexedDebouncedSearch(250);
const districtSearch = useIndexedDebouncedSearch(250);
const metroSearch = useIndexedDebouncedSearch(250);
const countryOptionCache = useAsyncSelectOptionCache<SelectOption>();
const regionOptionCache = useAsyncSelectOptionCache<SelectOption>();
const cityOptionCache = useAsyncSelectOptionCache<SelectOption>();
const districtOptionCache = useAsyncSelectOptionCache<SelectOption>();
const metroStationOptionCache = useAsyncSelectOptionCache<SelectOption>();

const nextMetroGroupId = ref(0);
const nextLocationId = ref(0);

const createMetroConnectionPair = (groupKey: string): LocalMetroConnection[] => [
  {
    _group_key: groupKey,
    metro_station_id: '',
    travel_mode: 'walk',
    duration_minutes: null,
  },
  {
    _group_key: groupKey,
    metro_station_id: '',
    travel_mode: 'drive',
    duration_minutes: null,
  },
];

const createEmptyLocation = (): MutableLocation => ({
  _local_key: getNewLocationKey(),
  country_id: null,
  region_id: null,
  city_id: null,
  district_id: null,
  address: '',
  lat: null,
  lng: null,
  metro_connections: [],
});

const getNewMetroGroupKey = (): string => {
  nextMetroGroupId.value += 1;
  return `metro-group-${nextMetroGroupId.value}`;
};

const getNewLocationKey = (): string => {
  nextLocationId.value += 1;
  return `location-${nextLocationId.value}`;
};

const ensureLocationKey = (location: MutableLocation): void => {
  if (location._local_key) {
    return;
  }

  const persistedId = String(location.id || '').trim();
  location._local_key = persistedId ? `existing:${persistedId}` : getNewLocationKey();
};

const normalizeLocationKeys = (): void => {
  model.value.forEach((location) => ensureLocationKey(location));
};

const syncOptionBuckets = () => {
  while (regionOptions.value.length < model.value.length) {
    regionOptions.value.push([]);
  }

  while (cityOptions.value.length < model.value.length) {
    cityOptions.value.push([]);
  }

  while (districtOptions.value.length < model.value.length) {
    districtOptions.value.push([]);
  }

  while (metroStationOptions.value.length < model.value.length) {
    metroStationOptions.value.push([]);
  }

  regionOptions.value.length = model.value.length;
  cityOptions.value.length = model.value.length;
  districtOptions.value.length = model.value.length;
  metroStationOptions.value.length = model.value.length;
};

const resolveError = (path: string): string => {
  return props.getError?.(path) || '';
};

const getMetroConnectionGroupKey = (connection: LocalMetroConnection, index: number): string => {
  if (connection._group_key) {
    return connection._group_key;
  }

  const stationId = String(connection.metro_station_id || '').trim();
  if (stationId) {
    return `station:${stationId}`;
  }

  return `index:${index}`;
};

const getLocationMetroConnections = (locationIndex: number): LocalMetroConnection[] => {
  const location = model.value[locationIndex];
  if (!location) {
    return [];
  }

  return (location.metro_connections || []) as LocalMetroConnection[];
};

const getMetroConnectionGroups = (locationIndex: number): MetroConnectionGroup[] => {
  const groups: MetroConnectionGroup[] = [];
  const groupIndexMap = new Map<string, number>();
  const connections = getLocationMetroConnections(locationIndex);

  connections.forEach((connection, index) => {
    const key = getMetroConnectionGroupKey(connection, index);
    const existingIndex = groupIndexMap.get(key);

    if (existingIndex === undefined) {
      groups.push({
        key,
        order: groups.length + 1,
        metro_station_id: String(connection.metro_station_id || ''),
        walk_duration: connection.travel_mode === 'walk' ? (connection.duration_minutes ?? '') : '',
        drive_duration:
          connection.travel_mode === 'drive' ? (connection.duration_minutes ?? '') : '',
        walk_index: connection.travel_mode === 'walk' ? index : -1,
        drive_index: connection.travel_mode === 'drive' ? index : -1,
      });
      groupIndexMap.set(key, groups.length - 1);
      return;
    }

    const group = groups[existingIndex];
    if (!group) {
      return;
    }

    group.metro_station_id = group.metro_station_id || String(connection.metro_station_id || '');
    if (connection.travel_mode === 'walk') {
      group.walk_duration = connection.duration_minutes ?? '';
      group.walk_index = index;
      return;
    }

    group.drive_duration = connection.duration_minutes ?? '';
    group.drive_index = index;
  });

  return groups;
};

const getMetroGroupConnectionIndex = (
  group: MetroConnectionGroup,
  mode: OrganizationLocationTravelMode
): number => {
  return mode === 'walk' ? group.walk_index : group.drive_index;
};

const resolveMetroGroupError = (locationIndex: number, group: MetroConnectionGroup): string => {
  const walkStationError = resolveError(
    `locations.${locationIndex}.metro_connections.${group.walk_index}.metro_station_id`
  );
  if (walkStationError) {
    return walkStationError;
  }

  if (group.drive_index < 0) {
    return '';
  }

  return resolveError(
    `locations.${locationIndex}.metro_connections.${group.drive_index}.metro_station_id`
  );
};

const resolveMetroGroupDurationError = (
  locationIndex: number,
  group: MetroConnectionGroup,
  mode: OrganizationLocationTravelMode
): string => {
  const connectionIndex = getMetroGroupConnectionIndex(group, mode);
  if (connectionIndex < 0) {
    return '';
  }

  return resolveError(
    `locations.${locationIndex}.metro_connections.${connectionIndex}.duration_minutes`
  );
};

const replaceOptions = (incoming: SelectOption[]): SelectOption[] =>
  sortAsyncSelectOptions(incoming);

const buildMetroStationLabel = (item: {
  name: string;
  metro_line?: { name?: string | null } | null;
}): string => {
  const lineName = item.metro_line?.name?.trim();
  return lineName ? `${item.name} (${lineName})` : item.name;
};

const loadCountryOptions = async (search = '') => {
  const response = await countriesApi.list({
    per_page: 50,
    entity_search: search.trim() || undefined,
    sort_by: 'name',
    sort_dir: 'asc',
  });

  const options = response.data.map((item) => ({
    value: item.id,
    label: item.name,
  }));

  countryOptionCache.putMany(options);
  countryOptions.value = replaceOptions(options);
};

const ensureSelectedCountryOption = async (countryId: string | null | undefined) => {
  const value = String(countryId || '').trim();
  if (!value || countryOptions.value.some((option) => option.value === value)) {
    return;
  }

  const cachedOption = countryOptionCache.get(value);
  if (cachedOption) {
    countryOptions.value = upsertAsyncSelectOptions(countryOptions.value, [cachedOption]);
    return;
  }

  try {
    const item = await countriesApi.show(value);
    const option = {
      value: item.id,
      label: item.name,
    };
    countryOptionCache.putMany([option]);
    countryOptions.value = upsertAsyncSelectOptions(countryOptions.value, [option]);
  } catch {
    const option = { value, label: value };
    countryOptionCache.putMany([option]);
    countryOptions.value = upsertAsyncSelectOptions(countryOptions.value, [option]);
  }
};

const loadRegionOptions = async (locationIndex: number, search = '') => {
  const location = model.value[locationIndex];
  const countryId = String(location?.country_id || '').trim();

  if (!countryId) {
    regionOptions.value[locationIndex] = [];
    return;
  }

  const response = await regionsApi.list({
    per_page: 50,
    country_id: countryId,
    entity_search: search.trim() || undefined,
    sort_by: 'name',
    sort_dir: 'asc',
  });

  const options = response.data.map((item) => ({
    value: item.id,
    label: item.name,
  }));

  regionOptionCache.putMany(options);
  regionOptions.value[locationIndex] = replaceOptions(options);
};

const ensureSelectedRegionOption = async (locationIndex: number) => {
  const location = model.value[locationIndex];
  const regionId = String(location?.region_id || '').trim();

  if (!regionId) {
    return;
  }

  if ((regionOptions.value[locationIndex] || []).some((option) => option.value === regionId)) {
    return;
  }
  const cachedOption = regionOptionCache.get(regionId);
  if (cachedOption) {
    regionOptions.value[locationIndex] = upsertAsyncSelectOptions(
      regionOptions.value[locationIndex] || [],
      [cachedOption]
    );
    return;
  }

  try {
    const item = await regionsApi.show(regionId);
    const option = {
      value: item.id,
      label: item.name,
    };
    regionOptionCache.putMany([option]);
    regionOptions.value[locationIndex] = upsertAsyncSelectOptions(
      regionOptions.value[locationIndex] || [],
      [option]
    );
  } catch {
    const option = { value: regionId, label: regionId };
    regionOptionCache.putMany([option]);
    regionOptions.value[locationIndex] = upsertAsyncSelectOptions(
      regionOptions.value[locationIndex] || [],
      [option]
    );
  }
};

const loadCityOptions = async (locationIndex: number, search = '') => {
  const location = model.value[locationIndex];
  const regionId = String(location?.region_id || '').trim();

  if (!regionId) {
    cityOptions.value[locationIndex] = [];
    return;
  }

  const response = await citiesApi.list({
    per_page: 50,
    country_id: String(location?.country_id || '').trim() || undefined,
    region_id: regionId,
    entity_search: search.trim() || undefined,
    sort_by: 'name',
    sort_dir: 'asc',
  });

  const options = response.data.map((item) => ({
    value: item.id,
    label: item.name,
  }));

  cityOptionCache.putMany(options);
  cityOptions.value[locationIndex] = replaceOptions(options);
};

const ensureSelectedCityOption = async (
  locationIndex: number,
  cityId: string | null | undefined
) => {
  const value = String(cityId || '').trim();
  if (!value || (cityOptions.value[locationIndex] || []).some((option) => option.value === value)) {
    return;
  }
  const cachedOption = cityOptionCache.get(value);
  if (cachedOption) {
    cityOptions.value[locationIndex] = upsertAsyncSelectOptions(
      cityOptions.value[locationIndex] || [],
      [cachedOption]
    );
    return;
  }

  try {
    const item = await citiesApi.show(value);
    const option = {
      value: item.id,
      label: item.name,
    };
    cityOptionCache.putMany([option]);
    cityOptions.value[locationIndex] = upsertAsyncSelectOptions(
      cityOptions.value[locationIndex] || [],
      [option]
    );
  } catch {
    const option = { value, label: value };
    cityOptionCache.putMany([option]);
    cityOptions.value[locationIndex] = upsertAsyncSelectOptions(
      cityOptions.value[locationIndex] || [],
      [option]
    );
  }
};

const loadDistrictOptions = async (locationIndex: number, search = '') => {
  const location = model.value[locationIndex];
  const cityId = String(location?.city_id || '').trim();

  if (!cityId) {
    districtOptions.value[locationIndex] = [];
    return;
  }

  const response = await districtsApi.list({
    per_page: 50,
    city_id: cityId,
    entity_search: search.trim() || undefined,
    sort_by: 'name',
    sort_dir: 'asc',
  });

  const options = response.data.map((item) => ({
    value: item.id,
    label: item.name,
  }));

  districtOptionCache.putMany(options);
  districtOptions.value[locationIndex] = replaceOptions(options);
};

const ensureSelectedDistrictOption = async (locationIndex: number) => {
  const location = model.value[locationIndex];
  const districtId = String(location?.district_id || '').trim();

  if (!districtId) {
    return;
  }

  if ((districtOptions.value[locationIndex] || []).some((option) => option.value === districtId)) {
    return;
  }
  const cachedOption = districtOptionCache.get(districtId);
  if (cachedOption) {
    districtOptions.value[locationIndex] = upsertAsyncSelectOptions(
      districtOptions.value[locationIndex] || [],
      [cachedOption]
    );
    return;
  }

  try {
    const item = await districtsApi.show(districtId);
    const option = {
      value: item.id,
      label: item.name,
    };
    districtOptionCache.putMany([option]);
    districtOptions.value[locationIndex] = upsertAsyncSelectOptions(
      districtOptions.value[locationIndex] || [],
      [option]
    );
  } catch {
    const option = { value: districtId, label: districtId };
    districtOptionCache.putMany([option]);
    districtOptions.value[locationIndex] = upsertAsyncSelectOptions(
      districtOptions.value[locationIndex] || [],
      [option]
    );
  }
};

const loadMetroStationOptions = async (locationIndex: number, search = '') => {
  const location = model.value[locationIndex];
  const cityId = String(location?.city_id || '').trim();

  if (!cityId) {
    metroStationOptions.value[locationIndex] = [];
    return;
  }

  const response = await metroStationsApi.list({
    per_page: 50,
    city_id: cityId,
    entity_search: search.trim() || undefined,
    sort_by: 'name',
    sort_dir: 'asc',
  });

  const options = response.data.map((item) => ({
    value: item.id,
    label: buildMetroStationLabel(item),
    color: item.metro_line?.color ?? null,
  }));

  metroStationOptionCache.putMany(options);
  metroStationOptions.value[locationIndex] = replaceOptions(options);

  const selectedMetroOptions = (location?.metro_connections || [])
    .map((connection) =>
      metroStationOptionCache.get(String(connection.metro_station_id || '').trim())
    )
    .filter((option): option is SelectOption => Boolean(option));

  if (selectedMetroOptions.length > 0) {
    metroStationOptions.value[locationIndex] = upsertAsyncSelectOptions(
      metroStationOptions.value[locationIndex] || [],
      selectedMetroOptions
    );
  }
};

const ensureSelectedMetroStationOption = async (locationIndex: number, metroStationId: string) => {
  const value = String(metroStationId || '').trim();
  if (!value) {
    return;
  }

  if ((metroStationOptions.value[locationIndex] || []).some((option) => option.value === value)) {
    return;
  }
  const cachedOption = metroStationOptionCache.get(value);
  if (cachedOption) {
    metroStationOptions.value[locationIndex] = upsertAsyncSelectOptions(
      metroStationOptions.value[locationIndex] || [],
      [cachedOption]
    );
    return;
  }

  try {
    const item = await metroStationsApi.show(value);
    const option = {
      value: item.id,
      label: buildMetroStationLabel(item),
      color: item.metro_line?.color ?? null,
    };
    metroStationOptionCache.putMany([option]);
    metroStationOptions.value[locationIndex] = upsertAsyncSelectOptions(
      metroStationOptions.value[locationIndex] || [],
      [option]
    );
  } catch {
    const option = { value, label: value };
    metroStationOptionCache.putMany([option]);
    metroStationOptions.value[locationIndex] = upsertAsyncSelectOptions(
      metroStationOptions.value[locationIndex] || [],
      [option]
    );
  }
};

const hydrateSelectedLocationOptions = async (locationIndex: number) => {
  const location = model.value[locationIndex];
  if (!location) {
    return;
  }

  await ensureSelectedCountryOption(location.country_id);
  await ensureSelectedRegionOption(locationIndex);
  await ensureSelectedCityOption(locationIndex, location.city_id);

  if (!location.country_id) {
    regionOptions.value[locationIndex] = [];
    cityOptions.value[locationIndex] = [];
    districtOptions.value[locationIndex] = [];
    metroStationOptions.value[locationIndex] = [];
    return;
  }

  if (!location.region_id && !location.city_id) {
    cityOptions.value[locationIndex] = [];
    districtOptions.value[locationIndex] = [];
    metroStationOptions.value[locationIndex] = [];
    return;
  }

  if (!location.city_id) {
    districtOptions.value[locationIndex] = [];
    metroStationOptions.value[locationIndex] = [];
    return;
  }

  await ensureSelectedDistrictOption(locationIndex);

  for (const connection of location.metro_connections || []) {
    await ensureSelectedMetroStationOption(locationIndex, connection.metro_station_id);
  }
};

const buildLocationHydrationState = (location: MutableLocation): LocationHydrationState => ({
  country_id: String(location.country_id || ''),
  region_id: String(location.region_id || ''),
  city_id: String(location.city_id || ''),
  district_id: String(location.district_id || ''),
  metro_station_ids: (location.metro_connections || []).map((item) =>
    String(item.metro_station_id || '')
  ),
});

const areHydrationStatesEqual = (
  left: LocationHydrationState | undefined,
  right: LocationHydrationState | undefined
): boolean => {
  if (!left || !right) {
    return false;
  }

  if (
    left.country_id !== right.country_id ||
    left.region_id !== right.region_id ||
    left.city_id !== right.city_id ||
    left.district_id !== right.district_id ||
    left.metro_station_ids.length !== right.metro_station_ids.length
  ) {
    return false;
  }

  return left.metro_station_ids.every((value, index) => value === right.metro_station_ids[index]);
};

const addLocation = () => {
  model.value.push(createEmptyLocation());
  regionOptions.value.push([]);
  cityOptions.value.push([]);
  districtOptions.value.push([]);
  metroStationOptions.value.push([]);
};

const removeLocation = (locationIndex: number) => {
  model.value.splice(locationIndex, 1);
  regionOptions.value.splice(locationIndex, 1);
  cityOptions.value.splice(locationIndex, 1);
  districtOptions.value.splice(locationIndex, 1);
  metroStationOptions.value.splice(locationIndex, 1);
};

const addMetroConnection = (locationIndex: number) => {
  const location = model.value[locationIndex];
  if (!location) {
    return;
  }

  location.metro_connections = location.metro_connections || [];
  location.metro_connections.push(...createMetroConnectionPair(getNewMetroGroupKey()));
};

const removeMetroGroup = (locationIndex: number, groupKey: string) => {
  const location = model.value[locationIndex];
  if (!location?.metro_connections) {
    return;
  }

  const connections = location.metro_connections as LocalMetroConnection[];
  location.metro_connections = connections.filter(
    (connection, index) => getMetroConnectionGroupKey(connection, index) !== groupKey
  );
};

type ResetLevel = 'country' | 'region' | 'city';

const clearOptionBuckets = (
  locationIndex: number,
  options: Array<'region' | 'city' | 'district' | 'metro'>
) => {
  if (options.includes('region')) {
    regionOptions.value[locationIndex] = [];
  }
  if (options.includes('city')) {
    cityOptions.value[locationIndex] = [];
  }
  if (options.includes('district')) {
    districtOptions.value[locationIndex] = [];
  }
  if (options.includes('metro')) {
    metroStationOptions.value[locationIndex] = [];
  }
};

const resetLocationDependents = (locationIndex: number, level: ResetLevel) => {
  const location = model.value[locationIndex];
  if (!location) {
    return;
  }

  if (level === 'country') {
    location.region_id = null;
    location.city_id = null;
    location.district_id = null;
    location.metro_connections = [];
    clearOptionBuckets(locationIndex, ['region', 'city', 'district', 'metro']);
    return;
  }

  if (level === 'region') {
    location.city_id = null;
    location.district_id = null;
    location.metro_connections = [];
    clearOptionBuckets(locationIndex, ['city', 'district', 'metro']);
    return;
  }

  location.district_id = null;
  location.metro_connections = [];
  clearOptionBuckets(locationIndex, ['district', 'metro']);
};

const updateMetroStationForGroup = (
  locationIndex: number,
  groupKey: string,
  metroStationId: string
) => {
  const connections = getLocationMetroConnections(locationIndex);

  connections.forEach((connection, index) => {
    if (getMetroConnectionGroupKey(connection, index) === groupKey) {
      connection.metro_station_id = metroStationId;
    }
  });
};

const updateMetroDurationForGroup = (
  locationIndex: number,
  groupKey: string,
  mode: OrganizationLocationTravelMode,
  value: string
) => {
  const location = model.value[locationIndex];
  const connections = getLocationMetroConnections(locationIndex);
  const normalized = value.trim();
  const parsed = Number.parseInt(normalized, 10);
  const durationMinutes = Number.isFinite(parsed) && parsed > 0 ? parsed : null;
  let updated = false;

  connections.forEach((connection, index) => {
    if (
      getMetroConnectionGroupKey(connection, index) === groupKey &&
      connection.travel_mode === mode
    ) {
      connection.duration_minutes = durationMinutes;
      updated = true;
    }
  });

  if (!updated && location?.metro_connections && durationMinutes !== null) {
    const group = getMetroConnectionGroups(locationIndex).find((item) => item.key === groupKey);
    location.metro_connections.push({
      _group_key: groupKey,
      metro_station_id: group?.metro_station_id || '',
      travel_mode: mode,
      duration_minutes: durationMinutes,
    } as LocalMetroConnection);
  }
};

const onCountryChanged = async (locationIndex: number) => {
  const location = model.value[locationIndex];
  if (!location) {
    return;
  }

  resetLocationDependents(locationIndex, 'country');
  await ensureSelectedCountryOption(location.country_id);
};

const onRegionChanged = async (locationIndex: number) => {
  if (!model.value[locationIndex]) {
    return;
  }

  resetLocationDependents(locationIndex, 'region');
  await ensureSelectedRegionOption(locationIndex);
};

const onCityChanged = async (locationIndex: number) => {
  const location = model.value[locationIndex];
  if (!location) {
    return;
  }

  resetLocationDependents(locationIndex, 'city');
  await ensureSelectedCityOption(locationIndex, location.city_id);
};

const updateNumericField = (locationIndex: number, field: 'lat' | 'lng', value: string) => {
  const location = model.value[locationIndex];
  if (!location) {
    return;
  }

  const normalized = value.trim();
  if (!normalized) {
    location[field] = null;
    return;
  }

  const parsed = Number(normalized);
  location[field] = Number.isFinite(parsed) ? parsed : null;
};

const onCountrySearch = (query: string) => {
  countrySearch.schedule(() => {
    loadCountryOptions(query);
  });
};

const onRegionSearch = (locationIndex: number, query: string) => {
  regionSearch.schedule(locationIndex, () => {
    loadRegionOptions(locationIndex, query);
  });
};

const onCitySearch = (locationIndex: number, query: string) => {
  citySearch.schedule(locationIndex, () => {
    loadCityOptions(locationIndex, query);
  });
};

const onDistrictSearch = (locationIndex: number, query: string) => {
  districtSearch.schedule(locationIndex, () => {
    loadDistrictOptions(locationIndex, query);
  });
};

const onMetroSearch = (locationIndex: number, query: string) => {
  metroSearch.schedule(locationIndex, () => {
    loadMetroStationOptions(locationIndex, query);
  });
};

onMounted(async () => {
  normalizeLocationKeys();
  syncOptionBuckets();
  await loadCountryOptions();
  await Promise.all(model.value.map((_, index) => hydrateSelectedLocationOptions(index)));
});

watch(
  () => model.value.map((location) => buildLocationHydrationState(location)),
  async (currentStates, previousStates) => {
    normalizeLocationKeys();
    syncOptionBuckets();
    const changedIndexes = currentStates.reduce<number[]>((indexes, state, index) => {
      if (!areHydrationStatesEqual(state, previousStates?.[index])) {
        indexes.push(index);
      }

      return indexes;
    }, []);

    if (changedIndexes.length === 0) {
      return;
    }

    await Promise.all(changedIndexes.map((index) => hydrateSelectedLocationOptions(index)));
  },
  { immediate: false }
);

onBeforeUnmount(() => {
  countrySearch.clear();
  regionSearch.clearAll();
  citySearch.clearAll();
  districtSearch.clearAll();
  metroSearch.clearAll();
});
</script>
