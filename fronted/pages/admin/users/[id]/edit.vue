<template>
  <section class="users-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.users.edit.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.users.edit.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <p v-if="loading" class="admin-muted text-sm">{{ t('common.loading') }}</p>
      <p v-else-if="loadError" class="admin-error text-sm">{{ loadError }}</p>

      <form v-else class="space-y-3" @submit.prevent="submitForm">
        <UiInput
          v-model="form.first_name"
          :label="t('admin.users.edit.fields.firstName')"
          required
          :disabled="saving"
          :error="fieldErrors.first_name"
        />
        <UiInput
          v-model="form.last_name"
          :label="t('admin.users.edit.fields.lastName')"
          required
          :disabled="saving"
          :error="fieldErrors.last_name"
        />
        <UiInput
          v-model="form.middle_name"
          :label="t('admin.users.edit.fields.middleName')"
          :disabled="saving"
          :error="fieldErrors.middle_name"
        />
        <UiInput
          v-model="form.email"
          preset="email"
          :label="t('admin.users.edit.fields.email')"
          required
          :disabled="saving"
          :error="fieldErrors.email"
        />
        <UiInput
          v-model="form.phone"
          preset="phone"
          :label="t('admin.users.edit.fields.phone')"
          :disabled="saving"
          :error="fieldErrors.phone"
        />
        <UiInput
          v-model="form.password"
          preset="password"
          password-toggle
          :label="t('admin.users.edit.fields.newPassword')"
          :disabled="saving"
          :error="fieldErrors.password"
        />
        <UiInput
          v-model="form.password_confirmation"
          preset="password"
          password-toggle
          :label="t('admin.users.edit.fields.newPasswordConfirmation')"
          :disabled="saving"
        />

        <UiSelect
          v-model="form.roles"
          :label="t('admin.users.edit.fields.roles')"
          :options="roleOptions"
          :placeholder="t('admin.users.edit.rolesPlaceholder')"
          multiple
          searchable
          :disabled="saving || loadingRoles"
          :error="fieldErrors.roles"
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
        <UiImageDropzone
          v-model="avatarDraftFiles"
          :title="t('admin.users.edit.fields.avatar')"
          :description="t('admin.users.edit.avatarHint')"
          :browse-button-text="t('admin.users.edit.fields.avatar')"
          accept="image/png,image/jpeg,image/webp"
          :multiple="false"
          :disabled="saving"
          @files-added="onAvatarFilesAdded"
        />
        <p v-if="avatarError" class="admin-error text-sm">{{ avatarError }}</p>

        <p v-if="formError" class="admin-error text-sm">{{ formError }}</p>

        <div class="flex flex-col gap-2 sm:flex-row">
          <button
            type="submit"
            class="admin-button w-full rounded-lg px-4 py-2 text-sm sm:w-auto"
            :disabled="saving"
          >
            {{ saving ? t('admin.users.edit.saving') : t('common.save') }}
          </button>
          <NuxtLink
            :to="`/admin/users/${route.params.id}`"
            class="admin-button-secondary w-full rounded-lg px-4 py-2 text-center text-sm sm:w-auto"
            >{{ t('common.cancel') }}</NuxtLink
          >
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
import type { UpdateUserPayload } from '~/composables/useAdminUsers';
import {
  getApiErrorPayload,
  getApiErrorMessage,
  getFieldError,
} from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
});

const route = useRoute();
const usersApi = useAdminUsers();
const rolesApi = useAdminRoles();

const loading = ref(false);
const loadError = ref('');
const saving = ref(false);
const loadingRoles = ref(false);
const formError = ref('');
const avatarError = ref('');
const roles = ref<AdminRole[]>([]);
const avatarDraftFiles = ref<File[]>([]);
const avatarFile = ref<File | null>(null);
const avatarPreviewUrl = ref<string | null>(null);
const avatarDeleted = ref(false);
const existingAvatar = ref<{ id: string; url: string } | null>(null);

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
          id: 'draft-avatar',
          src: avatarPreviewUrl.value,
          alt: t('admin.profile.avatar.previewAlt'),
          caption: t('admin.profile.avatar.previewAlt'),
        },
      ]
    : existingAvatar.value
      ? [
          {
            id: existingAvatar.value.id,
            src: existingAvatar.value.url,
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
  avatarDeleted.value = false;
  setAvatarDraft(files[0] ?? null);
};

const clearAvatar = () => {
  if (avatarFile.value) {
    setAvatarDraft(null);
    return;
  }

  if (existingAvatar.value) {
    existingAvatar.value = null;
    avatarDeleted.value = true;
  }
};

watch(avatarDraftFiles, (nextFiles) => {
  const nextFile = nextFiles[0] ?? null;
  if (nextFile !== avatarFile.value) {
    avatarDeleted.value = false;
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

const fetchUser = async () => {
  const id = String(route.params.id || '');

  if (!id) {
    loadError.value = t('admin.users.edit.errors.invalidId');
    return;
  }

  loading.value = true;
  loadError.value = '';

  try {
    const user = await usersApi.show(id);
    avatarDeleted.value = false;
    form.first_name = user.first_name || '';
    form.last_name = user.last_name || '';
    form.middle_name = user.middle_name || '';
    form.email = user.email || '';
    form.phone = user.phone || '';
    form.roles = [...(user.roles || [])];
    existingAvatar.value = user.avatar ? { id: user.avatar.id, url: user.avatar.url } : null;
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.users.edit.errors.load'));
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  saving.value = true;
  resetErrors();

  try {
    const id = String(route.params.id || '');
    const payload: UpdateUserPayload = {
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      middle_name: form.middle_name.trim() || null,
      email: form.email.trim(),
      phone: form.phone.trim() || null,
      roles: [...form.roles],
    };

    if (avatarFile.value) {
      payload.avatar = avatarFile.value;
    } else if (avatarDeleted.value) {
      payload.avatar_delete = true;
    }

    if (form.password.trim()) {
      payload.password = form.password;
      payload.password_confirmation = form.password_confirmation;
    }

    await usersApi.update(id, payload);
    await navigateTo(`/admin/users/${id}`);
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.users.edit.errors.update'));
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

onMounted(async () => {
  await Promise.all([fetchUser(), fetchRoles()]);
});

onBeforeUnmount(() => {
  if (avatarPreviewUrl.value) {
    URL.revokeObjectURL(avatarPreviewUrl.value);
  }
});
</script>

<style lang="scss" scoped src="./edit.scss"></style>
