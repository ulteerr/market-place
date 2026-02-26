<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Редактирование станции метро</h2>
      <p class="admin-muted mt-2 text-sm">Обновление /api/admin/geo/metro-stations/:id</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">Загрузка...</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <form v-else class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.name"
          label="Название"
          required
          :disabled="saving"
          :error="fieldErrors.name"
        />
        <UiInput
          v-model="form.external_id"
          label="External ID"
          :disabled="saving"
          :error="fieldErrors.external_id"
        />
        <UiInput
          v-model="form.line_id"
          label="Line ID"
          :disabled="saving"
          :error="fieldErrors.line_id"
        />
        <UiInput
          v-model="form.geo_lat"
          label="Geo lat"
          :disabled="saving"
          :error="fieldErrors.geo_lat"
        />
        <UiInput
          v-model="form.geo_lon"
          label="Geo lon"
          :disabled="saving"
          :error="fieldErrors.geo_lon"
        />
        <UiCheckbox v-model="form.is_closed" label="Станция закрыта" :disabled="saving" />
        <UiInput
          v-model="form.metro_line_id"
          label="Metro Line ID"
          :disabled="saving"
          :error="fieldErrors.metro_line_id"
        />
        <UiInput
          v-model="form.city_id"
          label="City ID"
          :disabled="saving"
          :error="fieldErrors.city_id"
        />
        <UiInput
          v-model="form.source"
          label="Source"
          :disabled="saving"
          :error="fieldErrors.source"
        />

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? 'Сохраняем...' : 'Сохранить' }}
          </button>
          <NuxtLink
            :to="`/admin/metro-stations/${route.params.id}`"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >Отмена</NuxtLink
          >
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiCheckbox from '~/components/ui/FormControls/UiCheckbox/UiCheckbox.vue';
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import type { UpdateMetroStationPayload } from '~/composables/useAdminMetroStations';
import {
  getApiErrorPayload,
  getApiErrorMessage,
  getFieldError,
} from '~/composables/useAdminCrudCommon';

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.panel.access',
});

const route = useRoute();
const api = useAdminMetroStations();
const loading = ref(false);
const loadError = ref('');
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
  source: '',
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

const fetchItem = async () => {
  const id = String(route.params.id || '');
  if (!id) {
    loadError.value = 'Некорректный ID';
    return;
  }

  loading.value = true;
  loadError.value = '';
  try {
    const item = await api.show(id);
    form.name = item.name;
    form.external_id = item.external_id || '';
    form.line_id = item.line_id || '';
    form.geo_lat = item.geo_lat === null || item.geo_lat === undefined ? '' : String(item.geo_lat);
    form.geo_lon = item.geo_lon === null || item.geo_lon === undefined ? '' : String(item.geo_lon);
    form.is_closed = Boolean(item.is_closed);
    form.metro_line_id = item.metro_line_id;
    form.city_id = item.city_id;
    form.source = item.source;
  } catch (error) {
    loadError.value = getApiErrorMessage(error, 'Не удалось загрузить станцию метро.');
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const id = String(route.params.id || '');
    const payload: UpdateMetroStationPayload = {
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

    await api.update(id, payload);
    await navigateTo(`/admin/metro-stations/${id}`);
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, 'Не удалось обновить станцию метро.');
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

onMounted(fetchItem);
</script>

<style lang="scss" scoped src="../../roles/[id]/edit.scss"></style>
