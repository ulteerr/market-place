<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.geo.countries.new.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.geo.countries.new.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.name"
          :label="t('admin.geo.countries.fields.name')"
          required
          :disabled="saving"
          :error="fieldErrors.name"
        />
        <UiInput
          v-model="form.iso_code"
          :label="t('admin.geo.countries.fields.isoCode')"
          :disabled="saving"
          :error="fieldErrors.iso_code"
        />

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.geo.countries.new.saving') : t('common.create') }}
          </button>
          <NuxtLink
            to="/admin/geo/countries"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
          >
            {{ t('common.cancel') }}
          </NuxtLink>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import type { CreateGeoCountryPayload } from '~/composables/useAdminGeoCountries';
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

const api = useAdminGeoCountries();
const saving = ref(false);
const formError = ref('');

const form = reactive({
  name: '',
  iso_code: '',
});

const fieldErrors = reactive<Record<string, string>>({
  name: '',
  iso_code: '',
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
    const payload: CreateGeoCountryPayload = {
      name: form.name.trim(),
      iso_code: form.iso_code.trim() || null,
    };

    await api.create(payload);
    await navigateTo('/admin/geo/countries');
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.geo.countries.new.errors.create'));
    fieldErrors.name = getFieldError(payload.errors, 'name');
    fieldErrors.iso_code = getFieldError(payload.errors, 'iso_code');
  } finally {
    saving.value = false;
  }
};
</script>

<style lang="scss" scoped src="../../roles/new.scss"></style>
