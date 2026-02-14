<template>
  <section class="profile-page mx-auto w-full max-w-3xl space-y-6">
    <div class="profile-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.profile.title') }}</h2>
      <p class="profile-muted mt-2 text-sm">{{ t('admin.profile.subtitle') }}</p>
    </div>

    <article class="profile-card rounded-2xl p-5 lg:p-6">
      <div class="mb-5 rounded-xl border border-[var(--border)] p-4">
        <p class="mb-2 text-sm font-medium">{{ t('admin.profile.avatar.title') }}</p>
        <p class="profile-muted mb-3 text-sm">{{ t('admin.files.avatarHint') }}</p>
        <UiImageBlock
          v-if="avatarImages.length"
          title=""
          :images="avatarImages"
          empty-text=""
          caption-prefix=""
          :remove-button-text="t('admin.actions.delete')"
          :show-add-button="false"
          :removable="!avatarUploading"
          @remove="onDeleteAvatar"
        />
        <UiImageDropzone
          v-model="avatarDraftFiles"
          :title="t('admin.profile.avatar.upload')"
          :description="t('admin.files.avatarHint')"
          :browse-button-text="t('admin.profile.avatar.upload')"
          :disabled="avatarUploading"
          accept="image/png,image/jpeg,image/webp"
          :multiple="false"
          @files-added="onAvatarFilesAdded"
        />
        <p v-if="avatarError" class="admin-error mt-2 text-sm">{{ avatarError }}</p>
      </div>

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

    <AdminChangeLogPanel
      model="profile"
      :entity-id="user?.id || null"
      @rolled-back="onProfileRolledBack"
    />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import UiInput from '~/components/ui/FormControls/UiInput.vue';
import UiImageBlock from '~/components/ui/ImageBlock/UiImageBlock.vue';
import UiImageDropzone from '~/components/ui/ImageBlock/UiImageDropzone.vue';
import {
  getApiErrorPayload,
  getApiErrorMessage,
  getFieldError,
} from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
});

const { user, refreshUser, updateProfile, uploadAvatar, deleteAvatar } = useAuth();

const saving = ref(false);
const formError = ref('');
const avatarUploading = ref(false);
const avatarError = ref('');
const avatarDraftFiles = ref<File[]>([]);

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

const avatarUrl = computed(() => user.value?.avatar?.url ?? null);
const avatarImages = computed(() =>
  avatarUrl.value
    ? [
        {
          id: user.value?.avatar?.id ?? 'avatar',
          src: avatarUrl.value,
          alt: t('admin.profile.avatar.previewAlt'),
          caption: t('admin.profile.avatar.previewAlt'),
        },
      ]
    : []
);

const resetErrors = () => {
  formError.value = '';
  fieldErrors.first_name = '';
  fieldErrors.last_name = '';
  fieldErrors.middle_name = '';
  fieldErrors.email = '';
};

const syncFormFromUser = () => {
  form.first_name = user.value?.first_name ?? '';
  form.last_name = user.value?.last_name ?? '';
  form.middle_name = user.value?.middle_name ?? '';
  form.email = user.value?.email ?? '';
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

const onAvatarFilesAdded = async (files: File[]) => {
  const file = files[0] ?? null;
  if (!file) {
    return;
  }

  avatarUploading.value = true;
  avatarError.value = '';

  try {
    await uploadAvatar(file);
  } catch (error) {
    avatarError.value = getApiErrorMessage(error, t('admin.profile.avatar.errors.upload'));
  } finally {
    avatarUploading.value = false;
    avatarDraftFiles.value = [];
  }
};

const onDeleteAvatar = async () => {
  avatarUploading.value = true;
  avatarError.value = '';

  try {
    await deleteAvatar();
  } catch (error) {
    avatarError.value = getApiErrorMessage(error, t('admin.profile.avatar.errors.delete'));
  } finally {
    avatarUploading.value = false;
  }
};

const onProfileRolledBack = async () => {
  await refreshUser();
  syncFormFromUser();
};
</script>

<style lang="scss" scoped src="./profile.scss"></style>
