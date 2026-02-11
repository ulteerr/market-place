<template>
  <section class="roles-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.roles.new.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.roles.new.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.code"
          :label="t('admin.roles.new.fields.code')"
          required
          :disabled="saving"
          :error="fieldErrors.code"
        />
        <UiInput
          v-model="form.label"
          :label="t('admin.roles.new.fields.label')"
          :disabled="saving"
          :error="fieldErrors.label"
        />

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.roles.new.saving') : t('common.create') }}
          </button>
          <NuxtLink to="/admin/roles" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">{{
            t('common.cancel')
          }}</NuxtLink>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput.vue';
import type { CreateRolePayload } from '~/composables/useAdminRoles';
import {
  getApiErrorPayload,
  getApiErrorMessage,
  getFieldError,
} from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
});

const rolesApi = useAdminRoles();

const saving = ref(false);
const formError = ref('');

const form = reactive({
  code: '',
  label: '',
});

const fieldErrors = reactive<Record<string, string>>({
  code: '',
  label: '',
});

const resetErrors = () => {
  formError.value = '';
  fieldErrors.code = '';
  fieldErrors.label = '';
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const payload: CreateRolePayload = {
      code: form.code.trim(),
      label: form.label.trim() || null,
    };

    await rolesApi.create(payload);
    await navigateTo('/admin/roles');
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.roles.new.errors.create'));
    fieldErrors.code = getFieldError(payload.errors, 'code');
    fieldErrors.label = getFieldError(payload.errors, 'label');
  } finally {
    saving.value = false;
  }
};
</script>

<style lang="scss" scoped src="./new.scss"></style>
