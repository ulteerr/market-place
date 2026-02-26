<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Новая ветка метро</h2>
      <p class="admin-muted mt-2 text-sm">Создание ветки в /api/admin/geo/metro-lines</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
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
          v-model="form.color"
          label="Color (#RRGGBB)"
          :disabled="saving"
          :error="fieldErrors.color"
        />
        <UiInput
          v-model="form.city_id"
          label="City ID"
          required
          :disabled="saving"
          :error="fieldErrors.city_id"
        />
        <UiInput
          v-model="form.source"
          label="Source"
          required
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
            {{ saving ? 'Сохраняем...' : 'Создать' }}
          </button>
          <NuxtLink
            to="/admin/metro-lines"
            class="admin-button-secondary rounded-lg px-4 py-2 text-sm"
            >Отмена</NuxtLink
          >
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput/UiInput.vue';
import type { CreateMetroLinePayload } from '~/composables/useAdminMetroLines';
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

const api = useAdminMetroLines();
const saving = ref(false);
const formError = ref('');

const form = reactive({
  name: '',
  external_id: '',
  line_id: '',
  color: '',
  city_id: '',
  source: 'manual',
});

const fieldErrors = reactive<Record<string, string>>({
  name: '',
  external_id: '',
  line_id: '',
  color: '',
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
    const payload: CreateMetroLinePayload = {
      name: form.name.trim(),
      external_id: form.external_id.trim() || null,
      line_id: form.line_id.trim() || null,
      color: form.color.trim() || null,
      city_id: form.city_id.trim(),
      source: form.source.trim(),
    };

    await api.create(payload);
    await navigateTo('/admin/metro-lines');
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, 'Не удалось создать ветку метро.');
    fieldErrors.name = getFieldError(payload.errors, 'name');
    fieldErrors.external_id = getFieldError(payload.errors, 'external_id');
    fieldErrors.line_id = getFieldError(payload.errors, 'line_id');
    fieldErrors.color = getFieldError(payload.errors, 'color');
    fieldErrors.city_id = getFieldError(payload.errors, 'city_id');
    fieldErrors.source = getFieldError(payload.errors, 'source');
  } finally {
    saving.value = false;
  }
};
</script>

<style lang="scss" scoped src="../roles/new.scss"></style>
