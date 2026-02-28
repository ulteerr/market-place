<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.geo.regions.edit.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.geo.regions.edit.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <form v-else class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.name"
          :label="t('admin.geo.regions.fields.name')"
          required
          :disabled="saving"
          :error="fieldErrors.name"
        />
        <UiInput
          v-model="form.country_id"
          :label="t('admin.geo.regions.fields.countryId')"
          :disabled="saving"
          :error="fieldErrors.country_id"
        />

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.geo.regions.edit.saving') : t('common.save') }}
          </button>
          <NuxtLink
            :to="`/admin/geo/regions/${route.params.id}`"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >{{ t('common.cancel') }}</NuxtLink
          >
        </div>
      </form>
    </article>

    <AdminChangeLogPanel model="geo_region" :entity-id="String(route.params.id || '')" />

    <AdminActionLogPanel model="geo_region" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import type { UpdateGeoRegionPayload } from '~/composables/useAdminGeoRegions';
import {
  getApiErrorMessage,
  getApiErrorPayload,
  getFieldError,
} from '~/composables/useAdminCrudCommon';

const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.panel.access',
});

const route = useRoute();
const api = useAdminGeoRegions();
const loading = ref(false);
const loadError = ref('');
const saving = ref(false);
const formError = ref('');
const form = reactive({ name: '', country_id: '' });
const fieldErrors = reactive<Record<string, string>>({ name: '', country_id: '' });

const resetErrors = () => {
  formError.value = '';
  Object.keys(fieldErrors).forEach((key) => {
    fieldErrors[key] = '';
  });
};

const fetchItem = async () => {
  const id = String(route.params.id || '');
  if (!id) {
    loadError.value = t('admin.geo.regions.edit.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';
  try {
    const item = await api.show(id);
    form.name = item.name;
    form.country_id = item.country_id;
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.geo.regions.edit.errors.load'));
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const id = String(route.params.id || '');
    const payload: UpdateGeoRegionPayload = {
      name: form.name.trim(),
      country_id: form.country_id.trim(),
    };

    await api.update(id, payload);
    await navigateTo(`/admin/geo/regions/${id}`);
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.geo.regions.edit.errors.update'));
    fieldErrors.name = getFieldError(payload.errors, 'name');
    fieldErrors.country_id = getFieldError(payload.errors, 'country_id');
  } finally {
    saving.value = false;
  }
};

onMounted(fetchItem);
</script>

<style lang="scss" scoped src="../../../roles/[id]/edit.scss"></style>
