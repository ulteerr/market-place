<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.metro.stations.new.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.metro.stations.new.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.name"
          :label="t('admin.metro.stations.fields.name')"
          required
          :disabled="saving"
          :error="fieldErrors.name"
        />
        <UiInput
          v-model="form.external_id"
          :label="t('admin.metro.stations.fields.externalId')"
          :disabled="saving"
          :error="fieldErrors.external_id"
        />
        <UiInput
          v-model="form.line_id"
          :label="t('admin.metro.stations.fields.lineId')"
          :disabled="saving"
          :error="fieldErrors.line_id"
        />
        <UiInput
          v-model="form.geo_lat"
          :label="t('admin.metro.stations.fields.geoLat')"
          :disabled="saving"
          :error="fieldErrors.geo_lat"
        />
        <UiInput
          v-model="form.geo_lon"
          :label="t('admin.metro.stations.fields.geoLon')"
          :disabled="saving"
          :error="fieldErrors.geo_lon"
        />
        <UiSwitch
          v-model="form.is_closed"
          :label="t('admin.metro.stations.fields.isClosed')"
          :disabled="saving"
        />
        <UiSelect
          v-model="form.metro_line_id"
          :label="t('admin.metro.stations.fields.metroLine')"
          :options="metroLineOptions"
          :placeholder="t('admin.metro.stations.new.metroLinePlaceholder')"
          searchable
          required
          :disabled="saving"
          :error="fieldErrors.metro_line_id"
          @search="onMetroLineSearch"
        />
        <UiSelect
          v-model="form.city_id"
          :label="t('admin.metro.stations.fields.cityId')"
          :options="cityOptions"
          :placeholder="t('admin.metro.stations.new.cityPlaceholder')"
          searchable
          required
          :disabled="saving"
          :error="fieldErrors.city_id"
          @search="onCitySearch"
        />
        <UiInput
          v-model="form.source"
          :label="t('admin.metro.stations.fields.source')"
          required
          :disabled="saving"
          :error="fieldErrors.source"
        />

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex flex-wrap gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.metro.stations.new.saving') : t('common.create') }}
          </button>
          <NuxtLink
            to="/admin/metro-stations"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >{{ t('common.cancel') }}</NuxtLink
          >
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect/UiSelect.vue';
import UiSwitch from '~/components/ui/FormControls/UiSwitch/UiSwitch.vue';
import { useAdminMetroStationSelectOptions } from '~/composables/useAdminMetroStationSelectOptions';
import type { CreateMetroStationPayload } from '~/composables/useAdminMetroStations';
import {
  getApiErrorPayload,
  getApiErrorMessage,
  getFieldError,
} from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.panel.access',
});

const api = useAdminMetroStations();
const { metroLineOptions, cityOptions, loadOptions, onMetroLineSearch, onCitySearch } =
  useAdminMetroStationSelectOptions();
const saving = ref(false);
const formError = ref('');

const form = reactive({
  name: '',
  external_id: '',
  line_id: '',
  geo_lat: '',
  geo_lon: '',
  is_closed: false,
  metro_line_id: '',
  city_id: '',
  source: 'manual',
});

const fieldErrors = reactive<Record<string, string>>({
  name: '',
  external_id: '',
  line_id: '',
  geo_lat: '',
  geo_lon: '',
  metro_line_id: '',
  city_id: '',
  source: '',
});

const resetErrors = () => {
  formError.value = '';
  Object.keys(fieldErrors).forEach((key) => {
    fieldErrors[key] = '';
  });
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const payload: CreateMetroStationPayload = {
      name: form.name.trim(),
      external_id: form.external_id.trim() || null,
      line_id: form.line_id.trim() || null,
      geo_lat: form.geo_lat.trim() === '' ? null : Number(form.geo_lat),
      geo_lon: form.geo_lon.trim() === '' ? null : Number(form.geo_lon),
      is_closed: form.is_closed,
      metro_line_id: form.metro_line_id.trim(),
      city_id: form.city_id.trim(),
      source: form.source.trim(),
    };

    await api.create(payload);
    await navigateTo('/admin/metro-stations');
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.metro.stations.new.errors.create'));
    fieldErrors.name = getFieldError(payload.errors, 'name');
    fieldErrors.external_id = getFieldError(payload.errors, 'external_id');
    fieldErrors.line_id = getFieldError(payload.errors, 'line_id');
    fieldErrors.geo_lat = getFieldError(payload.errors, 'geo_lat');
    fieldErrors.geo_lon = getFieldError(payload.errors, 'geo_lon');
    fieldErrors.metro_line_id = getFieldError(payload.errors, 'metro_line_id');
    fieldErrors.city_id = getFieldError(payload.errors, 'city_id');
    fieldErrors.source = getFieldError(payload.errors, 'source');
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadOptions('');
});
</script>

<style lang="scss" scoped src="../roles/new.scss"></style>
