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
        <AdminUserFormFields
          mode="edit"
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

    <AdminChangeLogPanel
      v-if="canReadChangeLog"
      model="user"
      :entity-id="String(route.params.id || '')"
      @rolled-back="onUserRolledBack"
    />

    <AdminActionLogPanel model="user" :entity-id="String(route.params.id || '')" />
  </section>
</template>

<script setup lang="ts">
import AdminChangeLogPanel from '~/components/admin/ChangeLog/AdminChangeLogPanel.vue';
import AdminUserFormFields from '~/components/admin/Users/AdminUserFormFields.vue';
import type { UpdateUserPayload } from '~/composables/useAdminUsers';
import { getApiErrorPayload, getApiErrorMessage } from '~/composables/useAdminCrudCommon';
const { t } = useI18n();

definePageMeta({
  layout: 'admin',
  middleware: 'admin-permission',
  permission: 'admin.users.update',
});

const route = useRoute();
const usersApi = useAdminUsers();
const { hasPermission } = usePermissions();
const canReadChangeLog = computed(() => hasPermission('admin.changelog.read'));

const loading = ref(false);
const loadError = ref('');
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
  avatarDeleted,
  normalizeUserRoleCodes,
  normalizeAssignableRoles,
  actorMaxRoleLevel,
  clearErrors,
  applyApiErrors,
  onAvatarFilesAdded,
  clearAvatar,
  onOverrideAllowToggle,
  onOverrideDenyToggle,
  fetchFormOptions,
  setExistingAvatar,
} = useAdminUserForm({
  mode: 'edit',
  initialRoles: [],
});

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
    const userRoleCodes = normalizeUserRoleCodes(user.roles || []);

    const targetMaxRoleLevel = getHighestRoleLevelFromCodes(userRoleCodes);
    if (targetMaxRoleLevel > actorMaxRoleLevel.value) {
      loadError.value = t('admin.users.edit.errors.cannotEditHigherRole');
      return;
    }

    form.first_name = user.first_name || '';
    form.last_name = user.last_name || '';
    form.middle_name = user.middle_name || '';
    form.gender = user.gender || '';
    form.email = user.email || '';
    form.phone = user.phone || '';
    form.roles = normalizeAssignableRoles(userRoleCodes);
    form.permission_overrides_allow = [...(user.permission_overrides?.allow || [])];
    form.permission_overrides_deny = [...(user.permission_overrides?.deny || [])];
    setExistingAvatar(user.avatar ? { id: user.avatar.id, url: user.avatar.url } : null);
  } catch (error) {
    loadError.value = getApiErrorMessage(error, t('admin.users.edit.errors.load'));
  } finally {
    loading.value = false;
  }
};

const submitForm = async () => {
  saving.value = true;
  clearErrors();

  try {
    const id = String(route.params.id || '');
    const safeRoles = normalizeAssignableRoles(form.roles);
    const payload: UpdateUserPayload = {
      first_name: form.first_name.trim(),
      last_name: form.last_name.trim(),
      middle_name: form.middle_name.trim() || null,
      gender: form.gender || null,
      email: form.email.trim(),
      phone: form.phone.trim() || null,
      roles: [...safeRoles],
      permission_overrides: showPermissionOverrides.value
        ? {
            allow: [...form.permission_overrides_allow],
            deny: [...form.permission_overrides_deny],
          }
        : undefined,
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
    applyApiErrors(payload);
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await Promise.all([fetchUser(), fetchFormOptions()]);
});

const onUserRolledBack = async () => {
  await fetchUser();
};
</script>

<style lang="scss" scoped src="./edit.scss"></style>
