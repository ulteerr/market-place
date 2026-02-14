<template>
  <section class="users-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.users.new.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.users.new.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.first_name"
          :label="t('admin.users.new.fields.firstName')"
          required
          :disabled="saving"
          :error="fieldErrors.first_name"
        />
        <UiInput
          v-model="form.last_name"
          :label="t('admin.users.new.fields.lastName')"
          required
          :disabled="saving"
          :error="fieldErrors.last_name"
        />
        <UiInput
          v-model="form.middle_name"
          :label="t('admin.users.new.fields.middleName')"
          :disabled="saving"
          :error="fieldErrors.middle_name"
        />
        <UiInput
          v-model="form.email"
          preset="email"
          :label="t('admin.users.new.fields.email')"
          required
          :disabled="saving"
          :error="fieldErrors.email"
        />
        <UiInput
          v-model="form.phone"
          preset="phone"
          :label="t('admin.users.new.fields.phone')"
          :disabled="saving"
          :error="fieldErrors.phone"
        />
        <UiInput
          v-model="form.password"
          preset="password"
          password-toggle
          :label="t('admin.users.new.fields.password')"
          required
          :disabled="saving"
          :error="fieldErrors.password"
        />
        <UiInput
          v-model="form.password_confirmation"
          preset="password"
          password-toggle
          :label="t('admin.users.new.fields.passwordConfirmation')"
          required
          :disabled="saving"
        />

        <UiSelect
          v-model="form.roles"
          :label="t('admin.users.new.fields.roles')"
          :options="roleOptions"
          :placeholder="t('admin.users.new.rolesPlaceholder')"
          multiple
          searchable
          :disabled="saving || loadingRoles"
          :error="fieldErrors.roles"
        />

        <UiImageDropzone
          v-model="avatarDraftFiles"
          :title="t('admin.users.new.fields.avatar')"
          :description="t('admin.files.avatarHint')"
          :browse-button-text="t('admin.users.new.fields.avatar')"
          accept="image/png,image/jpeg,image/webp"
          :multiple="false"
          :disabled="saving"
          @files-added="onAvatarFilesAdded"
        />
        <UiImageBlock
          v-if="avatarImages.length"
          title=""
          :images="avatarImages"
          :show-add-button="false"
          :removable="!saving"
          :remove-button-text="t('admin.actions.delete')"
          :empty-text="t('common.dash')"
          :caption-prefix="t('admin.profile.avatar.previewAlt')"
          @remove="clearAvatar"
        />
        <p v-if="avatarError" class="admin-error text-sm">{{ avatarError }}</p>

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex gap-2">
          <button
            type="submit"
            class="admin-button rounded-lg px-4 py-2 text-sm"
            :disabled="saving"
          >
            {{ saving ? t('admin.users.new.saving') : t('common.create') }}
          </button>
          <NuxtLink to="/admin/users" class="admin-button-secondary rounded-lg px-4 py-2 text-sm">{{
            t('common.cancel')
          }}</NuxtLink>
        </div>
      </form>
    </article>
  </section>
</template>

<script setup lang="ts">
import UiInput from '~/components/ui/FormControls/UiInput.vue';
import UiSelect from '~/components/ui/FormControls/UiSelect.vue';
import UiImageBlock from '~/components/ui/ImageBlock/UiImageBlock.vue';
import UiImageDropzone from '~/components/ui/ImageBlock/UiImageDropzone.vue';
import type { AdminRole } from '~/composables/useAdminRoles';
import type { CreateUserPayload } from '~/composables/useAdminUsers';
import {
  getApiErrorPayload,
  getApiErrorMessage,
  getFieldError,
} from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
});

const usersApi = useAdminUsers();
const rolesApi = useAdminRoles();

const saving = ref(false);
const loadingRoles = ref(false);
const formError = ref('');
const avatarError = ref('');
const roles = ref<AdminRole[]>([]);
const avatarDraftFiles = ref<File[]>([]);
const avatarFile = ref<File | null>(null);
const avatarPreviewUrl = ref<string | null>(null);

const form = reactive({
  first_name: '',
  last_name: '',
  middle_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  roles: [] as string[],
});

const fieldErrors = reactive<Record<string, string>>({
  first_name: '',
  last_name: '',
  middle_name: '',
  email: '',
  phone: '',
  password: '',
  roles: '',
});

const roleOptions = computed(() => {
  return roles.value.map((role) => ({
    label: role.label ? `${role.code} (${role.label})` : role.code,
    value: role.code,
  }));
});

const avatarImages = computed(() =>
  avatarPreviewUrl.value
    ? [
        {
          id: 'new-avatar',
          src: avatarPreviewUrl.value,
          alt: t('admin.profile.avatar.previewAlt'),
          caption: t('admin.profile.avatar.previewAlt'),
        },
      ]
    : []
);

const setAvatarDraft = (file: File | null) => {
  if (avatarPreviewUrl.value) {
    URL.revokeObjectURL(avatarPreviewUrl.value);
    avatarPreviewUrl.value = null;
  }

  avatarFile.value = file;
  avatarDraftFiles.value = file ? [file] : [];

  if (file) {
    avatarPreviewUrl.value = URL.createObjectURL(file);
  }
};

const resetErrors = () => {
  formError.value = '';
  avatarError.value = '';
  fieldErrors.first_name = '';
  fieldErrors.last_name = '';
  fieldErrors.middle_name = '';
  fieldErrors.email = '';
  fieldErrors.phone = '';
  fieldErrors.password = '';
  fieldErrors.roles = '';
};

const onAvatarFilesAdded = (files: File[]) => {
  avatarError.value = '';
  setAvatarDraft(files[0] ?? null);
};

const clearAvatar = () => {
  setAvatarDraft(null);
  avatarError.value = '';
};

watch(avatarDraftFiles, (nextFiles) => {
  const nextFile = nextFiles[0] ?? null;
  if (nextFile !== avatarFile.value) {
    setAvatarDraft(nextFile);
  }
});

const fetchRoles = async () => {
  loadingRoles.value = true;

  try {
    const page = await rolesApi.list({
      per_page: 100,
      sort_by: 'code',
      sort_dir: 'asc',
    });
    roles.value = page.data;
  } finally {
    loadingRoles.value = false;
  }
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const payload: CreateUserPayload = {
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      middle_name: form.middle_name.trim() || null,
      email: form.email.trim(),
      phone: form.phone.trim() || null,
      password: form.password,
      password_confirmation: form.password_confirmation,
      roles: [...form.roles],
      avatar: avatarFile.value,
    };

    await usersApi.create(payload);
    await navigateTo('/admin/users');
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.users.new.errors.create'));
    fieldErrors.first_name = getFieldError(payload.errors, 'first_name');
    fieldErrors.last_name = getFieldError(payload.errors, 'last_name');
    fieldErrors.middle_name = getFieldError(payload.errors, 'middle_name');
    fieldErrors.email = getFieldError(payload.errors, 'email');
    fieldErrors.phone = getFieldError(payload.errors, 'phone');
    fieldErrors.password = getFieldError(payload.errors, 'password');
    fieldErrors.roles =
      getFieldError(payload.errors, 'roles') || getFieldError(payload.errors, 'roles.0');
    avatarError.value = getFieldError(payload.errors, 'avatar');
  } finally {
    saving.value = false;
  }
};

onMounted(fetchRoles);

onBeforeUnmount(() => {
  if (avatarPreviewUrl.value) {
    URL.revokeObjectURL(avatarPreviewUrl.value);
  }
});
</script>

<style lang="scss" scoped src="./new.scss"></style>
