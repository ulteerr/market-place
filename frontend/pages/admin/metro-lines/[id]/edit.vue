<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">Редактирование ветки метро</h2>
      <p class="admin-muted mt-2 text-sm">Обновление /api/admin/geo/metro-lines/:id</p>
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
        <UiInput v-model="form.color" label="Color" :disabled="saving" :error="fieldErrors.color" />
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
            :to="`/admin/metro-lines/${route.params.id}`"
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
import type { UpdateMetroLinePayload } from '~/composables/useAdminMetroLines';
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
const api = useAdminMetroLines();
const loading = ref(false);
const loadError = ref('');
const saving = ref(false);
const formError = ref('');

const form = reactive({
  name: '',
  external_id: '',
  line_id: '',
  color: '',
  city_id: '',
  source: '',
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
    form.color = item.color || '';
    form.city_id = item.city_id;
    form.source = item.source;
  } catch (error) {
    loadError.value = getApiErrorMessage(error, 'Не удалось загрузить ветку метро.');
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const id = String(route.params.id || '');
    const payload: UpdateMetroLinePayload = {
      name: form.name.trim(),
      external_id: form.external_id.trim() || null,
      line_id: form.line_id.trim() || null,
      color: form.color.trim() || null,
      city_id: form.city_id.trim(),
      source: form.source.trim(),
    };

    await api.update(id, payload);
    await navigateTo(`/admin/metro-lines/${id}`);
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, 'Не удалось обновить ветку метро.');
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

onMounted(fetchItem);
</script>

<style lang="scss" scoped src="../../roles/[id]/edit.scss"></style>
