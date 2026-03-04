<template>
  <section class="users-form-page mx-auto w-full max-w-3xl space-y-6">
    <div class="admin-card rounded-2xl p-6 lg:p-8">
      <h2 class="text-2xl font-semibold">{{ t('admin.users.new.title') }}</h2>
      <p class="admin-muted mt-2 text-sm">{{ t('admin.users.new.subtitle') }}</p>
    </div>

    <article class="admin-card rounded-2xl p-5 lg:p-6">
      <form class="space-y-3" @submit.prevent="submitForm">
        <AdminUserFormFields
          mode="create"
          :form="form"
          :field-errors="fieldErrors"
          :saving="saving"
          :loading-roles="loadingRoles"
          :loading-permissions="loadingPermissions"
          :gender-options="genderOptions"
          :role-options="roleOptions"
          :participant-role-code="PARTICIPANT_ROLE_CODE"
          :show-permission-overrides="showPermissionOverrides"
          :permissions-by-scope="permissionsByScope"
          :avatar-draft-files="avatarDraftFiles"
          :avatar-images="avatarImages"
          :avatar-error="avatarError"
          :form-error="formError"
          :resolve-permission-scope-label="permissionsApi.resolvePermissionScopeLabel"
          :resolve-permission-label="permissionsApi.resolvePermissionLabel"
          @update:avatar-draft-files="(files) => (avatarDraftFiles = files)"
          @avatar-files-added="onAvatarFilesAdded"
          @clear-avatar="clearAvatar"
          @override-allow="onOverrideAllowToggle"
          @override-deny="onOverrideDenyToggle"
        />

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
import AdminUserFormFields from '~/components/admin/Users/AdminUserFormFields.vue';
import type { CreateUserPayload } from '~/composables/useAdminUsers';
import { getApiErrorPayload, getApiErrorMessage } from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.users.create',
});

const usersApi = useAdminUsers();
const {
  permissionsApi,
  PARTICIPANT_ROLE_CODE,
  form,
  fieldErrors,
  formError,
  avatarError,
  saving,
  loadingRoles,
  loadingPermissions,
  genderOptions,
  roleOptions,
  showPermissionOverrides,
  permissionsByScope,
  avatarDraftFiles,
  avatarImages,
  avatarFile,
  normalizeAssignableRoles,
  clearErrors,
  applyApiErrors,
  onAvatarFilesAdded,
  clearAvatar,
  onOverrideAllowToggle,
  onOverrideDenyToggle,
  fetchFormOptions,
} = useAdminUserForm({
  mode: 'create',
});

const submitForm = async () => {
  saving.value = true;
  clearErrors();

  try {
    const safeRoles = normalizeAssignableRoles(form.roles);
    const payload: CreateUserPayload = {
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      middle_name: form.middle_name.trim() || null,
      gender: form.gender || null,
      email: form.email.trim(),
      phone: form.phone.trim() || null,
      password: form.password,
      password_confirmation: form.password_confirmation,
      roles: [...safeRoles],
      permission_overrides: showPermissionOverrides.value
        ? {
            allow: [...form.permission_overrides_allow],
            deny: [...form.permission_overrides_deny],
          }
        : undefined,
      avatar: avatarFile.value,
    };

    await usersApi.create(payload);
    await navigateTo('/admin/users');
  } catch (error) {
    const payload = getApiErrorPayload(error);
    formError.value = getApiErrorMessage(error, t('admin.users.new.errors.create'));
    applyApiErrors(payload);
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await fetchFormOptions();
});
</script>

<style lang="scss" scoped src="./new.scss"></style>
