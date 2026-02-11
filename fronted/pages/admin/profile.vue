<template>
  <section class="profile-page mx-auto w-full max-w-3xl space-y-6">
    <div class="profile-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.profile.title') }}</h2>
      <p class="profile-muted mt-2 text-sm">{{ t('admin.profile.subtitle') }}</p>
    </div>

    <article class="profile-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.first_name"
          :label="t('admin.profile.fields.firstName')"
          required
          :disabled="saving"
          :error="fieldErrors.first_name"
        />
        <UiInput
          v-model="form.last_name"
          :label="t('admin.profile.fields.lastName')"
          required
          :disabled="saving"
          :error="fieldErrors.last_name"
        />
        <UiInput
          v-model="form.middle_name"
          :label="t('admin.profile.fields.middleName')"
          :disabled="saving"
          :error="fieldErrors.middle_name"
        />
        <UiInput
          v-model="form.email"
          preset="email"
          :label="t('admin.profile.fields.email')"
          required
          :disabled="saving"
          :error="fieldErrors.email"
        />

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.profile.saving') : t('common.save') }}
          </button>
          <NuxtLink to="/admin" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">{{
            t('common.cancel')
          }}</NuxtLink>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput.vue';
import {
  getApiErrorPayload,
  getApiErrorMessage,
  getFieldError,
} from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
});

const { user, updateProfile } = useAuth();

const saving = ref(false);
const formError = ref('');

const form = reactive({
  first_name: user.value?.first_name ?? '',
  last_name: user.value?.last_name ?? '',
  middle_name: user.value?.middle_name ?? '',
  email: user.value?.email ?? '',
});

const fieldErrors = reactive<Record<string, string>>({
  first_name: '',
  last_name: '',
  middle_name: '',
  email: '',
});

const resetErrors = () => {
  formError.value = '';
  fieldErrors.first_name = '';
  fieldErrors.last_name = '';
  fieldErrors.middle_name = '';
  fieldErrors.email = '';
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    await updateProfile({
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      middle_name: form.middle_name.trim() || null,
      email: form.email.trim(),
    });
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.profile.errors.update'));
    fieldErrors.first_name = getFieldError(payload.errors, 'first_name');
    fieldErrors.last_name = getFieldError(payload.errors, 'last_name');
    fieldErrors.middle_name = getFieldError(payload.errors, 'middle_name');
    fieldErrors.email = getFieldError(payload.errors, 'email');
  } finally {
    saving.value = false;
  }
};
</script>

<style lang="scss" scoped src="./profile.scss"></style>
